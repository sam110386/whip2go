<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\User;
use App\Models\Legacy\UserNote;
use Illuminate\Http\Request;

class UserNotesController extends LegacyAppController
{
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'User Notes';
        $sessLimitName = 'user_notes_limit';
        $date_from = $request->input('Search.date_from', $request->input('date_from', ''));
        $date_to = $request->input('Search.date_to', $request->input('date_to', ''));

        if ($request->has('user_id')) {
            $userid = $request->input('user_id');
        }

        if (!$userid) {
            return redirect('/admin/users/index');
        }

        if (!empty($date_from) && empty($date_to)) {
            $date_to = date('Y-m-d');
        }

        $query = UserNote::with('admin:id,first_name,last_name')
            ->where('user_id', $userid);

        if (!empty($date_from)) {
            $query->where('created_at', '>=', $date_from);
        }

        if (!empty($date_to)) {
            $query->where('created_at', '<=', $date_to);
        }

        if ($request->has('Record.limit')) {
            $limit = $request->input('Record.limit');
            session([$sessLimitName => $limit]);
        } elseif (session()->has($sessLimitName)) {
            $limit = session($sessLimitName);
        } else {
            $limit = $this->recordsPerPage;
        }

        $notelists = $query->orderBy('id', 'DESC')->paginate($limit);

        if ($request->ajax()) {
            return view('admin.user_note.elements.index', compact('notelists', 'date_from', 'date_to', 'userid', 'limit'));
        }

        $user = User::select('first_name', 'last_name')->find($userid);

        return view('admin.user_note.index', compact('notelists', 'date_from', 'date_to', 'userid', 'user', 'title', 'limit'));
    }
    public function add(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userid = trim($request->input('userid'));

        return view('admin.user_note.add', compact('userid'));
    }
    public function save(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $adminid = session('SESSION_ADMIN.id');
        $user_id = $request->input('UserNote.user_id', $request->input('user_id', ''));
        $note = $request->input('UserNote.note', $request->input('note', ''));

        UserNote::create([
            'user_id' => $user_id,
            'admin_id' => $adminid,
            'note' => $note,
            'created' => now(),
        ]);

        return response()->json([
            'status' => true
        ]);
    }
}
