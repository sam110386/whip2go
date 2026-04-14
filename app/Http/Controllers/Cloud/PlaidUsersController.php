<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\PlaidUsersController as AdminPlaidUsersController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CakePHP `PlaidUsersController` cloud actions — delegates to admin implementation after cloud session.
 */
class PlaidUsersController extends AdminPlaidUsersController
{
    public function index(Request $request, ?string $userid = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::indexForUser($request, $userid);
    }

    public function balance(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::plaidApiStubResponse();
    }

    public function transactions(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::plaidApiStubResponse();
    }

    public function income(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::plaidApiStubResponse();
    }

    public function combinedincome(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::plaidApiStubResponse();
    }

    public function payrollincome(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::plaidApiStubResponse();
    }

    public function pullPlaidBank(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::pullPlaidBankView($request);
    }

    public function pullPlaidPaystub(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::pullPlaidPaystubView($request);
    }

    public function pullBankDetail(Request $request): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::plaidApiStubResponse();
    }

    public function downloadpaystub(Request $request, ?string $verificationid = null): Response|RedirectResponse
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        return parent::downloadpaystubStub();
    }
}
