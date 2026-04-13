<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\UserCcsController as LegacyUserCcsController;
use App\Models\Legacy\UserCcToken;
use App\Models\Legacy\User;
use App\Services\Legacy\PaymentProcessor;
use Illuminate\Http\Request;

class UserCcsController extends LegacyUserCcsController
{
    public function admin_index($userid)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect('/admin/users/index');
        }

        $this->layout = 'admin';
        $UserCcTokens = UserCcToken::where('user_id', $userid)->orderBy('id', 'DESC')->get();
        $user = User::find($userid);
        $defaultcctoken = $user ? $user->cc_token_id : null;

        return view('admin.userccs.index', compact('UserCcTokens', 'userid', 'defaultcctoken'));
    }

    public function admin_status($id = null, $status = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        if (!empty($id)) {
            UserCcToken::where('id', $id)->update(['status' => $status]);
        }

        return redirect()->back()->with('success', "Record status has been changed.");
    }

    public function admin_delete($id = null, $userid)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $id = base64_decode($id);
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect('/admin/users/admin_index');
        }

        $isdefault = User::where('id', $userid)->where('cc_token_id', $id)->count();

        if ($isdefault) {
            return redirect()->back()->with('error', "Sorry, this is default CC record, this cant be deleted.");
        }

        $UserCcTokenObj = UserCcToken::where('id', $id)->where('user_id', $userid)->first();
        if (empty($UserCcTokenObj)) {
            return redirect()->back()->with('error', "Sorry, this CC record doesnt belong to selected user.");
        }

        /** @var PaymentProcessor $paymentProcessor */
        $paymentProcessor = app(PaymentProcessor::class);
        $paymentProcessor->deleteCustomerCard((string) $UserCcTokenObj->stripe_token, (string) $UserCcTokenObj->card_id);

        UserCcToken::where('id', $id)->delete();
        return redirect()->back()->with('success', "Record has been deleted successfully.");
    }

    public function admin_add(Request $request, $userid = null)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect('/admin/users/index');
        }

        $this->layout = 'admin';
        
        if ($request->isMethod('post')) {
            $dataInputs = $request->input('UserCcToken', []);
            $return = $this->_addCardLogic($userid, $dataInputs);

            if ($return['status']) {
                return redirect('/admin/user_ccs/admin_index/' . base64_encode($userid))->with('success', $return['message']);
            } else {
                return redirect()->back()->with('error', $return['message']);
            }
        }

        return view('admin.userccs.add', compact('userid'));
    }

    public function admin_makeccdefault($ccid, $userid)
    {
        if ($redirect = $this->ensureAdminSession()) return $redirect;

        $ccid = base64_decode($ccid);
        $userid = base64_decode($userid);
        if (empty($userid) || empty($ccid)) {
            return redirect('/admin/user_ccs/index');
        }

        $isassociatedwithUser = UserCcToken::where('id', $ccid)->where('user_id', $userid)->first();
        if (!empty($isassociatedwithUser)) {
            User::where('id', $userid)->update(['cc_token_id' => $ccid]);

            /** @var PaymentProcessor $paymentProcessor */
            $paymentProcessor = app(PaymentProcessor::class);
            $paymentProcessor->makeCardDefault((string) $isassociatedwithUser->stripe_token, (string) $isassociatedwithUser->card_id);

            return redirect()->back()->with('success', "CC record has been updated successfully.");
        } else {
            return redirect()->back()->with('error', "Sorry, this CC record doesnt belong to selected user.");
        }
    }
}
