<?php

namespace App\Helpers\Legacy;

use InvalidArgumentException;
use function in_array;
use function is_array;
use function is_string;
use function sprintf;
use function is_int;

class Number
{
    protected static $_currencies = [
        'AUD' => [
            'wholeSymbol' => '$',
            'wholePosition' => 'before',
            'fractionSymbol' => 'c',
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => ',',
            'decimals' => '.',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 2
        ],
        'CAD' => [
            'wholeSymbol' => '$',
            'wholePosition' => 'before',
            'fractionSymbol' => 'c',
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => ',',
            'decimals' => '.',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 2
        ],
        'USD' => [
            'wholeSymbol' => '$',
            'wholePosition' => 'before',
            'fractionSymbol' => 'c',
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => ',',
            'decimals' => '.',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 2
        ],
        'EUR' => [
            'wholeSymbol' => '€',
            'wholePosition' => 'before',
            'fractionSymbol' => false,
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => '.',
            'decimals' => ',',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 0
        ],
        'GBP' => [
            'wholeSymbol' => '£',
            'wholePosition' => 'before',
            'fractionSymbol' => 'p',
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => ',',
            'decimals' => '.',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 2
        ],
        'JPY' => [
            'wholeSymbol' => '¥',
            'wholePosition' => 'before',
            'fractionSymbol' => false,
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => ',',
            'decimals' => '.',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 0
        ],
        'INR' => [
            'wholeSymbol' => '₹',
            'wholePosition' => 'before',
            'fractionSymbol' => 'p',
            'fractionPosition' => 'after',
            'zero' => 0,
            'places' => 2,
            'thousands' => ',',
            'decimals' => '.',
            'negative' => '()',
            'escape' => true,
            'fractionExponent' => 2
        ],
    ];

    protected static $_currencyDefaults = [
        'wholeSymbol' => '',
        'wholePosition' => 'before',
        'fractionSymbol' => false,
        'fractionPosition' => 'after',
        'zero' => '0',
        'places' => 2,
        'thousands' => ',',
        'decimals' => '.',
        'negative' => '()',
        'escape' => true,
        'fractionExponent' => 2
    ];

    protected static $_defaultCurrency = 'USD';

    protected static $_numberFormatSupport = null;

    public static function precision($value, $precision = 3)
    {
        return sprintf("%01.{$precision}f", $value);
    }

    public static function toReadableSize($size)
    {
        return match (true) {
            $size < 1024 => trans_choice('%d Byte|%d Bytes', $size, ['count' => $size]),
            round($size / 1024) < 1024 => sprintf('%s KB', static::precision($size / 1024, 0)),
            round($size / 1024 / 1024, 2) < 1024 => sprintf('%s MB', static::precision($size / 1024 / 1024, 2)),
            round($size / 1024 / 1024 / 1024, 2) < 1024 => sprintf('%s GB', static::precision($size / 1024 / 1024 / 1024, 2)),
            default => sprintf('%s TB', static::precision($size / 1024 / 1024 / 1024 / 1024, 2)),
        };
    }

    public static function fromReadableSize($size, $default = false)
    {
        if (ctype_digit($size)) {
            return (int) $size;
        }
        $size = strtoupper($size);

        $l = -2;

        $i = array_search(substr($size, -2), ['KB', 'MB', 'GB', 'TB', 'PB']);

        if ($i === false) {
            $l = -1;
            $i = array_search(substr($size, -1), ['K', 'M', 'G', 'T', 'P']);
        }

        if ($i !== false) {
            $size = substr($size, 0, $l);
            return $size * pow(1024, $i + 1);
        }

        if (substr($size, -1) === 'B' && ctype_digit(substr($size, 0, -1))) {
            $size = substr($size, 0, -1);
            return (int) $size;
        }

        if ($default !== false) {
            return $default;
        }

        throw new InvalidArgumentException('No unit type.');
    }

    public static function toPercentage($value, $precision = 2, $options = [])
    {
        $options += ['multiply' => false];
        if ($options['multiply']) {
            $value *= 100;
        }
        return static::precision($value, $precision) . '%';
    }


    public static function format($value, $options = false)
    {
        $places = 0;

        if (is_int($options)) {
            $places = $options;
        }

        $separators = [',', '.', '-', ':'];

        $before = $after = null;

        if (is_string($options) && !in_array($options, $separators)) {
            $before = $options;
        }

        $thousands = ',';

        if (!is_array($options) && in_array($options, $separators)) {
            $thousands = $options;
        }

        $decimals = '.';

        if (!is_array($options) && in_array($options, $separators)) {
            $decimals = $options;
        }

        $escape = true;

        if (is_array($options)) {
            $defaults = ['before' => '$', 'places' => 2, 'thousands' => ',', 'decimals' => '.'];
            $options += $defaults;
            extract($options);
        }

        $value = static::_numberFormat($value, $places, '.', '');
        $out = $before . static::_numberFormat($value, $places, $decimals, $thousands) . $after;

        if ($escape) {
            return e($out);
        }
        return $out;
    }

    public static function formatDelta($value, $options = [])
    {
        $places = $options['places'] ?? 0;
        $value = static::_numberFormat($value, $places, '.', '');
        $sign = $value > 0 ? '+' : '';
        $options['before'] = isset($options['before']) ? $options['before'] . $sign : $sign;
        return static::format($value, $options);
    }

    protected static function _numberFormat($value, $places = 0, $decimals = '.', $thousands = ',')
    {
        if (!isset(static::$_numberFormatSupport)) {
            static::$_numberFormatSupport = version_compare(PHP_VERSION, '5.4.0', '>=');
        }

        if (static::$_numberFormatSupport) {
            return number_format($value, $places, $decimals, $thousands);
        }

        $value = number_format($value, $places, '.', '');
        $after = '';
        $foundDecimal = strpos($value, '.');

        if ($foundDecimal !== false) {
            $after = substr($value, $foundDecimal);
            $value = substr($value, 0, $foundDecimal);
        }

        while (($foundThousand = preg_replace('/(\d+)(\d\d\d)/', '\1 \2', $value)) !== $value) {
            $value = $foundThousand;
        }

        $value .= $after;

        return strtr($value, [' ' => $thousands, '.' => $decimals]);
    }
    public static function currency($value, $currency = null, $basecurrency = null, $options = [])
    {
        $defaults = static::$_currencyDefaults;
        if ($currency === null) {
            $currency = static::defaultCurrency();
        }

        if (isset(static::$_currencies[$currency])) {
            $defaults = static::$_currencies[$currency];
        } elseif (is_string($currency)) {
            $options['before'] = $currency;
        }

        $options += $defaults;

        if (isset($options['before']) && $options['before'] !== '') {
            $options['wholeSymbol'] = $options['before'];
        }
        if (isset($options['after']) && $options['after'] !== '') {
            $options['fractionSymbol'] = $options['after'];
        }

        $result = $options['before'] = $options['after'] = null;

        $symbolKey = 'whole';
        $value = (float) $value;

        if (!$value) {
            if ($options['zero'] !== 0) {
                return $options['zero'];
            }
        } elseif ($value < 1 && $value > -1) {
            if ($options['fractionSymbol'] !== false) {
                $multiply = pow(10, $options['fractionExponent']);
                $value = $value * $multiply;
                $options['places'] = null;
                $symbolKey = 'fraction';
            }
        }

        $position = $options[$symbolKey . 'Position'] !== 'after' ? 'before' : 'after';
        $options[$position] = $options[$symbolKey . 'Symbol'];

        $abs = abs($value);
        $result = static::format($abs, $options);

        if ($value < 0) {
            $result = $options['negative'] === '()' ? "({$result})" : $options['negative'] . $result;
        }
        return $result;
    }
    public static function addFormat($formatName, $options)
    {
        static::$_currencies[$formatName] = $options + static::$_currencyDefaults;
    }

    public static function defaultCurrency($currency = null)
    {
        if ($currency) {
            static::$_defaultCurrency = $currency;
        }
        return static::$_defaultCurrency;
    }

    public static function getCurrencies()
    {
        $keys = array_keys(static::$_currencies);
        return array_combine($keys, $keys);
    }

    public static function getCurrencySymbol($currency)
    {
        return isset(static::$_currencies[$currency]) ? static::$_currencies[$currency]['wholeSymbol'] : '$';
    }
}
