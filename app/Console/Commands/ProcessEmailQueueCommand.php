<?php

namespace App\Console\Commands;

use App\Services\Legacy\EmailQueueService;
use Illuminate\Console\Command;

/**
 * Migrated from: app/Plugin/EmailQueue/Console/Command/EmailQueueShell.php
 *
 * Cron: schedule()->command('emailqueue:process')->everyTwoMinutes()
 *
 * Uses file-based locking to prevent overlapping runs.
 */
class ProcessEmailQueueCommand extends Command
{
    protected $signature = 'emailqueue:process';
    protected $description = 'Process pending email queue entries and send receipt emails';

    public function handle(): int
    {
        $lockFile = storage_path('app/EmailQueueCron.lock');
        $fp = fopen($lockFile, 'w+');

        if (!$fp || !flock($fp, LOCK_EX | LOCK_NB)) {
            $this->warn('Another instance is already running.');
            return self::SUCCESS;
        }

        try {
            (new EmailQueueService())->processEmailQueue();
            $this->info('Email queue processed.');
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return self::SUCCESS;
    }
}
