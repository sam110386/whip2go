<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageHistoriesController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = true;

    public function loadmessagehistory(Request $request)
    {
        $renterId = (int)$request->input('renter_id', 0);
        $ownerId = (int)$request->input('owner_id', 0);
        if ($renterId <= 0 || $ownerId <= 0) {
            return response('Invalid participants', 400);
        }
        $rows = DB::table('message_histories')
            ->where(function ($q) use ($renterId, $ownerId) {
                $q->where('sender_id', $ownerId)->where('receiver_id', $renterId);
            })
            ->orWhere(function ($q) use ($renterId, $ownerId) {
                $q->where('sender_id', $renterId)->where('receiver_id', $ownerId);
            })
            ->orderBy('id')
            ->limit(500)
            ->get();

        return response()->view('admin.message_histories.history', compact('rows', 'renterId', 'ownerId'));
    }

    public function loadnewmessage(Request $request)
    {
        return response()->view('admin.message_histories.new_message', [
            'renterId' => (int)$request->input('renter_id', 0),
            'ownerId' => (int)$request->input('owner_id', 0),
        ]);
    }

    public function sendnewmessage(Request $request): JsonResponse
    {
        $sender = (int)$request->input('sender_id', 0);
        $receiver = (int)$request->input('receiver_id', 0);
        $message = trim((string)$request->input('message', ''));
        if ($sender <= 0 || $receiver <= 0 || $message === '') {
            return response()->json(['status' => false, 'message' => 'Invalid request']);
        }
        DB::table('message_histories')->insert([
            'sender_id' => $sender,
            'receiver_id' => $receiver,
            'message' => $message,
            'created' => now()->toDateTimeString(),
        ]);

        return response()->json(['status' => true, 'message' => 'Message sent successfully']);
    }
}

