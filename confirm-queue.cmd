@echo off
"%~dp0bin\php.exe" -c "%~dp0bin\php.ini" -n "%~dp0php\console.php" "confirm-queue"
