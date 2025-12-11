<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pearl Edu Fund Events - Join our upcoming events and activities">
    <meta name="author" content="">

    <title>Events - Pearl Edu Fund (PEF)</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/templatemo-kind-heart-charity.css" rel="stylesheet">
    
    <style>
        .event-status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 1;
        }
        .event-card {
            position: relative;
            transition: transform 0.3s ease;
        }
        .event-card:hover {
            transform: translateY(-5px);
        }
        .registration-closed {
            opacity: 0.7;
        }
    </style>
</head>

<body id="section_1">

    <?php
    // SQLite database configuration
    $db_path = "database/donor.db";
    $pdo = null; // Initialize $pdo to null
    $database_error = null;
    
    try {
        // Check if the file exists relative to the current script's directory
        if (!file_exists($db_path)) {
            throw new Exception("Database file not found at path: " . $db_path);
        }
        
        $pdo = new PDO("sqlite:" . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // --- CORRECTED QUERIES: Using only standard columns and date for logic ---
        
        // Get upcoming events
        // Logic simplified: assumes all events with future event_date are upcoming.
        $upcoming_events_sql = "
            SELECT e.*, p.title AS project_title 
            FROM events e
            LEFT JOIN projects p ON e.project_id = p.id
            WHERE e.event_date >= date('now') 
            ORDER BY e.event_date ASC";
        $upcoming_events_stmt = $pdo->prepare($upcoming_events_sql);
        $upcoming_events_stmt->execute();
        $upcoming_events = $upcoming_events_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get past events
        // Logic simplified: assumes all events with past event_date are completed.
        $past_events_sql = "
            SELECT e.*, p.title AS project_title 
            FROM events e
            LEFT JOIN projects p ON e.project_id = p.id
            WHERE e.event_date < date('now') 
            ORDER BY e.event_date DESC LIMIT 6";
        $past_events_stmt = $pdo->prepare($past_events_sql);
        $past_events_stmt->execute();
        $past_events = $past_events_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Participation counts default to 0 since 'current_participants' is missing
        $participation_counts = [];
        foreach ($upcoming_events as $event) {
            $participation_counts[$event['id']] = 0; 
        }
        foreach ($past_events as $event) {
            $participation_counts[$event['id']] = 0;
        }
        
    } catch(Exception $e) {
        $upcoming_events = [];
        $past_events = [];
        $participation_counts = [];
        $database_error = "Database Error: " . $e->getMessage();
        error_log($database_error);
    }


    // Handle form submission (only if connection is successful)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participate']) && $pdo) {
        $event_id = $_POST['event_id'];
        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $message = $_POST['message'];
        
        try {
            // First, ensure event_participants table exists
            $create_table_sql = "CREATE TABLE IF NOT EXISTS event_participants (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                event_id INTEGER NOT NULL,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT,
                message TEXT,
                status TEXT DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events (id)
            )";
            $pdo->exec($create_table_sql);
            
            // Start transaction for safe data modification
            $pdo->beginTransaction();

            $insert_sql = "INSERT INTO event_participants (event_id, full_name, email, phone, message) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([$event_id, $full_name, $email, $phone, $message]);
            
            // NOTE: Code to update 'current_participants' is commented out because the column is missing
            // $update_sql = "UPDATE events SET current_participants = current_participants + 1 WHERE id = ?";
            // $update_stmt = $pdo->prepare($update_sql);
            // $update_stmt->execute([$event_id]);
            
            // Commit transaction
            $pdo->commit();

            $success_message = "Thank you for your interest! We have received your participation request for the event.";
            
        } catch(PDOException $e) {
            if ($pdo && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error_message = "Sorry, there was an error processing your request. Please try again.";
            error_log("Database error during participation: " . $e->getMessage());
        }
    }

    // --- Helper Functions (Adjusted to avoid missing columns) ---
    function getEventStatusBadge($event) {
        $event_date = $event['event_date'];
        $today = date('Y-m-d');
        
        if ($event_date < $today) {
            return '<span class="badge bg-secondary event-status-badge">Completed</span>';
        } else {
            return '<span class="badge bg-primary event-status-badge">Upcoming</span>';
        }
    }

    function isRegistrationOpen($event) {
        $event_date = $event['event_date'];
        $today = date('Y-m-d');
        // Assume open unless event is in the past
        return $event_date >= $today;
    }

    function getAvailableSpots($event, $participation_counts) {
        // Cannot check max_participants, so we default to 'Open'
        $current = isset($participation_counts[$event['id']]) ? $participation_counts[$event['id']] : 0;
        
        if ($current > 0) {
            return 'Registered: ' . $current;
        }
        return 'Open';
    }
    ?>

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
                  <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#section_2">About</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="index.html#section_6">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="partners.html">Partners</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a class="nav-link custom-btn custom-border-btn btn" href="auth.php">Sign In / Sign Up</a>
                    </li>
                    <li class="nav-item ms-3">
                        <a class="nav-link custom-btn custom-border-btn btn" href="donate.php">Donate</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main>
        <section class="section-padding">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 col-12 text-center mx-auto mb-5">
                        <h2 class="mb-2">Our Events</h2>
                        <p>Join our upcoming activities and make an impact in children's education</p>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($database_error): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        Error loading data! <?php echo htmlspecialchars($database_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php foreach ($upcoming_events as $event): ?>
                        <?php 
                        $is_registration_open = isRegistrationOpen($event);
                        $available_spots = getAvailableSpots($event, $participation_counts);
                        $is_full = false; // Assumed false since max_participants is missing
                        ?>
                        <div class="col-lg-6 col-12 mb-4">
                            <div class="custom-block-wrap event-card <?php echo (!$is_registration_open) ? 'registration-closed' : ''; ?>">
                                <?php echo getEventStatusBadge($event); ?>
                               
                                
                                <div class="custom-block">
                                    <div class="custom-block-body">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h5 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h5>
                                        </div>
                                        
                                        <?php if (!empty($event['project_title'])): ?>
                                            <small class="text-secondary mb-3 d-block">Related to: **<?php echo htmlspecialchars($event['project_title']); ?>**</small>
                                        <?php endif; ?>

                                        <div class="d-flex mb-3">
                                            <div class="me-4">
                                                <i class="bi-calendar-event me-2"></i>
                                                <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                                            </div>
                                        </div>
                                        <p class="mb-3"><?php echo htmlspecialchars($event['description']); ?></p> 
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi-geo-alt me-2"></i>
                                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                                            </div>
                                            <div class="text-end">
                                                <small class="text-muted d-block"><?php echo $available_spots; ?></small>
                                                <?php if ($is_registration_open): ?>
                                                    <button class="btn custom-btn participate-btn" 
                                                            data-event-id="<?php echo $event['id']; ?>"
                                                            data-event-title="<?php echo htmlspecialchars($event['title']); ?>">
                                                        Participate
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary" disabled>Completed</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($upcoming_events)): ?>
                        <div class="col-12 text-center">
                            <div class="alert alert-info">
                                <h4>No Upcoming Events</h4>
                                <p>Check back later for new events or <a href="index.html#section_6">contact us</a> to learn about other ways to get involved.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="section-padding section-bg">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 col-12 text-center mx-auto mb-5">
                        <h2 class="mb-2">Past Events</h2>
                        <p>See the impact we've made together through our previous activities</p>
                    </div>
                </div>

                <div class="row">
                    <?php foreach ($past_events as $event): ?>
                        <div class="col-lg-4 col-md-6 col-12 mb-4">
                            <div class="custom-block-wrap">
                                <div class="custom-block">
                                    <div class="custom-block-body">
                                        <h5 class="mb-3"><?php echo htmlspecialchars($event['title']); ?></h5>
                                        
                                        <?php if (!empty($event['project_title'])): ?>
                                            <small class="text-secondary mb-3 d-block">Related to: **<?php echo htmlspecialchars($event['project_title']); ?>**</small>
                                        <?php endif; ?>

                                        <div class="d-flex mb-3">
                                            <div class="me-4">
                                                <i class="bi-calendar-event me-2"></i>
                                                <span><?php echo date('F j, Y', strtotime($event['event_date'])); ?></span>
                                            </div>
                                        </div>
                                        <p class="mb-3"><?php echo htmlspecialchars($event['description']); ?></p>
                                        <div class="progress mt-4">
                                            <div class="progress-bar bg-success" role="progressbar" style="width:100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="d-flex align-items-center my-2">
                                            <p class="mb-0">
                                                <strong>Participants:</strong> 
                                                <?php echo isset($participation_counts[$event['id']]) ? $participation_counts[$event['id']] : '0'; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (empty($past_events)): ?>
                        <div class="col-12 text-center">
                            <p class="text-muted">No past events to display.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="modal fade" id="participationModal" tabindex="-1" aria-labelledby="participationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="participationModalLabel">Participate in Event</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <input type="hidden" name="event_id" id="modalEventId">
                            <div class="mb-3">
                                <label for="eventName" class="form-label">Event</label>
                                <input type="text" class="form-control" id="modalEventTitle" readonly>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message (Optional)</label>
                                <textarea class="form-control" id="message" name="message" rows="3" placeholder="Tell us why you're interested in participating..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input type="hidden" name="participate" value="1">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn custom-btn">Submit Participation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        </footer>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/click-scroll.js"></script>
    <script src="js/counter.js"></script>
    <script src="js/custom.js"></script>

    <script>
        // Participation functionality
        document.addEventListener('DOMContentLoaded', function() {
            const participationModal = new bootstrap.Modal(document.getElementById('participationModal'));
            const participateButtons = document.querySelectorAll('.participate-btn');
            const eventIdInput = document.getElementById('modalEventId');
            const eventTitleInput = document.getElementById('modalEventTitle');
            
            // Set up participation buttons
            participateButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const eventId = this.getAttribute('data-event-id');
                    const eventTitle = this.getAttribute('data-event-title');
                    eventIdInput.value = eventId;
                    eventTitleInput.value = eventTitle;
                    participationModal.show();
                });
            });
        });
    </script>

</body>
</html>