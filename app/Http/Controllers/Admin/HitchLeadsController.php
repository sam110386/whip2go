<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HitchLeadsController extends LegacyAppController
{
    protected int $recordsPerPage = 25;

    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $keyword = '';
        $conditions = [];

        if ($request->has('Search') || $request->filled('keyword')) {
            $keyword = $request->input('Search.keyword', $request->input('keyword', ''));
            $escaped = addcslashes($keyword, '%_\\');
            if ($escaped !== '') {
                $conditions['keyword'] = $escaped;
            }
        }

        $sessLimitKey = 'hitch_leads_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitKey, $this->recordsPerPage);
        session([$sessLimitKey => $limit]);

        $query = DB::table('hitch_leads as HitchLead')
            ->select('HitchLead.*')
            ->orderByDesc('HitchLead.id');

        if (!empty($conditions['keyword'])) {
            $like = '%' . $conditions['keyword'] . '%';
            $query->where(function ($q) use ($like) {
                $q->where('HitchLead.first_name', 'like', $like)
                    ->orWhere('HitchLead.last_name', 'like', $like)
                    ->orWhere('HitchLead.email', 'like', $like)
                    ->orWhere('HitchLead.phone', 'like', $like);
            });
        }

        $leads = $query->paginate($limit)->withQueryString();

        if ($request->ajax()) {
            return response()->view('admin.hitch.leads._table', [
                'leads' => $leads,
                'keyword' => $keyword,
            ]);
        }

        return view('admin.hitch.leads.index', [
            'leads' => $leads,
            'keyword' => $keyword,
            'title_for_layout' => 'Customers',
        ]);
    }

    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        $listTitle = $decodedId ? 'Update Lead' : 'Add New Lead';
        $lead = null;

        if ($request->isMethod('post')) {
            $data = $request->input('HitchLead', []);
            $data['phone'] = substr(preg_replace('/[^0-9]/', '', $data['phone'] ?? ''), -10);

            $validator = Validator::make($data, [
                'phone' => 'required|unique:hitch_leads,phone,' . ($data['id'] ?? 'NULL'),
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => 'required|email',
            ], [
                'phone.required' => 'Please enter phone#',
                'phone.unique' => 'Phone # already exists',
                'first_name.required' => 'Please enter your first name',
                'last_name.required' => 'Please enter last name',
                'email.required' => 'Please enter your email.',
                'email.email' => 'Please enter valid email address',
            ]);

            if ($validator->fails()) {
                return view('admin.hitch.leads.add', [
                    'listTitle' => $listTitle,
                    'lead' => (object) $data,
                    'errors' => $validator->errors(),
                    'title_for_layout' => $listTitle,
                ]);
            }

            try {
                if (!empty($data['id'])) {
                    DB::table('hitch_leads')->where('id', $data['id'])->update([
                        'dealer_id' => $data['dealer_id'] ?? null,
                        'phone' => $data['phone'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'] ?? '',
                        'payroll' => $data['payroll'] ?? 0,
                        'updated' => now(),
                    ]);
                    session()->flash('success', 'Lead has been updated successfully.');
                } else {
                    DB::table('hitch_leads')->insert([
                        'dealer_id' => $data['dealer_id'] ?? null,
                        'phone' => $data['phone'],
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                        'email' => $data['email'] ?? '',
                        'payroll' => $data['payroll'] ?? 0,
                        'created' => now(),
                        'updated' => now(),
                    ]);
                    session()->flash('success', 'Lead has been added successfully.');
                }

                return redirect('/admin/hitch/leads/index');
            } catch (\Exception $e) {
                session()->flash('error', $e->getMessage());
            }
        }

        if ($decodedId) {
            $lead = DB::table('hitch_leads')->where('id', $decodedId)->first();
            if (empty($lead)) {
                session()->flash('error', 'Sorry, you are not authorized user for this action');
                return redirect('/admin/hitch/leads/index');
            }
        }

        return view('admin.hitch.leads.add', [
            'listTitle' => $listTitle,
            'lead' => $lead,
            'title_for_layout' => $listTitle,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $leadid = $request->input('leadid');
        $lead = DB::table('hitch_leads')
            ->where('id', $leadid)
            ->where('status', '!=', 3)
            ->first();

        if (empty($lead)) {
            return response()->json(['status' => false, 'message' => 'Sorry, lead record not found or already approved.']);
        }

        $phone = substr(preg_replace('/[^0-9]/', '', $lead->phone), -10);
        $users = DB::table('users')
            ->where(function ($q) use ($phone) {
                $q->where('username', $phone)
                    ->orWhere('contact_number', 'like', '%' . $phone . '%');
            })
            ->get();

        if ($users->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Sorry, no registered user found with respective phone#.']);
        }

        return response()->json(['status' => true, 'message' => 'User found']);
    }

    public function delete($id = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $decodedId = $this->decodeId($id);
        if ($decodedId) {
            DB::table('hitch_leads')->where('id', $decodedId)->delete();
        }

        session()->flash('success', 'Record has been deleted, succesfully');
        return redirect()->back();
    }

    public function import(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->isMethod('post')) {
            $file = $request->file('file');
            if (!$file || !in_array(strtolower($file->getClientOriginalExtension()), ['csv'])) {
                session()->flash('error', 'Invalid File Formats. Please use only .csv file');
                return redirect()->back();
            }

            $newfilename = time() . '.csv';
            $storagePath = storage_path('app/import_leads');
            if (!is_dir($storagePath)) {
                mkdir($storagePath, 0755, true);
            }

            $allowedsize = $this->returnFileSizeBytes(ini_get('upload_max_filesize'));
            if (empty($file->getSize()) || $file->getSize() >= $allowedsize) {
                session()->flash('error', 'You are trying to upload a large size file. Please try to upload only max to max ' . ini_get('upload_max_filesize'));
                return redirect()->back();
            }

            $file->move($storagePath, $newfilename);
            $filepath = $storagePath . '/' . $newfilename;

            if (!mb_check_encoding(file_get_contents($filepath), 'UTF-8')) {
                @unlink($filepath);
                session()->flash('error', 'Invalid File. File data must be in UTF-8 encode.');
                return redirect()->back();
            }

            $count = 0;
            if (($handle = fopen($filepath, 'r')) !== false) {
                while (fgetcsv($handle, 1000, ',') !== false) {
                    $count++;
                }
                fclose($handle);
            }

            if ($count) {
                $dealerid = $request->input('dealer_id', '');
                $skip = $request->input('skip', false);
                return redirect("/admin/hitch/leads/processimport/{$count}/{$newfilename}/{$dealerid}/{$skip}");
            }

            @unlink($filepath);
            session()->flash('error', 'Invalid File. File contains no data.');
            return redirect()->back();
        }

        return view('admin.hitch.leads.import', [
            'title_for_layout' => 'Bulk Lead Import',
        ]);
    }

    public function processimport(Request $request, $count, $filename, $dealerid, $skip = false)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $storagePath = storage_path('app/import_leads');

        if ($request->isMethod('post')) {
            $j = 0;
            $filepath = $storagePath . '/' . $filename;
            if (($handle = fopen($filepath, 'r')) !== false) {
                while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                    if ($skip && $j === 0) {
                        $j++;
                        continue;
                    }

                    $dataToSave = [
                        'dealer_id' => $dealerid,
                        'phone' => $data[0] ?? '',
                        'first_name' => $data[1] ?? '',
                        'last_name' => $data[2] ?? '',
                        'email' => $data[3] ?? '',
                        'payroll' => $data[4] ?? 0,
                    ];

                    $validator = Validator::make($dataToSave, [
                        'phone' => 'required|unique:hitch_leads,phone',
                        'first_name' => 'required',
                        'last_name' => 'required',
                        'email' => 'required|email',
                    ]);

                    if ($validator->passes()) {
                        $dataToSave['created'] = now();
                        $dataToSave['updated'] = now();
                        DB::table('hitch_leads')->insert($dataToSave);
                    }

                    $j++;
                }
                fclose($handle);
                session()->flash('success', 'File data imported successfully');
            } else {
                session()->flash('error', 'Invalid File. File contains no data.');
            }

            return redirect('/admin/hitch/leads/index');
        }

        $j = 0;
        $previewData = [];
        $filepath = $storagePath . '/' . $filename;
        if (($handle = fopen($filepath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($skip && $j === 0) {
                    $j++;
                    continue;
                }
                if ($j >= 0 && $j < 15) {
                    $previewData[] = $data;
                }
                $j++;
            }
            fclose($handle);
        }

        return view('admin.hitch.leads.processimport', [
            'count' => $count,
            'filename' => $filename,
            'previewData' => $previewData,
            'skip' => $skip,
            'dealerid' => $dealerid,
            'title_for_layout' => 'Process Lead Import',
        ]);
    }

    private function returnFileSizeBytes($sizeStr): int
    {
        $suffix = strtoupper(substr($sizeStr, -1));
        $val = (int) $sizeStr;
        return match ($suffix) {
            'M' => $val * 1048576,
            'K' => $val * 1024,
            'G' => $val * 1073741824,
            default => $val,
        };
    }
}
