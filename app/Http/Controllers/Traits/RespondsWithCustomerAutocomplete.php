<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User as LegacyUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RespondsWithCustomerAutocomplete
{
    /**
     * Cake BookingsController::admin_customerautocomplete / customerautocomplete JSON.
     *
     * @param  'admin'|'frontend'  $mode
     */
    protected function respondCustomerAutocomplete(Request $request, string $mode): JsonResponse
    {
        $term = (string) $request->query('term', '');
        $like = '%' . addcslashes($term, '%_\\') . '%';

        $q = LegacyUser::query()
            ->where('status', 1)
            ->select(['id', 'first_name', 'contact_number']);

        if ($mode === 'frontend') {
            $q->where('is_dealer', 0);
        } else {
            if ($request->has('is_dealer') && !$request->filled('dealer_id')) {
                $q->where('is_dealer', 1);
            } elseif ($request->filled('dealer_id')) {
                $q->where('dealer_id', (int) $request->query('dealer_id'));
            } elseif ($request->filled('id')) {
                $q->where('id', (int) $request->query('id'));
            }
        }

        $q->where(function ($sub) use ($like) {
            $sub->where('contact_number', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('last_name', 'like', $like);
        });

        $rows = $q->orderBy('first_name')->limit(10)->get();

        $out = [];
        foreach ($rows as $u) {
            $out[] = [
                'id' => $u->id,
                'tag' => $u->first_name . ' - ' . $u->contact_number,
            ];
        }

        return response()->json($out);
    }
}
