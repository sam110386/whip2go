<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Elandlib;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ElandmobController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request, $user)
    {
        $user = base64_decode($user);
        if (empty($user)) {
            abort(400, 'Sorry, wrong attempt.');
        }

        $states = $this->getStates();
        $years = $months = [];
        for ($i = 0; $i <= 50; $i++) {
            $years[$i] = $i . ' Years';
        }
        for ($i = 0; $i <= 11; $i++) {
            $months[$i] = $i . ' Months';
        }

        $residenceType = ['B' => 'Buying', 'P' => 'Own', 'R' => 'Rent', 'O' => 'Other'];
        $emptype = ['Full-Time' => 'Employed - Full Time', 'PartTime' => 'Employed - Part Time', 'Retired' => 'Retired', 'Military' => 'Military', 'Self-Employed' => 'Self-Employed'];

        $userdata = DB::table('users')->where('id', $user)->first();
        if (empty($userdata)) {
            return redirect('/');
        }

        $lead = DB::table('eland_leads')->where('user_id', $user)->first();
        $formData = [];
        if (!empty($lead)) {
            $formData = json_decode($lead->data, true);
        }

        return view('cloud.eland.index', compact('states', 'years', 'months', 'residenceType', 'emptype', 'userdata', 'formData'));
    }

    public function final(Request $request, $user)
    {
        $user = base64_decode($user);
        if (empty($user)) {
            abort(400, 'Sorry, wrong attempt.');
        }

        $states = $this->getStates();
        $years = $months = [];
        for ($i = 0; $i <= 50; $i++) {
            $years[$i] = $i . ' Years';
        }
        for ($i = 0; $i <= 11; $i++) {
            $months[$i] = $i . ' Months';
        }

        $residenceType = ['B' => 'Buying', 'P' => 'Own', 'R' => 'Rent', 'O' => 'Other'];
        $emptype = ['Full-Time' => 'Employed - Full Time', 'PartTime' => 'Employed - Part Time', 'Retired' => 'Retired', 'Military' => 'Military', 'Self-Employed' => 'Self-Employed'];

        $userdata = DB::table('users')->where('id', $user)->first();
        $lead = DB::table('eland_leads')->where('user_id', $user)->first();
        $formData = [];
        if (!empty($lead)) {
            $formData = json_decode($lead->data, true);
        }

        return view('cloud.eland.final', compact('states', 'years', 'months', 'residenceType', 'emptype', 'userdata', 'formData'));
    }

    public function success()
    {
        return view('cloud.eland.success');
    }

    public function saveStepOne(Request $request, $user)
    {
        $user = base64_decode($user);
        if (empty($user)) {
            abort(400, 'Sorry, wrong attempt.');
        }

        $data = $request->input('Eland');
        if (!isset($data['term']) || empty($data['term'])) {
            return redirect()->back()->with('error', 'Sorry, please accept the terms & conditions');
        }

        $lead = DB::table('eland_leads')->where('user_id', $user)->first();
        $existingResidence = !empty($lead) ? (json_decode($lead->data, true)['ElandResidence'] ?? []) : [];

        $dataToSave = [
            'user_id' => $user,
            'data' => json_encode(['Eland' => $data, 'ElandResidence' => $existingResidence]),
            'modified' => now(),
        ];

        if (!empty($lead)) {
            DB::table('eland_leads')->where('id', $lead->id)->update($dataToSave);
        } else {
            $dataToSave['created'] = now();
            DB::table('eland_leads')->insert($dataToSave);
        }

        return redirect(url('eland/elandmob/final/' . base64_encode($user)));
    }

    public function saveFinalStep(Request $request, $user)
    {
        $user = base64_decode($user);
        if (empty($user)) {
            abort(400, 'Sorry, wrong attempt.');
        }

        $data = $request->input('ElandResidence');
        $lead = DB::table('eland_leads')->where('user_id', $user)->first();

        if (empty($lead)) {
            return redirect(url('eland/elandmob/index/' . base64_encode($user)))
                ->with('error', 'Sorry, please fill all details again');
        }

        $leadData = json_decode($lead->data, true);
        $tempData = ['Eland' => $leadData['Eland'], 'ElandResidence' => $data];

        $reservation = DB::table('vehicle_reservations')
            ->where('renter_id', $user)
            ->where('status', 0)
            ->orderByDesc('id')
            ->select('id', 'user_id', 'vehicle_id')
            ->first();

        if (!empty($reservation)) {
            $elandSetting = DB::table('eland_settings')->where('user_id', $reservation->user_id)->first();
            $elandlib = new Elandlib(
                $elandSetting->jwt_sub ?? '',
                $elandSetting->token ?? '',
                $elandSetting->indentifier ?? ''
            );
            $tempData['eland_id'] = $lead->eland_id ?? '';
            $tempData['Eland']['ssn'] = '';
            $resp = $elandlib->sendLead($tempData);

            $updateData = ['data' => json_encode($tempData), 'modified' => now()];
            if ($resp['status']) {
                $updateData['eland_id'] = $resp['appid'];
            }
            DB::table('eland_leads')->where('id', $lead->id)->update($updateData);
        }

        return redirect(url('eland/elandmob/success'));
    }

    private function getStates(): array
    {
        return [
            'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
            'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
            'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
            'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
            'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
            'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
            'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
            'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
            'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
            'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
            'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
            'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
            'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'Washington DC',
        ];
    }
}
