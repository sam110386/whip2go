<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Shared vehicle list filters (Cake VehiclesController::admin_index / LinkedVehiclesController::cloud_index).
 */
final class VehicleListing
{
    /**
     * Cake Common::getVehicleStatus() with key 10 overridden to Waitlist (see VehiclesController::admin_index).
     *
     * @return array<string, string>
     */
    public static function adminStatusLabels(): array
    {
        return [
            '0' => 'Unlisted',
            '1' => 'Active',
            '4' => 'Inactive',
            '2' => 'In Body Shop',
            '3' => 'In Maintenance',
            '5' => 'Maintenance Issues',
            '6' => 'Booked',
            '8' => 'Starter Disabled',
            '9' => 'Starter Enabled',
            '10' => 'Waitlist',
            '11' => 'Deleted',
            '12' => 'Undo Deleted',
        ];
    }

    /** @return array<string, string> */
    public static function linkedStatusLabels(): array
    {
        return ['Active' => 'Active', 'Deactive' => 'Inactive'];
    }

    /**
     * @param  Builder<\App\Models\Legacy\Vehicle>  $q
     */
    public static function applyAdminFilters(Builder $q, Request $request): void
    {
        $keyword = trim((string)$request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = trim((string)$request->input('Search.searchin', $request->query('searchin', '')));
        $show = (string)$request->input('Search.show', $request->query('showtype', ''));
        $userId = trim((string)$request->input('Search.user_id', $request->query('user_id', '')));
        $type = trim((string)$request->input('Search.type', $request->query('type', '')));
        $visibility = trim((string)$request->input('Search.visibility', $request->query('visibility', '')));

        $showArr = self::adminStatusLabels();

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            if (in_array($searchin, ['vehicle_name', 'vin_no', 'plate_number'], true)) {
                $q->where($searchin, 'like', $like);
            } else {
                $q->where(function ($qq) use ($like) {
                    $qq->where('vehicle_name', 'like', $like)
                        ->orWhere('vin_no', 'like', $like);
                });
            }
        }

        if ($show !== '') {
            if ($show === '10') {
                $q->where('waitlist', 1);
            } elseif ($show === '6') {
                $q->where('booked', 1);
            } elseif ($show === '7') {
                $q->where('booked', 0);
            } elseif ($show === '8') {
                $q->where('passtime_status', 0);
            } elseif ($show === '9') {
                $q->where('passtime_status', 1);
            } elseif (array_key_exists($show, $showArr)) {
                $q->where('status', (int)$show);
            }
        }

        if ($userId !== '' && ctype_digit($userId)) {
            $q->where('user_id', (int)$userId);
        }
        if ($type !== '') {
            $q->where('is_featured', $type === 'featured' ? 1 : 0);
        }
        if ($visibility !== '') {
            $q->where('visibility', (int)$visibility);
        }
    }

    /**
     * @param  Builder<\App\Models\Legacy\Vehicle>  $q
     * @param  list<int>  $dealerUserIds
     */
    public static function applyLinkedFilters(Builder $q, Request $request, array $dealerUserIds): void
    {
        if ($dealerUserIds === []) {
            $q->whereRaw('1 = 0');

            return;
        }
        $q->whereIn('user_id', $dealerUserIds);

        $keyword = trim((string)$request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = trim((string)$request->input('Search.searchin', $request->query('searchin', '')));
        $show = (string)$request->input('Search.show', $request->query('show', ''));
        $userId = trim((string)$request->input('Search.user_id', $request->query('user_id', '')));

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            if ($searchin === '' || strcasecmp($searchin, 'All') === 0) {
                $q->where(function ($qq) use ($like) {
                    $qq->where('vehicle_name', 'like', $like)
                        ->orWhere('vehicle_unique_id', 'like', $like);
                });
            } elseif ($searchin === 'cab_name') {
                $q->where('cab_type', 'like', $like);
            } elseif ($searchin === 'vehicle_number') {
                $q->where('vehicle_unique_id', 'like', $like);
            } elseif (in_array($searchin, ['vehicle_name', 'plate_number'], true)) {
                $q->where($searchin, 'like', $like);
            }
        }

        if ($show === 'Active') {
            $q->where('status', 1);
        } elseif ($show === 'Deactive') {
            $q->where('status', 0);
        }

        if ($userId !== '' && ctype_digit($userId)) {
            $q->where('user_id', (int)$userId);
        }
    }

    public static function humanizeAdminRow(object $v): string
    {
        $waitlist = (int)($v->waitlist ?? 0);
        if ($waitlist === 1) {
            return 'Waitlist';
        }
        $booked = (int)($v->booked ?? 0);
        if ($booked === 1) {
            return 'Booked';
        }
        $st = (int)($v->status ?? 0);

        return self::adminStatusLabels()[(string)$st] ?? ('Status ' . $st);
    }
}
