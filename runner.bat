@echo off
TITLE Savore Restaurant - PHP Server

echo =============================================================
echo      Starting PHP Development Server for Savore Restaurant
echo =============================================================
echo.
echo  The server is running in this window.
echo  Press CTRL+C to stop the server at any time.
echo.
echo ========================================================
echo.

rem This command automatically finds the script's directory
set "PROJECT_ROOT=%~dp0"

rem Launch the browser to the main project (you can choose which page to open)
start http://localhost/savore-restaurant/customer/index.php

rem Change directory to the main project folder and start the server
php -S localhost:8000 -t "%PROJECT_ROOT%"