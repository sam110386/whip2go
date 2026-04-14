<?php

namespace App\Services\Legacy;

use NumberFormatter as IntlNumberFormatter;

/**
 * Ported from CakePHP app/Controller/Component/NumberComponent.php
 *
 * Wraps PHP's intl NumberFormatter to provide precision, currency,
 * readable-size, and percentage formatting previously backed by CakeNumber.
 */
class NumberFormatter
{
    protected string $defaultCurrency = 'USD';

    protected array $customFormats = [];

    private static array $currencySymbols = [
        'USD' => '$',
        'CAD' => 'CA$',
        'EUR' => '€',
        'GBP' => '£',
        'AUD' => 'A$',
        'JPY' => '¥',
        'INR' => '₹',
        'MXN' => 'MX$',
        'BRL' => 'R$',
        'CHF' => 'CHF',
        'CNY' => 'CN¥',
    ];

    public function precision($number, int $precision = 3): string
    {
        return number_format((float) $number, $precision, '.', '');
    }

    public function toReadableSize($size): string
    {
        $size = (int) $size;

        if ($size < 1) {
            return '0 Bytes';
        }

        $units = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = (int) floor(log($size, 1024));
        $i = min($i, count($units) - 1);

        return round($size / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function toPercentage($number, int $precision = 2, array $options = []): string
    {
        $multiply = $options['multiply'] ?? false;
        $value = $multiply ? (float) $number * 100 : (float) $number;

        return number_format($value, $precision) . '%';
    }

    public function format($number, $options = false): string
    {
        if ($options === false || $options === null) {
            $fmt = new IntlNumberFormatter('en_US', IntlNumberFormatter::DECIMAL);
            return $fmt->format((float) $number);
        }

        if (is_int($options)) {
            return number_format((float) $number, $options);
        }

        if (is_string($options)) {
            return $options . number_format((float) $number, 2);
        }

        if (is_array($options)) {
            $places = $options['places'] ?? 2;
            $before = $options['before'] ?? '';
            $after  = $options['after'] ?? '';
            return $before . number_format((float) $number, $places) . $after;
        }

        return (string) $number;
    }

    public function currency($number, ?string $currency = null, ?string $basecurrency = null, array $options = []): string
    {
        $currency = $currency ?: $this->defaultCurrency;

        if (isset($this->customFormats[$currency])) {
            $fmt = $this->customFormats[$currency];
            $before = $fmt['before'] ?? '';
            $after  = $fmt['after'] ?? '';
            $places = $fmt['places'] ?? 2;
            return $before . number_format((float) $number, $places) . $after;
        }

        if (extension_loaded('intl')) {
            $locale = $options['locale'] ?? 'en_US';
            $fmt = new IntlNumberFormatter($locale, IntlNumberFormatter::CURRENCY);
            return $fmt->formatCurrency((float) $number, $currency);
        }

        $symbol = self::getCurrencySymbol($currency);
        return $symbol . number_format((float) $number, 2);
    }

    public function addFormat(string $formatName, array $options): void
    {
        $this->customFormats[$formatName] = $options;
    }

    public function defaultCurrency(?string $currency = null): string
    {
        if ($currency !== null) {
            $this->defaultCurrency = $currency;
        }
        return $this->defaultCurrency;
    }

    public function getCurrencies(): array
    {
        return self::$currencySymbols;
    }

    /**
     * Resolve a currency code to its symbol.
     * Used as a drop-in for CakeNumber::getCurrencySymbol().
     */
    public static function getCurrencySymbol(?string $currency = null): string
    {
        if ($currency === null) {
            return '$';
        }

        if (isset(self::$currencySymbols[$currency])) {
            return self::$currencySymbols[$currency];
        }

        if (extension_loaded('intl')) {
            $fmt = new IntlNumberFormatter('en_US@currency=' . $currency, IntlNumberFormatter::CURRENCY);
            $symbol = $fmt->getSymbol(IntlNumberFormatter::CURRENCY_SYMBOL);
            if ($symbol) {
                return $symbol;
            }
        }

        return $currency;
    }
}
