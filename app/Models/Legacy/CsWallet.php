<?php

namespace App\Models\Legacy;
use App\Models\Legacy\CsWalletTransaction;

class CsWallet extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_wallets';

    protected $fillable = [
        'user_id',
        'balance',
        'term',
        'created',
        'updated',
    ];
    protected $hidden = [];
    protected $guarded = [
        'id',
    ];

    public function addBalance($balance, $userid, $transactionid, $note, $orderid, $charged_at = '')
    {
        if ($balance <= 0) {
            return;
        }
        $wallet = self::where('user_id', $userid)->first();
        if ($wallet) {
            $wallet->balance += $balance;
            $wallet->update(['balance' => $wallet->balance, 'updated' => now()]);
        } else {
            $wallet = self::create([
                'user_id' => $userid,
                'balance' => $balance,
                'created' => now(),
                'updated' => now()
            ]);
        }

        $this->savewalletTransaction($wallet->id, $balance, $transactionid, $note, $balance, $orderid, 0, 0, $charged_at, $wallet->balance);

        return $wallet->balance;
    }

    private function savewalletTransaction($walletid, $balance, $transactionid, $note, $amt, $orderid, $type = 0, $status = 0, $charged_at = '', $currentBalance = 0)
    {
        if ($transactionid == 'f_a_k_e') {
            $status = $type = 1;
        }
        CsWalletTransaction::create([
            'cs_wallet_id' => $walletid,
            'amount' => $balance,
            'transaction_id' => $transactionid,
            'cs_order_id' => $orderid,
            'amt' => $amt,
            'note' => str_replace(' ', '_', strtolower(trim($note))),
            'type' => $type,
            'status' => $status,
            'balance' => $currentBalance,
            'charged_at' => $charged_at ?: now()->toDateTimeString(),
            'created' => now()
        ]);
    }
}
