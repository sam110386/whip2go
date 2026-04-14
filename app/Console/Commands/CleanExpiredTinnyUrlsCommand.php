<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrated from: app/Plugin/TinnyUrl/Console/Command/TinnyUrlShell.php
 *
 * Cron: schedule()->command('tinnyurl:clean')->everyThreeHours()
 */
class CleanExpiredTinnyUrlsCommand extends Command
{
    protected $signature = 'tinnyurl:clean';
    protected $description = 'Delete expired short URLs from tinny_urls table';

    public function handle(): int
    {
        $deleted = DB::table('tinny_urls')
            ->where('expire', '<', now()->toDateTimeString())
            ->delete();

        $this->info("Deleted {$deleted} expired tinny_url records.");

        return self::SUCCESS;
    }
}
