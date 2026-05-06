<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PromotionRule extends Model
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = 'modified';
    protected $table = 'promotion_rules';

    protected $fillable = [
        'user_id',
        'promo',
        'title',
        'status',
        'type',
        'discount',
        'initial_discount_type',
        'initial_discount',
        'uses_count',
        'conditions',
        'terms',
        'list',
        'logo',
        'created',
        'modified',
    ];
}
