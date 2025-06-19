<?php
session_start();
include '../conn.php';


$display_message = '';
$message_type = ''; // 'success' or 'error'

// Check for messages from session
if (isset($_SESSION['response_message'])) {
    $display_message = $_SESSION['response_message'];
    $message_type = $_SESSION['response_type'] ?? 'success'; // Default to success if type not set
    // Clear the session variables after displaying
    unset($_SESSION['response_message']);
    unset($_SESSION['response_type']);
}

// Function to set a message for display (used within this page's PHP logic)
function set_message($message, $type = 'success')
{
    $_SESSION['response_message'] = $message;
    $_SESSION['response_type'] = $type;
}


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

// --- PHP for handling Reservation Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'reservation') {
    // Collect and sanitize form data
    $fullName = mysqli_real_escape_string($conn, $_POST['fullName']);
    $licenseNumber = mysqli_real_escape_string($conn, $_POST['licenseNumber']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $messengerName = mysqli_real_escape_string($conn, $_POST['messengerName']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNumber']);
    $rentalCarId = mysqli_real_escape_string($conn, $_POST['rentalCar']);
    $pickupDate = mysqli_real_escape_string($conn, $_POST['pickupDate']);
    $returnDate = mysqli_real_escape_string($conn, $_POST['returnDate']);
    $pickupLocation = mysqli_real_escape_string($conn, $_POST['pickupLocation']);
    $passengerCount = mysqli_real_escape_string($conn, $_POST['passengerCount']);
    $accommodations = mysqli_real_escape_string($conn, $_POST['accommodations']);
    $specialRequests = mysqli_real_escape_string($conn, $_POST['specialRequests']);
    $estimatedPrice = mysqli_real_escape_string($conn, $_POST['estimatedPrice']);

    error_log("[KRM DEBUG] Reservation attempt details: Full Name: $fullName, License Number: $licenseNumber, Email: $email, Phone: $phoneNumber"); // Detailed Debugging log

    $customer_id = null;
    $customer_found = false;


    if (!empty($email)) { // Only search by email if it's not empty
        $sql_check_customer_by_email = "SELECT id FROM customers WHERE email = ?";
        $stmt_check_customer_by_email = mysqli_prepare($conn, $sql_check_customer_by_email);
        if ($stmt_check_customer_by_email) {
            mysqli_stmt_bind_param($stmt_check_customer_by_email, "s", $email);
            mysqli_stmt_execute($stmt_check_customer_by_email);
            $result_check_customer_by_email = mysqli_stmt_get_result($stmt_check_customer_by_email);
            if (mysqli_num_rows($result_check_customer_by_email) > 0) {
                $customer_row = mysqli_fetch_assoc($result_check_customer_by_email);
                $customer_id = $customer_row['id'];
                $customer_found = true;
                error_log("[KRM DEBUG] Existing customer found by license. ID: $customer_id");
            }
            mysqli_stmt_close($stmt_check_customer_by_email);
        } else {
            error_log('[KRM ERROR] Error preparing customer check by email statement: ' . mysqli_error($conn));
        }
    }



    if (!$customer_found && !empty($licenseNumber)) { // Only search by license if not found by email and license is not empty
        $sql_check_customer_by_license = "SELECT id FROM customers WHERE driver_license_number = ?";
        $stmt_check_customer_by_license = mysqli_prepare($conn, $sql_check_customer_by_license);
        if ($stmt_check_customer_by_license) {
            mysqli_stmt_bind_param($stmt_check_customer_by_license, "s", $licenseNumber);
            mysqli_stmt_execute($stmt_check_customer_by_license);
            $result_check_customer_by_license = mysqli_stmt_get_result($stmt_check_customer_by_license);
            if (mysqli_num_rows($result_check_customer_by_license) > 0) {
                $customer_row = mysqli_fetch_assoc($result_check_customer_by_license);
                $customer_id = $customer_row['id'];
                $customer_found = true;
                error_log("[KRM DEBUG] Existing customer found by license. ID: $customer_id");
            }
            mysqli_stmt_close($stmt_check_customer_by_license);
        } else {
            error_log('[KRM ERROR] Error preparing customer check by license statement: ' . mysqli_error($conn));
        }
    }



    if ($customer_found) {
        // Customer exists: UPDATE their details with latest info from form
        // This ensures the customer's name in the database reflects what was just entered.
        $sql_update_customer = "UPDATE customers SET name=?, email=?, address=?, messenger_name=?, phone_number=? WHERE id=?";
        $stmt_update_customer = mysqli_prepare($conn, $sql_update_customer);
        if ($stmt_update_customer) {
            mysqli_stmt_bind_param($stmt_update_customer, "sssssi", $fullName, $email, $address, $messengerName, $phoneNumber, $customer_id);
            if (mysqli_stmt_execute($stmt_update_customer)) {
                error_log("[KRM DEBUG] Existing customer details updated for ID: $customer_id, New Name: $fullName");
                // Message about update will be part of the reservation success message.
            } else {
                $db_error = mysqli_error($conn);
                set_message('Error updating existing customer profile: ' . $db_error, 'error');
                error_log('[KRM ERROR] Error updating existing customer: ' . $db_error);
                header("Location: index.php?tab=reservation"); // Redirect to show message
                exit();
            }
            mysqli_stmt_close($stmt_update_customer);
        } else {
            set_message('Error preparing customer update statement: ' . mysqli_error($conn), 'error');
            error_log('[KRM ERROR] Error preparing customer update statement: ' . mysqli_error($conn));
            header("Location: index.php?tab=reservation");
            exit();
        }
    } else {
        // Customer does not exist: INSERT new customer
        $sql_insert_customer = "INSERT INTO customers (name, driver_license_number, email, address, messenger_name, phone_number) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_customer = mysqli_prepare($conn, $sql_insert_customer);
        if ($stmt_insert_customer) {
            mysqli_stmt_bind_param($stmt_insert_customer, "ssssss", $fullName, $licenseNumber, $email, $address, $messengerName, $phoneNumber);
            if (mysqli_stmt_execute($stmt_insert_customer)) {
                $customer_id = mysqli_insert_id($conn);
                set_message("New customer profile created successfully for " . htmlspecialchars($fullName) . "!");
                error_log("[KRM DEBUG] New customer inserted with ID: $customer_id, Name: $fullName, Email: $email");
            } else {
                $db_error = mysqli_error($conn);
                if (str_contains($db_error, "Duplicate entry") && str_contains($db_error, "for key 'email'")) {
                    set_message('Error: An account with this email already exists. Please use a different email or check your details.', 'error');
                } else if (str_contains($db_error, "Duplicate entry") && str_contains($db_error, "for key 'driver_license_number'")) {
                    set_message('Error: An account with this driver license number already exists. Please check your details.', 'error');
                } else {
                    set_message('Error adding new customer: ' . $db_error, 'error');
                }
                error_log('[KRM ERROR] Error adding new customer: ' . $db_error);
                header("Location: index.php?tab=reservation");
                exit();
            }
            mysqli_stmt_close($stmt_insert_customer);
        } else {
            set_message('Error preparing customer insert statement: ' . mysqli_error($conn), 'error');
            error_log('[KRM ERROR] Error preparing customer insert statement: ' . mysqli_error($conn));
            header("Location: index.php?tab=reservation");
            exit();
        }
    }

    if ($customer_id) {
        $status = 'pending'; // Default status for new reservations
        $sql_insert_reservation = "INSERT INTO reservations (customer_id, car_id, pickup_date, return_date, pickup_location, passenger_count, accommodations, special_requests, estimated_price, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_reservation = mysqli_prepare($conn, $sql_insert_reservation);
        if ($stmt_insert_reservation) {
            mysqli_stmt_bind_param($stmt_insert_reservation, "iisssissss", $customer_id, $rentalCarId, $pickupDate, $returnDate, $pickupLocation, $passengerCount, $accommodations, $specialRequests, $estimatedPrice, $status);
            if (mysqli_stmt_execute($stmt_insert_reservation)) {
                $reservation_id = mysqli_insert_id($conn);
                // Combine messages for clarity if customer was updated
                if ($customer_found) {
                    set_message('Reservation submitted successfully for existing customer ' . htmlspecialchars($fullName) . '! Your reservation ID is: ' . $reservation_id, 'success');
                } else {
                    set_message('Reservation submitted successfully! Your reservation ID is: ' . $reservation_id, 'success');
                }
                error_log("[KRM DEBUG] Reservation submitted for customer ID: $customer_id, Reservation ID: $reservation_id");
                // Redirect to self to clear POST data and show message
                header("Location: index.php?tab=reservation");
                exit();
            } else {
                set_message('Error submitting reservation: ' . mysqli_error($conn), 'error');
                error_log('[KRM ERROR] Error submitting reservation: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($stmt_insert_reservation);
        } else {
            set_message('Error preparing reservation insert statement: ' . mysqli_error($conn), 'error');
            error_log('[KRM ERROR] Error preparing reservation insert statement: ' . mysqli_error($conn));
        }
    } else {
        set_message('Failed to obtain customer ID for reservation. Customer creation/lookup might have failed.', 'error');
        error_log('[KRM ERROR] Failed to obtain customer ID for reservation.');
    }
}

// --- PHP for handling Feedback Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'feedback') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $phoneNumber = mysqli_real_escape_string($conn, $_POST['phoneNum']);
    $rentedCar = mysqli_real_escape_string($conn, $_POST['rentedCar']); // This is car ID
    $rating = mysqli_real_escape_string($conn, $_POST['rating']);
    $comments = mysqli_real_escape_string($conn, $_POST['feedback']);

    $sql_insert_feedback = "INSERT INTO feedbacks (name, phone_number, car_id, rating, comments) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert_feedback = mysqli_prepare($conn, $sql_insert_feedback);

    if ($stmt_insert_feedback) {
        mysqli_stmt_bind_param($stmt_insert_feedback, "ssiis", $name, $phoneNumber, $rentedCar, $rating, $comments);
        if (mysqli_stmt_execute($stmt_insert_feedback)) {
            set_message('Feedback submitted successfully!', 'success');
            // Redirect to self to clear POST data and show message
            header("Location: index.php?tab=feedback");
            exit();
        } else {
            set_message('Error submitting feedback: ' . mysqli_error($conn), 'error');
            error_log('[KRM ERROR] Error submitting feedback: ' . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt_insert_feedback);
    } else {
        set_message('Error preparing feedback insert statement: ' . mysqli_error($conn), 'error');
        error_log('[KRM ERROR] Error preparing feedback insert statement: ' . mysqli_error($conn));
    }
}

// --- PHP for fetching Admin Info for About Us Tab ---
$admin_name_about = "KRM Rent a Car Services";
$admin_email_about = "ADMIN@GMAIL.COM";
$admin_phone_about = "09267369135";
$admin_location_about = "Rosario, Cavite";
$admin_profile_photo = ""; // Default
$admin_license_image = ""; // Default

$sql_fetch_admin_about = "SELECT name, email, phone, location, profile_photo_path, license_image_path FROM admins WHERE id = 1"; // Assuming admin ID 1
$result_fetch_admin_about = mysqli_query($conn, $sql_fetch_admin_about);
if ($result_fetch_admin_about && mysqli_num_rows($result_fetch_admin_about) > 0) {
    $admin_data_about = mysqli_fetch_assoc($result_fetch_admin_about);
    $admin_name_about = htmlspecialchars($admin_data_about['name']);
    $admin_email_about = htmlspecialchars($admin_data_about['email']);
    $admin_phone_about = htmlspecialchars($admin_data_about['phone']);
    $admin_location_about = htmlspecialchars($admin_data_about['location']);

    if (!empty($admin_data_about['profile_photo_path'])) {
        $admin_profile_photo = "uploads/" . htmlspecialchars($admin_data_about['profile_photo_path']);
    }
    if (!empty($admin_data_about['license_image_path'])) {
        $admin_license_image = "uploads/" . htmlspecialchars($admin_data_about['license_image_path']);
    }
}

// --- PHP for fetching Feedback data for Feedback Tab ---
$feedbacks_data = [];
$sql_feedbacks = "SELECT f.id, f.name, f.rating, f.comments, c.make, c.model, c.year
                  FROM feedbacks f
                  LEFT JOIN cars c ON f.car_id = c.id
                  ORDER BY f.created_at DESC";
$result_feedbacks = mysqli_query($conn, $sql_feedbacks);
if ($result_feedbacks) {
    while ($row = mysqli_fetch_assoc($result_feedbacks)) {
        $feedbacks_data[] = $row;
    }
} else {
    set_message('Error fetching feedback data: ' . mysqli_error($conn), 'error');
    error_log('[KRM ERROR] Error fetching feedback data: ' . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KRM Rent a Car Services Portal</title>
    <link rel="stylesheet" href="customer.css">
    <style>
        /* Styles for the custom message box */
        .message-box {
            padding: 15px 20px;
            margin: 20px auto;
            /* Center the message box */
            border-radius: 8px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1120px;
            /* Match main content width */
        }

        .message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message-box .close-message {
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">KRM RENT A CAR SERVICES PORTAL</div>
        <div class="header-buttons">
            <button class="btn btn-primary" onclick="adminPanel()">ADMIN PANEL</button>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="nav-tabs" id="navBar">
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

    <script>
        // Tab switching functionality
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll(".tab-content");
            tabs.forEach((tab) => tab.classList.add("hidden"));

            // Remove active class from all nav tabs
            const navTabs = document.querySelectorAll(".nav-tab");
            navTabs.forEach((tab) => tab.classList.remove("active"));

            // Show selected tab
            document.getElementById(tabName + "-tab").classList.remove("hidden");

            // Add active class to selected nav tab using event.target or find by tabName
            const clickedButton = Array.from(navTabs).find(button => button.getAttribute('onclick') === `showTab('${tabName}')`);
            if (clickedButton) {
                clickedButton.classList.add("active");
            }

            // Save the active tab to localStorage
            localStorage.setItem('customer_last_active_tab', tabName);
        }

        function showMessageBox(message, type) {
            const messageBox = document.getElementById('messageBox');
            const messageText = messageBox.querySelector('span');
            messageText.textContent = message;
            messageBox.className = 'message-box ' + type; // Reset classes and add type
            messageBox.style.display = 'flex'; // Show the box
            // Automatically hide the message after 5 seconds
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
        }

        // Check if there's a PHP message to display on page load
        <?php if (!empty($display_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showMessageBox('<?php echo $display_message; ?>', '<?php echo $message_type; ?>');
            });
        <?php endif; ?>


        // Modal functionality for Car Details
        function showCarDetails(carId) {
            // Fetch car data from PHP (cars_data array) and populate modal
            const carsData = <?php echo json_encode($cars_data); ?>;
            const selectedCar = carsData.find(car => car.id == carId);

            if (selectedCar) {
                document.getElementById("modalTitle").textContent = selectedCar.make + ' ' + selectedCar.model + ' ' + selectedCar.year;
                document.getElementById("make").textContent = selectedCar.make;
                document.getElementById("model").textContent = selectedCar.model;
                document.getElementById("year").textContent = selectedCar.year;
                document.getElementById("color").textContent = selectedCar.color;
                document.getElementById("engine").textContent = selectedCar.engine;
                document.getElementById("transmission").textContent = selectedCar.transmission;
                document.getElementById("fuelEconomy").textContent = selectedCar.fuel_economy;
                document.getElementById("passengerVolume").textContent = selectedCar.seating_capacity;
                document.getElementById("safetyFeatures").textContent = selectedCar.safety_features;
                document.getElementById("additionalFeatures").textContent = selectedCar.additional_features;
                document.getElementById("basePrice").textContent = parseFloat(selectedCar.price).toFixed(2);

                document.getElementById("car-modal").classList.add("active");
            } else {
                showMessageBox("Car details not found.", "error");
            }
        }

        function reserveNow() {
            document.getElementById("reserve-modal").classList.add("active");
        }

        function adminPanel() {
            window.location.href = 'login.php';
            console.log("Welcome to Admin Login - KRM Rent a Car Services");
        }

        function addFeedback() {
            document.getElementById("feedback-modal").classList.add("active");
        }

        function closeModal() {
            document.getElementById("reserve-modal").classList.remove("active");
            document.getElementById("feedback-modal").classList.remove("active");
            document.getElementById("car-modal").classList.remove("active");
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const feedbackModal = document.getElementById("feedback-modal");
            const carModal = document.getElementById("car-modal");

            if (feedbackModal && event.target === feedbackModal) {
                closeModal();
            }
            if (carModal && event.target === carModal) {
                closeModal();
            }
        }

        // Initial active tab setting on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlHash = window.location.hash.substring(1); // Get hash without '#'
            const lastActiveTab = localStorage.getItem('customer_last_active_tab');

            // Check if the URL has a hash, specifically for a "fresh" load to the home tab (e.g., from admin logout)
            if (urlHash === 'home-tab') {
                showTab('home');
                // Clear the hash from the URL so subsequent refreshes respect localStorage
                window.history.replaceState({}, document.title, window.location.pathname);
            } else if (lastActiveTab) {
                // If no specific hash for home, but a tab is stored, use it (for regular refreshes)
                showTab(lastActiveTab);
            } else {
                // If neither, default to 'home' (first time access or localStorage cleared)
                showTab('home');
            }
        });

        // Event listener for estimated price calculation (basic example)
        document.addEventListener('DOMContentLoaded', function() {
            const pickupDateInput = document.getElementById('pickupDate');
            const returnDateInput = document.getElementById('returnDate');
            const rentalCarSelect = document.getElementById('rentalCar');
            const estimatedPriceInput = document.getElementById('estimatedPrice');
            const reservationForm = document.getElementById('reservationForm'); // Get the reservation form

            function calculateEstimatedPrice() {
                const pickupDate = new Date(pickupDateInput.value);
                const returnDate = new Date(returnDateInput.value);
                const selectedCarId = rentalCarSelect.value;
                const carsData = <?php echo json_encode($cars_data); ?>;
                const selectedCar = carsData.find(car => car.id == selectedCarId);

                if (pickupDate && returnDate && selectedCar && pickupDate < returnDate) {
                    const timeDiff = returnDate.getTime() - pickupDate.getTime();
                    const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    const basePricePerDay = parseFloat(selectedCar.price); // Use base price from DB
                    if (!isNaN(basePricePerDay) && daysDiff > 0) {
                        const calculatedPrice = basePricePerDay * daysDiff;
                        estimatedPriceInput.value = calculatedPrice.toFixed(2);
                    } else {
                        estimatedPriceInput.value = 'N/A';
                    }
                } else {
                    estimatedPriceInput.value = 'N/A';
                }
            }

            // Attach event listeners for calculating price
            pickupDateInput.addEventListener('change', calculateEstimatedPrice);
            returnDateInput.addEventListener('change', calculateEstimatedPrice);
            rentalCarSelect.addEventListener('change', calculateEstimatedPrice);

            // This part handles clearing the form after a successful submission if the page reloads.
            // The PHP now redirects directly to index.php?tab=reservation after submission, which will then trigger showTab.
            // The fields will naturally be cleared on reload. No client-side reset is needed here, as it's a full page reload.
        });
    </script>
</body>

</html>