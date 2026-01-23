<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Create a backup of the database';

    public function handle()
    {
        $database = env('DB_DATABASE');
        $username = env('DB_USERNAME');
        $password = env('DB_PASSWORD');
        $host = env('DB_HOST', '127.0.0.1');

        $filename = "backup_{$database}_" . Carbon::now()->format('Y-m-d_His') . ".sql";
        $path = storage_path("app/backups/{$filename}");

        // Create backups directory if not exists
        if (!file_exists(storage_path('app/backups'))) {
            mkdir(storage_path('app/backups'), 0755, true);
        }

        // Build mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            escapeshellarg($username),
            escapeshellarg($password),
            escapeshellarg($host),
            escapeshellarg($database),
            escapeshellarg($path)
        );

        $result = null;
        $output = null;
        exec($command, $output, $result);

        if ($result === 0) {
            $size = round(filesize($path) / 1024, 2);
            $this->info("✅ Backup created: {$filename} ({$size} KB)");

            // Keep only last 7 backups
            $this->cleanOldBackups();

            return 0;
        } else {
            $this->error("❌ Backup failed. Exit code: {$result}");
            return 1;
        }
    }

    protected function cleanOldBackups()
    {
        $files = glob(storage_path('app/backups/*.sql'));

        if (count($files) > 7) {
            usort($files, fn($a, $b) => filemtime($a) - filemtime($b));
            $toDelete = array_slice($files, 0, count($files) - 7);

            foreach ($toDelete as $file) {
                unlink($file);
                $this->info("Deleted old backup: " . basename($file));
            }
        }
    }
}
