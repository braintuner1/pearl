<?php
session_start();

// --- MESSAGE DISPLAY LOGIC (NEW) ---
$display_message = '';
// Check for and store success/error messages from handlers (like admin-handlers.php)
if (isset($_SESSION['success_message'])) {
    $display_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi-check-circle-fill me-2"></i>
        ' . htmlspecialchars($_SESSION['success_message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['success_message']);
} elseif (isset($_SESSION['error_message'])) {
    $display_message = '<div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi-exclamation-triangle-fill me-2"></i>
        ' . htmlspecialchars($_SESSION['error_message']) . '
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>';
    unset($_SESSION['error_message']);
}


// Check if member is logged in
if (!isset($_SESSION['id'])) {
    header('Location: auth.php?mode=login');
    exit;
}

// Database connection
$db = new PDO('sqlite:' . __DIR__ . '/database/donor.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$donor_id = $_SESSION['id'];
$is_admin = $_SESSION['is_admin'] ?? false; // Get Admin status from session

try {
    // 1. Fetch donor information (Crucial: Includes is_admin)
    $stmt = $db->prepare('SELECT * FROM donors WHERE id = ?');
    $stmt->execute([$donor_id]);
    $donor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$donor) {
        // If donor record is missing after login, destroy session
        header('Location: php/logout.php');
        exit;
    }
    
    // Fetch common donor details
    $wallet_balance = $donor['wallet_balance'] ?? 0;
    $loyalty_points = $donor['loyalty_points'] ?? 0;
    $donor_id_code = $donor['donor_id_code'];
    
    // 2. Data Fetching for ALL USERS (Regular Donor Data)
    // Fetch all projects
    $projects = $db->query('SELECT * FROM projects ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
    
    // Fetch recent donations for THIS donor
    // FIX: Corrected query to use WHERE d.donor_id = ?
    $stmt = $db->prepare('
        SELECT d.*, p.title as project_title 
        FROM donations d
        LEFT JOIN projects p ON d.project_id = p.id
        WHERE d.donor_id = ? 
        ORDER BY d.donation_date DESC
        LIMIT 5
    ');
    $stmt->execute([$donor_id]);
    $recent_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total donations for THIS donor
    $stmt = $db->prepare('SELECT SUM(amount) as total FROM donations WHERE donor_id = ?');
    $stmt->execute([$donor_id]);
    $total_donations = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // 3. Data Fetching for ADMIN ONLY
    $all_donations = [];
    $total_collected = 0;
    $all_donors = [];
    $all_events = []; // Initialize for non-admin users
    
    if ($is_admin) {
        // Fetch ALL donations/transactions
        $stmt = $db->query('
            SELECT 
                d.*, 
                p.title as project_title, 
                o.name as donor_name,
                o.donor_id_code
            FROM donations d
            LEFT JOIN projects p ON d.project_id = p.id
            LEFT JOIN donors o ON d.donor_id = o.id
            ORDER BY d.donation_date DESC
        ');
        $all_donations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch total collected amount
        $total_collected_stmt = $db->query('SELECT SUM(amount) as total FROM donations');
        $total_collected = $total_collected_stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        // Fetch all donors (for user management/points)
        $all_donors = $db->query('SELECT id, name, username, email, phone, loyalty_points, donor_id_code, is_admin FROM donors ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch all projects for event management
        $all_projects_for_events = $db->query('SELECT id, title FROM projects ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC);
        
        // NEW: Fetch all events for management
        $all_events = $db->query('SELECT * FROM events ORDER BY event_date DESC')->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pearl Edu Fund - Donor Dashboard">
    <title>Pearl Edu Fund - <?php echo $is_admin ? 'Admin Panel' : 'Donor Dashboard'; ?></title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/templatemo-kind-heart-charity.css" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
    <style>
        .admin-stat-card {
            background: #fff;
            padding: 20px;
            border-left: 5px solid var(--custom-color);
            border-radius: 6px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .admin-stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--custom-color);
        }
        .nav-link.admin-tab.active {
            color: #fff !important;
            background-color: var(--custom-color);
        }
        .nav-link.admin-tab {
            color: var(--custom-color);
        }
        .table-responsive {
            max-height: 400px; /* Limit height for long tables */
            overflow-y: auto;
        }
        .stat-card.admin-total {
            background-color: #e5f6e5;
            border: 1px solid #c2e6c2;
        }
    </style>
</head>

<body>

    

    <nav class="navbar navbar-expand-lg bg-light shadow-lg">
        <div class="container">
            <a class="navbar-brand" href="index.html">
                <img src="docs/extracted/IMG_5792_images/image_1_1.jpeg" class="logo img-fluid" alt="Pearl Edu Fund">
                <span>
                    Pearl Edu Fund
                    <small>Education for Non-Teaching Staff Children</small>
                </span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="edit-profile.php">Edit Profile</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi-person-circle me-1"></i><?php echo htmlspecialchars($donor['name']); ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="profileDropdown">
                            <li><a class="dropdown-item" href="php/logout.php"><i class="bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="dashboard-main">
        <div class="dashboard-container">

            <?php if ($display_message): ?>
                <div class="container mt-4">
                    <?php echo $display_message; ?>
                </div>
            <?php endif; ?>

            <section class="dashboard-header section-padding section-bg">
                <div class="container">
                    <div class="row align-items-stretch g-4">
                        <div class="col-lg-6 col-12 d-flex flex-column justify-content-center">
                            <div class="donor-info-section">
                                <h1 class="mb-3">Welcome, <?php echo htmlspecialchars($donor['name']); ?>
                                    <?php if ($is_admin): ?>
                                        <span class="badge bg-danger ms-2">ADMIN</span>
                                    <?php endif; ?>
                                </h1>
                                <p class="mb-2">
                                    <i class="bi-person-badge me-2"></i>
                                    <strong>ID:</strong> <?php echo htmlspecialchars($donor_id_code); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="bi-envelope me-2"></i>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($donor['email']); ?>
                                </p>
                                <p class="mb-2">
                                    <i class="bi-telephone me-2"></i>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($donor['phone'] ?? 'N/A'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-lg-6 col-12 d-flex flex-column align-items-center justify-content-center">
                            <div class="profile-picture-container">
                                <?php if ($donor['profile_photo_path']): ?>
                                    <img src="<?php echo htmlspecialchars($donor['profile_photo_path']); ?>" 
                                        alt="<?php echo htmlspecialchars($donor['name']); ?>" class="profile-picture">
                                <?php else: ?>
                                    <div class="profile-picture-placeholder">
                                        <i class="bi-person-circle"></i>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="organization-info mt-4 text-center">
                                <p class="organization-label">Organization</p>
                                <h3 class="organization-name"><?php echo htmlspecialchars($donor['organization']); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            
            <?php if ($is_admin): ?>
            
            <section class="admin-panel section-padding">
                <div class="container">
                    <h2 class="section-title mb-5">Admin Panel</h2>

                    <div class="row mb-5 g-4">
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="stat-card admin-total">
                                <div class="stat-icon icon-donation">
                                    <i class="bi-currency-exchange"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Total Collected Amount</h5>
                                    <h3 class="stat-value">UGX <?php echo number_format($total_collected, 0); ?></h3>
                                    <p class="stat-label">All-time revenue from donations</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="stat-card">
                                <div class="stat-icon icon-count">
                                    <i class="bi-people"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Total Donors</h5>
                                    <h3 class="stat-value"><?php echo number_format(count($all_donors)); ?></h3>
                                    <p class="stat-label">Registered members</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="stat-card">
                                <div class="stat-icon icon-points">
                                    <i class="bi-journal-check"></i>
                                </div>
                                <div class="stat-content">
                                    <h5>Total Transactions</h5>
                                    <h3 class="stat-value"><?php echo number_format(count($all_donations)); ?></h3>
                                    <p class="stat-label">Donations recorded</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link admin-tab active" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="true">
                                <i class="bi-list-ul me-2"></i>All Transactions
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link admin-tab" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">
                                <i class="bi-person-gear me-2"></i>User Management
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link admin-tab" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="false">
                                <i class="bi-calendar-event me-2"></i>Event Management
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link admin-tab" id="points-tab" data-bs-toggle="tab" data-bs-target="#points" type="button" role="tab" aria-controls="points" aria-selected="false">
                                <i class="bi-award me-2"></i>Award Points
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content pt-4" id="adminTabsContent">
                        
                        <div class="tab-pane fade show active" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                            <h4 class="mb-4">Complete Donation History</h4>
                            <?php if (count($all_donations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Donor Name</th>
                                            <th>Donor ID</th>
                                            <th>Project</th>
                                            <th>Amount (UGX)</th>
                                            <th>Date</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_donations as $donation): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($donation['id']); ?></td>
                                            <td><?php echo htmlspecialchars($donation['donor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($donation['donor_id_code']); ?></td>
                                            <td><?php echo htmlspecialchars($donation['project_title'] ?? 'N/A'); ?></td>
                                            <td><?php echo number_format($donation['amount'], 0); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($donation['donation_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($donation['points_earned']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="alert alert-info">No donations have been recorded yet.</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                            <h4 class="mb-4">Manage Members</h4>
                            
                            <div class="p-4 mb-4 border rounded bg-light">
                                <h5><i class="bi-person-plus me-2"></i>Add New Donor (External Link)</h5>
                                <p>To add a new user, please direct them to the sign-up page or use the dedicated admin tool.</p>
                                <a href="auth.php?mode=signup" target="_blank" class="btn custom-btn btn-sm">Go to Sign Up Page</a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Points</th>
                                            <th>Role</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_donors as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['donor_id_code']); ?></td>
                                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo number_format($user['loyalty_points']); ?></td>
                                            <td><?php echo $user['is_admin'] == 1 ? 'Admin' : 'Donor'; ?></td>
                                            <td>
                                                <?php if ($user['id'] != $donor_id): // Prevent self-deletion ?>
                                                <form action="php/admin-handlers.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove <?php echo addslashes($user['name']); ?>? This action is irreversible.');">
                                                    <input type="hidden" name="action" value="remove_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi-trash"></i> Remove</button>
                                                </form>
                                                <?php else: ?>
                                                <span class="text-muted">Current User</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="events" role="tabpanel" aria-labelledby="events-tab">
                            
                            <h4 class="mb-4">Add New Event</h4>
                            <div class="p-4 border rounded bg-light mb-5">
                                <form action="php/event-handler.php" method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="add_event">
                                    
                                    <div class="col-md-6">
                                        <label for="event_title" class="form-label">Event Title *</label>
                                        <input type="text" class="form-control" id="event_title" name="title" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="event_location" class="form-label">Location *</label>
                                        <input type="text" class="form-control" id="event_location" name="location" required>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="event_date" class="form-label">Date *</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="event_time" class="form-label">Time (Optional)</label>
                                        <input type="time" class="form-control" id="event_time" name="event_time">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="project_id" class="form-label">Related Project (Optional)</label>
                                        <select class="form-select" id="project_id" name="project_id">
                                            <option value="">None</option>
                                            <?php foreach ($all_projects_for_events as $project): ?>
                                                <option value="<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="short_description" class="form-label">Short Description (Max 150 chars)</label>
                                        <input type="text" class="form-control" id="short_description" name="short_description" maxlength="150">
                                    </div>
                                    <div class="col-12">
                                        <label for="full_description" class="form-label">Full Description</label>
                                        <textarea class="form-control" id="full_description" name="full_description" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn custom-btn mt-3"><i class="bi-plus-circle me-2"></i>Create Event</button>
                                    </div>
                                </form>
                            </div>
                            
                            <h4 class="mb-4">Manage Existing Events</h4>
                            <?php if (count($all_events) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Title</th>
                                            <th>Date</th>
                                            <th>Location</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_events as $event): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['id']); ?></td>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo htmlspecialchars($event['event_date']); ?></td>
                                            <td><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td>
                                                <button type="button" class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" 
                                                    data-bs-target="#editEventModal<?php echo $event['id']; ?>"><i class="bi-pencil"></i> Edit</button>

                                                <form action="php/admin-handlers.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete event: <?php echo addslashes($event['title']); ?>?');">
                                                    <input type="hidden" name="action" value="delete_event">
                                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm"><i class="bi-trash"></i> Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                                <p class="alert alert-info">No events have been created yet.</p>
                            <?php endif; ?>
                            
                            <?php foreach ($all_events as $event): ?>
                            <div class="modal fade" id="editEventModal<?php echo $event['id']; ?>" tabindex="-1" aria-labelledby="editEventModalLabel<?php echo $event['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editEventModalLabel<?php echo $event['id']; ?>">Edit Event: <?php echo htmlspecialchars($event['title']); ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="php/admin-handlers.php" method="POST">
                                            <input type="hidden" name="action" value="update_event">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label for="edit_title_<?php echo $event['id']; ?>" class="form-label">Title *</label>
                                                    <input type="text" class="form-control" id="edit_title_<?php echo $event['id']; ?>" name="title" value="<?php echo htmlspecialchars($event['title']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_location_<?php echo $event['id']; ?>" class="form-label">Location *</label>
                                                    <input type="text" class="form-control" id="edit_location_<?php echo $event['id']; ?>" name="location" value="<?php echo htmlspecialchars($event['location']); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_date_<?php echo $event['id']; ?>" class="form-label">Date (YYYY-MM-DD) *</label>
                                                    <input type="date" class="form-control" id="edit_date_<?php echo $event['id']; ?>" name="event_date" value="<?php echo htmlspecialchars(date('Y-m-d', strtotime($event['event_date']))); ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="edit_description_<?php echo $event['id']; ?>" class="form-label">Description</label>
                                                    <textarea class="form-control" id="edit_description_<?php echo $event['id']; ?>" name="description" rows="3"><?php echo htmlspecialchars($event['description']); ?></textarea>
                                                </div>
                                                </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>

                        <div class="tab-pane fade" id="points" role="tabpanel" aria-labelledby="points-tab">
                            <h4 class="mb-4">Award Loyalty Points to a Member</h4>
                            <div class="p-4 border rounded bg-light">
                                <form action="php/admin-handlers.php" method="POST" class="row g-3">
                                    <input type="hidden" name="action" value="award_points">
                                    
                                    <div class="col-md-6">
                                        <label for="user_to_award" class="form-label">Select Donor *</label>
                                        <select class="form-select" id="user_to_award" name="user_id" required>
                                            <option value="" selected disabled>Select Donor</option>
                                            <?php foreach ($all_donors as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['name']); ?> (ID: <?php echo htmlspecialchars($user['donor_id_code']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="points_amount" class="form-label">Points to Award *</label>
                                        <input type="number" class="form-control" id="points_amount" name="points" min="1" required>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label for="reason" class="form-label">Reason (Optional)</label>
                                        <input type="text" class="form-control" id="reason" name="reason" placeholder="e.g., Volunteer work, Special recognition">
                                    </div>
                                    
                                    <div class="col-12">
                                        <button type="submit" class="btn custom-btn mt-3"><i class="bi-award me-2"></i>Award Points</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </section>

            <?php endif; // End of Admin Panel ?>


            <section class="dashboard-stats section-padding <?php echo $is_admin ? 'pt-0' : ''; ?>">
                <div class="container">
                    <h2 class="section-title mb-5"><?php echo $is_admin ? 'Your Donor Stats' : 'Your Stats'; ?></h2>
                    <div class="row g-4">
                        
                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="stat-card">
                                <div class="stat-icon"><i class="bi-wallet2"></i></div>
                                <div class="stat-content">
                                    <h5>Wallet Balance</h5>
                                    <h3 class="stat-value">UGX <?php echo number_format($wallet_balance, 0); ?></h3>
                                    <p class="stat-label">Available funds</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="stat-card">
                                <div class="stat-icon icon-points"><i class="bi-star"></i></div>
                                <div class="stat-content">
                                    <h5>Loyalty Points</h5>
                                    <h3 class="stat-value"><?php echo number_format($loyalty_points); ?></h3>
                                    <p class="stat-label">Earned from donations</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="stat-card">
                                <div class="stat-icon icon-donation"><i class="bi-heart"></i></div>
                                <div class="stat-content">
                                    <h5>Total Donated</h5>
                                    <h3 class="stat-value">UGX <?php echo number_format($total_donations, 0); ?></h3>
                                    <p class="stat-label">All time contributions</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-6 col-12">
                            <div class="stat-card">
                                <div class="stat-icon icon-count"><i class="bi-check-circle"></i></div>
                                <div class="stat-content">
                                    <h5>Transactions Count</h5>
                                    <h3 class="stat-value"><?php echo count($recent_donations); ?></h3>
                                    <p class="stat-label">Recent transactions</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

            <section class="projects-section section-padding pt-0">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12 col-12">
                            <h2 class="section-title mb-5">Support Our Projects</h2>
                        </div>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($projects as $project): ?>
                            <div class="col-lg-4 col-md-6 col-12">
                                <div class="project-card">
                                    <div class="project-header">
                                        <h5><?php echo htmlspecialchars($project['title']); ?></h5>
                                        <span class="project-category"><?php echo ucfirst($project['category']); ?></span>
                                    </div>

                                    <p class="project-description">
                                        <?php echo htmlspecialchars(substr($project['description'], 0, 100) . '...'); ?>
                                    </p>

                                    <div class="progress project-progress">
                                        <div class="progress-bar" role="progressbar" 
                                            style="width: <?php echo $project['progress_percentage']; ?>%">
                                        </div>
                                    </div>

                                    <div class="project-stats">
                                        <div class="stat-item">
                                            <p class="stat-label">Raised</p>
                                            <p class="stat-amount">UGX <?php echo number_format($project['raised_amount'], 0); ?></p>
                                        </div>
                                        <div class="stat-item">
                                            <p class="stat-label">Goal</p>
                                            <p class="stat-amount">UGX <?php echo number_format($project['target_amount'], 0); ?></p>
                                        </div>
                                        <div class="stat-item">
                                            <p class="stat-label">Progress</p>
                                            <p class="stat-amount"><?php echo round($project['progress_percentage'], 1); ?>%</p>
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-donate w-100" data-bs-toggle="modal" 
                                        data-bs-target="#donationModal<?php echo $project['id']; ?>"
                                        data-project-id="<?php echo $project['id']; ?>"
                                        data-project-title="<?php echo htmlspecialchars($project['title']); ?>">
                                        <i class="bi-heart me-2"></i>Donate Now
                                    </button>
                                </div>
                            </div>

                            <div class="modal fade" id="donationModal<?php echo $project['id']; ?>" tabindex="-1" aria-labelledby="donationModalLabel<?php echo $project['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="donationModalLabel<?php echo $project['id']; ?>">
                                                Donate to <?php echo htmlspecialchars($project['title']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <form action="php/process-donation.php" method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                
                                                <div class="mb-4">
                                                    <label for="amount<?php echo $project['id']; ?>" class="form-label">Donation Amount (UGX)</label>
                                                    <input type="number" class="form-control" id="amount<?php echo $project['id']; ?>" 
                                                        name="amount" placeholder="Enter amount" min="1000" step="1000" required>
                                                </div>

                                                <div class="alert alert-info">
                                                    <strong>💡 Tip:</strong> Earn 1 loyalty point for every UGX 1,000 donated!
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-donate">Confirm Donation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="recent-section section-padding section-bg pt-0">
                <div class="container">
                    <div class="row">
                        <div class="col-lg-12 col-12">
                            <h2 class="section-title mb-4">Your Recent Donations</h2>
                        </div>
                    </div>

                    <?php if (count($recent_donations) > 0): ?>
                        <div class="row">
                            <div class="col-lg-12 col-12">
                                <div class="donation-list">
                                    <?php foreach ($recent_donations as $donation): ?>
                                        <div class="donation-item">
                                            <div class="donation-icon">
                                                <i class="bi-heart-fill"></i>
                                            </div>
                                            <div class="donation-details">
                                                <h6><?php echo htmlspecialchars($donation['project_title'] ?? 'Pearl Edu Fund'); ?></h6>
                                                <p class="donation-date">
                                                    <?php echo date('M d, Y - h:i A', strtotime($donation['donation_date'])); ?>
                                                </p>
                                            </div>
                                            <div class="donation-amount">
                                                <h6>UGX <?php echo number_format($donation['amount'], 0); ?></h6>
                                                <p class="points-earned">+<?php echo $donation['points_earned']; ?> pts</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-lg-12 col-12 text-center">
                                <p class="no-donations">No donations yet. Start supporting our causes today!</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-12 mb-4">
                    <h5 class="site-footer-title mb-3">Quick Links</h5>
                    <ul class="footer-menu">
                        <li class="footer-menu-item"><a href="index.html" class="footer-menu-link">Home</a></li>
                        <li class="footer-menu-item"><a href="donate.php" class="footer-menu-link">Donate</a></li>
                        <li class="footer-menu-item"><a href="php/logout.php" class="footer-menu-link">Logout</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6 col-12 mx-auto">
                    <h5 class="site-footer-title mb-3">Contact Information</h5>
                    <p class="text-white d-flex mb-2">
                        <i class="bi-telephone me-2"></i>
                        <a href="tel:0774607494" class="site-footer-link">0774607494</a>
                    </p>
                    <p class="text-white d-flex">
                        <i class="bi-envelope me-2"></i>
                        <a href="mailto:pefaug@gmail.com" class="site-footer-link">pefaug@gmail.com</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="site-footer-bottom">
            <div class="container">
                <p class="copyright-text mb-0">Copyright © 2025 <a href="#">Pearl Edu Fund</a>.</p>
            </div>
        </div>
    </footer>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>

</body>

</html>