<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListUnlistRulesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    /**
     * Admin: manage list/unlist rules for a dealer.
     */
    public function index(Request $request, $userId = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (empty($userId) && $request->has('user_id')) {
            $userId = $request->input('user_id');
        }
        $userId = is_numeric($userId) ? (int) $userId : null;

        $result = $this->manageRules($request, true, $userId);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $formUrl = '/admin/list_unlist_rules/index' . ($userId ? '/' . $userId : '') . '?user_id=' . $userId;
        $backUrl = '/admin/vehicles/index';
        $backLabel = 'Back to Vehicles';

        return view('admin.list_unlist_rules.index', array_merge($result, [
            'title_for_layout' => 'List / Unlist Rules',
            'formUrl' => $formUrl,
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
            'managedUserId' => $userId,
        ]));
    }

    /**
     * Load/save list-unlist rules.
     *
     * @return RedirectResponse|array View data array when no redirect needed.
     */
    protected function manageRules(Request $request, bool $isAdmin, ?int $userId)
    {
        if (!$isAdmin) {
            $userId = (int) session('userParentId');
            if ($userId === 0) {
                $userId = (int) session('userid');
            }
        }

        if (empty($userId) || !is_numeric($userId)) {
            if ($isAdmin) {
                return redirect('/admin/homes/dashboard')
                    ->with('error', 'Please provide a user id in the URL (e.g. user_id:123).');
            }
            return redirect('/users/login')
                ->with('error', 'Please log in.');
        }
        $userId = (int) $userId;

        $setting = DB::table('cs_settings')->where('user_id', $userId)->first();
        $listingRule = 'list_all';
        $unlistRules = [];

        if (!empty($setting->listing_rule)) {
            $listingRule = $setting->listing_rule;
        }
        if (!empty($setting->unlist_rules)) {
            $decoded = json_decode($setting->unlist_rules, true);
            $unlistRules = is_array($decoded) ? $decoded : [];
        }

        if ($request->isMethod('post') && $request->has('ListUnlistRule')) {
            $data = $request->input('ListUnlistRule');
            $listingRule = $data['listing_rule'] ?? 'list_all';
            $unlistRules = [];

            if ($listingRule === 'unlist_by_ymm' && !empty($data['unlist_rules']) && is_array($data['unlist_rules'])) {
                foreach ($data['unlist_rules'] as $row) {
                    if (empty($row['year']) && empty($row['make']) && empty($row['model'])) {
                        continue;
                    }
                    $unlistRules[] = [
                        'year' => isset($row['year']) ? trim($row['year']) : '',
                        'make' => isset($row['make']) ? trim($row['make']) : '',
                        'model' => isset($row['model']) ? trim($row['model']) : '',
                    ];
                }
            }

            $toSave = [
                'listing_rule' => $listingRule,
                'unlist_rules' => json_encode($unlistRules),
            ];

            if (!empty($setting->id)) {
                DB::table('cs_settings')->where('id', $setting->id)->update($toSave);
            } else {
                $toSave['user_id'] = $userId;
                DB::table('cs_settings')->insert($toSave);
            }

            if (!empty($data['apply_now'])) {
                $this->applyRulesToVehicles($userId, $listingRule, $unlistRules);
            }

            $flash = 'List/Unlist rules saved successfully.';

            if ($isAdmin) {
                return redirect('/admin/list_unlist_rules/index/' . $userId . '?user_id=' . $userId)
                    ->with('success', $flash);
            }
            return redirect('/cloud/list_unlist_rules/index')
                ->with('success', $flash);
        }

        return compact('listingRule', 'unlistRules');
    }

    /**
     * Apply listing rule to all vehicles for this dealer.
     */
    protected function applyRulesToVehicles(int $userId, string $listingRule, array $unlistRules): void
    {
        $vehicles = DB::table('vehicles')
            ->where('user_id', $userId)
            ->where('trash', 0)
            ->select('id', 'year', 'make', 'model', 'status')
            ->get();

        foreach ($vehicles as $v) {
            $newStatus = 1;
            if ($listingRule === 'unlist_all') {
                $newStatus = 0;
            } elseif ($listingRule === 'unlist_by_ymm' && !empty($unlistRules)) {
                foreach ($unlistRules as $rule) {
                    $yearOk = empty($rule['year']) || ((string) $v->year === (string) $rule['year']);
                    $makeOk = empty($rule['make']) || (strcasecmp(trim($v->make ?? ''), trim($rule['make'])) === 0);
                    $modelOk = empty($rule['model']) || (strcasecmp(trim($v->model ?? ''), trim($rule['model'])) === 0);
                    if ($yearOk && $makeOk && $modelOk) {
                        $newStatus = 0;
                        break;
                    }
                }
            }
            if ((int) $v->status !== $newStatus) {
                DB::table('vehicles')->where('id', $v->id)->update(['status' => $newStatus]);
            }
        }
    }
}
