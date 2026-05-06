<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class SchemaMigration extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'schema_migrations';

    protected $fillable = [
        'class',
        'type',
        'created',
    ];
}
