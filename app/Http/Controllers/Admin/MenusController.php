<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\AdminModule;
use Illuminate\Http\Request;

class MenusController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request)
    {
        $menu = $this->getThreadedMenu();
        $menus = AdminModule::query()
            ->where('status', 1)
            ->orderBy('module')
            ->pluck('module', 'id')
            ->toArray();

        return view('admin.menus.index', [
            'listTitle' => 'Menu Manager',
            'menu' => $menu,
            'menus' => $menus,
        ]);
    }

    public function reload(Request $request)
    {
        $menu = $this->getThreadedMenu();
        return view('admin.menus._menu_tree', ['nodes' => $menu]);
    }

    public function edit(Request $request, $id)
    {
        $decodedId = $this->decodeId($id);
        if (!$decodedId) {
            return redirect('/admin/menus/index');
        }

        $module = AdminModule::query()->find($decodedId);
        if (!$module) {
            return redirect('/admin/menus/index');
        }

        $menus = AdminModule::query()
            ->where('status', 1)
            ->orderBy('module')
            ->pluck('module', 'id')
            ->toArray();

        return view('admin.menus.add', [
            'module' => $module,
            'menus' => $menus,
        ]);
    }

    public function delete(Request $request, $id)
    {
        $decodedId = $this->decodeId($id);
        if ($decodedId) {
            AdminModule::query()->whereKey($decodedId)->delete();
        }

        return response()->noContent();
    }

    public function updateOrder(Request $request)
    {
        $return = ['status' => true];

        $raw = $request->input('_order');
        $items = is_string($raw) ? json_decode($raw, true) : $raw;

        if (!is_array($items)) {
            return response()->json(['status' => false, 'message' => 'Invalid order payload']);
        }

        $menuOrder = 1;
        $this->saveMenuOrder(0, $items, $menuOrder);

        return response()->json($return);
    }

    public function saveNewMenu(Request $request)
    {
        $return = ['status' => false, 'message' => 'Sorry, something went wrong.'];

        $data = $request->input('data.AdminModule');
        if (!is_array($data) || empty($data)) {
            $data = $request->input('AdminModule', []);
        }
        if (!is_array($data) || empty($data)) {
            return response()->json($return);
        }

        $moduleName = trim((string)($data['module'] ?? ''));
        $moduleUrl = $data['module_url'] ?? null;
        $moduleUrl = $moduleUrl === null ? null : trim((string)$moduleUrl);
        $htmlId = trim((string)($data['html_id'] ?? ''));
        $icon = trim((string)($data['icon'] ?? ''));
        $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
        $id = isset($data['id']) ? (int)$data['id'] : 0;

        if ($moduleName === '' || $moduleUrl === '' || $htmlId === '' || $icon === '') {
            return response()->json(['status' => false, 'message' => 'Please fill required fields.']);
        }

        $payload = [
            'module' => $moduleName,
            'module_url' => $moduleUrl,
            'html_id' => $htmlId,
            'icon' => $icon,
            'parent_id' => $parentId ?: 0,
        ];

        try {
            if (empty($id)) {
                $maxOrder = (int)(AdminModule::query()->max('order') ?? 0);
                $payload['order'] = $maxOrder + 1;
                $payload['status'] = 1;
                AdminModule::query()->create($payload);
            } else {
                AdminModule::query()->whereKey($id)->update($payload);
            }
        } catch (\Throwable $e) {
            return response()->json($return);
        }

        return response()->json(['status' => true, 'message' => 'Menu saved successfully']);
    }

    /**
     * Persist Nestable output into `admin_modules.order` + `parent_id`.
     *
     * Nestable serializes nodes as: [{id: 1, children: [{id: 2, children: []}]}]
     */
    private function saveMenuOrder(int $parentId, array $items, int &$menuOrder): void
    {
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $id = (int)$item['id'];
            AdminModule::query()->whereKey($id)->update([
                'order' => $menuOrder,
                'parent_id' => $parentId,
            ]);
            $menuOrder++;

            $children = $item['children'] ?? [];
            if (is_array($children) && !empty($children)) {
                $this->saveMenuOrder($id, $children, $menuOrder);
            }
        }
    }

    private function getThreadedMenu(): array
    {
        $rows = AdminModule::query()
            ->where('status', 1)
            ->orderBy('order')
            ->get();

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[(int)($row->parent_id ?? 0)][] = $row;
        }

        return $this->buildThreadedFromParent(0, $byParent);
    }

    private function buildThreadedFromParent(int $parentId, array $byParent): array
    {
        $children = $byParent[$parentId] ?? [];
        $nodes = [];

        foreach ($children as $row) {
            $node = [
                'AdminModule' => $row->toArray(),
            ];

            $childNodes = $this->buildThreadedFromParent((int)$row->id, $byParent);
            if (!empty($childNodes)) {
                $node['children'] = $childNodes;
            }

            $nodes[] = $node;
        }

        return $nodes;
    }
}

