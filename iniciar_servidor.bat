@echo off
title mi ERP - Servidor de Desarrollo
echo ====================================================
echo             mi ERP - SERVIDOR LOCAL
echo ====================================================
echo.

set PHP_PATH=C:\xampp\php\php.exe

if not exist "%PHP_PATH%" (
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        set PHP_PATH=php
    ) else (
        echo [ERROR] No se encontro PHP en C:\xampp\php\php.exe ni en el PATH.
        echo Por favor asegurese de tener XAMPP instalado.
        pause
        exit /b 1
    )
)

echo Iniciando servidor en http://127.0.0.1:8000 ...
echo Presione Ctrl + C para detener el servidor.
echo.
cd /d "%~dp0public"
"%PHP_PATH%" -S 127.0.0.1:8000 index.php
pause
