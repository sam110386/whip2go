<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\SavvyDealer;
use App\Http\Controllers\Legacy\LegacyAppController;

class SavvyDealersController extends LegacyAppController
{
    public function index(Request $request)
    {
        $this->ensureAdminSession();

        $title = 'Savvy Dealers';
        $sessLimitName = 'savvy_dealers_limit';

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } else {
            $limit = session($sessLimitName, $this->recordsPerPage);
        }

        $dealers = SavvyDealer::with('user:id,first_name,last_name')
            ->orderBy('id', 'DESC')
            ->paginate($limit);

        return view('admin.savvy.index', compact('title', 'dealers', 'limit'));
    }
    public function add(Request $request, $id = null)
    {
        $this->ensureAdminSession();

        $id = $this->decodeId($id);
        $listTitle = $id ? 'Update' : 'Add';
        $dealer = null;

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $uniqueRule = 'unique:savvy_dealers,user_id';

            if ($id) {
                $uniqueRule .= ",{$id}"; // Ignore current ID during update
            }

            $validatedData = $request->validate([
                'SavvyDealer.user_id' => ['required', $uniqueRule],
            ], [
                'SavvyDealer.user_id.required' => 'Please select dealer',
                'SavvyDealer.user_id.unique' => 'Dealer already added.',
            ]);

            $dealerData = $request->input('SavvyDealer');
            $dealerData['filters'] = json_encode($dealerData['filters'] ?? []);

            $dealer = SavvyDealer::updateOrCreate(
                ['id' => $id],
                $dealerData
            );

            return redirect('admin/savvy_dealers/index')->with('success', 'Dealer data saved successfully.');
        }

        if ($id) {
            $dealer = SavvyDealer::with('user:id,first_name,last_name')
                ->where('id', $id)
                ->first();

            if ($dealer) {
                $dealer->filters = json_decode($dealer->filters, true) ?: [];
            }
        }

        return view('admin.savvy.add', compact('listTitle', 'dealer'));
    }
    public function status($id = null, $status = null)
    {
        $this->ensureAdminSession();

        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            SavvyDealer::where('id', $decodedId)
                ->update(
                    ['status' => ($status == 1) ? 1 : 0]
                );
        }

        return redirect()->back()->with('success', 'Dealer status has been changed.');
    }
    public function delete($id = null)
    {
        $this->ensureAdminSession();

        $decodedId = $this->decodeId($id);

        if (!empty($decodedId)) {
            SavvyDealer::where('id', $decodedId)->delete();
        }

        return redirect()->back()->with('success', 'Dealer is deleted successfully');
    }
}
