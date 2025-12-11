<?php
// quick_fix.php - SIMPLE SOLUTION
$primaryKey = '3cb17ed3a89d45478eb08bf922d2a6b1'; // CORRECT KEY
$secondaryKey = '3e3fd81469a44246886ed0e7244553c9'; // CORRECT KEY

// Try SANDBOX first (most likely)
$baseUrl = 'https://sandbox.momodeveloper.mtn.com';

echo "<h2>Quick Fix - Creating API User</h2>";

// Create API User
$apiUserId = 'pearl_edu_' . time();
$url = $baseUrl . '/v1_0/apiuser';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Reference-Id: ' . $apiUserId,
    'Content-Type: application/json',
    'Ocp-Apim-Subscription-Key: ' . $primaryKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'providerCallbackHost' => 'localhost'
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($code === 201) {
    echo "<p style='color:green;'>✅ API User Created!</p>";
    echo "<p>User ID: $apiUserId</p>";
    
    // Get API Key
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1_0/apiuser/' . $apiUserId . '/apikey');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Ocp-Apim-Subscription-Key: ' . $primaryKey]);
    
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    if (isset($data['apiKey'])) {
        echo "<div style='background:lightgreen; padding:20px;'>";
        echo "<h3>✅ CREDENTIALS READY!</h3>";
        echo "<p><strong>Add to config.php:</strong></p>";
        echo "<pre>
define('API_ENVIRONMENT', 'sandbox');
define('PRIMARY_KEY', '$primaryKey');
define('SECONDARY_KEY', '$secondaryKey');
define('COLLECTION_API_USER_ID', '$apiUserId');
define('COLLECTION_API_KEY', '" . $data['apiKey'] . "');
        </pre>";
        echo "</div>";
    }
} else {
    echo "<p style='color:red;'>❌ Failed: HTTP $code</p>";
    echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    
    // Try PRODUCTION
    echo "<p>Trying production...</p>";
    $baseUrl = 'https://momodeveloper.mtn.com';
    
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/v1_0/apiuser');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-Reference-Id: ' . $apiUserId,
        'Content-Type: application/json',
        'Ocp-Apim-Subscription-Key: ' . $primaryKey
    ]);
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($code === 201) {
        echo "<p style='color:green;'>✅ Works in PRODUCTION!</p>";
        echo "<p>Use production environment in config.php</p>";
    }
}

curl_close($ch);
?>