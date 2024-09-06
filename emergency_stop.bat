@echo off

REM Stop Apache and MySQL (if started by XAMPP)
taskkill /IM httpd.exe /F
taskkill /IM mysqld.exe /F

REM Stop npm run dev (Node.js process)
taskkill /IM node.exe /F

REM Stop PHP Artisan server
taskkill /IM php.exe /F

REM Stop Uvicorn server
taskkill /IM uvicorn.exe /F

REM Stop any remaining cmd.exe processes related to the batch script
taskkill /IM cmd.exe /F

echo All processes stopped.
pause
