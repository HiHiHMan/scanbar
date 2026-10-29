package main

import (
	"context"
	"errors"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"time"
)

// setWorkingDirectory makes .env and the web asset folders resolve relative to the EXE,
// rather than the Windows service manager's default working directory.
func setWorkingDirectory() error {
	exePath, err := os.Executable()
	if err != nil {
		return err
	}
	return os.Chdir(filepath.Dir(exePath))
}

func runConsole() error {
	if err := setWorkingDirectory(); err != nil {
		return err
	}
	server, db, err := buildServer()
	if err != nil {
		return err
	}
	defer db.Close()

	err = server.ListenAndServe()
	if errors.Is(err, http.ErrServerClosed) {
		return nil
	}
	return err
}

func shutdownApplication(server *http.Server, dbCloser interface{ Close() error }) error {
	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	var shutdownErr error
	if server != nil {
		shutdownErr = server.Shutdown(ctx)
	}
	if dbCloser != nil {
		if err := dbCloser.Close(); shutdownErr == nil {
			shutdownErr = err
		}
	}
	log.Printf("BarLabel shutdown complete")
	return shutdownErr
}
