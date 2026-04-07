<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Controller;
use App\Models\Legacy\CsTwilioLog;
use App\Models\Legacy\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SmslogsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $searchKey = $request->input('searchKey');

        $query = CsTwilioLog::where('cs_twilio_logs.user_id', $userId)
            ->leftJoin('users as User', 'User.username', '=', 'cs_twilio_logs.renter_phone')
            ->select('cs_twilio_logs.*', 'User.id as renter_id', 'User.first_name', 'User.last_name', 'User.photo')
            ->groupBy('cs_twilio_logs.renter_phone');

        if (!empty($searchKey)) {
            $query->where('cs_twilio_logs.renter_phone', 'LIKE', '%' . $searchKey . '%');
        }

        $TwilioLogs = $query->orderBy('cs_twilio_logs.id', 'DESC')->paginate(25);

        // Fetch last message for each conversation
        foreach ($TwilioLogs as $log) {
            $lastMsg = CsTwilioLog::where('renter_phone', $log->renter_phone)
                ->where('user_id', $log->user_id)
                ->orderBy('id', 'DESC')
                ->value('msg');
            $log->msg = $lastMsg;
        }

        if ($request->ajax()) {
            return view('legacy.elements.smslogs.userlist', compact('TwilioLogs'));
        }

        return view('legacy.smslogs.index', compact('TwilioLogs'));
    }

    public function loadchat(Request $request)
    {
        $userId = Session::get('userParentId') ?: Session::get('userid');
        $phone = $request->input('phone');
        $renterid = $request->input('userid');

        $CsTwilioLogs = CsTwilioLog::where('user_id', $userId)
            ->where('renter_phone', $phone)
            ->orderBy('id', 'DESC')
            ->limit(30)
            ->get()
            ->reverse();

        $renter = User::where('id', $renterid)->select('id', 'first_name', 'last_name', 'photo')->first();

        return view('legacy.elements.smslogs.chatwindow', compact('CsTwilioLogs', 'renter', 'phone'));
    }

    public function sendmessage(Request $request)
    {
        // Placeholder for Twilio::autonotifyByTwilio
        return response()->json(['status' => false, 'message' => "SMS sending pending Lib migration."]);
    }
}
