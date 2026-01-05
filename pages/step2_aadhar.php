<?php
session_start();
require_once __DIR__ . '/../lib/config.php';



if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

if (!isset($_COOKIE['client_state'])) {
    header('Location: index.php');
    exit();
}


if (!(isset($_SESSION['sub_state']) == isset($_COOKIE['client_state']))) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VyomCloud Digital KYC Verification Platform</title>
</head>
<body>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            overflow: hidden;
        }

        .modal {
            width: 80%;
            max-width: 600px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            padding: 20px;
            position: relative;
            text-align: center;
        }

        .header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
        }

        .form-div form {
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }

        .form-div form input {
            width: 350px;
            height: 30px;
            border-style: groove;
            font-family: "Open Sans", sans-serif;
            font-size: 16px;
            line-height: 20px;
            background-color: rgb(255, 255, 255);
            border-radius: 4px;
            color: rgb(73, 80, 87);
        }

        .form-div form button {
            width: 150px;
            background-color: #007bff;
            border-radius: 5px;
            border: none;
            color: white;
            transition: 0.3s;
            font-size: 16px;
            cursor: pointer;
            padding: 15px;
        }

        .form-div form button:hover {
            background-color: #0056b3;
        }

        .countdown {
            padding-top: 20px;
            padding-bottom: 20px;
        }
    </style>

    <div class="modal">
        <div class="header">
            <h1>VyomCloud Digital KYC Verification Platform</h1>
        </div>
        <h2>Enter Your Aadhaar Number</h2>
        <div id="countdown" class="countdown"></div>
        <div class="form-div">
            <form action="/kyc.php?action=step3_aadhar" method="POST" id="aadharform">
                <input type="text" id="aadhaar" name="aadhaar" maxlength="12" placeholder="Enter Your Aadhar Number..." required>
                <span id="lblError" class="error"></span>
                <button type="submit" id='aadhar_btn'>Verify</button>
            </form>
        </div>
    </div>

    <script>
        let timer;
        let countdownTime = 900;

        function updateCountdown() {
            const minutes = Math.floor(countdownTime / 60);
            const seconds = countdownTime % 60;
            document.getElementById('countdown').textContent = 
                `Session Time: ${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            countdownTime--;

            if (countdownTime < 0) {
                logout();
            }
        }

        function logout() {
            window.location.href = 'logout.php';
        }

        window.onload = function() {
            updateCountdown();
            setInterval(updateCountdown, 1000);
        }

        function validateAadhaar(event) {
            event.preventDefault();
            var aadhaarField = document.getElementById("aadhaar");
            var aadhaar = aadhaarField.value;
            var aadhaarRegex = /^[2-9]{1}[0-9]{11}$/;
            if (aadhaarRegex.test(aadhaar)) {
                document.getElementById("aadharform").submit();
            }
            else {
                alert("Invalid Aadhaar number. Please enter a valid 12-digit Aadhaar number.");
                aadhaarField.value = "";
                aadhaarField.focus();
            }
        }

        document.getElementById('aadhar_btn').addEventListener('click', validateAadhaar);
    </script>

</body>
</html>
