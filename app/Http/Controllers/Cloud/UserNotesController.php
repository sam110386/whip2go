<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserNotesController extends LegacyAppController
{
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        if (empty($userid)) {
            return redirect('/cloud/linked_users/index');
        }

        $dateFrom = '';
        $dateTo = '';
        $conditions = [];
        $bindings = [];

        $search = $request->input('Search', $request->query());

        if (!empty($search['date_from'])) {
            $dateFrom = $search['date_from'];
        }
        if (!empty($search['date_to'])) {
            $dateTo = $search['date_to'];
        }
        if (!empty($search['user_id'])) {
            $userid = $search['user_id'];
        }

        if (!empty($dateFrom) && empty($dateTo)) {
            $dateTo = date('Y-m-d');
        }
        if (!empty($dateFrom)) {
            $dateFrom = Carbon::parse($dateFrom)->format('Y-m-d');
            $conditions[] = 'user_notes.created >= ?';
            $bindings[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $dateTo = Carbon::parse($dateTo)->format('Y-m-d');
            $conditions[] = 'user_notes.created <= ?';
            $bindings[] = $dateTo;
        }

        $sessLimitName = 'cloud_user_notes_limit';
        $limit = $request->input('Record.limit') ?: session($sessLimitName, 25);
        if ($request->input('Record.limit')) {
            session([$sessLimitName => $limit]);
        }

        $query = DB::table('user_notes')
            ->leftJoin('users as Admin', 'Admin.id', '=', 'user_notes.admin_id')
            ->select('user_notes.*', 'Admin.first_name as admin_first_name', 'Admin.last_name as admin_last_name')
            ->where('user_notes.user_id', $userid);

        foreach ($conditions as $i => $cond) {
            $query->whereRaw($cond, [$bindings[$i]]);
        }

        $notelists = $query->orderByDesc('user_notes.id')->paginate($limit);

        if ($request->ajax()) {
            return view('cloud.user_note._cloud_index', compact('notelists', 'userid'));
        }

        $user = DB::table('users')->where('id', $userid)->select('first_name', 'last_name')->first();

        return view('cloud.user_note.index', compact('notelists', 'dateFrom', 'dateTo', 'userid', 'user'));
    }

    public function add(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $userid = trim($request->input('userid'));

        return view('cloud.user_note.add', compact('userid'));
    }

    public function save(Request $request)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $adminid = session('SESSION_ADMIN.id');

        DB::table('user_notes')->insert([
            'user_id' => $request->input('UserNote.user_id'),
            'note' => $request->input('UserNote.note'),
            'admin_id' => $adminid,
            'created' => now(),
            'modified' => now(),
        ]);

        return response()->json(['status' => true]);
    }
}
