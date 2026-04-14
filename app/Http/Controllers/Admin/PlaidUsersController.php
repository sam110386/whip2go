<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CakePHP `PlaidUsersController` admin actions (`admin_*` prefix removed). Plaid API calls stubbed.
 */
class PlaidUsersController extends LegacyAppController
{
    public function index(Request $request, ?string $userid = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->indexForUser($request, $userid);
    }

    public function balance(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->plaidApiStubResponse();
    }

    public function transactions(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->plaidApiStubResponse();
    }

    public function income(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->plaidApiStubResponse();
    }

    public function combinedincome(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->plaidApiStubResponse();
    }

    public function payrollincome(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->plaidApiStubResponse();
    }

    public function pullPlaidBank(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->pullPlaidBankView($request);
    }

    public function pullPlaidPaystub(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->pullPlaidPaystubView($request);
    }

    public function pullBankDetail(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->plaidApiStubResponse();
    }

    public function downloadpaystub(Request $request, ?string $verificationid = null): Response|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        return $this->downloadpaystubStub();
    }

    /**
     * Shared listing logic for admin + cloud (session checked by caller).
     */
    protected function indexForUser(Request $request, ?string $userid): View|RedirectResponse
    {
        $decoded = $userid !== null && $userid !== '' ? base64_decode($userid, true) : false;
        $userId = ($decoded !== false && ctype_digit((string) $decoded)) ? (int) $decoded : null;
        if ($userId === null || $userId === 0) {
            return redirect('/admin/users');
        }

        $plaids = DB::table('plaid_users')
            ->where('user_id', $userId)
            ->orderByDesc('id')
            ->get();

        return view('admin.plaid_users.index', [
            'title_for_layout' => 'Connected Bank Accounts',
            'plaids' => $plaids,
            'userid' => $userId,
        ]);
    }

    protected function plaidApiStubResponse(): Response
    {
        return response('Plaid API not yet ported', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    protected function pullPlaidBankView(Request $request): View
    {
        $userid = $this->decodePostedUserId($request);
        $plaids = $userid > 0
            ? DB::table('plaid_users')->where('user_id', $userid)->where('paystub', 0)->orderByDesc('id')->get()
            : collect();

        return view('admin.plaid_users.pull_plaid_bank', [
            'plaids' => $plaids,
            'userid' => $userid,
            'modal' => 'statementModal',
        ]);
    }

    protected function pullPlaidPaystubView(Request $request): View
    {
        $userid = $this->decodePostedUserId($request);
        $plaids = $userid > 0
            ? DB::table('plaid_users')->where('user_id', $userid)->where('paystub', 1)->orderByDesc('id')->get()
            : collect();

        return view('admin.plaid_users.pull_plaid_paystub', [
            'plaids' => $plaids,
            'userid' => $userid,
            'modal' => 'statementModal',
        ]);
    }

    protected function downloadpaystubStub(): Response
    {
        return response('Plaid API not yet ported', 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    private function decodePostedUserId(Request $request): int
    {
        if (!$request->filled('userid')) {
            return 0;
        }
        $decoded = base64_decode((string) $request->input('userid'), true);

        return ($decoded !== false && ctype_digit((string) $decoded)) ? (int) $decoded : 0;
    }
}
