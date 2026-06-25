<?php

namespace App\Models\Legacy;

class PlaidUser extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'plaid_users';

    protected $fillable = [
        'user_id',
        'token',
        'user_token',
        'metadata',
        'link_session_id',
        'link_token',
        'plaid_user_id',
        'paystub',
        'created',
    ];
    protected $hidden = [
        'token',
    ];
    protected $guarded = [
        'id',
    ];


    public static function getUserFlags($userId)
    {
        $plaids = self::where('user_id', $userId)
            ->whereNotNull('token')
            ->where('token', '!=', '')
            ->get();

        $paystub = $plaids->contains('paystub', 1);
        $paybank = $plaids->contains('paystub', 0);

        return [$paystub, $paybank];
    }

}
