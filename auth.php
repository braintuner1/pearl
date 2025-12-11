<?php
session_start();

$old_signup = $_SESSION['old_signup'] ?? [];
$old_login = $_SESSION['old_login'] ?? [];
$signup_errors = $_SESSION['signup_errors'] ?? null;
$login_error = $_SESSION['login_error'] ?? null;
// FIX: Retrieve the logout success message from the session
$logout_success_message = $_SESSION['logout_success_message'] ?? null;

// Clear old data after reading so it doesn't persist forever
// FIX: Added $logout_success_message to the unset list
unset($_SESSION['old_signup'], $_SESSION['old_login'], $_SESSION['signup_errors'], $_SESSION['login_error'], $_SESSION['logout_success_message']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pearl Edu Fund - Sign In or Sign Up">
    <meta name="author" content="">
    <title>Pearl Edu Fund - Sign In or Sign Up</title>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link href="css/templatemo-kind-heart-charity.css" rel="stylesheet">
    <link href="css/login.css" rel="stylesheet">
</head>

<body id="auth-page">

    <?php if (isset($logout_success_message) && $logout_success_message): ?>
        <div class="container mt-4">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($logout_success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>

   
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
                        <a class="nav-link click-scroll" href="index.html">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link click-scroll" href="index.html">About</a>
                    </li>
                      
                    <li class="nav-item ms-3">
                        <a class="nav-link custom-btn custom-border-btn btn" href="donate.php">Donate</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="login-main">
        <section class="login-section section-padding">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-6 col-md-8 col-12">
                        <div class="login-wrapper auth-container">

                            <div class="auth-form-wrapper" id="login-form-wrapper">
                                <div class="login-header text-center mb-5">
                                    <h2>Member Portal</h2>
                                    <p>Sign in to access your dashboard</p>
                                </div>

                                <form class="login-form custom-form" action="php/login-handler.php" method="POST" role="form">
                                    
                                    <div id="login-error-message" class="alert alert-danger alert-dismissible fade <?php echo $login_error ? 'show' : ''; ?>" role="alert" style="display: <?php echo $login_error ? 'block' : 'none'; ?>;">
                                        <span id="login-error-text"><?php echo htmlspecialchars($login_error ?? ''); ?></span>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>

                                    <div class="form-field-group">
                                        <label for="login-username" class="form-label">
                                            <i class="bi-person me-2"></i>Username or Email
                                        </label>
                                        <input type="text" class="form-control login-input" id="login-username" name="username" 
                                            placeholder="Enter your username or email" required value="<?php echo htmlspecialchars($old_login['username'] ?? ''); ?>">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="login-password" class="form-label">
                                            <i class="bi-lock me-2"></i>Password
                                        </label>
                                        <input type="password" class="form-control login-input" id="login-password" name="password" 
                                            placeholder="Enter your password" required>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">
                                            Remember me
                                        </label>
                                    </div>

                                    <button type="submit" class="btn btn-login w-100 mb-3">
                                        <i class="bi-box-arrow-in-right me-2"></i>Sign In
                                    </button>

                                    <div class="toggle-auth">
                                        <p class="mb-0">Don't have an account?</p>
                                        <button type="button" class="toggle-btn" onclick="toggleAuth('signup')">
                                            Create one now
                                        </button>
                                    </div>

                                    <div class="login-footer text-center mt-4">
                                        <small class="text-muted">Default credentials: admin / admin123</small>
                                    </div>
                                </form>
                            </div>

                            <div class="auth-form-wrapper hidden" id="signup-form-wrapper">
                                <div class="login-header text-center mb-5">
                                    <h2>Create Member Account</h2>
                                    <p>Join Pearl Edu Fund to make donations and track impact</p>
                                </div>

                                <form class="login-form custom-form" action="php/signup-handler.php" method="POST" role="form" enctype="multipart/form-data">
                                    
                                    <div id="signup-error-message" class="alert alert-danger alert-dismissible fade <?php echo $signup_errors ? 'show' : ''; ?>" role="alert" style="display: <?php echo $signup_errors ? 'block' : 'none'; ?>;">
                                        <div id="signup-error-text"><?php echo htmlspecialchars(is_array($signup_errors) ? implode(', ', $signup_errors) : ($signup_errors ?? '')); ?></div>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-name" class="form-label">
                                            <i class="bi-person me-2"></i>Full Name
                                        </label>
                                        <input type="text" class="form-control login-input" id="signup-name" name="name" 
                                            placeholder="Enter your full name" required value="<?php echo htmlspecialchars($old_signup['name'] ?? ''); ?>">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-username" class="form-label">
                                            <i class="bi-at me-2"></i>Username
                                        </label>
                                        <input type="text" class="form-control login-input" id="signup-username" name="username" 
                                            placeholder="Choose a username (min 3 characters)" required minlength="3" value="<?php echo htmlspecialchars($old_signup['username'] ?? ''); ?>">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-email" class="form-label">
                                            <i class="bi-envelope me-2"></i>Email
                                        </label>
                                        <input type="email" class="form-control login-input" id="signup-email" name="email" 
                                            placeholder="Enter your Gmail or email" required value="<?php echo htmlspecialchars($old_signup['email'] ?? ''); ?>">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-phone" class="form-label">
                                            <i class="bi-telephone me-2"></i>Phone Number
                                        </label>
                                        <input type="tel" class="form-control login-input" id="signup-phone" name="phone" 
                                            placeholder="Enter your phone number" required value="<?php echo htmlspecialchars($old_signup['phone'] ?? ''); ?>">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-organization" class="form-label">
                                            <i class="bi-building me-2"></i>Organization (Optional)
                                        </label>
                                        <input type="text" class="form-control login-input" id="signup-organization" name="organization" 
                                            placeholder="Your organization name" value="<?php echo htmlspecialchars($old_signup['organization'] ?? ''); ?>">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-profile-photo" class="form-label">
                                            <i class="bi-image me-2"></i>Profile Photo (Optional)
                                        </label>
                                        <input type="file" class="form-control login-input" id="signup-profile-photo" name="profile_photo" 
                                            accept="image/jpeg,image/png,image/gif">
                                        <small class="form-text text-muted d-block mt-1">Max 5MB. Accepted: JPG, PNG, GIF</small>
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-password" class="form-label">
                                            <i class="bi-lock me-2"></i>Password
                                        </label>
                                        <input type="password" class="form-control login-input" id="signup-password" name="password" 
                                            placeholder="Enter a strong password (min 6 characters)" required minlength="6">
                                    </div>

                                    <div class="form-field-group">
                                        <label for="signup-password-confirm" class="form-label">
                                            <i class="bi-lock-check me-2"></i>Confirm Password
                                        </label>
                                        <input type="password" class="form-control login-input" id="signup-password-confirm" name="password_confirm" 
                                            placeholder="Confirm your password" required minlength="6">
                                    </div>

                                    <button type="submit" class="btn btn-login w-100 mb-3">
                                        <i class="bi-person-plus me-2"></i>Create Account
                                    </button>

                                    <div class="toggle-auth">
                                        <p class="mb-0">Already have an account?</p>
                                        <button type="button" class="toggle-btn" onclick="toggleAuth('login')">
                                            Sign in here
                                        </button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 col-12 mb-4">
                    <h5 class="site-footer-title mb-3">Quick Links</h5>
                    <ul class="footer-menu">
                        <li class="footer-menu-item"><a href="index.html" class="footer-menu-link">Home</a></li>
                        <li class="footer-menu-item"><a href="donate.php" class="footer-menu-link">Donate</a></li>
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
                <div class="row">
                    <div class="col-lg-12 col-12">
                        <p class="copyright-text mb-0">Copyright © 2025 <a href="#">Pearl Edu Fund</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/custom.js"></script>
    <script>
        // Toggle between login and signup forms
        function toggleAuth(mode) {
            const loginWrapper = document.getElementById('login-form-wrapper');
            const signupWrapper = document.getElementById('signup-form-wrapper');

            if (mode === 'login') {
                loginWrapper.classList.remove('hidden');
                signupWrapper.classList.add('hidden');
            } else if (mode === 'signup') {
                signupWrapper.classList.remove('hidden');
                loginWrapper.classList.add('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const mode = urlParams.get('mode') || 'login';

            if (mode === 'signup') toggleAuth('signup');
            else toggleAuth('login');
        });
    </script>

</body>

</html>