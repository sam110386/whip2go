<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Port of CakePHP app/Lib/Currency.php
 * Fetches and caches USD-based exchange rates from currencyapi.com.
 */
class Currency
{
    private string $cacheKey = 'currencies';

    public function pull(): void
    {
        $data = $this->fetch();
        $decoded = json_decode($data, true);
        if (empty($decoded['data'])) {
            return;
        }
        Cache::put($this->cacheKey, $data, now()->addDays(7));
    }

    public function fetch(): string
    {
        $apiKey = config('services.currency_api.key', 'bwUajNF6BKhhhHvFxvmrPProrCPP0HpmY9MtxKSO');
        $url = "https://api.currencyapi.com/v3/latest?apikey={$apiKey}&base_currency=USD";

        $response = Http::withoutVerifying()->timeout(30)->get($url);

        return $response->body() ?: '';
    }

    public function getCurrencyRate(string $code = 'CAD'): string
    {
        $data = Cache::get($this->cacheKey);
        $decoded = json_decode($data, true);
        if (!empty($decoded)) {
            return isset($decoded['data'][$code]['value'])
                ? sprintf('%.4f', $decoded['data'][$code]['value'])
                : '1.3877';
        }
        return '1.3877';
    }
}
