<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load required files
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/mysql.php';

// Check user authentication
if (!isset($_SESSION['user'])) {
    header('Location: /clientarea.php');
    exit();
}

$client = $_SESSION['user'];
$aadhaar = $_POST['aadhaar'] ?? '';

// Validate Aadhaar number
if (empty($aadhaar) || strlen($aadhaar) != 12 || !is_numeric($aadhaar)) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invalid Aadhaar</title>
    <style>
        body{font-family:Arial;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .box{background:white;padding:40px;border-radius:15px;max-width:500px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;}
        .error-icon{font-size:64px;margin-bottom:20px;}
        h2{color:#dc3545;margin:20px 0;}
        p{color:#666;line-height:1.6;}
        .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.3s;}
        .btn:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,0.4);}
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">⚠️</div>
        <h2>Invalid Aadhaar Number</h2>
        <p>Please enter a valid 12-digit Aadhaar number.</p>
        <p style="font-size:13px;color:#999;">Aadhaar should be exactly 12 numeric digits.</p>
        <a href="/kyc.php" class="btn">← Go Back</a>
    </div>
    </body></html>');
}

// Check if required constants are defined
if (!defined('KYC_TEST_MODE')) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Configuration Error</title>
    <style>
        body{font-family:Arial;background:#f5f5f5;padding:50px;text-align:center;}
        .box{background:white;padding:40px;border-radius:15px;max-width:600px;margin:0 auto;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
        .error-icon{font-size:64px;color:#dc3545;margin-bottom:20px;}
        h2{color:#333;}
        .error-details{background:#f8d7da;padding:15px;border-radius:8px;margin:20px 0;color:#721c24;text-align:left;}
        .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:#667eea;color:white;text-decoration:none;border-radius:5px;}
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">⚙️</div>
        <h2>Configuration Error</h2>
        <div class="error-details">
            <strong>Error:</strong> KYC_TEST_MODE constant is not defined.<br>
            <strong>File:</strong> /var/www/html/modules/addons/kycverification/lib/config.php<br>
            <strong>Action:</strong> Config file may not have loaded properly.
        </div>
        <p>Please contact administrator.</p>
        <a href="/kyc.php" class="btn">← Go Back</a>
    </div>
    </body></html>');
}

// Check if API credentials are defined
if (!defined('CASHFREE_API_BASE') || !defined('CASHFREE_KYC_CLIENT_ID') || !defined('CASHFREE_KYC_CLIENT_SECRET')) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Configuration Error</title>
    <style>
        body{font-family:Arial;background:#f5f5f5;padding:50px;text-align:center;}
        .box{background:white;padding:40px;border-radius:15px;max-width:600px;margin:0 auto;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
        .error-icon{font-size:64px;color:#dc3545;margin-bottom:20px;}
        h2{color:#333;}
        .error-details{background:#f8d7da;padding:15px;border-radius:8px;margin:20px 0;color:#721c24;text-align:left;}
        .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:#667eea;color:white;text-decoration:none;border-radius:5px;}
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">🔐</div>
        <h2>API Configuration Missing</h2>
        <div class="error-details">
            <strong>Error:</strong> Cashfree API credentials not configured.<br>
            <strong>Missing:</strong> API Base URL, Client ID, or Client Secret<br>
            <strong>Action:</strong> Check module configuration in admin panel.
        </div>
        <p>Please contact administrator.</p>
        <a href="/kyc.php" class="btn">← Go Back</a>
    </div>
    </body></html>');
}

// TEST MODE - Skip API call
if (KYC_TEST_MODE) {
    $_SESSION['kyc_ref'] = 'TEST-' . time();
    $_SESSION['kyc_aadhaar'] = $aadhaar;
    header('Location: /kyc.php?action=step4');
    exit();
}

// PRODUCTION MODE - Real API Call
$apiUrl = CASHFREE_API_BASE . '/verification/offline-aadhaar/otp';

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode(['aadhaar_number' => $aadhaar]),
    CURLOPT_HTTPHEADER => [
        "accept: application/json",
        "content-type: application/json",
        "x-client-id: " . CASHFREE_KYC_CLIENT_ID,
        "x-client-secret: " . CASHFREE_KYC_CLIENT_SECRET
    ],
]);

$response = curl_exec($curl);
$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$err = curl_error($curl);

curl_close($curl);

// Handle CURL errors
if ($err) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Connection Error</title>
    <style>
        body{font-family:Arial;background:linear-gradient(135deg,#dc3545 0%,#c82333 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .box{background:white;padding:40px;border-radius:15px;max-width:600px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;}
        .error-icon{font-size:64px;margin-bottom:20px;}
        h2{color:#dc3545;margin:20px 0;}
        .error-details{background:#f8d7da;padding:15px;border-radius:8px;margin:20px 0;color:#721c24;text-align:left;font-size:14px;}
        .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;}
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">🔌</div>
        <h2>Connection Error</h2>
        <p>Unable to connect to verification service.</p>
        <div class="error-details">
            <strong>Error:</strong> ' . htmlspecialchars($err) . '<br>
            <strong>Endpoint:</strong> ' . htmlspecialchars($apiUrl) . '
        </div>
        <p style="font-size:13px;color:#666;">Please try again in a few moments.</p>
        <a href="/kyc.php" class="btn">← Try Again</a>
    </div>
    </body></html>');
}

// Parse response
$data = json_decode($response, true);

// Success - OTP sent
if ($httpCode == 200 && isset($data['ref_id'])) {
    $_SESSION['kyc_ref'] = $data['ref_id'];
    $_SESSION['kyc_aadhaar'] = $aadhaar;
    header('Location: /kyc.php?action=step4');
    exit();
}

// API Error - Show detailed error
$errorMsg = $data['message'] ?? 'Unknown error occurred';
$errorCode = $data['code'] ?? 'unknown';

// Determine specific error message
$specificError = '';
$solutionText = '';

if ($httpCode == 404) {
    $specificError = 'KYC API Product Not Enabled';
    $solutionText = 'The KYC Verification API is not activated on the Cashfree account. Please contact <strong>support@cashfree.com</strong> to enable this service.';
} elseif ($httpCode == 401) {
    $specificError = 'Authentication Failed';
    $solutionText = 'The API credentials (Client ID or Client Secret) are incorrect. Please verify the credentials in admin panel.';
} elseif ($httpCode == 403) {
    $specificError = 'Access Forbidden';
    $solutionText = 'Server IP address may not be whitelisted. Please add <strong>160.187.22.100</strong> to Cashfree IP whitelist.';
} elseif ($httpCode == 400) {
    $specificError = 'Invalid Request';
    $solutionText = 'The Aadhaar number format may be incorrect or the number is not registered with UIDAI.';
} elseif ($httpCode >= 500) {
    $specificError = 'Server Error';
    $solutionText = 'Cashfree API is experiencing issues. Please try again in a few minutes.';
} else {
    $specificError = 'Verification Failed';
    $solutionText = 'An unexpected error occurred. Please contact support if the issue persists.';
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Verification Error</title>
<style>
body{font-family:Arial;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:white;padding:40px;border-radius:15px;max-width:700px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;}
.error-icon{font-size:64px;margin-bottom:20px;}
h2{color:#dc3545;margin:20px 0;}
.error-msg{font-size:16px;color:#666;margin:15px 0;}
.error-details{background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;text-align:left;}
.error-details h4{margin:0 0 10px 0;color:#495057;font-size:14px;text-transform:uppercase;letter-spacing:0.5px;}
.error-details code{background:#e9ecef;padding:2px 8px;border-radius:3px;font-size:13px;color:#495057;}
.error-row{padding:8px 0;border-bottom:1px solid #dee2e6;}
.error-row:last-child{border-bottom:none;}
.error-row strong{color:#495057;display:inline-block;width:120px;}
.solution-box{background:#fff3cd;border-left:4px solid #ffc107;padding:15px;border-radius:8px;margin:20px 0;text-align:left;}
.solution-box h4{margin:0 0 10px 0;color:#856404;}
.solution-box p{margin:0;color:#856404;font-size:14px;line-height:1.6;}
.btn{display:inline-block;margin-top:20px;padding:12px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;transition:all 0.3s;}
.btn:hover{transform:translateY(-2px);box-shadow:0 5px 15px rgba(102,126,234,0.4);}
.admin-note{font-size:12px;color:#999;margin-top:20px;}
</style>
</head><body>
<div class="box">
    <div class="error-icon">❌</div>
    <h2>' . htmlspecialchars($specificError) . '</h2>
    <p class="error-msg">' . htmlspecialchars($errorMsg) . '</p>
    
    <div class="error-details">
        <h4>Technical Details</h4>
        <div class="error-row">
            <strong>HTTP Status:</strong> <code>' . $httpCode . '</code>
        </div>
        <div class="error-row">
            <strong>Error Code:</strong> <code>' . htmlspecialchars($errorCode) . '</code>
        </div>
        <div class="error-row">
            <strong>API Endpoint:</strong> <code>' . htmlspecialchars($apiUrl) . '</code>
        </div>
        <div class="error-row">
            <strong>Mode:</strong> <code>Production</code>
        </div>
    </div>
    
    <div class="solution-box">
        <h4>💡 Solution</h4>
        <p>' . $solutionText . '</p>
    </div>
    
    <a href="/kyc.php" class="btn">← Try Again</a>
    
    <p class="admin-note">
        If you continue to experience issues, please contact system administrator.
    </p>
</div>
</body></html>';
