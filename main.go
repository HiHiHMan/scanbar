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
	"strconv"
	"strings"
	"time"

	_ "github.com/microsoft/go-mssqldb"
)

const labelsQuery = "SELECT TOP (200) * FROM [Inventory].[dbo].[Tbl_Label_Prints]"

type app struct {
	db *sql.DB
}

type apiError struct {
	Error string `json:"error"`
}

func main() {
	addr := env("API_ADDR", ":8080")
	db, err := openDatabase()
	if err != nil {
		log.Fatalf("database configuration error: %v", err)
	}
	defer db.Close()

	ctx, cancel := context.WithTimeout(context.Background(), 8*time.Second)
	defer cancel()
	if err := db.PingContext(ctx); err != nil {
		log.Fatalf("cannot connect to SQL Server: %v", err)
	}

	a := &app{db: db}
	mux := http.NewServeMux()
	mux.HandleFunc("GET /api/health", a.health)
	mux.HandleFunc("GET /api/labels", a.labels)
	mux.HandleFunc("GET /", serveIndex)

	server := &http.Server{
		Addr:              addr,
		Handler:           securityHeaders(mux),
		ReadHeaderTimeout: 5 * time.Second,
		ReadTimeout:       15 * time.Second,
		WriteTimeout:      30 * time.Second,
		IdleTimeout:       60 * time.Second,
	}

	log.Printf("Scanbar running at http://localhost%s", addr)
	log.Fatal(server.ListenAndServe())
}

func openDatabase() (*sql.DB, error) {
	host := strings.TrimSpace(env("DB_HOST", "192.168.123.45"))
	portText := env("DB_PORT", "1433")
	port, err := strconv.Atoi(portText)
	if err != nil || port < 1 || port > 65535 {
		return nil, errors.New("DB_PORT must be a valid TCP port")
	}
	database := strings.TrimSpace(env("DB_NAME", "Inventory"))
	user := os.Getenv("DB_USER")
	password := os.Getenv("DB_PASSWORD")
	if user == "" || password == "" {
		return nil, errors.New("set DB_USER and DB_PASSWORD environment variables")
	}

	// SQL Server connection string. TLS is enabled; TrustServerCertificate is
	// suitable for a private network with a self-signed server certificate.
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
	// QueryEscape is also safe for userinfo here; convert '+' so it is not
	// interpreted as a space by URL parsers.
	return strings.ReplaceAll(urlQueryEscape(value), "+", "%20")
}

func urlQueryEscape(value string) string {
	// Keep URL escaping in the standard library and avoid manual string handling.
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

	rows, err := a.db.QueryContext(ctx, labelsQuery)
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
