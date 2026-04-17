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
                    ["id" => 1, "category_id" => 1, "question" => "How much does it cost to start?", "answer" => "<p>You get to choose the initial fee--anything from $500-$1500. The more you start with, the lower your weekly/monthly rate will be. That is all that is required to book the vehicle.</p><p>The program is prepaid. When you pick up the car, you also will be prepaying for 1 week of usage and insurance.</p><p>*We can also schedule payments in the future to align with your pay dates--just an FYI. Altogether, budget for around $500 to start, but this could be more or less depending on the car you choose. After you've selected your car, the final screen in the app will tell you what is due.</p><p>All payments are collected through the mobile app.</p><p>And, remember, this isn't an auto loan nor the monthly payment you are committing to. Instead of paying a down payment all at once, you get some months to build it up--and, you get to try the car out while doing so!</p>", "category_name" => "Pricing and Payment"],
                    ["id" => 2, "category_id" => 1, "question" => "What is the monthly payment?", "answer" => "<p>The amount you pay each week or month depends on the selling price of the car and how much you want to pay to start with. The more you start with, the lower the daily/monthly rate will be. Also, our program will aim to build you a 20% down payment within 6 months while driving the car. So, the more expensive the car, the more down payment you need to build and vice versa.</p><p>Regarding miles, if you drive more than 1000 per month, you will pay more usage fees, but this will simply get you to your goal quicker. These payments count towards your down payment too.</p><p>Find the vehicle you are interested in and click through to the pricing screen. You'll see the break-down.</p>", "category_name" => "Pricing and Payment"],
                    ["id" => 3, "category_id" => 1, "question" => "Can I use a Debit Card?", "answer" => "You are welcome use EITHER a Debit or Credit card. Both are welcome. However, prepaid cards are typically not allowed.", "category_name" => "Pricing and Payment"],
                    ["id" => 4, "category_id" => 2, "question" => "How long is the program?", "answer" => "<p>The standard DriveItAway program is 6 months long and will build you a 20% down payment within that time. If you happen to drive more than 1000 miles per month, more usage fees will be incurred, but these will count towards your ownership goal. This will get you to your goal quicker.</p><p>In either case, if you wanted a shorter program, we could customize something for you.</p>", "category_name" => "Program"],
                    ["id" => 5, "category_id" => 2, "question" => "How does DriveItAway work?", "answer" => "<p>DriveItAway is a drive before you buy program that requires no down payment or credit to begin. You pay for the use of the car and your payments will count towards your down payment if you decide to commit.</p><p>Basically, you get to build a down payment while driving and deciding if the car is for you.</p><p>Once you've built enough down payment, we can help you qualify for a loan to then buy the car where it's then yours!</p><p>The best way to proceed is browse vehicles in the mobile app; find one that works for you; customize your pricing; and place the booking. It's truly that simple.</p>", "category_name" => "Program"],
                    ["id" => 6, "category_id" => 2, "question" => "Do the rental car fees go towards ownership?", "answer" => "<p>The DriveItAway program allows you to build down payment while driving. With each prepaid rental payment, the majority is treated as \"potential down payment.\" At the point that you are able to qualify for an auto loan using this down payment, the dealer reclassifies the rental payments as a cash credit towards your car purchase. Said more simply, your payments will transfer over in the form of a credit when you decide to convert to ownership.</p>", "category_name" => "Program"],
                    ["id" => 7, "category_id" => 2, "question" => "Is insurance included and can I use my own?", "answer" => "Insurance is included with the program, but it is a separate charge--same as if you had your own lease. While in the program, this insurance will be used. Once you reach your down payment goal and you can qualify for a loan and buy the car, you can then get and use your own insurance.", "category_name" => "Program"],
                    ["id" => 8, "category_id" => 2, "question" => "How do the miles work?", "answer" => "The program is Pay-As-You-Go with regards to both rent and insurance. The base rate includes 1000 miles per month. With the rental charges, if you drive more than the allowed miles, you pay a fee per mile, but this will count towards your ownership. So, if you drive more, you'll get to your down payment goal quicker. Said differently, if you intend to keep the car, you'll own it quicker. It wouldn't be fair to the dealer to get a car back with tons of miles added and the driver didn't intend to purchase it. Hope that makes sense!", "category_name" => "Program"],
                    ["id" => 9, "category_id" => 2, "question" => "How many miles are allowed?", "answer" => "<p>The program is based on a Pay-As-You-Go model. You are allowed 1000 miles per month with the base rental and insurance fees (base rates are reasonably low) and then you are billed per mile thereafter.</p><p>If you choose your vehicle in the app, go to the Build Program page to see the fee structure related to that vehicle.</p>", "category_name" => "Program"],
                    ["id" => 10, "category_id" => 2, "question" => "In maintenance included?", "answer" => "Yes, all routine maintenance is covered while you are in the DriveItAway program.", "category_name" => "Program"],
                    ["id" => 11, "category_id" => 2, "question" => "What types of cars are available?", "answer" => "The best way to see is to search within the mobile app. In any given market, there are sedans, SUVs, minivans, and sometimes trucks and coupes. The vehicle years and odometers range as well. It really differs market by market, and what dealers in different areas are offering. Let us know if you have any trouble finding what you are looking for.", "category_name" => "Program"],
                    ["id" => 12, "category_id" => 3, "question" => "Does credit score matter?", "answer" => "DriveItAway doesn't require any particular credit score. The program is based mostly on the income you can prove.", "category_name" => "Policy and Qualification"],
                    ["id" => 13, "category_id" => 3, "question" => "What documentation do you need?", "answer" => "<p>Prior to picking up the car, you are welcome to add your pay stubs and other types of proof of income, but it's not required. These will be needed by the time you are ready to convert to owning the car.</p><p>When picking up the car, you'll need your driver's license and the debit/credit card you are using that matches the name on your driver's license.</p>", "category_name" => "Policy and Qualification"],
                    ["id" => 14, "category_id" => 3, "question" => "What are the qualifications?", "answer" => "<p>There are three basic qualifications:</p><ol><li>i) We run a Motor Vehicle background check to qualify for our insurance coverage/provider to make sure you are a safe driver.</li><li>i) You must be at least 25 or older and have a valid driver's license.</li><li>ii) You must show an income of $3000 or more per month.</li></ol>", "category_name" => "Policy and Qualification"],
                    ["id" => 15, "category_id" => 3, "question" => "What is the refund policy?", "answer" => "<p>Booking your DriveItAway vehicle is a reservation. If you decide not to pick up the vehicle for any reason or \"DriveItAway,\" everything is 100% refundable.</p><p>If you pick up the car and start the program, you pay-as-you-go like any other rental, and you only pay while you keep the car. This will last for as long as you have the car and until you've built enough down payment. If the car is returned, any planned/future charges will be voided.</p>", "category_name" => "Policy and Qualification"],
                    ["id" => 16, "category_id" => 3, "question" => "Can I change cars?", "answer" => "<p>If you need to change cars for some mechanical reason or some true fault of the car, the dealer can likely make concessions to have amounts you've paid apply to another vehicle. However, if it is just a matter of personal choice, usually the initial fee can transfer, but any other usage fees would stay with that car to pay for the depreciation, wear and tear, etc.</p><p>It is still much easier than trying to turn back a car you've technically bought. With this program, because it is still considered a rental up to the point that you've officially bought it, these types of situations are much easier to deal with.</p>", "category_name" => "Policy and Qualification"],
                    ["id" => 17, "category_id" => 3, "question" => "Can I use the car for Uber or other rideshare or delivery services?", "answer" => "<p>With DriveItAway, you are free to use the car as you please, meaning you can use it for personal use or rideshare/delivery (Uber, Lyft, Postmates, Instacart, Doordash, Grubhub, Amazon, etc.) The program has a Pay-As-You-Go structure when it comes to both rental and insurance. All types of rental/usage fees count towards your ownership. If you drive more, you'll simply build the down payment quicker.</p><p>That being said, DriveItAway is a drive before you buy program that requires no down payment or credit to begin. You pay for use of the car and your payments will count towards the purchase if you decide to commit.</p><p>The best next step is to start browsing vehicles and find your car!</p>", "category_name" => "Policy and Qualification"],
                    ["id" => 18, "category_id" => 3, "question" => "Do I have to be in that state?", "answer" => "There isn't necessarily a state-line requirement, but you need to live relatively close to the dealer you are picking up the vehicle from. We try to match customers within 50-75 miles of the dealer. In the rare instance the car needs to be fixed or towed, the dealer wants to have closer access to the vehicle and not \"have to tow it from across the country.\"", "category_name" => "Policy and Qualification"],
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
                ["name" => "Andres DLC", "thumbnail" => "https://play-lh.googleusercontent.com/a-/AOh14GhcmE18Mri-uXXw_Dpu9U8Qu7z3AbF0-jFxnXj8LDY=w96-h96-n-rw", "short" => "I've been renting cars with ubers rental program and lyfts as well for the last 4 yrs. I've also rented directly through avis,enterprise,and hertz through..", "detail" => "I've been renting cars with ubers rental program and lyfts as well for the last 4 yrs. I've also rented directly through avis,enterprise,and hertz through the same time frame and nothing to show for it until I came across \"drive it away's\" app. Since the second I made an account on their app the staff has been very helpful in answering all my questions fast and making my experience a memoriable. Finally a company that I can select a newer car and all my payments renting will be towards my car", "rating" => 5],
                ["name" => "Steven Funderburk", "thumbnail" => "https://play-lh.googleusercontent.com/a-/AOh14GhfRQNffKmuTJRlxAY3OlqSjkd_rZsRIYhlR66dsA=w96-h96-n-rw", "short" => "This program finally got me out of being stuck spending money on rental cars and having the money go no were. If you dont mind working and you..", "detail" => "This program finally got me out of being stuck spending money on rental cars and having the money go no were. If you dont mind working and you need a car this is the program for you. I have been an uber driver for 4 years and have spent so much money on rentals just to see it disappear. Now i can actually own a car with no pressure.. Thank you drive it away for getting me out of that cycle.", "rating" => 5],
                ["name" => "Abraham Diaz", "thumbnail" => "https://play-lh.googleusercontent.com/a-/AOh14Ghd7O-PSSrenVzr-LsR9fr5QE3izgTJ6KFe9CCvvQ=w96-h96-n-rw", "short" => "I give them a rightous 5 star! The vehicle I got was brand new and I made sure to keep it that way. The rental company affiliated with..", "detail" => "I give them a rightous 5 star! The vehicle I got was brand new and I made sure to keep it that way. The rental company affiliated with DriveItAway was very courteous and their service team is top notch. I have used them before on several occasions and wasn't disappointed on using their services this time around. Lastly the livechat feature is very responsive and the in app customer service team is also exceptional.", "rating" => 5],
                ["name" => "Stephan Sherard", "thumbnail" => "https://play-lh.googleusercontent.com/a/AATXAJwuVnNxk6pLEkW6mPVaKy2uJ9LdjGe8llMqoaz2=w96-h96-n-rw-mo", "short" => "Great Customer Service and Great Prices for vehicles and very Easy to use app. You guys are Amazing. Thank You So Much.", "detail" => "Great Customer Service and Great Prices for vehicles and very Easy to use app. You guys are Amazing. Thank You So Much.", "rating" => 5],
            ]
        ];
    }

    public function postContactUs($postData) {
        $data = is_array($postData) ? $postData : json_decode($postData, true);
        $dataValues = $data['data'] ?? $data;

        $recordType = "Driver";
        if (($dataValues['iam'] ?? '') == '0121U000000EnwR') {
            $recordType = "Dealer";
        } elseif (($dataValues['iam'] ?? '') == '0121U000000Erzx') {
            $recordType = "Other";
        }

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
                ["title" => "Flexible Lease", "icon" => 'owner_ship.png', "content" => "DriveItAway is a Vehicle Flexible Lease program where you Pay-As-You-Go. Any credit is welcome. Lease for 1 month at a time. At the end of each cycle, you can renew, purchase, or return. Your choice!"],
                ["title" => "Drive Down The Price of the Car", "icon" => 'owner_ship2.png', "content" => "Each usage payment will reduce the selling price of the car, if you choose to buy it. Drive the car down to a price where you can get financing to buy the vehicle."],
                ["title" => "Receive Loan Offers Along The Way", "icon" => 'owner_ship3.png', "content" => "While driving, you may get loan offers from lenders to convert to an auto loan once you’ve reached a buyout price that makes sense based on your credit and income"],
                ["title" => "Qualify Based On Income Not Credit", "icon" => 'product_dtl.png', "content" => "DriveItAway has a credit-agnostic approach that will allow you to drive based on your income and budget. Credit does not matter at this point."],
                ["title" => "Full Warranty", "icon" => 'owner_ship4.png', "content" => "High quality vehicles are listed in DriveItAway. All vehicles are fully covered under warranty while in the program."]
            ]
        ];
    }
}
