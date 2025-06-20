<?php

session_start();
include 'conn.php';
// --- PHP for fetching car data for Rentals Tab and Car Details Modal ---
$cars_data = [];
$sql_cars = "SELECT id, make, model, year, color, engine, transmission, fuel_economy, seating_capacity, safety_features, additional_features, price, image_path FROM cars";
$result_cars = mysqli_query($conn, $sql_cars);
if ($result_cars) {
    while ($row = mysqli_fetch_assoc($result_cars)) {
        $cars_data[] = $row;
    }
} else {
    set_message('Error fetching car data: ' . mysqli_error($conn), 'error');
    error_log('[KRM ERROR] Error fetching car data: ' . mysqli_error($conn)); // Debugging log

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRM Rent a Car Services</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
        }

        .hero {
            background: url('assets/hero.jpg') no-repeat center center/cover;
            color: white;
            padding: 6rem 2rem;
            text-align: center;
        }

        .feature-icon {
            font-size: 2.5rem;
        }

        .nav-link.active {
            font-weight: bold;
        }

        footer {
            background-color: #f8f9fa;
            padding: 2rem;
            text-align: center;
        }

        /* CAROUSEL */
        .carousel-item img {
            object-fit: cover;
            width: 100%;
            height: 100%;
        }

        .carousel-inner,
        .carousel-item {
            height: 100%;
        }

        .bg-dark.opacity-50 {
            opacity: 0.5;
            background-color: #000 !important;
        }
    </style>
</head>

<body>
    <div>
        <header class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">KRM Rent a Car Services</h1>
            <div>
                <a href="login.php" class="btn btn-outline-light btn-sm">Login</a>
                <a href="signup.php" class="btn btn-primary btn-sm">Sign Up</a>
            </div>
        </header>

        <nav class="navbar navbar-expand-lg navbar-light bg-light sticky-top">
            <div class="container">
                <div class="navbar-nav">
                    <a class="nav-link active" href="#home">Home</a>
                    <a class="nav-link" href="#rentals">Rentals</a>
                    <a class="nav-link" href="#feedback">Feedback</a>
                    <a class="nav-link" href="#about">About Us</a>
                </div>
            </div>
        </nav>

        <div class="container">
            <!-- Alert Banner -->
            <?php if (isset($_COOKIE['err_message'])): ?>
                <div class="alert <?= htmlspecialchars($_COOKIE['message_class']) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_COOKIE['err_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <!-- End Alert Banner -->
        </div>

        <!-- Shared Background Carousel for Hero + Features -->
        <section class="position-relative text-white" style="overflow: hidden;">
            <!-- Carousel Background -->
            <div id="bgCarousel" class="carousel slide position-absolute w-100 h-100 top-0 start-0 z-n1" data-bs-ride="carousel" data-bs-interval="3500">
                <div class="carousel-inner h-100">
                    <?php if (!empty($cars_data)): ?>
                        <?php $first = true; ?>
                        <?php foreach ($cars_data as $car): ?>
                            <div class="carousel-item h-100 <?php echo $first ? 'active' : ''; ?>">
                                <img src="uploads/<?php echo htmlspecialchars($car['image_path']); ?>" class="d-block w-100 h-100 object-fit-cover" alt="Car Image">
                                <div class="position-absolute top-0 start-0 w-100 h-100 bg-dark opacity-50"></div>
                            </div>
                            <?php $first = false; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="carousel-item active h-100 bg-secondary"></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- HERO Section -->
            <section class="hero text-center d-flex flex-column justify-content-center align-items-center" id="home" style="min-height: 60vh; z-index: 2; position: relative;">
                <h1 class="display-4 fw-bold">Caring for Cars Since You Were Born</h1>
                <p class="lead">Your trusted partner in reliable and affordable car rentals.</p>
                <a href="#rentals" class="btn btn-warning btn-lg mt-4">Browse Vehicles</a>
            </section>

            <!-- FEATURES Section -->
            <section class="container py-5" id="features" style="position: relative; z-index: 2;">
                <div class="row text-center">
                    <div class="col-md-4">
                        <div class="feature-icon mb-2">🚗</div>
                        <h5>Wide Vehicle Selection</h5>
                        <p>Choose from sedans, SUVs, vans and more.</p>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-icon mb-2">📅</div>
                        <h5>Easy Booking</h5>
                        <p>Reserve your rental online anytime, anywhere.</p>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-icon mb-2">💬</div>
                        <h5>Reliable Feedback</h5>
                        <p>See what customers say about our service.</p>
                    </div>
                </div>
            </section>
        </section>


        <section class="bg-light py-5" id="rentals">
            <div class="container">
                <h2 class="text-center mb-4">Available Rentals</h2>
                <div class="row" id="rentalCards">
                    <?php if (empty($cars_data)): ?>
                        <p class="text-center">No cars available for rent at the moment.</p>
                    <?php else: ?>
                        <?php $count = 0; ?>
                        <?php foreach ($cars_data as $car): ?>
                            <?php if ($count >= 6) break; ?>
                            <div class="col-md-4 col-sm-6 mb-4 d-flex">
                                <div class="card w-100 shadow-sm">
                                    <img src="uploads/<?php echo htmlspecialchars($car['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?>">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model']); ?></h5>
                                        <p class="card-text">Year: <?php echo htmlspecialchars($car['year']); ?></p>
                                        <p class="card-text text-muted">₱<?php echo number_format($car['price'], 2); ?> / day</p>
                                        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#loginPromptModal">
                                            Reserve Now
                                        </button>

                                    </div>
                                </div>
                            </div>
                            <?php $count++; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- See More Button (Only shown if more than 6 cars) -->
                <?php if (count($cars_data) > 6): ?>
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#seeMoreModal">See More</button>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- See More Modal -->
        <div class="modal fade" id="seeMoreModal" tabindex="-1" aria-labelledby="seeMoreModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-warning text-dark">
                        <h5 class="modal-title" id="seeMoreModalLabel">Login Required</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p>To view all available rental cars, please log in to your account.</p>
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Login Prompt Modal -->
        <div class="modal fade" id="loginPromptModal" tabindex="-1" aria-labelledby="loginPromptModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="loginPromptModalLabel">Login Required</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <p class="mb-3">To make a reservation, please log in to your account.</p>
                        <a href="login.php" class="btn btn-primary">Login Now</a>
                    </div>
                </div>
            </div>
        </div>


        <section class="container py-5" id="feedback">
            <h2 class="text-center mb-4">Customer Feedback</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Vehicle</th>
                            <th>Rating</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamically injected feedback rows using PHP -->
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-light py-5" id="about">
            <div class="container text-center">
                <h2>About KRM Rent a Car</h2>
                <p class="lead">We're passionate about getting you safely on the road with style and ease.</p>
                <div class="d-flex justify-content-center">
                    <!-- Sample profile info -->
                    <div>
                        <img src="assets/admin.jpg" alt="Admin" class="rounded-circle mb-3" width="100">
                        <h5>Juan Dela Cruz</h5>
                        <p>Email: krm@example.com<br>Phone: 0912-345-6789<br>Location: Cavite, PH</p>
                    </div>
                </div>
            </div>
        </section>

        <footer>
            <p>&copy; 2025 KRM Rent a Car Services. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/bootstrap.bundle.min.js"></script>
</body>

</html>