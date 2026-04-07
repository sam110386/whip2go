<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait ReactWeb {

    public function webFaq() {
        return [
            'status' => 1,
            "message" => "",
            "result" => [
                "faq" => [
                    ["id" => 1, "category_id" => 1, "question" => "How much does it cost to start?", "answer" => "...", "category_name" => "Pricing and Payment"],
                    ["id" => 5, "category_id" => 2, "question" => "How does DriveItAway work?", "answer" => "...", "category_name" => "Program"],
                ],
                "categories" => [
                    ["id" => 1, "category_name" => "Pricing and Payment"],
                    ["id" => 2, "category_name" => "Program"],
                    ["id" => 3, "category_name" => "Policy and Qualification"],
                ]
            ]
        ];
    }

    public function webTestimonials() {
        return [
            'status' => 1,
            "message" => "",
            "result" => [
                ["name" => "Andres DLC", "rating" => 5, "detail" => "..."],
                ["name" => "Steven Funderburk", "rating" => 5, "detail" => "..."],
            ]
        ];
    }

    public function postContactUs($postData) {
        $data = is_array($postData) ? $postData : json_decode($postData, true);
        $dataValues = $data['data'] ?? $data;

        $recordType = "Driver";
        if (($dataValues['iam'] ?? '') == '0121U000000EnwR') {
            $recordType = "Dealer";
        }

        // Logging the contact attempt
        Log::info("Contact Form Submission: " . json_encode($dataValues));

        // Mail simulation (Laravel way)
        try {
            // Mail::to('adam@whip2go.com')->send(new \App\Mail\ContactUs($dataValues));
            Log::info("Mail: Sending contact us email to adam@whip2go.com");
        } catch (\Exception $e) {
            Log::error("Mail Error: " . $e->getMessage());
        }

        // Salesforce push
        $this->pushToSalesForce($dataValues);

        return ['status' => 1, "message" => "Success", "redirect" => 1];
    }

    private function pushToSalesForce($data) {
        $oid = '00D1U000000uO5w';
        $params = [
            'encoding' => 'UTF-8',
            'oid' => $oid,
            'first_name' => $data['name'] ?? '',
            'last_name' => $data['lname'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'recordType' => $data['iam'] ?? '',
            'company' => 'DriveItAway',
            'street' => preg_replace("/[^a-zA-Z0-9]/", "", $data['address'] ?? ''),
            'city' => $data['city'] ?? '',
            'state' => $data['state'] ?? '',
            'zip' => $data['zip'] ?? '',
            'retURL' => '',
            'URL' => 'https://www.driveitaway.com'
        ];

        try {
            Http::asForm()->post("https://webto.salesforce.com/servlet/servlet.WebToLead", $params);
            Log::info("Salesforce: Pushed lead " . ($data['email'] ?? ''));
        } catch (\Exception $e) {
            Log::error("Salesforce Error: " . $e->getMessage());
        }
    }

    public function VehicleDetailText() {
        return [
            'status' => 1,
            "message" => "",
            "result" => [
                ["title" => "Flexible Lease", "content" => "..."],
                ["title" => "Drive Down The Price of the Car", "content" => "..."],
            ]
        ];
    }
}
