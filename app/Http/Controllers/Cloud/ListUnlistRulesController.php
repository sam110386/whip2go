<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Admin\ListUnlistRulesController as AdminListUnlistRulesController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListUnlistRulesController extends AdminListUnlistRulesController
{
    /**
     * Cloud/dealer frontend: manage list/unlist rules (user id from session).
     */
    public function index(Request $request, $userId = null)
    {
        if ($redirect = $this->ensureCloudAdminSession()) {
            return $redirect;
        }

        $result = $this->manageRules($request, false, null);
        if ($result instanceof RedirectResponse) {
            return $result;
        }

        $formUrl = '/cloud/list_unlist_rules/index';
        $backUrl = '/cloud/vehicles/index';
        $backLabel = 'Back to Vehicles';

        return view('cloud.list_unlist_rules.index', array_merge($result, [
            'title_for_layout' => 'List / Unlist Rules',
            'formUrl' => $formUrl,
            'backUrl' => $backUrl,
            'backLabel' => $backLabel,
        ]));
    }
}
