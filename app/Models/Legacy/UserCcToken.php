<?php

namespace App\Models\Legacy;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCcToken extends LegacyModel
{
    protected $table = 'user_cc_tokens';

    protected $fillable = [
        'user_id',
        'address',
        'city',
        'state',
        'country',
        'zip',
        'card_type',
        'card_holder_name',
        'credit_card_number',
        'expiration',
        'cvv',
        'status',
        'card_funding',
        'stripe_token',
        'card_id',
        'created',
        'modified',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
