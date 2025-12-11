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
    $initFile = __DIR__ . '/init-db.php';
    if (file_exists($initFile)) {
        require_once $initFile;
    }
}

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// Enable real-time database changes and proper transaction handling
$db->exec('PRAGMA synchronous = FULL');
$db->exec('PRAGMA journal_mode = WAL');
$db->setAttribute(PDO::ATTR_AUTOCOMMIT, false);

// Handle signup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $passwordConfirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
    $organization = isset($_POST['organization']) ? trim($_POST['organization']) : '';
    $profilePhoto = $_FILES['profile_photo'] ?? null;
    $errors = [];
    
    // --- Validation Checks ---
    if (empty($username) || empty($name) || empty($email) || empty($phone) || empty($password) || empty($passwordConfirm)) {
        $errors[] = 'All required fields must be filled.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    $profilePhotoPath = null;

    if ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($profilePhoto['type'], $allowedTypes)) {
            $errors[] = 'Invalid file type for profile photo. Only JPG, PNG, or GIF allowed.';
        }
        // Basic file size check (5MB limit)
        if ($profilePhoto['size'] > 5000000) {
            $errors[] = 'Profile photo file size exceeds 5MB.';
        }
    }

    if (!empty($errors)) {
        $_SESSION['old_signup'] = [
            'name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone, 'organization' => $organization
        ];
        $_SESSION['signup_errors'] = $errors;
        header('Location: ../auth.php?mode=signup');
        exit;
    }
    
    // --- Security and Database Operations ---
    
    try {
        // Check for duplicate username or email
        $checkStmt = $db->prepare('SELECT COUNT(*) FROM donors WHERE username = ? OR email = ?');
        $checkStmt->execute([$username, $email]);
        if ($checkStmt->fetchColumn() > 0) {
            $errors[] = 'Username or email is already registered.';
            $_SESSION['old_signup'] = [
                'name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone, 'organization' => $organization
            ];
            $_SESSION['signup_errors'] = $errors;
            header('Location: ../auth.php?mode=signup');
            exit;
        }

        // Generate unique ID code
        $donorIdCode = 'DONOR-' . strtoupper(uniqid());
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        // Start transaction
        $db->beginTransaction();
        
        // 1. Insert Member Record
        // ADDITION: Setting 'is_admin' to 0 for new signups
        $insertStmt = $db->prepare('
            INSERT INTO donors (
                username, password_hash, name, email, phone, organization, donor_id_code, is_admin
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 0
            )
        ');
        $insertStmt->execute([
            $username, $passwordHash, $name, $email, $phone, $organization, $donorIdCode
        ]);
        
        $memberId = $db->lastInsertId();

        // 2. Handle Profile Photo Upload
        if ($profilePhoto && $profilePhoto['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = pathinfo($profilePhoto['name'], PATHINFO_EXTENSION);
            $photoName = $memberId . '_' . time() . '.' . $extension;
            $profilePhotoPath = $uploadDir . $photoName;
            
            if (move_uploaded_file($profilePhoto['tmp_name'], $profilePhotoPath)) {
                // Update record with photo path
                $db->prepare('UPDATE donors SET profile_photo_path = ? WHERE id = ?')
                   ->execute([$profilePhotoPath, $memberId]);
            } else {
                // If upload fails, just log it and continue without photo path
                // This won't throw an error, but it's good practice.
            }
        }
        
        // Commit transaction
        $db->commit();
        
        // 3. Verification and Auto-login
        $verifyStmt = $db->prepare('SELECT id, is_admin FROM donors WHERE id = ?');
        $verifyStmt->execute([$memberId]);
        $verifiedRecord = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$verifiedRecord) {
            throw new Exception('Database verification failed - record not saved');
        }
        
        // Successful signup - auto-login (member session keys)
        // STANDARDIZED: Using 'id' for the session key
        $_SESSION['id'] = $verifiedRecord['id']; 
        $_SESSION['member_username'] = $username;
        $_SESSION['member_name'] = $name;
        $_SESSION['member_organization'] = $organization;
        $_SESSION['member_profile_photo'] = $profilePhotoPath;
        $_SESSION['login_time'] = time();
        $_SESSION['is_admin'] = $verifiedRecord['is_admin'] ?? 0;
        $_SESSION['signup_success'] = 'Account created successfully! Welcome, ' . $name . '.';
        
        // Redirect to dashboard
        header('Location: ../dashboard.php');
        exit;
        
    } catch (Exception $dbError) {
        // Rollback transaction on error
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        // General error handling
        $errorMessage = 'Database error: ' . $dbError->getMessage();
        
        $_SESSION['old_signup'] = [
            'name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone, 'organization' => $organization
        ];
        $_SESSION['signup_errors'] = [$errorMessage];
        header('Location: ../auth.php?mode=signup');
        exit;
    }
}
?>