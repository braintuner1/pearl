<?php
// config.php
define('API_ENVIRONMENT', 'sandbox'); // Change to 'production' when live
define('CURRENCY', 'UGX');

// MTN MoMo API Credentials
if (API_ENVIRONMENT === 'sandbox') {
    define('API_BASE_URL', 'https://sandbox.momodeveloper.mtn.com');
} else {
    define('API_BASE_URL', 'https://momodeveloper.mtn.com');
}

// Your subscription keys (from your image)
define('PRIMARY_KEY', '3cb17ed3a89d45478eb08bf922d2a6b1');
define('SECONDARY_KEY', 'ae286dc43ad94dabb9ae4f8c5885d7e6');

// Collection API credentials
define('COLLECTION_API_USER_ID', ''); // You'll generate this
define('COLLECTION_API_KEY', ''); // You'll generate this

// Database configuration
define('DB_PATH', 'database/donations_db.db');

// Website configuration
define('SITE_URL', 'http://localhost/PEFA-Website%20(2)/PEFA-Website'); // Change to your domain
define('CALLBACK_URL', SITE_URL . '/payment_callback.php');
?>
