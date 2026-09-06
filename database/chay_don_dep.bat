@echo off
REM ============================================================
REM  chay_don_dep.bat - Chay don dep dinh ky (goi tu Task Scheduler)
REM
REM  Lam 2 viec:
REM    1. Sao luu database ra file .sql
REM    2. Don nhat ky cu, bo dem dang nhap sai, file tam,
REM       va BAO GIA LAM DO qua 24h (muc 10.2)
REM
REM  Ghi log ra database\logs\ de sau con biet da chay hay chua.
REM  Task Scheduler khong hien man hinh console nen khong co log
REM  la khong biet no chay dung hay loi.
REM
REM  SUA 2 DUONG DAN duoi cho khop may that truoc khi dat lich.
REM ============================================================

REM --- Duong dan PHP va thu muc ma nguon ---
set PHP=C:\xampp\php\php.exe
set DUAN=D:\wwweb\thbaogia

REM --- Thu muc log, tu tao neu chua co ---
set LOGDIR=%DUAN%\database\logs
if not exist "%LOGDIR%" mkdir "%LOGDIR%"

REM --- Ten file log theo thang: don_dep_2026-09.log ---
REM  KHONG cat chuoi %date%: dinh dang khac nhau tuy Windows (MM/DD/YYYY,
REM  DD/MM/YYYY, yyyy-mm-dd...) nen cat tay se ra sai thang.
REM  Hoi PowerShell cho chac, may nao cung dung.
for /f %%d in ('powershell -NoProfile -Command "Get-Date -Format yyyy-MM"') do set THANG=%%d
set LOG=%LOGDIR%\don_dep_%THANG%.log

echo. >> "%LOG%"
echo ======================================== >> "%LOG%"
echo Bat dau: %date% %time% >> "%LOG%"
echo ======================================== >> "%LOG%"

REM --- 1. Sao luu database TRUOC khi don ---
echo [1] Sao luu database >> "%LOG%"
"%PHP%" "%DUAN%\database\sao_luu.php" >> "%LOG%" 2>&1

REM --- 2. Don dep ---
echo [2] Don dep >> "%LOG%"
"%PHP%" "%DUAN%\cron_cleanup.php" >> "%LOG%" 2>&1

echo Ket thuc: %date% %time% >> "%LOG%"
