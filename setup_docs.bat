@echo off
set PHP_PATH=C:\xampp\php\php.exe
set DOC_PHAR=phpDocumentor.phar

echo [1/3] Verificando phpDocumentor...
if not exist %DOC_PHAR% (
    echo Descargando phpDocumentor.phar...
    powershell -Command "Invoke-WebRequest -Uri https://phpdoc.org/phpDocumentor.phar -OutFile %DOC_PHAR%"
)

echo [2/3] Generando Documentacion Backend (PHP)...
"%PHP_PATH%" %DOC_PHAR% -d app/ -d api/ -t docs/php-api --title "Hotel Platinium - API PHP"

echo [3/3] Generando Documentacion Frontend (JS)...

REM Verificar si JSDoc está instalado
where jsdoc >nul 2>nul
if %errorlevel% neq 0 (
    echo Instalando JSDoc...
    call npm install -g jsdoc
)

REM Generar documentación JS
call jsdoc app/Views/admin -r -d docs/js-api

echo.
echo ======================================================
echo DOCUMENTACION GENERADA EXITOSAMENTE
echo ======================================================
echo Backend: docs/php-api/index.html
echo Frontend: docs/js-api/index.html
echo ======================================================
pause