//go:build windows

package main

import (
	"errors"
	"fmt"
	"log"
	"net/http"

	"golang.org/x/sys/windows/svc"
)

type barLabelService struct{}

func (p *barLabelService) Execute(args []string, requests <-chan svc.ChangeRequest, changes chan<- svc.Status) (bool, uint32) {
	const acceptedCommands = svc.AcceptStop | svc.AcceptShutdown

	changes <- svc.Status{State: svc.StartPending}
	if err := setWorkingDirectory(); err != nil {
		log.Printf("set service working directory failed: %v", err)
		return false, 1
	}

	server, db, err := buildServer()
	if err != nil {
		log.Printf("BarLabel startup failed: %v", err)
		return false, 1
	}

	serverErrors := make(chan error, 1)
	go func() {
		err := server.ListenAndServe()
		if err != nil && !errors.Is(err, http.ErrServerClosed) {
			serverErrors <- err
		}
	}()

	status := svc.Status{State: svc.Running, Accepts: acceptedCommands}
	changes <- status
	log.Printf("BarLabel Windows Service is running")

	for {
		select {
		case request, ok := <-requests:
			if !ok {
				_ = shutdownApplication(server, db)
				return false, 0
			}

			switch request.Cmd {
			case svc.Interrogate:
				changes <- status
			case svc.Stop, svc.Shutdown:
				changes <- svc.Status{State: svc.StopPending}
				if err := shutdownApplication(server, db); err != nil {
					log.Printf("BarLabel shutdown warning: %v", err)
				}
				return false, 0
			default:
				log.Printf("unsupported service control request: %v", request.Cmd)
			}
		case err := <-serverErrors:
			log.Printf("BarLabel HTTP server stopped unexpectedly: %v", err)
			_ = shutdownApplication(server, db)
			return false, 1
		}
	}
}

func runService() error {
	isService, err := svc.IsWindowsService()
	if err != nil {
		return fmt.Errorf("detect Windows Service mode: %w", err)
	}
	if !isService {
		return runConsole()
	}
	return svc.Run("BarLabel", &barLabelService{})
}
