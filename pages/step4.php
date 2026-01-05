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

        .text{
            width: 50px;
        }

        .form-div form{
            display: flex;
            flex-direction: column;
            gap: 20px;
            align-items: center;
        }

        .form-div form input{
            width: 350px;
            height: 30px;
        }

        .form-div form button {
            width: 150px;
            background-color:#007bff;
            border-radius: 5px;
            border-style: none;
            color: white;
            transition: 0.3s;
            font-size: 16px;
            cursor: pointer;
            padding: 15px 15px 15px 15px;
        }

        .form-div form button:hover {
            background-color: #0056b3;
        }
        
        .form-div form input {
            border-style: groove;
            font-family: "Open Sans", sans-serif;
            font-size: 16px;
            line-height: 20px;
            background-color: rgb(255, 255, 255);
            border-radius: 4px;
            color: rgb(73, 80, 87); 
            display: block;
            font-weight: 400;
            height: 38px;
            width: 430px;
            text-align: start;
        }

        .form-div form label {
            color: rgb(102, 102, 102);
            font-size: 16px;
            margin-bottom: 20px;
            text-align: center;
            font-family: Arial, sans-serif;
            background: none 0% 0% / auto repeat scroll padding-box border-box rgb(255, 255, 255);
        }

        .countdown{
            padding-top: 20px;
            padding-bottom: 20px;
        }
    </style>
        <div class="modal">
            <div class="header">
                <h1>VyomCloud Digital KYC Verification Platform</h1>
            </div>
            <label >Type Your OTP here</label>
            <div id="countdown" class="countdown"></div>
            <div class="form-div">
                <form action="/kyc.php?action=verify_otp" method="POST">
                    <input type="text" id="otp" name="otp" placeholder="Enter Your OTP" required>
                    <button type="submit" name="otp_btn" value="verify">Verify OTP</button>
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

    
            window.onload = function() {
                updateCountdown();
                setInterval(updateCountdown, 1000);
            }
        </script>
</body>
</html>
