<?php

namespace App\Http\Controllers\Traits;

use App\Models\Legacy\User;
use App\Services\Legacy\IntercomClient;
use Exception;

trait LeadLib
{
    protected function _pushToIntercom(array &$dataToSave)
    {
        try {
            $searchQuery = [
                "query" => [
                    "operator" => "AND",
                    "value" => [
                        [
                            "field" => "email",
                            "operator" => "=",
                            "value" => $dataToSave['email']
                        ],
                        [
                            "field" => "role",
                            "operator" => "=",
                            "value" => "lead"
                        ]
                    ]
                ],
                "pagination" => [
                    "per_page" => 1
                ]
            ];

            $intercom = new IntercomClient();
            $resp = $intercom->searchLead($searchQuery);

            if (isset($resp->total_count) && $resp->total_count > 0) {
                $dataToSave['intercom_id'] = $resp->data[0]->id ?? '';
                return;
            }

            $fullName = !empty($dataToSave['first_name']) ? ($dataToSave['first_name'] . ' ' . $dataToSave['last_name']) : '';

            $resp = $intercom->createLead([
                "email" => $dataToSave['email'],
                "name" => $fullName,
                "phone" => $dataToSave['phone'] ?? '',
                "type" => 'Lead',
                "role" => 'lead'
            ]);

            $dataToSave['intercom_id'] = $resp->id ?? '';

            if (!empty($resp->id)) {
                $tag = $this->_getUserTagById($dataToSave['admin_id'] ?? 0);
                $nameTagOpt = ["name" => $tag];

                $tagResp = $intercom->createTag($nameTagOpt);

                if (isset($tagResp->id) && !empty($tagResp->id)) {
                    $tagOpt = ["id" => $tagResp->id];
                } else {
                    $tagOpt = ["id" => 12341912]; // Free2Move leads
                }

                $intercom->createUserTag($resp->id, $tagOpt);
            }

        } catch (Exception $e) {
            $logPath = storage_path('logs/cloudlead_' . now()->format('Y-m-d') . '.log');
            $logMessage = "\n" . now()->format('Y-m-d H:i:s') . '=_pushToIntercom=:' . $e->getMessage();
            file_put_contents($logPath, $logMessage, FILE_APPEND);
        }
    }
    private function _getUserTagById($userId)
    {
        if (empty($userId) || $userId == 0 || $userId === '0') {
            return 'DIA Admin Lead';
        }

        $user = User::find($userId);
        return $user ? ($user->first_name . ' ' . $user->last_name . ' Lead') : '';
    }
    public function pullIntercomContact($intercom_id)
    {
        try {
            return (new IntercomClient())->getContact($intercom_id);
        } catch (Exception $e) {
            // Silently capture exception payload matching original layout rules
        }
        return [];
    }
}