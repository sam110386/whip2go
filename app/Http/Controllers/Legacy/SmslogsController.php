<?php

namespace App\Http\Controllers\Legacy;

use App\Services\LegacyDealerOutboundSms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SmslogsController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        if (!Schema::hasTable('cs_twilio_logs')) {
            return view('smslogs.index', [
                'title_for_layout' => 'Messages',
                'keyword' => '',
                'logsPaginator' => new LengthAwarePaginator(collect(), 0, 10, 1, ['path' => url('/smslogs/index')]),
            ]);
        }

        $userId = $this->effectiveUserId();
        $keyword = trim((string) $request->input('searchKey', $request->input('data.searchKey', '')));

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;

        [$total, $rows] = $this->groupedTwilioPage($userId, $keyword, $page, $perPage);

        $items = $this->hydrateTwilioRows($rows);

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => url('/smslogs/index'), 'pageName' => 'page']
        );
        $paginator->appends(['searchKey' => $keyword]);

        if ($request->ajax()) {
            return view('smslogs.partials.userlist', [
                'logsPaginator' => $paginator,
                'keyword' => $keyword,
            ]);
        }

        return view('smslogs.index', [
            'title_for_layout' => 'Messages',
            'keyword' => $keyword,
            'logsPaginator' => $paginator,
        ]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function loadchat(Request $request)
    {
        if ($redirect = $this->ensureUserSession()) {
            return $redirect;
        }

        $userId = $this->effectiveUserId();
        $phone = (string) $request->input('phone', '');
        $renterId = (int) $request->input('userid', 0);

        $logs = collect();
        if (Schema::hasTable('cs_twilio_logs')) {
            $ids = DB::table('cs_twilio_logs')
                ->where('user_id', $userId)
                ->where('renter_phone', $phone)
                ->orderByDesc('id')
                ->limit(30)
                ->pluck('id')
                ->all();

            if ($ids !== []) {
                $logs = DB::table('cs_twilio_logs')
                    ->whereIn('id', $ids)
                    ->orderBy('id')
                    ->get();
            }
        }

        $renter = null;
        if ($renterId > 0 && Schema::hasTable('users')) {
            $renter = DB::table('users')
                ->where('id', $renterId)
                ->select(['id', 'first_name', 'last_name', 'photo'])
                ->first();
        }

        return view('smslogs.partials.chatwindow', [
            'CsTwilioLogs' => $logs,
            'renter' => $renter,
            'phone' => $phone,
        ]);
    }

    public function sendmessage(Request $request, LegacyDealerOutboundSms $sender): JsonResponse
    {
        if ($redirect = $this->ensureUserSession()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userId = $this->effectiveUserId();
        $msg = (string) $request->input('message', $request->input('data.message', ''));
        $phone = (string) $request->input('phone', $request->input('data.phone', ''));

        $result = $sender->sendFromDealerSession($userId, $phone, $msg);

        return response()->json($result);
    }

    private function effectiveUserId(): int
    {
        $parent = (int) session()->get('userParentId', 0);
        if ($parent !== 0) {
            return $parent;
        }

        return (int) session()->get('userid', 0);
    }

    /**
     * @return array{0: int, 1: array<int, object>}
     */
    private function groupedTwilioPage(int $userId, string $keyword, int $page, int $perPage): array
    {
        $conditions = ['user_id = ?'];
        $bindings = [$userId];
        if ($keyword !== '') {
            $conditions[] = 'renter_phone LIKE ?';
            $bindings[] = '%' . addcslashes($keyword, '%_\\') . '%';
        }
        $where = implode(' AND ', $conditions);
        $inner = "SELECT MAX(id) AS last_id FROM cs_twilio_logs WHERE {$where} GROUP BY renter_phone";

        $total = (int) (DB::selectOne("SELECT COUNT(*) AS c FROM ({$inner}) g", $bindings)->c ?? 0);

        $limit = max(1, $perPage);
        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT last_id FROM ({$inner}) AS t ORDER BY last_id DESC LIMIT {$limit} OFFSET {$offset}";
        $rows = DB::select($sql, $bindings);

        return [$total, $rows];
    }

    /**
     * @param array<int, object> $rows
     */
    private function hydrateTwilioRows(array $rows): Collection
    {
        $out = collect();
        foreach ($rows as $r) {
            $lastId = (int) ($r->last_id ?? 0);
            if ($lastId <= 0) {
                continue;
            }
            $q = DB::table('cs_twilio_logs as t')->where('t.id', $lastId);
            if (Schema::hasTable('users')) {
                $q->leftJoin('users as u', 'u.username', '=', 't.renter_phone')
                    ->select([
                        't.id',
                        't.cs_twilio_order_id',
                        't.user_id',
                        't.renter_phone',
                        't.msg',
                        't.type',
                        't.created',
                        't.modified',
                        'u.id as renter_user_id',
                        'u.first_name',
                        'u.last_name',
                        'u.photo',
                    ]);
            } else {
                $q->select(['t.*']);
            }
            $row = $q->first();
            if ($row !== null) {
                $latestMsg = DB::table('cs_twilio_logs')
                    ->where('renter_phone', $row->renter_phone)
                    ->where('user_id', $row->user_id)
                    ->orderByDesc('id')
                    ->value('msg');
                if ($latestMsg !== null) {
                    $row->msg = $latestMsg;
                }
                $out->push($row);
            }
        }

        return $out;
    }
}
