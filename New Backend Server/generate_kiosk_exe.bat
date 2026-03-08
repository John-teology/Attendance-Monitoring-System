@echo off
setlocal enabledelayedexpansion

echo ========================================================
echo       Library Attendance Kiosk - EXE Generator
echo ========================================================
echo.
echo This script will generate a standalone EXE for your Kiosk.
echo.

:: --- Defaults ---
set "DEFAULT_IP=127.0.0.1"
set "DEFAULT_PORT=8000"
set "DEFAULT_PATH=/kiosk"

:: --- Try to read settings.json ---
if exist "settings.json" (
    echo Reading settings.json...
    for /f "usebackq tokens=*" %%A in (`powershell -NoProfile -Command "$json = Get-Content 'settings.json' | ConvertFrom-Json; Write-Host $json.web_server.listen_on[0]"`) do set "JSON_IP=%%A"
    for /f "usebackq tokens=*" %%A in (`powershell -NoProfile -Command "$json = Get-Content 'settings.json' | ConvertFrom-Json; Write-Host $json.web_server.listen_on[1]"`) do set "JSON_PORT=%%A"
)

if not "%JSON_IP%"=="" (
    echo Found IP in settings.json: %JSON_IP%
    set "DEFAULT_IP=%JSON_IP%"
)

if not "%JSON_PORT%"=="" (
    echo Found Port in settings.json: %JSON_PORT%
    set "DEFAULT_PORT=%JSON_PORT%"
)

echo.
echo Current Defaults: %DEFAULT_IP%:%DEFAULT_PORT%%DEFAULT_PATH%
echo.

:: --- Prompt for IP ---
set /p "TARGET_IP=Enter IP Address (Press Enter for %DEFAULT_IP%): "
if "%TARGET_IP%"=="" set "TARGET_IP=%DEFAULT_IP%"

:: --- Prompt for Port ---
set /p "TARGET_PORT=Enter Port (Press Enter for %DEFAULT_PORT%): "
if "%TARGET_PORT%"=="" set "TARGET_PORT=%DEFAULT_PORT%"

:: --- Prompt for Path ---
set /p "TARGET_PATH=Enter URL Path (Press Enter for %DEFAULT_PATH%): "
if "%TARGET_PATH%"=="" set "TARGET_PATH=%DEFAULT_PATH%"

:: --- Construct URL ---
set "FULL_URL=http://%TARGET_IP%:%TARGET_PORT%%TARGET_PATH%"

echo.
echo --------------------------------------------------------
echo Target URL: %FULL_URL%
echo --------------------------------------------------------
echo.
echo Generating Kiosk EXE... This may take a few minutes...
echo.

:: --- Clean previous build ---
if exist "KioskBuild" (
    echo Cleaning up previous build...
    rmdir /s /q "KioskBuild"
)

:: --- Run Nativefier ---
:: We use call to ensure npx runs and returns control
call npx nativefier ^
    --name "LibraryKiosk" ^
    --full-screen ^
    --hide-window-frame ^
    --single-instance ^
    --disable-context-menu ^
    --disable-dev-tools ^
    --internal-urls ".*" ^
    "%FULL_URL%" ^
    "KioskBuild"

echo.
if %ERRORLEVEL% EQU 0 (
    echo ========================================================
    echo                  BUILD SUCCESSFUL!
    echo ========================================================
    echo.
    echo Your Kiosk EXE is located in the "KioskBuild" folder.
    echo You can move that folder to any computer to run the Kiosk.
    echo.
    echo Opening build folder...
    start "" "KioskBuild"
) else (
    echo ========================================================
    echo                    BUILD FAILED
    echo ========================================================
    echo Please check the error messages above.
)

pause
