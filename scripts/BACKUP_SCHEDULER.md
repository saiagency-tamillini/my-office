# Laravel Scheduler — Windows Task Setup

Laravel runs scheduled tasks (including weekly `db:backup`) only when something calls `schedule:run`. On Windows/XAMPP, use **Task Scheduler** to run it **once daily at 11:00 AM** (backup fires on **Monday at 11:00 AM**).

Uses `php-win.exe` so no Command Prompt window appears.

## Option A: Register automatically (PowerShell as Administrator)

From the project root:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\register-scheduler-task.ps1
```

## Option B: Create the task manually

1. Open **Task Scheduler** → **Create Task**
2. **General** tab:
   - Name: `Laravel Scheduler - Sai Agency`
   - Select: **Run whether user is logged on or not**
3. **Triggers** tab → **New**:
   - Begin: **Daily**, start time `11:00:00`
   - Do **not** set "repeat every 1 minute"
4. **Actions** tab → **New**:
   - Action: **Start a program**
   - Program: `C:\xampp\php\php-win.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `C:\xampp\htdocs\my_anand_app`
5. **Conditions** tab: uncheck **Start the task only if the computer is on AC power** (optional, for laptops)
6. Save the task

## Verify

```powershell
cd C:\xampp\htdocs\my_anand_app
php artisan schedule:list
php artisan db:backup
```

Expected schedule entry: `0 11 * * 1  php artisan db:backup` (Monday 11:00 AM).

## Requirements

- **MySQL (XAMPP)** must be running when the weekly backup runs (Monday 11:00 AM)
- Set these in `.env`:

```env
BACKUP_EMAIL=your-trusted-email@example.com
MYSQLDUMP_PATH=C:\xampp\mysql\bin\mysqldump.exe
BACKUP_RETENTION_COUNT=10
```

Backups are stored in `storage/app/backups/`. Only the **10 most recent** backup files are kept; older ones are deleted automatically.
