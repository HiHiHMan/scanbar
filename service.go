package main

import (
	"context"
	"log"
	"net/http"
	"os"
	"path/filepath"
	"time"
)

// setWorkingDirectory makes .env and web assets resolve relative to the executable.
func setWorkingDirectory() error {
	exePath, err := os.Executable()
	if err != nil {
		return err
	}
	return os.Chdir(filepath.Dir(exePath))
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
