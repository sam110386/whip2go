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

        return DB::transaction(function () use ($balance, $userid, $transactionid, $note, $orderid, $charged_at) {
            $wallet = self::where('user_id', $userid)->lockForUpdate()->first();

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
        });
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

        return;
    }

    public static function chargeFromWallet($userId, $amt, $note = '', $type = '', $orderId = '')
    {
        $response = ["status" => false, "msg" => "Wallet amount is not enough"];

        return DB::transaction(function () use ($userId, $amt, $note, $type, $orderId, $response) {
            $wallet = self::where('user_id', $userId)->lockForUpdate()->first();

            if (empty($wallet) || $amt > $wallet->balance) {
                return $response;
            }

            $pendings = $amt;
            $transactions = self::consumeFifoTransactions($wallet->id, $amt, $pendings);

            self::subtractBalance($amt, $userId, $note, $orderId);

            return [
                "status" => true,
                "transactions" => $transactions
            ];
        });
    }

    public static function debtFromWallet($userid, $amt, $note = '', $orderid = '')
    {
        if (!$amt) {
            return;
        }

        $return = ["status" => false, "msg" => "Wallet amount is not enough"];

        return DB::transaction(function () use ($userid, $amt, $note, $orderid, $return) {
            $wallet = self::where('user_id', $userid)->lockForUpdate()->first();
            $balanceDeduction = $pendings = $amt;

            if (!empty($wallet)) {
                self::consumeFifoTransactions($wallet->id, $amt, $pendings);
            }

            $return["status"] = true;
            self::subtractBalance($balanceDeduction, $userid, $note, $orderid);
            return $return;
        });
    }

    public static function chargePartialFromWallet($userid, $amt, $note = '', $orderid = '', $type = '')
    {
        $return = ["status" => false, "msg" => "Wallet amount is not enough", "pending" => 0];

        return DB::transaction(function () use ($userid, $amt, $note, $orderid, $type, $return) {
            $wallet = self::where('user_id', $userid)->lockForUpdate()->first();

            if (empty($wallet) || $wallet->balance < 1) {
                return $return;
            }

            $pendings = $amt;
            $transactions = self::consumeFifoTransactions($wallet->id, $amt, $pendings);

            $balanceDeduction = $amt - (float) sprintf('%0.2f', $pendings);
            $return["status"] = true;
            $return['transactions'] = $transactions;
            $return['pending'] = sprintf('%0.2f', $pendings);

            self::subtractBalance($balanceDeduction, $userid, $note, $orderid);
            return $return;
        });
    }

    public static function addBalanceFromPayoutRevshare($balance, $userid, $transactionid, $note, $orderid)
    {
        return DB::transaction(function () use ($balance, $userid, $transactionid, $note, $orderid) {
            $wallet = self::where('user_id', $userid)->lockForUpdate()->first();

            if (empty($wallet) || $wallet->balance >= 0) {
                return false;
            }

            if ($balance > $wallet->balance) {
                $overdue = $balance - abs($wallet->balance);
                $absOldBalance = abs($wallet->balance);

                $wallet->balance += $absOldBalance;
                $wallet->save();

                self::saveWalletTransaction($wallet->id, $absOldBalance, $transactionid, $note, $absOldBalance, $orderid, 1, 1);
                $wallet->balance += $overdue;
                $wallet->save();

                self::saveWalletTransaction($wallet->id, $overdue, $transactionid, $note, $overdue, $orderid, 0);

            } else {
                $wallet->balance += $balance;
                $wallet->save();

                self::saveWalletTransaction($wallet->id, $balance, $transactionid, $note, $balance, $orderid, 1, 1);
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

            return true;
        });
    }

    public static function chargeAmtFromDealerWallet($amt, $userid, $note = "", $orderid = '')
    {
        $return = ["status" => false, "msg" => "Wallet amount is not enough", "pending" => 0];

        return DB::transaction(function () use ($userid, $amt, $note, $orderid, $return) {
            $wallet = self::where('user_id', $userid)->lockForUpdate()->first();

            if (!empty($wallet)) {
                $pendings = $amt;
                $transactions = self::consumeFifoTransactions($wallet->id, $amt, $pendings);

                $return["status"] = true;

                if ($pendings > 0) {
                    $transactions[] = ["amt" => $amt, "transaction_id" => "f_a_k_e", "source" => "wallet"];
                    $pendings = 0;
                }

                $return['pending'] = $pendings;
                $return['transactions'] = $transactions;
                self::subtractBalance($amt, $userid, $note, $orderid);
            } else {
                $return["status"] = true;
                $return['transactions'] = [["amt" => $amt, "transaction_id" => "f_a_k_e", "source" => "wallet"]];
                $return['pending'] = 0;
                self::subtractBalance($amt, $userid, $note, $orderid);
            }

            return $return;
        });
    }

    public static function refundFromWallet($userid, $amt, $note = '', $orderid = '')
    {
        $return = ["status" => false, "msg" => "Wallet amount is not enough"];

        return DB::transaction(function () use ($userid, $amt, $note, $orderid, $return) {
            $wallet = self::where('user_id', $userid)->lockForUpdate()->first();

            if (empty($wallet) || $amt > $wallet->balance) {
                return $return;
            }

            $PaymentProcessorObj = new PaymentProcessor();
            $walletTransactions = self::getFifoTransactions($wallet->id, $amt);

            $TotalRefund = 0;
            $pendings = $amt;
            $status1Ids = [];

            foreach ($walletTransactions as $walletTransaction) {
                if (!$pendings) {
                    break;
                }

                $isFullDeduction = ($walletTransaction->amt <= $pendings);
                $refundAmt = $isFullDeduction ? $walletTransaction->amt : $pendings;

                $refund = $PaymentProcessorObj->refundWalletBalance($refundAmt, $walletTransaction->transaction_id, $orderid);

                if ($refund['status'] == 'success') {
                    $TotalRefund += $refundAmt;
                    $pendings -= $refundAmt;

                    if ($isFullDeduction) {
                        $status1Ids[] = $walletTransaction->id;
                    } else {
                        CsWalletTransaction::where('id', $walletTransaction->id)->update([
                            "amt" => $walletTransaction->amt - $refundAmt
                        ]);
                    }

                    $logAmt = $isFullDeduction ? $refundAmt : $walletTransaction->amt;

                    Reportlib::saveAccountReportData([
                        "user_id" => $userid,
                        "cs_order_id" => $orderid,
                        "rtype" => "C",
                        "type" => 14,
                        "transaction_id" => $walletTransaction->transaction_id,
                        "amt" => $logAmt,
                        "note" => $note
                    ]);

                    ReportPayment::saveWalletRefund([
                        "orderid" => $walletTransaction->cs_order_id,
                        "type" => 2,
                        "transaction_id" => $walletTransaction->transaction_id,
                        "amount" => $logAmt,
                        "source" => "stripe",
                        "charged_at" => $walletTransaction->charged_at
                    ]);
                } else {
                    $return["msg"] = $refund['message'];
                }
            }

            if (!empty($status1Ids)) {
                CsWalletTransaction::whereIn('id', $status1Ids)->update(["status" => 1]);
            }

            if (!$pendings) {
                $return["status"] = true;
            }

            self::subtractBalance($TotalRefund, $userid, $note, $orderid, false);

            return $return;
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

    private static function getFifoTransactions($walletId, $amt)
    {
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

        return DB::select($query, [$walletId, $amt, $amt, $amt]);
    }

    private static function consumeFifoTransactions($walletId, $amt, &$pendings)
    {
        $walletTransactions = self::getFifoTransactions($walletId, $amt);
        $transactions = [];
        $pendings = $amt;
        $status1Ids = [];

        foreach ($walletTransactions as $walletTransaction) {

            if ($pendings <= 0) {
                break;
            }

            $isFullDeduction = ($walletTransaction->amt <= $pendings);
            $deductAmt = $isFullDeduction ? $walletTransaction->amt : $pendings;

            $transactions[] = [
                "amt" => $deductAmt,
                "transaction_id" => $walletTransaction->transaction_id,
                "source" => "wallet",
                "charged_at" => $walletTransaction->charged_at
            ];

            if ($isFullDeduction) {
                $status1Ids[] = $walletTransaction->id;
            } else {
                CsWalletTransaction::where('id', $walletTransaction->id)->update([
                    'amt' => $walletTransaction->amt - $deductAmt
                ]);
            }

            $pendings -= $deductAmt;
        }

        if (!empty($status1Ids)) {
            CsWalletTransaction::whereIn('id', $status1Ids)->update(['status' => 1]);
        }

        return $transactions;
    }
}
