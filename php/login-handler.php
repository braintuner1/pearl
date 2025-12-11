<?php
session_start();

// Database connection (SQLite for simplicity)
$dbPath = __DIR__ . '/../database/donor.db';

// Ensure database directory exists
if (!is_dir(dirname($dbPath))) {
    mkdir(dirname($dbPath), 0755, true);
}

// If the database file doesn't exist, attempt to initialize it
if (!file_exists($dbPath)) {
    // init-db.php will create the database and required tables
    $initFile = __DIR__ . '/init-db.php';
    if (file_exists($initFile)) {
        // include once to avoid re-running on subsequent requests
        require_once $initFile;
    }
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Renamed variable to $identifier since it can be username OR email
    $identifier = isset($_POST['username']) ? trim($_POST['username']) : ''; 
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    
    if (empty($identifier) || empty($password)) {
        $_SESSION['old_login'] = ['username' => $identifier];
        $_SESSION['login_error'] = 'Username and password are required';
        header('Location: ../auth.php?mode=login&error=' . urlencode('Username and password are required'));
        exit;
    }
    
    try {
        // Query donor by username OR email
        $stmt = $db->prepare('SELECT * FROM donors WHERE username = ? OR email = ?');
        // Pass the single input value ($identifier) for both checks
        $stmt->execute([$identifier, $identifier]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($member && password_verify($password, $member['password_hash'])) {
            // Successful login - set member session keys
            $_SESSION['id'] = $member['id'];
            $_SESSION['member_username'] = $member['username'];
            $_SESSION['member_name'] = $member['name'];
            $_SESSION['member_organization'] = $member['organization'];
            $_SESSION['login_time'] = time();
            
            // FIX: Set the admin privilege status in the session
            $_SESSION['is_admin'] = $member['is_admin']; // This value is 1 for admin, 0 otherwise
            
            // Redirect to dashboard
            header('Location: ../dashboard.php');
            exit;
        } else {
            $_SESSION['old_login'] = ['username' => $identifier];
            $_SESSION['login_error'] = 'Invalid username or password';
            header('Location: ../auth.php?mode=login&error=' . urlencode('Invalid username or password'));
            exit;
        }
    } catch (Exception $e) {
        $_SESSION['login_error'] = 'Database error: ' . $e->getMessage();
        // Corrected redirect to auth.php
        header('Location: ../auth.php?mode=login&error=' . urlencode('Database error: ' . $e->getMessage()));
        exit;
    }
}
?>