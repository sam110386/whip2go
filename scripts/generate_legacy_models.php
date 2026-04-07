<?php

declare(strict_types=1);

$targetDir = __DIR__ . '/../app/Models/Legacy';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$classes = [
    'DepositRule', 'Vehicle', 'Passtime', 'CsOrderPayment', 'User', 'CsReservationPayment', 'CsTrackVehicle',
    'CsWallet', 'CsUserBalance', 'DynamicFare', 'CsOrderStatuslog', 'DepositTemplate', 'VehicleLocation',
    'VehicleReservation', 'SchedulePayQueue', 'CsUserBalanceLog', 'CsPayoutTransaction', 'AdminRolePermission',
    'AdminPermission', 'AdminRole', 'CsPaymentRetry', 'CsOrder', 'Admin', 'Wishlist', 'VehicleSetting',
    'VehicleOffer', 'VehicleImage', 'UserReport', 'UserLicenseDetail', 'UserIncome', 'UserCreditScore',
    'UserCcToken', 'TwilioSetting', 'Tracking', 'Timezone', 'TdkVehicle', 'TdkDealer', 'State', 'StaffUser',
    'Role', 'RevSetting', 'PtoSetting', 'PopularMarket', 'PlaidUser', 'PaymentReport', 'Page', 'OrderDepositRule',
    'MileOverdue', 'MetroExport', 'MarketplaceVehicleLead', 'MarketplacePdealer', 'Home', 'EmailTemplate',
    'DynamicDeposit', 'CsWorkingHour', 'CsWalletTransaction', 'CsUserConvertibility', 'CsTwilioOrder',
    'CsTwilioLog', 'CsSetting', 'CsPayout', 'CsPaymentLog', 'CsOwnerPayout', 'CsOrderReviewImage',
    'CsOrderReview', 'CsOrderAvailability', 'CsMsrpSetting', 'CsLeaseAvailability', 'CsLease',
    'CsInsuranceTemplate', 'CsEquitySetting', 'CsEavSetting', 'CalculateToll', 'CabName', 'ArgyleUserRecord',
    'ArgyleUser', 'ArgyleActivity', 'AdminUserRole', 'AdminUserAssociation', 'AdminRoleUser', 'AdminRoleMenu',
];

$tableOverrides = [
    'DepositRule' => 'cs_deposit_rules',
    'DepositTemplate' => 'cs_deposit_templates',
    'Admin' => 'users',
    'StaffUser' => 'users',
    'VehicleImage' => 'cs_vehicle_images',
    'UserLicenseDetail' => 'cs_user_license_details',
    'OrderDepositRule' => 'cs_order_deposit_rules',
    'DynamicDeposit' => 'cs_dynamic_deposits',
    'AdminRoleMenu' => 'admin_role_menu',
];

$toSnake = static function (string $name): string {
    $snake = preg_replace('/(?<!^)[A-Z]/', '_$0', $name);
    return strtolower((string) $snake);
};

$inferTable = static function (string $class) use ($toSnake): string {
    $snake = $toSnake($class);
    if (str_ends_with($snake, 'y')) {
        return substr($snake, 0, -1) . 'ies';
    }
    if (str_ends_with($snake, 's')) {
        return $snake . 'es';
    }
    return $snake . 's';
};

foreach ($classes as $class) {
    if ($class === 'LegacyModel') {
        continue;
    }
    $table = $tableOverrides[$class] ?? $inferTable($class);
    $filePath = $targetDir . '/' . $class . '.php';
    $code = <<<PHP
<?php

namespace App\Models\Legacy;

class {$class} extends LegacyModel
{
    protected \$table = '{$table}';
}

PHP;
    file_put_contents($filePath, $code);
}

echo 'Generated ' . count($classes) . " legacy model classes.\n";

