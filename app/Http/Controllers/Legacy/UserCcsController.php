<?php

namespace App\Http\Controllers\Legacy;

use App\Models\Legacy\UserCcToken;
use App\Models\Legacy\User;
use App\Http\Controllers\Traits\UserCcsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserCcsController extends LegacyAppController
{
    use UserCcsTrait;

    public function index($userid)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect('/users/index');
        }

        $this->layout = 'main';
        $UserCcTokens = UserCcToken::where('user_id', $userid)->orderBy('id', 'DESC')->get();

        return view('legacy.userccs.index', compact('UserCcTokens', 'userid'));
    }

    public function delete($id = null, $userid)
    {
        $id = base64_decode($id);
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect('/users/index');
        }

        $isdefault = User::where('id', $userid)->where('cc_token_id', $id)->count();

        if (!$isdefault) {
            UserCcToken::where('id', $id)->delete();
            return redirect()->back()->with('success', "Record has been deleted successfully.");
        } else {
            return redirect()->back()->with('error', "Sorry, this is default CC record, this cant be deleted.");
        }
    }

    public function add(Request $request, $userid = null)
    {
        $userid = base64_decode($userid);
        if (empty($userid)) {
            return redirect('/users/index');
        }

        $this->layout = 'main';
        
        if ($request->isMethod('post')) {
            $dataInputs = $request->input('UserCcToken', []);
            $return = $this->_addCardLogic($userid, $dataInputs);

            if ($return['status']) {
                return redirect('/user_ccs/index/' . base64_encode($userid))->with('success', $return['message']);
            } else {
                return redirect()->back()->with('error', $return['message']);
            }
        }

        return view('legacy.userccs.add', compact('userid'));
    }
}
