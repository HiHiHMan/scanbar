# Scanbar

Go API + SQL Server + plain HTML dashboard for `[Inventory].[dbo].[Tbl_Label_Prints]`.

## Requirements
- Go 1.22+
- Network access from the machine running the API to SQL Server `192.168.123.45:1433`

## Configure (PowerShell)

```powershell
$env:DB_HOST = "192.168.123.45"
$env:DB_PORT = "1433"
$env:DB_NAME = "Inventory"
$env:DB_USER = "we"
$env:DB_PASSWORD = "<your SQL Server password>"
$env:API_ADDR = ":8080"
```

Keep the real password out of Git. `.env` is ignored by Git; this starter reads environment variables directly.

## Run

```powershell
go mod tidy
go run .
```

Open http://localhost:8080. Endpoints:
- `GET /api/health` — database connection status
- `GET /api/labels` — up to 200 rows from the target table

The SQL table is fixed in the server; the browser cannot submit SQL. Use a SQL login with only the required SELECT permission. If your server uses a trusted certificate, remove `TrustServerCertificate=true` from the connection string once the certificate is configured correctly.
