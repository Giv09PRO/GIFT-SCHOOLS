<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogs extends Command
{
    protected $signature = 'logs:clear';
    protected $description = 'Clear all Laravel log files in storage/logs';

    public function handle()
    {
        $logPath = storage_path('logs');

        if (!File::exists($logPath)) {
            $this->error("Log directory not found.");
            return 1;
        }

        $files = File::files($logPath);

        foreach ($files as $file) {
            File::put($file, ''); // Clear the file content
        }

        $this->info('All Laravel log files have been cleared.');
        return 0;
    }
}
