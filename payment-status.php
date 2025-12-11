<?php
// payment_status.php
session_start();
require_once 'config.php';
require_once 'momocollection.php';
require_once 'database.php';

$db = new SQLite3(DB_PATH);

$referenceId = $_GET['ref'] ?? ($_SESSION['reference_id'] ?? '');
$donationId = $_SESSION['donation_id'] ?? 0;

if (!$referenceId) {
    header("Location: donate.php");
    exit();
}

// Check payment status
$momo = new MTNCollection(PRIMARY_KEY);
$status = $momo->getPaymentStatus($referenceId);

// Update database with status
if ($status && isset($status['status'])) {
    $stmt = $db->prepare("UPDATE donations SET status = :status WHERE transaction_id = :ref");
    $stmt->bindValue(':status', $status['status'], SQLITE3_TEXT);
    $stmt->bindValue(':ref', $referenceId, SQLITE3_TEXT);
    $stmt->execute();
    
    $currentStatus = $status['status'];
} else {
    $currentStatus = 'PENDING';
}

// Get donation details
$stmt = $db->prepare("SELECT d.*, dn.name, dn.email FROM donations d 
                     JOIN donors dn ON d.donor_id = dn.id 
                     WHERE d.transaction_id = :ref");
$stmt->bindValue(':ref', $referenceId, SQLITE3_TEXT);
$result = $stmt->execute();
$donation = $result->fetchArray(SQLITE3_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Status - Pearl Edu Fund</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/templatemo-kind-heart-charity.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg bg-light shadow-lg">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="docs/extracted/IMG_5792_images/image_1_1.jpeg" class="logo img-fluid" alt="Pearl Edu Fund">
                <span>Pearl Edu Fund</span>
            </a>
        </div>
    </nav>

    <main class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Payment Status</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($currentStatus === 'SUCCESSFUL'): ?>
                            <div class="alert alert-success text-center">
                                <h4>✅ Payment Successful!</h4>
                                <p>Thank you for your donation to Pearl Edu Fund.</p>
                            </div>
                            
                            <div class="payment-details">
                                <h5>Payment Details:</h5>
                                <table class="table">
                                    <tr>
                                        <th>Transaction ID:</th>
                                        <td><?php echo htmlspecialchars($donation['reference']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Amount:</th>
                                        <td>UGX <?php echo number_format($donation['amount'], 2); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                    <tr>
                                        <th>Date:</th>
                                        <td><?php echo date('F d, Y H:i:s', strtotime($donation['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Donor:</th>
                                        <td><?php echo htmlspecialchars($donation['name']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div class="text-center mt-4">
                                <p>You will receive a confirmation SMS from MTN Mobile Money.</p>
                                <a href="index.html" class="btn btn-primary">Return to Home</a>
                                <a href="donate.php" class="btn btn-outline-primary">Make Another Donation</a>
                            </div>
                            
                        <?php elseif ($currentStatus === 'PENDING'): ?>
                            <div class="alert alert-warning text-center">
                                <h4>⏳ Payment Pending</h4>
                                <p>Please check your phone and enter your Mobile Money PIN to complete the payment.</p>
                                
                                <div class="mt-3">
                                    <div class="spinner-border text-warning" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2">Checking payment status...</p>
                                </div>
                            </div>
                            
                            <script>
                            // Auto-refresh every 5 seconds to check status
                            setTimeout(function() {
                                window.location.reload();
                            }, 5000);
                            </script>
                            
                        <?php elseif ($currentStatus === 'FAILED'): ?>
                            <div class="alert alert-danger text-center">
                                <h4>❌ Payment Failed</h4>
                                <p>The payment could not be completed. Please try again.</p>
                            </div>
                            
                            <div class="text-center mt-4">
                                <a href="donate.php" class="btn btn-primary">Try Again</a>
                                <a href="index.html" class="btn btn-outline-primary">Return to Home</a>
                            </div>
                            
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <h4>🔄 Processing Payment</h4>
                                <p>Your payment is being processed. Please wait...</p>
                            </div>
                            
                            <script>
                            setTimeout(function() {
                                window.location.reload();
                            }, 3000);
                            </script>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">If you have any issues with your payment, please contact us at <strong>pefaug@gmail.com</strong> or call <strong>0774607494</strong></p>
                </div>
            </div>
        </div>
    </main>
    
    <footer class="site-footer">
        <!-- Your footer code here -->
    </footer>
</body>
</html>