<?php
session_start();

// --- Configuration and Security Checks ---

// Database connection path (assumed location relative to the handler in php/ folder)
$dbPath = __DIR__ . '/../database/donor.db';
$redirect_url = '../dashboard.php'; // Default redirect location

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect_url);
    exit;
}

// 1. Check if user is logged in
if (!isset($_SESSION['id'])) {
    // If not logged in, redirect to login page
    header('Location: ../auth.php?mode=login');
    exit;
}

// 2. Check if the logged-in user is an administrator
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    $_SESSION['error_message'] = "Unauthorized access. You must be an administrator to perform this action.";
    header('Location: ' . $redirect_url);
    exit;
}

// Database connection
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Use WAL mode for reliable writes in SQLite
    $db->exec('PRAGMA journal_mode = WAL;');
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database connection error: " . $e->getMessage();
    header('Location: ' . $redirect_url);
    exit;
}

// --- Action Handling ---
$action = $_POST['action'] ?? null;

if (!$action) {
    $_SESSION['error_message'] = "No action specified.";
    header('Location: ' . $redirect_url);
    exit;
}

switch ($action) {
    case 'remove_user':
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $current_admin_id = $_SESSION['id'];

        if (!$user_id) {
            $_SESSION['error_message'] = "Invalid user ID.";
            header('Location: ' . $redirect_url . '#users');
            exit;
        }

        // Prevent admin from deleting themselves
        if ($user_id == $current_admin_id) {
            $_SESSION['error_message'] = "You cannot remove your own account.";
            header('Location: ' . $redirect_url . '#users');
            exit;
        }

        try {
            // Fetch donor name/ID for confirmation message
            $name_stmt = $db->prepare("SELECT name, donor_id_code FROM donors WHERE id = ?");
            $name_stmt->execute([$user_id]);
            $donor_info = $name_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$donor_info) {
                $_SESSION['error_message'] = "Donor not found.";
                header('Location: ' . $redirect_url . '#users');
                exit;
            }

            $donor_name = htmlspecialchars($donor_info['name']);
            $donor_id_code = htmlspecialchars($donor_info['donor_id_code']);

            // Start transaction: delete user and their related data
            $db->beginTransaction();

            // Delete the donor record
            $stmt = $db->prepare("DELETE FROM donors WHERE id = ?");
            $stmt->execute([$user_id]);

            $db->commit();

            $_SESSION['success_message'] = "User {$donor_name} (ID: {$donor_id_code}) successfully removed.";
            // Redirect back to the User Management tab
            header('Location: ' . $redirect_url . '#users');
            exit;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Database error during user removal: " . $e->getMessage();
            header('Location: ' . $redirect_url . '#users');
            exit;
        }
        break;

    case 'award_points':
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $points = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_INT);
        $reason = filter_input(INPUT_POST, 'reason', FILTER_SANITIZE_STRING) ?? 'Admin Award';

        if (!$user_id || !$points || $points <= 0) {
            $_SESSION['error_message'] = "Invalid user or point amount provided.";
            header('Location: ' . $redirect_url . '#points');
            exit;
        }
        
        try {
            $db->beginTransaction();

            // 1. Update donor loyalty points safely
            $update_stmt = $db->prepare("
                UPDATE donors 
                SET loyalty_points = loyalty_points + ? 
                WHERE id = ?
            ");
            $update_stmt->execute([$points, $user_id]);
            
            // Check if a row was actually updated (if user_id exists)
            if ($update_stmt->rowCount() === 0) {
                $db->rollBack();
                $_SESSION['error_message'] = "Donor ID #{$user_id} not found.";
                header('Location: ' . $redirect_url . '#points');
                exit;
            }

            // 2. Fetch the updated donor's name for the success message
            $name_stmt = $db->prepare("SELECT name FROM donors WHERE id = ?");
            $name_stmt->execute([$user_id]);
            $donor_name = $name_stmt->fetchColumn();

            // 3. Commit transaction
            $db->commit();

            $_SESSION['success_message'] = "Successfully awarded " . number_format($points) . " points to " . htmlspecialchars($donor_name) . " for: " . htmlspecialchars($reason) . ".";
            header('Location: ' . $redirect_url . '#points');
            exit;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['error_message'] = "Database error during point award: " . $e->getMessage();
            header('Location: ' . $redirect_url . '#points');
            exit;
        }
        break;

    case 'delete_event':
        $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);

        if (!$event_id) {
            $_SESSION['error_message'] = "Invalid event ID for deletion.";
            header('Location: ' . $redirect_url . '#events');
            exit;
        }

        try {
            // Fetch event title for success message
            $name_stmt = $db->prepare("SELECT title FROM events WHERE id = ?");
            $name_stmt->execute([$event_id]);
            $event_title = $name_stmt->fetchColumn();

            if (!$event_title) {
                $_SESSION['error_message'] = "Event not found.";
                header('Location: ' . $redirect_url . '#events');
                exit;
            }

            // Start transaction
            $db->beginTransaction();

            // Delete the event record (assuming events table exists)
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);

            $db->commit();

            $_SESSION['success_message'] = "Event '{$event_title}' (ID: {$event_id}) successfully deleted.";
            // Redirect back to the Events Management tab using the #events hash
            header('Location: ' . $redirect_url . '#events');
            exit;

        } catch (PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if (strpos($e->getMessage(), 'constraint failed') !== false) {
                 $_SESSION['error_message'] = "Cannot delete event '{$event_title}' (ID: {$event_id}). Please remove all related participations first.";
            } else {
                 $_SESSION['error_message'] = "Database error during event deletion: " . $e->getMessage();
            }
            header('Location: ' . $redirect_url . '#events');
            exit;
        }
        break;
        
    // --- NEW: Update Event Action ---
    case 'update_event':
        $event_id = filter_input(INPUT_POST, 'event_id', FILTER_VALIDATE_INT);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
        $event_date = filter_input(INPUT_POST, 'event_date', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        // Using FILTER_UNSAFE_RAW for description to allow for broader text input
        $description = filter_input(INPUT_POST, 'description', FILTER_UNSAFE_RAW);

        if (!$event_id || empty($title) || empty($event_date) || empty($location)) {
            $_SESSION['error_message'] = "Invalid or missing event details for update.";
            header('Location: ' . $redirect_url . '#events');
            exit;
        }
        
        // Basic date format validation (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $event_date)) {
             $_SESSION['error_message'] = "Invalid date format. Please use YYYY-MM-DD.";
            header('Location: ' . $redirect_url . '#events');
            exit;
        }

        try {
            // Update the event record. Add updated_at if you track modification time.
            $stmt = $db->prepare("
                UPDATE events 
                SET title = ?, event_date = ?, location = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $event_date, $location, $description, $event_id]);
            
            if ($stmt->rowCount() > 0) {
                 $_SESSION['success_message'] = "Event '{$title}' successfully updated.";
            } else {
                 $_SESSION['error_message'] = "Event ID {$event_id} not found or no changes were made.";
            }

            // Redirect back to the Events tab
            header('Location: ' . $redirect_url . '#events');
            exit;

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Database error during event update: " . $e->getMessage();
            header('Location: ' . $redirect_url . '#events');
            exit;
        }
        break;

    default:
        $_SESSION['error_message'] = "Unknown action.";
        header('Location: ' . $redirect_url);
        exit;
}
?>