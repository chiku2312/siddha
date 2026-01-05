<?php
/**
 * OTP Verification with Auto-Approval
 * After successful verification, KYC is automatically approved
 */

require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/mysql.php';

use WHMCS\Database\Capsule;

session_start();

if (!isset($_SESSION['user']) || !isset($_SESSION['kyc_ref'])) {
    header('Location: /kyc.php');
    exit();
}

$client = $_SESSION['user'];
$otp = $_POST['otp'] ?? '';

if (empty($otp) || strlen($otp) != 6) {
    die('Invalid OTP format');
}

$companyLogo = LOGO_URL;
$companyName = COMPANY_NAME;

// Check if test mode
if (KYC_TEST_MODE) {
    // TEST MODE - Accept any 6-digit OTP
    $kycData = [
        'name' => 'Test User ' . $client['firstname'],
        'dob' => '01-01-1990',
        'gender' => 'M',
        'address' => 'Test Address, Test City, Test State, 110001',
        'email' => '',
        'photo_link' => '',
    ];
    
    $aadhaarMasked = 'XXXX XXXX ' . substr($_SESSION['kyc_aadhaar'] ?? '123456789012', -4);
    
} else {
    // PRODUCTION MODE - Verify with Cashfree API
    $apiUrl = CASHFREE_API_BASE . '/verification/offline-aadhaar/verify';
    
    $postData = [
        'ref_id' => $_SESSION['kyc_ref'],
        'otp' => $otp
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-client-id: " . CASHFREE_KYC_CLIENT_ID,
            "x-client-secret: " . CASHFREE_KYC_CLIENT_SECRET,
        ],
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode != 200) {
        die('OTP verification failed. Please try again.');
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['status']) || $result['status'] != 'VALID') {
        die('Invalid OTP. Please try again.');
    }
    
    $kycData = $result['details'] ?? [];
    $aadhaarMasked = $result['aadhaar_number'] ?? 'XXXX XXXX XXXX';
}

// Save to database with AUTO-APPROVAL
try {
    $encryptedData = [
        'aadhaar_masked' => $aadhaarMasked,
        'test_mode' => KYC_TEST_MODE,
        'raw_response' => $response ?? 'test_mode_data',
    ];
    
    // Insert with approval_status = 'approved' (AUTO-APPROVED)
    Capsule::table('mod_kyc_aadhar')->insert([
        'client_id' => $client['id'],
        'ref_id' => $_SESSION['kyc_ref'],
        'status' => 'VALID',
        'approval_status' => 'approved',  // ← AUTO-APPROVED!
        'name' => $kycData['name'] ?? '',
        'dob' => $kycData['dob'] ?? '',
        'address' => $kycData['address'] ?? '',
        'email' => $kycData['email'] ?? '',
        'gender' => $kycData['gender'] ?? '',
        'photo_link' => $kycData['photo_link'] ?? '',
        'encrypted_data' => json_encode($encryptedData),
        'verified_at' => date('Y-m-d H:i:s'),
        'approved_by' => 0, // System auto-approved
        'approved_at' => date('Y-m-d H:i:s'), // Approved immediately
        'admin_notes' => 'Auto-approved after successful OTP verification',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
    
    // Update client table - Mark as verified
    Capsule::table('tblclients')
        ->where('id', $client['id'])
        ->update(['kyc_verified' => 1]);
    
    // Clear session
    unset($_SESSION['kyc_ref']);
    unset($_SESSION['kyc_aadhaar']);
    
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verified Successfully!</title>
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
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: white;
            animation: scaleIn 0.5s ease-out;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        h1 {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 18px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin: 30px 0;
            text-align: left;
        }
        .info-row {
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            font-weight: 600;
            color: #495057;
        }
        .info-value {
            color: #212529;
            font-weight: 500;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            margin: 20px 0;
        }
        .btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 30px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .test-mode-badge {
            background: #ffc107;
            color: #000;
            padding: 10px 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="<?= $companyLogo ?>" alt="<?= $companyName ?>" class="logo">
        
        <div class="success-icon">✓</div>
        
        <h1>KYC Verified Successfully!</h1>
        <p class="subtitle">Your identity has been verified and approved</p>
        
        <div class="status-badge">✅ VERIFIED & APPROVED</div>
        
        <?php if (KYC_TEST_MODE): ?>
        <div class="test-mode-badge">
            🧪 TEST MODE VERIFICATION
        </div>
        <?php endif; ?>
        
        <div class="info-box">
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?= htmlspecialchars($kycData['name'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date of Birth:</span>
                <span class="info-value"><?= htmlspecialchars($kycData['dob'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Gender:</span>
                <span class="info-value"><?= htmlspecialchars($kycData['gender'] ?? 'N/A') ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Aadhaar:</span>
                <span class="info-value" style="font-family: monospace; font-size: 16px;"><?= htmlspecialchars($aadhaarMasked) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" style="color: #28a745; font-weight: 700;">APPROVED ✓</span>
            </div>
            <div class="info-row">
                <span class="info-label">Verified On:</span>
                <span class="info-value"><?= date('d M Y, H:i A') ?></span>
            </div>
        </div>
        
        <p style="color: #666; font-size: 14px; margin: 20px 0;">
            Your account is now fully verified. You can now access all services without any restrictions.
        </p>
        
        <a href="/clientarea.php" class="btn">Go to Dashboard →</a>
    </div>
</body>
</html>
