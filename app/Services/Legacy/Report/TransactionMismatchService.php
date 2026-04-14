<?php

namespace App\Services\Legacy\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Thin DB::table wrapper for `transaction_mismatches` and its reporting view.
 */
class TransactionMismatchService
{
    protected string $table = 'transaction_mismatches';

    protected string $viewTable = 'transaction_mismatches_view';

    public function builder(): Builder
    {
        return DB::table($this->table);
    }

    public function viewBuilder(): Builder
    {
        return DB::table($this->viewTable);
    }

    /**
     * @param  int|string  $id
     */
    public function findById($id): ?object
    {
        return $this->builder()->where('id', $id)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function save(array $attributes): bool
    {
        if (! empty($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);

            return $this->builder()->where('id', $id)->update($attributes) !== false;
        }

        return $this->builder()->insert($attributes);
    }
}
