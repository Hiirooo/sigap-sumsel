@echo off
setlocal
title SIGAP SUMSEL - Laravel Server 0.0.0.0:8000
color 0A

cd /d "%~dp0"

set "LOCAL_IP="
for /f "tokens=2 delims=:" %%A in ('ipconfig ^| findstr /R /C:"IPv4.*192\.168\." /C:"IPv4.*10\." /C:"IPv4.*172\."') do (
    if not defined LOCAL_IP set "LOCAL_IP=%%A"
)
if not defined LOCAL_IP (
    for /f "tokens=2 delims=:" %%A in ('ipconfig ^| findstr /R /C:"IPv4"') do (
        if not defined LOCAL_IP set "LOCAL_IP=%%A"
    )
)
set "LOCAL_IP=%LOCAL_IP: =%"
if not defined LOCAL_IP set "LOCAL_IP=IP-KOMPUTER-INI"

cls
echo ============================================================
echo   SIGAP SUMSEL - SERVER APLIKASI
echo   Biro Humas dan Protokol Setda Provinsi Sumatera Selatan
echo ============================================================
echo.
echo   Project : %CD%
echo   Host    : 0.0.0.0
echo   Port    : 8000
echo   Local   : http://127.0.0.1:8000
echo   Network : http://%LOCAL_IP%:8000
echo.
echo ------------------------------------------------------------
echo   Server akan berjalan sampai jendela ini ditutup.
echo   Tekan CTRL + C untuk menghentikan server.
echo ------------------------------------------------------------
echo.

php artisan serve --host=0.0.0.0 --port=8000

echo.
echo ============================================================
echo   Server SIGAP SUMSEL berhenti.
echo ============================================================
pause
