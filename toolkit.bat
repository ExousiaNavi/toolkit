@echo off

REM Get the directory of the batch script
for %%I in (%0) do set "script_dir=%%~dpI"

REM Change to the directory of the batch script
cd /d "%script_dir%"

REM Start Apache and MySQL using XAMPP command-line tool
"C:\xampp\xampp_start.exe" apache && "C:\xampp\xampp_start.exe" mysql

REM Wait for a few seconds to allow services to start (adjust the delay as needed)
timeout /t 5 >nul

REM Start npm run dev in the background (assuming it's a long-running process)
start /b "" npm run dev

REM Wait for a few seconds (adjust the delay as needed)
timeout /t 5 >nul

REM Start php artisan serve in a new command prompt window
start /b cmd /c php artisan serve --host=10.1.55.79 --port=8000

REM Wait for a moment to ensure the PHP server starts
timeout /t 5 >nul

REM Change directory to toolkit_backend
cd /d "%script_dir%\toolkit_backend"

REM Activate the virtual environment and start Uvicorn server without showing the terminal
start /b cmd /c "venv\Scripts\activate && uvicorn app.main:app --host 127.0.0.1 --port 8082"

REM Wait for a moment to ensure the Uvicorn server starts
timeout /t 5 >nul

REM Open the URL in the default web browser
start "" "chrome.exe" http://10.1.55.79:8000/
