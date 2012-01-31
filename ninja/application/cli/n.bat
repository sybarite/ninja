@echo off

rem -------------------------------------------------------------
rem  Ninja command line script for Windows.
rem
rem  This is the bootstrap script for running ninja on Windows.
rem
rem -------------------------------------------------------------

@setlocal

set NINJA_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%NINJA_PATH%../../vendor/Ninja/includes/ninjac.php" %*

@endlocal