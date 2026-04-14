<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\LinkedUsersTrait;
use App\Models\Legacy\CsOrder;
use App\Models\Legacy\User;
use Illuminate\Http\Request;

class LinkedUsersController extends LegacyAppController
{
    use LinkedUsersTrait;

    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = session('userParentId');
        if (empty($userId) || $userId == 0) {
            $userId = session('userid');
        }

        $searchData = $request->input('Search', []);
        $namedData = $request->query();

        $value = $namedData['keyword'] ?? $searchData['keyword'] ?? '';
        $searchin = $namedData['searchin'] ?? $searchData['searchin'] ?? '';

        $query = CsOrder::query()
            ->from('cs_orders as CsOrder')
            ->leftJoin('users as User', 'User.id', '=', 'CsOrder.renter_id')
            ->leftJoin('cs_user_convertibilities as CsUserConvertibility', 'CsUserConvertibility.user_id', '=', 'CsOrder.renter_id')
            ->leftJoin('user_reports as UserReport', 'UserReport.user_id', '=', 'CsOrder.renter_id')
            ->select('CsOrder.id', 'User.*', 'CsUserConvertibility.score', 'CsUserConvertibility.target_score', 'CsUserConvertibility.reference_id', 'UserReport.*')
            ->where('CsOrder.user_id', $userId)
            ->groupBy('CsOrder.renter_id');

        if ($value !== "" && !empty($searchin)) {
            $value1 = strip_tags($value); // Sanitize
            // Simple generic LIKE clause matching legacy pattern
            $query->where("User.{$searchin}", 'LIKE', "%{$value1}%");
        }

        $sessionLimitKey = 'LinkedUsers_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit = $request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $users = $query->orderBy('CsOrder.id', 'DESC')->paginate($limit)->withQueryString();

        return view('legacy.linked_users.index', [
            'title_for_layout' => 'Manage Linked Users',
            'keyword' => $value,
            'searchin' => $searchin,
            'users' => $users,
        ]);
    }

    public function view($id)
    {
        $decodedId = base64_decode($id);

        $user = User::find($decodedId);

        return view('legacy.linked_users.view', [
            'listTitle' => 'View User',
            'user' => $user ? ['User' => $user->toArray()] : []
        ]);
    }

    public function updatetargetscore(Request $request)
    {
        return $this->processUpdateTargetScore($request);
    }
}
