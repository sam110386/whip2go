<?php

namespace App\Models\Legacy;

class EmailTemplate extends LegacyModel
{
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
