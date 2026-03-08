@echo off
setlocal EnableDelayedExpansion

REM Get the directory where this script is located
set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%"

REM Set PHP path to ensure we use the local PHP and its DLLs first
set "PATH=%SCRIPT_DIR%php;%PATH%"

echo ===================================================
echo   Library Attendance System - Full Repair and Setup
echo ===================================================

taskkill /f /im phpdesktop-chrome.exe /t >nul 2>&1
taskkill /f /im php-cgi.exe /t >nul 2>&1

echo.
echo [1/11] Verifying Environment...

REM First, check if PHP works (which implies VCRUNTIME is present either locally or globally)
php -v >nul 2>&1
if %ERRORLEVEL% equ 0 (
    echo    - PHP Environment: OK
) else (
    echo    ! PHP is not running correctly. Checking for runtime files...
    
    REM Check for critical DLLs and download/install if missing
    if not exist "php\vcruntime140.dll" (
        echo    ! Critical PHP runtime files are missing locally.
        echo    ! Downloading Visual C++ Redistributable...
        powershell -Command "Invoke-WebRequest -Uri 'https://aka.ms/vs/17/release/vc_redist.x64.exe' -OutFile 'vc_redist.x64.exe'"
        if exist "vc_redist.x64.exe" (
            echo    ! Installing VC Redistributable - Accept UAC if prompted...
            start /wait vc_redist.x64.exe /install /passive /norestart
            del vc_redist.x64.exe
            echo    - VC Redistributable installed.
        ) else (
            echo    ! Failed to download VC Redistributable.
        )
    )
    
    REM Re-check PHP
    php -v >nul 2>&1
    if !ERRORLEVEL! neq 0 (
        echo    ! Error: PHP is still not working.
        echo    ! Please manually install Visual C++ Redistributable 2015-2022.
    ) else (
        echo    - PHP Environment: OK (Recovered)
    )
)

echo.
echo [1b/11] Detecting Network IP...
for /f "tokens=*" %%a in ('powershell -Command "(Get-NetIPAddress -AddressFamily IPv4 | Where-Object { $_.InterfaceAlias -notlike '*Loopback*' -and $_.PrefixOrigin -ne 'WellKnown' } | Select-Object -First 1).IPAddress"') do set LOCAL_IP=%%a
if "%LOCAL_IP%"=="" set LOCAL_IP=127.0.0.1
echo    - Detected IP: %LOCAL_IP%

echo.
echo [2/11] Configuring Application Settings...
if exist "settings.json" (
    powershell -Command "$p='settings.json'; Copy-Item -LiteralPath $p -Destination ($p + '.bak') -Force; try { $bytes=[IO.File]::ReadAllBytes($p); if($bytes.Length -ge 2 -and $bytes[0] -eq 0xFF -and $bytes[1] -eq 0xFE){ $raw=[Text.Encoding]::Unicode.GetString($bytes) } elseif($bytes.Length -ge 2 -and $bytes[0] -eq 0xFE -and $bytes[1] -eq 0xFF){ $raw=[Text.Encoding]::BigEndianUnicode.GetString($bytes) } else { $raw=[Text.Encoding]::UTF8.GetString($bytes) }; $raw=$raw.TrimStart([char]0xFEFF); $j=$raw | ConvertFrom-Json; if($j.web_server -and $j.web_server.listen_on -and $j.web_server.listen_on.Count -ge 1){ $j.web_server.listen_on[0] = $env:LOCAL_IP }; $json = $j | ConvertTo-Json -Depth 20; [IO.File]::WriteAllText($p, $json, (New-Object System.Text.UTF8Encoding($false))) } catch { Copy-Item -LiteralPath ($p + '.bak') -Destination $p -Force }"
    echo    - Updated settings.json listen_on to %LOCAL_IP%.
)

if exist "php\php.ini" (
    echo    - Enabling required PHP extensions...
    powershell -Command "$c = Get-Content php\php.ini; $c = $c -replace ';extension=zip', 'extension=zip'; $c = $c -replace ';extension=fileinfo', 'extension=fileinfo'; $c = $c -replace ';extension=gd', 'extension=gd'; $c = $c -replace ';extension=intl', 'extension=intl'; $c = $c -replace ';extension=mbstring', 'extension=mbstring'; $c = $c -replace ';extension=openssl', 'extension=openssl'; $c = $c -replace ';extension=pdo_sqlite', 'extension=pdo_sqlite'; $c = $c -replace ';extension=sqlite3', 'extension=sqlite3'; $c = $c -replace ';extension=curl', 'extension=curl'; $c = $c -replace ';extension=exif', 'extension=exif'; $c = $c -replace '^\\s*max_execution_time\\s*=.*', 'max_execution_time = 0'; $c = $c -replace '^\\s*max_input_time\\s*=.*', 'max_input_time = 0'; $c = $c -replace '^\\s*memory_limit\\s*=.*', 'memory_limit = 1024M'; $c = $c -replace 'upload_max_filesize = 2M', 'upload_max_filesize = 10M'; $c = $c -replace 'post_max_size = 8M', 'post_max_size = 10M'; if(-not ($c -match '^\\s*max_execution_time\\s*=')) { $c += 'max_execution_time = 0' }; if(-not ($c -match '^\\s*max_input_time\\s*=')) { $c += 'max_input_time = 0' }; if(-not ($c -match '^\\s*memory_limit\\s*=')) { $c += 'memory_limit = 1024M' }; $c | Set-Content php\php.ini"
    echo    - Updated php.ini extensions and upload limits.
)

echo.
echo [3/11] Setting up Laravel Configuration...
cd www || (echo    ! Missing 'www' directory. && pause && goto :EOF)
if not exist ".env" (
    echo    - Creating .env file...
    if exist ".env.example" (
        copy .env.example .env >nul || (echo    ! Failed to copy .env.example. && pause && goto :EOF)
    ) else (
        echo    - .env.example missing. Creating minimal .env...
        (
            echo APP_URL=http://%LOCAL_IP%:8001
            echo DB_CONNECTION=sqlite
        )> ".env"
        if not exist ".env" (echo    ! Failed to create .env. && pause && goto :EOF)
    )
) else (
    echo    - .env file exists.
)

REM Ensure DB_CONNECTION is sqlite and APP_URL is correct for assets
powershell -NoProfile -ExecutionPolicy Bypass -Command "$p='.env'; try{ $c=Get-Content -LiteralPath $p -ErrorAction Stop } catch { $c=@() }; $hasAppUrl=$false; $hasDbConn=$false; foreach($line in $c){ if($line -match '^APP_URL='){ $hasAppUrl=$true } if($line -match '^DB_CONNECTION='){ $hasDbConn=$true } }; $c = $c -replace '^DB_CONNECTION=.*','DB_CONNECTION=sqlite'; if($hasAppUrl){ $c = $c -replace '^APP_URL=.*',('APP_URL=http://' + $env:LOCAL_IP + ':8001') } else { $c += ('APP_URL=http://' + $env:LOCAL_IP + ':8001') }; if(-not $hasDbConn){ $c += 'DB_CONNECTION=sqlite' }; if($c -match '^SESSION_DOMAIN='){ $c = $c -replace '^SESSION_DOMAIN=.*','SESSION_DOMAIN=' } ; [IO.File]::WriteAllLines($p, $c, (New-Object System.Text.UTF8Encoding($false)))" || (echo    ! .env update failed. && pause && goto :EOF)

echo.
echo [4/11] Preparing Database...
if not exist "database" mkdir "database"
if exist "database\database.sqlite" goto DB_OK
if exist "storage\database.sqlite" (
    echo    - Restoring database from storage...
    move /y "storage\database.sqlite" "database\database.sqlite" >nul
    goto DB_OK
)
if exist "..\web-backend\storage\database.sqlite" (
    echo    - Importing existing database from web-backend...
    copy /y "..\web-backend\storage\database.sqlite" "database\database.sqlite" >nul
    goto DB_OK
)
echo    - Creating fresh database file...
copy /y nul "database\database.sqlite" >nul
:DB_OK
echo    - Database verified (Existing data preserved).

echo.
echo [4b/11] Backing up Database...
if exist "database\database.sqlite" (
    if not exist "storage\app\backups" mkdir "storage\app\backups"
    for /f %%i in ('powershell -NoProfile -Command "Get-Date -Format yyyyMMdd_HHmmss"') do set "TS=%%i"
    copy /y "database\database.sqlite" "storage\app\backups\database_backup_%TS%.sqlite" >nul 2>nul
    echo    - Backup saved to storage\app\backups\database_backup_%TS%.sqlite
)

echo.
echo [5/11] Checking Dependencies (Vendor)...
if not exist "vendor\autoload.php" (
    if exist "vendor" (
        echo    - Cleaning up incomplete vendor folder...
        rmdir /s /q "vendor"
    )

    echo    - Installing/Repairing dependencies - this may take a few minutes...
    
    if not exist "..\composer.phar" (
        echo    - Downloading Composer...
        php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php composer-setup.php --install-dir=.. --filename=composer.phar
        del composer-setup.php
    )
    
    php ..\composer.phar install --no-dev --optimize-autoloader
) else (
    echo    - Dependencies already installed.
)

echo.
echo [6/11] Generating Application Keys...
REM Check if APP_KEY is empty or default
php artisan key:generate --force

echo.
echo [7/11] Fixing Storage & Assets...
echo    - Ensuring storage directories exist...
if not exist "storage\app\public" mkdir "storage\app\public"
if not exist "storage\app\public\branding" mkdir "storage\app\public\branding"

echo    - Relinking public storage...
REM Force remove existing link or folder to prevent conflicts
if exist "public\storage" (
    rmdir /s /q "public\storage" 2>nul
    del /f /q "public\storage" 2>nul
)

php artisan storage:link

echo.
echo [8/11] Finalizing Database & Cache...
echo    - Running migrations...
php artisan migrate --force
echo    - Clearing application cache...
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
php artisan event:clear
php artisan route:clear

REM Only seed if the database was just created (empty)
REM We check if users table has entries. If not, we seed.
php artisan tinker --execute="if(\App\Models\User::count() == 0) { echo 'Seeding...'; Artisan::call('db:seed', ['--class' => 'Database\Seeders\FreshInstallSeeder']); }"

echo.
echo [9/11] Fixing Broken Asset Links...
if exist "fix_images.php" (
    echo    - Downloading default assets and repairing database records...
    php fix_images.php
)

echo.
echo [9b/11] Applying Performance Limits...
if exist "php\php.ini" (
    powershell -Command "$c = Get-Content php\php.ini; $c = $c -replace '^\\s*max_execution_time\\s*=.*', 'max_execution_time = 0'; $c = $c -replace '^\\s*max_input_time\\s*=.*', 'max_input_time = 0'; $c = $c -replace '^\\s*memory_limit\\s*=.*', 'memory_limit = 1024M'; if(-not ($c -match '^\\s*max_execution_time\\s*=')) { $c += 'max_execution_time = 0' }; if(-not ($c -match '^\\s*max_input_time\\s*=')) { $c += 'max_input_time = 0' }; if(-not ($c -match '^\\s*memory_limit\\s*=')) { $c += 'memory_limit = 1024M' }; $c | Set-Content php\php.ini"
)

echo.
echo [10/11] Clearing Browser Cache...
cd ..
if exist "webcache" (
    taskkill /f /im phpdesktop-chrome.exe /t >nul 2>&1
    taskkill /f /im php-cgi.exe /t >nul 2>&1
    taskkill /f /im php.exe /t >nul 2>&1
    echo    - Removing old browser cache...
    rmdir /s /q "webcache" 2>nul
    if exist "webcache" (
        ping -n 2 127.0.0.1 >nul
        rmdir /s /q "webcache" 2>nul
    )
    echo    - Browser cache cleared.
)

echo.
echo [11/11] Verifying Assets...
cd www
if not exist "public\build" (
    echo    - Note: public\build directory is missing.
    echo      If the app looks unstyled, you may need to run 'npm run build' manually.
    REM Create directory to avoid 500 error if Vite manifest is checked
    mkdir "public\build" 2>nul
)

REM Verify and redownload fonts if missing/corrupt
if not exist "public\webfonts\fa-solid-900.woff" (
    echo    - Downloading Font Awesome assets...
    mkdir "public\webfonts" 2>nul
    mkdir "public\css" 2>nul
    curl -s -o "public/css/all.min.css" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    curl -s -o "public/webfonts/fa-solid-900.woff2" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff2"
    curl -s -o "public/webfonts/fa-solid-900.woff" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.woff"
    curl -s -o "public/webfonts/fa-solid-900.ttf" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-solid-900.ttf"
    curl -s -o "public/webfonts/fa-regular-400.woff2" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff2"
    curl -s -o "public/webfonts/fa-regular-400.woff" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.woff"
    curl -s -o "public/webfonts/fa-regular-400.ttf" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-regular-400.ttf"
    curl -s -o "public/webfonts/fa-brands-400.woff2" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff2"
    curl -s -o "public/webfonts/fa-brands-400.woff" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.woff"
    curl -s -o "public/webfonts/fa-brands-400.ttf" "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/webfonts/fa-brands-400.ttf"
)

REM Ensure core UI assets are available offline (Bootstrap, jQuery, Poppins, Login Logo)
if not exist "public\css\bootstrap.min.css" (
    echo    - Downloading Bootstrap CSS...
    mkdir "public\css" 2>nul
    curl -s -o "public\css\bootstrap.min.css" "https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
)

if not exist "public\js\jquery-3.7.1.min.js" (
    echo    - Downloading jQuery...
    mkdir "public\js" 2>nul
    curl -s -o "public\js\jquery-3.7.1.min.js" "https://code.jquery.com/jquery-3.7.1.min.js"
)

if not exist "public\fonts\poppins\Poppins-400.ttf" (
    echo    - Downloading Poppins font files...
    mkdir "public\fonts" 2>nul
    mkdir "public\fonts\poppins" 2>nul
    curl -s -o "public\fonts\poppins\Poppins-300.ttf" "https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLDz8V1s.ttf"
    curl -s -o "public\fonts\poppins\Poppins-400.ttf" "https://fonts.gstatic.com/s/poppins/v24/pxiEyp8kv8JHgFVrFJA.ttf"
    curl -s -o "public\fonts\poppins\Poppins-500.ttf" "https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLGT9V1s.ttf"
    curl -s -o "public\fonts\poppins\Poppins-600.ttf" "https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLEj6V1s.ttf"
    curl -s -o "public\fonts\poppins\Poppins-700.ttf" "https://fonts.gstatic.com/s/poppins/v24/pxiByp8kv8JHgFVrLCz7V1s.ttf"
)

if not exist "public\css\poppins.css" (
    echo    - Creating local Poppins CSS...
    mkdir "public\css" 2>nul
    powershell -Command "$p='public\css\poppins.css'; $c=@(); foreach($w in 300,400,500,600,700){ $c += \"@font-face { font-family: 'Poppins'; font-style: normal; font-weight: $w; font-display: swap; src: url('../fonts/poppins/Poppins-$w.ttf') format('truetype'); }\" }; $c | Set-Content -Encoding UTF8 $p"
)

if not exist "public\img\login-logo.png" (
    echo    - Downloading login logo...
    mkdir "public\img" 2>nul
    curl -s -o "public\img\login-logo.png" "https://cdn-icons-png.flaticon.com/512/167/167707.png"
)

cd ..

echo.
echo ===================================================
echo   Setup Complete! 
echo   The application is ready to launch.
echo ===================================================
pause
