<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\DB;

trait VerifiesEmail
{
    /**
     * Verifies if a user with the given email exists in the specified model table.
     * Mimics CakePHP verifyEmail behavior.
     *
     * @param string|null $model Model name to query (or table name).
     * @param string|null $email Email address to verify.
     * @return bool True if email exists, false otherwise.
     */
    public function verifyEmail(?string $model, ?string $email): bool
    {
        if (empty($email) || empty($model)) {
            return false;
        }

        // Simplistic mapping for common models to tables
        $tables = [
            'User' => 'users',
            'LegacyUser' => 'users',
            'Admin' => 'users',
        ];

        $table = $tables[$model] ?? strtolower($model . 's');

        return DB::table($table)->where('email', 'like', $email)->exists();
    }
}
