<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Models\Legacy\PromoTerm;
use App\Models\Legacy\PromotionRule;
use App\Services\Legacy\PromoService;
use Illuminate\Http\Request;

class PromoRulesController extends LegacyAppController
{
    public function index(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Promotion Rules';
        $conditions = [];
        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));
        $show = $request->input('Search.show', $request->query('showtype', ''));
        $fieldname = $request->input('Search.searchin', $request->query('searchin', ''));

        $sessLimitName = 'promo_rules_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitName, 20);
        session([$sessLimitName => $limit]);

        $query = PromotionRule::query();

        if (!empty($keyword)) {
            $query->where('promo', 'LIKE', '%' . $keyword . '%');
        }

        if ($show !== '' && $show !== 'All') {
            $matchshow = ($show === 'Active') ? '1' : '0';
            $query->where('status', $matchshow);
        }

        $PromotionRules = $query->orderByDesc('created')
            ->paginate($limit)
            ->appends($request->query());

        $options = ['promo' => 'Coupon Code'];
        $showArr = ['Active' => 'Active', 'Deactive' => 'Deactive'];

        if ($request->ajax()) {
            return view('admin.promo_rules.elements.index', compact(
                'limit',
                'PromotionRules',
                'keyword',
                'show',
                'fieldname'
            ));
        }

        return view('admin.promo_rules.index', compact(
            'limit',
            'PromotionRules',
            'keyword',
            'show',
            'fieldname',
            'options',
            'showArr'
        ));
    }
    public function changeStatus($id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        PromotionRule::where('id', $id)->update(['status' => $status]);

        return redirect('/admin/promo_rules/index')
            ->with('success', 'Status has been changed for selected record');
    }
    public function delete($id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $allowed = PromotionRule::find($id);

        if (empty($allowed)) {
            return redirect('/admin/promo_rules/index')
                ->with('error', 'Sorry, you are not allowed to delete this promo record.');
        }

        $allowed->delete();

        return redirect('/admin/promo_rules/index')
            ->with('success', 'Selected record deleted successfully');
    }
    public function add(Request $request, $id = null)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $id = $this->decodeId($id);
        $listTitle = 'Add';
        $data = [];

        if ($request->isMethod('post')) {
            $dataToSave = $request->input('PromotionRule', []);
            $dataToSave['promo'] = strtoupper($dataToSave['promo'] ?? '');
            $conditions = $dataToSave['conditions'] ?? [];
            $dataToSave['conditions'] = !empty($conditions['con1']) ? json_encode($conditions) : null;
            unset($dataToSave['logo']);

            $existingId = $dataToSave['id'] ?? null;

            $existsCheck = PromotionRule::where('promo', $dataToSave['promo'])
                ->when($existingId, fn($q) => $q->where('id', '!=', $existingId))
                ->exists();

            if ($existsCheck) {
                return back()->withInput()
                    ->with('error', 'promo code already exists');
            }

            if (empty($dataToSave['promo']) || empty($dataToSave['type'])) {
                return back()->withInput()
                    ->with('error', 'Please enter promo code and choose promo type');
            }

            if ($existingId) {
                $promotionRule = PromotionRule::findOrFail($existingId);
                $promotionRule->update(collect($dataToSave)->except(['id'])->toArray());
                $savedId = $existingId;
            } else {
                $promotionRule = PromotionRule::create(collect($dataToSave)->except(['id'])->toArray());
                $savedId = $promotionRule->id;
            }

            if ($request->hasFile('PromotionRule.logo')) {
                $file = $request->file('PromotionRule.logo');
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                    $promoDir = public_path('img/promo');
                    if (!is_dir($promoDir)) {
                        @mkdir($promoDir, 0755, true);
                    }
                    $filename = 'promo_' . $savedId . '.' . $ext;
                    $file->move($promoDir, $filename);
                    PromotionRule::where('id', $savedId)->update(['logo' => $filename]);
                }
            }

            return redirect('/admin/promo_rules/index')
                ->with('success', 'Promotion Rule saved successfully.');
        }

        if (!empty($id)) {
            $promotionRule = PromotionRule::find($id);
            if ($promotionRule) {
                $data = $promotionRule->toArray();
                $data['conditions'] = !empty($data['conditions']) ? json_decode($data['conditions'], true) : [];
                $listTitle = 'Update';
            }
        }

        $promoService = new PromoService();
        $promoconditions = $promoService->promoconditions;
        $rules = $promoService->rules;

        return view('admin.promo_rules.add', compact('data', 'listTitle', 'promoconditions', 'rules'));
    }
    public function deletePromoterm(Request $request)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        if ($request->ajax()) {
            $promotermid = $request->input('promoid');
            PromoTerm::where('id', $promotermid)->delete();
            return response()->json(['status' => true, 'message' => 'Promo rule deleted for respective user']);
        }

        return response()->json(['status' => false, 'message' => 'Sorry, something went wrong']);
    }
    public function promousers(Request $request, $promo)
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }

        $title = 'Promotion Rule Users';
        $promoRuleId = $this->decodeId($promo);
        $keyword = $request->input('Search.keyword', $request->query('keyword', ''));

        $sessLimitName = 'promo_rules_limit';
        $limit = $request->input('Record.limit')
            ?: session($sessLimitName, 20);
        session([$sessLimitName => $limit]);

        $query = PromoTerm::with('user')
            ->where('promo_rule_id', $promoRuleId);

        if (!empty($keyword)) {
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('first_name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('email', 'LIKE', $keyword . '%')
                    ->orWhere('contact_number', 'LIKE', '%' . $keyword . '%');
            });
        }

        $PromoTerms = $query->orderByDesc('created')
            ->paginate($limit)
            ->appends($request->query());

        if ($request->ajax()) {
            return view('admin.promo_rules.elements.promousers', compact('PromoTerms', 'promo', 'keyword', 'limit'));
        }

        return view('admin.promo_rules.promousers', compact('title', 'PromoTerms', 'promo', 'keyword', 'limit'));
    }
}

