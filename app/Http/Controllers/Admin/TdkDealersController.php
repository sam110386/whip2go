<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Legacy\TdkDealer;
use App\Http\Controllers\Legacy\LegacyAppController;

class TdkDealersController extends LegacyAppController
{

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $sessionLimitName = "tdk_dealers_limit";
        $title = 'TDK Dealers';
        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        $show = $request->input('Search.show', $request->query('showtype', ''));

        if ($request->filled('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessionLimitName => $limit]);
        } else {
            $limit = session($sessionLimitName, $this->recordsPerPage);
        }


        $query = TdkDealer::with('user:id,first_name,last_name,username,email');

        if (!empty($keyword)) {
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('username', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%");
            });
        }

        if ($show === 'Active') {
            $query->where('status', 1);
        } elseif ($show === 'Deactive') {
            $query->where('status', 0);
        }

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $users = $query->orderBy($sort, $direction)->paginate($limit);

        return view('admin.tdk_dealers.index', compact('title', 'users', 'keyword', 'show', 'limit'));
    }
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $title = $id ? 'Update TDK Dealer' : 'Add TDK Dealer';

        if ($request->isMethod('post') || $request->isMethod('put')) {
            $uniqueRule = 'unique:tdk_dealers,user_id';

            if ($id) {
                $uniqueRule .= ",{$id}"; // Ignore current ID during update
            }

            $validatedData = $request->validate([
                'TdkDealer.user_id' => ['required', $uniqueRule],
                'TdkDealer.metro_city' => ['required'],
                'TdkDealer.metro_state' => ['required'],
            ], [
                'TdkDealer.user_id.required' => 'Please select dealer',
                'TdkDealer.user_id.unique' => 'Dealer already added.',
                'TdkDealer.metro_city.required' => 'Please enter metro city',
                'TdkDealer.metro_state.required' => 'Please enter metro state',
            ]);

            $saveValues = $request->input('TdkDealer');
            TdkDealer::updateOrCreate(
                ['id' => $id],
                $saveValues
            );

            $message = $id ? "Dealer record updated successfully" : "Dealer record created successfully";

            return redirect('admin/tdk_dealers/index')->with('success', $message);
        }

        $dealer = null;

        if ($id) {
            $dealer = TdkDealer::with('user:id,first_name,last_name')->findOrFail($id);
        }

        return view('admin.tdk_dealers.add', compact('title', 'dealer'));
    }
    public function delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decoded = $this->decodeId($id);

        if ($decoded) {
            $dealer = TdkDealer::findOrFail($decoded);
            $dealer->delete();

            return redirect()->back()->with('success', 'Record deleted successfully.');
        }

        return redirect()->back()->with('error', 'Sorry, selected record not found');
    }
}
