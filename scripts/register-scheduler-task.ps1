# Registers a Windows Task Scheduler job that runs Laravel's scheduler once daily at 11:00 AM.
# Run PowerShell as Administrator from the project root:
#   powershell -ExecutionPolicy Bypass -File scripts\register-scheduler-task.ps1

$ProjectRoot = Split-Path -Parent $PSScriptRoot
$PhpPath = "C:\xampp\php\php-win.exe"
$TaskName = "Laravel Scheduler - Sai Agency"

if (-not (Test-Path $PhpPath)) {
    Write-Error "PHP not found at $PhpPath. Update `$PhpPath in this script if XAMPP is installed elsewhere."
    exit 1
}

$Action = New-ScheduledTaskAction `
    -Execute $PhpPath `
    -Argument "artisan schedule:run" `
    -WorkingDirectory $ProjectRoot

$Trigger = New-ScheduledTaskTrigger -Daily -At "11:00"

$Settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -MultipleInstances IgnoreNew

Register-ScheduledTask `
    -TaskName $TaskName `
    -Action $Action `
    -Trigger $Trigger `
    -Settings $Settings `
    -Description "Runs php artisan schedule:run daily at 11:00 AM for Laravel scheduled tasks (weekly DB backup on Monday)." `
    -Force

Write-Host "Scheduled task '$TaskName' registered successfully."
Write-Host "Project: $ProjectRoot"
Write-Host "Runs daily at 11:00 AM using php-win.exe (no CMD window)."
Write-Host "Verify with: php artisan schedule:list"
