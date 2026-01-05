<?php

session_start();
require_once __DIR__ . '/../lib/config.php';


function decode_jwt($jwt) {
    return json_decode(base64_decode(str_replace('_', '/', str_replace('-','+',explode('.', $jwt)[1]))));
}

if (isset($_GET['code']) && isset($_GET['state'])) {
    $code = $_GET['code'];
    $state = $_GET['state'];

    
    if ($_COOKIE['client_state'] == $_SESSION['sub_state']) {
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, TOKEN_ENDPOINT);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => REDIRECT_URL,
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $token_response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'cURL Error (Token Request): ' . curl_error($ch);
            curl_close($ch);
            header("Refresh: 2; url=index.php");
            exit();
        }

        curl_close($ch);

        $token_data = json_decode($token_response, true);
        $access_token = $token_data['access_token'];
        $id_token = decode_jwt($token_data['id_token']);

        if ($access_token) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, USERINFO_ENDPOINT);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'access_token' => $access_token
            ]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $user_info_response = curl_exec($ch);
            curl_close($ch);

            $user_info = json_decode($user_info_response, true);

            if (isset($user_info['error'])) {
                echo 'User Info Error: ' . htmlspecialchars($user_info['error']);
                header("Refresh: 2; url=index.php");
                exit();
            }

            $_SESSION['user'] = $user_info;
            header('Location: step1.php');
            exit();

        } else {
            echo 'Failed to obtain access token. Redirecting...';
            header("Refresh: 2; url=index.php");
        }

    }
    else {
        echo 'State value does not match. Possible CSRF attempt.';
        header("Refresh: 2; url=index.php");
        exit();
    }
    
} 
else {
    echo 'Authorization code or state not received';
    header("Refresh: 2; url=index.php");
}