<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverCreditRecordsController extends LegacyAppController
{
    private int $stripeFee = 3;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $keyword = '';
        $fieldname = '';
        $dateFrom = '';
        $dateTo = '';
        $conditions = [['driver_credits.owner_id', '=', $userid]];
        $rawConditions = [];
        $rawBindings = [];

        $search = $request->input('Search', $request->query());

        if (!empty($search['searchin'])) {
            $fieldname = $search['searchin'];
        }
        if (!empty($search['keyword'])) {
            $keyword = $search['keyword'];
        }
        if (!empty($search['date_from'])) {
            $dateFrom = $search['date_from'];
        }
        if (!empty($search['date_to'])) {
            $dateTo = $search['date_to'];
        }

        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = date('Y-m-d');
        }
        if (!empty($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
            $rawConditions[] = 'driver_credits.created >= ?';
            $rawBindings[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->format('Y-m-d');
            $rawConditions[] = 'driver_credits.created <= ?';
            $rawBindings[] = $dateTo;
        }

        $sessLimitName = 'cloud_driver_credit_records_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitName, 25);

        if ($request->input('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $query = DB::table('driver_credits')
            ->leftJoin('users as Renter', 'Renter.id', '=', 'driver_credits.renter_id')
            ->select(
                'driver_credits.*',
                'Renter.first_name as renter_first_name',
                'Renter.last_name as renter_last_name',
                'Renter.contact_number as renter_contact_number'
            )
            ->where('driver_credits.owner_id', $userid);

        foreach ($rawConditions as $i => $cond) {
            $query->whereRaw($cond, [$rawBindings[$i]]);
        }

        $reportlists = $query->orderByDesc('driver_credits.id')
            ->paginate($limit);

        return view('cloud.driver_credit.index', compact(
            'reportlists', 'keyword', 'fieldname', 'dateFrom', 'dateTo'
        ));
    }

    public function credit(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return view('cloud.driver_credit.credit', ['stripeFee' => $this->stripeFee]);
    }

    public function processcedit(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $return = ['status' => 'error', 'message' => 'Sorry, something went wrong'];

        if ($request->ajax() && $request->isMethod('post') && $request->filled('DriverCredit')) {
            $dc = $request->input('DriverCredit');
            if (empty($dc['renter_id']) || empty($userid) || empty($dc['amount'])) {
                return response()->json(['status' => 'error', 'message' => 'Please select all required fields']);
            }
            $data = $request->all();
            $data['DriverCredit']['dealer_id'] = $userid;
            $return = $this->processCreditInternal($data, 0);
        }

        return response()->json($return);
    }

    public function creditdriver(Request $request, $id)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        return redirect()->back()
            ->with('error', 'Sorry, we dont provide this feature now.');
    }

    public function bookingautocomplete(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userid = session('userParentId', 0);
        if ($userid == 0) {
            $userid = session('userid');
        }

        $searchTerm = $request->query('term', '');

        $userlists = DB::table('cs_orders')
            ->where('status', 3)
            ->where('user_id', $userid)
            ->where('increment_id', 'LIKE', "%{$searchTerm}%")
            ->orderBy('id', 'ASC')
            ->limit(10)
            ->select('id', 'increment_id')
            ->get();

        $users = [];
        foreach ($userlists as $j => $value) {
            $users[$j] = [
                'id' => $value->id,
                'tag' => $value->increment_id,
                'encode' => base64_encode($value->id),
            ];
        }

        return response()->json($users);
    }

    private function processCreditInternal(array $data, int $byAdmin = 0): array
    {
        $return = ['status' => 'error', 'message' => 'Please enter correct amount'];
        $dc = $data['DriverCredit'] ?? [];
        $realamount = $dc['amount'] ?? 0;

        if ((int) $realamount < 1) {
            return $return;
        }

        $creditnote = !empty($dc['note']) ? $dc['note'] : ($byAdmin ? 'Credit given by DIA' : 'Credit given by dealer');
        $amount = sprintf('%0.2f', $realamount + $realamount * $this->stripeFee / 100);
        $renterid = $dc['renter_id'];
        $dealerid = $dc['dealer_id'];

        $PaymentProcessorObj = new \PaymentProcessor();
        $return = $PaymentProcessorObj->chargeFromDealer($amount, $dealerid, $creditnote, 12);
        if ($return['status'] == 'error') {
            return $return;
        }

        DB::table('cs_wallets')->insert([
            'amount' => $realamount,
            'user_id' => $renterid,
            'transaction_id' => $return['transaction_id'],
            'note' => $creditnote,
            'created' => now(),
        ]);

        DB::table('driver_credits')->insert([
            'renter_id' => $renterid,
            'owner_id' => $dealerid,
            'by_admin' => $byAdmin,
            'amount' => $realamount,
            'note' => $creditnote,
            'created' => now(),
        ]);

        return ['status' => 'success', 'message' => 'Your request processed successfully'];
    }
}
