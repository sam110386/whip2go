<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\SavvyDealer;
use Illuminate\Http\Request;

class SavvyDealersController extends LegacyAppController
{
    public function index(Request $request)
    {
        $this->ensureAdminSession();

        $sessLimitName = 'savvy_dealers_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitName, $this->records_per_page ?? 20);

        if ($request->input('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $dealers = SavvyDealer::query()
            ->leftJoin('users', 'users.id', '=', 'savvy_dealers.user_id')
            ->select('savvy_dealers.*', 'users.first_name', 'users.last_name')
            ->orderBy('savvy_dealers.id', 'DESC')
            ->paginate($limit);

        return view('admin.savvy.index', [
            'title_for_layout' => 'Savvy Dealers',
            'dealers' => $dealers,
            'limit' => $limit,
        ]);
    }

    public function add(Request $request, $id = null)
    {
        $this->ensureAdminSession();

        $decodedId = $id ? base64_decode($id) : null;
        $listTitle = !empty($decodedId) ? 'Update' : 'Add';
        $dealer = null;

        if ($request->isMethod('post')) {
            $data = $request->input('SavvyDealer', []);
            $data['filters'] = json_encode($data['filters'] ?? []);

            if (empty($data['user_id'])) {
                return back()->withErrors(['user_id' => 'Please enter user id'])->withInput();
            }

            if (empty($data['id'])) {
                $exists = SavvyDealer::where('user_id', $data['user_id'])->exists();
                if ($exists) {
                    return back()->withErrors(['user_id' => 'User records already exists'])->withInput();
                }
            }

            if (!empty($data['id'])) {
                SavvyDealer::where('id', $data['id'])->update([
                    'user_id' => $data['user_id'],
                    'search_url' => $data['search_url'] ?? '',
                    'filters' => $data['filters'],
                ]);
            } else {
                SavvyDealer::create([
                    'user_id' => $data['user_id'],
                    'search_url' => $data['search_url'] ?? '',
                    'filters' => $data['filters'],
                ]);
            }

            return redirect('admin/savvy_dealers/index')
                ->with('success', 'Dealer data saved successfully.');
        }

        if (!empty($decodedId)) {
            $dealer = SavvyDealer::query()
                ->leftJoin('users', 'users.id', '=', 'savvy_dealers.user_id')
                ->where('savvy_dealers.id', $decodedId)
                ->select('savvy_dealers.*', 'users.first_name', 'users.last_name')
                ->first();

            if ($dealer) {
                $dealer->filters_decoded = json_decode($dealer->filters, true) ?: [];
            }
        }

        return view('admin.savvy.add', [
            'listTitle' => $listTitle,
            'dealer' => $dealer,
        ]);
    }

    public function status($id = null, $status = null)
    {
        $this->ensureAdminSession();

        $decodedId = $id ? base64_decode($id) : null;
        if (!empty($decodedId)) {
            SavvyDealer::where('id', $decodedId)
                ->update(['status' => ($status == 1) ? 1 : 0]);
        }

        return redirect()->back()->with('success', 'Dealer status has been changed.');
    }

    public function delete($id = null)
    {
        $this->ensureAdminSession();

        $decodedId = $id ? base64_decode($id) : null;
        if (!empty($decodedId)) {
            SavvyDealer::where('id', $decodedId)->delete();
        }

        return redirect()->back()->with('success', 'Dealer is deleted successfully');
    }
}
