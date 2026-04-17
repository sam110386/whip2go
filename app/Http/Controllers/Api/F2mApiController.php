<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Free2Move API Controller - wraps ServicesController with F2M-specific logic.
 *
 * Delegates to ServicesController for core business logic while applying
 * Free2Move branding and version constraints. Version-check and X-Security
 * header validation should be handled by middleware.
 */
class F2mApiController extends Controller
{
    private string $version = 'v3.0';

    protected function services(): ServicesController
    {
        return app(ServicesController::class);
    }

    /* ------------------------------------------------------------------ */
    /*  Auth                                                               */
    /* ------------------------------------------------------------------ */

    public function login(Request $request): JsonResponse
    {
        return $this->services()->login($request);
    }

    public function loginadvance(Request $request): JsonResponse
    {
        return $this->services()->loginadvance($request);
    }

    public function refreshToken(Request $request): JsonResponse
    {
        return $this->services()->refreshToken($request);
    }

    public function Logout(Request $request): JsonResponse
    {
        return response()->json(['status' => 1, 'message' => '']);
    }

    public function register(Request $request): JsonResponse
    {
        return $this->services()->register($request);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        return $this->services()->forgotPassword($request);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        return $this->services()->updatePassword($request);
    }

    public function resendActivation(Request $request): JsonResponse
    {
        return $this->services()->resendActivation($request);
    }

    public function verifyAccount(Request $request): JsonResponse
    {
        return $this->services()->verifyAccount($request);
    }

    public function ssologin(Request $request): JsonResponse
    {
        return $this->services()->ssologin($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Account / Profile                                                  */
    /* ------------------------------------------------------------------ */

    public function getmyaccountDetails(Request $request): JsonResponse
    {
        return $this->services()->getmyaccountDetails($request);
    }

    public function changePassword(Request $request): JsonResponse
    {
        return $this->services()->changePassword($request);
    }

    public function updateAccountDetails(Request $request): JsonResponse
    {
        return $this->services()->updateAccountDetails($request);
    }

    public function uploadDocument(Request $request): JsonResponse
    {
        try {
            return $this->services()->uploadDocument($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Upload failed']);
        }
    }

    public function updateLicenseDetails(Request $request): JsonResponse
    {
        return $this->services()->updateLicenseDetails($request);
    }

    public function addLicenseDetails(Request $request): JsonResponse
    {
        return $this->services()->addLicenseDetails($request);
    }

    public function getLicenseDetails(Request $request): JsonResponse
    {
        return $this->services()->getLicenseDetails($request);
    }

    public function getMySignature(Request $request): JsonResponse
    {
        return $this->services()->getMySignature($request);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        return $this->services()->deleteMyAccount($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Cards / Stripe                                                     */
    /* ------------------------------------------------------------------ */

    public function addMyCard(Request $request): JsonResponse
    {
        return $this->services()->addMyCard($request);
    }

    public function getMyCards(Request $request): JsonResponse
    {
        return $this->services()->getMyCards($request);
    }

    public function makeMyCardDefault(Request $request): JsonResponse
    {
        return $this->services()->makeMyCardDefault($request);
    }

    public function deleteMyCard(Request $request): JsonResponse
    {
        return $this->services()->deleteMyCard($request);
    }

    public function getStripeUrl(Request $request): JsonResponse
    {
        return $this->services()->getStripeUrl($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Bookings                                                           */
    /* ------------------------------------------------------------------ */

    public function getMyActiveLease(Request $request): JsonResponse
    {
        return $this->services()->getMyActiveBooking($request);
    }

    public function getMyPendingBooking(Request $request): JsonResponse
    {
        return $this->services()->getPendingBooking($request);
    }

    public function cancelbookedLease(Request $request): JsonResponse
    {
        return $this->services()->cancelbookedLease($request);
    }

    public function startbookedLease(Request $request): JsonResponse
    {
        return $this->services()->startbookedLease($request);
    }

    public function completebookedLease(Request $request): JsonResponse
    {
        return $this->services()->completebookedLease($request);
    }

    public function getMyLeaseHistories(Request $request): JsonResponse
    {
        return $this->services()->getMyLeaseHistories($request);
    }

    public function getPendingBookingDetails(Request $request): JsonResponse
    {
        return $this->services()->getPendingBookingDetails($request);
    }

    public function getActiveBookingDetail(Request $request): JsonResponse
    {
        return $this->services()->getActiveBookingDetail($request);
    }

    public function getMyPastBooking(Request $request): JsonResponse
    {
        return $this->services()->getMyPastBooking($request);
    }

    public function getPastBookingDetail(Request $request): JsonResponse
    {
        return $this->services()->getPastBookingDetail($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Vehicle Docs / Inspections                                         */
    /* ------------------------------------------------------------------ */

    public function addVehicleDoc(Request $request): JsonResponse
    {
        return $this->services()->addVehicleDoc($request);
    }

    public function getinsurancetoken(Request $request): JsonResponse
    {
        return $this->services()->getinsurancetoken($request);
    }

    public function checkVinDetails(Request $request): JsonResponse
    {
        return $this->services()->checkVinDetails($request);
    }

    public function getVehicleRegistration(Request $request): JsonResponse
    {
        return $this->services()->getVehicleRegistration($request);
    }

    public function getVehicleInspection(Request $request): JsonResponse
    {
        return $this->services()->getVehicleInspection($request);
    }

    public function myVehicleInspection(Request $request): JsonResponse
    {
        return $this->services()->myVehicleInspection($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Reviews                                                            */
    /* ------------------------------------------------------------------ */

    public function quoteAgreement(Request $request): JsonResponse
    {
        return $this->services()->quoteAgreement($request);
    }

    public function getInitialReview(Request $request): JsonResponse
    {
        return $this->services()->getInitialReview($request);
    }

    public function addInitialReview(Request $request): JsonResponse
    {
        return $this->services()->addInitialReview($request);
    }

    public function initFinalReview(Request $request): JsonResponse
    {
        return $this->services()->initFinalReview($request);
    }

    public function uploadFinaleReviewImage(Request $request): JsonResponse
    {
        return $this->services()->uploadFinaleReviewImage($request);
    }

    public function saveFinaleReview(Request $request): JsonResponse
    {
        return $this->services()->saveFinaleReview($request);
    }

    public function removeReviewImage(Request $request): JsonResponse
    {
        return $this->services()->removeReviewImage($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Support / Issues                                                   */
    /* ------------------------------------------------------------------ */

    public function addMechanicalIssue(Request $request): JsonResponse
    {
        return $this->services()->addMechanicalIssue($request);
    }

    public function uploadSupportIssueImage(Request $request): JsonResponse
    {
        return $this->services()->uploadSupportIssueImage($request);
    }

    public function addAccidentalIssue(Request $request): JsonResponse
    {
        return $this->services()->addAccidentalIssue($request);
    }

    public function pullMyAccidentalIssue(Request $request): JsonResponse
    {
        return $this->services()->pullMyAccidentalIssue($request);
    }

    public function pullAccidentalIssueDetail(Request $request): JsonResponse
    {
        return $this->services()->pullAccidentalIssueDetail($request);
    }

    public function saveAccidentalIssueClaim(Request $request): JsonResponse
    {
        return $this->services()->saveAccidentalIssueClaim($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Payments / Transactions                                            */
    /* ------------------------------------------------------------------ */

    public function retryPeningPayment(Request $request): JsonResponse
    {
        return $this->services()->retryPendingPayment($request);
    }

    public function retryPendingPayment(Request $request): JsonResponse
    {
        return $this->services()->retryPendingPayment($request);
    }

    public function getMyTransactions(Request $request): JsonResponse
    {
        return $this->services()->getMyTransactions($request);
    }

    public function getCreditHealthyInfo(Request $request): JsonResponse
    {
        return $this->services()->getCreditHealthyInfo($request);
    }

    public function getRequestUponPaymentInfo(Request $request): JsonResponse
    {
        return $this->services()->getRequestUponPaymentInfo($request);
    }

    public function getSchedulePayment(Request $request): JsonResponse
    {
        return $this->services()->getSchedulePayment($request);
    }

    public function requestAdvancePayment(Request $request): JsonResponse
    {
        return $this->services()->requestAdvancePayment($request);
    }

    public function makeAdvancePayment(Request $request): JsonResponse
    {
        return $this->services()->makeAdvancePayment($request);
    }

    public function bookingPaymentTerms(Request $request): JsonResponse
    {
        return $this->services()->bookingPaymentTerms($request);
    }

    public function bookingPaymentTermsConfirm(Request $request): JsonResponse
    {
        return $this->services()->bookingPaymentTermsConfirm($request);
    }

    public function requestCreditScore(Request $request): JsonResponse
    {
        return $this->services()->requestCreditScore($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Sample Docs                                                        */
    /* ------------------------------------------------------------------ */

    public function getSampleInsurance(Request $request): JsonResponse
    {
        return $this->services()->getSampleInsurance($request);
    }

    public function getSampleRegistrationDoc(Request $request): JsonResponse
    {
        return $this->services()->getSampleRegistrationDoc($request);
    }

    public function getSampleInspectionnDoc(Request $request): JsonResponse
    {
        return $this->services()->getSampleInspectionnDoc($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Offers                                                             */
    /* ------------------------------------------------------------------ */

    public function getMyOffers(Request $request): JsonResponse
    {
        return $this->services()->getMyOffers($request);
    }

    public function getOfferQuote(Request $request): JsonResponse
    {
        try {
            return $this->services()->getOfferQuote($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Error processing offer quote']);
        }
    }

    public function acceptOffer(Request $request): JsonResponse
    {
        try {
            return $this->services()->acceptOffer($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Error accepting offer']);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Income Verification                                                */
    /* ------------------------------------------------------------------ */

    public function verifyUberLyft(Request $request): JsonResponse
    {
        return $this->services()->linkIncomeSource($request);
    }

    public function linkIncomeSource(Request $request): JsonResponse
    {
        return $this->services()->linkIncomeSource($request);
    }

    public function uploadPaystub(Request $request): JsonResponse
    {
        return $this->services()->uploadPaystub($request);
    }

    public function connectBankAccount(Request $request): JsonResponse
    {
        return $this->services()->connectBankAccount($request);
    }

    public function saveMonthlyIncome(Request $request): JsonResponse
    {
        return $this->services()->saveMonthlyIncome($request);
    }

    public function getMonthlyIncomeAgreement(Request $request): JsonResponse
    {
        return $this->services()->getMonthlyIncomeAgreement($request);
    }

    public function getLoanData(Request $request): JsonResponse
    {
        return $this->services()->getLoanData($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Vehicle Search / Listing                                           */
    /* ------------------------------------------------------------------ */

    public function searchPreAuthVehicles(Request $request): JsonResponse
    {
        return $this->services()->searchVehicles($request);
    }

    public function getPreAuthVehiclePriceDetail(Request $request): JsonResponse
    {
        try {
            return $this->services()->getVehiclePriceDetail($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Error fetching price detail']);
        }
    }

    public function getVehicleFilters(Request $request): JsonResponse
    {
        return $this->services()->getVehicleFilters($request);
    }

    public function searchVehicles(Request $request): JsonResponse
    {
        return $this->services()->searchVehicles($request);
    }

    public function vehicleAutocomplete(Request $request): JsonResponse
    {
        return $this->services()->vehicleAutocomplete($request);
    }

    public function getVehicleDetail(Request $request): JsonResponse
    {
        return $this->services()->getVehicleDetail($request);
    }

    public function getVehiclePriceDetail(Request $request): JsonResponse
    {
        try {
            return $this->services()->getVehiclePriceDetail($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Error fetching price detail']);
        }
    }

    public function getVehicleQuote(Request $request): JsonResponse
    {
        try {
            return $this->services()->getVehicleQuote($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Error generating quote']);
        }
    }

    public function getQuoteAgreement(Request $request): JsonResponse
    {
        return $this->services()->getQuoteAgreement($request);
    }

    public function book(Request $request): JsonResponse
    {
        try {
            return $this->services()->book($request);
        } catch (\Exception $e) {
            report($e);
            return response()->json(['status' => 0, 'message' => 'Error processing booking']);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Agreement / Ravin / Change Plan                                    */
    /* ------------------------------------------------------------------ */

    public function getAgreement(Request $request): JsonResponse
    {
        return $this->services()->getAgreement($request);
    }

    public function getRavinScan(Request $request): JsonResponse
    {
        return $this->services()->getRavinScan($request);
    }

    public function initiateChangePlan(Request $request): JsonResponse
    {
        return $this->services()->initiateChangePlan($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Wishlist                                                           */
    /* ------------------------------------------------------------------ */

    public function getWishlistVehicle(Request $request): JsonResponse
    {
        return $this->services()->getWishlistVehicle($request);
    }

    public function addVehicleToWishlist(Request $request): JsonResponse
    {
        return $this->services()->addVehicleToWishlist($request);
    }

    public function removeWishlistVehicle(Request $request): JsonResponse
    {
        return $this->services()->removeWishlistVehicle($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Home Screen / Static Content                                       */
    /* ------------------------------------------------------------------ */

    public function getHomeScreenText(Request $request): JsonResponse
    {
        $blocks = [
            ['title' => 'How Flexible Leasing Works', 'subtitle' => '', 'type' => 'title'],
            [
                'title' => 'Flexible Lease',
                'subtitle' => 'Vehicle Flexible Leases are Pay-As-You-Go. Any credit is welcome. Lease for 1 month at a time. At the end of each cycle, you can renew, purchase, or return. Your choice!',
                'type' => 'test_ownership',
            ],
            [
                'title' => 'Drive Down The Price of the Car',
                'subtitle' => 'Each usage payment will reduce the selling price of the car, if you choose to buy it. Drive the car down to a price where you can get financing to buy the vehicle.',
                'type' => 'down_payment',
            ],
            [
                'title' => 'Receive Loan Offers Along The Way',
                'subtitle' => "While driving, you may get loan offers from lenders to convert to an auto loan once you've reached a buyout price that makes sense based on your credit and income",
                'type' => 'loan_offer',
            ],
            [
                'title' => 'Qualify Based On Income Not Credit',
                'subtitle' => 'The program has a credit-agnostic approach that will allow you to drive based on your income and budget. Credit does not matter at this point.',
                'type' => 'quality',
            ],
            [
                'title' => 'Full Warranty',
                'subtitle' => 'High quality vehicles are listed from car dealer inventory. All vehicles are fully covered while in the program.',
                'type' => 'warranty',
            ],
        ];

        return response()->json([
            'status' => 1,
            'message' => '',
            'intercom_android_secret' => 'RyfOaoU9ENDIxO6kSUHDV4RUEGqWgNslqbR-ZIeY',
            'intercom_ios_secret' => 'XqNthRtmtyNpd2vp5ZyfpzsGmA2zmHcMC0Yn6DfC',
            'result' => [
                'top_text_1' => 'Flexible <br>Lease <br>To Own',
                'top_text_2' => 'To Own',
                'bottom_text_1' => 'Payments Count Towards Your Purchase',
                'promotion_text' => 'Employer promotions available. Connect with your employer to unlock discounts.',
                'bottom_blocks' => $blocks,
            ],
        ]);
    }

    public function getIntercomCarousel(Request $request): JsonResponse
    {
        return $this->services()->getIntercomCarousel($request);
    }

    public function getCountryCounty(Request $request): JsonResponse
    {
        return $this->services()->getCountryCounty($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Social / Misc                                                      */
    /* ------------------------------------------------------------------ */

    public function inviteFriend(Request $request): JsonResponse
    {
        return $this->services()->inviteFriend($request);
    }

    public function faq(Request $request): JsonResponse
    {
        return $this->services()->faq($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Extensions                                                         */
    /* ------------------------------------------------------------------ */

    public function requestExtension(Request $request): JsonResponse
    {
        return $this->services()->requestExtension($request);
    }

    public function processExtension(Request $request): JsonResponse
    {
        return $this->services()->processExtension($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Uber / Ride-hail                                                   */
    /* ------------------------------------------------------------------ */

    public function findUberCars(Request $request): JsonResponse
    {
        return $this->services()->findUberCars($request);
    }

    public function bookUberCar(Request $request): JsonResponse
    {
        return $this->services()->bookUberCar($request);
    }

    public function getUberBookings(Request $request): JsonResponse
    {
        return $this->services()->getUberBookings($request);
    }

    public function cancelUberCar(Request $request): JsonResponse
    {
        return $this->services()->cancelUberCar($request);
    }

    public function sendTextUberDriver(Request $request): JsonResponse
    {
        return $this->services()->sendTextUberDriver($request);
    }

    public function getUberBookingDetail(Request $request): JsonResponse
    {
        return $this->services()->getUberBookingDetail($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Waitlist / EV / Eland                                              */
    /* ------------------------------------------------------------------ */

    public function addVehicleToWaitlist(Request $request): JsonResponse
    {
        return $this->services()->addVehicleToWaitlist($request);
    }

    public function getEvStation(Request $request): JsonResponse
    {
        return $this->services()->getEvStation($request);
    }

    public function getElandUrl(Request $request): JsonResponse
    {
        return $this->services()->getElandUrl($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Wallet / Coupon                                                    */
    /* ------------------------------------------------------------------ */

    public function getWalletTermText(Request $request): JsonResponse
    {
        return $this->services()->getWalletTermText($request);
    }

    public function acceptWalletTerm(Request $request): JsonResponse
    {
        return $this->services()->acceptWalletTerm($request);
    }

    public function getCouponTerms(Request $request): JsonResponse
    {
        return $this->services()->getCouponTerms($request);
    }

    public function acceptCouponTerms(Request $request): JsonResponse
    {
        return $this->services()->acceptCouponTerms($request);
    }

    /* ------------------------------------------------------------------ */
    /*  IDology / Insurance / Plaid                                        */
    /* ------------------------------------------------------------------ */

    public function pushDataToIdology(Request $request): JsonResponse
    {
        return $this->services()->pushDataToIdology($request);
    }

    public function acceptInsuranceQuote(Request $request): JsonResponse
    {
        return $this->services()->acceptInsuranceQuote($request);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        return $this->services()->applyCoupon($request);
    }

    public function plaidtoken(Request $request): JsonResponse
    {
        return $this->services()->plaidtoken($request);
    }

    public function savePlaidUser(Request $request): JsonResponse
    {
        return $this->services()->savePlaidUser($request);
    }

    /* ------------------------------------------------------------------ */
    /*  Employer Promo / CMM / Insurance                                   */
    /* ------------------------------------------------------------------ */

    public function getEmployerPromoWebView(Request $request): JsonResponse
    {
        return $this->services()->getEmployerPromoWebView($request);
    }

    public function getCMMCard(Request $request): JsonResponse
    {
        return $this->services()->getCMMCard($request);
    }

    public function getInsuranceDetails(Request $request): JsonResponse
    {
        return $this->services()->getInsuranceDetails($request);
    }

    public function updateInsuranceDetails(Request $request): JsonResponse
    {
        return $this->services()->updateInsuranceDetails($request);
    }
}
