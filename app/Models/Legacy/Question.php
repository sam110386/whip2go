<?php

namespace App\Models\Legacy;

use App\Models\Legacy\LegacyModel;

class Question extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'questions';

    protected $fillable = [
        'question_title',
        'status',
        'created',
    ];
}
