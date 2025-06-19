<?php
session_start();
include '../conn.php';


$display_message = '';
$message_type = ''; // 'success' or 'error'

// Logout function
if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    // Redirect to index.php and specifically to the 'home' tab
    header("Location: ../");
    exit();
}

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
$sql_cars = "SELECT * FROM cars";
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
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['form_type']) && $_POST['form_type'] === 'reservation') {
    require_once '../conn.php';

    // Basic sanitization
    $fullName         = trim($_POST['fullName']);
    $licenseNumber    = trim($_POST['licenseNumber']);
    $email            = trim($_POST['email']);
    $address          = trim($_POST['address']);
    $messengerName    = trim($_POST['messengerName']);
    $phoneNumber      = trim($_POST['phoneNumber']);
    $rentalCarId      = (int) $_POST['rentalCar'];
    $pickupDate       = $_POST['pickupDate'];
    $returnDate       = $_POST['returnDate'];
    $pickupLocation   = trim($_POST['pickupLocation']);
    $passengerCount   = (int) $_POST['passengerCount'];
    $accommodations   = trim($_POST['accommodations']);
    $specialRequests  = trim($_POST['specialRequests']);
    $estimatedPrice = isset($_POST['estimatedPrice']) && is_numeric($_POST['estimatedPrice'])
        ? number_format((float)$_POST['estimatedPrice'], 2, '.', '')
        : 0.00;


    $carStatus        = "rented";
    $status           = "pending";

    error_log("[KRM DEBUG] Reservation submission received: $fullName, $email");

    $customer_id = null;
    $customer_found = false;

    // ✅ Check customer by email
    if (!empty($email)) {
        $sql = "SELECT id FROM customers WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $customer_id = $row['id'];
                $customer_found = true;
                error_log("[KRM DEBUG] Existing customer found by email. ID: $customer_id");
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("[KRM ERROR] Email check failed: " . mysqli_error($conn));
        }
    }

    // ✅ If not found, check by license number
    if (!$customer_found && !empty($licenseNumber)) {
        $sql = "SELECT id FROM customers WHERE driver_license_number = ?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $licenseNumber);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $customer_id = $row['id'];
                $customer_found = true;
                error_log("[KRM DEBUG] Existing customer found by license. ID: $customer_id");
            }
            mysqli_stmt_close($stmt);
        } else {
            error_log("[KRM ERROR] License check failed: " . mysqli_error($conn));
        }
    }

    // ✅ Update or insert customer
    if ($customer_found) {
        $sql = "UPDATE customers SET name=?, email=?, address=?, messenger_name=?, phone_number=? WHERE id=?";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sssssi", $fullName, $email, $address, $messengerName, $phoneNumber, $customer_id);
            if (mysqli_stmt_execute($stmt)) {
                error_log("[KRM DEBUG] Customer profile updated. ID: $customer_id");
            } else {
                set_message('Error updating customer: ' . mysqli_error($conn), 'error');
                header("Location: index.php?tab=reservation");
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $sql = "INSERT INTO customers (name, driver_license_number, email, address, messenger_name, phone_number) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssss", $fullName, $licenseNumber, $email, $address, $messengerName, $phoneNumber);
            if (mysqli_stmt_execute($stmt)) {
                $customer_id = mysqli_insert_id($conn);
                error_log("[KRM DEBUG] New customer created. ID: $customer_id");
                set_message("Customer profile created for " . htmlspecialchars($fullName), 'success');
            } else {
                $err = mysqli_error($conn);
                if (str_contains($err, "Duplicate entry") && str_contains($err, "email")) {
                    set_message("Email already exists. Please use another email.", 'error');
                } elseif (str_contains($err, "Duplicate entry") && str_contains($err, "driver_license_number")) {
                    set_message("License number already exists. Please use another.", 'error');
                } else {
                    set_message("Error inserting customer: $err", 'error');
                }
                error_log("[KRM ERROR] Customer insert failed: $err");
                header("Location: index.php?tab=reservation");
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }

    // ✅ Check if car is already rented
    $sql = "SELECT status FROM cars WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $rentalCarId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $car = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if ($car && $car['status'] !== 'available') {
            set_message('This car is currently unavailable. Please choose another.', 'error');
            header("Location: index.php?tab=reservation");
            exit();
        }
    }

    // ✅ Insert reservation
    $sql = "INSERT INTO reservations (customer_id, car_id, pickup_date, return_date, pickup_location, passenger_count, accommodations, special_requests, estimated_price, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iisssissss", $customer_id, $rentalCarId, $pickupDate, $returnDate, $pickupLocation, $passengerCount, $accommodations, $specialRequests, $estimatedPrice, $status);
        if (mysqli_stmt_execute($stmt)) {
            $reservation_id = mysqli_insert_id($conn);
            error_log("[KRM DEBUG] Reservation inserted. ID: $reservation_id");

            // ✅ Update car status to "rented"
            $update_sql = "UPDATE cars SET status = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            if ($update_stmt) {
                mysqli_stmt_bind_param($update_stmt, "si", $carStatus, $rentalCarId);
                mysqli_stmt_execute($update_stmt);
                mysqli_stmt_close($update_stmt);
                error_log("[KRM DEBUG] Car ID $rentalCarId status updated to '$carStatus'");
            }

            $msg = $customer_found
                ? "Reservation submitted for existing customer $fullName. Reservation ID: $reservation_id"
                : "Reservation successful! Reservation ID: $reservation_id";
            set_message($msg, 'success');

            header("Location: index.php?tab=reservation");
            exit();
        } else {
            set_message("Error inserting reservation: " . mysqli_error($conn), 'error');
            error_log("[KRM ERROR] Reservation insert failed: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);
    } else {
        set_message("Error preparing reservation insert.", 'error');
        error_log("[KRM ERROR] Reservation insert prepare failed: " . mysqli_error($conn));
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
    <link rel="stylesheet" href="../css/bootstrap.min.css">
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


        /* Rentals tab overlay */

        .card {
            position: relative;
            overflow: hidden;
            border: none;
            background: #f9f9f9;
            cursor: pointer;
        }

        .unavailable-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.6);
            color: white;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
            z-index: 2;
        }

        .card:disabled {
            pointer-events: none;
            opacity: 0.7;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="logo">KRM RENT A CAR SERVICES PORTAL</div>
        <div class="header-buttons">
            <button class="btn btn-danger" name="logout" onclick="log_out()">LOG OUT</button>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="nav-tabs" id="navBar">
        <button class="nav-tab active" onclick="showTab('home')">HOME</button>
        <button class="nav-tab" onclick="showTab('rentals')">AVAILABLE RENTALS</button>
        <button class="nav-tab" onclick="showTab('feedback')">FEEDBACK &amp; RATING</button>
        <button class="nav-tab" onclick="showTab('profile')">PROFILE</button>
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
                        <button class="card" onclick="showCarDetails(<?php echo $car['id']; ?>)" <?php echo ($car['status'] !== 'available') ? 'disabled' : ''; ?>>
                            <img class="car-image" src="../uploads/<?php echo htmlspecialchars($car['image_path']); ?>" alt="<?php echo $car['make'] . ' ' . $car['model']; ?>">

                            <!-- Unavailable overlay -->
                            <?php if ($car['status'] !== 'available'): ?>
                                <div class="unavailable-overlay">Unavailable</div>
                            <?php endif; ?>

                            <div class="car-info">
                                <?php echo htmlspecialchars($car['make']); ?><br>
                                <?php echo htmlspecialchars($car['model']) . ' ' . htmlspecialchars($car['year']); ?>
                            </div>
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
                        <select id="rentalCar" name="rentalCar" class="form-input" onchange="updatePassengerLimit()">
                            <option value="" disabled selected>--Please select--</option>
                            <?php
                            foreach ($cars_data as $car) {
                                $disabled = ($car['status'] !== 'available') ? 'disabled' : '';
                                echo "<option value='{$car['id']}' data-seating='{$car['seating_capacity']}' $disabled>";
                                echo $car['make'] . " " . $car['model'] . " " . $car['year'] . (($car['status'] !== 'available') ? ' (Unavailable)' : '');
                                echo "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <div class="form-row">
                            <label class="form-label" for="pickupDate">Pickup Date</label>
                            <input type="date" id="pickupDate" name="pickupDate" class="form-control" required>

                            <label class="form-label" for="returnDate">Return Date</label>
                            <input type="date" id="returnDate" name="returnDate" class="form-control" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="pickupLocation">Pickup Location</label>
                        <input type="text" id="pickupLocation" name="pickupLocation" class="form-input" placeholder="e.g. Rosario, Cavite" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="passengerCount">Passenger Count</label>
                        <input type="number" id="passengerCount" name="passengerCount" class="form-control mt-2" min="1" placeholder="Enter number of passengers">
                        <small id="capacityNote" class="text-muted"></small>
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
                        <label for="estimatedPriceDisplay">Estimated Price</label>
                        <input type="text" id="priceDisplay" class="form-control" readonly>
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

        <?php
        $userId = $_SESSION["customer_id"];

        $sql_customer_data = "SELECT * FROM customers WHERE id = ?";
        $stmt_customer_data = mysqli_prepare($conn, $sql_customer_data);

        if ($stmt_customer_data) {
            mysqli_stmt_bind_param($stmt_customer_data, "i", $userId);
            mysqli_stmt_execute($stmt_customer_data);
            $result_fetch_customer = mysqli_stmt_get_result($stmt_customer_data);

            if ($result_fetch_customer && mysqli_num_rows($result_fetch_customer) > 0) {
                $customer_data = mysqli_fetch_assoc($result_fetch_customer);
                $customer_name = htmlspecialchars($customer_data['name']);
                $customer_email = htmlspecialchars($customer_data['email']);
                $customer_phone = htmlspecialchars($customer_data['phone_number']);
                $customer_address = htmlspecialchars($customer_data['address']);
                $customer_license = htmlspecialchars($customer_data['driver_license_number']);
                $customer_messenger = htmlspecialchars($customer_data['messenger_name']);

                $customer_profile_photo_display = !empty($customer_data['profile_photo_path'])
                    ? "../uploads/" . htmlspecialchars($customer_data['profile_photo_path'])
                    : "";

                $customer_license_image_display = !empty($customer_data['license_image_path'])
                    ? "../uploads/" . htmlspecialchars($customer_data['license_image_path'])
                    : "";
            }
            mysqli_stmt_close($stmt_customer_data);
        }
        ?>

        <div id="profile-tab" class="tab-content hidden">
            <section class="vh-100 gradient-custom">
                <div class="container py-5 h-100">
                    <div class="row justify-content-center align-items-center h-100">
                        <div class="col-12 col-lg-9 col-xl-7">
                            <div class="card shadow-2-strong card-registration" style="border-radius: 15px;">
                                <div class="card-body p-4 p-md-5">
                                    <h3 class="mb-4 pb-2 pb-md-0 mb-md-5 text-center">Update Profile</h3>
                                    <!-- Alert Banner -->
                                    <?php if (isset($_COOKIE['err_message'])): ?>
                                        <div class="alert <?= htmlspecialchars($_COOKIE['message_class']) ?> alert-dismissible fade show" role="alert">
                                            <?= htmlspecialchars($_COOKIE['err_message']) ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                        </div>
                                    <?php endif; ?>
                                    <!-- End Alert Banner -->
                                    <form action="update_customer_profile.php" method="POST" enctype="multipart/form-data">

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <input type="text" id="nameInput" name="name" class="form-control form-control-sm"
                                                        value="<?php echo $customer_name; ?>" required />
                                                    <label class="form-label" for="nameInput">Full Name</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <input type="email" id="emailInput" name="email" class="form-control form-control-sm"
                                                        value="<?php echo $customer_email; ?>" required />
                                                    <label class="form-label" for="emailInput">Email</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <input type="tel" id="phoneInput" name="phone" class="form-control form-control-sm"
                                                        value="<?php echo $customer_phone; ?>" required />
                                                    <label class="form-label" for="phoneInput">Phone Number</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <input type="text" id="messenger_name" name="messenger_name" class="form-control form-control-sm"
                                                        value="<?php echo $customer_messenger; ?>" required />
                                                    <label class="form-label" for="messenger_name">Messenger/Facebook Name</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-12 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <textarea id="address" name="address" class="form-control form-control-sm" rows="3" required><?php echo $customer_address; ?></textarea>
                                                    <label class="form-label" for="address">Address</label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <input type="text" id="license" name="license" class="form-control form-control-sm"
                                                        value="<?php echo $customer_license; ?>" required />
                                                    <label class="form-label" for="license">Driver's License Number</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div data-mdb-input-init class="form-outline">
                                                    <input type="password" id="password" name="password" class="form-control form-control-sm" />
                                                    <label class="form-label" for="password">New Password</label>
                                                    <small class="form-text text-muted fst-italic">Leave blank if you do not want to change your password.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <div class="form-outline">
                                                    <label class="form-label d-block" for="profilePhotoInputModal">Profile Photo</label>
                                                    <input class="form-control form-control-sm" type="file" id="profilePhotoInputModal" name="profilePhotoInputModal" accept="image/*" />
                                                    <small class="form-text text-muted fst-italic">Leave blank to keep current photo.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <div class="form-outline">
                                                    <label class="form-label d-block" for="licenseInputModal">Driver's License Image</label>
                                                    <input class="form-control form-control-sm" type="file" id="licenseInputModal" name="licenseInputModal" accept="image/*" />
                                                    <small class="form-text text-muted fst-italic">Leave blank to keep current image.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-4 pt-2 text-center">
                                            <input data-mdb-ripple-init class="btn btn-primary btn-lg" type="submit" value="Update Profile" />
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

    </main>

    <script src="../js/bootstrap.bundle.min.js"></script>
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

        // Check for the maximum capacity available for the selecter car in reservation modal
        function updatePassengerLimit() {
            const carSelect = document.getElementById('rentalCar');
            const passengerCount = document.getElementById('passengerCount');
            const capacityNote = document.getElementById('capacityNote');

            const selectedOption = carSelect.options[carSelect.selectedIndex];
            const seatingCapacity = selectedOption.getAttribute('data-seating');

            if (seatingCapacity) {
                passengerCount.max = seatingCapacity;
                passengerCount.value = ""; // Clear current input
                passengerCount.placeholder = `Max ${seatingCapacity} passengers`;
                capacityNote.textContent = `Maximum capacity: ${seatingCapacity} passengers`;
            } else {
                passengerCount.removeAttribute('max');
            }
        }

        //Prevent manual typing over max limit
        passengerCount.addEventListener('input', () => {
            if (parseInt(passengerCount.value) > parseInt(passengerCount.max)) {
                passengerCount.value = passengerCount.max;
            }
        });

        // Disable past dates for date picker
        window.addEventListener('DOMContentLoaded', () => {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('pickupDate').setAttribute('min', today);
            document.getElementById('returnDate').setAttribute('min', today);
        });

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

        function addFeedback() {
            document.getElementById("feedback-modal").classList.add("active");
        }

        function closeModal() {
            document.getElementById("reserve-modal").classList.remove("active");
            document.getElementById("feedback-modal").classList.remove("active");
            document.getElementById("car-modal").classList.remove("active");
        }

        function log_out() {
            window.location.href = "?logout=true";
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

        // Event listener for estimated price calculation
        document.addEventListener('DOMContentLoaded', function() {
            const rentalCarSelect = document.getElementById('rentalCar');
            const pickupDateInput = document.getElementById('pickupDate');
            const returnDateInput = document.getElementById('returnDate');
            const passengerInput = document.getElementById('passengerCount');
            const estimatedPriceInput = document.createElement('input'); // hidden input
            estimatedPriceInput.type = 'hidden';
            estimatedPriceInput.name = 'estimatedPrice';
            estimatedPriceInput.id = 'estimatedPrice';
            document.getElementById('reservationForm').appendChild(estimatedPriceInput);

            const priceDisplay = document.getElementById('priceDisplay');
            const capacityNote = document.getElementById('capacityNote');

            const carsData = <?php echo json_encode($cars_data); ?>;

            function updatePassengerLimit() {
                const carId = rentalCarSelect.value;
                const selectedCar = carsData.find(car => car.id == carId);
                if (selectedCar) {
                    const capacity = parseInt(selectedCar.seating_capacity);
                    passengerInput.max = capacity;
                    capacityNote.textContent = `Maximum capacity: ${capacity} passengers`;
                } else {
                    passengerInput.removeAttribute('max');
                    capacityNote.textContent = '';
                }
                calculateEstimatedPrice();
            }

            function calculateEstimatedPrice() {
                const pickupDate = new Date(pickupDateInput.value);
                const returnDate = new Date(returnDateInput.value);
                const carId = rentalCarSelect.value;
                const passengers = parseInt(passengerInput.value);
                const selectedCar = carsData.find(car => car.id == carId);

                // Reset values if not ready
                if (!selectedCar || !pickupDate || !returnDate || isNaN(passengers) || passengers <= 0) {
                    estimatedPriceInput.value = '';
                    priceDisplay.value = '';
                    return;
                }

                const capacity = parseInt(selectedCar.seating_capacity);
                if (passengers > capacity) {
                    passengerInput.value = capacity;
                }

                if (pickupDate < returnDate) {
                    const days = Math.ceil((returnDate - pickupDate) / (1000 * 60 * 60 * 24));
                    const basePrice = parseFloat(selectedCar.price);

                    if (!isNaN(basePrice)) {
                        const total = basePrice * days;
                        estimatedPriceInput.value = total.toFixed(2);
                        priceDisplay.value = '₱' + total.toLocaleString(undefined, {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        });
                    }
                } else {
                    estimatedPriceInput.value = '';
                    priceDisplay.value = '';
                }
            }

            // Disable past dates
            const today = new Date().toISOString().split("T")[0];
            pickupDateInput.setAttribute("min", today);
            returnDateInput.setAttribute("min", today);

            // Listeners
            rentalCarSelect.addEventListener('change', updatePassengerLimit);
            pickupDateInput.addEventListener('change', calculateEstimatedPrice);
            returnDateInput.addEventListener('change', calculateEstimatedPrice);
            passengerInput.addEventListener('input', calculateEstimatedPrice);
        });
    </script>
</body>

</html>