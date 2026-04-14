<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Services\Legacy\AtomicService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AtomicWebhooksController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    public function index(Request $request)
    {
        $postData = $request->getContent();
        if (empty($postData)) {
            return response('Sorry, wrong effort!!', 400);
        }

        Log::channel('daily')->info('Atomic Webhook', [
            'headers' => $request->headers->all(),
            'body' => $postData,
        ]);

        $decodedData = json_decode($postData, true);

        if (isset($decodedData['eventType']) && $decodedData['eventType'] == 'linked-account-disconnected') {
            if ($decodedData['user']['_id'] != '') {
                $exists = DB::table('atomic_users')
                    ->where('linkedAccount', $decodedData['data']['linkedAccount'])
                    ->first();
                if (empty($exists)) {
                    return response('dont do anything');
                }
                DB::table('atomic_users')->where('id', $exists->id)->update(['trash' => 1]);
            }
        }

        if (isset($decodedData['eventType']) && $decodedData['eventType'] == 'task-authentication-status-updated' && ($decodedData['data']['authenticated'] ?? false)) {
            if ($decodedData['user']['_id'] != '') {
                $userId = base64_decode($decodedData['metadata']['userid']);
                $userObj = DB::table('users')->where('id', $userId)->select('id')->first();
                if (empty($userObj)) {
                    return response('Sorry user not found');
                }
                $dataToSave = [
                    'user_id' => $userId,
                    'company' => $decodedData['company']['name'],
                    'customerId' => $decodedData['user']['_id'],
                    'payrollId' => '',
                    'companyId' => $decodedData['company']['_id'],
                ];
                $exists = DB::table('atomic_users')
                    ->where('user_id', $userId)
                    ->where('companyId', $decodedData['company']['_id'])
                    ->select('id', 'income')
                    ->first();
                if (!empty($exists)) {
                    return response('Sorry user already connected');
                }
                try {
                    DB::table('atomic_users')->insert($dataToSave);
                    $this->syncAccounts($userId);
                } catch (\Exception $e) {
                    return response($e->getMessage(), 500);
                }
            }
        }

        return response('finished');
    }

    private function syncAccounts($userid): void
    {
        $atomicObj = new AtomicService();
        $allConnectedAccounts = $atomicObj->pullConnectedAccount($userid);
        if (isset($allConnectedAccounts['status']) && $allConnectedAccounts['status'] == false) {
            return;
        }
        foreach ($allConnectedAccounts['data'] ?? [] as $account) {
            $exists = DB::table('atomic_users')
                ->where('user_id', $userid)
                ->where('companyId', $account['company']['_id'])
                ->whereNull('linkedAccount')
                ->first();
            if (empty($exists)) {
                continue;
            }
            DB::table('atomic_users')->where('id', $exists->id)->update([
                'income' => 1,
                'linkedAccount' => $account['_id'],
            ]);
        }
    }
}
