@echo off
echo Starting Library Server...
start "" "LibraryServer.exe"
timeout /t 5 >nul
echo Opening Kiosk in Default Browser...
start http://localhost:8001/kiosk
exit
