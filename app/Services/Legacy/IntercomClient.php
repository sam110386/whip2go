<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Intercom\IntercomClient as IntercomSDK;
use Exception;

/**
 * Port of CakePHP app/Lib/Intercom.php
 *
 * Wraps the Intercom PHP SDK for contact search/create, messaging,
 * conversations with tags, events, tickets, and contact cleanup.
 *
 * Requires: intercom/intercom-php composer package.
 */
class IntercomClient
{
    private array $_tags = [
        'accident' => ['id' => 4899908, 'name' => 'Accident'],
        'billing' => ['id' => 5512941, 'name' => 'Billing'],
        'booked' => ['id' => 5001674, 'name' => 'Booked'],
        'maintenance' => ['id' => 5517619, 'name' => 'Maintenance'],
        'midway' => ['id' => 5399976, 'name' => 'Midway'],
        'roadside_assistance' => ['id' => 5517627, 'name' => 'Roadside Assistance'],
        'insurance_quote' => ['id' => 8199472, 'name' => 'Insurance Quote'],
        'payment_request' => ['id' => 8645239, 'name' => 'Payment Request'],
        'vehicle_scan_alert' => ['id' => 8991028, 'name' => 'Vehicle Scan Alert'],
        'insurance_type_changed' => ['id' => 10637681, 'name' => 'Insurance Type Changed'],
    ];

    private function client(string $version = '2.0'): IntercomSDK
    {
        return new IntercomSDK(config('services.intercom.access_token'), null, ['Intercom-Version' => $version]);
    }

    private function adminId(): string
    {
        return (string) config('services.intercom.admin_id');
    }

    /**
     * Search or create an Intercom contact from user info.
     */
    private function resolveContact(IntercomSDK $client, array $userinfo): ?string
    {
        try {
            $resp = $client->contacts->search([
                'query' => [
                    'operator' => 'OR',
                    'value' => [
                        ['field' => 'custom_attributes.member_id', 'operator' => '=', 'value' => $userinfo['id']],
                        ['field' => 'phone', 'operator' => '=', 'value' => $userinfo['contact_number']],
                        ['field' => 'email', 'operator' => '=', 'value' => $userinfo['email']],
                    ],
                ],
            ]);
            $user = isset($resp->data[0]) ? $resp->data[0]->id : '';
            if (empty($user)) {
                $resp = $client->contacts->create([
                    'role' => 'user',
                    'external_id' => $userinfo['id'],
                    'email' => $userinfo['email'],
                    'phone' => $userinfo['contact_number'],
                    'name' => $userinfo['first_name'] . ' ' . $userinfo['last_name'],
                    'unsubscribed_from_emails' => false,
                    'custom_attributes' => ['member_id' => $userinfo['id']],
                ]);
                $user = $resp->id;
            }
            return $user ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Resolve contact with extended_id field search too.
     */
    private function resolveContactExtended(IntercomSDK $client, array $userinfo): ?string
    {
        try {
            $resp = $client->contacts->search([
                'query' => [
                    'operator' => 'OR',
                    'value' => [
                        ['field' => 'custom_attributes.member_id', 'operator' => '=', 'value' => $userinfo['id']],
                        ['field' => 'phone', 'operator' => '=', 'value' => $userinfo['contact_number']],
                        ['field' => 'email', 'operator' => '=', 'value' => $userinfo['email']],
                        ['field' => 'external_id', 'operator' => '=', 'value' => $userinfo['id']],
                    ],
                ],
            ]);
            $user = isset($resp->data[0]) ? $resp->data[0]->id : '';
            if (empty($user)) {
                $resp = $client->contacts->create([
                    'role' => 'user',
                    'external_id' => $userinfo['id'],
                    'email' => $userinfo['email'],
                    'phone' => $userinfo['contact_number'],
                    'name' => $userinfo['first_name'] . ' ' . $userinfo['last_name'],
                    'unsubscribed_from_emails' => false,
                    'custom_attributes' => ['member_id' => $userinfo['id']],
                ]);
                $user = $resp->id;
            }
            return $user ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    private function replyOrNewConversation(IntercomSDK $clientV2, IntercomSDK $clientV14, string $user, string $msg): void
    {
        try {
            $clientV14->conversations->replyToLastConversation([
                'intercom_user_id' => $user,
                'body' => $msg,
                'type' => 'admin',
                'admin_id' => $this->adminId(),
                'message_type' => 'comment',
            ]);
        } catch (Exception $e) {
            $clientV2->messages->create([
                'message_type' => 'inapp',
                'body' => $msg,
                'from' => ['type' => 'admin', 'id' => $this->adminId()],
                'to' => ['type' => 'user', 'id' => $user],
            ]);
        }
    }

    public function sendMessage(array $userinfo, string $msg, $cs_twilio_order_id = '', $userid = ''): ?array
    {
        $client = $this->client('2.0');
        $user = $this->resolveContact($client, $userinfo);
        if (empty($user)) {
            return null;
        }

        $clientV14 = $this->client('1.4');
        $this->replyOrNewConversation($client, $clientV14, $user, $msg);

        if (!empty($cs_twilio_order_id)) {
            DB::table('cs_twilio_logs')->insert([
                'cs_twilio_order_id' => $cs_twilio_order_id,
                'renter_phone' => substr(preg_replace('/[^0-9]/', '', $userinfo['contact_number']), -10),
                'user_id' => $userid,
                'msg' => $msg,
                'created' => now(),
                'modified' => now(),
            ]);
        }

        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }

    public function sendMessageOpt(array $userinfo, string $msg, $userid): ?array
    {
        $client = $this->client('2.0');
        $user = $this->resolveContact($client, $userinfo);
        if (empty($user)) {
            return null;
        }

        $clientV14 = $this->client('1.4');
        $this->replyOrNewConversation($client, $clientV14, $user, $msg);

        if (!empty($userinfo['username'])) {
            $old = DB::table('cs_twilio_logs')
                ->where('user_id', $userid)
                ->where('renter_phone', $userinfo['username'])
                ->value('cs_twilio_order_id');

            if ($old) {
                DB::table('cs_twilio_logs')->insert([
                    'cs_twilio_order_id' => $old,
                    'renter_phone' => $userinfo['username'],
                    'user_id' => $userid,
                    'msg' => $msg,
                    'created' => now(),
                    'modified' => now(),
                ]);
            }
        }

        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }

    public function sendEvent(array $userinfo, array $metadata, string $event = 'pathscreen'): ?string
    {
        $client = $this->client('2.0');
        $user = $this->resolveContact($client, $userinfo);
        if (empty($user)) {
            return null;
        }
        try {
            $client->events->create([
                'event_name' => $event,
                'created_at' => time(),
                'id' => $user,
                'user_id' => $userinfo['id'],
                'metadata' => $metadata,
            ]);
            return null;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function syncForWebhook(array $data, string $msg): ?array
    {
        $client = $this->client('2.0');
        $user = '';
        try {
            $resp = $client->contacts->search([
                'query' => [
                    'operator' => 'OR',
                    'value' => [
                        ['field' => 'phone', 'operator' => '=', 'value' => $data['phone']],
                        ['field' => 'email', 'operator' => '=', 'value' => $data['email']],
                    ],
                ],
            ]);
            $user = isset($resp->data[0]) ? $resp->data[0]->id : '';
            if (empty($user)) {
                $resp = $client->contacts->create([
                    'role' => 'user',
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'custom_attributes' => [
                        'address' => $data['address'],
                        'City' => $data['city'],
                        'State' => $data['state'],
                        'postal' => $data['postal'],
                        'usertype' => $data['usertype'],
                    ],
                    'unsubscribed_from_emails' => false,
                ]);
                $user = $resp->id;
            }
        } catch (Exception $e) {
            return null;
        }
        if (empty($user)) {
            return null;
        }

        $clientV14 = $this->client('1.4');
        try {
            $clientV14->conversations->replyToLastConversation([
                'intercom_user_id' => $user,
                'body' => $msg,
                'type' => 'user',
                'admin_id' => $this->adminId(),
                'message_type' => 'comment',
            ]);
        } catch (Exception $e) {
            $client->messages->create([
                'message_type' => 'inapp',
                'body' => $msg,
                'to' => ['type' => 'admin', 'id' => $this->adminId()],
                'from' => ['type' => 'user', 'id' => $user],
            ]);
        }

        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }

    public function searchAndDeleteContact(array $data = []): void
    {
        $client = $this->client('2.0');
        try {
            $resp = $client->contacts->search([
                'pagination' => ['page' => 1, 'per_page' => 100],
                'query' => [
                    'field' => 'last_seen_at',
                    'operator' => '<',
                    'value' => strtotime('-30 days'),
                ],
            ]);
            $contacts = $resp->data ?? [];
            foreach ($contacts as $contact) {
                $client->contacts->deleteContact($contact->id);
            }
        } catch (Exception $e) {
            // silently fail
        }
    }

    public function searchAndDeleteNonRegisteredContact(array $data = []): void
    {
        $client = $this->client('2.0');
        try {
            $resp = $client->contacts->search([
                'pagination' => ['page' => 1, 'per_page' => 100],
                'query' => [
                    'operator' => 'AND',
                    'value' => [
                        ['field' => 'last_seen_at', 'operator' => '<', 'value' => strtotime('-3 days')],
                        ['field' => 'email', 'operator' => '=', 'value' => null],
                    ],
                ],
            ]);
            $contacts = $resp->data ?? [];
            foreach ($contacts as $contact) {
                $client->contacts->deleteContact($contact->id);
            }
        } catch (Exception $e) {
            // silently fail
        }
    }

    public function searchAndDeleteDeuplicateContact(int $limit = 20): void
    {
        $client = $this->client('2.0');
        try {
            $resp = $client->contacts->search([
                'pagination' => ['page' => 1, 'per_page' => $limit],
                'query' => [
                    'operator' => 'AND',
                    'value' => [
                        ['field' => 'email', 'operator' => '!=', 'value' => null],
                        ['field' => 'phone', 'operator' => '=', 'value' => null],
                        ['field' => 'role', 'operator' => '!=', 'value' => 'lead'],
                    ],
                ],
            ]);
            $contacts = $resp->data ?? [];
            foreach ($contacts as $contact) {
                $client->contacts->deleteContact($contact->id);
            }
        } catch (Exception $e) {
            Log::error('IntercomClient::searchAndDeleteDeuplicateContact: ' . $e->getMessage());
        }
    }

    public function getIntercomTags()
    {
        $intercom = $this->client('2.0');
        return $intercom->tags->getTags([]);
    }

    public function sendMessageWithTag(array $userinfo, string $msg, string $tag = 'billing', $bookingid = '', array $attributeopt = []): ?array
    {
        $client = $this->client('2.3');
        $user = $this->resolveContactExtended($client, $userinfo);
        if (empty($user)) {
            return null;
        }

        if (!empty($attributeopt)) {
            $client->contacts->update($user, ['custom_attributes' => $attributeopt]);
        }
        if (empty($msg)) {
            return null;
        }

        try {
            $clientObj = $this->client('2.3');
            $conversationId = '';

            if (!empty($bookingid)) {
                $conversationId = DB::table('intercome_orders')
                    ->where('order_id', $bookingid)
                    ->value('conversation_id') ?: '';
            }

            if (!$conversationId) {
                $conversation = $clientObj->conversations->createConversation([
                    'message_type' => 'inapp',
                    'type' => 'conversation',
                    'body' => 'hey',
                    'from' => ['type' => 'user', 'id' => $user],
                    'custom_attributes' => ['booking_message_type' => $tag, 'booking_id' => $bookingid],
                ]);
                $conversationId = $conversation->conversation_id;
                $clientObj->conversations->attachTagToConversation($conversationId, [
                    'id' => (string) $this->_tags[$tag]['id'],
                    'admin_id' => $this->adminId(),
                    'custom_attributes' => ['booking_message_type' => $tag, 'booking_id' => $bookingid],
                ]);
                if (!empty($bookingid)) {
                    DB::table('intercome_orders')->insert([
                        'order_id' => $bookingid,
                        'conversation_id' => $conversationId,
                    ]);
                }
            }
            if (empty($conversationId)) {
                return null;
            }
            $clientObj->conversations->replyToConversation($conversationId, [
                'intercom_user_id' => $user,
                'body' => $msg,
                'type' => 'admin',
                'admin_id' => $this->adminId(),
                'message_type' => 'comment',
                'custom_attributes' => ['booking_message_type' => $tag],
            ]);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }

        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }

    public function sendMessageWithTagAsRenter(array $userinfo, string $msg, string $tag = 'billing'): ?array
    {
        $client = $this->client('2.3');
        $user = $this->resolveContactExtended($client, $userinfo);
        if (empty($user)) {
            return null;
        }

        try {
            $clientObj = $this->client('2.3');
            $conversationId = '';

            $resp = $clientObj->conversations->search([
                'query' => [
                    'operator' => 'AND',
                    'value' => [
                        ['field' => 'tag_ids', 'operator' => '=', 'value' => (string) $this->_tags[$tag]['id']],
                        ['field' => 'contact_ids', 'operator' => '=', 'value' => $user],
                    ],
                ],
            ]);
            if (isset($resp->total_count) && $resp->total_count > 0) {
                $conversationId = isset($resp->conversations[0]) ? $resp->conversations[0]->id : '';
            }

            if (!isset($resp->total_count) || $resp->total_count == 0) {
                $conversation = $clientObj->conversations->createConversation([
                    'message_type' => 'inapp',
                    'type' => 'conversation',
                    'body' => $msg,
                    'from' => ['type' => 'user', 'id' => $user],
                    'custom_attributes' => ['booking_message_type' => $tag],
                ]);
                $conversationId = $conversation->conversation_id;
                $clientObj->conversations->attachTagToConversation($conversationId, [
                    'id' => (string) $this->_tags[$tag]['id'],
                    'admin_id' => $this->adminId(),
                    'custom_attributes' => ['booking_message_type' => $tag],
                ]);
                return null;
            }

            if (empty($conversationId)) {
                return null;
            }
            $clientObj->conversations->replyToConversation($conversationId, [
                'intercom_user_id' => $user,
                'body' => $msg,
                'type' => 'user',
                'user_id' => $user,
                'message_type' => 'comment',
                'custom_attributes' => ['booking_message_type' => $tag],
            ]);
        } catch (Exception $e) {
            // silently fail
        }
        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }

    public function pushEmployeBridgeLead(array $data): array
    {
        $client = $this->client('2.0');
        try {
            $client->contacts->create([
                'role' => 'user',
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'external_id' => $data['id'],
                'custom_attributes' => ['Employbridge' => true, 'member_id' => $data['id']],
                'unsubscribed_from_emails' => false,
            ]);
        } catch (Exception $e) {
            // user already exists
        }
        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }

    public function updateUserAttrbute(array $userinfo, array $opt = [], bool $sendmessage = false): void
    {
        $client = $this->client('2.3');
        $user = '';
        try {
            $resp = $client->contacts->search([
                'query' => [
                    'operator' => 'OR',
                    'value' => [
                        ['field' => 'custom_attributes.member_id', 'operator' => '=', 'value' => $userinfo['id']],
                        ['field' => 'phone', 'operator' => '=', 'value' => $userinfo['contact_number']],
                        ['field' => 'email', 'operator' => '=', 'value' => $userinfo['email']],
                    ],
                ],
            ]);
            $user = isset($resp->data[0]) ? $resp->data[0]->id : '';
            if (empty($user)) {
                $opt = array_merge($opt, ['member_id' => $userinfo['id']]);
                $resp = $client->contacts->create([
                    'role' => 'user',
                    'external_id' => $userinfo['id'],
                    'email' => $userinfo['email'],
                    'phone' => $userinfo['contact_number'],
                    'name' => $userinfo['first_name'] . ' ' . $userinfo['last_name'],
                    'unsubscribed_from_emails' => false,
                    'custom_attributes' => $opt,
                ]);
                $user = $resp->id;
            }
        } catch (Exception $e) {
            return;
        }
        if (empty($user)) {
            return;
        }
        $client->contacts->update($user, ['custom_attributes' => $opt]);
    }

    public function getEvents(): array
    {
        $client = $this->client('2.3');
        try {
            $events = $client->events->getEvents(['user_id' => 800]);
            return ['status' => true, 'message' => 'Your message is sent successfully.', 'events' => $events];
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function createEvents(array $opt): array
    {
        $client = $this->client('2.3');
        try {
            $client->events->create($opt);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
        return ['status' => true, 'message' => 'Your event is created successfully.'];
    }

    public function updateContact(string $user, array $opt)
    {
        $client = $this->client('2.11');
        try {
            return $client->contacts->update($user, $opt);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getContact(string $id)
    {
        $client = $this->client('2.11');
        try {
            return $client->contacts->getContact($id);
        } catch (Exception $e) {
            return [];
        }
    }

    public function createLead(array $opt)
    {
        $client = $this->client('2.3');
        try {
            return $client->leads->create($opt);
        } catch (Exception $e) {
            return [];
        }
    }

    public function createUserTag(string $user, array $opt)
    {
        $client = $this->client('2.3');
        try {
            $path = "contacts/$user/tags";
            return $client->post($path, $opt);
        } catch (Exception $e) {
            return [];
        }
    }

    public function createTag(array $opt)
    {
        $client = $this->client('2.3');
        try {
            return $client->tags->tag($opt);
        } catch (Exception $e) {
            return [];
        }
    }

    public function searchLead(array $opt)
    {
        $client = $this->client('2.3');
        try {
            return $client->contacts->search($opt);
        } catch (Exception $e) {
            return [];
        }
    }

    public function convertLead(array $opt)
    {
        $client = $this->client('1.1');
        try {
            return $client->leads->convertLead($opt);
        } catch (Exception $e) {
            return [];
        }
    }

    public function createTicket(array $userinfo, $ticket_type_id, string $title = '', string $description = ''): ?array
    {
        $client = $this->client('2.0');
        $user = $this->resolveContact($client, $userinfo);
        if (empty($user)) {
            return null;
        }

        try {
            $clientObj = new IntercomSDK(config('services.intercom.access_token'), null, ['Intercom-Version:2.13', 'Content-Type: application/json']);
            $ticket = [
                'ticket_type_id' => $ticket_type_id,
                'contacts' => [['id' => $user]],
                'ticket_attributes' => [
                    '_default_title_' => $title,
                    '_default_description_' => $description,
                ],
            ];
            return $clientObj->post('tickets', $ticket);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateTicketSatatus(string $ticketid): ?array
    {
        try {
            $clientObj = new IntercomSDK(config('services.intercom.access_token'), null, ['Intercom-Version:2.13', 'Content-Type: application/json']);
            return $clientObj->put('tickets/' . $ticketid, ['open' => false]);
        } catch (Exception $e) {
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    public function sendMessageAsRenter(array $userinfo, string $msg): ?array
    {
        $client = $this->client('2.3');
        $user = $this->resolveContactExtended($client, $userinfo);
        if (empty($user)) {
            return null;
        }

        $clientV14 = $this->client('1.4');
        try {
            $clientV14->conversations->replyToLastConversation([
                'intercom_user_id' => $user,
                'body' => $msg,
                'type' => 'user',
                'user_id' => $user,
                'message_type' => 'comment',
            ]);
        } catch (Exception $e) {
            $client->messages->create([
                'message_type' => 'inapp',
                'body' => $msg,
                'to' => ['type' => 'admin', 'id' => $this->adminId()],
                'from' => ['type' => 'user', 'id' => $user],
            ]);
        }

        return ['status' => true, 'message' => 'Your message is sent successfully.'];
    }
}
