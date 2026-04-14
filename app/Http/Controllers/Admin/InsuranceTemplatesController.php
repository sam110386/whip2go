<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

class InsuranceTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /**
     * @return View|RedirectResponse
     */
    public function index(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $userId = $this->decodeId((string) $userid);
        if ($userId === null) {
            abort(404);
        }

        if ($request->isMethod('post') && $request->has('CsInsuranceTemplate')) {
            $this->persistTemplate($request, $userId);

            return redirect('/admin/insurance_templates/index/' . $this->encodeId($userId))
                ->with('success', 'Record saved successfully');
        }

        $row = DB::table('cs_insurance_templates')->where('user_id', $userId)->first();

        return view('admin.insurance_templates.index', [
            'title' => 'Insurance',
            'userid' => $userId,
            'userParamEncoded' => $this->encodeId($userId),
            'CsInsuranceTemplate' => $row ? (array) $row : [],
            'programOptions' => $this->programOptions(),
        ]);
    }

    public function syncVehicleInsurance(Request $request): JsonResponse
    {
        if ($this->ensureAdminSession() !== null) {
            return response()->json([
                'status' => false,
                'message' => 'Session expired.',
            ], 401);
        }

        $return = [
            'status' => false,
            'message' => 'Sorry, something went wrong, please try again later',
        ];

        $templateId = $request->input('templateid');
        if ($templateId === null || $templateId === '') {
            return response()->json($return);
        }

        $template = DB::table('cs_insurance_templates')
            ->where('id', (int) $templateId)
            ->first();

        if ($template === null) {
            return response()->json($return);
        }

        try {
            DB::table('vehicles')
                ->where('user_id', (int) $template->user_id)
                ->update([
                    'insurance_policy_no' => $template->insurance_policy_no,
                    'insurance_company' => $template->insurance_company,
                    'insurance_policy_date' => $template->insurance_policy_date,
                    'insurance_policy_exp_date' => $template->insurance_policy_exp_date,
                ]);

            $return['status'] = true;
            $return['message'] = 'Vehicle records updated successfully.';
        } catch (Throwable $e) {
            $return['message'] = $e->getMessage();
        }

        return response()->json($return);
    }

    private function persistTemplate(Request $request, int $userId): void
    {
        $input = $request->input('CsInsuranceTemplate', []);

        $payload = [
            'user_id' => $userId,
            'program' => (int) ($input['program'] ?? 1),
            'insu_token_name' => $this->truncateString($input['insu_token_name'] ?? null, 255),
            'insurance_company' => $this->truncateString($input['insurance_company'] ?? null, 200),
            'insurance_policy_no' => $this->truncateString($input['insurance_policy_no'] ?? null, 25),
            'insurance_policy_date' => $this->truncateString($input['insurance_policy_date'] ?? null, 12),
            'insurance_policy_exp_date' => $this->truncateString($input['insurance_policy_exp_date'] ?? null, 12),
        ];

        $submittedId = isset($input['id']) && $input['id'] !== '' ? (int) $input['id'] : null;

        if ($submittedId) {
            DB::table('cs_insurance_templates')
                ->where('id', $submittedId)
                ->where('user_id', $userId)
                ->update($payload);

            return;
        }

        $existing = DB::table('cs_insurance_templates')->where('user_id', $userId)->first();
        if ($existing) {
            DB::table('cs_insurance_templates')
                ->where('user_id', $userId)
                ->update($payload);

            return;
        }

        $payload['status'] = 1;
        DB::table('cs_insurance_templates')->insert($payload);
    }

    /**
     * @return array<int|string, string>
     */
    private function programOptions(): array
    {
        return [
            0 => 'General Access',
            1 => 'Rideshare (Uber/Lfyt Acess)',
            2 => 'Both',
        ];
    }

    private function truncateString(?string $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }
}
