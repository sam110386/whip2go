<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmailTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    protected function _validateId($id): bool
    {
        return !empty($id) && is_numeric($id);
    }

    // ─── admin_index (List all email templates) ────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $this->set('title_for_layout', 'Manage Email Templates');

        $keyword   = $request->input('Search.keyword', $request->query('keyword', ''));
        $searchIn  = $request->input('Search.searchin', $request->query('searchin', 'All'));
        $showType  = $request->input('Search.show', $request->query('showtype', ''));

        $query = EmailTemplate::query()->orderBy('id', 'DESC');

        if (!empty($keyword)) {
            $v = strip_tags($keyword);
            if ($searchIn == 'All') {
                $query->where(function($q) use ($v) {
                    $q->where('head_title', 'LIKE', "%$v%")
                      ->orWhere('subject', 'LIKE', "%$v%");
                });
            } else {
                $query->where($searchIn, 'LIKE', "%$v%");
            }
        }

        if ($showType !== '' && $showType !== 'All') {
            $query->where('type', $showType);
        }

        $sessionLimitKey  = 'EmailTemplates_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $emailTemplates = $query->paginate($limit)->withQueryString();

        return view('admin.email_templates.index', [
            'emailTemplates' => $emailTemplates,
            'keyword'        => $keyword,
            'fieldname'      => $searchIn,
            'show'           => $showType,
            'listTitle'      => 'Manage Email Templates',
        ]);
    }

    // ─── admin_add / admin_edit ───────────────────────────────────────────────
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $listTitle = empty($id) ? 'Add email template' : 'Update email template';
        $submitButton = empty($id) ? 'Add' : 'Update';

        if ($request->isMethod('post')) {
            $data = $request->input('EmailTemplate', []);
            
            // Handle reminder-specific provider/customer IDs (type 2)
            if (($data['type'] ?? 1) == 2) {
                $data['providers'] = !empty($data['provider_id']) ? implode(',', $data['provider_id']) : '0';
                $data['customers'] = !empty($data['customer_id']) ? implode(',', $data['customer_id']) : '0';
            } else {
                $data['providers'] = '0';
                $data['customers'] = '0';
            }

            if ($id) {
                EmailTemplate::where('id', $id)->update($data);
                $msg = 'Email template updated successfully.';
            } else {
                EmailTemplate::create($data);
                $msg = 'Email template added successfully.';
            }

            return redirect('/admin/email_templates/index')->with('success', $msg);
        }

        $record = $id ? EmailTemplate::find($id) : null;
        view()->share('data', $record);

        return view('admin.email_templates.add', compact('listTitle', 'id', 'submitButton', 'record'));
    }

    // ─── admin_view ───────────────────────────────────────────────────────────
    public function admin_view(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $record = EmailTemplate::findOrFail($id);
        
        // Decode html entities for display
        $record->head_title = html_entity_decode($record->head_title ?? '');
        $record->subject    = html_entity_decode($record->subject ?? '');
        $record->description = html_entity_decode($record->description ?? '');

        return view('admin.email_templates.view', [
            'listTitle' => 'View Email Template',
            'record'    => $record
        ]);
    }

    // ─── admin_status (Toggle activation) ─────────────────────────────────────
    public function admin_status(Request $request, $id, $status = 0)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        EmailTemplate::where('id', $id)->update(['status' => ($status == 1 ? 0 : 1)]);

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    // ─── admin_delete ─────────────────────────────────────────────────────────
    public function admin_delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        if ($id) {
            EmailTemplate::where('id', $id)->delete();
            return redirect('/admin/email_templates')->with('success', 'Email template deleted successfully.');
        }

        return redirect('/admin/email_templates')->with('error', 'Information not deleted.');
    }

    // ─── admin_multiplAction (Bulk actions) ──────────────────────────────────
    public function admin_multiplAction(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $action = $request->input('EmailTemplate.submit');
        $selectedIds = $request->input('select', []);

        if (!empty($selectedIds)) {
            if ($action == 'active') {
                EmailTemplate::whereIn('id', $selectedIds)->update(['status' => 1]);
            } elseif ($action == 'inactive') {
                EmailTemplate::whereIn('id', $selectedIds)->update(['status' => 0]);
            } elseif ($action == 'del') {
                EmailTemplate::whereIn('id', $selectedIds)->delete();
            }
        }

        return redirect()->back()->with('success', 'Bulk action completed.');
    }
}
