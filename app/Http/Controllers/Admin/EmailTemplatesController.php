<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmailTemplatesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    private const SESSION_LIMIT_KEY = 'email_templates_limit';

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!Schema::hasTable('email_templates')) {
            return view('admin.email_templates.index', [
                'listTitle' => 'Manage Email Templates',
                'emailTemplates' => null,
                'keyword' => '',
                'searchin' => '',
                'fieldname' => '',
                'show' => '',
                'options' => $this->searchFieldOptions(),
                'showArr' => $this->statusLabels(),
                'limit' => 25,
            ]);
        }

        $limit = $this->resolveLimit($request);
        $keyword = trim((string) $request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = trim((string) $request->input('Search.searchin', $request->query('searchin', '')));
        $show = trim((string) $request->input('Search.show', $request->query('showtype', '')));

        $fieldname = $searchin !== '' ? $searchin : 'All';

        $q = DB::table('email_templates')->orderByDesc('id');

        if ($keyword !== '') {
            $like = '%' . addcslashes($keyword, '%_\\') . '%';
            if ($fieldname === 'All' || $fieldname === '') {
                // CakePHP parity: combined AND on head_title and subject.
                $q->where('head_title', 'like', $like)->where('subject', 'like', $like);
            } elseif (in_array($fieldname, ['head_title', 'subject'], true)) {
                $q->where($fieldname, 'like', $like);
            }
        }
        if ($show !== '' && $show !== 'All' && ($show === '1' || $show === '2')) {
            $q->where('type', (int) $show);
        }

        $emailTemplates = $q->paginate($limit)->withQueryString();

        return view('admin.email_templates.index', [
            'listTitle' => 'Manage Email Templates',
            'emailTemplates' => $emailTemplates,
            'keyword' => $keyword,
            'searchin' => $searchin,
            'fieldname' => $fieldname,
            'show' => $show,
            'options' => $this->searchFieldOptions(),
            'showArr' => $this->statusLabels(),
            'limit' => $limit,
        ]);
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (!Schema::hasTable('email_templates')) {
            return redirect('/admin/email_templates/index')->with('error', 'Email templates table is not available.');
        }

        $decodedId = $id !== null && $id !== '' ? $this->decodeId($id) : null;
        $existing = null;
        if ($decodedId !== null) {
            $existing = DB::table('email_templates')->where('id', $decodedId)->first();
        }

        if ($decodedId !== null && $existing === null) {
            return redirect('/admin/email_templates/index')->with('error', 'Template not found.');
        }

        if ($request->isMethod('post')) {
            return $this->handleAddPost($request, $decodedId, $existing);
        }

        $emailTemplate = $this->emptyEmailTemplateRow();
        if ($existing) {
            $emailTemplate = (array) $existing;
            $emailTemplate['provider_id'] = $this->csvToIntArray($emailTemplate['providers'] ?? '0');
            $emailTemplate['customer_id'] = $this->csvToIntArray($emailTemplate['customers'] ?? '0');
        }

        $idSegment = $decodedId !== null ? $this->encodeId($decodedId) : null;

        return view('admin.email_templates.add', [
            'listTitle' => $decodedId ? 'Update email template' : 'Add email template',
            'submit_button' => $decodedId ? 'Update' : 'Add',
            'id' => $idSegment,
            'decodedId' => $decodedId,
            'emailTemplate' => $emailTemplate,
            'providers' => $this->loadProviderOptions(),
            'users' => $this->loadCustomerOptions(),
            'hours' => $this->reminderHourOptions(),
            'errors' => [],
        ]);
    }

    public function view(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        if ($decodedId === null || !Schema::hasTable('email_templates')) {
            return redirect('/admin/email_templates/index')->with('error', 'Id is missing.');
        }

        $row = DB::table('email_templates')->where('id', $decodedId)->first();
        if (!$row) {
            return redirect('/admin/email_templates/index')->with('error', 'Id is missing.');
        }

        $emailTemplate = [];
        foreach ((array) $row as $key => $info) {
            if (!is_string($info) && !is_numeric($info)) {
                $emailTemplate[$key] = $info;
                continue;
            }
            $v = is_string($info) ? html_entity_decode($info, ENT_QUOTES | ENT_HTML5, 'UTF-8') : $info;
            if (is_string($v)) {
                $v = str_replace('&#039;', "'", $v);
                $v = str_replace('\n', '', $v);
            }
            $emailTemplate[$key] = $v;
        }

        return view('admin.email_templates.view', [
            'listTitle' => 'View Email Template',
            'emailTemplate' => $emailTemplate,
        ]);
    }

    public function status(Request $request, $id = null, $status = 0): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        if ($decodedId !== null && Schema::hasTable('email_templates')) {
            $newStatus = ((string) $status === '1') ? 0 : 1;
            DB::table('email_templates')->where('id', $decodedId)->update(['status' => $newStatus]);
        }

        return redirect($this->indexSearchUrl($request))->with('success', 'Record updated successfully.');
    }

    public function delete(Request $request, $id = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        if ($decodedId !== null && Schema::hasTable('email_templates')) {
            DB::table('email_templates')->where('id', $decodedId)->delete();
        }

        return redirect('/admin/email_templates')->with('success', 'Record deleted successfully.');
    }

    public function multiplAction(Request $request): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $submit = (string) $request->input('EmailTemplate.submit', '');
        $ids = $request->input('select', []);
        if (!is_array($ids)) {
            $ids = [];
        }
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids !== [] && Schema::hasTable('email_templates')) {
            if ($submit === 'active') {
                DB::table('email_templates')->whereIn('id', $ids)->update(['status' => 1]);
            } elseif ($submit === 'inactive') {
                DB::table('email_templates')->whereIn('id', $ids)->update(['status' => 0]);
            } elseif ($submit === 'del') {
                DB::table('email_templates')->whereIn('id', $ids)->delete();
            }
        }

        return redirect($this->indexSearchUrl($request))->with('success', 'Record updated successfully.');
    }

    protected function resolveLimit(Request $request): int
    {
        $allowed = [25, 50, 100, 200];
        $fromForm = $request->input('Record.limit');
        if ($fromForm !== null && $fromForm !== '') {
            $lim = (int) $fromForm;
            if (in_array($lim, $allowed, true)) {
                session()->put(self::SESSION_LIMIT_KEY, $lim);

                return $lim;
            }
        }
        $sess = (int) session()->get(self::SESSION_LIMIT_KEY, 0);
        if (in_array($sess, $allowed, true)) {
            return $sess;
        }

        return 25;
    }

    private function handleAddPost(Request $request, ?int $decodedId, $existing): RedirectResponse|\Illuminate\Contracts\View\View
    {
        $payload = $request->input('EmailTemplate', []);
        $postId = $payload['id'] ?? null;
        if ($postId !== null && $postId !== '') {
            $fromForm = $this->decodeId((string) $postId);
            if ($fromForm !== null) {
                $decodedId = $fromForm;
                $existing = DB::table('email_templates')->where('id', $decodedId)->first();
            }
        }

        $headTitle = trim((string) ($payload['head_title'] ?? ''));
        $subject = trim((string) ($payload['subject'] ?? ''));
        $type = isset($payload['type']) ? (string) $payload['type'] : '';
        $description = (string) ($payload['description'] ?? '');
        $errors = [];
        if ($headTitle === '') {
            $errors['head_title'] = 'Enter title';
        }
        if ($type === '' || !in_array($type, ['1', '2'], true)) {
            $errors['type'] = 'Select template type';
        }
        if ($subject === '') {
            $errors['subject'] = 'Please enter subject';
        }

        if ($errors !== []) {
            $emailTemplate = array_merge($this->emptyEmailTemplateRow(), $payload);
            $emailTemplate['provider_id'] = (array) ($payload['provider_id'] ?? []);
            $emailTemplate['customer_id'] = (array) ($payload['customer_id'] ?? []);

            $idSegment = $decodedId !== null ? $this->encodeId($decodedId) : null;

            return view('admin.email_templates.add', [
                'listTitle' => $decodedId ? 'Update email template' : 'Add email template',
                'submit_button' => $decodedId ? 'Update' : 'Add',
                'id' => $idSegment,
                'decodedId' => $decodedId,
                'emailTemplate' => $emailTemplate,
                'providers' => $this->loadProviderOptions(),
                'users' => $this->loadCustomerOptions(),
                'hours' => $this->reminderHourOptions(),
                'errors' => $errors,
            ]);
        }

        $typeInt = (int) $type;
        $providersCsv = '0';
        $customersCsv = '0';
        if ($typeInt === 2) {
            $pids = (array) ($payload['provider_id'] ?? []);
            $pids = array_values(array_filter(array_map('intval', $pids)));
            if ($pids !== []) {
                $providersCsv = implode(',', $pids);
            }
            $cids = (array) ($payload['customer_id'] ?? []);
            $cids = array_values(array_filter(array_map('intval', $cids)));
            if ($cids !== []) {
                $customersCsv = implode(',', $cids);
            }
        }

        $reminderTime = '0';
        if ($typeInt === 2 && isset($payload['reminder_time'])) {
            $reminderTime = (string) $payload['reminder_time'];
        }

        $titleValue = trim((string) ($payload['title'] ?? ''));
        if ($titleValue === '') {
            $titleValue = $headTitle;
        }

        $now = date('Y-m-d H:i:s');
        $row = [
            'head_title' => $headTitle,
            'title' => $titleValue,
            'subject' => $subject,
            'type' => $typeInt,
            'description' => $description,
            'providers' => $providersCsv,
            'customers' => $customersCsv,
            'modified' => $now,
        ];
        if (Schema::hasColumn('email_templates', 'reminder_time')) {
            $row['reminder_time'] = $reminderTime;
        }

        if ($decodedId !== null) {
            if (!$existing) {
                return redirect('/admin/email_templates/index')->with('error', 'Template not found.');
            }
            DB::table('email_templates')->where('id', $decodedId)->update($row);
        } else {
            if (Schema::hasColumn('email_templates', 'status')) {
                $row['status'] = 1;
            }
            if (Schema::hasColumn('email_templates', 'created')) {
                $row['created'] = $now;
            }
            DB::table('email_templates')->insert($row);
        }

        return redirect('/admin/email_templates/index')->with('success', 'Record updated successfully.');
    }

    private function indexSearchUrl(Request $request): string
    {
        $keyword = trim((string) $request->input('Search.keyword', $request->query('keyword', '')));
        $searchin = trim((string) $request->input('Search.searchin', $request->query('searchin', '')));
        $show = trim((string) $request->input('Search.show', $request->query('showtype', '')));

        $parts = [];
        if ($keyword !== '') {
            $parts['keyword'] = $keyword;
        }
        if ($searchin !== '') {
            $parts['searchin'] = $searchin;
        }
        if ($show !== '') {
            $parts['showtype'] = $show;
        }
        $q = http_build_query($parts);

        return '/admin/email_templates/index' . ($q !== '' ? ('?' . $q) : '');
    }

    private function searchFieldOptions(): array
    {
        return [
            'head_title' => 'Title',
            'subject' => 'Subject',
        ];
    }

    private function statusLabels(): array
    {
        return ['Active' => 'Active', 'Deactive' => 'Inactive'];
    }

    private function emptyEmailTemplateRow(): array
    {
        return [
            'id' => '',
            'head_title' => '',
            'title' => '',
            'subject' => '',
            'type' => '',
            'description' => '',
            'status' => 1,
            'providers' => '0',
            'customers' => '0',
            'reminder_time' => '0',
            'provider_id' => [],
            'customer_id' => [],
        ];
    }

    private function csvToIntArray(string $csv): array
    {
        if ($csv === '' || $csv === '0') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $csv))));
    }

    private function loadProviderOptions(): array
    {
        if (!Schema::hasTable('users')) {
            return [];
        }
        $rows = DB::table('users')
            ->where('is_owner', 1)
            ->where('is_admin', 0)
            ->where('trash', 0)
            ->orderBy('id')
            ->limit(500)
            ->get(['id', 'business_name', 'first_name', 'last_name', 'username', 'email']);

        $out = [];
        foreach ($rows as $r) {
            $label = trim((string) ($r->business_name ?? ''));
            if ($label === '') {
                $label = trim(trim((string) ($r->first_name ?? '') . ' ' . (string) ($r->last_name ?? '')));
            }
            if ($label === '') {
                $label = (string) ($r->username ?? $r->email ?? ('User #' . $r->id));
            }
            $out[(string) $r->id] = $label;
        }

        return $out;
    }

    private function loadCustomerOptions(): array
    {
        if (!Schema::hasTable('users')) {
            return [];
        }
        $rows = DB::table('users')
            ->where('is_renter', 1)
            ->where('is_admin', 0)
            ->where('trash', 0)
            ->orderBy('id')
            ->limit(500)
            ->get(['id', 'first_name', 'last_name', 'username', 'email']);

        $out = [];
        foreach ($rows as $r) {
            $label = trim(trim((string) ($r->first_name ?? '') . ' ' . (string) ($r->last_name ?? '')));
            if ($label === '') {
                $label = (string) ($r->username ?? $r->email ?? ('User #' . $r->id));
            }
            $out[(string) $r->id] = $label;
        }

        return $out;
    }

    private function reminderHourOptions(): array
    {
        $hours = [];
        for ($h = 0; $h < 24; $h++) {
            $hours[(string) $h] = sprintf('%02d:00', $h);
        }

        return $hours;
    }
}
