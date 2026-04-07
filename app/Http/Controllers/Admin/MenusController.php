<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenusController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function admin_reload(Request $request)
    {
        return $this->admin_index($request);
    }

    // ─── admin_index (Menu Manager) ──────────────────────────────────────────
    public function admin_index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $this->layout = 'admin';
        $this->set('listTitle', 'Menu Manager');

        $modules = AdminModule::orderBy('order')->get();
        $menuTree = $this->buildMenuTree($modules);

        $menus = AdminModule::pluck('module', 'id');

        return view('admin.menus.index', compact('menuTree', 'menus'));
    }

    // ─── admin_saveNewMenu (AJAX) ────────────────────────────────────────────
    public function admin_saveNewMenu(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $data = $request->input('AdminModule', []);
        
        if (empty($data['id'])) {
            $maxOrder = AdminModule::max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
        }

        if (empty($data['parent_id'])) $data['parent_id'] = 0;
        if (empty($data['module_url'])) $data['module_url'] = null;

        AdminModule::updateOrCreate(['id' => $data['id'] ?? null], $data);

        return response()->json(['status' => true, 'message' => 'Menu saved successfully.']);
    }

    // ─── admin_updateOrder (AJAX) ───────────────────────────────────────────
    public function admin_updateOrder(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $orderData = json_decode($request->input('_order'), true);
        $this->menuOrder = 1;
        $this->saveMenuOrderInternal(0, $orderData);

        return response()->json(['status' => true, 'message' => 'Menu order updated.']);
    }

    private int $menuOrder = 1;

    private function saveMenuOrderInternal(int $parentId, array $menuItems)
    {
        foreach ($menuItems as $item) {
            AdminModule::where('id', $item['id'])->update([
                'order'     => $this->menuOrder++,
                'parent_id' => $parentId
            ]);

            if (!empty($item['children'])) {
                $this->saveMenuOrderInternal($item['id'], $item['children']);
            }
        }
    }

    protected function saveMorder(int $parentId, array $menuItems)
    {
        $this->saveMenuOrderInternal($parentId, $menuItems);
    }

    protected function savemenuorder(int $parentId, array $menuItems)
    {
        $this->saveMenuOrderInternal($parentId, $menuItems);
    }

    // ─── admin_delete (AJAX/Post) ────────────────────────────────────────────
    public function admin_delete($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        AdminModule::where('id', $id)->delete();
        return response()->json(['status' => true, 'message' => 'Menu deleted.']);
    }

    // ─── admin_edit (AJAX) ───────────────────────────────────────────────────
    public function admin_edit($id)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $record = AdminModule::findOrFail($id);
        $menus  = AdminModule::pluck('module', 'id');

        return view('admin.menus.edit', compact('record', 'menus'));
    }

    // ─── Helper: Recursive tree builder ─────────────────────────────────────
    private function buildMenuTree($modules, $parentId = 0)
    {
        $branch = [];
        foreach ($modules as $module) {
            if ($module->parent_id == $parentId) {
                $children = $this->buildMenuTree($modules, $module->id);
                if ($children) {
                    $module->children = $children;
                }
                $branch[] = $module;
            }
        }
        return $branch;
    }
}
