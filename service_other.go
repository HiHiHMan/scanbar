//go:build !windows

package main

// On non-Windows systems, run as a regular foreground HTTP server.
func runService() error {
	return runConsole()
}
