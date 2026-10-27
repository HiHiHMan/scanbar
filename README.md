"# scanbar" 
:setup
go mod tidy

:run
go run .

git pull --rebase origin main
Copy-Item .env.example .env
notepad .env