<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\Common;
use Illuminate\Http\Request;
use App\Models\Legacy\InsuranceProvider;

class InsuranceProvidersController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $keyword = '';
        $query = InsuranceProvider::query();

        if ($request->has('Search') || $request->route('keyword')) {
            $keyword = $request->input('Search.keyword', $request->route('keyword', ''));
            $value = trim($keyword);
            if ($value !== '') {
                $query->where('name', 'LIKE', "%{$value}%");
            }
        }

        $sessKey = 'insurance_providers_limit';
        $limit = $request->input('Record.limit', session($sessKey, 20));
        session([$sessKey => $limit]);

        $sort = $request->input('sort', 'id');
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'name', 'city'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'id';
        }

        $records = $query->orderBy($sort, $direction)->paginate($limit);

        $data = compact('keyword', 'records', 'limit', 'sort', 'direction');
        $data['title_for_layout'] = 'Insurance Providers';

        if ($request->ajax()) {
            return view('admin.insurance_provider.elements.index', $data);
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

        $common = app(Common::class);
        $usStates = $common->getStates();
        $caStates = $common->getCanadaStates();

        if ($request->isMethod('post')) {
            $data = $request->input('InsuranceProvider', []);

            $rules = [
                'InsuranceProvider.name' => 'required|unique:insurance_providers,name' . (!empty($data['id']) ? ',' . $data['id'] : ''),
            ];
            $messages = [
                'InsuranceProvider.name.required' => 'Please enter lender name',
                'InsuranceProvider.name.unique' => 'Lender name already exists',
            ];
            $request->validate($rules, $messages);

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

            if (isset($data['country']) && $data['country'] === 'CA' && isset($data['castate'])) {
                $data['state'] = $data['castate'];
            }
            unset($data['castate']);

            try {
                if (!empty($data['id'])) {
                    InsuranceProvider::where('id', $data['id'])->update($data);

                    return redirect('/admin/insurance_providers/index')
                        ->with('success', 'Insurance Provider is updated successfully.');
                } else {
                    unset($data['id']);
                    InsuranceProvider::create($data);
                    return redirect('/admin/insurance_providers/index')
                        ->with('success', 'Insurance Provider is added successfully.');
                }
            } catch (\Exception $e) {
                return redirect()->back()->with('error', $e->getMessage());
            }
        }

        $record = [];
        if (!empty($id)) {
            $record = InsuranceProvider::where('id', $id)->first();
            if (empty($record)) {
                return redirect('/admin/insurance_providers/index')
                    ->with('error', 'Sorry, you are not authorized user for this action');
            }
            // $record = $record->toArray();
        }

        return view('admin.insurance_provider.insurance_providers.add', [
            'listTitle' => $listTitle,
            'record' => $record,
            'usStates' => $usStates,
            'caStates' => $caStates
        ]);
    }

    public function status($id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        if (!empty($id)) {
            InsuranceProvider::where('id', $id)->update([
                'status' => ($status == 1) ? 1 : 0,
            ]);
        }

        return redirect()->back()->with('success', 'Record status has been changed.');
    }
}
