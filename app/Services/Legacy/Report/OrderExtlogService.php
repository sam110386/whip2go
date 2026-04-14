<?php

namespace App\Services\Legacy\Report;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Thin DB::table wrapper for `cs_order_extlogs` (legacy raw-query style).
 */
class OrderExtlogService
{
    protected string $table = 'cs_order_extlogs';

    public function builder(): Builder
    {
        return DB::table($this->table);
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
