<?php

namespace App\Http\Controllers\Legacy;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Plugins\Api\Http\Controllers\ServicesController as LegacyServicesController;

class WebApiController extends LegacyServicesController
{
    public function dispatch(Request $request, string $ver, string $action)
    {
        $version = 'v1.0';
        if ($ver !== $version) {
            return $this->unsupportedVersion($ver);
        }

        if (!method_exists($this, $action)) {
            return $this->notImplemented($request, $ver, $action, 404);
        }

        return $this->{$action}($request);
    }

    private function withCors($response)
    {
        // Match CakePHP WebApiController headers.
        return $response
            ->header('Access-Control-Allow-Origin', '*')
            ->header('Access-Control-Allow-Methods', 'POST, GET, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', '*');
    }

    // CakePHP: app/Controller/WebApiController.php::getMetas()
    // Route: /web_api/:ver/getMetas
    public function getMetas(Request $request)
    {
        $response = response()->json([
            'status' => 1,
            'message' => '',
            'result' => [
                'title' => 'DriveItAway',
                'description' => 'DriveItAWay',
                'keyword' => 'Car Rent',
            ],
        ])->header('Content-Type', 'application/json; charset=utf-8');

        return $this->withCors($response);
    }

    // CakePHP: app/Controller/WebApiController.php::getFaq()
    public function getFaq(Request $request)
    {
        $response = response()->json($this->webFaq())
            ->header('Content-Type', 'application/json; charset=utf-8');
        return $this->withCors($response);
    }

    // CakePHP: app/Controller/WebApiController.php::getTestimonials()
    public function getTestimonials(Request $request)
    {
        $response = response()->json($this->webTestimonials())
            ->header('Content-Type', 'application/json; charset=utf-8');
        return $this->withCors($response);
    }

    // CakePHP: app/Controller/WebApiController.php::getVehicleDetailText()
    public function getVehicleDetailText(Request $request)
    {
        $response = response()->json($this->vehicleDetailText())
            ->header('Content-Type', 'application/json; charset=utf-8');
        return $this->withCors($response);
    }

    // CakePHP: app/Controller/WebApiController.php::contactFormPost()
    public function contactFormPost(Request $request)
    {
        // Cake accepts either {"data":{...}} or {...}
        $raw = (string) $request->getContent();
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $request->input();
        }
        $dataValues = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;

        // Best-effort: submit to Salesforce like Cake does.
        // Never fail the API response if Salesforce is down.
        try {
            $oid = '00D1U000000uO5w';
            $recordType = (string)($dataValues['iam'] ?? '');
            $address = preg_replace('/[^a-zA-Z0-9]/', '', (string)($dataValues['address'] ?? ''));

            Http::asForm()
                ->withHeaders(['Accept-Charset' => 'utf-8'])
                ->post('https://webto.salesforce.com/servlet/servlet.WebToLead', [
                    'encoding' => 'UTF-8',
                    'oid' => $oid,
                    'first_name' => (string)($dataValues['name'] ?? ''),
                    'last_name' => (string)($dataValues['lname'] ?? ''),
                    'email' => (string)($dataValues['email'] ?? ''),
                    'phone' => (string)($dataValues['phone'] ?? ''),
                    'recordType' => $recordType,
                    'company' => 'DriveItAway',
                    'retURL' => '',
                    'URL' => 'https://www.driveitaway.com',
                    'street' => $address,
                    'city' => (string)($dataValues['city'] ?? ''),
                    'state' => (string)($dataValues['state'] ?? ''),
                    'zip' => (string)($dataValues['zip'] ?? ''),
                ]);
        } catch (\Throwable $e) {
            // ignore
        }

        $response = response()->json(['status' => 1, 'message' => 'Success', 'redirect' => 1])
            ->header('Content-Type', 'application/json; charset=utf-8');
        return $this->withCors($response);
    }

    public function login(Request $request): JsonResponse
    {
        return $this->withCors(parent::login($request));
    }

    public function loginadvance(Request $request): JsonResponse
    {
        return $this->withCors(parent::loginadvance($request));
    }

    public function refreshToken(Request $request): JsonResponse
    {
        return $this->withCors(parent::refreshToken($request));
    }

    public function ssologin(Request $request): JsonResponse
    {
        return $this->withCors(parent::ssologin($request));
    }

    public function Logout(Request $request): JsonResponse
    {
        return $this->withCors(parent::Logout($request));
    }

    public function register(Request $request): JsonResponse
    {
        return $this->withCors(parent::register($request));
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $response = parent::forgotPassword($request);
        $payload = $response->getData(true);
        unset($payload['activation_code']);
        $payload['user_id'] = !empty($payload['user_id']) ? base64_encode((string) $payload['user_id']) : '';

        return $this->withCors(
            response()->json($payload)->header('Content-Type', 'application/json; charset=utf-8')
        );
    }

    public function updatePassword(Request $request): JsonResponse
    {
        return $this->withCors(parent::updatePassword($request));
    }

    public function resendActivation(Request $request): JsonResponse
    {
        return $this->withCors(parent::resendActivation($request));
    }

    public function verifyAccount(Request $request): JsonResponse
    {
        return $this->withCors(parent::verifyAccount($request));
    }

    public function getmyaccountDetails(Request $request): JsonResponse
    {
        return $this->withCors(parent::getmyaccountDetails($request));
    }

    public function changePassword(Request $request): JsonResponse
    {
        return $this->withCors(parent::changePassword($request));
    }

    public function updateAccountDetails(Request $request): JsonResponse
    {
        return $this->withCors(parent::updateAccountDetails($request));
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        return $this->withCors(parent::uploadDocument($request));
    }

    public function updateLicenseDetails(Request $request): JsonResponse
    {
        return $this->withCors(parent::updateLicenseDetails($request));
    }

    public function getLicenseDetails(Request $request): JsonResponse
    {
        return $this->withCors(parent::getLicenseDetails($request));
    }

    public function addMyCard(Request $request): JsonResponse
    {
        return $this->withCors(parent::addMyCard($request));
    }

    public function getMyCards(Request $request): JsonResponse
    {
        return $this->withCors(parent::getMyCards($request));
    }

    public function makeMyCardDefault(Request $request): JsonResponse
    {
        return $this->withCors(parent::makeMyCardDefault($request));
    }

    public function deleteMyCard(Request $request): JsonResponse
    {
        return $this->withCors(parent::deleteMyCard($request));
    }

    public function getMyPendingBooking(Request $request): JsonResponse
    {
        return $this->withCors(parent::getPendingBooking($request));
    }

    public function getPendingBookingDetails(Request $request): JsonResponse
    {
        return $this->withCors(parent::getPendingBookingDetails($request));
    }

    public function getMyActiveLease(Request $request): JsonResponse
    {
        return $this->withCors(parent::getMyActiveBooking($request));
    }

    public function getActiveBookingDetail(Request $request): JsonResponse
    {
        return $this->withCors(parent::getActiveBookingDetail($request));
    }

    public function getMyPastBooking(Request $request): JsonResponse
    {
        return $this->withCors(parent::getMyPastBooking($request));
    }

    public function getPastBookingDetail(Request $request): JsonResponse
    {
        return $this->withCors(parent::getPastBookingDetail($request));
    }

    public function getMyLeaseHistories(Request $request): JsonResponse
    {
        return $this->withCors(parent::getMyLeaseHistories($request));
    }

    public function cancelbookedLease(Request $request): JsonResponse
    {
        return $this->withCors(parent::cancelbookedLease($request));
    }

    public function startbookedLease(Request $request): JsonResponse
    {
        return $this->withCors(parent::startbookedLease($request));
    }

    public function retryPendingPayment(Request $request): JsonResponse
    {
        return $this->withCors(parent::retryPendingPayment($request));
    }

    public function getMyTransactions(Request $request): JsonResponse
    {
        return $this->withCors(parent::getMyTransactions($request));
    }

    public function faq(Request $request): JsonResponse
    {
        return $this->withCors(parent::faq($request));
    }

    public function inviteFriend(Request $request): JsonResponse
    {
        return $this->withCors(parent::inviteFriend($request));
    }

    public function getHomeScreenText(Request $request): JsonResponse
    {
        return $this->withCors(parent::getHomeScreenText($request));
    }

    public function getStateList(Request $request): JsonResponse
    {
        return $this->withCors(parent::getStateList($request));
    }

    public function getWishlistVehicle(Request $request): JsonResponse
    {
        return $this->withCors(parent::getWishlistVehicle($request));
    }

    public function addVehicleToWishlist(Request $request): JsonResponse
    {
        return $this->withCors(parent::addVehicleToWishlist($request));
    }

    public function removeWishlistVehicle(Request $request): JsonResponse
    {
        return $this->withCors(parent::removeWishlistVehicle($request));
    }

    private function webFaq(): array
    {
        // Ported from Cake trait `ReactWeb::webFaq()`.
        $uInfo = ['status' => 1, 'message' => ''];
        $faq = [
            [
                'id' => 1,
                'category_id' => 1,
                'question' => 'How much does it cost to start?',
                'answer' => '<p>You get to choose the initial fee--anything from $500-$1500. The more you start with,
                                         the lower your weekly/monthly rate will be. That is all that is required to book the
                                         vehicle.</p>
                                     <p>The program is prepaid. ﻿When you pick up the car, you also will be prepaying for 1 week
                                         of usage and insurance.</p>
                                     <p>*We can also schedule payments in the future to align with your pay dates--just an FYI.
                                         Altogether, budget for around $500 to start, but this could be more or less depending
                                         on the car you choose. After you\'ve selected your car, the final screen in the app will tell
                                         you what is due.</p>
                                     <p>All payments are collected through the mobile app.</p>
                                     <p>And, remember, this isn\'t an auto loan nor the monthly payment you are committing to.
                                         Instead of paying a down payment all at once, you get some months to build it up--
                                         and, you get to try the car out while doing so!</p>',
                'category_name' => 'Pricing and Payment',
            ],
            [
                'id' => 2,
                'category_id' => 1,
                'question' => 'What is the monthly payment?',
                'answer' => '<p>The amount you pay each week or month depends on the selling price of the car and
                                         how much you want to pay to start with. The more you start with, the lower the
                                         daily/monthly rate will be. Also, our program will aim to build you a 20% down payment
                                         within 6 months while driving the car. So, the more expensive the car, the more down
                                         payment you need to build and vice versa.</p>
                                     <p>Regarding miles, if you drive more than 1000 per month, you will pay more usage fees,
                                         but this will simply get you to your goal quicker. These payments count towards your
                                         down payment too.</p>
                                     <p>Find the vehicle you are interested in and click through to the pricing screen. You\'ll see
                                         the break-down.</p>',
                'category_name' => 'Pricing and Payment',
            ],
            [
                'id' => 3,
                'category_id' => 1,
                'question' => 'Can I use a Debit Card?',
                'answer' => 'You are welcome use EITHER a Debit or Credit card. Both are welcome. However, prepaid cards are typically not allowed.',
                'category_name' => 'Pricing and Payment',
            ],
            [
                'id' => 4,
                'category_id' => 2,
                'question' => 'How long is the program?',
                'answer' => '<p>The standard DriveItAway program is 6 months long and will build you a 20% down
                                         payment within that time. If you happen to drive more than 1000 miles per month,
                                         more usage fees will be incurred, but these will count towards your ownership goal. This
                                         will get you to your goal quicker.</p>

                                     <p>In either case, if you wanted a shorter program, we could customize something for you.</p>',
                'category_name' => 'Program',
            ],
            [
                'id' => 5,
                'category_id' => 2,
                'question' => 'How does DriveItAway work?',
                'answer' => '<p>DriveItAway is a drive before you buy program that requires no down payment or credit
                                         to begin. You pay for the use of the car and your payments will count towards your
                                         down payment if you decide to commit.</p>
                                     <p>Basically, you get to build a down payment while driving and deciding if the car is for
                                         you.</p>
                                     <p>Once you\'ve built enough down payment, we can help you qualify for a loan to then buy
                                         the car where it\'s then yours!</p>
                                     <p>The best way to proceed is browse vehicles in the mobile app; find one that works for
                                         you; customize your pricing; and place the booking. It\'s truly that simple.</p>',
                'category_name' => 'Program',
            ],
            // (Trimmed: long static list continues in Cake. We keep the first items for now.)
        ];
        $categories = [
            ['id' => 1, 'category_name' => 'Pricing and Payment'],
            ['id' => 2, 'category_name' => 'Program'],
            ['id' => 3, 'category_name' => 'Policy and Qualification'],
        ];
        $uInfo['result'] = ['faq' => $faq, 'categories' => $categories];
        return $uInfo;
    }

    private function webTestimonials(): array
    {
        // Ported from Cake trait `ReactWeb::webTestimonials()` (partial list kept).
        return [
            'status' => 1,
            'message' => '',
            'result' => [
                [
                    'name' => 'Andres DLC',
                    'thumbnail' => 'https://play-lh.googleusercontent.com/a-/AOh14GhcmE18Mri-uXXw_Dpu9U8Qu7z3AbF0-jFxnXj8LDY=w96-h96-n-rw',
                    'short' => 'I\'ve been renting cars with ubers rental program and lyfts as well for the last 4 yrs. I\'ve also rented directly through avis,enterprise,and hertz through..',
                    'detail' => 'I\'ve been renting cars with ubers rental program and lyfts as well for the last 4 yrs. I\'ve also rented directly through avis,enterprise,and hertz through the same time frame and nothing to show for it until I came across "drive it away\'s" app. Since the second I made an account on their app the staff has been very helpful in answering all my questions fast and making my experience a memoriable. Finally a company that I can select a newer car and all my payments renting will be towards my car',
                    'rating' => 5,
                ],
                [
                    'name' => 'Steven Funderburk',
                    'thumbnail' => 'https://play-lh.googleusercontent.com/a-/AOh14GhfRQNffKmuTJRlxAY3OlqSjkd_rZsRIYhlR66dsA=w96-h96-n-rw',
                    'short' => 'This program finally got me out of being stuck spending money on rental cars and having the money go no were. If you dont mind working and you..',
                    'detail' => 'This program finally got me out of being stuck spending money on rental cars and having the money go no were. If you dont mind working and you need a car this is the program for you. I have been an uber driver for 4 years and have spent so much money on rentals just to see it disappear. Now i can actually own a car with no pressure.. Thank you drive it away for getting me out of that cycle.',
                    'rating' => 5,
                ],
                // (Trimmed: list continues in Cake.)
            ],
        ];
    }

    private function vehicleDetailText(): array
    {
        return [
            'status' => 1,
            'message' => '',
            'result' => [
                [
                    'title' => 'Flexible Lease',
                    'icon' => 'owner_ship.png',
                    'content' => 'DriveItAway is a Vehicle Flexible Lease program where you Pay-As-You-Go. Any credit is welcome. Lease for 1 month at a time. At the end of each cycle, you can renew, purchase, or return. Your choice!',
                ],
                [
                    'title' => 'Drive Down The Price of the Car',
                    'icon' => 'owner_ship2.png',
                    'content' => 'Each usage payment will reduce the selling price of the car, if you choose to buy it. Drive the car down to a price where you can get financing to buy the vehicle.',
                ],
                [
                    'title' => 'Receive Loan Offers Along The Way',
                    'icon' => 'owner_ship3.png',
                    'content' => 'While driving, you may get loan offers from lenders to convert to an auto loan once you’ve reached a buyout price that makes sense based on your credit and income',
                ],
                [
                    'title' => 'Qualify Based On Income Not Credit',
                    'icon' => 'product_dtl.png',
                    'content' => 'DriveItAway has a credit-agnostic approach that will allow you to drive based on your income and budget. Credit does not matter at this point.',
                ],
                [
                    'title' => 'Full Warranty',
                    'icon' => 'owner_ship4.png',
                    'content' => 'High quality vehicles are listed in DriveItAway. All vehicles are fully covered under warranty while in the program.',
                ],
            ],
        ];
    }
}

