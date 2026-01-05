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
$dlNumber = $_POST['dl_number'] ?? '';

// Validate DL number (format: MH12-20190012345 or similar)
if (empty($dlNumber) || strlen($dlNumber) < 10) {
    die('<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invalid DL</title>
    <style>
        body{font-family:Arial;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .box{background:white;padding:40px;border-radius:15px;max-width:500px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;}
        .error-icon{font-size:64px;margin-bottom:20px;}
        h2{color:#dc3545;margin:20px 0;}
        p{color:#666;line-height:1.6;}
        .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;}
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">⚠️</div>
        <h2>Invalid Driving License Number</h2>
        <p>Please enter a valid Driving License number.</p>
        <p style="font-size:13px;color:#999;">Format: MH12-20190012345</p>
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
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">⚙️</div>
        <h2>Configuration Error</h2>
        <p>KYC system not configured properly. Please contact administrator.</p>
        <a href="/kyc.php" style="display:inline-block;margin-top:20px;padding:12px 30px;background:#667eea;color:white;text-decoration:none;border-radius:5px;">← Go Back</a>
    </div>
    </body></html>');
}

// TEST MODE - Skip API call
if (KYC_TEST_MODE) {
    // Generate dummy data
    $_SESSION['kyc_dl_data'] = [
        'dl_number' => $dlNumber,
        'name' => 'Test User ' . $client['firstname'],
        'dob' => '01-01-1990',
        'address' => 'Test Address, Test City, Test State',
        'validity' => '01-01-2030',
        'issue_date' => '01-01-2020',
        'cov' => 'LMV, MCWG',
    ];
    
    $_SESSION['kyc_dl_number'] = $dlNumber;
    
    // Show success page
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DL Verification Success</title>
    <style>
        body{font-family:Arial;background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .box{background:white;padding:50px;border-radius:20px;max-width:600px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
        .success-icon{font-size:80px;margin-bottom:20px;}
        h1{color:#28a745;margin-bottom:15px;}
        .info{background:#d4edda;padding:20px;border-radius:10px;margin:20px 0;text-align:left;}
        .info-row{padding:10px 0;border-bottom:1px solid #c3e6cb;}
        .info-row:last-child{border-bottom:none;}
        .info-row strong{display:inline-block;width:150px;color:#155724;}
        .btn{display:inline-block;margin-top:20px;padding:15px 40px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;font-size:16px;}
        .test-badge{background:#ffc107;color:#000;padding:5px 15px;border-radius:20px;font-size:12px;font-weight:600;margin-top:10px;display:inline-block;}
    </style>
    </head><body>
    <div class="box">
        <div class="success-icon">✅</div>
        <h1>DL Verified Successfully!</h1>
        <p>Your Driving License has been verified (Test Mode)</p>
        
        <div class="info">
            <div class="info-row">
                <strong>DL Number:</strong> ' . htmlspecialchars($dlNumber) . '
            </div>
            <div class="info-row">
                <strong>Name:</strong> Test User ' . htmlspecialchars($client['firstname']) . '
            </div>
            <div class="info-row">
                <strong>DOB:</strong> 01-01-1990
            </div>
            <div class="info-row">
                <strong>Valid Until:</strong> 01-01-2030
            </div>
            <div class="info-row">
                <strong>Vehicle Class:</strong> LMV, MCWG
            </div>
        </div>
        
        <div class="test-badge">🧪 TEST MODE DATA</div>
        
        <p style="margin-top:20px;color:#666;font-size:14px;">
            Your verification will be reviewed by admin before approval.
        </p>
        
        <a href="/clientarea.php" class="btn">Go to Dashboard →</a>
    </div>
    </body></html>';
    exit();
}

// PRODUCTION MODE - Real API Call
$apiUrl = CASHFREE_API_BASE . '/verification/driving-license';

$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode([
        'license_number' => $dlNumber,
        'dob' => $_POST['dob'] ?? date('Y-m-d', strtotime('-25 years'))
    ]),
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
        .btn{display:inline-block;margin-top:20px;padding:12px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;}
    </style>
    </head><body>
    <div class="box">
        <div class="error-icon">🔌</div>
        <h2>Connection Error</h2>
        <p>' . htmlspecialchars($err) . '</p>
        <a href="/kyc.php" class="btn">← Try Again</a>
    </div>
    </body></html>');
}

// Parse response
$data = json_decode($response, true);

// Success - DL verified
if ($httpCode == 200 && isset($data['license_number'])) {
    $_SESSION['kyc_dl_data'] = $data;
    $_SESSION['kyc_dl_number'] = $dlNumber;
    
    // Save to database (you can add this later)
    
    // Show success
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>DL Verified</title>
    <style>
        body{font-family:Arial;background:linear-gradient(135deg,#56ab2f 0%,#a8e063 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
        .box{background:white;padding:50px;border-radius:20px;max-width:600px;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,0.3);}
        .success-icon{font-size:80px;margin-bottom:20px;}
        h1{color:#28a745;margin-bottom:15px;}
        .btn{display:inline-block;margin-top:20px;padding:15px 40px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;}
    </style>
    </head><body>
    <div class="box">
        <div class="success-icon">✅</div>
        <h1>Driving License Verified!</h1>
        <p>Your license has been verified successfully.</p>
        <a href="/clientarea.php" class="btn">Go to Dashboard →</a>
    </div>
    </body></html>';
    exit();
}

// API Error
$errorMsg = $data['message'] ?? 'Unknown error occurred';
$errorCode = $data['code'] ?? 'unknown';

// Determine specific error message
$specificError = '';
$solutionText = '';

if ($httpCode == 404) {
    $specificError = 'KYC API Product Not Enabled';
    $solutionText = 'The KYC Verification API is not activated. Contact support@cashfree.com';
} elseif ($httpCode == 401) {
    $specificError = 'Authentication Failed';
    $solutionText = 'API credentials are incorrect.';
} elseif ($httpCode == 400) {
    $specificError = 'Invalid Request';
    $solutionText = 'The Driving License number or details may be incorrect.';
} else {
    $specificError = 'Verification Failed';
    $solutionText = 'An unexpected error occurred.';
}

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Verification Error</title>
<style>
body{font-family:Arial;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.box{background:white;padding:40px;border-radius:15px;max-width:700px;box-shadow:0 10px 40px rgba(0,0,0,0.3);text-align:center;}
.error-icon{font-size:64px;margin-bottom:20px;}
h2{color:#dc3545;margin:20px 0;}
.error-details{background:#f8f9fa;padding:20px;border-radius:8px;margin:20px 0;text-align:left;font-size:14px;}
.error-details code{background:#e9ecef;padding:2px 8px;border-radius:3px;}
.btn{display:inline-block;margin-top:20px;padding:12px 30px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;border-radius:8px;font-weight:600;}
</style>
</head><body>
<div class="box">
    <div class="error-icon">❌</div>
    <h2>' . htmlspecialchars($specificError) . '</h2>
    <p>' . htmlspecialchars($errorMsg) . '</p>
    
    <div class="error-details">
        <strong>HTTP Status:</strong> <code>' . $httpCode . '</code><br>
        <strong>Error Code:</strong> <code>' . htmlspecialchars($errorCode) . '</code><br>
        <strong>Solution:</strong> ' . $solutionText . '
    </div>
    
    <a href="/kyc.php" class="btn">← Try Again</a>
</div>
</body></html>';
