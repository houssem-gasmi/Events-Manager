@echo off
cd /d "%~dp0.."
"C:\php85\php.exe" bin\console app:send-event-reminders --minutes=1 --env=dev --no-interaction