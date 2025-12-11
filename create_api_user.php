<?php
// create_api_user.php - FIXED VERSION

// CORRECT KEYS FROM YOUR IMAGE
$primaryKey = '3cb17ed3a89d45478eb08bf922d2a6b1';
$secondaryKey = '3e3fd81469a44246886ed0e7244553c9';

echo "<h2>MTN MoMo API Setup - FIXED</h2>";
echo "<p>Using Primary Key: " . substr($primaryKey, 0, 8) . "..." . substr($primaryKey, -8) . "</p>";

// Test which environment works
$environments = [
    'sandbox' => 'https://sandbox.momodeveloper.mtn.com',
    'production' => 'https://momodeveloper.mtn.com'
];

foreach ($environments as $envName => $baseUrl) {
    echo "<h3>Testing $envName Environment</h3>";
    
    // Test if subscription key is valid
    $testUrl = $baseUrl . '/collection/v1_0/balance';
    $headers = [
        'Ocp-Apim-Subscription-Key: ' . $primaryKey,
        'Authorization: Bearer dummy'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    
    if ($httpCode === 401) {
        echo "<p style='color:green;'>✅ Subscription key is VALID for $envName!</p>";
        
        // Now create API User
        createApiUserInEnvironment($envName, $baseUrl, $primaryKey);
        break;
    } else {
        echo "<p style='color:orange;'>⚠️ Key not valid for $envName</p>";
    }
}

function createApiUserInEnvironment($envName, $baseUrl, $primaryKey) {
    echo "<h4>Creating API User in $envName...</h4>";
    
    // Generate unique API User ID
    $apiUserId = 'pearl_' . date('YmdHis') . '_' . rand(1000, 9999);
    
    // Step 1: Create API User
    $url = $baseUrl . '/v1_0/apiuser';
    $headers = [
        'X-Reference-Id: ' . $apiUserId,
        'Content-Type: application/json',
        'Ocp-Apim-Subscription-Key: ' . $primaryKey
    ];
    
    // Callback host - for sandbox use localhost, for production use your domain
    $callbackHost = ($envName === 'sandbox') ? 'localhost' : 'yourdomain.com';
    
    $data = json_encode([
        'providerCallbackHost' => $callbackHost
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p>Create User - HTTP Code: $httpCode</p>";
    
    if ($httpCode === 201) {
        echo "<p style='color:green;'>✅ API User created successfully!</p>";
        echo "<p>API User ID: <strong>$apiUserId</strong></p>";
        
        // Step 2: Generate API Key
        generateApiKey($baseUrl, $apiUserId, $primaryKey, $envName);
    } else {
        echo "<p style='color:red;'>❌ Failed to create API User</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
        
        // Try alternative: Maybe API User already exists
        echo "<p>Trying to generate key for existing user 'Dalton'...</p>";
        generateApiKey($baseUrl, 'Dalton', $primaryKey, $envName);
    }
    
    curl_close($ch);
}

function generateApiKey($baseUrl, $apiUserId, $primaryKey, $envName) {
    $url = $baseUrl . '/v1_0/apiuser/' . $apiUserId . '/apikey';
    $headers = [
        'Ocp-Apim-Subscription-Key: ' . $primaryKey
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p>Generate API Key - HTTP Code: $httpCode</p>";
    
    if ($httpCode === 201) {
        $data = json_decode($response, true);
        if (isset($data['apiKey'])) {
            $apiKey = $data['apiKey'];
            
            echo "<div style='border:3px solid green; padding:20px; background:#f8fff8; margin:20px 0;'>";
            echo "<h3 style='color:green;'>🎉 SUCCESS! CREDENTIALS GENERATED</h3>";
            echo "<p><strong>Environment:</strong> $envName</p>";
            echo "<p><strong>API User ID:</strong> $apiUserId</p>";
            echo "<p><strong>API Key:</strong> <code style='background:#eee; padding:5px;'>$apiKey</code></p>";
            echo "<p><strong>Primary Key:</strong> " . substr($primaryKey, 0, 8) . "..." . substr($primaryKey, -8) . "</p>";
            echo "<p style='color:red; font-weight:bold;'>⚠️ SAVE THE API KEY NOW! You won't see it again.</p>";
            echo "</div>";
            
            // Generate config file
            generateConfigFile($envName, $apiUserId, $apiKey, $primaryKey);
        }
    } else {
        echo "<p style='color:red;'>❌ Failed to generate API Key</p>";
        echo "<p>Response: " . htmlspecialchars($response) . "</p>";
    }
    
    curl_close($ch);
}

function generateConfigFile($envName, $apiUserId, $apiKey, $primaryKey) {
    $config = "<?php
// config.php - Generated for MTN MoMo Collection API
// Environment: $envName
// Generated on: " . date('Y-m-d H:i:s') . "

// API Environment
define('API_ENVIRONMENT', '$envName'); // 'sandbox' or 'production'
define('CURRENCY', 'UGX');

// API Base URLs
if (API_ENVIRONMENT === 'sandbox') {
    define('API_BASE_URL', 'https://sandbox.momodeveloper.mtn.com');
} else {
    define('API_BASE_URL', 'https://momodeveloper.mtn.com');
}

// Your Subscription Keys (from MTN Developer Portal)
define('PRIMARY_KEY', '$primaryKey');
define('SECONDARY_KEY', '3e3fd81469a44246886ed0e7244553c9');

// API User Credentials
define('COLLECTION_API_USER_ID', '$apiUserId');
define('COLLECTION_API_KEY', '$apiKey');

// Database Configuration
define('DB_PATH', dirname(__FILE__) . '/database/pearl_edu_fund.db');
define('SITE_URL', 'http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "');
define('CALLBACK_URL', SITE_URL . '/payment_callback.php');

// Sandbox Test Numbers (for testing only)
if (API_ENVIRONMENT === 'sandbox') {
    define('SANDBOX_TEST_PHONE_SUCCESS', '46733123454'); // Always succeeds
    define('SANDBOX_TEST_PHONE_FAILURE', '46733123453'); // Always fails
    define('SANDBOX_TEST_PIN', '123456'); // Test PIN
}

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>";

    echo "<h4>config.php File:</h4>";
    echo "<pre style='background:#f0f0f0; padding:15px; border:1px solid #ccc; overflow:auto;'>" . htmlspecialchars($config) . "</pre>";
    
    // Try to save file
    if (file_put_contents('config.php', $config)) {
        echo "<p style='color:green;'>✅ config.php created successfully!</p>";
        echo "<p><a href='config.php' target='_blank'>View config.php</a></p>";
    } else {
        echo "<p style='color:red;'>⚠️ Could not write config.php. Please create it manually with the content above.</p>";
    }
}

// If all environments fail, show help
echo "<hr>";
echo "<h3>If All Tests Fail:</h3>";
echo "<ol>
    <li>Copy your keys EXACTLY as shown in MTN portal (watch for 0 vs O, 1 vs l)</li>
    <li>Your subscription might need activation - check MTN portal</li>
    <li>Try using 'Dalton' as API User ID directly</li>
    <li>Contact MTN support: developersupport@mtn.com</li>
</ol>";

// Try direct approach with 'Dalton'
echo "<h3>Direct Approach - Use 'Dalton' as API User:</h3>";
echo "<p>Try this PHP code:</p>";
echo "<pre style='background:#f0f0f0; padding:10px;'>
// In your config.php:
define('COLLECTION_API_USER_ID', 'Dalton');
define('COLLECTION_API_KEY', '[Generate API Key for Dalton in MTN Portal]');
</pre>";
?>