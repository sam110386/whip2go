<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserCcsController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function index(Request $request, ?string $userid = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $uid = $this->decodeId($userid);
        if (!$uid) {
            return redirect('/admin/users/index');
        }

        $tokens = DB::table('user_cc_tokens')
            ->where('user_id', $uid)
            ->orderByDesc('id')
            ->get();

        $defaultCcTokenId = DB::table('users')
            ->where('id', $uid)
            ->value('cc_token_id');

        return view('admin.user_ccs.index', [
            'title_for_layout' => 'Manage User CC Details',
            'UserCcTokens' => $tokens,
            'userid' => $uid,
            'useridB64' => $this->encodeId($uid),
            'defaultcctoken' => $defaultCcTokenId !== null ? (int) $defaultCcTokenId : null,
        ]);
    }

    public function status(Request $request, ?string $id = null, ?string $status = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $tokenId = $this->decodeId($id);
        if ($tokenId) {
            DB::table('user_cc_tokens')
                ->where('id', $tokenId)
                ->update(['status' => ((string) $status === '1') ? 1 : 0]);
        }
        session()->flash('success', 'Record status has been changed.');

        return back();
    }

    public function delete(Request $request, ?string $id = null, ?string $userid = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $tokenId = $this->decodeId($id);
        $uid = $this->decodeId($userid);
        if (!$uid) {
            return redirect('/admin/users/index');
        }

        $isDefault = DB::table('users')
            ->where('id', $uid)
            ->where('cc_token_id', $tokenId)
            ->exists();
        if ($isDefault) {
            session()->flash('error', 'Sorry, this is default CC record, this cant be deleted.');

            return back();
        }

        $row = DB::table('user_cc_tokens')
            ->where('id', $tokenId)
            ->where('user_id', $uid)
            ->first();
        if ($row === null) {
            session()->flash('error', 'Sorry, this CC record doesnt belong to selected user');

            return back();
        }

        $processor = new PaymentProcessor();
        $return = $processor->deleteCustomerCard($row->stripe_token ?? null, $row->card_id ?? null);
        if (($return['status'] ?? '') === 'success') {
            DB::table('user_cc_tokens')->where('id', $tokenId)->delete();
            session()->flash('success', 'Record has been deleted succesfully');
        } else {
            session()->flash('error', $return['message'] ?? 'Payment processing not yet ported to Laravel');
        }

        return back();
    }

    public function add(Request $request, ?string $userid = null): View|RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $uid = $this->decodeId($userid);
        if (!$uid) {
            return redirect('/admin/users/index');
        }

        $user = DB::table('users')->where('id', $uid)->first();

        if ($request->isMethod('post')) {
            $input = $request->input('UserCcToken', []);
            if (!is_array($input)) {
                $input = [];
            }
            $input['user_id'] = $uid;
            $input['status'] = 1;
            $dataValues = json_decode(json_encode($input));

            $existing = DB::table('user_cc_tokens')
                ->where('user_id', $uid)
                ->orderBy('id')
                ->first();

            $processor = new PaymentProcessor();
            $return = $processor->addNewCard(
                $dataValues,
                $existing && !empty($existing->stripe_token) ? (string) $existing->stripe_token : ''
            );

            if (($return['status'] ?? '') === 'success') {
                $ccDigits = preg_replace('/\D/', '', (string) ($dataValues->credit_card_number ?? ''));
                $last4 = $ccDigits !== '' ? substr($ccDigits, -4) : substr((string) ($dataValues->credit_card_number ?? ''), -4);

                $ccid = DB::table('user_cc_tokens')->insertGetId([
                    'user_id' => $uid,
                    'card_type' => $dataValues->card_type ?? null,
                    'credit_card_number' => $last4,
                    'card_holder_name' => $dataValues->card_holder_name ?? null,
                    'expiration' => $dataValues->expiration ?? null,
                    'card_funding' => $return['card_funding'] ?? '',
                    'cvv' => $input['cvv'] ?? '',
                    'address' => $input['address'] ?? null,
                    'city' => $input['city'] ?? null,
                    'state' => $input['state'] ?? null,
                    'zip' => $input['zip'] ?? null,
                    'country' => 'US',
                    'stripe_token' => $return['stripe_token'] ?? '',
                    'card_id' => $return['card_id'] ?? '',
                    'status' => 1,
                    'created' => now(),
                ]);

                $cctokenid = DB::table('users')->where('id', $uid)->value('cc_token_id');
                $defaultChecked = $request->has('UserCcToken.default');
                if (empty($cctokenid) || $defaultChecked) {
                    DB::table('users')->where('id', $uid)->update([
                        'cc_token_id' => $ccid,
                        'is_renter' => 1,
                    ]);
                }
                if ($existing !== null && $defaultChecked && !empty($return['card_id'] ?? '')) {
                    $processor->makeCardDefault((string) $existing->stripe_token, (string) ($return['card_id'] ?? ''));
                }
                session()->flash('success', 'Card has been added successfully.');

                return redirect('/admin/user_ccs/index/' . $this->encodeId($uid));
            }
            session()->flash('error', $return['message'] ?? 'Payment processing not yet ported to Laravel');

            return back()->withInput();
        }

        return view('admin.user_ccs.add', [
            'listTitle' => 'Add CC Details',
            'userid' => $uid,
            'useridB64' => $this->encodeId($uid),
            'user' => $user,
        ]);
    }

    public function makeccdefault(Request $request, ?string $ccid = null, ?string $userid = null): RedirectResponse
    {
        if ($redirect = $this->ensureAdminSession()) {
            return $redirect;
        }
        $tokenId = $this->decodeId($ccid);
        $uid = $this->decodeId($userid);
        if (!$uid || !$tokenId) {
            return redirect('/admin/users/index');
        }

        $token = DB::table('user_cc_tokens')
            ->where('id', $tokenId)
            ->where('user_id', $uid)
            ->first();
        if ($token !== null) {
            DB::table('users')->where('id', $uid)->update(['cc_token_id' => $tokenId]);
            if (!empty($token->stripe_token) && !empty($token->card_id)) {
                (new PaymentProcessor())->makeCardDefault((string) $token->stripe_token, (string) $token->card_id);
            }
            session()->flash('success', 'CC record has been updated succesfully');
        } else {
            session()->flash('error', 'Sorry, this CC record doesnt belong to selected user');
        }

        return back();
    }
}
