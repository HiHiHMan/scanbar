//go:build windows

package main

import (
	"errors"
	"fmt"
	"log"
	"net/http"
	"os"

	"github.com/kardianos/service"
)

type barLabelService struct {
	server *http.Server
	db     interface{ Close() error }
}

// Start prepares the existing BarLabel API, then starts its HTTP server
// asynchronously as required by kardianos/service.
func (p *barLabelService) Start(s service.Service) error {
	if err := setWorkingDirectory(); err != nil {
		return fmt.Errorf("set service working directory: %w", err)
	}

	server, db, err := buildServer()
	if err != nil {
		return fmt.Errorf("start BarLabel: %w", err)
	}

	p.server = server
	p.db = db

	go func() {
		err := server.ListenAndServe()
		if err != nil && !errors.Is(err, http.ErrServerClosed) {
			// Match the reference service's behavior: a server that cannot
			// continue running should exit so Windows can report a failure.
			log.Printf("BarLabel HTTP server stopped unexpectedly: %v", err)
			os.Exit(1)
		}
	}()

	log.Printf("BarLabel Windows Service is running")
	return nil
}

// Stop gracefully closes HTTP requests and the SQL Server connection pool.
func (p *barLabelService) Stop(s service.Service) error {
	return shutdownApplication(p.server, p.db)
}

func runService() error {
	if err := setWorkingDirectory(); err != nil {
		return fmt.Errorf("set executable working directory: %w", err)
	}

	svcConfig := &service.Config{
		Name:        "BarLabel",
		DisplayName: "BarLabel",
		Description: "BarLabel web application API on port 8080",
	}

	prg := &barLabelService{}
	s, err := service.New(prg, svcConfig)
	if err != nil {
		return fmt.Errorf("configure BarLabel service: %w", err)
	}

	// These commands follow docs/main.go and allow the EXE to manage
	// its own Windows Service registration.
	if len(os.Args) > 1 {
		switch os.Args[1] {
		case "install":
			if err := s.Install(); err != nil {
				return fmt.Errorf("install BarLabel service: %w", err)
			}
			fmt.Println("BarLabel service installed successfully")
			return nil
		case "uninstall":
			if err := s.Uninstall(); err != nil {
				return fmt.Errorf("uninstall BarLabel service: %w", err)
			}
			fmt.Println("BarLabel service uninstalled successfully")
			return nil
		case "start":
			if err := s.Start(); err != nil {
				return fmt.Errorf("start BarLabel service: %w", err)
			}
			fmt.Println("BarLabel service start requested successfully")
			return nil
		case "stop":
			if err := s.Stop(); err != nil {
				return fmt.Errorf("stop BarLabel service: %w", err)
			}
			fmt.Println("BarLabel service stop requested successfully")
			return nil
		}
	}

	if err := s.Run(); err != nil {
		return fmt.Errorf("run BarLabel service: %w", err)
	}
	return nil
}
