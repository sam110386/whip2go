<?php

namespace App\Http\Controllers\Cloud;

use App\Http\Controllers\Legacy\LegacyAppController;
use App\Http\Controllers\Traits\AgreementTrait;
use App\Services\Legacy\DocusignToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocusignController extends LegacyAppController
{
    use AgreementTrait;

    private $config;
    private array $args = [];
    private int $signer_client_id = 1000;
    private ?array $userObj = null;

    public function returncallback(Request $request)
    {
        $orderandusers = session('orderandusers');
        $parts = explode('|', base64_decode($orderandusers ?? ''));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        $event = $request->query('event');

        if (in_array($event, ['session_timeout', 'viewing_complete'])) {
            return redirect('/insurance_provider/insurance_quotes/review/' . $orderandusers);
        }

        $thankyou = null;
        if ($event === 'signing_alreadycomplete') {
            $thankyou = 'Seems you already signed the document. Please contact to support if you want to change any selection';
        }

        if ($event === 'signing_complete') {
            DB::table('vehicle_reservations')->where('id', $order)->update(['docusign' => 1]);
            DB::table('insurance_quotes')
                ->where('order_id', $order)
                ->where('selected', 2)
                ->update(['selected' => 1]);
            $thankyou = 'Thanks for completing the documents. One of our representatives will be in touch shortly with next steps.';
        }

        return view('cloud.insurance_provider.docusign.returncallback', compact('thankyou'));
    }

    public function connectDocusign()
    {
        try {
            $params = [
                'response_type' => 'code',
                'scope'         => 'signature extended',
                'client_id'     => config('legacy.Docusign.integration_key'),
                'state'         => 'a39fh23hnf23',
                'redirect_uri'  => url('/insurance_provider/docusign/callback'),
            ];
            $queryBuild = http_build_query($params);
            $url = config('legacy.Docusign.auth_url');
            return redirect($url . $queryBuild);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $code = $request->query('code');
        try {
            $this->config = new \DocuSign\eSign\Configuration(['host' => config('legacy.Docusign.url')]);
            $obj = new \DocuSign\eSign\Client\ApiClient($this->config);
            $result = $obj->generateAccessToken(
                config('legacy.Docusign.integration_key'),
                config('legacy.Docusign.secret_key'),
                $code
            );

            $tokenFile = storage_path('app/Docusign.txt');
            $result = json_decode(json_encode($result['result']), true);
            $result['expire_at'] = (time() + $result['expires_in'] - 60);
            file_put_contents($tokenFile, json_encode($result));

            return response('Token is generated successfully');
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    public function signDocument($insuranceQuoteId, $orderandusers)
    {
        $parts = explode('|', base64_decode($orderandusers));
        $order = $parts[0] ?? null;
        $user = $parts[1] ?? null;

        if (empty($order) || empty($user)) {
            abort(403, 'sorry wrong attempt');
        }

        session(['orderandusers' => $orderandusers]);

        try {
            $this->signer_client_id = (int) $user;

            $userRow = DB::table('users')
                ->where('id', $user)
                ->first(['id', 'email', 'first_name', 'last_name', 'address', 'city', 'state', 'zip', 'currency', 'licence_number', 'licence_state']);

            if (empty($userRow)) {
                abort(403, 'Seems your are spam!!!');
            }

            $userArr = (array) $userRow;
            $userArr['licence_number'] = decrypt($userArr['licence_number']);
            $this->userObj = $userArr;

            $this->args = $this->getTemplateArgs();
            $args = $this->args;
            $envelope_args = $args['envelope_args'];
            $envelope_api = $this->getEnvelopeApi();

            $insuranceObj = DB::table('insurance_quotes')
                ->where('order_id', $order)
                ->where('docusign_envelope_id', '!=', '')
                ->first();

            $envelopeId = null;

            if (!empty($insuranceObj)) {
                $envelopeId = $insuranceObj->docusign_envelope_id;
                $envelopObj = $envelope_api->getEnvelope($args['account_id'], $envelopeId);

                if ($envelopObj['status'] === 'completed') {
                    return redirect('/insurance_provider/docusign/returncallback?event=signing_alreadycomplete');
                }

                $documents = $envelope_api->listDocuments($args['account_id'], $envelopeId);
                foreach ($documents['envelopeDocuments'] as $document) {
                    $docId = $document['documentId'];
                    if ($docId !== 'certificate') {
                        $envelope_api->deleteDocuments($args['account_id'], $envelopeId, [$docId]);
                    }
                }

                $envelope_definition = $this->makeEnvelopeFileObject($envelope_args, $order, $envelopeId, $insuranceQuoteId);
                $envelope_api->updateDocuments($args['account_id'], $envelopeId, $envelope_definition);
            } else {
                $envelope_definition = $this->makeEnvelopeFileObject($envelope_args, $order, '', $insuranceQuoteId);
                $results = $envelope_api->createEnvelope($args['account_id'], $envelope_definition);
                $envelopeId = $results->getEnvelopeId();
            }

            $recipient_view_request = new \DocuSign\eSign\Model\RecipientViewRequest([
                'authentication_method' => 'None',
                'client_user_id'        => $envelope_args['signer_client_id'],
                'recipient_id'          => '1',
                'return_url'            => $envelope_args['ds_return_url'],
                'user_name'             => $this->userObj['first_name'] . ' ' . $this->userObj['last_name'],
                'email'                 => $this->userObj['email'],
            ]);

            $results = $envelope_api->createRecipientView($args['account_id'], $envelopeId, $recipient_view_request);

            DB::table('insurance_quotes')
                ->where('id', $insuranceQuoteId)
                ->update(['docusign_envelope_id' => $envelopeId]);

            return redirect($results['url']);
        } catch (\Exception $e) {
            abort(500, $e->getMessage());
        }
    }

    private function makeEnvelopeFileObject(array $args, $order, string $envelopeId = '', $insuranceQuoteId = null)
    {
        $InsuranceQuoteObj = DB::table('insurance_quotes')
            ->where('id', $insuranceQuoteId)
            ->first();

        if (empty($InsuranceQuoteObj)) {
            abort(403, 'sorry wrong attempt');
        }

        $quoteArr = (array) $InsuranceQuoteObj;

        $VehicleReservationObj = [];
        $VehicleReservationObj['currency'] = $this->userObj['currency'];
        $VehicleReservationObj['today'] = date('m/d/Y');
        $loan_agreement = 'loan_agreement_' . $order . '.pdf';
        $VehicleReservationObj['insurance'] = sprintf('%0.2f', ($quoteArr['daily_rate'] * 365 / 2));
        $VehicleReservationObj['first_insurance'] = sprintf('%0.2f', ($quoteArr['daily_rate'] * 365 / 2));

        $loan_agreement_file = public_path('files/agreements/temp/' . $loan_agreement);
        // TODO: Generate loan agreement PDF via Agreement service
        // $this->Agreement->generateLoanAgreement($VehicleReservationObj, $loan_agreement_file);

        $power_of_attorny = 'power_of_attorny_' . $order . '.pdf';
        $power_of_attorny_file = public_path('files/agreements/temp/' . $power_of_attorny);
        // TODO: Generate power of attorney PDF via Agreement service if not already present
        // if (!is_file($power_of_attorny_file)) {
        //     $this->Agreement->generatePowerOfAttorny($this->userObj, $power_of_attorny_file);
        // }

        $AgreementObj = $this->_generateDocusignAgreement($order, $quoteArr['daily_rate']);

        $sslContext = stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $documents = [];
        if (file_exists($loan_agreement_file)) {
            $ContentBytes = file_get_contents($loan_agreement_file, false, $sslContext);
            $documents[] = new \DocuSign\eSign\Model\Document([
                'document_base64' => base64_encode($ContentBytes),
                'name'            => 'DRIVEITAWAY Loan Agreement',
                'file_extension'  => 'pdf',
                'document_id'     => 1,
            ]);
        }
        if (file_exists($power_of_attorny_file)) {
            $ContentBytes = file_get_contents($power_of_attorny_file, false, $sslContext);
            $documents[] = new \DocuSign\eSign\Model\Document([
                'document_base64' => base64_encode($ContentBytes),
                'name'            => 'Power Of Attorney',
                'file_extension'  => 'pdf',
                'document_id'     => 2,
            ]);
        }
        if (!empty($AgreementObj['status'])) {
            $ContentBytes = file_get_contents($AgreementObj['result']['filepath'], false, $sslContext);
            $documents[] = new \DocuSign\eSign\Model\Document([
                'document_base64' => base64_encode($ContentBytes),
                'name'            => 'DRIVEITAWAY Lease Agreement',
                'file_extension'  => 'pdf',
                'document_id'     => 3,
            ]);
        }

        $signer = new \DocuSign\eSign\Model\Signer([
            'email'            => $this->userObj['email'],
            'name'             => $this->userObj['first_name'] . ' ' . $this->userObj['last_name'],
            'recipient_id'     => '1',
            'routing_order'    => '1',
            'client_user_id'   => $args['signer_client_id'],
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

    private function getEnvelopeApi(): \DocuSign\eSign\Api\EnvelopesApi
    {
        $this->config = new \DocuSign\eSign\Configuration();
        $this->config->setHost($this->args['base_path']);
        $this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->args['ds_access_token']);
        $apiClient = new \DocuSign\eSign\Client\ApiClient($this->config);
        return new \DocuSign\eSign\Api\EnvelopesApi($apiClient);
    }

    private function getTemplateArgs(): array
    {
        $token = (new DocusignToken())->getToken();
        return [
            'account_id'      => config('legacy.Docusign.accountid'),
            'base_path'       => config('legacy.Docusign.url') . '/restapi',
            'ds_access_token' => $token['access_token'],
            'envelope_args'   => [
                'signer_client_id' => $this->signer_client_id,
                'ds_return_url'    => url('/insurance_provider/docusign/returncallback'),
            ],
        ];
    }
}
