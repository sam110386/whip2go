<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AxleProcessCommand extends Command
{
    protected $signature = 'axle:process';
    protected $description = 'Process Axle insurance cron tasks';

    public function handle(): int
    {
        $lockFile = storage_path('app/AxleShellCron.lock');
        $fp = fopen($lockFile, 'w+');
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $this->process();
            flock($fp, LOCK_UN);
        } else {
            $this->info('Process already running.');
        }
        fclose($fp);
        return 0;
    }

    private function process(): void
    {
        // Placeholder for future cron logic (matches empty legacy shell)
    }
}
