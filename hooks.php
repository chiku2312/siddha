<?php
if (!defined("WHMCS")) die("Access denied");

use WHMCS\Database\Capsule;

add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    if (!is_null($primarySidebar)) {
        $clientId = $_SESSION['uid'] ?? null;
        if (!$clientId) return;

        $kycRecord = Capsule::table('mod_kyc_aadhar')
            ->where('client_id', $clientId)
            ->first();

        $badgeHtml = '';
        if ($kycRecord && $kycRecord->approval_status == 'approved') {
            $badgeHtml = '<span class="badge" style="background:#28a745; color:white; padding:3px 8px; border-radius:3px; font-size:11px; margin-left:5px;">✓ Verified</span>';
        } elseif ($kycRecord && $kycRecord->approval_status == 'pending') {
            $badgeHtml = '<span class="badge" style="background:#ffc107; color:#000; padding:3px 8px; border-radius:3px; font-size:11px; margin-left:5px;">⏳ Pending</span>';
        } else {
            $badgeHtml = '<span class="badge" style="background:#dc3545; color:white; padding:3px 8px; border-radius:3px; font-size:11px; margin-left:5px;">⚠ Not Verified</span>';
        }

        $primarySidebar->addChild('kyc_verification', [
            'label' => 'KYC Verification ' . $badgeHtml,
            'uri' => 'kyc.php',
            'icon' => 'fas fa-id-card',
            'order' => 1,
        ]);
    }
});

add_hook('ClientAreaFooterOutput', 1, function($vars) {
    $clientId = $_SESSION['uid'] ?? null;
    if (!$clientId) return '';

    $currentPage = basename($_SERVER['PHP_SELF']);
    $allowedPages = ['kyc.php', 'logout.php', 'dologout.php'];
    if (in_array($currentPage, $allowedPages)) return '';

    $kycRecord = Capsule::table('mod_kyc_aadhar')
        ->where('client_id', $clientId)
        ->first();

    if ($kycRecord && $kycRecord->approval_status == 'approved') return '';

    if ($kycRecord && $kycRecord->approval_status == 'pending') {
        $verifiedDate = date('d M Y, H:i', strtotime($kycRecord->verified_at));
        return <<<HTML
<style>
#kyc-fullblock-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(243, 156, 18, 0.4) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 999999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    animation: fadeIn 0.3s ease !important;
}
#kyc-fullblock-content {
    background: white !important;
    padding: 50px 40px !important;
    border-radius: 20px !important;
    text-align: center !important;
    max-width: 600px !important;
    width: 90% !important;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4) !important;
    animation: slideUp 0.5s ease !important;
    position: relative !important;
    z-index: 1000000 !important;
}
#kyc-fullblock-content h1 {
    font-size: 72px !important;
    margin-bottom: 20px !important;
    color: #f39c12 !important;
}
#kyc-fullblock-content h2 {
    font-size: 32px !important;
    margin-bottom: 15px !important;
    font-weight: 700 !important;
    color: #333 !important;
}
#kyc-fullblock-content p {
    font-size: 18px !important;
    line-height: 1.6 !important;
    margin-bottom: 20px !important;
    color: #666 !important;
}
.kyc-block-btn {
    display: inline-block !important;
    padding: 16px 45px !important;
    background: linear-gradient(135deg, #f39c12, #e67e22) !important;
    color: white !important;
    border-radius: 10px !important;
    text-decoration: none !important;
    font-weight: 700 !important;
    font-size: 18px !important;
    transition: all 0.3s !important;
    margin: 8px !important;
    border: none !important;
    cursor: pointer !important;
}
.kyc-block-btn:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 10px 30px rgba(243, 156, 18, 0.4) !important;
    color: white !important;
}
.kyc-block-btn.secondary {
    background: #95a5a6 !important;
}
.kyc-block-btn.secondary:hover {
    background: #7f8c8d !important;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
body.kyc-blocked { overflow: hidden !important; }
</style>
<div id="kyc-fullblock-overlay">
    <div id="kyc-fullblock-content">
        <h1>⏳</h1>
        <h2>KYC VERIFICATION PENDING</h2>
        <p>Your KYC verification is submitted and awaiting admin approval.</p>
        <p>You will be able to access all features once approved.</p>
        <p style="font-size: 14px; color: #999; margin-top: 20px;">Submitted: {$verifiedDate}</p>
        <a href="kyc.php" class="kyc-block-btn">View Status →</a>
        <a href="logout.php" class="kyc-block-btn secondary">Logout</a>
    </div>
</div>
<script>
(function() {
    document.body.classList.add('kyc-blocked');
    document.body.style.overflow = 'hidden';
    
    // Prevent clicks on background
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#kyc-fullblock-content')) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
    
    // Prevent keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' || e.key === 'Escape') {
            if (!e.target.closest('#kyc-fullblock-content')) {
                e.preventDefault();
            }
        }
    }, true);
})();
</script>
HTML;
    }

    return <<<HTML
<style>
#kyc-fullblock-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(220, 53, 69, 0.4) !important;
    backdrop-filter: blur(8px) !important;
    -webkit-backdrop-filter: blur(8px) !important;
    z-index: 999999 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    animation: fadeIn 0.3s ease !important;
}
#kyc-fullblock-content {
    background: white !important;
    padding: 50px 40px !important;
    border-radius: 20px !important;
    text-align: center !important;
    max-width: 600px !important;
    width: 90% !important;
    box-shadow: 0 30px 80px rgba(0,0,0,0.4) !important;
    animation: slideUp 0.5s ease !important;
    position: relative !important;
    z-index: 1000000 !important;
}
#kyc-fullblock-content h1 {
    font-size: 72px !important;
    margin-bottom: 20px !important;
    color: #dc3545 !important;
    animation: pulse 2s infinite !important;
}
#kyc-fullblock-content h2 {
    font-size: 36px !important;
    margin-bottom: 15px !important;
    font-weight: 700 !important;
    color: #333 !important;
    text-transform: uppercase !important;
}
#kyc-fullblock-content p {
    font-size: 18px !important;
    line-height: 1.6 !important;
    margin-bottom: 20px !important;
    color: #666 !important;
}
.kyc-block-btn {
    display: inline-block !important;
    padding: 16px 45px !important;
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
    color: white !important;
    border-radius: 10px !important;
    text-decoration: none !important;
    font-weight: 700 !important;
    font-size: 18px !important;
    transition: all 0.3s !important;
    margin: 8px !important;
    box-shadow: 0 5px 20px rgba(220, 53, 69, 0.3) !important;
    border: none !important;
    cursor: pointer !important;
}
.kyc-block-btn:hover {
    transform: translateY(-5px) !important;
    box-shadow: 0 15px 40px rgba(220, 53, 69, 0.4) !important;
    color: white !important;
}
.kyc-block-btn.secondary {
    background: #95a5a6 !important;
    box-shadow: 0 5px 20px rgba(149, 165, 166, 0.3) !important;
}
.kyc-block-btn.secondary:hover {
    background: #7f8c8d !important;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes slideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}
body.kyc-blocked { overflow: hidden !important; }
</style>
<div id="kyc-fullblock-overlay">
    <div id="kyc-fullblock-content">
        <h1>⚠️</h1>
        <h2>KYC Verification Required</h2>
        <p>Your account requires KYC verification to access our services.</p>
        <p>Please complete the verification process to continue using all features.</p>
        <a href="kyc.php" class="kyc-block-btn">Verify Now →</a>
        <a href="logout.php" class="kyc-block-btn secondary">Logout</a>
    </div>
</div>
<script>
(function() {
    document.body.classList.add('kyc-blocked');
    document.body.style.overflow = 'hidden';
    
    // Prevent clicks on background
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#kyc-fullblock-content')) {
            e.preventDefault();
            e.stopPropagation();
        }
    }, true);
    
    // Prevent keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab' || e.key === 'Escape') {
            if (!e.target.closest('#kyc-fullblock-content')) {
                e.preventDefault();
            }
        }
    }, true);
})();
</script>
HTML;
});
