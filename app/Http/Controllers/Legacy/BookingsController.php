<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Traits\RespondsWithCustomerAutocomplete;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingsController extends LegacyAppController
{
    use RespondsWithCustomerAutocomplete;

    protected bool $shouldLoadLegacyModules = false;

    public function customerautocomplete(Request $request): JsonResponse
    {
        return $this->respondCustomerAutocomplete($request, 'frontend');
    }
}
