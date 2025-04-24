@echo off
:: Definir les chemins de votre projet et de Tomcat
set PROJECT_DIR=.\
set XAMPP_DIR=C:\xampp\htdocs\SI\TP
set SERVER_PROJECT_DIR=%XAMPP_DIR%\CRM

echo Nettoyage du repertoire du projet au serveur...
if exist "%SERVER_PROJECT_DIR%" (
    rd /s /q "%SERVER_PROJECT_DIR%"
)

mkdir "%SERVER_PROJECT_DIR%"

if %errorlevel% neq 0 (
    echo Une erreur est survenue pendant la compilation des fichiers Java. Arrêt du deploiement.
    pause
    exit /b %errorlevel%
)

echo Copie des fichiers...
xcopy /s /y %PROJECT_DIR%\* %SERVER_PROJECT_DIR%\