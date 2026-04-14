<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Migrated from: app/Plugin/TinnyUrl/Lib/TinnyUrlLib.php
 *
 * Generates short URLs stored in tinny_urls table.
 */
class TinnyUrlService
{
    public function generate(?string $url): string
    {
        $parsed = parse_url($url);
        if (!isset($parsed['path']) || empty($parsed['path'])) {
            return $url ?? '';
        }

        $path = $parsed['path'];
        $shortkey = hash('sha256', $path . time());

        DB::table('tinny_urls')->insert([
            'ukey'   => $shortkey,
            'target' => $path,
            'expire' => now()->addHours(5)->toDateTimeString(),
        ]);

        return config('app.url') . '/g/' . $shortkey;
    }
}
