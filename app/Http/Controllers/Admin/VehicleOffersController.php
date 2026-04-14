<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\VehicleOffer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleOffersController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $limit = $this->resolveLimit($request);
        $offers = $this->offerQuery()
            ->orderByDesc('vo.id')
            ->paginate($limit)
            ->withQueryString();

        return view('admin.vehicle_offers.index', [
            'offers' => $offers,
            'limit' => $limit,
            'basePath' => $this->offerBasePath(),
        ]);
    }

    public function add(Request $request, $offer_id = null)
    {
        $id = $this->decodeId((string)$offer_id);
        $offer = $id ? DB::table('vehicle_offers')->where('id', $id)->first() : null;

        if ($request->isMethod('POST')) {
            $payload = (array)$request->input('VehicleOffer', []);
            $save = $this->filterOfferPayload($payload);
            if ($id) {
                DB::table('vehicle_offers')->where('id', $id)->update($save);
            } else {
                $save['created'] = $save['created'] ?? now()->toDateTimeString();
                $id = (int)DB::table('vehicle_offers')->insertGetId($save);
            }

            return redirect($this->offerBasePath() . '/view/' . base64_encode((string)$id))
                ->with('success', 'Offer saved successfully');
        }

        return view('admin.vehicle_offers.add', [
            'offer' => $offer,
            'basePath' => $this->offerBasePath(),
        ]);
    }

    public function userautocomplete(Request $request): JsonResponse
    {
        $term = trim((string)$request->query('term', ''));
        $id = (int)$request->query('id', 0);
        $q = DB::table('users')->where('status', 1);
        if ($id > 0) {
            $q->where('id', $id);
        } elseif ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('contact_number', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }
        $rows = $q->orderBy('first_name')->limit(15)->get(['id', 'first_name', 'last_name', 'contact_number']);

        return response()->json($rows->map(static fn ($u) => [
            'id' => (int)$u->id,
            'tag' => trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? '') . ' - ' . ($u->contact_number ?? '')),
        ])->values()->all());
    }

    public function vehicleautocomplete(Request $request): JsonResponse
    {
        $term = trim((string)$request->query('term', ''));
        $id = (int)$request->query('id', 0);
        $q = DB::table('vehicles')->where('trash', 0);
        if ($id > 0) {
            $q->where('id', $id);
        } elseif ($term !== '') {
            $like = '%' . addcslashes($term, '%_\\') . '%';
            $q->where(function ($qq) use ($like) {
                $qq->where('vehicle_unique_id', 'like', $like)
                    ->orWhere('vehicle_name', 'like', $like)
                    ->orWhere('vin_no', 'like', $like);
            });
        }
        $rows = $q->orderBy('vehicle_unique_id')->limit(15)->get(['id', 'vehicle_unique_id', 'vehicle_name']);

        return response()->json($rows->map(static fn ($v) => [
            'id' => (int)$v->id,
            'tag' => trim(($v->vehicle_unique_id ?? '') . ' - ' . ($v->vehicle_name ?? '')),
        ])->values()->all());
    }

    public function cancel($id = null)
    {
        $offerId = $this->decodeId((string)$id);
        if ($offerId) {
            DB::table('vehicle_offers')->where('id', $offerId)->update(['status' => 2]);
        }

        return redirect($this->offerBasePath() . '/index')->with('success', 'Offer cancelled');
    }

    public function delete($id = null)
    {
        $offerId = $this->decodeId((string)$id);
        if ($offerId) {
            DB::table('vehicle_offers')->where('id', $offerId)->delete();
        }

        return redirect($this->offerBasePath() . '/index')->with('success', 'Offer deleted');
    }

    public function view($offer_id = null)
    {
        $id = $this->decodeId((string)$offer_id);
        if (!$id) {
            return redirect($this->offerBasePath() . '/index');
        }
        $offer = $this->offerQuery()->where('vo.id', $id)->first();
        if (!$offer) {
            return redirect($this->offerBasePath() . '/index');
        }

        return view('admin.vehicle_offers.view', ['offer' => $offer, 'basePath' => $this->offerBasePath()]);
    }

    public function qualify(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Qualification checks passed']);
    }

    public function qualifyIncome(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'message' => 'Income qualifies']);
    }

    public function getVehicleDynamicFareMatrix(Request $request): JsonResponse
    {
        return response()->json(['status' => true, 'data' => ['matrix' => []]]);
    }

    public function duplicate($offerid)
    {
        $id = $this->decodeId((string)$offerid);
        if (!$id) {
            return redirect($this->offerBasePath() . '/index');
        }
        $offer = DB::table('vehicle_offers')->where('id', $id)->first();
        if (!$offer) {
            return redirect($this->offerBasePath() . '/index');
        }
        $copy = (array)$offer;
        unset($copy['id']);
        $copy['created'] = now()->toDateTimeString();
        $newId = (int)DB::table('vehicle_offers')->insertGetId($copy);

        return redirect($this->offerBasePath() . '/view/' . base64_encode((string)$newId))
            ->with('success', 'Offer duplicated');
    }

    protected function offerBasePath(): string
    {
        return '/admin/vehicle_offers';
    }

    protected function offerQuery()
    {
        return DB::table('vehicle_offers as vo')
            ->leftJoin('users as u', 'u.id', '=', 'vo.user_id')
            ->leftJoin('users as r', 'r.id', '=', 'vo.renter_id')
            ->leftJoin('vehicles as v', 'v.id', '=', 'vo.vehicle_id')
            ->select([
                'vo.*',
                'u.first_name as owner_first_name',
                'u.last_name as owner_last_name',
                'r.first_name as renter_first_name',
                'r.last_name as renter_last_name',
                'v.vehicle_unique_id',
                'v.vehicle_name',
            ]);
    }

    protected function filterOfferPayload(array $payload): array
    {
        $allowed = [
            'user_id', 'renter_id', 'vehicle_id', 'status', 'offer_price', 'finance_type', 'term',
            'down_payment', 'apr', 'monthly_payment', 'note', 'start_datetime', 'end_datetime',
        ];
        $out = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $payload)) {
                $out[$key] = $payload[$key];
            }
        }
        $out['modified'] = now()->toDateTimeString();

        return $out;
    }

    protected function resolveLimit(Request $request): int
    {
        if ($request->has('Record.limit')) {
            $lim = (int)$request->input('Record.limit');
            if ($lim > 0 && $lim <= 500) {
                session(['vehicle_offers_limit' => $lim]);
            }
        }
        $limit = (int)session('vehicle_offers_limit', 50);

        return $limit > 0 ? $limit : 50;
    }
}

