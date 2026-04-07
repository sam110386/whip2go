<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\Page;
use Illuminate\Http\Request;

class PagesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    // ─── admin_index ──────────────────────────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $searchData = $request->input('Search', []);
        $namedData  = $request->query();

        $fieldname = $namedData['searchin'] ?? $searchData['searchin'] ?? '';
        $value     = $namedData['keyword']  ?? $searchData['keyword']  ?? '';

        $options = ['title' => 'Title', 'description' => 'Description'];

        $query = Page::query();

        if ($value !== '') {
            $v     = strip_tags($value);
            $fname = empty($fieldname) ? 'All' : $fieldname;

            if ($fname === 'All') {
                $query->where(function ($q) use ($v) {
                    $q->where('title', 'LIKE', "%{$v}%")
                      ->orWhere('description', 'LIKE', "%{$v}%");
                });
            } elseif (in_array($fname, ['title', 'description'])) {
                $query->where($fname, 'LIKE', "%{$v}%");
            }
        }

        $sessionLimitKey  = 'Pages_limit';
        $limitFromSession = session($sessionLimitKey, 20);
        $limit            = (int)$request->input('Record.limit', $limitFromSession);
        if ($limit < 1) $limit = 20;
        session([$sessionLimitKey => $limit]);

        $staticPages = $query->orderBy('id', 'ASC')->paginate($limit)->withQueryString();

        return view('admin.pages.index', [
            'listTitle'   => 'Manage Static Pages',
            'heading'     => 'Admin Users',
            'options'     => $options,
            'keyword'     => $value,
            'fieldname'   => $fieldname,
            'staticPages' => $staticPages,
        ]);
    }

    // ─── admin_add / admin_edit ───────────────────────────────────────────────
    public function admin_add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $listTitle = empty($id) ? 'Add Content Page' : 'Update Content Page';

        if ($request->isMethod('post')) {
            $pageData = $request->input('Page', []);

            // Sanitize pagecode — strip special characters
            $pageData['pagecode'] = preg_replace(
                '/[~!@#$%\^\*&?<>;:,.%"()_+= "{}\[\]]/',
                '-',
                $pageData['pagecode'] ?? ''
            );

            $changeLang = $request->input('User.change_lang');
            if (!empty($changeLang) && !empty($id)) {
                $existing = Page::where('id', $id)->where('lang_code', trim($changeLang))->first();
                if ($existing) {
                    Page::where('id', $id)->update($pageData);
                } else {
                    unset($pageData['id']);
                    $pageData['lang_code'] = $changeLang;
                    Page::create($pageData);
                }
            } elseif (!empty($id)) {
                Page::where('id', $id)->update($pageData);
            } else {
                Page::create($pageData);
            }

            return redirect('/admin/pages/index')->with('success', 'Record updated successfully');
        }

        $data = !empty($id) ? Page::find($id) : null;

        return view('admin.pages.add', compact('listTitle', 'id', 'data'));
    }

    // ─── admin_view ───────────────────────────────────────────────────────────
    public function admin_view(Request $request, $id)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if (empty($id) || !is_numeric($id)) {
            return redirect('/admin/content_pages/index')->with('error', 'Id is missing.');
        }

        $page = Page::find($id);
        if ($page) {
            foreach (['title', 'description'] as $field) {
                $page->$field = html_entity_decode(str_replace(["&#039;", "\n"], ["'", ''], $page->$field ?? ''));
            }
        }

        return view('admin.pages.view', ['list_title' => 'View static page', 'data' => $page]);
    }

    // ─── admin_status ─────────────────────────────────────────────────────────
    public function admin_status(Request $request, $id, $status = 0)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        Page::where('id', $id)->update(['status' => $status == 1 ? 0 : 1]);

        $keyword  = $request->query('keyword', '');
        $searchin = $request->query('searchin', '');
        $showtype = $request->query('showtype', '');

        return redirect("/admin/pages/index?keyword={$keyword}&searchin={$searchin}&showtype={$showtype}")
            ->with('success', 'Record updated successfully');
    }

    // ─── admin_delete ─────────────────────────────────────────────────────────
    public function admin_delete(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $deleted = Page::where('id', $id)->delete();

        return redirect('/admin/pages')->with(
            $deleted ? 'success' : 'error',
            $deleted ? 'Record deleted successfully' : 'Information not deleted.'
        );
    }

    // ─── admin_multiplAction ─────────────────────────────────────────────────
    public function admin_multiplAction(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $submit = $request->input('Pages.submit');
        $ids    = array_filter($request->input('select', []));

        foreach ($ids as $id) {
            if ($submit === 'active') {
                Page::where('id', $id)->update(['status' => 1]);
            } elseif ($submit === 'inactive') {
                Page::where('id', $id)->update(['status' => 0]);
            } elseif ($submit === 'del') {
                Page::where('id', $id)->delete();
            }
        }

        $keyword  = $request->input('Search.keyword', '');
        $searchin = $request->input('Search.searchin', '');
        $showtype = $request->input('Search.show', '');

        return redirect("/admin/pages/index?keyword={$keyword}&searchin={$searchin}&showtype={$showtype}");
    }
}
