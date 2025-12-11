<?php
// request_to_pay.php
session_start();
require_once 'config.php';
require_once 'momocollection.php';
require_once 'database.php';

// Create database connection
$db = new SQLite3(DB_PATH);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $amount = isset($_POST['custom_amount']) && !empty($_POST['custom_amount']) 
        ? $_POST['custom_amount'] 
        : $_POST['amount'];
    
    $donorName = $_POST['donor_name'];
    $donorEmail = $_POST['donor_email'];
    $phone = $_POST['phone'];
    $frequency = isset($_POST['DonationFrequency']) && $_POST['DonationFrequency'] === 'Monthly' 
        ? 'monthly' 
        : 'one_time';
    
    // Validate amount
    if ($amount < 500) {
        die("Minimum donation amount is UGX 500");
    }
    
    // Validate phone number (MTN Uganda)
    if (!preg_match('/^(077|078|076|070|075)\d{7}$/', $phone)) {
        die("Please enter a valid MTN Uganda phone number");
    }
    
    // Save donor to database
    $stmt = $db->prepare("INSERT INTO donors (name, email, phone) VALUES (:name, :email, :phone)");
    $stmt->bindValue(':name', $donorName, SQLITE3_TEXT);
    $stmt->bindValue(':email', $donorEmail, SQLITE3_TEXT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->execute();
    
    $donorId = $db->lastInsertRowID();
    
    // Generate unique transaction ID
    $transactionId = 'PEF_' . date('YmdHis') . '_' . uniqid();
    
    // Save donation to database
    $stmt = $db->prepare("INSERT INTO donations (donor_id, amount, phone, reference, frequency) 
                          VALUES (:donor_id, :amount, :phone, :reference, :frequency)");
    $stmt->bindValue(':donor_id', $donorId, SQLITE3_INTEGER);
    $stmt->bindValue(':amount', $amount, SQLITE3_FLOAT);
    $stmt->bindValue(':phone', $phone, SQLITE3_TEXT);
    $stmt->bindValue(':reference', $transactionId, SQLITE3_TEXT);
    $stmt->bindValue(':frequency', $frequency, SQLITE3_TEXT);
    $stmt->execute();
    
    $donationId = $db->lastInsertRowID();
    
    // Initialize MTN Collection
    $momo = new MTNCollection(PRIMARY_KEY);
    
    try {
        // Request payment
        $result = $momo->requestToPay(
            $amount,
            $phone,
            $transactionId,
            'Donation to Pearl Edu Fund',
            'Thank you for supporting education'
        );
        
        if ($result['success']) {
            // Update donation with reference ID
            $stmt = $db->prepare("UPDATE donations SET transaction_id = :transaction_id WHERE id = :id");
            $stmt->bindValue(':transaction_id', $result['reference_id'], SQLITE3_TEXT);
            $stmt->bindValue(':id', $donationId, SQLITE3_INTEGER);
            $stmt->execute();
            
            // Redirect to confirmation page
            $_SESSION['donation_id'] = $donationId;
            $_SESSION['transaction_id'] = $transactionId;
            $_SESSION['reference_id'] = $result['reference_id'];
            
            header("Location: payment_status.php?ref=" . $result['reference_id']);
            exit();
        } else {
            // Log error
            error_log("MTN Payment failed: " . print_r($result, true));
            
            // Update donation status
            $stmt = $db->prepare("UPDATE donations SET status = 'failed' WHERE id = :id");
            $stmt->bindValue(':id', $donationId, SQLITE3_INTEGER);
            $stmt->execute();
            
            die("Failed to initiate payment. Please try again.");
        }
    } catch (Exception $e) {
        error_log("MTN Payment exception: " . $e->getMessage());
        die("Payment service is temporarily unavailable. Please try again later.");
    }
} else {
    header("Location: donate.php");
    exit();
}
?>