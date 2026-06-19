<?php

return [

    'path' => env('BACKUP_PATH', storage_path('app/backups')),

    'retention_count' => (int) env('BACKUP_RETENTION_COUNT', 10),

    'email' => env('BACKUP_EMAIL'),

    'mysqldump_path' => env('MYSQLDUMP_PATH', 'C:\\xampp\\mysql\\bin\\mysqldump.exe'),

    'max_email_mb' => (int) env('BACKUP_MAX_EMAIL_MB', 20),

];
