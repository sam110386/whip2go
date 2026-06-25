<?php

namespace App\Models\Legacy;

class Page extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'pages';

    protected $fillable = [
        'title',
        'page_code',
        'description',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'created',
        'modified',
        'other_page',
        'status',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

}
