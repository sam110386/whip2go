<?php

namespace App\Models\Legacy;

class EmailTemplate extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'email_templates';

    protected $fillable = [
        'providers',
        'customers',
        'title',
        'head_title',
        'subject',
        'description',
        'status',
        'type',
        'reminder_time',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
