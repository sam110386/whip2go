<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * CakePHP `ArgyleController` — Argyle Link / webhooks (external API calls stubbed in Laravel).
 */
class ArgyleController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request, ?string $userid = null): View|RedirectResponse
    {
        $decodedUserId = $this->decodeUserIdFromSegment($userid);
        if ($decodedUserId === null) {
            return redirect('/argyle/error');
        }

        $userCount = DB::table('users')->where('id', $decodedUserId)->count();
        if ($userCount === 0) {
            return redirect('/argyle/error');
        }

        $argyleRow = DB::table('argyle_users')->where('user_id', $decodedUserId)->first();
        $token = $argyleRow->auth_token ?? '';

        $uberlyftPartners = ['lyft', 'grubhub', 'doordash', 'shipt', 'postmates', 'uber'];
        $incomedataPartners = [];

        return view('argyle.index', compact('userid', 'token', 'uberlyftPartners', 'incomedataPartners'));
    }

    public function linkincome(?string $userid = null): RedirectResponse
    {
        return redirect('/argyle/index/' . ($userid ?? ''));
    }

    public function uber(?string $userid = null): View
    {
        return view('argyle.uber', compact('userid'));
    }

    public function lyft(?string $userid = null): View
    {
        return view('argyle.lyft', compact('userid'));
    }

    public function error(): View
    {
        return view('argyle.error');
    }

    public function success(): View
    {
        return view('argyle.success');
    }

    public function saveUser(Request $request): JsonResponse
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];
        if (!$request->boolean('isAjax')) {
            $return['msg'] = 'sorry wrong page';

            return response()->json($return);
        }

        $user = 0;
        if ($request->filled('user')) {
            $decodedUser = base64_decode((string) $request->input('user'), true);
            if ($decodedUser !== false && ctype_digit((string) $decodedUser)) {
                $user = (int) $decodedUser;
            }
        }
        $accountId = trim((string) $request->input('accountId', ''));
        $account = trim((string) $request->input('account', ''));
        $income = $request->boolean('income');

        if ($user <= 0) {
            return response()->json($return);
        }

        $userObj = DB::table('users')->where('id', $user)->first(['id', 'uberlyft_verified', 'uber_lyft']);
        if ($userObj === null) {
            return response()->json($return);
        }

        $exists = DB::table('argyle_users')
            ->where('user_id', $user)
            ->first(['id', 'income']);

        if ($exists === null) {
            $return['msg'] = 'sorry wrong page';

            return response()->json($return);
        }

        try {
            DB::table('argyle_user_records')->updateOrInsert(
                ['user_id' => $user, 'account' => $account],
                [
                    'argyle_user_id' => (int) $exists->id,
                    'account_id' => $accountId,
                ]
            );
        } catch (\Throwable) {
            // parity with Cake: swallow save errors
        }

        if ($income) {
            DB::table('argyle_users')->where('id', $exists->id)->update(['income' => 1]);
        }

        DB::table('users')->where('id', $user)->update([
            'uberlyft_verified' => 1,
            'uber_lyft' => 1,
        ]);

        return response()->json(['status' => true, 'msg' => 'You are successfully connected now']);
    }

    public function saveToken(Request $request): JsonResponse
    {
        $return = ['status' => false, 'msg' => 'Sorry, something went wrong, please try again'];
        if (!$request->boolean('isAjax')) {
            $return['msg'] = 'sorry wrong page';

            return response()->json($return);
        }

        $user = 0;
        if ($request->filled('user')) {
            $decodedUser = base64_decode((string) $request->input('user'), true);
            if ($decodedUser !== false && ctype_digit((string) $decodedUser)) {
                $user = (int) $decodedUser;
            }
        }
        $token = $request->input('token');
        $userId = $request->input('userId');

        if ($user <= 0) {
            return response()->json($return);
        }

        $userObj = DB::table('users')->where('id', $user)->first(['id']);
        if ($userObj === null) {
            return response()->json($return);
        }

        $exists = DB::table('argyle_users')->where('user_id', $user)->first(['id']);
        $payload = [
            'user_id' => $user,
            'auth_token' => $token,
            'argyle_user_id' => $userId,
        ];
        if ($exists !== null) {
            DB::table('argyle_users')->where('id', $exists->id)->update($payload);
        } else {
            $payload['created'] = now();
            DB::table('argyle_users')->insert($payload);
        }

        return response()->json(['status' => true, 'msg' => 'You are succcessfully connected now']);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        return response()->json([
            'status' => false,
            'msg' => 'Argyle refreshToken not yet ported (API stub).',
        ]);
    }

    public function webhook(Request $request): Response
    {
        $raw = $request->getContent();
        if ($raw === '' || $raw === '0') {
            exit('Sorry, wrong effort!!');
        }

        $logPath = storage_path('logs/argyle_' . date('Y-m-d') . '.log');
        File::append($logPath, "\n" . date('Y-m-d H:i:s') . '=' . $raw);

        $decoded = json_decode($raw, true);
        if (is_array($decoded) && ($decoded['event'] ?? null) === 'activities.updated') {
            // Stub: ArgyleActivity::processWebhookData not ported
        }

        return response('finished', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function decodeUserIdFromSegment(?string $userid): ?int
    {
        if ($userid === null || $userid === '') {
            return null;
        }
        $decoded = base64_decode($userid, true);
        if ($decoded !== false && ctype_digit($decoded)) {
            return (int) $decoded;
        }

        return null;
    }
}
