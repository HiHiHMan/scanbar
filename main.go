package main

import (
	"context"
	"database/sql"
	"encoding/json"
	"errors"
	"fmt"
	"log"
	"net/http"
	"net/url"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"time"

	_ "github.com/microsoft/go-mssqldb"
	"github.com/joho/godotenv"
	"github.com/kardianos/service"
)

// Settings: edit these defaults here, or override them with values in the local conf.env file.
var (
	defaultDBHost     = "192.168.123.45"
	defaultDBPort     = "1433"
	defaultDBName     = "Inventory"
	defaultDBUser     = "" // Prefer setting DB_USER in conf.env
	defaultDBPassword = "" // Prefer setting DB_PASSWORD in conf.env
	defaultAPIAddr    = ":8080"
)

type app struct {
	db *sql.DB
}

type apiError struct {
	Error string `json:"error"`
}

// program implements service.Interface for Windows Service management.
type program struct {
	server *http.Server
	db     *sql.DB
}

func (p *program) Start(s service.Service) error {
	go p.run()
	return nil
}

func (p *program) run() {
	addr := env("API_ADDR", defaultAPIAddr)
	db, err := openDatabase()
	if err != nil {
		log.Fatalf("database configuration error: %v", err)
	}
	p.db = db

	ctx, cancel := context.WithTimeout(context.Background(), 8*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		log.Fatalf("cannot connect to SQL Server: %v", err)
	}

	a := &app{db: db}
	mux := http.NewServeMux()
	mux.HandleFunc("GET /api/health", a.health)
	mux.HandleFunc("GET /api/labels", a.labels)
	mux.HandleFunc("GET /api/labels/search", a.searchLabels)
	mux.HandleFunc("GET /api/label-print-list", a.labelPrintList)
	mux.HandleFunc("POST /api/label-print-list", a.manageLabelPrintList)
	mux.Handle("GET /style/", http.StripPrefix("/style/", http.FileServer(http.Dir("style"))))
	mux.Handle("GET /js/", http.StripPrefix("/js/", http.FileServer(http.Dir("js"))))
	mux.Handle("GET /font/", http.StripPrefix("/font/", http.FileServer(http.Dir("font"))))
	mux.HandleFunc("GET /", serveIndex)

	p.server = &http.Server{
		Addr:              addr,
		Handler:           securityHeaders(mux),
		ReadHeaderTimeout: 5 * time.Second,
		ReadTimeout:       15 * time.Second,
		WriteTimeout:      30 * time.Second,
		IdleTimeout:       60 * time.Second,
	}

	log.Printf("Scanbar running at http://localhost%s", addr)
	if err := p.server.ListenAndServe(); err != nil && !errors.Is(err, http.ErrServerClosed) {
		log.Fatalf("HTTP server error: %v", err)
	}
}

func (p *program) Stop(s service.Service) error {
	if p.server != nil {
		ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
		defer cancel()
		_ = p.server.Shutdown(ctx)
	}
	if p.db != nil {
		_ = p.db.Close()
	}
	log.Println("Scanbar service stopped.")
	return nil
}

func main() {
	// 1. ลองโหลด conf.env จากโฟลเดอร์ปัจจุบันก่อน (สำหรับตอนใช้ go run)
	_ = godotenv.Load("conf.env")

	// 2. จัดการเรื่อง Path ของไฟล์ .exe และโหลด conf.env จากโฟลเดอร์เดียวกัน (สำหรับตอนรันจริง / Service)
	if ex, err := os.Executable(); err == nil {
		exeDir := filepath.Dir(ex)
		envPath := filepath.Join(exeDir, "conf.env")
		if err := godotenv.Load(envPath); err == nil {
			log.Printf("โหลดไฟล์ conf.env สำเร็จจากพาธ: %s", envPath)
		}
		os.Chdir(exeDir)
	}

	// เช็คและกำหนดค่าพอร์ต API_ADDR หากยังไม่ได้กำหนด
	if os.Getenv("API_ADDR") == "" {
		os.Setenv("API_ADDR", defaultAPIAddr)
	}

	svcConfig := &service.Config{
		Name:        "Port4071_ScanBarService",
		DisplayName: "Port:4071-ScanBar Web Service",
		Description: "Port:4071-Inventory and Label Printing Web Service.",
	}

	prg := &program{}
	s, err := service.New(prg, svcConfig)
	if err != nil {
		log.Fatal(err)
	}

	if len(os.Args) > 1 {
		switch os.Args[1] {
		case "install":
			err = s.Install()
			if err != nil {
				log.Fatal(err)
			}
			fmt.Println("Service Installed Successfully")
			return

		case "uninstall", "remove":
			err = s.Uninstall()
			if err != nil {
				log.Fatal(err)
			}
			fmt.Println("Service Uninstalled Successfully")
			return

		case "start":
			err = s.Start()
			if err != nil {
				log.Fatal(err)
			}
			fmt.Println("Service Started Successfully")
			return

		case "stop":
			err = s.Stop()
			if err != nil {
				log.Fatal(err)
			}
			fmt.Println("Service Stopped Successfully")
			return
		}
	}

	err = s.Run()
	if err != nil {
		log.Fatal(err)
	}
}

func openDatabase() (*sql.DB, error) {
	host := strings.TrimSpace(env("DB_HOST", defaultDBHost))
	portText := env("DB_PORT", defaultDBPort)
	port, err := strconv.Atoi(portText)
	if err != nil || port < 1 || port > 65535 {
		return nil, errors.New("DB_PORT must be a valid TCP port")
	}
	database := strings.TrimSpace(env("DB_NAME", defaultDBName))
	user := env("DB_USER", defaultDBUser)
	password := env("DB_PASSWORD", defaultDBPassword)
	if user == "" || password == "" {
		return nil, errors.New("set DB_USER and DB_PASSWORD environment variables")
	}

	connString := fmt.Sprintf("sqlserver://%s:%s@%s:%d?database=%s&encrypt=true&TrustServerCertificate=true",
		urlEscape(user), urlEscape(password), host, port, urlEscape(database))
	db, err := sql.Open("sqlserver", connString)
	if err != nil {
		return nil, fmt.Errorf("open SQL Server connection: %w", err)
	}
	db.SetMaxOpenConns(10)
	db.SetMaxIdleConns(5)
	db.SetConnMaxLifetime(30 * time.Minute)
	return db, nil
}

func urlEscape(value string) string {
	return strings.ReplaceAll(urlQueryEscape(value), "+", "%20")
}

func urlQueryEscape(value string) string {
	return url.QueryEscape(value)
}

func env(key, fallback string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return fallback
}

func (a *app) health(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 3*time.Second)
	defer cancel()
	if err := a.db.PingContext(ctx); err != nil {
		writeJSON(w, http.StatusServiceUnavailable, apiError{Error: "database unavailable"})
		return
	}
	writeJSON(w, http.StatusOK, map[string]string{"status": "ok", "database": "connected"})
}

func (a *app) labels(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 15*time.Second)
	defer cancel()

	rows, err := a.db.QueryContext(ctx,
		"EXEC [dbo].[Label_prints] @Action = @p1",
		"getlabels",
	)
	if err != nil {
		log.Printf("query labels failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read label records; check database permissions and table schema"})
		return
	}
	defer rows.Close()

	columns, err := rows.Columns()
	if err != nil {
		log.Printf("read columns failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read table columns"})
		return
	}

	data := make([]map[string]any, 0)
	for rows.Next() {
		values := make([]any, len(columns))
		dest := make([]any, len(columns))
		for i := range values {
			dest[i] = &values[i]
		}
		if err := rows.Scan(dest...); err != nil {
			log.Printf("scan label row failed: %v", err)
			writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read a label record"})
			return
		}

		item := make(map[string]any, len(columns))
		for i, column := range columns {
			value := values[i]
			if bytes, ok := value.([]byte); ok {
				value = string(bytes)
			}
			item[column] = value
		}
		data = append(data, item)
	}
	if err := rows.Err(); err != nil {
		log.Printf("iterate label rows failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed while reading label records"})
		return
	}

	writeJSON(w, http.StatusOK, map[string]any{
		"count": len(data),
		"data":  data,
	})
}

func (a *app) searchLabels(w http.ResponseWriter, r *http.Request) {
	productID := strings.TrimSpace(r.URL.Query().Get("productID"))
	if productID == "" {
		writeJSON(w, http.StatusBadRequest, apiError{Error: "productID query parameter is required"})
		return
	}

	ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
	defer cancel()

	rows, err := a.db.QueryContext(ctx,
		"EXEC [dbo].[Label_prints] @Action = @p1, @ProductID = @p2",
		"searchlabels",
		productID,
	)
	if err != nil {
		log.Printf("search labels by productID failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to search label records; check productID column and database permissions"})
		return
	}
	defer rows.Close()

	columns, err := rows.Columns()
	if err != nil {
		log.Printf("read search result columns failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read search result columns"})
		return
	}

	data := make([]map[string]any, 0)
	for rows.Next() {
		values := make([]any, len(columns))
		dest := make([]any, len(columns))
		for i := range values {
			dest[i] = &values[i]
		}
		if err := rows.Scan(dest...); err != nil {
			log.Printf("scan search result row failed: %v", err)
			writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read a search result"})
			return
		}

		item := make(map[string]any, len(columns))
		for i, column := range columns {
			value := values[i]
			if bytes, ok := value.([]byte); ok {
				value = string(bytes)
			}
			item[column] = value
		}
		data = append(data, item)
	}
	if err := rows.Err(); err != nil {
		log.Printf("iterate search results failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed while searching label records"})
		return
	}
	if len(data) == 0 {
		writeJSON(w, http.StatusNotFound, apiError{Error: "no label records found for this productID"})
		return
	}

	writeJSON(w, http.StatusOK, map[string]any{
		"count": len(data),
		"data":  data,
	})
}

func (a *app) labelPrintList(w http.ResponseWriter, r *http.Request) {
	ctx, cancel := context.WithTimeout(r.Context(), 10*time.Second)
	defer cancel()

	rows, err := a.db.QueryContext(ctx,
		"EXEC [dbo].[Label_prints] @Action = @p1",
		"getprintlist",
	)
	if err != nil {
		log.Printf("read label print list failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read saved Box items"})
		return
	}
	defer rows.Close()

	data := make([]map[string]any, 0)
	for rows.Next() {
		var barcode, category sql.NullString
		var createdAt any
		var productID, description, barcodeNo, location, psize1, psize2, psize3 sql.NullString
		if err := rows.Scan(&barcode, &category, &createdAt, &productID, &description, &barcodeNo, &location, &psize1, &psize2, &psize3); err != nil {
			log.Printf("scan label print list failed: %v", err)
			writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed to read a saved Box item"})
			return
		}
		data = append(data, map[string]any{
			"Barcode": barcode.String, "Category": category.String, "CreateAt": createdAt,
			"ProductID": productID.String, "Description": description.String, "BarcodeNo": barcodeNo.String,
			"Location": location.String, "PSize1": psize1.String, "PSize2": psize2.String, "PSize3": psize3.String,
		})
	}
	if err := rows.Err(); err != nil {
		log.Printf("iterate label print list failed: %v", err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "failed while reading saved Box items"})
		return
	}
	writeJSON(w, http.StatusOK, map[string]any{"count": len(data), "data": data})
}

type labelPrintListRequest struct {
	Action   string `json:"action"`
	Barcode  string `json:"barcode"`
	Category string `json:"category"`
}

func (a *app) manageLabelPrintList(w http.ResponseWriter, r *http.Request) {
	r.Body = http.MaxBytesReader(w, r.Body, 1<<20)
	var req labelPrintListRequest
	decoder := json.NewDecoder(r.Body)
	decoder.DisallowUnknownFields()
	if err := decoder.Decode(&req); err != nil {
		writeJSON(w, http.StatusBadRequest, apiError{Error: "invalid JSON request body"})
		return
	}

	req.Action = strings.ToLower(strings.TrimSpace(req.Action))
	req.Barcode = strings.TrimSpace(req.Barcode)
	req.Category = strings.TrimSpace(req.Category)
	validCategories := map[string]bool{"Label Repack": true, "Label ประตูรับ": true, "Label หน้าโหลด": true}

	switch req.Action {
	case "insert":
		if req.Barcode == "" || !validCategories[req.Category] {
			writeJSON(w, http.StatusBadRequest, apiError{Error: "barcode and a valid category (Label Repack, Label ประตูรับ, Label หน้าโหลด) are required"})
			return
		}
	case "delete":
		if req.Barcode == "" || !validCategories[req.Category] {
			writeJSON(w, http.StatusBadRequest, apiError{Error: "barcode and category are required to remove one item"})
			return
		}
	case "clearcategory":
		if !validCategories[req.Category] {
			writeJSON(w, http.StatusBadRequest, apiError{Error: "a valid category (Label Repack, Label ประตูรับ, Label หน้าโหลด) is required"})
			return
		}
	case "builddata":
		// No additional parameters required.
	default:
		writeJSON(w, http.StatusBadRequest, apiError{Error: "action must be insert, delete, clearCategory, or buildData"})
		return
	}

	ctx, cancel := context.WithTimeout(r.Context(), 15*time.Second)
	defer cancel()
	procedureAction := req.Action
	if procedureAction == "clearcategory" {
		procedureAction = "delete"
	}
	_, err := a.db.ExecContext(ctx,
		"EXEC [dbo].[Label_prints] @Action = @p1, @Barcode = @p2, @Category = @p3",
		procedureAction, nullableString(req.Barcode), nullableString(req.Category),
	)
	if err != nil {
		log.Printf("Label_prints action %q failed: %v", req.Action, err)
		writeJSON(w, http.StatusInternalServerError, apiError{Error: "stored procedure failed; check dbo.Label_prints parameters and SQL Server logs"})
		return
	}

	writeJSON(w, http.StatusOK, map[string]any{
		"status":   "success",
		"action":   req.Action,
		"barcode":  req.Barcode,
		"category": req.Category,
	})
}

func nullableString(value string) any {
	if strings.TrimSpace(value) == "" {
		return nil
	}
	return value
}

func serveIndex(w http.ResponseWriter, r *http.Request) {
	if r.URL.Path != "/" {
		http.NotFound(w, r)
		return
	}
	http.ServeFile(w, r, "index.html")
}

func writeJSON(w http.ResponseWriter, status int, value any) {
	w.Header().Set("Content-Type", "application/json; charset=utf-8")
	w.WriteHeader(status)
	if err := json.NewEncoder(w).Encode(value); err != nil {
		log.Printf("write JSON response failed: %v", err)
	}
}

func securityHeaders(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("X-Content-Type-Options", "nosniff")
		w.Header().Set("X-Frame-Options", "DENY")
		w.Header().Set("Referrer-Policy", "no-referrer")
		next.ServeHTTP(w, r)
	})
}