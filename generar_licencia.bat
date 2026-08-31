@echo off
title Generador de Licencias RSA mi
echo ====================================================
echo        GENERADOR DE LICENCIAS DIGITALES mi
echo ====================================================
echo.

set PHP_PATH=C:\xampp\php\php.exe

if not exist "%PHP_PATH%" (
    where php >nul 2>&1
    if %errorlevel% equ 0 (
        set PHP_PATH=php
    ) else (
        echo [ERROR] No se encontro PHP en C:\xampp\php\php.exe ni en el PATH.
        pause
        exit /b 1
    )
)

echo Generando archivo de licencia firmado con RSA-SHA256...
echo.
"%PHP_PATH%" "%~dp0tools\generate_license.php"
echo.
echo ====================================================
echo El archivo de licencia ha sido generado en:
echo   c:\dasp\mi\license\sistema.lic
echo ====================================================
echo.
pause
