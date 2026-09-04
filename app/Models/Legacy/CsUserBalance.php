<?php

namespace App\Models\Legacy;

use Illuminate\Support\Facades\DB;

class CsUserBalance extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_user_balances';

    protected $fillable = [
        'owner_id',
        'user_id',
        'credit',
        'debit',
        'balance',
        'chargetype',
        'installment_type',
        'installment',
        'installment_day',
        'last_processed',
        'type',
        'note',
        'status',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function csUserBalanceLog()
    {
        return $this->hasMany(CsUserBalanceLog::class, 'user_id', 'user_id');
    }

    public static function addBadDebtRecord(array $data = [], $type = 19): void
    {
        if ($data['amount'] ?? 0 <= 0) {
            return;
        }

        DB::transaction(function () use ($data, $type) {

            CsUserBalanceLog::create([
                'user_id' => $data['user_id'],
                'credit' => $data['amount'],
                'type' => $type,
                'owner_id' => 0,
                'note' => $data['note'] ?? '',
            ]);

            self::create([
                'user_id' => $data['user_id'],
                'owner_id' => 0,
                'credit' => $data['amount'],
                'balance' => $data['amount'],
                'debit' => 0,
                'type' => $type,
                'chargetype' => 'lumpsum',
                'installment_type' => 'daily',
                'installment_day' => null,
                'installment' => 0,
                'note' => $data['note'] ?? '',
            ]);

        });
    }

}
