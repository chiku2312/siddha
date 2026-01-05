<?php
/**
 * KYC Configuration - DigiLocker Integration
 */

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    define("WHMCS", true);
}

// Get module settings
$settings = Capsule::table('tbladdonmodules')
    ->where('module', 'kycverification')
    ->pluck('value', 'setting')->toArray();

// Determine test mode
$testMode = isset($settings['test_mode']) && $settings['test_mode'] == 'on';

if ($testMode) {
    // TEST MODE - DigiLocker Sandbox
    define('DIGILOCKER_CLIENT_ID', 'DIGILOCKER_TEST_CLIENT');
    define('DIGILOCKER_CLIENT_SECRET', 'DIGILOCKER_TEST_SECRET');
    define('DIGILOCKER_REDIRECT_URI', 'http://160.187.22.100/kyc.php?action=digilocker_callback');
    define('DIGILOCKER_API_BASE', 'https://api.digitallocker.gov.in');
    define('KYC_TEST_MODE', true);
} else {
    // PRODUCTION MODE
    define('DIGILOCKER_CLIENT_ID', $settings['digilocker_client_id'] ?? '');
    define('DIGILOCKER_CLIENT_SECRET', $settings['digilocker_client_secret'] ?? '');
    define('DIGILOCKER_REDIRECT_URI', $settings['digilocker_redirect_uri'] ?? 'https://panel.vyomcloud.com/kyc.php?action=digilocker_callback');
    define('DIGILOCKER_API_BASE', 'https://api.digitallocker.gov.in');
    define('KYC_TEST_MODE', false);
}

// Other settings
define('ENCRYPTION_KEY', $settings['encryption_key'] ?? 'V835tUOZBFnXRzvRUiWmYa7wc0RE8xaa');
define('COMPANY_NAME', $settings['company_name'] ?? 'VyomCloud');
define('LOGO_URL', $settings['logo_url'] ?? 'https://panel.vyomcloud.com/assets/img/logo.png');
define('COUNTRY', $settings['country'] ?? 'India');

// Database credentials
define('MYSQL_HOST', $GLOBALS['db_host']);
define('MYSQL_USER', $GLOBALS['db_username']);
define('MYSQL_PASSWORD', $GLOBALS['db_password']);
define('MYSQL_DBNAME', $GLOBALS['db_name']);

// Compatibility
define('MAIN_DB_HOST', $GLOBALS['db_host']);
define('MAIN_DB_USER', $GLOBALS['db_username']);
define('MAIN_DB_PASSWORD', $GLOBALS['db_password']);
define('MAIN_DB_NAME', $GLOBALS['db_name']);
