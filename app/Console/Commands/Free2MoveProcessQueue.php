<?php

namespace App\Console\Commands;

use App\Services\Legacy\Free2MoveService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Free2MoveProcessQueue extends Command
{
    protected $signature = 'free2move:process-queue';

    protected $description = 'Process Free2Move queue items (migrated from Free2MoveShell)';

    public function handle(): int
    {
        $lockFile = storage_path('app/Free2MoveShellCron.lock');
        $fp = fopen($lockFile, 'w+');

        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            $this->info('Process already running.');
            fclose($fp);
            return self::SUCCESS;
        }

        try {
            $this->processQueue();
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }

        return self::SUCCESS;
    }

    private function processQueue(): void
    {
        $queue = DB::table('free2move_queue')
            ->where('status', 0)
            ->limit(50)
            ->get();

        if ($queue->isEmpty()) {
            $this->info('No Free2MoveQueue items to process.');
            DB::statement('TRUNCATE TABLE free2move_queue');
            return;
        }

        $api = new Free2MoveService();

        foreach ($queue as $item) {
            DB::table('free2move_queue')
                ->where('id', $item->id)
                ->update(['status' => 2]);

            $api->callApi(json_decode($item->data, true));

            DB::table('free2move_queue')
                ->where('id', $item->id)
                ->update(['status' => 1]);
        }
    }
}
