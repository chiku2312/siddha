<?php
/**
 * Step 1: DigiLocker Authentication
 * User clicks button to login via DigiLocker
 */

require_once __DIR__ . '/../lib/config.php';

use WHMCS\Database\Capsule;

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /clientarea.php');
    exit();
}

$client = $_SESSION['user'];
$companyLogo = LOGO_URL;
$companyName = COMPANY_NAME;
$testMode = KYC_TEST_MODE;

// Generate state token for security
$state = bin2hex(random_bytes(16));
$_SESSION['digilocker_state'] = $state;
$_SESSION['kyc_start_time'] = time();

// DigiLocker OAuth URL
if ($testMode) {
    // Test mode - skip DigiLocker, go directly to mock verification
    $authUrl = '/kyc.php?action=digilocker_callback&test=1&state=' . $state;
} else {
    // Production - Real DigiLocker OAuth
    $authUrl = DIGILOCKER_API_BASE . '/public/oauth2/1/authorize?' . http_build_query([
        'response_type' => 'code',
        'client_id' => DIGILOCKER_CLIENT_ID,
        'redirect_uri' => DIGILOCKER_REDIRECT_URI,
        'state' => $state,
        'code_challenge' => base64_encode(hash('sha256', $state, true)),
        'code_challenge_method' => 'S256'
    ]);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigiLocker KYC Verification</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        .logo { max-width: 150px; margin-bottom: 30px; }
        .digilocker-logo {
            width: 120px;
            margin: 20px auto;
        }
        h1 {
            color: #333;
            margin-bottom: 15px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 16px;
            line-height: 1.6;
        }
        .info-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: left;
        }
        .info-box h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 18px;
        }
        .info-box ul {
            list-style: none;
            padding: 0;
        }
        .info-box li {
            padding: 10px 0;
            color: #212529;
            border-bottom: 1px solid #e9ecef;
        }
        .info-box li:last-child { border-bottom: none; }
        .info-box li:before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
            margin-right: 10px;
        }
        .btn {
            padding: 18px 40px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 30px;
            transition: all 0.3s;
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.3);
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.5);
        }
        .btn:before {
            content: "🔐 ";
            margin-right: 10px;
        }
        .test-badge {
            background: #ffc107;
            color: #000;
            padding: 10px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 600;
            display: inline-block;
        }
        .security-note {
            background: #d1ecf1;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
            font-size: 14px;
            color: #0c5460;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="<?= $companyLogo ?>" alt="<?= $companyName ?>" class="logo">
        
        <svg class="digilocker-logo" viewBox="0 0 200 80" xmlns="http://www.w3.org/2000/svg">
            <rect x="10" y="10" width="180" height="60" rx="10" fill="#FF6B35"/>
            <text x="100" y="50" font-size="24" fill="white" text-anchor="middle" font-weight="bold">DigiLocker</text>
        </svg>
        
        <h1>🔐 DigiLocker KYC Verification</h1>
        <p class="subtitle">
            Verify your identity securely using government-issued documents from your DigiLocker account
        </p>
        
        <?php if ($testMode): ?>
        <div class="test-badge">
            🧪 TEST MODE - Mock Verification
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <h3>What is DigiLocker?</h3>
            <ul>
                <li>Government of India's official digital document storage</li>
                <li>Secure access to Aadhaar, Driving License, PAN & more</li>
                <li>Documents directly verified from government databases</li>
                <li>No need to upload physical copies</li>
            </ul>
        </div>
        
        <div class="info-box">
            <h3>How it works:</h3>
            <ul>
                <li>Click "Login with DigiLocker" button below</li>
                <li>Login to your DigiLocker account</li>
                <li>Authorize document access</li>
                <li>Your KYC will be verified automatically</li>
            </ul>
        </div>
        
        <div class="security-note">
            <strong>🔒 Secure & Private:</strong> Your documents are fetched securely from government servers. We only receive verified information and never store your DigiLocker password.
        </div>
        
        <a href="<?= htmlspecialchars($authUrl) ?>" class="btn">
            Login with DigiLocker
        </a>
        
        <br>
        <a href="/kyc.php" class="back-link">← Choose Different Method</a>
    </div>
</body>
</html>
