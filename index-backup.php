    <?php
    session_start();
    include 'conn.php';

    ?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>KRM Rent a Car Services Portal</title>
        <link rel="stylesheet" href="customer.css">
        <link rel="stylesheet" href="css/bootstrap.min.css">
        <script src="js/bootstrap.bundle.min.js" type="text/javascript"></script>

    </head>

    <body>
        <!-- Header -->
        <header class="header">
            <div class="logo">KRM RENT A CAR SERVICES PORTAL</div>
            <div class="header-buttons">
                <!-- <button class="btn btn-primary" onclick="adminPanel()">ADMIN PANEL</button> -->
                <div class="d-flex align-items-center">
                    <a href="login.php" class="btn btn-link px-3 me-2">
                        Login
                    </a>
                    <a href="signup.php" class="btn btn-primary me-3">
                        Sign up for free
                    </a>
                </div>
            </div>
        </header>

        <!-- Navigation Tabs -->
        <nav class=" nav-tabs" id="navBar">
            <button class="nav-tab active" onclick="showTab('home')">HOME</button>
            <button class="nav-tab" onclick="showTab('rentals')">RENTALS</button>
            <button class="nav-tab" onclick="showTab('feedback')">FEEDBACK &amp; RATING</button>
            <button class="nav-tab" onclick="showTab('about-us')">ABOUT US</button>
        </nav>

        <!-- Main Content -->
        <main class="main-content" id="main-content">
            <!-- Custom Message Box -->
            <div id="messageBox" class="message-box <?php echo $message_type; ?>" style="display: <?php echo !empty($display_message) ? 'flex' : 'none'; ?>;">
                <span><?php echo $display_message; ?></span>
                <span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span>
            </div>

            <!-- Home Tab -->
            <div id="home-tab" class="tab-content active"> <!-- Set home tab active by default -->
                <section class="face" id="welcome">
                    <section class="welcome-card">
                        <h1>KRM SERVICE</h1>
                        <h2>Caring for cars since you were born</h2>
                    </section>
                    <section class="welcome-section">
                        <h2>Welcome to KRM Rent a Car Services</h2>
                        <p>Your trusted partner for car rental services</p>
                    </section>
                </section>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">&#x1F697;</div> <!-- Unicode car icon -->
                        <div class="feature-title">Wide Vehicle Selection</div>
                        <div class="feature-description">
                            Choose from our extensive fleet of well-maintained vehicles
                        </div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">&#x1F4DD;</div> <!-- Unicode pen/document icon -->
                        <div class="feature-title">Online Reservation</div>
                        <div class="feature-description">
                            Reserve your rental car quickly and easily
                        </div>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">&#x1F4AD;</div> <!-- feedback icon -->
                        <div class="feature-title">Feedback</div>
                        <div class="feature-description">
                            Open for feedback and ratings
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rentals Tab -->
            <div id="rentals-tab" class="tab-content hidden">
                <div class="card-grid">
                    <?php if (empty($cars_data)): ?>
                        <p>No cars available for rent at the moment.</p>
                    <?php else: ?>
                        <?php foreach ($cars_data as $car): ?>
                            <button class="card" onclick="showCarDetails(<?php echo $car['id']; ?>)">
                                <img class="car-image" src="uploads/<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo $car['make'] . ' ' . $car['model']; ?>">
                                <div class="car-info"><?php echo htmlspecialchars($car['make']); ?><br><?php echo htmlspecialchars($car['model']) . ' ' . htmlspecialchars($car['year']); ?></div>
                            </button>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="submit-btn" onclick="reserveNow()">RESERVE NOW</button>
                <div class="form-label">
                    *Prices may vary depending on the agreement between the Renter and the owner
                </div>
            </div>

            <!-- Car Details Modal -->
            <div id="car-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title" id="modalTitle"></div>
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                    </div>

                    <div class="list-group">
                        <div class="specs">
                            <h4>Make</h4>
                            <p id="make"></p>
                        </div>
                        <div class="specs">
                            <h4>Model</h4>
                            <p id="model"></p>
                        </div>
                        <div class="specs">
                            <h4>Year</h4>
                            <p id="year"></p>
                        </div>
                        <div class="specs">
                            <h4>Color</h4>
                            <p id="color"></p>
                        </div>
                        <div class="specs">
                            <h4>Engine</h4>
                            <p id="engine"></p>
                        </div>
                        <div class="specs">
                            <h4>Transmission</h4>
                            <p id="transmission"></p>
                        </div>
                        <div class="specs">
                            <h4>Fuel Economy</h4>
                            <p id="fuelEconomy"></p>
                        </div>
                        <div class="specs">
                            <h4>Seating Capacity</h4>
                            <p id="passengerVolume"></p>
                        </div>
                        <div class="specs">
                            <h4>Safety Features</h4>
                            <p id="safetyFeatures"></p>
                        </div>
                        <div class="specs">
                            <h4>Additional Features</h4>
                            <p id="additionalFeatures"></p>
                        </div>
                        <div class="specs">
                            <h4>Base Price</h4>
                            <p id="basePrice"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reservation Modal -->
            <div class="modal" id="reserve-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title">Reservation Form</div>
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                    </div>
                    <form action="" method="POST" id="reservationForm">
                        <input type="hidden" name="form_type" value="reservation">
                        <div class="form-group">
                            <label class="form-label" for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="fullName" class="form-input" placeholder="e.g. Juan dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="licenseNumber">Rental Driver's License Number</label>
                            <input type="text" id="licenseNumber" name="licenseNumber" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="e.g. juandelacruz@example.com" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="address">Complete Address</label>
                            <input type="text" id="address" name="address" class="form-input" placeholder="e.g. 123 Anywhere St., Municipality, Any City, Province" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="messengerName">Messenger Name</label>
                            <input type="text" id="messengerName" name="messengerName" class="form-input" placeholder="e.g. Juan dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phoneNumber">Phone Number</label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" class="form-input" placeholder="e.g. 09123456789" minlength="11" maxlength="11" required>
                        </div>
                        <div class="form-group">
                            <label for="rentalCar" class="form-label">Rental Car</label>
                            <select id="rentalCar" name="rentalCar" class="form-input" required>
                                <option value="" disabled selected>Select a rental car</option>
                                <?php foreach ($cars_data as $car): ?>
                                    <option value="<?php echo $car['id']; ?>"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <div class="form-row">
                                <label class="form-label" for="pickupDate">Pickup Date</label>
                                <input type="date" id="pickupDate" name="pickupDate" required>
                                <label class="form-label" for="returnDate">Return Date</label>
                                <input type="date" id="returnDate" name="returnDate" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="pickupLocation">Pickup Location</label>
                            <input type="text" id="pickupLocation" name="pickupLocation" class="form-input" placeholder="e.g. Rosario, Cavite" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="passengerCount">Passenger Count</label>
                            <input type="number" id="passengerCount" name="passengerCount" class="form-input" placeholder="Enter maximum number of passengers" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="accommodations">Accommodations</label>
                            <input type="text" id="accommodations" name="accommodations" class="form-input" placeholder="Optional (e.g. Novotel Hotel)">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="specialRequests">Special Requests</label>
                            <input type="text" id="specialRequests" name="specialRequests" class="form-input" placeholder="Optional (e.g. child seat, GPS, etc., or N/A if none)">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Renter's Warranty</label>
                            <div class="agreement">
                                The Renter agrees that Renter will not
                                <br /><br />
                                (a) Vehicle is only allowed to be operated by the Renter stated on his agreement;
                                <br /><br />
                                (b) Allow any other person with no valid driving license to operate the vehicle;
                                <br /><br />
                                (c) Operate the vehicle in violation of any laws or for an illegal purpose and that if the
                                renter does, Renter is responsible for all associated tickets, fines and fees;
                                <br /><br />
                                (d) Used the vehicle to push or tow another vehicle;
                                <br /><br />
                                (e) Use the vehicle for any race competition;
                                <br /><br />
                                (f) Operate the vehicle in a negligent manner;
                                <br /><br />
                                (g) The Renter shall not remove the GPS Tracker, otherwise it will automatically report
                                as vehicle theft and penalty of Php 5,000 and immediate pullout of vehicle;
                                <br /><br />
                                (h) Renter warranty to use the vehicle on the declared destinations listed above,
                                failure to comply will result to Php 3,000 penalty and Php 10,000 for inter-island trips;
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">
                                Please make sure the information above are all correct.
                                <br>
                                Warning: Once the car is reserved, there is no cancellation.
                            </label>
                            <input type="checkbox" required>
                            <label>Click if you agree to the Renter's Warranty</label>
                        </div>
                        <button type="submit" class="submit-btn" onclick="">Reserve Now</button>
                    </form>
                </div>
            </div>

            <!-- Feedback Tab -->
            <div id="feedback-tab" class="tab-content hidden">
                <div class="modal-header">
                    <div class="modal-title">Customer Feedbacks</div>
                    <button class="btn feedback" onclick="addFeedback()">Add feedback</button>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Name</th>
                            <th>Rented car</th>
                            <th>Rating (5/5)</th>
                            <th>Comments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($feedbacks_data)): ?>
                            <tr>
                                <td colspan="5">No feedback available yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php $count = 1; ?>
                            <?php foreach ($feedbacks_data as $feedback): ?>
                                <tr>
                                    <td><?php echo $count++; ?></td>
                                    <td><?php echo htmlspecialchars($feedback['name']); ?></td>
                                    <td><?php echo htmlspecialchars($feedback['make'] . ' ' . $feedback['model'] . ' ' . $feedback['year']); ?></td>
                                    <td><?php echo htmlspecialchars($feedback['rating']); ?></td>
                                    <td><?php echo htmlspecialchars($feedback['comments']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Fedback Modal -->
            <div id="feedback-modal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3 class="modal-title">Feedback &amp; Review</h3>
                        <button class="close-btn" onclick="closeModal()">&times;</button>
                    </div>

                    <form action="" method="POST" id="feedbackForm">
                        <input type="hidden" name="form_type" value="feedback">
                        <div class="form-group">
                            <label for="name" class="form-label">Name</label>
                            <input
                                type="text"
                                id="feedbackName"
                                name="name"
                                placeholder="e.g. Juan dela Cruz"
                                required
                                class="form-input" />
                        </div>
                        <div class="form-group">
                            <label for="phoneNum" class="form-label">Phone number</label>
                            <input
                                type="tel"
                                id="feedbackPhoneNum"
                                name="phoneNum"
                                placeholder="e.g. 09123456789"
                                minlength="11" maxlength="11"
                                class="form-input" />
                        </div>
                        <div class="form-group">
                            <label for="rentedCar" class="form-label">Rented car</label>
                            <select id="rentedCar" name="rentedCar" class="form-input" required>
                                <option value="" disabled selected>Select rented car</option>
                                <?php foreach ($cars_data as $car): ?>
                                    <option value="<?php echo $car['id']; ?>"><?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="rating" class="form-label">Rating</label>
                            <select id="rating" name="rating" class="form-input" required>
                                <option value="" disabled selected>Select rating</option>
                                <option value="5">5 - Excellent</option>
                                <option value="4">4 - Very Good</option>
                                <option value="3">3 - Good</option>
                                <option value="2">2 - Fair</option>
                                <option value="1">1 - Poor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="comments" class="form-label">Comments</label>
                            <textarea
                                id="comments"
                                name="feedback"
                                placeholder="Write your feedback or review here..."
                                class="form-input" required>
                        </textarea>
                        </div>
                        <button type="submit" class="submit-btn">Submit Feedback</button>
                    </form>
                </div>
            </div>

            <!-- About Us -->
            <div id="about-us-tab" class="tab-content hidden">
                <div class="profile">
                    <div class="profile-image-container">
                        <?php if (!empty($admin_profile_photo)): ?>
                            <img src="<?php echo $admin_profile_photo; ?>" alt="Admin Profile Photo" class="profile-photo-about">
                        <?php else: ?>
                            <div class="profile-photo-placeholder">[PROFILE PHOTO]</div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo $admin_name_about; ?></h3>
                        <p><?php echo $admin_email_about; ?></p>
                        <p><?php echo $admin_phone_about; ?></p>
                        <p><?php echo $admin_location_about; ?></p>
                    </div>
                </div>
                <div class="license-box-about">
                    <?php if (!empty($admin_license_image)): ?>
                        <img src="<?php echo $admin_license_image; ?>" alt="Driver's License Document" class="license-image">
                        <span id="licensePlaceholderText" style="display: none;">
                            [DRIVER'S LICENSE DOCUMENT IMAGE]
                        </span>
                    <?php else: ?>
                        <img
                            alt="Driver's license document image"
                            class="license-image"
                            id="licenseImage"
                            src=""
                            style="display: none;" />
                        <span id="licensePlaceholderText">
                            [DRIVER'S LICENSE DOCUMENT IMAGE]
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </main>


    </body>

    </html>