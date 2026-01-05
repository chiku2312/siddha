<?php
use WHMCS\Database\Capsule;

if (!isset($_SESSION['user'])) {
    die('<div style="padding:50px; text-align:center;"><h2>Session Error</h2><p>Please <a href="/clientarea.php">login</a> again.</p></div>');
}

$client = $_SESSION['user'];
$clientData = Capsule::table('tblclients')->where('id', $client['id'])->first();
$kyc_verified = $clientData->kyc_verified ?? 0;

$settings = Capsule::table('tbladdonmodules')
    ->where('module', 'kycverification')
    ->pluck('value', 'setting')->toArray();

$companyName = $settings['company_name'] ?? 'VyomCloud';
$logoUrl = $settings['logo_url'] ?? 'https://panel.vyomcloud.com/assets/img/logo.png';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Verification</title>
    <style>
        body { margin:0; padding:20px; font-family:Arial; background:#667eea; min-height:100vh; display:flex; align-items:center; justify-content:center; }
        .box { background:white; padding:40px; border-radius:15px; max-width:600px; width:100%; box-shadow:0 10px 40px rgba(0,0,0,0.2); }
        .logo { max-width:150px; display:block; margin:0 auto 20px; }
        h1 { text-align:center; color:#333; margin-bottom:10px; }
        .welcome { text-align:center; color:#666; font-size:18px; margin-bottom:30px; }
        .timer { background:#f5f5f5; padding:10px; border-radius:8px; text-align:center; margin-bottom:30px; font-weight:bold; }
        .options { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin:30px 0; }
        .card { background:linear-gradient(135deg, #667eea 0%, #764ba2 100%); color:white; padding:30px 20px; border-radius:12px; text-align:center; text-decoration:none; border:none; cursor:pointer; transition:transform 0.3s; width:100%; }
        .card:hover { transform:translateY(-5px); }
        .card .icon { font-size:40px; margin-bottom:15px; }
        .card h3 { margin:10px 0; font-size:20px; }
        .card p { font-size:14px; opacity:0.9; }
        .badge { display:inline-block; padding:5px 15px; background:rgba(255,255,255,0.3); border-radius:20px; margin-top:10px; font-size:12px; }
        .success { background:#28a745; color:white; padding:30px; border-radius:10px; text-align:center; }
        .back { text-align:center; margin-top:20px; }
        .back a { color:#667eea; text-decoration:none; }
        form { margin:0; }
    </style>
</head>
<body>
    <div class="box">
        <?php if($logoUrl): ?>
        <img src="<?php echo htmlspecialchars($logoUrl); ?>" class="logo" alt="Logo">
        <?php endif; ?>
        
        <h1>🔐 KYC Verification</h1>
        <div class="welcome">Hello, <?php echo htmlspecialchars($client['firstname']); ?>! 👋</div>
        
        <div class="timer" id="timer">⏱️ Session: 15:00</div>
        
        <?php if($kyc_verified == 1): ?>
            <div class="success">
                <h2>✓ KYC Verified!</h2>
                <p>Your verification is complete.</p>
            </div>
        <?php else: ?>
            <p style="text-align:center; color:#666; margin-bottom:20px;">
                Select your document to begin verification:
            </p>
            
            <div class="options">
                <form action="/kyc.php?action=step2_aadhar" method="POST">
                    <button type="submit" class="card">
                        <div class="icon">📱</div>
                        <h3>Aadhaar Card</h3>
                        <p>Instant OTP verification</p>
                        <span class="badge">Recommended</span>
                    </button>
                </form>
                
                <form action="/kyc.php?action=step2_dl" method="POST">
                    <button type="submit" class="card">
                        <div class="icon">🪪</div>
                        <h3>Driving License</h3>
                        <p>Manual verification</p>
                        <span class="badge">1-2 days</span>
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <div class="back">
            <a href="/clientarea.php">← Back to Dashboard</a>
        </div>
    </div>
    
    <script>
        let t = 900;
        setInterval(() => {
            const m = Math.floor(t/60), s = t%60;
            document.getElementById('timer').textContent = '⏱️ Session: ' + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
            if(--t < 0) location.href='/clientarea.php';
        }, 1000);
    </script>
</body>
</html>
