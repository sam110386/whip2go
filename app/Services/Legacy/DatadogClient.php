<?php

namespace App\Services\Legacy;

use Exception;

/**
 * Ported from CakePHP app/Controller/Component/DatadogComponent.php
 *
 * Simple metrics client for Datadog via DogStatsd.
 */
class DatadogClient
{
    private string $apiKey;
    private string $appKey;

    // TODO: Install datadog/php-datadogstatsd via Composer and
    //       replace with: use DataDog\DogStatsd;
    private $dogStatsd = null;

    public function __construct()
    {
        $this->apiKey = config('services.datadog.api_key', '');
        $this->appKey = config('services.datadog.app_key', '');
    }

    private function startme(): void
    {
        if ($this->dogStatsd !== null) {
            return;
        }

        // TODO: Once datadog/php-datadogstatsd is installed, uncomment:
        // $this->dogStatsd = new \DataDog\DogStatsd([
        //     'api_key' => $this->apiKey,
        //     'app_key' => $this->appKey,
        // ]);

        if (!class_exists('\\DataDog\\DogStatsd')) {
            return;
        }

        $this->dogStatsd = new \DataDog\DogStatsd([
            'api_key' => $this->apiKey,
            'app_key' => $this->appKey,
        ]);
    }

    public function increment(string $name): void
    {
        if (app()->environment('local')) {
            return;
        }

        try {
            $this->startme();
            if ($this->dogStatsd) {
                $this->dogStatsd->increment($name);
            }
        } catch (Exception $e) {
            // silently ignore
        }
    }

    public function timing(string $name, $time, string $tagname = ''): void
    {
        if (app()->environment('local')) {
            return;
        }

        try {
            $this->startme();
            if ($this->dogStatsd) {
                $this->dogStatsd->timing($name, $time, 1, ['tagname' => $tagname]);
            }
        } catch (Exception $e) {
            // silently ignore
        }
    }
}
