<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\Page as LegacyPage;
use Illuminate\Http\Request;

class PagesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $searchIn = trim((string)($request->input('Search.searchin', $request->query('searchin', ''))));
        $keyword = trim((string)($request->input('Search.keyword', $request->query('keyword', ''))));

        $q = LegacyPage::query()->orderBy('id', 'asc');
        if ($keyword !== '') {
            $like = '%' . $keyword . '%';
            if ($searchIn === 'title' || $searchIn === 'description') {
                $q->where($searchIn, 'like', $like);
            } else {
                $q->where(function ($qq) use ($like) {
                    $qq->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like);
                });
            }
        }

        $pages = $q->limit(100)->get();

        return view('admin.pages.index', [
            'listTitle' => 'Manage Static Pages',
            'pages' => $pages,
            'keyword' => $keyword,
            'fieldname' => $searchIn,
            'options' => ['title' => 'Title', 'description' => 'Description'],
        ]);
    }

    public function add(Request $request, $id = null)
    {
        $page = (is_numeric($id) && (int)$id > 0) ? LegacyPage::query()->find((int)$id) : null;

        if (!$request->isMethod('POST')) {
            return view('admin.pages.add', [
                'listTitle' => $page ? 'Update Content Page' : 'Add Content Page',
                'page' => $page,
            ]);
        }

        $payload = $request->input('Page', []);
        $title = trim((string)($payload['title'] ?? ''));
        if ($title === '') {
            return back()->withInput()->with('error', 'Title is required.');
        }

        $data = [
            'title' => $title,
            'description' => (string)($payload['description'] ?? ''),
            'meta_title' => (string)($payload['meta_title'] ?? ''),
            'meta_description' => (string)($payload['meta_description'] ?? ''),
            'meta_keyword' => (string)($payload['meta_keyword'] ?? ''),
            'status' => isset($payload['status']) ? (int)$payload['status'] : 1,
            'pagecode' => isset($payload['pagecode']) ? preg_replace('/[^a-zA-Z0-9\-]+/', '-', (string)$payload['pagecode']) : null,
            'pagegroup' => $payload['pagegroup'] ?? null,
            'sequence' => isset($payload['sequence']) ? (int)$payload['sequence'] : null,
            'lang_code' => $payload['lang_code'] ?? null,
        ];

        if ($page) {
            LegacyPage::query()->whereKey((int)$page->id)->update($data);
        } else {
            LegacyPage::query()->create($data);
        }

        return redirect('/admin/pages/index');
    }

    public function view(Request $request, $id = null)
    {
        if (empty($id) || !is_numeric($id)) {
            return redirect('/admin/pages/index');
        }
        $page = LegacyPage::query()->find((int)$id);
        if (!$page) {
            return redirect('/admin/pages/index');
        }

        return view('admin.pages.view', [
            'listTitle' => 'View static page',
            'page' => $page,
        ]);
    }

    public function status(Request $request, $id = null, $status = 0)
    {
        if (!empty($id) && is_numeric($id)) {
            LegacyPage::query()->whereKey((int)$id)->update(['status' => ((string)$status === '1') ? 0 : 1]);
        }
        return redirect('/admin/pages/index');
    }

    public function delete(Request $request, $id = null)
    {
        if (!empty($id) && is_numeric($id)) {
            LegacyPage::query()->whereKey((int)$id)->delete();
        }
        return redirect('/admin/pages/index');
    }

    public function multiplAction(Request $request)
    {
        $action = (string)$request->input('Pages.submit', '');
        $selected = $request->input('select', []);
        if (!is_array($selected)) {
            $selected = [];
        }
        $ids = array_values(array_filter(array_map('intval', array_keys(array_filter($selected)))));

        if (empty($ids)) {
            return redirect('/admin/pages/index');
        }

        if ($action === 'active') {
            LegacyPage::query()->whereIn('id', $ids)->update(['status' => 1]);
        } elseif ($action === 'inactive') {
            LegacyPage::query()->whereIn('id', $ids)->update(['status' => 0]);
        } elseif ($action === 'del') {
            LegacyPage::query()->whereIn('id', $ids)->delete();
        }

        return redirect('/admin/pages/index');
    }
}

