@echo off
echo === PHP Syntax Check ===
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ERROR] php.exe not found in PATH
    echo Please install PHP or add PHP to PATH
    exit /b 1
)
set ERROR_COUNT=0
for /r "app" %%f in (*.php) do (
    php -l "%%f" >nul 2>&1
    if %errorlevel% neq 0 (
        echo [FAIL] %%f
        set /a ERROR_COUNT+=1
    ) else (
        echo [OK]   %%f
    )
)
if %ERROR_COUNT% equ 0 (
    echo All PHP files passed syntax check
) else (
    echo %ERROR_COUNT% file(s) failed
    exit /b 1
)
