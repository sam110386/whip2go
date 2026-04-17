<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class InsuranceQuotesController extends LegacyAppController
{
    public function review($orderandusers)
    {
        $parts = explode('|', base64_decode($orderandusers));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('order_deposit_rules as OrderDepositRule', 'OrderDepositRule.vehicle_reservation_id', '=', 'VehicleReservation.id')
            ->where('VehicleReservation.id', $order)
            ->where('VehicleReservation.status', 0)
            ->whereNotNull('OrderDepositRule.id')
            ->select(
                'VehicleReservation.id',
                'VehicleReservation.renter_id',
                'OrderDepositRule.insurance_payer'
            )
            ->orderByDesc('VehicleReservation.id')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $selectedQuote = DB::table('insurance_quotes as InsuranceQuote')
            ->leftJoin('insurance_providers as InsuranceProvider', 'InsuranceProvider.id', '=', 'InsuranceQuote.provider_id')
            ->where('InsuranceQuote.order_id', $order)
            ->where('InsuranceQuote.selected', 1)
            ->where('InsuranceQuote.docusign_envelope_id', '!=', '')
            ->select(
                'InsuranceQuote.*',
                'InsuranceProvider.id as provider_table_id',
                'InsuranceProvider.name as provider_name',
                'InsuranceProvider.logo as provider_logo'
            )
            ->first();

        $VehicleReservation = DB::table('vehicle_reservations')
            ->where('id', $order)
            ->first(['docusign']);

        if (!empty($selectedQuote) && $VehicleReservation && $VehicleReservation->docusign == 1) {
            return view('cloud.insurance_provider.docusign.returncallback', [
                'thankyou' => 'You have already selected an insurance policy with Lincoln Insurance and Bonding Group. We will be in touch with next steps.',
                'title_for_layout' => 'Insurance Review Quote',
            ]);
        }

        $providers = DB::table('insurance_quotes')
            ->where('order_id', $order)
            ->pluck('total_limit')
            ->toArray();

        if (empty($providers) && $booking->insurance_payer == 6) {
            return redirect('/insurance_provider/insurance_quotes/lincolnreview/' . $orderandusers);
        }

        return view('cloud.insurance_provider.insurance_quotes.review', [
            'providers'        => $providers,
            'orderandusers'    => $orderandusers,
            'title_for_layout' => 'Insurance Review Quote',
        ]);
    }

    public function providers($id, $orderandusers)
    {
        $parts = explode('|', base64_decode($orderandusers));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $query = DB::table('insurance_quotes as InsuranceQuote')
            ->leftJoin('insurance_providers as InsuranceProvider', function ($join) {
                $join->on('InsuranceProvider.id', '=', 'InsuranceQuote.provider_id')
                    ->where('InsuranceProvider.status', 1);
            })
            ->where('InsuranceQuote.order_id', $order)
            ->select(
                'InsuranceQuote.*',
                'InsuranceProvider.id as provider_table_id',
                'InsuranceProvider.name as provider_name',
                'InsuranceProvider.logo as provider_logo'
            );

        $limitMap = [
            1 => '25/50/25',
            2 => '50/100/50',
            3 => '100/300/100',
        ];
        if (isset($limitMap[$id])) {
            $query->where('InsuranceQuote.total_limit', $limitMap[$id]);
        }

        $providers = $query->get();

        if ($providers->isEmpty()) {
            abort(403, 'sorry wrong attempt');
        }

        return view('cloud.insurance_provider.insurance_quotes.providers', [
            'providers'        => $providers,
            'orderandusers'    => $orderandusers,
            'title_for_layout' => 'Insurance Review Quote',
        ]);
    }

    public function finalreview($id, $orderandusers)
    {
        $parts = explode('|', base64_decode($orderandusers));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $quotes = DB::table('insurance_quotes as InsuranceQuote')
            ->leftJoin('insurance_providers as InsuranceProvider', function ($join) {
                $join->on('InsuranceProvider.id', '=', 'InsuranceQuote.provider_id')
                    ->where('InsuranceProvider.status', 1);
            })
            ->where('InsuranceQuote.order_id', $order)
            ->select('InsuranceQuote.id')
            ->get();

        if ($quotes->isEmpty()) {
            abort(403, 'sorry wrong attempt');
        }

        foreach ($quotes as $quote) {
            $selected = ($quote->id == $id) ? 2 : 0;
            DB::table('insurance_quotes')->where('id', $quote->id)->update(['selected' => $selected]);
        }

        return view('cloud.insurance_provider.insurance_quotes.finalreview', [
            'orderandusers'    => $orderandusers,
            'insuranceQuoteId' => $id,
            'title_for_layout' => 'Insurance Review Quote Thank you!! ',
        ]);
    }

    public function lincolnreview($orderandusers)
    {
        $parts = explode('|', base64_decode($orderandusers));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = DB::table('vehicle_reservations as VehicleReservation')
            ->leftJoin('users as Driver', 'Driver.id', '=', 'VehicleReservation.renter_id')
            ->leftJoin('user_license_details as UserLicenseDetail', 'UserLicenseDetail.user_id', '=', 'VehicleReservation.renter_id')
            ->leftJoin('vehicles as Vehicle', 'Vehicle.id', '=', 'VehicleReservation.vehicle_id')
            ->where('VehicleReservation.id', $order)
            ->where('VehicleReservation.renter_id', $user)
            ->where('VehicleReservation.status', 0)
            ->select(
                'VehicleReservation.start_datetime',
                'Driver.first_name', 'Driver.last_name', 'Driver.email',
                'Driver.contact_number', 'Driver.address', 'Driver.city',
                'Driver.state', 'Driver.zip',
                'UserLicenseDetail.dateOfBirth', 'UserLicenseDetail.sex',
                'UserLicenseDetail.documentNumber', 'UserLicenseDetail.addressState',
                'UserLicenseDetail.addressStreet', 'UserLicenseDetail.addressCity',
                'UserLicenseDetail.addressPostalCode',
                'Vehicle.make', 'Vehicle.model', 'Vehicle.year', 'Vehicle.vin_no'
            )
            ->orderByDesc('VehicleReservation.id')
            ->first();

        if (empty($booking)) {
            abort(403, 'sorry wrong attempt');
        }

        $booking = (array) $booking;

        if (!empty($booking['documentNumber'])) {
            $booking['documentNumber'] = decrypt($booking['documentNumber']);
        }

        if (empty($booking['address'])) {
            $booking['address'] = $booking['addressStreet'] ?? '';
        }
        if (empty($booking['city'])) {
            $booking['city'] = $booking['addressCity'] ?? '';
        }
        if (empty($booking['state'])) {
            $booking['state'] = $booking['addressState'] ?? '';
        }
        if (empty($booking['zip'])) {
            $booking['zip'] = $booking['addressPostalCode'] ?? '';
        }

        $libilities = [
            'Recomended' => [
                '25/50/25' => '$25k/$50K/$25K',
            ],
            'Other' => [
                '50/100/25'  => '$50k/$100K/$25K',
                '50/100/50'  => '$50k/$100K/$50K',
                '100/300/50' => '$100k/$300K/$50K',
                '100/300/100' => '$100k/$300K/$100K',
                '250/500/100' => '$250k/$500K/$100K',
                '250/500/250' => '$250k/$500K/$250K',
            ],
        ];

        $education = [
            'No High School Diploma or GED' => 'No High School Diploma or GED',
            'High School'                   => 'High School',
            'Associate Degree'              => 'Associate Degree',
            "Bachelor's Degree"             => "Bachelor's Degree",
            'Graduate or Professional Degree' => 'Graduate or Professional Degree',
            'Some College'                  => 'Some College',
            'Other'                         => 'Other',
            'Prefer Not to Answer'          => 'Prefer Not to Answer',
        ];

        $marital_status = [
            '0' => 'Single',
            '1' => 'Married',
            '2' => 'Domestic Partner (Unmarried)',
            '3' => 'Widowed',
            '4' => 'Separated',
            '5' => 'Divorced',
            '6' => 'Fiance or Fiancee',
            '7' => 'Other',
            '8' => 'Unknown',
            '9' => 'Civil Union / Registered Domestic Partner',
        ];

        return view('cloud.insurance_provider.insurance_quotes._lincolnn_insurance_quote_from', compact(
            'orderandusers',
            'libilities',
            'education',
            'marital_status',
            'booking'
        ));
    }

    public function lincolnquotesave(Request $request, $orderandusers)
    {
        $parts = explode('|', base64_decode($orderandusers));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        $titleForLayout = 'Sorry, something went wrong in your application data. We will review the details and get back to you.';

        if ($request->isMethod('post') && $request->has('quote')) {
            $q = $request->input('quote');

            $requestBody = [
                'input_4.3'  => 'Auto',
                'input_274'  => date('m/d/Y', strtotime($q['start_date'] ?? '')),
                'input_1.3'  => $q['first_name'] ?? '',
                'input_1.6'  => $q['last_name'] ?? '',
                'input_6'    => $q['email'] ?? '',
                'input_7'    => substr($q['phone'] ?? '', -10),
                'input_8'    => 'Yes',
                'input_11_copy_values_activated' => '1',
                'input_10.1' => $q['address'] ?? '',
                'input_10.3' => $q['city'] ?? '',
                'input_10.4' => $q['state'] ?? '',
                'input_10.5' => $q['zip'] ?? '',
                'input_13'   => $q['dob'] ?? '',
                'input_14'   => (strtolower($q['gender'] ?? '') == 'm') ? 0 : 1,
                'input_15'   => $q['marital_status'] ?? '',
                'input_16'   => $q['license_number'] ?? '',
                'input_17'   => $q['license_state'] ?? '',
                'input_281'  => 'No',
                'input_236'  => 'No',
                'input_89'   => strtoupper($q['vin'] ?? ''),
                'input_90'   => $q['year'] ?? '',
                'input_91'   => $q['make'] ?? '',
                'input_92'   => $q['model'] ?? '',
                'input_209'  => 'Lease',
                'input_246'  => 'Yes',
                'input_261'  => 'Unsure',
                'input_262'  => 'Unsure',
                'input_283'  => $q['past_cliam'] ?? '',
                'input_284'  => $q['need_sr_filling'] ?? '',
                'input_292'  => (int) ($q['current_auto_libility'] ?? 0),
                'input_293'  => (int) ($q['desired_auto_libility'] ?? 0),
                'input_294'  => $q['current_insurance_company'] ?? '',
                'input_287'  => 'DriveItAway',
                'input_191'  => 'No',
                'input_198'  => 'I Agree',
                'input_198.1' => 1,
                'input_198.2' => 'I Agree',
                'input_198.3' => 24,
                'input_285'  => 'N/A',
            ];

            $resp = $this->sendHttpRequest($requestBody);
            if (isset($resp['is_valid']) && $resp['is_valid']) {
                $titleForLayout = 'Thanks for your submission. We will review the details and get back to you.';
            }
        }

        return view('cloud.insurance_provider.insurance_quotes._thankyou', [
            'title_for_layout' => $titleForLayout,
        ]);
    }

    private function sendHttpRequest(array $requestBody): array
    {
        $url = 'https://lincolninsure.com/wp-json/gf/v2/forms/69/submissions';
        $credentials = base64_encode(
            'ck_bb002c433a3c72420415de3a4cae084b762ef53f:cs_08e40f2c3028d5d0f4d7d2216309f97ebcf8ea3e'
        );

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type'  => 'application/json',
            ])
                ->withoutVerifying()
                ->post($url, $requestBody);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            return ['is_valid' => false, 'error' => $e->getMessage()];
        }
    }
}
