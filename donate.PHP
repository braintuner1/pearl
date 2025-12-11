<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="description" content="">
    <meta name="author" content="">

    <title>Pearl Edu Fund - Donate / Support Our Work</title>

    <!-- CSS FILES -->
    <link href="css/bootstrap.min.css" rel="stylesheet">

    <link href="css/bootstrap-icons.css" rel="stylesheet">

    <link href="css/templatemo-kind-heart-charity.css" rel="stylesheet">
    <!--

-->
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

        <section class="donate-section">
            <div class="section-overlay"></div>
            <div class="container">
                <div class="row">

                        <form class="custom-form donate-form" action="request_to_pay.php" method="POST" role="form">
    <h3 class="mb-4">Support Pearl Edu Fund</h3>

    <div class="row">
        <div class="col-lg-12 col-12">
            <h5 class="mb-3">Donation Frequency</h5>
        </div>

        <div class="col-lg-6 col-6 form-check-group form-check-group-donation-frequency">
            <div class="form-check form-check-radio">
                <input class="form-check-input" type="radio" name="DonationFrequency" id="DonationFrequencyOne" checked>
                <label class="form-check-label" for="DonationFrequencyOne">
                    One Time
                </label>
            </div>
        </div>

        <div class="col-lg-6 col-6 form-check-group form-check-group-donation-frequency">
            <div class="form-check form-check-radio">
                <input class="form-check-input" type="radio" name="DonationFrequency" id="DonationFrequencyMonthly">
                <label class="form-check-label" for="DonationFrequencyMonthly">
                    Monthly
                </label>
            </div>
        </div>

        <div class="col-lg-12 col-12">
            <h5 class="mt-2 mb-3">Select an amount</h5>
        </div>

        <!-- Fixed amounts -->
        <div class="col-lg-3 col-md-6 col-6 form-check-group">
            <div class="form-check form-check-radio">
                <input class="form-check-input amount-radio" type="radio" name="amount" value="1000" id="amt1">
                <label class="form-check-label" for="amt1">UGX 1,000</label>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 form-check-group">
            <div class="form-check form-check-radio">
                <input class="form-check-input amount-radio" type="radio" name="amount" value="2000" id="amt2">
                <label class="form-check-label" for="amt2">UGX 2,000</label>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 form-check-group">
            <div class="form-check form-check-radio">
                <input class="form-check-input amount-radio" type="radio" name="amount" value="5000" id="amt3">
                <label class="form-check-label" for="amt3">UGX 5,000</label>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-6 form-check-group">
            <div class="form-check form-check-radio">
                <input class="form-check-input amount-radio" type="radio" name="amount" value="10000" id="amt4">
                <label class="form-check-label" for="amt4">UGX 10,000</label>
            </div>
        </div>

        <!-- Custom Amount -->
        <div class="col-lg-6 col-12 form-check-group mt-3">
            <div class="input-group">
                <span class="input-group-text">UGX</span>
                <input type="number" class="form-control" placeholder="Custom amount" name="custom_amount" id="customAmount">
            </div>
        </div>

        <div class="col-lg-12 col-12 mt-4">
            <h5>Personal Info</h5>
        </div>

        <div class="col-lg-6 col-12 mt-2">
            <input type="text" name="donor_name" class="form-control" placeholder="Your Name" required>
        </div>

        <div class="col-lg-6 col-12 mt-2">
            <input type="email" name="donor_email" class="form-control" placeholder="Your Email" required>
        </div>

        <div class="col-lg-12 col-12 mt-4">
            <h5>Enter Phone Number (MTN)</h5>
        </div>

        <!-- PHONE NUMBER INPUT -->
        <div class="col-lg-12 col-12 mt-2">
            <input type="text" name="phone" class="form-control" placeholder="077xxxxxxx" required>
        </div>

        <div class="col-lg-12 col-12 mt-4">
            <button type="submit" class="form-control custom-btn btn">Donate via MTN Mobile Money</button>
        </div>
    </div>
</form>

<script>
    // When user types custom amount → uncheck radio buttons
    document.getElementById("customAmount").addEventListener("input", function () {
        const radios = document.querySelectorAll(".amount-radio");
        radios.forEach(r => r.checked = false);
    });

    // When user selects radio → clear custom amount
    const radios = document.querySelectorAll(".amount-radio");
    radios.forEach(r => {
        r.addEventListener("change", function () {
            document.getElementById("customAmount").value = "";
        });
    });
</script>

                    </div>

                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-12 mb-4">
                    <img src="images/logo.png" class="logo img-fluid" alt="">
                </div>

                <div class="col-lg-4 col-md-6 col-12 mb-4">
                    <h5 class="site-footer-title mb-3">Quick Links</h5>

                    <ul class="footer-menu">
                        <li class="footer-menu-item"><a href="#" class="footer-menu-link">Our Story</a></li>

                        <li class="footer-menu-item"><a href="#" class="footer-menu-link">Newsroom</a></li>

                        <li class="footer-menu-item"><a href="#" class="footer-menu-link">Causes</a></li>

                        <li class="footer-menu-item"><a href="#" class="footer-menu-link">Become a volunteer</a></li>

                        <li class="footer-menu-item"><a href="#" class="footer-menu-link">Partner with us</a></li>
                    </ul>
                </div>

                <div class="col-lg-4 col-md-6 col-12 mx-auto">
                    <h5 class="site-footer-title mb-3">Contact Infomation</h5>

                    <p class="text-white d-flex mb-2">
                        <i class="bi-telephone me-2"></i>

                        <a href="tel:0774607494" class="site-footer-link">
                            0774607494
                        </a>
                    </p>

                    <p class="text-white d-flex">
                        <i class="bi-envelope me-2"></i>

                        <a href="mailto:pefaug@gmail.com" class="site-footer-link">
                            pefaug@gmail.com
                        </a>
                    </p>

                    <p class="text-white d-flex mt-3">
                        <i class="bi-geo-alt me-2"></i>
                        St Mary's College Kisubi, P.O. Box 26, Kisubi, Kampala, Uganda
                    </p>

                    <a href="#" class="custom-btn btn mt-3"></a>
                </div>
            </div>
        </div>

        <div class="site-footer-bottom">
            <div class="container">
                <div class="row">

                    <div class="col-lg-6 col-md-7 col-12">
                        <p class="copyright-text mb-0">Copyright © 2025 <a href="#">Pearl Edu Fund</a>.
                            Design: <a href="https://templatemo.com" target="_blank">TemplateMo</a><br>Distribution:
                            <a href="https://themewagon.com">ThemeWagon</a>
                        </p>
                    </div>

                    <div class="col-lg-6 col-md-5 col-12 d-flex justify-content-center align-items-center mx-auto">
                        <ul class="social-icon">
                            <li class="social-icon-item">
                                <a href="#" class="social-icon-link bi-twitter"></a>
                            </li>

                            <li class="social-icon-item">
                                <a href="#" class="social-icon-link bi-facebook"></a>
                            </li>

                            <li class="social-icon-item">
                                <a href="#" class="social-icon-link bi-instagram"></a>
                            </li>

                            <li class="social-icon-item">
                                <a href="#" class="social-icon-link bi-linkedin"></a>
                            </li>

                            <li class="social-icon-item">
                                <a href="https://youtube.com/templatemo" class="social-icon-link bi-youtube"></a>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
    </footer>

    <!-- JAVASCRIPT FILES -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/jquery.sticky.js"></script>
    <script src="js/counter.js"></script>
    <script src="js/custom.js"></script>

</body>

</html>