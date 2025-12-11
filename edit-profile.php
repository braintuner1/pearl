<?php
session_start();

// Require login
if (!isset($_SESSION['id'])) {
    header('Location: auth.php?mode=login&error=' . urlencode('Please sign in to edit your profile'));
    exit;
}

$dbPath = __DIR__ . '/database/donor.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$donorId = $_SESSION['id'];
$stmt = $db->prepare('SELECT * FROM donors WHERE id = ?');
$stmt->execute([$donorId]);
$donor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$donor) {
    session_destroy();
    header('Location: auth.php?mode=login&error=' . urlencode('Account not found'));
    exit;
}

// Read any flash messages
$success = $_SESSION['profile_success'] ?? null;
$errors = $_SESSION['profile_errors'] ?? null;
unset($_SESSION['profile_success'], $_SESSION['profile_errors']);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profile - Pearl Edu Fund</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/templatemo-kind-heart-charity.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>
<body>
<?php // simple header reuse - nav exists in each page; keep consistent with other pages ?>
   
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
                        <a class="nav-link" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">Dashboard</a>
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

<main class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-12">
                <h2 class="mb-4">Edit Profile</h2>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if ($errors): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars(is_array($errors) ? implode(', ', $errors) : $errors); ?></div>
                <?php endif; ?>

                <form action="php/update-profile.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($donor['name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username (cannot change)</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($donor['username']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($donor['email']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($donor['phone']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Organization</label>
                        <input type="text" name="organization" class="form-control" value="<?php echo htmlspecialchars($donor['organization']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Profile Photo</label>
                        <?php if (!empty($donor['profile_photo_path'])): ?>
                            <div class="mb-2"><img src="<?php echo htmlspecialchars($donor['profile_photo_path']); ?>" alt="profile" style="max-width:140px;border-radius:8px;"></div>
                        <?php endif; ?>
                        <input type="file" name="profile_photo" accept="image/*" class="form-control">
                        <small class="text-muted">Server limits still apply.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirm" class="form-control">
                    </div>

                    <button class="btn btn-primary" type="submit">Save Changes</button>
                    <a class="btn btn-outline-secondary ms-2" href="dashboard.php">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</main>

<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
