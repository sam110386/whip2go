<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsuranceProvidersController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $keyword = '';
        $query = DB::table('insurance_providers as InsuranceProvider')
            ->select('InsuranceProvider.*');

        if ($request->has('Search') || $request->route('keyword')) {
            $keyword = $request->input('Search.keyword', $request->route('keyword', ''));
            $value = trim($keyword);
            if ($value !== '') {
                $query->where('InsuranceProvider.name', 'LIKE', "%{$value}%");
            }
        }

        $sessKey = 'insurance_providers_limit';
        $limit = $request->input('Record.limit', session($sessKey, 20));
        session([$sessKey => $limit]);

        // Handle sorting
        $sort = $request->input('sort', 'id');
        $direction = $request->input('direction', 'desc');
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        // Allowed sortable columns
        $allowedSort = ['id', 'name', 'address', 'city', 'state', 'country', 'status'];
        if (!in_array($sort, $allowedSort)) {
            $sort = 'id';
        }

        $records = $query->orderBy("InsuranceProvider.{$sort}", $direction)->paginate($limit);

        $data = compact('keyword', 'records', 'limit', 'sort', 'direction');
        $data['title_for_layout'] = 'Insurance Providers';

        if ($request->ajax()) {
            return view('admin.insurance_provider.elements.admin_index', $data);
        }

        return view('admin.insurance_provider.insurance_providers.index', $data);
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $listTitle = !empty($id) ? 'Update Insurance Provider' : 'Add Insurance Provider';

        if ($request->isMethod('post')) {
            $data = $request->input('InsuranceProvider', []);
            unset($data['logo']);

            if ($request->hasFile('InsuranceProvider.logo')) {
                $uploadData = $request->file('InsuranceProvider.logo');
                if ($uploadData->isValid()) {
                    $ext = $uploadData->getClientOriginalExtension();
                    $filename = time() . '.' . $ext;
                    $uploadFolder = public_path('img/insurance_providers');
                    if (!file_exists($uploadFolder)) {
                        @mkdir($uploadFolder, 0755, true);
                    }
                    $uploadData->move($uploadFolder, $filename);
                    $data['logo'] = $filename;
                }
            }

            try {
                if (!empty($data['id'])) {
                    DB::table('insurance_providers')->where('id', $data['id'])->update($data);
                    return redirect('/admin/insurance_providers')
                        ->with('success', 'Insurance Provider is updated successfully.');
                } else {
                    unset($data['id']);
                    DB::table('insurance_providers')->insert($data);
                    return redirect('/admin/insurance_providers')
                        ->with('success', 'Insurance Provider is added successfully.');
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $record = [];
        if (!empty($id)) {
            $record = DB::table('insurance_providers')->where('id', $id)->first();
            if (empty($record)) {
                return redirect('/admin/insurance_providers')
                    ->with('error', 'Sorry, you are not authorized user for this action');
            }
            $record = (array) $record;
        }

        return view('admin.insurance_provider.insurance_providers.add', [
            'listTitle' => $listTitle,
            'record'    => $record,
        ]);
    }

    public function status($id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        if (!empty($id)) {
            DB::table('insurance_providers')->where('id', $id)->update([
                'status' => ($status == 1) ? 1 : 0,
            ]);
        }

        return redirect()->back()->with('success', 'Record status has been changed.');
    }
}
