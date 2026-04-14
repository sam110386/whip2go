<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class LeadService
{
    /**
     * Push lead data to Intercom (search/create lead, tag).
     * Uses IntercomClient from the legacy services layer.
     */
    public function pushToIntercom(array &$dataToSave): void
    {
        try {
            $intercom = app(\App\Services\Legacy\IntercomClient::class);

            $searchQuery = [
                'query' => [
                    'operator' => 'AND',
                    'value' => [
                        ['field' => 'email', 'operator' => '=', 'value' => $dataToSave['email']],
                        ['field' => 'role', 'operator' => '=', 'value' => 'lead'],
                    ],
                ],
                'pagination' => ['per_page' => 1],
            ];

            $resp = $intercom->searchLead($searchQuery);
            if (isset($resp->total_count) && $resp->total_count > 0) {
                $dataToSave['intercom_id'] = $resp->data[0]->id ?? '';
                return;
            }

            $resp = $intercom->createLead([
                'email' => $dataToSave['email'],
                'name' => $dataToSave['first_name'] ? ($dataToSave['first_name'] . ' ' . $dataToSave['last_name']) : '',
                'phone' => $dataToSave['phone'] ?? '',
                'type' => 'Lead',
                'role' => 'lead',
            ]);

            $dataToSave['intercom_id'] = $resp->id ?? '';

            if (!empty($resp->id)) {
                $tag = $this->getUserTagById($dataToSave['admin_id'] ?? 0);
                $nameTagOpt = ['name' => $tag];
                $tagResp = $intercom->createTag($nameTagOpt);

                $tagOpt = (!empty($tagResp->id)) ? ['id' => $tagResp->id] : ['id' => 12341912];
                $intercom->createUserTag($resp->id, $tagOpt);
            }
        } catch (\Exception $e) {
            \Log::channel('daily')->error('_pushToIntercom: ' . $e->getMessage());
        }
    }

    private function getUserTagById($userId): string
    {
        if (empty($userId)) {
            return 'DIA Admin Lead';
        }

        $user = DB::table('users')->where('id', $userId)->first();
        return $user ? ($user->first_name . ' ' . $user->last_name . ' Lead') : '';
    }

    public function pullIntercomContact($intercomId)
    {
        try {
            return app(\App\Services\Legacy\IntercomClient::class)->getContact($intercomId);
        } catch (\Exception $e) {
            // ignore
        }
        return [];
    }
}
