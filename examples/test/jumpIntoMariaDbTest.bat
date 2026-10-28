@echo off
setlocal EnableExtensions EnableDelayedExpansion

set "SCRIPT_DIR=%~dp0"
if "%SCRIPT_DIR:~-1%"=="\" set "SCRIPT_DIR=%SCRIPT_DIR:~0,-1%"

pushd "%SCRIPT_DIR%\.." >nul
set "EXAMPLES_DIR=%CD%"
set "SQL_FILE=%EXAMPLES_DIR%\00__mariaDB_tables.sql"
popd >nul

set "PHP_CONFIG=%SCRIPT_DIR%\test_db_account.php"

if not exist "%SQL_FILE%" (
    echo SQL file not found: %SQL_FILE%
    exit /b 1
)

if not exist "%PHP_CONFIG%" (
    echo PHP config not found: %PHP_CONFIG%
    exit /b 1
)

call :readPhpValue "_test_db_host" DB_HOST
call :readPhpValue "_test_db_user" DB_USER
call :readPhpValue "_test_db_password" DB_PASSWORD
call :readPhpValue "_test_db_name" DB_NAME

where mariadb >nul 2>&1
if %errorlevel% equ 0 (
    set "DB_CLIENT=mariadb"
) else (
    where mysql >nul 2>&1
    if %errorlevel% equ 0 (
        set "DB_CLIENT=mysql"
    ) else (
        echo Neither mariadb nor mysql client found in PATH.
        exit /b 1
    )
)

cd /d "%EXAMPLES_DIR%"
echo.
echo Current directory: %CD%
echo Using database '!DB_NAME!' on '!DB_HOST!' with user '!DB_USER!' via !DB_CLIENT!
echo.

set /p updateDB="Do you want to update the test database with all entries from 00__mariaDB_tables.sql? (y/n): "

if /i "!updateDB!"=="y" (
    echo.
    echo Recreating database '!DB_NAME!'...
    !DB_CLIENT! --protocol=tcp -h "!DB_HOST!" -u "!DB_USER!" -p"!DB_PASSWORD!" -e "DROP DATABASE IF EXISTS `!DB_NAME!`; CREATE DATABASE `!DB_NAME!`;"
    if !errorlevel! neq 0 (
        echo Error recreating database!
        exit /b 1
    )
    echo Database recreated successfully.
    echo.
    echo Importing SQL data from %SQL_FILE% ...
    !DB_CLIENT! --protocol=tcp -h "!DB_HOST!" -u "!DB_USER!" -p"!DB_PASSWORD!" "!DB_NAME!" ^< "%SQL_FILE%"
    if !errorlevel! neq 0 (
        echo Error updating database!
        exit /b 1
    )
    echo Database updated successfully.
    echo.
)

echo Starting DB client connected to '!DB_NAME!'...
echo Use 'exit' to quit and return to command prompt.
echo.
!DB_CLIENT! --protocol=tcp -h "!DB_HOST!" -u "!DB_USER!" -p"!DB_PASSWORD!" "!DB_NAME!"
exit /b 0

:readPhpValue
set "PHP_KEY=%~1"
set "OUT_VAR=%~2"
set "RAW_VALUE="

for /f "tokens=2 delims==" %%A in ('findstr /B /C:"$%PHP_KEY%=" "%PHP_CONFIG%"') do (
    set "RAW_VALUE=%%A"
)

if not defined RAW_VALUE (
    echo Could not read %PHP_KEY% from %PHP_CONFIG%
    exit /b 1
)

set "RAW_VALUE=!RAW_VALUE: =!"
set "RAW_VALUE=!RAW_VALUE:"=!"
set "RAW_VALUE=!RAW_VALUE:;=!"
set "%OUT_VAR%=!RAW_VALUE!"
exit /b 0
