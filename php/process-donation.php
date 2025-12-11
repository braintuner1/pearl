<?php
session_start();

// Check if member is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../auth.php?mode=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard.php');
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;

if ($project_id <= 0 || $amount <= 0) {
    $_SESSION['donation_error'] = 'Invalid donation amount';
    header('Location: ../dashboard.php');
    exit;
}

try {
    $db = new PDO('sqlite:' . __DIR__ . '/../database/donor.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $id = $_SESSION['id'];

    // Calculate loyalty points (1 point per 1000 UGX)
    $points_earned = (int)($amount / 1000);

    // Start transaction
    $db->beginTransaction();

    // Insert donation record (id)
    $stmt = $db->prepare('
        INSERT INTO donations (id, project_id, amount, points_earned, status)
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$id, $project_id, $amount, $points_earned, 'completed']);

    // Update donor loyalty points
    $stmt = $db->prepare('
        UPDATE donors SET loyalty_points = loyalty_points + ? WHERE id = ?
    ');
    $stmt->execute([$points_earned, $id]);

    // Update project raised amount
    $stmt = $db->prepare('
        UPDATE projects SET raised_amount = raised_amount + ? WHERE id = ?
    ');
    $stmt->execute([$amount, $project_id]);

    // Update progress percentage
    $stmt = $db->prepare('
        SELECT target_amount, raised_amount FROM projects WHERE id = ?
    ');
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $progress = ($project['raised_amount'] / $project['target_amount']) * 100;
    $stmt = $db->prepare('UPDATE projects SET progress_percentage = ? WHERE id = ?');
    $stmt->execute([$progress, $project_id]);

    // Insert wallet transaction
    $stmt = $db->prepare('
        INSERT INTO wallet_transactions (id, transaction_type, amount, description)
        VALUES (?, ?, ?, ?)
    ');
    $stmt->execute([
        $id,
        'donation',
        $amount,
        'Donation to project #' . $project_id
    ]);

    $db->commit();

    $_SESSION['donation_success'] = 'Thank you! Your donation has been processed successfully. You earned ' . $points_earned . ' loyalty points!';
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['donation_error'] = 'Error processing donation: ' . $e->getMessage();
}

header('Location: ../dashboard.php');
exit;
?>
