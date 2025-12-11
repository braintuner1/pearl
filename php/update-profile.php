<?php
session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ../auth.php?mode=login&error=' . urlencode('Please sign in'));
    exit;
}

$dbPath = __DIR__ . '/../database/donor.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$donorId = $_SESSION['id'];

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$organization = isset($_POST['organization']) ? trim($_POST['organization']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

$errors = [];

if (empty($name)) $errors[] = 'Name is required';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';

if ($password !== '' && strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters';
}

if ($password !== '' && $password !== $password_confirm) {
    $errors[] = 'Passwords do not match';
}

// Validate email uniqueness (if changed)
$stmt = $db->prepare('SELECT id FROM donors WHERE email = ? AND id != ?');
$stmt->execute([$email, $donorId]);
if ($stmt->fetch()) {
    $errors[] = 'Email already in use by another account';
}

// Handle profile photo upload if provided
$profilePhotoPath = null;
if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['profile_photo'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg','jpeg','png','gif'];
    if (!in_array($fileExt, $allowedExts)) {
        $errors[] = 'Profile photo must be JPG, PNG, or GIF';
    } else {
        $uploadDir = __DIR__ . '/../images/avatar/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $filename = 'profile_' . time() . '_' . uniqid() . '.' . $fileExt;
        $uploadPath = $uploadDir . $filename;
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $errors[] = 'Failed to move uploaded file';
        } else {
            $profilePhotoPath = 'images/avatar/' . $filename;
        }
    }
}

if (!empty($errors)) {
    $_SESSION['profile_errors'] = $errors;
    header('Location: ../edit-profile.php');
    exit;
}

try {
    $db->beginTransaction();

    $updateFields = ['name' => $name, 'email' => $email, 'phone' => $phone, 'organization' => $organization];
    $params = [$name, $email, $phone, $organization, $donorId];

    // If password provided, update password_hash
    if ($password !== '') {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $updateSql = 'UPDATE donors SET name = ?, email = ?, phone = ?, organization = ?, password_hash = ? WHERE id = ?';
        $stmt = $db->prepare($updateSql);
        $stmt->execute([$name, $email, $phone, $organization, $passwordHash, $donorId]);
    } else {
        $updateSql = 'UPDATE donors SET name = ?, email = ?, phone = ?, organization = ? WHERE id = ?';
        $stmt = $db->prepare($updateSql);
        $stmt->execute($params);
    }

    // If a new profile photo was uploaded, update the path and try to remove old file
    if ($profilePhotoPath) {
        // Get old path
        $oldStmt = $db->prepare('SELECT profile_photo_path FROM donors WHERE id = ?');
        $oldStmt->execute([$donorId]);
        $old = $oldStmt->fetch(PDO::FETCH_ASSOC);
        if ($old && !empty($old['profile_photo_path'])) {
            $oldPath = __DIR__ . '/../' . $old['profile_photo_path'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }

        $stmt = $db->prepare('UPDATE donors SET profile_photo_path = ? WHERE id = ?');
        $stmt->execute([$profilePhotoPath, $donorId]);
        $_SESSION['member_profile_photo'] = $profilePhotoPath;
    }

    $db->commit();

    // Update session display name and organization
    $_SESSION['member_name'] = $name;
    $_SESSION['member_organization'] = $organization;

    $_SESSION['profile_success'] = 'Profile updated successfully';
    header('Location: ../edit-profile.php');
    exit;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['profile_errors'] = ['Database error: ' . $e->getMessage()];
    header('Location: ../edit-profile.php');
    exit;
}

?>
