<?php

namespace App\Models\Legacy;

use Illuminate\Support\Facades\DB;
use App\Models\Legacy\CsWalletTransaction;
use App\Services\Legacy\PaymentProcessor;
use App\Services\Legacy\ReportPayment;
use App\Services\Legacy\Reportlib;


class CsWallet extends LegacyModel
{
    public $timestamps = true;
    const CREATED_AT = 'created';
    const UPDATED_AT = null;
    protected $table = 'cs_wallets';

    private static $_currntBal;

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
            return 0;
        }

        $wallet = self::where('user_id', $userid)->first();
        $finalBalance = 0;

        if (!empty($wallet)) {
            if ($balance > $wallet->balance && $wallet->balance < 0) {
                self::$_currntBal = $overdue = $balance - abs($wallet->balance);
                $wallet->balance = $overdue;
                $wallet->save();
                $status = 0;

                if ($overdue <= 0) {
                    $overdue = 0;
                    $status = 1;
                }

                self::saveWalletTransaction($wallet->id, $balance, $transactionid, $note, $overdue, $orderid, 0, $status, $charged_at);

            } else {
                self::$_currntBal = $wallet->balance + $balance;
                $wallet->balance = self::$_currntBal;
                $wallet->save();
                self::saveWalletTransaction($wallet->id, $balance, $transactionid, $note, $balance, $orderid, 0, 0, $charged_at);
            }

        } else {
            self::$_currntBal = $balance;
            $wallet = new self();
            $wallet->user_id = $userid;
            $wallet->balance = self::$_currntBal;
            $wallet->save();

            self::saveWalletTransaction(
                $wallet->id,
                $balance,
                $transactionid,
                $note,
                $balance,
                $orderid,
                0,
                0,
                $charged_at
            );
        }

        Reportlib::saveAccountReportData([
            "user_id" => $userid,
            "cs_order_id" => $orderid,
            "rtype" => "C",
            "type" => 13,
            "transaction_id" => $transactionid,
            "amt" => $balance,
            "note" => $note
        ]);

        return self::$_currntBal;
    }

    public static function subtractBalance($balance, $userid, $note, $orderid = '', $saveAccounting = true)
    {
        if ($balance <= 0) {
            return;
        }

        $wallet = self::where('user_id', $userid)->first();

        if ($wallet) {
            $wallet->balance -= $balance;
        } else {
            $wallet = new self();
            $wallet->user_id = $userid;
            $wallet->balance = -$balance;
        }

        $wallet->save();

        self::$_currntBal = $wallet->balance;
        self::saveWalletTransaction($wallet->id, "-" . $balance, "", $note, $balance, $orderid, 1);

        if (!$saveAccounting) {
            return;
        }

        return;
    }

    public static function chargeFromWallet($userId, $amt, $note = '', $type = '', $orderId = '')
    {
        $response = ["status" => false, "msg" => "Wallet amount is not enough"];

        return DB::transaction(function () use ($userId, $amt, $note, $type, $orderId, $response) {

            $wallet = self::where('user_id', $userId)->first();

            if (empty($wallet) || $amt > $wallet->balance) {
                return $response;
            }

            $query = "SELECT CsWalletTransaction.* FROM (
                        SELECT *, (@sum := @sum + amt) as cume_stock
                        FROM cs_wallet_transactions CROSS JOIN (SELECT @sum := 0) params
                        WHERE status = 0 
                        AND type = 0 
                        AND amt > 0 
                        AND transaction_id != '' 
                        AND cs_wallet_id = ? 
                        ORDER BY id ASC
                    ) CsWalletTransaction
                    WHERE cume_stock < ? OR (cume_stock >= ? AND cume_stock - amt < ?);";

            $walletTransactions = DB::select($query, [$wallet->id, $amt, $amt, $amt]);

            $transactions = [];
            $pendings = $amt;

            foreach ($walletTransactions as $walletTransaction) {

                if (!$pendings) {
                    break;
                }

                if ($walletTransaction->amt < $pendings) {
                    $pendings -= $walletTransaction->amt;
                    $transactions[] = [
                        "amt" => $walletTransaction->amt,
                        "transaction_id" => $walletTransaction->transaction_id,
                        "source" => "wallet",
                        "charged_at" => $walletTransaction->charged_at
                    ];

                    CsWalletTransaction::where('id', $walletTransaction->id)->update(['status' => 1]);

                } elseif ($walletTransaction->amt == $pendings) {

                    $transactions[] = [
                        "amt" => $walletTransaction->amt,
                        "transaction_id" => $walletTransaction->transaction_id,
                        "source" => "wallet",
                        "charged_at" => $walletTransaction->charged_at
                    ];

                    CsWalletTransaction::where('id', $walletTransaction->id)->update(['status' => 1]);
                    $pendings = 0;

                } else {
                    $transactions[] = [
                        "amt" => $pendings,
                        "transaction_id" => $walletTransaction->transaction_id,
                        "source" => "wallet",
                        "charged_at" => $walletTransaction->charged_at
                    ];

                    CsWalletTransaction::where('id', $walletTransaction->id)->update(['amt' => $walletTransaction->amt - $pendings]);
                    $pendings = 0;
                }
            }

            self::subtractBalance($amt, $userId, $note, $orderId);

            return [
                "status" => true,
                "transactions" => $transactions
            ];
        });
    }

    private static function savewalletTransaction($walletid, $balance, $transactionid, $note, $amt, $orderid, $type = 0, $status = 0, $charged_at = '', $currentBalance = 0)
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
