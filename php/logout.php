<?php
// php/logout.php

session_start();

// Destroy all session variables (clears $_SESSION array)
$_SESSION = array();

// If it's a session using cookies, clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session data on the server
session_destroy();

// Set a one-time success message to be displayed on the login page (auth.php)
$_SESSION['logout_success_message'] = "You have been successfully logged out.";

// Redirect to the login page
header('Location: ../auth.php?mode=login');
exit;
?>