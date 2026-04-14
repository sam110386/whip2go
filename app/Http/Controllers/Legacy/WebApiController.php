<?php

namespace App\Http\Controllers\Legacy;

use App\Http\Controllers\Legacy\Api\ServicesController;
use App\Http\Controllers\Traits\ReactWeb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class WebApiController extends ServicesController
{
    use ReactWeb;

    private $version = 'v1.0';

    private function _getClientUserIp(Request $request)
    {
        if ($request->header('HTTP_CF_CONNECTING_IP')) {
            return $request->header('HTTP_CF_CONNECTING_IP');
        } elseif ($request->header('X-Forwarded-For')) {
            $ipList = explode(',', $request->header('X-Forwarded-For'));
            return trim(end($ipList));
        }
        return $request->ip();
    }

    public function getTestimonials()
    {
        return response()->json($this->webTestimonials());
    }

    public function getMetas()
    {
        return response()->json([
            'status' => 1,
            "message" => "",
            'result' => [
                "title" => "DriveItAway",
                "description" => "DriveItAWay",
                "keyword" => "Car Rent"
            ]
        ]);
    }

    public function getFaq()
    {
        return response()->json($this->webFaq());
    }

    public function contactFormPost(Request $request)
    {
        return response()->json($this->postContactUs($request->all()));
    }

    public function getVehicleDetailText()
    {
        return response()->json($this->VehicleDetailText());
    }

    public function login(Request $request): JsonResponse
    {
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw);
        if (isset($dataValues->referer) && !empty($dataValues->referer) && isset($dataValues->phone_number) && !empty($dataValues->phone_number)) {
            $this->logWidgetEvent('login_button_clicked', $dataValues, $request);
        }
        return parent::login($request);
    }

    public function register(Request $request): JsonResponse
    {
        $raw = (string) $request->getContent();
        $dataValues = json_decode($raw);
        if (isset($dataValues->referer) && !empty($dataValues->referer) && isset($dataValues->phone_number) && !empty($dataValues->phone_number)) {
            $this->logWidgetEvent('register_button_clicked', $dataValues, $request);
        }
        return parent::register($request);
    }

    private function logWidgetEvent($event, $dataValues, $request)
    {
        $logfile = public_path('files/widgets/') . date('Y-m-d') . '_log.jsonl';
        $logDir = dirname($logfile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logData = [
            'event' => $event,
            'vin' => $dataValues->phone_number,
            'referer' => $dataValues->referer,
            'timestamp' => date('c'),
            "ip" => $this->_getClientUserIp($request),
            "user" => ($dataValues->phone_number . "||" . ($dataValues->first_name ?? '') . " " . ($dataValues->last_name ?? ''))
        ];
        $jsonLine = json_encode($logData) . "\n";
        @file_put_contents($logfile, $jsonLine, FILE_APPEND);
    }

    private function pendingResponse(string $action): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'message' => "WebApi::{$action} pending migration",
            'result' => (object)[],
        ]);
    }

    // Delegations to parent ServicesController
    public function loginadvance(Request $request): JsonResponse { return parent::loginadvance($request); }
    public function ssologin(Request $request): JsonResponse { return parent::ssologin($request); }
    public function refreshToken(Request $request): JsonResponse { return parent::refreshToken($request); }
    public function Logout() { return response()->json(["status" => 1, "message" => ""]); }
    public function forgotPassword(Request $request): JsonResponse { return parent::forgotPassword($request); }
    public function updatePassword(Request $request): JsonResponse { return parent::updatePassword($request); }
    public function resendActivation(Request $request): JsonResponse { return parent::resendActivation($request); }
    public function verifyAccount(Request $request): JsonResponse { return parent::verifyAccount($request); }
    public function getmyaccountDetails(Request $request): JsonResponse { return parent::getmyaccountDetails($request); }
    public function changePassword(Request $request): JsonResponse { return parent::changePassword($request); }
    public function updateAccountDetails(Request $request): JsonResponse { return parent::updateAccountDetails($request); }
    public function uploadDocument(Request $request): JsonResponse { return parent::uploadDocument($request); }
    public function updateLicenseDetails(Request $request): JsonResponse { return parent::updateLicenseDetails($request); }
    public function getLicenseDetails(Request $request): JsonResponse { return parent::getLicenseDetails($request); }
    public function addMyCard(Request $request): JsonResponse { return parent::addMyCard($request); }
    public function getMyCards(Request $request): JsonResponse { return parent::getMyCards($request); }
    public function makeMyCardDefault(Request $request): JsonResponse { return parent::makeMyCardDefault($request); }
    public function deleteMyCard(Request $request): JsonResponse { return parent::deleteMyCard($request); }
    public function getMyActiveLease(Request $request): JsonResponse { return parent::getMyActiveLease($request); }
    public function getMyPendingBooking(Request $request): JsonResponse { return parent::getMyPendingBooking($request); }
    public function cancelbookedLease(Request $request): JsonResponse { return parent::cancelbookedLease($request); }
    public function startbookedLease(Request $request): JsonResponse { return parent::startbookedLease($request); }
    public function getMyLeaseHistories(Request $request): JsonResponse { return parent::getMyLeaseHistories($request); }

    // Added for CakePHP action parity during migration
    public function acceptOffer(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function achAgreement(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function achNewAgreement(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function addAccidentalIssue(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function addInitialReview(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function addMechanicalIssue(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function addVehicleToWishlist(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function book(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function chapmanVehicles(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function completebookedLease(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function connectBankAccount(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function createMarketplaceLead(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function faq(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getActiveBookingDetail(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getActiveNonReviwedBooking(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getAgreement(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getChapmanFilters(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getCreditHealthyInfo(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getHomeScreenText(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getInitialReview(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getLoanData(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getMonthlyIncomeAgreement(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getMyOffers(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getMyPastBooking(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getMySignature(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getMyTransactions(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getOfferQuote(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getPastBookingDetail(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getPendingBookingDetails(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getPreAuthVehicleDetail(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getPreAuthVehiclePriceDetail(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getQuoteAgreement(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getRavinScan(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getRequestUponPaymentInfo(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getSampleInspectionnDoc(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getSampleInsurance(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getSampleRegistrationDoc(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getSchedulePayment(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getStateList(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getVehicleFilters(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getVehicleInspection(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getVehiclePriceDetail(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getVehicleQuote(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getVehicleRegistration(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getWishlistVehicle(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function getinsurancetoken(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function initFinalReview(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function initiateChangePlan(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function inviteFriend(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function linkIncomeSource(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function quoteAgreement(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function removeReviewImage(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function removeWishlistVehicle(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function requestCreditScore(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function retryPendingPayment(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function retryPeningPayment(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function saveFinaleReview(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function saveMonthlyIncome(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function searchPreAuthVehicles(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function searchVehicles(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function similarVehicles(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function thirdPartyUpdateLicenseDetails(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function thirdPartyUploadDocument(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function uploadFinaleReviewImage(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function uploadPaystub(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function uploadSupportIssueImage(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }
    public function verifyUberLyft(Request $request): JsonResponse { return $this->pendingResponse(__FUNCTION__); }

    protected function _thirdPartyUpdateLicenseDetails(...$args) { return ['status' => 0, 'message' => __FUNCTION__ . ' pending migration']; }
    protected function _thirdPartyUploadDocument(...$args) { return ['status' => 0, 'message' => __FUNCTION__ . ' pending migration']; }
}
