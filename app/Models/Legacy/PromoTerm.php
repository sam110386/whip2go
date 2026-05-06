<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Model;

class PromoTerm extends Model
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'promo_terms';

    protected $fillable = [
        'user_id',
        'promo_rule_id',
        'status',
        'created',
    ];
}
