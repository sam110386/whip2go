<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Port of CakePHP app/Lib/DiaError.php
 * Logs errors to a dedicated channel and optionally emails admin.
 */
class DiaError
{
    public function logError(string $module, string $message, string $severity = 'WARN', bool $email = false): void
    {
        if (app()->environment('local')) {
            return;
        }

        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $bt[0] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;

        $logLine = "[{$severity}][{$file}][{$line}]: {$message}";

        match (strtoupper($severity)) {
            'ERROR' => Log::channel('diaerror')->error($logLine),
            'CRITICAL' => Log::channel('diaerror')->critical($logLine),
            default => Log::channel('diaerror')->warning($logLine),
        };

        if ($email) {
            try {
                Mail::raw("{$module}: {$message}", function ($m) use ($module) {
                    $m->from('no-reply@whip2go.com')
                      ->to('adam@driveitaway.com')
                      ->subject("ERROR ALERT - {$module}");
                });
            } catch (\Throwable $e) {
                Log::warning("DiaError: failed to send alert email – {$e->getMessage()}");
            }
        }
    }
}
