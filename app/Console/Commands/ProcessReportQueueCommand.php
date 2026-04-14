<?php

namespace App\Console\Commands;

use App\Services\Legacy\Report\ReportCustomerlibService;
use Illuminate\Console\Command;

class ProcessReportQueueCommand extends Command
{
    protected $signature = 'report:process-queue';

    protected $description = 'Process the report generation queue (legacy ReportShell equivalent)';

    public function handle(ReportCustomerlibService $service): int
    {
        $lockFile = storage_path('app/reportCron.txt');
        $fp = fopen($lockFile, 'w+');

        if ($fp === false) {
            $this->error('Could not open lock file: '.$lockFile);

            return self::FAILURE;
        }

        $locked = flock($fp, LOCK_EX | LOCK_NB);

        try {
            if ($locked) {
                $service->processQueue();
            } else {
                $this->info('Report processing already running.');
            }
        } finally {
            if ($locked) {
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        return self::SUCCESS;
    }
}
