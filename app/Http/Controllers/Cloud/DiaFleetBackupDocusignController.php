<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\AgreementTrait;
use App\Services\Legacy\DocusignToken;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class DiaFleetBackupDocusignController extends LegacyAppController
{
    use AgreementTrait;

    private $config;
    private $args;
    private int $signer_client_id = 1000;
    private array $userObj = [];

    public function returncallback()
    {
        $orderandusers = session('orderandusers');
        list($order, $user) = explode('|', base64_decode($orderandusers));

        $event = request()->query('event');

        if ($event === 'session_timeout' || $event === 'viewing_complete') {
            return redirect('/');
        }

        if ($event === 'signing_alreadycomplete') {
            return redirect('/insurance/roi/diafleetbackupreview/' . $orderandusers)
                ->with('thankyou', 'Seems you already signed the document. Please contact to support if you want to change any selection');
        }

        if ($event === 'signing_complete') {
            DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $order)
                ->where('docusign_status', 0)
                ->update(['docusign_status' => 1]);

            DB::table('vehicle_reservations')
                ->where('id', $order)
                ->update(['docusign' => 1]);

            return redirect('/insurance/roi/diafleetbackupreview/' . $orderandusers)
                ->with('thankyou', 'Thanks for completing the documents. One of our representatives will be in touch shortly with next steps.');
        }

        abort(500, 'Sorry, something went wrong. Please try again later or contact support.');
    }

    public function signDocument($orderandusers)
    {
        list($order, $user) = explode('|', base64_decode($orderandusers));
        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        session(['orderandusers' => $orderandusers]);

        try {
            $this->signer_client_id = (int) $user;

            $userObj = DB::table('users')
                ->where('id', $user)
                ->select('id', 'email', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'currency', 'licence_number', 'licence_state')
                ->first();

            if (empty($userObj)) {
                abort(403, 'Seems your are spam!!!');
            }

            $userArr = (array) $userObj;
            try {
                $userArr['licence_number'] = Crypt::decrypt($userArr['licence_number']);
            } catch (\Exception $e) {
                // Keep raw value if decryption fails
            }

            $this->userObj = ['User' => $userArr];

            $this->args = $this->getTemplateArgs($orderandusers);
            $args = $this->args;
            $envelope_args = $args['envelope_args'];

            $this->getEnvelopeApi();
            $api_client = new \DocuSign\eSign\Client\ApiClient($this->config);
            $envelope_api = new \DocuSign\eSign\Api\EnvelopesApi($api_client);

            $quoteObj = DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $order)
                ->first();
            $quoteArr = $quoteObj ? (array) $quoteObj : [];

            if (!empty($quoteArr['docusign_envelope_id'])) {
                $envelopeId = $quoteArr['docusign_envelope_id'];
                $envelopObj = $envelope_api->getEnvelope($args['account_id'], $envelopeId);

                if ($envelopObj['status'] === 'completed') {
                    return redirect('/insurance/dia_fleet_backup_docusign/returncallback?event=signing_alreadycomplete');
                }

                $documents = $envelope_api->listDocuments($args['account_id'], $envelopeId);
                foreach ($documents['envelopeDocuments'] as $document) {
                    $docId = $document['documentId'];
                    if ($docId !== 'certificate') {
                        $envelope_api->deleteDocuments($args['account_id'], $envelopeId, [$docId]);
                    }
                }

                $envelope_definition = $this->makeEnvelopeFileObject($envelope_args, $order, $envelopeId, $quoteArr);
                $envelope_api->updateDocuments($args['account_id'], $envelopeId, $envelope_definition);
            } else {
                $envelope_definition = $this->makeEnvelopeFileObject($envelope_args, $order, '', $quoteArr);
                $results = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
                $envelopeId = $results->getEnvelopeId();
            }

            $recipient_view_request = new \DocuSign\eSign\Model\RecipientViewRequest([
                'authentication_method' => 'None',
                'client_user_id'        => $envelope_args['signer_client_id'],
                'recipient_id'          => '1',
                'return_url'            => $envelope_args['ds_return_url'],
                'user_name'             => $this->userObj['User']['first_name'] . ' ' . $this->userObj['User']['last_name'],
                'email'                 => $this->userObj['User']['email'],
            ]);

            $results = $envelope_api->createRecipientView($args['account_id'], $envelopeId, $recipient_view_request);

            DB::table('driver_financed_insurance_quotes')
                ->where('order_id', $order)
                ->update(['docusign_envelope_id' => $envelopeId]);

            return redirect($results['url']);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    private function makeEnvelopeFileObject(array $args, $order, string $envelopeId = '', array $quoteArr = [])
    {
        $reservationData = [];
        $reservationData['currency'] = $this->userObj['User']['currency'];
        $reservationData['today'] = date('m/d/Y');
        $reservationData['insurance'] = sprintf('%0.2f', $quoteArr['premium_finance_total'] ?? 0);
        $reservationData['first_insurance'] = sprintf('%0.2f', $quoteArr['premium_total'] ?? 0);

        $loan_agreement = 'loan_agreement_' . $order . '.pdf';
        $loan_agreement_file = public_path('files/agreements/temp/' . $loan_agreement);
        // TODO: Replace with Agreement service – generateLoanAgreement()

        $power_of_attorny = 'power_of_attorny_' . $order . '.pdf';
        $power_of_attorny_file = public_path('files/agreements/temp/' . $power_of_attorny);
        // TODO: Replace with Agreement service – generatePowerOfAttorny()

        $AgreementObj = $this->_generateDocusignAgreement($order, $quoteArr['daily_rate'] ?? 0);

        $arrContextOptions = [
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
        ];

        $documents = [];

        if ($loan_agreement_file && file_exists($loan_agreement_file)) {
            $ContentBytes = file_get_contents($loan_agreement_file, false, stream_context_create($arrContextOptions));
            $documents[] = new \DocuSign\eSign\Model\Document([
                'document_base64' => base64_encode($ContentBytes),
                'name'            => 'DRIVEITAWAY Loan Agreement',
                'file_extension'  => 'pdf',
                'document_id'     => 1,
            ]);
        }

        if ($power_of_attorny_file && file_exists($power_of_attorny_file)) {
            $ContentBytes = file_get_contents($power_of_attorny_file, false, stream_context_create($arrContextOptions));
            $documents[] = new \DocuSign\eSign\Model\Document([
                'document_base64' => base64_encode($ContentBytes),
                'name'            => 'Power Of Attorney',
                'file_extension'  => 'pdf',
                'document_id'     => 2,
            ]);
        }

        if ($AgreementObj['status'] && !empty($AgreementObj['result']['filepath']) && file_exists($AgreementObj['result']['filepath'])) {
            $ContentBytes = file_get_contents($AgreementObj['result']['filepath'], false, stream_context_create($arrContextOptions));
            $documents[] = new \DocuSign\eSign\Model\Document([
                'document_base64' => base64_encode($ContentBytes),
                'name'            => 'DRIVEITAWAY Lease Agreement',
                'file_extension'  => 'pdf',
                'document_id'     => 3,
            ]);
        }

        $signer = new \DocuSign\eSign\Model\Signer([
            'email'          => $this->userObj['User']['email'],
            'name'           => $this->userObj['User']['first_name'] . ' ' . $this->userObj['User']['last_name'],
            'recipient_id'   => '1',
            'routing_order'  => '1',
            'client_user_id' => $args['signer_client_id'],
        ]);

        if (empty($envelopeId)) {
            $signHere = new \DocuSign\eSign\Model\SignHere([
                'anchor_string'   => '/Renter_Priter_Name/',
                'anchor_units'    => 'pixels',
                'anchor_y_offset' => '10',
                'anchor_x_offset' => '20',
            ]);
            $signHere2 = new \DocuSign\eSign\Model\SignHere([
                'anchor_string'   => '/sn2/',
                'anchor_units'    => 'pixels',
                'anchor_y_offset' => '0',
                'anchor_x_offset' => '0',
            ]);
            $signHere3 = new \DocuSign\eSign\Model\SignHere([
                'anchor_string'   => '/sn3/',
                'anchor_units'    => 'pixels',
                'anchor_y_offset' => '0',
                'anchor_x_offset' => '0',
            ]);
            $signer->setTabs(new \DocuSign\eSign\Model\Tabs([
                'sign_here_tabs' => [$signHere, $signHere2, $signHere3],
            ]));
        }

        return new \DocuSign\eSign\Model\EnvelopeDefinition([
            'email_subject' => 'Please sign this document sent from the driveitaway.com',
            'documents'     => $documents,
            'recipients'    => new \DocuSign\eSign\Model\Recipients(['signers' => [$signer]]),
            'status'        => 'sent',
        ]);
    }

    private function getEnvelopeApi()
    {
        $this->config = new \DocuSign\eSign\Configuration();
        $this->config->setHost($this->args['base_path']);
        $this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->args['ds_access_token']);
        $apiClient = new \DocuSign\eSign\Client\ApiClient($this->config);

        return new \DocuSign\eSign\Api\EnvelopesApi($apiClient);
    }

    private function getTemplateArgs(string $orderandusers): array
    {
        $token = (new DocusignToken())->getToken();

        return [
            'account_id'      => config('legacy.Docusign.accountid'),
            'base_path'       => config('legacy.Docusign.url') . '/restapi',
            'ds_access_token' => $token['access_token'],
            'envelope_args'   => [
                'signer_client_id' => $this->signer_client_id,
                'ds_return_url'    => url('/insurance/dia_fleet_backup_docusign/returncallback'),
            ],
        ];
    }
}
