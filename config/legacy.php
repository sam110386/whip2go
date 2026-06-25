<?php

$legacyOwnerPart = (int) env('LEGACY_OWNER_PART', 85);

/**
 * Settings for Blade ports of CakePHP `app/View/Layouts/*` and static assets
 * that still live under the Cake `app/webroot` tree.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Public URL of the Cake webroot (no trailing slash)
    |--------------------------------------------------------------------------
    |
    | When empty, `legacy_asset()` prefixes paths with "/" so URLs resolve
    | from the same host document root as Cake (e.g. /theme2/bootstrap.css).
    | Set to e.g. "https://example.com/app/webroot" if assets are served from
    | another base during migration.
    |
    */
    'asset_base' => env('LEGACY_ASSET_BASE', ''),

    /*
    |--------------------------------------------------------------------------
    | SITE_URL equivalent for JS (no trailing slash)
    |--------------------------------------------------------------------------
    */
    'site_url' => env('LEGACY_SITE_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Google Analytics (optional; matches Cake main layout when set)
    |--------------------------------------------------------------------------
    */
    'analytics_measurement_id' => env('LEGACY_ANALYTICS_MEASUREMENT_ID', 'G-GLQS917RCM'),

    'support_phone' => env('LEGACY_SUPPORT_PHONE', '(203) 491-4283'),

    /*
    | Third-party widgets from Cake `driveitaway.ctp` (off by default).
    */
    'enable_salesforce_live_agent' => env('LEGACY_ENABLE_SALESFORCE_LIVE_AGENT', false),
    'enable_intercom' => env('LEGACY_ENABLE_INTERCOM', false),

    /*
    |--------------------------------------------------------------------------
    | Homes / support contact form (Cake `HomesController::support`)
    |--------------------------------------------------------------------------
    */
    'homes_support_to' => env('LEGACY_HOMES_SUPPORT_TO', ''),

    /*
    |--------------------------------------------------------------------------
    | DriveItAway marketing: /contactus (Cake `HomesController::contactus`)
    |--------------------------------------------------------------------------
    */
    'homes_driveitaway_contactus_to' => env('LEGACY_HOMES_DRIVEITAWAY_CONTACTUS_TO', ''),
    'homes_driveitaway_contactus_from_address' => env('LEGACY_HOMES_DRIVEITAWAY_CONTACTUS_FROM_ADDRESS', ''),
    'homes_driveitaway_contactus_from_name' => env('LEGACY_HOMES_DRIVEITAWAY_CONTACTUS_FROM_NAME', 'Whip Team'),
    'homes_driveitaway_contactus_subject' => env('LEGACY_HOMES_DRIVEITAWAY_CONTACTUS_SUBJECT', 'Whip - Contact Us'),

    /*
    |--------------------------------------------------------------------------
    | DriveItAway marketing: /nada2019 (Cake `HomesController::nada`)
    |--------------------------------------------------------------------------
    */
    'homes_driveitaway_nada_to' => env('LEGACY_HOMES_DRIVEITAWAY_NADA_TO', ''),
    'homes_driveitaway_nada_from_address' => env('LEGACY_HOMES_DRIVEITAWAY_NADA_FROM_ADDRESS', ''),
    'homes_driveitaway_nada_from_name' => env('LEGACY_HOMES_DRIVEITAWAY_NADA_FROM_NAME', 'Whip Team'),
    'homes_driveitaway_nada_subject' => env('LEGACY_HOMES_DRIVEITAWAY_NADA_SUBJECT', 'DriveitAway - NADA 2019'),

    /*
    |--------------------------------------------------------------------------
    | Default owner revenue share (Cake `OWNER_PART` in bootstrap)
    |--------------------------------------------------------------------------
    |
    | Used when `rev_settings.rental_rev` is missing for booking report math.
    |
    */

    /** Cake `Configure::read('OWNER_PART')` alias for ported services */
    'OWNER_PART' => $legacyOwnerPart,

    /*
    |--------------------------------------------------------------------------
    | DIAWEB URL (Cake `Configure::read('DIAWEB.url')`)
    |--------------------------------------------------------------------------
    */
    'diaweb_url' => env('LEGACY_DIAWEB_URL', 'https://cars.driveitaway.com'),


    'security' => [
        'salt' => env('LEGACY_SECURITY_SALT', ''),
        'encryptKey' => env('LEGACY_SECURITY_ENCRYPT_KEY', ''),
        'cipherSeed' => env('LEGACY_SECURITY_CIPHER_SEED', ''),
        'level' => env('LEGACY_SECURITY_LEVEL', 'medium'),
        'useOpenSsl' => env('LEGACY_SECURITY_USE_OPENSSL', true),
    ],


    'GOOGLE_MAPS_EMBED_URL' => env('GOOGLE_MAPS_EMBED_URL', 'https://www.google.com/maps/embed/v1/place'),
    'GOOGLE_MAPS_API_KEY' => env('GOOGLE_MAPS_API_KEY', ''),

    'vindecoder' => [
        'apiUrl' => env('LEGACY_VINDECODER_BASE_URL', 'https://api.vindecoder.eu/3.2'),
        'apiKey' => env('LEGACY_VINDECODER_API_KEY', ''),
        'apiSecret' => env('LEGACY_VINDECODER_API_SECRET', ''),
    ],

    'tinyurl' => [
        'apiUrl' => env('LEGACY_TINYURL_BASE_URL', 'http://tiny-url.info/api/v1/create'),
        'apiKey' => env('LEGACY_TINYURL_API_KEY', ''),
    ],

    'intercom' => [
        'access_token' => env('INTERCOM_ACCESS_TOKEN'),
        'admin_id' => env('INTERCOM_ADMIN_ID'),
    ],

    'axle' => [
        'x-client_id' => env('AXLE_API_ID', ''),
        'x-client_secret' => env('AXLE_API_SECRET', ''),
        'api_host' => env('AXLE_API_HOST', 'https://api.axle.insure'),
    ],

    'Free2Move' => [
        'apiHost' => env('FREE2MOVE_API_HOST', ''),
        'apiAgreementHost' => env('FREE2MOVE_API_AGREEMENT_HOST', ''),
        'apiToken' => env('FREE2MOVE_API_TOKEN', ''),
    ],

    'Ituran' => [
        'url' => env('ITURAN_API_URL', ''),
        'networkId' => env('ITURAN_API_NETWORK_ID', ''),
        'senderUserName' => env('ITURAN_API_SENDER_USER_NAME', ''),
        'senderPassword' => env('ITURAN_API_SENDER_PASSWORD', ''),
    ],

    'Passtime' => [
        'url' => env('PASSTIME_API_URL', 'https://softwarepartners.passtimeusa.com'),
        'username' => env('PASSTIME_API_USERNAME', 'adam@mindseyeny.com'),
        'password' => env('PASSTIME_API_PASSWORD', 'Mindseyeisgreat1!'),
    ],

    'MaintenanceMonitoring' => [
        'miles' => 5000
    ],

    'Inspektlabs' => [
        'clientId' => env('INSPEKTLABS_CLIENT_ID', ''),
        'login_pwd' => env('INSPEKTLABS_LOGIN_PWD', ''),
        'url' => env('INSPEKTLABS_URL', ''),
        'auth_url' => env('INSPEKTLABS_AUTH_URL', ''),
        'api_ley' => env('INSPEKTLABS_API_LEY', ''),
        'secret_key' => env('INSPEKTLABS_SECRET_KEY', ''),
        'webhook_token' => env('INSPEKTLABS_WEBHOOK_TOKEN', ''),
    ],

    'Salesforce' => [
        'username' => env('SALESFORCE_USERNAME', ''),
        'password' => env('SALESFORCE_PASSWORD', ''),
        'enabled' => env('SALESFORCE_ENABLED', false),
    ],

    'Agreement' => [
        'api_url' => env('AGREEMENT_API_URL', 'http://localhost:3000/'),
    ],

    'DIA_FEE' => 5,
    'TELEMATICUNITMONTHSERVICE' => 13.99,
    'COMPANY_DISPACHER' => env('LEGACY_COMPANY_DISPACHER', 73),
    'SUPPORT_PHONE' => env('SUPPORT_PHONE', '(203) 491-4283)'),

    'plaid' => [
        'client_id' => env('PLAID_CLIENT_ID', ''),
        'secret' => env('PLAID_SECRET', ''),
        'key' => env('PLAID_PUBLIC_KEY', ''),
        'env' => env('PLAID_ENV', 'sandbox'),
        'url' => env('PLAID_URL', 'https://sandbox.plaid.com'),
        'identifier' => env('PLAID_IDENTIFIER', 'driveitaway_'),
    ],

];
