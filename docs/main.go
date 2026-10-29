package main

import (
	"context"
	"database/sql"
	_ "embed" // บังคับใช้งานร่วมกับ go:embed
	"fmt"
	"log"
	"net/http"
	"os"
	"path/filepath"

	_ "github.com/denisenkom/go-mssqldb"
	"github.com/joho/godotenv"
	"github.com/kardianos/service"
	"github.com/xuri/excelize/v2" // เพิ่มตัวนี้เข้ามาเพื่อใช้งานเปิดไฟล์
)

//go:embed templates/index.html
var indexHTML []byte

type program struct{}

func (p *program) Start(s service.Service) error {
	go p.run()
	return nil
}

func (p *program) run() {
	port := os.Getenv("PORT")
	if port == "" {
		port = "8130" // ค่า Default ป้องกันกรณีหาค่าไม่ได้
	}

	http.HandleFunc("/", handleIndex)
	http.HandleFunc("/run", handleRunProcess)

	log.Printf("Server Start Port %s", port)

	err := http.ListenAndServe(":"+port, nil)
	if err != nil {
		log.Fatal(err)
	}
}

func (p *program) Stop(s service.Service) error {
	log.Println("Service Stopped")
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

	// เช็คและกำหนดค่าพอร์ตให้เป็น 8130 ตามที่คุณต้องการ
	if os.Getenv("PORT") == "" {
		os.Setenv("PORT", "8130")
	}

	svcConfig := &service.Config{
		Name:        "Port8130_Parcel_Import",
		DisplayName: "Port:8130-Parcel Import Service",
		Description: "Parcel CSV Import Service to SQL Server",
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

		case "uninstall":
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

// แสดงหน้าเว็บ index.html (ถ้ามี)
func handleIndex(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Content-Type", "text/html; charset=utf-8")
	w.Write(indexHTML)
}

// ฟังก์ชันหลักที่ทำงานเมื่อเรียกผ่าน API /run
func handleRunProcess(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// // อ่านค่าจาก conf.env
	// dbServer := os.Getenv("DBSERVER")
	// dbPort := os.Getenv("DBPORT")
	// dbUser := os.Getenv("DBUSER")
	// dbPassword := os.Getenv("DBPASSWORD")
	// dbName := os.Getenv("DB_NAME")

	// อ่านค่าจาก conf.env พร้อมกำหนดค่า Default เผื่อกรณีตัวแปรว่าง
	dbServer := os.Getenv("DBSERVER")
	if dbServer == "" {
		dbServer = "192.168.101.239"
	}

	dbPort := os.Getenv("DBPORT")
	if dbPort == "" {
		dbPort = "1433" // กำหนด Port เริ่มต้นของ SQL Server
	}

	dbUser := os.Getenv("DBUSER")
	if dbUser == "" {
		dbUser = "monitor"
	}

	dbPassword := os.Getenv("DBPASSWORD")
	if dbPassword == "" {
		dbPassword = "iloveyou"
	}

	dbName := os.Getenv("DB_NAME")
	if dbName == "" {
		dbName = "InboundTransport"
	}

	// เปิดไฟล์จาก Network Share (ใช้ excelize เปิดแทน csv เพื่อรองรับรูปแบบที่ปะปน)
	filePath := `\\192.168.101.239\DataDashboard\InboundTransport\fileToUpload\parcel.csv`
	
	f, err := excelize.OpenFile(filePath)
	if err != nil {
		log.Printf("ไม่สามารถเปิดไฟล์ได้: %v", err)
		http.Error(w, fmt.Sprintf("ไม่สามารถเปิดไฟล์ได้: %v", err), http.StatusInternalServerError)
		return
	}
	defer f.Close()

	// ดึงชื่อชีทแรกของไฟล์มาอ่าน
	sheets := f.GetSheetList()
	if len(sheets) == 0 {
		http.Error(w, "ไม่พบชีทข้อมูลในไฟล์", http.StatusBadRequest)
		return
	}
	
	rows, err := f.GetRows(sheets[0])
	if err != nil {
		http.Error(w, fmt.Sprintf("ไม่สามารถอ่านข้อมูลจากชีทได้: %v", err), http.StatusInternalServerError)
		return
	}

	if len(rows) == 0 {
		http.Error(w, "ไม่พบข้อมูลในไฟล์", http.StatusBadRequest)
		return
	}

	header := rows[0]
	dataRows := rows[1:]

	// เชื่อมต่อ SQL Server
	connString := fmt.Sprintf("server=%s;port=%s;user id=%s;password=%s;database=%s;encrypt=true;trustservercertificate=true;",
		dbServer, dbPort, dbUser, dbPassword, dbName)

	db, err := sql.Open("sqlserver", connString)
	if err != nil {
		http.Error(w, fmt.Sprintf("ไม่สามารถเชื่อมต่อฐานข้อมูลได้: %v", err), http.StatusInternalServerError)
		return
	}
	defer db.Close()

	ctx := context.Background()
	if err := db.PingContext(ctx); err != nil {
		http.Error(w, fmt.Sprintf("เชื่อมต่อฐานข้อมูลไม่สำเร็จ: %v", err), http.StatusInternalServerError)
		return
	}

	// ใช้ Transaction
	tx, err := db.BeginTx(ctx, nil)
	if err != nil {
		http.Error(w, fmt.Sprintf("ไม่สามารถเริ่ม Transaction ได้: %v", err), http.StatusInternalServerError)
		return
	}
	defer tx.Rollback()

	// ลบตารางเก่า (Temp_Parcel)
	_, err = tx.ExecContext(ctx, "IF OBJECT_ID('Temp_Parcel', 'U') IS NOT NULL DROP TABLE Temp_Parcel;")
	if err != nil {
		http.Error(w, fmt.Sprintf("ไม่สามารถลบตารางเก่าได้: %v", err), http.StatusInternalServerError)
		return
	}

	// สร้างตารางใหม่ Temp_Parcel
	createTableQuery := "CREATE TABLE Temp_Parcel ("
	for i, colName := range header {
		safeColName := fmt.Sprintf("[Col_%d]", i+1)
		if colName != "" {
			safeColName = fmt.Sprintf("[%s]", colName)
		}
		createTableQuery += fmt.Sprintf("%s NVARCHAR(MAX) NULL", safeColName)
		if i < len(header)-1 {
			createTableQuery += ", "
		}
	}
	createTableQuery += ");"

    _, err = tx.ExecContext(ctx, createTableQuery)
	if err != nil {
		http.Error(w, fmt.Sprintf("ไม่สามารถสร้างตารางใหม่ได้: %v", err), http.StatusInternalServerError)
		return
	}

	// Insert ข้อมูลทีละแถว
	for _, row := range dataRows {
		insertQuery := "INSERT INTO Temp_Parcel VALUES ("
		vals := []interface{}{}
		for i := range header {
			insertQuery += fmt.Sprintf("@p%d", i+1)
			if i < len(header)-1 {
				insertQuery += ", "
			}

			var val interface{} = ""
			if i < len(row) {
				val = row[i]
			}
			vals = append(vals, val)
		}
		insertQuery += ");"

		_, err = tx.ExecContext(ctx, insertQuery, vals...)
		if err != nil {
			log.Printf("เกิดข้อผิดพลาดในการ Insert แถว: %v", err)
		}
	}

	// Commit Transaction
	if err := tx.Commit(); err != nil {
		http.Error(w, fmt.Sprintf("Commit Transaction ไม่สำเร็จ: %v", err), http.StatusInternalServerError)
		return
	}

	w.WriteHeader(http.StatusOK)
	fmt.Fprint(w, "นำเข้าข้อมูลลง Temp_Parcel สำเร็จเรียบร้อยแล้ว!")
}