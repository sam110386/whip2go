<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\CsLeaseAvailability;
use App\Models\Legacy\CsLease;
use Illuminate\Http\Request;

class LeaseHistoriesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }

        $value = $paymentMethod = $addressType = $fieldname = $statusType = $dateFrom = $dateTo = '';

        // Mimic Cake PHP's request data merging
        $searchData = $request->input('Search', []);
        $namedData = $request->query();

        $fieldname = $namedData['searchin'] ?? $searchData['searchin'] ?? '';
        $value = $namedData['keyword'] ?? $searchData['keyword'] ?? '';
        $paymentMethod = $namedData['payment_method'] ?? $searchData['payment_method'] ?? '';
        $dateFrom = $namedData['date_from'] ?? $searchData['date_from'] ?? '';
        $dateTo = $namedData['date_to'] ?? $searchData['date_to'] ?? '';
        $statusType = $namedData['status_type'] ?? $searchData['status_type'] ?? '';

        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = date('Y-m-d');
        }

        $query = CsLeaseAvailability::query()
            ->from('cs_lease_availabilities as CsLeaseAvailability')
            ->leftJoin('cs_leases as Lease', 'Lease.id', '=', 'CsLeaseAvailability.lease_id')
            ->leftJoin('users as User', 'User.id', '=', 'Lease.user_id')
            ->select('CsLeaseAvailability.*', 'Lease.*', 'User.unique_code')
            ->selectRaw('(Lease.fare * 0.025) AS black_car_fund')
            ->where('CsLeaseAvailability.user_id', $userId);

        if (!empty($fieldname)) {
            $fieldnameParsed = date('Y-m-d', strtotime($fieldname));
            $query->whereDate('CsLeaseAvailability.start_date', $fieldnameParsed);
        }

        if (!empty($value)) {
            $addressType = $namedData['type'] ?? $searchData['show_address'] ?? '';
            if ($addressType === '1') {
                $query->where('CsLeaseAvailability.pickup_address', 'LIKE', '%' . $value . '%');
            } elseif ($addressType === '2') {
                $query->where('Lease.vehicle_unique_id', $value);
            } elseif ($addressType === '3') {
                $query->where('CsLeaseAvailability.id', $value);
            } elseif ($addressType === '4') {
                $query->where('CsLeaseAvailability.telephone', $value);
            }
        }

        if (!empty($dateFrom)) {
            $query->whereDate('CsLeaseAvailability.start_date', '>=', date('Y-m-d', strtotime($dateFrom)));
        }
        if (!empty($dateTo)) {
            $query->whereDate('CsLeaseAvailability.start_date', '<=', date('Y-m-d', strtotime($dateTo)));
        }

        if (!empty($statusType)) {
            if ($statusType === 'cancel') {
                $query->where('CsLeaseAvailability.status', '2');
            } elseif ($statusType === 'complete') {
                $query->where('CsLeaseAvailability.status', '3');
            } elseif ($statusType === 'incomplete') {
                $query->where('CsLeaseAvailability.status', '!=', '3');
            }
        }

        // Pagination limit
        $sessionLimitKey = 'LeaseHistories_limit';
        $limitFromSession = session($sessionLimitKey, 20); // Fallback to assumed 20 default
        $limit = $request->input('Record.limit', $limitFromSession);
        
        if ($limit < 1) {
            $limit = 20;
        }
        session([$sessionLimitKey => $limit]);

        $tripLog = $query->orderBy('CsLeaseAvailability.id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.lease_histories.index', [
            'listTitle' => 'Reports',
            'title_for_layout' => 'Reports',
            'triploglist' => $tripLog,
            'keyword' => $value,
            'payment_method' => $paymentMethod,
            'fieldname' => $fieldname,
            'address_type' => $addressType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'status_type' => $statusType,
            'limit' => $limit
        ]);
    }

    public function cancel_lease($id = null)
    {
        $id = base64_decode($id);
        if ($id) {
            CsLease::where('id', $id)->update(['status' => 2]);
            return redirect('/lease_histories/index')->with('success', 'Record updated successfully.');
        }
        return redirect('/lease_histories/index')->with('error', 'Invalid ID.');
    }

    public function auto_complete($id = null)
    {
        $id = base64_decode($id);
        if ($id) {
            CsLease::where('id', $id)->update(['status' => 3]);
            return redirect('/lease_histories/index')->with('success', 'Lease autocompleted successfully.');
        }
        return redirect('/lease_histories/index')->with('error', 'Invalid ID.');
    }

    public function lease_details($id)
    {
        // Legacy returned ajax layout
        $dispacherId = session('dispacherParentId');
        if (empty($dispacherId) || $dispacherId == 0) {
            $dispacherId = session('dispacherId');
        }

        $data = CsLease::query()
            ->from('cs_leases as Lease')
            ->leftJoin('users as User', 'User.id', '=', 'Lease.user_id')
            ->select('Lease.*', 'User.unique_code')
            ->selectRaw('(Lease.fare * 0.025) AS black_car_fund')
            ->where('Lease.id', $id)
            ->first();

        return view('legacy.lease_histories.lease_details', [
            'title_for_layout' => 'Lease Detail',
            'triplog' => $data ? $data->toArray() : []
        ]);
    }

    public function edit_lease_details(Request $request, $id)
    {
        $dispacherId = session('dispacherParentId');
        if (empty($dispacherId) || $dispacherId == 0) {
            $dispacherId = session('dispacherId');
        }

        $decodedId = base64_decode($id);

        if ($request->isMethod('post')) {
            $updateData = $request->input('Lease', []);
            $lease = CsLease::find($decodedId);

            if ($lease) {
                // Ensure ID is not overwritten
                unset($updateData['id']);
                try {
                    $lease->fill($updateData);
                    $lease->save();
                    return redirect('/lease_histories/index')->with('success', 'Lease record updated successfully.');
                } catch (\Exception $e) {
                    return redirect('/lease_histories/index')->with('error', 'Sorry, something went wrong.');
                }
            }
            return redirect('/lease_histories/index')->with('error', 'Lease not found.');
        }

        $data = CsLease::query()
            ->from('cs_leases as Lease')
            ->leftJoin('users as User', 'User.id', '=', 'Lease.user_id')
            ->select('Lease.*', 'User.unique_code')
            ->selectRaw('(Lease.fare * 0.025) AS black_car_fund')
            ->where('Lease.id', $decodedId)
            ->first();

        return view('legacy.lease_histories.edit_lease_details', [
            'title_for_layout' => 'Update Lease Detail',
            'triplog' => $data ? $data->toArray() : [],
            'requestData' => ['Lease' => $data ? $data->toArray() : []]
        ]);
    }
}
