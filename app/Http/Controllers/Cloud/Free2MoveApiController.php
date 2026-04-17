<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use Illuminate\Http\Request;

class Free2MoveApiController extends LegacyAppController
{
    protected bool $shouldLoadLegacyModules = false;

    private string $SIGNATURE = 'FFGJHGJHGHG788768UYT';

    public function lead(Request $request)
    {
        $authHeader = $request->header('Dia-Signature', '');
        if ($authHeader !== $this->SIGNATURE) {
            return response()->json(['status' => false, 'message' => 'Sorry, login signature dont match'], 400);
        }

        $postData = $request->getContent();
        $dataValues = json_decode($postData, true);

        if (!isset($dataValues['name']) || !isset($dataValues['email']) || empty($dataValues['email'])) {
            return response()->json(['status' => false, 'message' => 'sorry, wrong attempt']);
        }

        $searchQuery = [
            'query' => [
                'operator' => 'AND',
                'value' => [
                    ['field' => 'email', 'operator' => '=', 'value' => $dataValues['email']],
                    ['field' => 'role', 'operator' => '=', 'value' => 'lead'],
                ],
            ],
            'pagination' => ['per_page' => 1],
        ];

        // Intercom lead search - preserved as legacy call
        $intercom = new \Intercom();
        $resp = $intercom->searchLead($searchQuery);
        if (isset($resp->total_count) && $resp->total_count > 0) {
            return response()->json(['status' => true, 'message' => 'already exists']);
        }

        $resp = $intercom->createLead([
            'email' => $dataValues['email'],
            'name' => $dataValues['name'] ?? '',
            'phone' => $dataValues['phone'] ?? '',
            'type' => 'Lead',
            'role' => 'lead',
        ]);

        if (isset($resp->id) && !empty($resp->id)) {
            $tagOpt = ['id' => 12341912];
            $intercom->createUserTag($resp->id, $tagOpt);
            return response()->json(['status' => true, 'message' => 'Lead saved successfully with tag']);
        }

        return response()->json(['status' => true, 'message' => 'success']);
    }
}
