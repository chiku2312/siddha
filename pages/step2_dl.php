<?php
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/mysql.php';

if (!isset($_SESSION['user'])) {
    header('Location: /clientarea.php');
    exit();
}

$client = $_SESSION['user'];
$companyLogo = LOGO_URL;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driving License Verification</title>
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
            max-width: 550px;
            width: 100%;
            text-align: center;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 16px;
        }
        .form-group {
            margin-bottom: 25px;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
            font-size: 14px;
        }
        input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
        }
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="<?= $companyLogo ?>" alt="Logo" class="logo">
        <h1>🚗 Driving License Verification</h1>
        <p class="subtitle">Enter your Driving License details below</p>
        
        <form action="kyc.php?action=step3_dl" method="POST">
            <div class="form-group">
                <label for="dl_number">Driving License Number</label>
                <input 
                    type="text" 
                    id="dl_number" 
                    name="dl_number" 
                    placeholder="MH12-20190012345"
                    required
                    pattern="[A-Z]{2}[0-9]{2}[-\s]?[0-9]{11}"
                    title="Format: MH12-20190012345"
                >
                <p class="help-text">Format: State Code + RTO Code + Year + Number (e.g., MH12-20190012345)</p>
            </div>
            
            <div class="form-group">
                <label for="dob">Date of Birth</label>
                <input 
                    type="date" 
                    id="dob" 
                    name="dob" 
                    required
                    max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                >
                <p class="help-text">As mentioned in your Driving License</p>
            </div>
            
            <button type="submit" class="btn">Verify License →</button>
        </form>
        
        <a href="kyc.php" class="back-link">← Choose Different Document</a>
    </div>
</body>
</html>
