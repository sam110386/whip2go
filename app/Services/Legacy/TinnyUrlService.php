<?php

namespace App\Services\Legacy;

use App\Helpers\Legacy\Security;
use App\Models\Legacy\TinnyUrl;
use Carbon\Carbon;

/**
 * Migrated from: app/Plugin/TinnyUrl/Lib/TinnyUrlLib.php
 */

class TinnyUrlService
{
    public function generate($url = null)
    {
        $url = parse_url($url);

        if (!isset($url['path']) || empty($url['path'])) {
            return $url;
        }

        $url = $url['path'];
        $shortkey = Security::hash($url . time());

        TinnyUrl::create([
            'ukey' => $shortkey,
            'target' => $url,
            'expire' => Carbon::now()->addHours(5),
        ]);

        return url('/g/' . $shortkey);
    }
}
