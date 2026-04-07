<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\TdkDealer;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TdkDealersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index (List of TDK Dealers) ───────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $this->set('title_for_layout', 'TDK Dealers');
        
        $keyword  = $request->input('Search.keyword', $request->query('keyword', ''));
        $showType = $request->input('Search.show', $request->query('showtype', ''));

        $query = TdkDealer::from('tdk_dealers as TdkDealer')
            ->leftJoin('users as User', 'User.id', '=', 'TdkDealer.user_id')
            ->select('TdkDealer.*', 'User.first_name', 'User.last_name', 'User.email', 'User.username')
            ->orderBy('TdkDealer.id', 'DESC');

        if (!empty($keyword)) {
            $v = strip_tags($keyword);
            $query->where(fn($q) => $q->where('User.first_name', 'LIKE', "%$v%")
                ->orWhere('User.last_name', 'LIKE', "%$v%")
                ->orWhere('User.email', 'LIKE', "%$v%")
                ->orWhere('User.username', 'LIKE', "%$v%"));
        }

        if (!empty($showType)) {
            $matchShow = ($showType == 'Active') ? 1 : 0;
            $query->where('TdkDealer.status', $matchShow);
        }

        $sessionLimitKey  = 'Admin_TdkDealers_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $users = $query->paginate($limit)->withQueryString();

        return view('admin.tdk_dealers.index', [
            'users'   => $users,
            'keyword' => $keyword,
            'show'    => $showType,
            'heading' => 'TDK Dealers',
        ]);
    }

    // ─── admin_add / admin_edit ──────────────────────────────────────────────
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $id ? base64_decode($id) : null;
        $listTitle = $id ? 'Update TDK Dealer' : 'Add TDK Dealer';

        if ($request->isMethod('post')) {
            $data = $request->input('TdkDealer', []);
            
            if ($id) {
                TdkDealer::where('id', $id)->update($data);
                $msg = 'Dealer record updated successfully.';
            } else {
                TdkDealer::create($data);
                $msg = 'Dealer record created successfully.';
            }

            return redirect('/admin/tdk_dealers/index')->with('success', $msg);
        }

        $record = null;
        if ($id) {
            $record = TdkDealer::from('tdk_dealers as TdkDealer')
                ->leftJoin('users as User', 'User.id', '=', 'TdkDealer.user_id')
                ->where('TdkDealer.id', $id)
                ->select('TdkDealer.*', 'User.first_name', 'User.last_name')
                ->first();
        }

        return view('admin.tdk_dealers.add', compact('listTitle', 'id', 'record'));
    }

    // ─── admin_delete ────────────────────────────────────────────────────────
    public function admin_delete(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = base64_decode($id);
        if (!empty($id)) {
            TdkDealer::where('id', $id)->delete();
        }

        return redirect()->back()->with('success', 'Dealer record deleted successfully.');
    }
}
