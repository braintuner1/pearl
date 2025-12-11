<?php

function getAccessToken() {
    $apiUser = "YOUR_API_USER_ID";
    $apiKey = "YOUR_API_KEY";
    $subscriptionKey = "YOUR_COLLECTION_PRIMARY_KEY"; // From MTN
    $tokenUrl = "https://proxy.momoapi.mtn.com/collection/token/"; 

    $credentials = base64_encode("$apiUser:$apiKey");

    $headers = [
        "Authorization: Basic $credentials",
        "Ocp-Apim-Subscription-Key: $subscriptionKey"
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['access_token'];
}
