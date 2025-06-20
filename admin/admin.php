<?php
session_start();
include '../conn.php';
include '../check_customer_session.php';


$display_admin_message = '';
$admin_message_type = ''; // 'success' or 'error'

// Function to set a message in session for the admin page to display
function set_admin_message($message, $type = 'success')
{
    $_SESSION['admin_response_message'] = $message;
    $_SESSION['admin_response_type'] = $type;
}

// Check for messages from session and clear them after displaying
if (isset($_SESSION['admin_response_message'])) {
    $display_admin_message = $_SESSION['admin_response_message'];
    $admin_message_type = $_SESSION['admin_response_type'] ?? 'success';
    unset($_SESSION['admin_response_message']);
    unset($_SESSION['admin_response_type']);
}

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['logout']) && $_GET['logout'] === 'true') {
    session_destroy();
    // Redirect to index.php and specifically to the 'home' tab
    header("Location: index.php#home-tab");
    exit();
}

// --- PHP for handling admin information updates (Profile Tab) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['name']) && isset($_POST['email'])) {
    $admin_id = $_SESSION['admin_id'] ?? 1; // Use admin ID from session or default to 1
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Fetch current image paths before update to handle cases where no new file is uploaded
    $current_profile_photo_db = '';
    $current_license_image_db = '';
    $sql_fetch_current_images = "SELECT profile_photo_path, license_image_path FROM admins WHERE id = ?";
    $stmt_fetch_current_images = mysqli_prepare($conn, $sql_fetch_current_images);
    if ($stmt_fetch_current_images) {
        mysqli_stmt_bind_param($stmt_fetch_current_images, "i", $admin_id);
        mysqli_stmt_execute($stmt_fetch_current_images);
        $result_current_images = mysqli_stmt_get_result($stmt_fetch_current_images);
        if ($row = mysqli_fetch_assoc($result_current_images)) {
            $current_profile_photo_db = $row['profile_photo_path'];
            $current_license_image_db = $row['license_image_path'];
        }
        mysqli_stmt_close($stmt_fetch_current_images);
    }

    // Handle profile photo upload
    $new_profile_photo_name = $current_profile_photo_db; // Default to current if no new upload
    if (isset($_FILES['profilePhotoInputModal']) && $_FILES['profilePhotoInputModal']['error'] == 0) { // !!! Changed name for modal input
        $target_dir = "../uploads/";
        $image_basename = basename($_FILES["profilePhotoInputModal"]["name"]);
        $unique_filename = uniqid() . "_" . $image_basename; // Prevent name collisions
        $target_file = $target_dir . $unique_filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "avif", "webp");

        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES["profilePhotoInputModal"]["tmp_name"], $target_file)) {
                $new_profile_photo_name = $unique_filename;
            } else {
                set_admin_message('Sorry, there was an error uploading your profile photo.', 'error');
                // Do not exit, continue with other updates if image upload fails
            }
        } else {
            set_admin_message('Sorry, only JPG, JPEG, PNG, GIF, AVIF, & WEBP files are allowed for profile photo.', 'error');
        }
    }

    // Handle driver's license image upload
    $new_license_image_name = $current_license_image_db;
    if (isset($_FILES['licenseInputModal']) && $_FILES['licenseInputModal']['error'] == 0) { // !!! Changed name for modal input
        $target_dir = "../uploads/";
        $image_basename = basename($_FILES["licenseInputModal"]["name"]);
        $unique_filename = uniqid() . "_" . $image_basename; // Prevent name collisions
        $target_file = $target_dir . $unique_filename;
        $imageFileType = strtolower(pathinfo(htmlentities($target_file), PATHINFO_EXTENSION)); // htmlspecialchars added to pathinfo
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "avif", "webp");

        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES["licenseInputModal"]["tmp_name"], $target_file)) {
                $new_license_image_name = $unique_filename;
            } else {
                set_admin_message('Sorry, there was an error uploading your driver\'s license image.', 'error');
                // Do not exit, continue with other updates if image upload fails
            }
        } else {
            set_admin_message('Sorry, only JPG, JPEG, PNG, GIF, AVIF, & WEBP files are allowed for driver\'s license image.', 'error');
        }
    }

    $sql_update_admin = "UPDATE admins SET name=?, email=?, phone=?, location=?, profile_photo_path=?, license_image_path=? WHERE id=?";
    $stmt_update_admin = mysqli_prepare($conn, $sql_update_admin);

    if ($stmt_update_admin) {
        mysqli_stmt_bind_param($stmt_update_admin, "ssssssi", $name, $email, $phone, $location, $new_profile_photo_name, $new_license_image_name, $admin_id);
        if (mysqli_stmt_execute($stmt_update_admin)) {
            set_admin_message('Admin information updated successfully!', 'success');
            // Update session variables if they store admin info (for immediate display)
            $_SESSION['admin_name'] = $name;
            $_SESSION['admin_email'] = $email;
            $_SESSION['admin_phone'] = $phone;
            $_SESSION['admin_location'] = $location;
            $_SESSION['admin_profile_photo_path'] = $new_profile_photo_name;
            $_SESSION['admin_license_image_path'] = $new_license_image_name;
        } else {
            set_admin_message('Error updating admin information: ' . mysqli_error($conn), 'error');
        }
        mysqli_stmt_close($stmt_update_admin);
    } else {
        set_admin_message('Error preparing admin update statement: ' . mysqli_error($conn), 'error');
    }
    // Always redirect back to admin.php (profile tab) after form submission
    header("Location: admin.php#profile-tab");
    exit();
}


// --- PHP for fetching admin information (Profile Tab) ---
$admin_id = $_SESSION['admin_id'] ?? null; // Get admin ID from session
$admin_name = "KRM Rent a Car Services";
$admin_email = "ADMIN@GMAIL.COM";
$admin_phone = "09267369135";
$admin_location = "Rosario, Cavite";

// Initialize display paths with placeholders. These will be overwritten if data exists in DB.
$admin_profile_photo_display = "../uploads/admin_placeholder.png";
$admin_license_image_display = "";

if ($admin_id) { // Only fetch if admin ID is available in session
    $sql_fetch_admin = "SELECT name, email, phone, location, profile_photo_path, license_image_path FROM admins WHERE id = ?";
    $stmt_fetch_admin = mysqli_prepare($conn, $sql_fetch_admin);
    if ($stmt_fetch_admin) {
        mysqli_stmt_bind_param($stmt_fetch_admin, "i", $admin_id);
        mysqli_stmt_execute($stmt_fetch_admin);
        $result_fetch_admin = mysqli_stmt_get_result($stmt_fetch_admin);
        if ($result_fetch_admin && mysqli_num_rows($result_fetch_admin) > 0) {
            $admin_data = mysqli_fetch_assoc($result_fetch_admin);
            $admin_name = htmlspecialchars($admin_data['name']);
            $admin_email = htmlspecialchars($admin_data['email']);
            $admin_phone = htmlspecialchars($admin_data['phone']);
            $admin_location = htmlspecialchars($admin_data['location']);

            // If profile_photo_path exists in DB, use it. Otherwise, keep the default placeholder.
            if (!empty($admin_data['profile_photo_path'])) {
                $admin_profile_photo_display = "../uploads/" . htmlspecialchars($admin_data['profile_photo_path']);
            }
            // If license_image_path exists in DB, use it. Otherwise, keep the default empty string.
            if (!empty($admin_data['license_image_path'])) {
                $admin_license_image_display = "../uploads/" . htmlspecialchars($admin_data['license_image_path']);
            }
            // Update session for immediate display in profile tab
            $_SESSION['admin_profile_photo_path'] = $admin_data['profile_photo_path'];
            $_SESSION['admin_license_image_path'] = $admin_data['license_image_path'];
        }
        mysqli_stmt_close($stmt_fetch_admin);
    }
}


// --- PHP for fetching car status (Dashboard Tab) ---
$car_status_data = [];
// Updated SQL query to connect car status to reservation status
$sql_car_status = "
        SELECT
            c.id AS car_id,
            c.model,
            c.make,
            COALESCE(r_latest.status, 'available') AS status, -- Use latest active reservation status, or 'available'
            cust.name AS customer_name
        FROM
            cars c
        LEFT JOIN (
            SELECT
                res.car_id,
                res.customer_id,
                res.status,
                res.created_at
            FROM
                reservations res
            INNER JOIN (
                SELECT
                    car_id,
                    MAX(created_at) AS max_created_at
                FROM
                    reservations
                WHERE
                    -- IMPORTANT: Included 'completed' and 'cancelled' to show their status on the dashboard
                    -- If a car's latest reservation is completed or cancelled, it will now show that status.
                    -- If a car has no reservations or its latest is one of these, it will default to 'available'
                    -- via COALESCE if this WHERE clause only included active states.
                    -- By including them here, we ensure that if the *most recent* status is completed/cancelled, it shows.
                    status IN ('pending', 'reserved', 'in-route', 'completed', 'cancelled')
                GROUP BY
                    car_id
            ) AS latest_active ON res.car_id = latest_active.car_id AND res.created_at = latest_active.max_created_at
        ) AS r_latest ON c.id = r_latest.car_id
        LEFT JOIN
            customers cust ON r_latest.customer_id = cust.id
        ORDER BY
            c.id ASC;
    ";
$result_car_status = mysqli_query($conn, $sql_car_status);
if ($result_car_status) {
    while ($row = mysqli_fetch_assoc($result_car_status)) {
        // Ensure customer_name is 'N/A' if no active reservation or customer
        $customer_name_display = $row['customer_name'] ? htmlspecialchars($row['customer_name']) : 'N/A';
        $car_status_data[] = [
            'id' => $row['car_id'],
            'model' => htmlspecialchars($row['model']),
            'make' => htmlspecialchars($row['make']),
            'status' => htmlspecialchars($row['status']),
            'customer_name' => $customer_name_display
        ];
    }
} else {
    error_log('Error fetching car status: ' . mysqli_error($conn));
}

// --- PHP for fetching recent rents (Dashboard Tab Sidebar) ---
$recent_rents_data = [];
$sql_recent_rents = "SELECT make, model FROM cars ORDER BY id DESC"; // Using cars table for now
$result_recent_rents = mysqli_query($conn, $sql_recent_rents);
if ($result_recent_rents) {
    while ($row = mysqli_fetch_assoc($result_recent_rents)) {
        $recent_rents_data[] = [
            'make' => htmlspecialchars($row['make']),
            'model' => htmlspecialchars($row['model'])
        ];
    }
} else {
    error_log('Error fetching recent rents: ' . mysqli_error($conn));
}

// --- PHP for Reservation Counts (Reports Tab) ---
$reserved_count = 0;
$pending_count = 0;
$completed_count = 0;
$total_reservation_count = 0;

$sql_counts = "SELECT
        COUNT(CASE WHEN status = 'reserved' THEN 1 END) AS reserved,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) AS completed,
        COUNT(*) AS total
    FROM reservations";
$result_counts = mysqli_query($conn, $sql_counts);
if ($result_counts && mysqli_num_rows($result_counts) > 0) {
    $counts = mysqli_fetch_assoc($result_counts);
    $reserved_count = $counts['reserved'];
    $pending_count = $counts['pending'];
    $completed_count = $counts['completed'];
    $total_reservation_count = $counts['total'];
} else {
    error_log('Error fetching reservation counts: ' . mysqli_error($conn));
}

// --- PHP for Detailed Reservation Reports (Reports Tab Table) ---
$detailed_reservations = [];
$sql_detailed_reservations = "
        SELECT
            r.id AS reservation_id,
            c.name AS customer_name,
            c.phone_number AS customer_phone,
            ca.make AS car_make,
            ca.model AS car_model,
            ca.year AS car_year,
            r.pickup_date,
            r.return_date,
            r.pickup_location,
            r.passenger_count,
            r.accommodations,
            r.special_requests,
            r.estimated_price,
            r.status
        FROM reservations r
        JOIN customers c ON r.customer_id = c.id
        JOIN cars ca ON r.car_id = ca.id
        ORDER BY r.created_at DESC;
    ";
$result_detailed_reservations = mysqli_query($conn, $sql_detailed_reservations);

if ($result_detailed_reservations) {
    while ($row = mysqli_fetch_assoc($result_detailed_reservations)) {
        $detailed_reservations[] = $row;
    }
} else {
    error_log('Error fetching detailed reservations: ' . mysqli_error($conn));
}


// --- PHP for Vehicle listings (Vehicles Tab) ---
$vehicles_data = [];
$sql_vehicles = "SELECT * FROM cars";
$result_vehicles = mysqli_query($conn, $sql_vehicles);
if ($result_vehicles) {
    while ($row = mysqli_fetch_assoc($result_vehicles)) {
        $vehicles_data[] = [
            'id' => $row['id'],
            'make' => htmlspecialchars($row['make']),
            'model' => htmlspecialchars($row['model']),
            'year' => htmlspecialchars($row['year']),
            'image_path' => htmlspecialchars($row['image_path']),
            'status' => htmlspecialchars($row['status']),
        ];
    }
} else {
    error_log('Error fetching vehicles: ' . mysqli_error($conn));
}

// --- PHP for Customer Data (Customer Tab) ---
$customer_data = [];
$sql_customers = "
        SELECT
            c.id,
            c.name,
            c.driver_license_number,
            c.address,
            c.messenger_name,
            c.phone_number,
            GROUP_CONCAT(DISTINCT CONCAT(ca.make, ' ', ca.model) SEPARATOR '; ') AS rental_cars_summary,
            GROUP_CONCAT(CONCAT(r.pickup_date, ' to ', r.return_date) SEPARATOR '; ') AS rental_dates_summary
        FROM customers c
        LEFT JOIN reservations r ON c.id = r.customer_id
        LEFT JOIN cars ca ON r.car_id = ca.id
        GROUP BY c.id
        ORDER BY c.id DESC, rental_dates_summary DESC;
    ";
$result_customers = mysqli_query($conn, $sql_customers);

if ($result_customers) {
    while ($row = mysqli_fetch_assoc($result_customers)) {
        $customer_data[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name']),
            'driver_license_number' => htmlspecialchars($row['driver_license_number']),
            'address' => htmlspecialchars($row['address']),
            'messenger_name' => htmlspecialchars($row['messenger_name']),
            'phone_number' => htmlspecialchars($row['phone_number']),
            'rental_cars' => htmlspecialchars($row['rental_cars_summary'] ?? 'N/A'),
            'rental_dates' => htmlspecialchars($row['rental_dates_summary'] ?? 'N/A'),
            'pickup_dates' => 'N/A', // Placeholder
            'return_dates' => 'N/A', // Placeholder
            'passenger_counts' => 'N/A', // Placeholder
            'accommodations' => 'N/A', // Placeholder
            'special_requests' => 'N/A' // Placeholder
        ];
    }
} else {
    error_log('Error fetching customer data: ' . mysqli_error($conn));
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin panel - KRM Rent A Car Services Portal</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        /* Styles for the dropdown action menu */
        .action-dropdown-container {
            position: relative;
            display: inline-block;
            /* Allows side-by-side or block based on parent */
        }

        .action-dropdown-button {
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: 4px 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            line-height: 1;
            text-align: center;
            width: 30px;
            height: 30px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #555;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            /* Subtle shadow */
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .action-dropdown-button:hover {
            background-color: #e0e0e0;
            border-color: #aaa;
        }

        .action-dropdown-menu {
            display: none;
            /* Hidden by default */
            position: absolute;
            background-color: white;
            min-width: 160px;
            max-height: 150px;
            box-shadow: 0px 8px 16px 0px rgba(0, 0, 0, 0.2);
            z-index: 1;
            /* Ensure it appears above other content */
            border-radius: 4px;
            overflow: auto;
            /* Ensures rounded corners are applied to children */
            top: 100%;
            /* Position below the button */
            left: 50%;
            /* Center horizontally with the button */
            transform: translateX(-50%);
            /* Adjust to truly center */
            margin-top: 5px;
            /* Small gap below button */
            border: 1px solid #ddd;
        }

        .action-dropdown-menu.show {
            display: block;
            /* Show when 'show' class is added */
            overflow: auto;
        }

        .action-dropdown-menu button {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            display: block;
            border: none;
            /* Remove default button border */
            background: none;
            /* Remove default button background */
            width: 100%;
            /* Full width within the dropdown */
            text-align: left;
            cursor: pointer;
            transition: background-color 0.1s ease;
            font-size: 14px;
        }

        .action-dropdown-menu button:hover {
            background-color: #f2f2f2;
        }

        /* Specific button styling within dropdown for consistency with original design */
        .action-dropdown-menu .btn-edit,
        .action-dropdown-menu .btn-complete,
        .action-dropdown-menu .btn-cancel {
            /* Override existing button styles if necessary, or let them apply */
            /* Ensure these look like simple list items in the dropdown */
            background-color: transparent !important;
            /* Force transparent background */
            color: #333 !important;
            /* Default text color */
            border: none !important;
            /* No borders */
            padding: 10px 15px !important;
            text-align: left !important;
            font-weight: normal !important;
            border-radius: 0 !important;
            /* No rounded corners for individual items */
        }

        .action-dropdown-menu .btn-edit:hover,
        .action-dropdown-menu .btn-complete:hover,
        .action-dropdown-menu .btn-cancel:hover {
            background-color: #e9e9e9 !important;
            /* Subtle hover effect */
        }

        /* Styles for the custom message box */
        .admin-message-box {
            padding: 15px 20px;
            margin: 20px auto;
            /* Center the message box */
            border-radius: 8px;
            font-size: 14px;
            display: none;
            /* Hidden by default */
            justify-content: space-between;
            align-items: center;
            max-width: 1120px;
            /* Match main content width */
        }

        .admin-message-box.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .admin-message-box.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .admin-message-box .close-message {
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
            <button class="btn btn-primary" name="logout" onclick="log_out()">LOG OUT</button>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <nav class="nav-tabs" id="navBar">
        <button class="nav-tab active" onclick="showTab('dashboard')">DASHBOARD</button>
        <button class="nav-tab" onclick="showTab('reservation')">REPORTS</button>
        <button class="nav-tab" onclick="showTab('vehicles')">VEHICLES</button>
        <button class="nav-tab" onclick="showTab('customer')">CUSTOMER</button>
        <button class="nav-tab" onclick="showTab('profile')">PROFILE</button>
    </nav>

    <!-- Main Content -->
    <main class="main-content" id="main-content">
        <!-- Admin Custom Message Box -->
        <div id="adminMessageBox" class="admin-message-box <?php echo $admin_message_type; ?>" style="display: <?php echo !empty($display_admin_message) ? 'flex' : 'none'; ?>;">
            <span><?php echo $display_admin_message; ?></span>
            <span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span>
        </div>

        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content active">
            <div class="hero-section">
                <div class="hero-title">KRM SERVICE</div>
                <div class="hero-subtitle">Caring for cars since you were born</div>
            </div>
            <div class="dashboard-grid">
                <div class="card">
                    <div class="card-header">CAR STATUS</div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>MAKE</th>
                                    <th>MODEL</th>
                                    <th>CUSTOMER NAME</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($car_status_data)): ?>
                                    <tr>
                                        <td colspan="5">No car status data available.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($car_status_data as $car): ?>
                                        <tr>
                                            <td><?php echo $car['id']; ?></td>
                                            <td><?php echo $car['make']; ?></td>
                                            <td><?php echo $car['model']; ?></td>
                                            <td><?php echo $car['customer_name'] ?></td>
                                            <td>
                                                <?php
                                                $status_class = '';
                                                switch (strtolower($car['status'])) {
                                                    case 'completed':
                                                        $status_class = 'status-completed';
                                                        break;
                                                    case 'in-route':
                                                        $status_class = 'status-in-route';
                                                        break;
                                                    case 'pending':
                                                        $status_class = 'status-pending';
                                                        break;
                                                    case 'reserved':
                                                        $status_class = 'status-reserved';
                                                        break;
                                                    case 'cancelled': // Added this case for dashboard display
                                                        $status_class = 'status-cancelled';
                                                        break;
                                                    case 'available':
                                                        $status_class = 'status-available';
                                                        break;
                                                    default:
                                                        $status_class = 'status-available';
                                                        break;
                                                }
                                                echo '<span class="status-badge ' . $status_class . '">' . strtoupper($car['status']) . '</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="sidebar">
                    <div class="sidebar-section">
                        <div class="sidebar-title">RECENT RENT:</div>
                        <div class="sidebar-content">
                            <?php if (empty($recent_rents_data)): ?>
                                <div class="recent-item">No recent rents to display.</div>
                            <?php else: ?>
                                <?php foreach ($recent_rents_data as $rent): ?>
                                    <div class="recent-item">
                                        <div class="car-icon">🚗</div>
                                        <div>
                                            <div style="font-weight: bold"><?php echo $rent['make']; ?></div>
                                            <div style="font-size: 12px; color: #999"><?php echo $rent['model']; ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reservation-tab" class="tab-content hidden">
            <div class="summary-cards">
                <div class="summary-card">
                    <div class="summary-title">PENDING</div>
                    <div class="summary-count">(<?php echo $pending_count; ?>)</div>
                </div>
                <div class="summary-card">
                    <div class="summary-title">COMPLETED</div>
                    <div class="summary-count">(<?php echo $completed_count; ?>)</div>
                </div>
                <div class="summary-card">
                    <div class="summary-title">RESERVED</div>
                    <div class="summary-count">(<?php echo $reserved_count; ?>)</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">TOTAL RESERVATION: (<?php echo $total_reservation_count; ?>)</div>
            </div>

            <div class="card">
                <div class="card-header">Reservation Reports & Analytics</div>
                <div class="card-body">
                    <?php if (empty($detailed_reservations)): ?>
                        <p>No reservation data available.</p>
                    <?php else: ?>
                        <table class="table full-width-table">
                            <thead>
                                <tr>
                                    <th>Res. ID</th>
                                    <th>Customer Name</th>
                                    <th>Customer Phone</th>
                                    <th>Car</th>
                                    <th>Pickup Date</th>
                                    <th>Return Date</th>
                                    <th>Pickup Location</th>
                                    <th>Passengers</th>
                                    <th>Accommodations</th>
                                    <th>Special Requests</th>
                                    <th>Est. Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detailed_reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reservation['reservation_id']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['customer_phone']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['car_make'] . ' ' . $reservation['car_model'] . ' (' . $reservation['car_year'] . ')'); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['pickup_date']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['return_date']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['pickup_location']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['passenger_count']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['accommodations']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['special_requests']); ?></td>
                                        <td><?php echo htmlspecialchars(number_format($reservation['estimated_price'], 2)); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch (strtolower($reservation['status'])) {
                                                case 'completed':
                                                    $status_class = 'status-completed';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'status-pending';
                                                    break;
                                                case 'reserved':
                                                    $status_class = 'status-reserved';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'status-cancelled';
                                                    break;
                                                case 'in-route':
                                                    $status_class = 'status-in-route';
                                                    break;
                                                default:
                                                    $status_class = '';
                                                    break;
                                            }
                                            echo '<span class="status-badge ' . $status_class . '">' . strtoupper($reservation['status']) . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-dropdown-container">
                                                <button class="action-dropdown-button" onclick="toggleDropdown(this, event)">...</button>
                                                <div class="action-dropdown-menu">
                                                    <button class="btn btn-sm btn-reserve" onclick="changeReservationStatus(<?php echo $reservation['reservation_id']; ?>, 'reserved')">Reserved</button>
                                                    <button class="btn btn-sm btn-in-route" onclick="changeReservationStatus(<?php echo $reservation['reservation_id']; ?>, 'in-route')">In route</button>
                                                    <button class="btn btn-sm btn-complete" onclick="changeReservationStatus(<?php echo $reservation['reservation_id']; ?>, 'completed')">Complete</button>
                                                    <button class="btn btn-sm btn-cancel" onclick="changeReservationStatus(<?php echo $reservation['reservation_id']; ?>, 'cancelled')">Cancel</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Vehicles Tab -->
        <div id="vehicles-tab" class="tab-content hidden">
            <div class="card">
                <div class="card-header">Vehicle settings
                    <button class="btn add" onclick="addVehicle()">Add vehicle</button>
                </div>
                <div class="card-body">
                    <?php if (empty($vehicles_data)): ?>
                        <p>No vehicles available. Add a new vehicle.</p>
                    <?php else: ?>
                        <?php foreach ($vehicles_data as $vehicle): ?>
                            <div class="vehicle-card">
                                <div class="card" style="flex: 1">
                                    <div class="vehicles-tab">
                                        <img class="vehicle-settings-image" src="../uploads/<?php echo htmlspecialchars($vehicle['image_path']); ?>" alt="<?php echo $vehicle['make'] . ' ' . $vehicle['model']; ?>">
                                        <div>
                                            <div class="vehicle-make"><?php echo $vehicle['make']; ?></div>
                                            <div class="vehicle-model-year"><?php echo $vehicle['model'] . ' ' . $vehicle['year']; ?></div>
                                            <div class="vehicle-status"><span class=" text-mute">Status: <?php echo $vehicle['status']; ?></span></div>
                                        </div>
                                        <div class="vehicle-settings">
                                            <div class="vehicle-settings-btns">
                                                <button class="btn btn-primary" onclick="updateVehicle(<?php echo $vehicle['id']; ?>)">Update</button>
                                                <button class="btn btn-danger" onclick="deleteVehicle(<?php echo $vehicle['id']; ?>)">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Update Car Modal -->
        <div class="modal" id="update-modal">
            <div class="modal-content">
                <div class="modal-title">Update car</div>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <form action="update-car.php" method="POST" enctype="multipart/form-data">
                <div class="form-type">
                    <label for="make" class="form-label">Make</label>
                    <input type="text" class="form-input" name="make" placeholder="e.g. Mitsubishi" value="<?php echo htmlspecialchars($car_data['make'] ?? ''); ?>" required>
                </div>
                <div class="form-type">
                    <label for="model" class="form-label">Model</label>
                    <input type="text" class="form-input" name="model" placeholder="e.g. Mirage" value="<?php echo htmlspecialchars($car_data['model'] ?? ''); ?>" required>
                </div>
                <div class="form-type">
                    <label for="year" class="form-label">Year</label>
                    <input type="number" class="form-input" name="year" placeholder="e.g. 2023" value="<?php echo htmlspecialchars($car_data['year'] ?? ''); ?>" required>
                </div>
                <div class="form-type">
                    <label for="color" class="form-label">Color</label>
                    <input type="text" class="form-input" name="color" placeholder="e.g. Silver" value="<?php echo htmlspecialchars($car_data['color'] ?? ''); ?>" required />
                </div>
                <div class="form-type">
                    <label for="engine" class="form-label">Engine</label>
                    <input type="text" class="form-input" name="engine" placeholder="e.g. 1.2-liter MIVEC DOHC 3-cylinder" value="<?php echo htmlspecialchars($car_data['engine'] ?? ''); ?>" required />
                </div>
                <div class="form-type">
                    <label for="transmission" class="form-label">Transmission</label>
                    <input type="text" class="form-input" name="transmission" placeholder="e.g. Continuously Variable Transmission (CVT)" value="<?php echo htmlspecialchars($car_data['transmission'] ?? ''); ?>" required />
                </div>
                <div class="form-type">
                    <label for="fuelEconomy" class="form-label">Fuel Economy</label>
                    <input type="text" class="form-input" name="fuelEconomy" placeholder="e.g. 37 combined mpg" value="<?php echo htmlspecialchars($car_data['fuel_economy'] ?? ''); ?>" required />
                </div>
                <div class="form-type">
                    <label for="seatingCapacity" class="form-label">Seating Capacity</label>
                    <input type="text" class="form-input" name="seatingCapacity" placeholder="e.g. 5 passengers" value="<?php echo htmlspecialchars($car_data['seating_capacity'] ?? ''); ?>" required />
                </div>
                <div class="form-type">
                    <label for="safetyFeatures" class="form-label">Safety Features</label>
                    <input type="text" class="form-input" name="safetyFeatures" placeholder="e.g. Anti-lock Braking System, Front SRS airbags, etc." value="<?php echo htmlspecialchars($car_data['safety_features'] ?? ''); ?>" required />
                </div>
                <div class="cont">
                    <label for="addtlFeatures" class="form-label">Additional Features</label>
                    <input type="text" class="form-input" name="addtlFeatures" placeholder="e.g. Cruise control, ECO indicator, Bluetooth wireless technology" value="<?php echo htmlspecialchars($car_data['additional_features'] ?? ''); ?>" required />
                </div>
                <div class="form-type">
                    <label for="price" class="form-label">Car's base price</label>
                    <input type="number" step="0.01" class="form-input" name="price" placeholder="e.g. 1234.00" value="<?php echo htmlspecialchars($car_data['price'] ?? ''); ?>" required>
                </div>
                <div class="cont">
                    <label for="image" class="form-label">Please insert image of the car</label>
                    <?php if (!empty($car_data['image_path'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($car_data['image_path']); ?>" alt="Current Car Image" style="max-width: 150px; margin-top: 10px; display: block;">
                        <small>Current Image</small><br>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-input" accept="image/png, image/jpeg, image/avif, image/webp" />
                    <small>Leave blank to keep current image</small>
                </div>

                <button type="submit" class="submit-btn">Update Car</button>
            </form>
        </div>

        <!-- Customer Tab -->
        <div id="customer-tab" class="tab-content hidden">
            <div class="card">
                <div class="card-header">Customer</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Name</th>
                                <th>Driver's License Number</th>
                                <th>Address</th>
                                <th>Messenger Name</th>
                                <th>Phone Number</th>
                                <th>Rental Cars (Summary)</th>
                                <th>Rental Dates (Summary)</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($customer_data)): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No customer data available.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($customer_data as $customer): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($customer['id']); ?></td>
                                        <td><?= htmlspecialchars($customer['name']); ?></td>
                                        <td><?= htmlspecialchars($customer['driver_license_number']); ?></td>
                                        <td><?= htmlspecialchars($customer['address']); ?></td>
                                        <td><?= htmlspecialchars($customer['messenger_name']); ?></td>
                                        <td><?= htmlspecialchars($customer['phone_number']); ?></td>
                                        <td><?= htmlspecialchars($customer['rental_cars']); ?></td>
                                        <td><?= htmlspecialchars($customer['rental_dates']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $customer['id']; ?>">
                                                Rentals
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php foreach ($customer_data as $customer): ?>
            <div class="modal fade" id="viewModal<?= $customer['id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?= $customer['id']; ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewModalLabel<?= $customer['id']; ?>">
                                Rentals of <?= htmlspecialchars($customer['name']); ?>
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <?php
                            $reservations = [];
                            $customer_id = $customer['id'];
                            $sql = "
              SELECT r.id, r.pickup_date, r.return_date, r.status,
                     CONCAT(c.make, ' ', c.model, ' ', c.year) AS car_model
              FROM reservations r
              JOIN cars c ON r.car_id = c.id
              WHERE r.customer_id = ?
              ORDER BY r.created_at DESC
            ";
                            $stmt = mysqli_prepare($conn, $sql);
                            if ($stmt) {
                                mysqli_stmt_bind_param($stmt, "i", $customer_id);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                while ($row = mysqli_fetch_assoc($result)) {
                                    $reservations[] = [
                                        'id' => $row['id'],
                                        'car_model' => htmlspecialchars($row['car_model']),
                                        'pickup_date' => htmlspecialchars($row['pickup_date']),
                                        'return_date' => htmlspecialchars($row['return_date']),
                                        'status' => htmlspecialchars($row['status']),
                                    ];
                                }
                                mysqli_stmt_close($stmt);
                            } else {
                                echo "<p class='text-danger'>Error loading reservations.</p>";
                            }
                            ?>

                            <?php if (empty($reservations)): ?>
                                <p class="text-muted">No rentals found for this customer.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Car</th>
                                                <th>Pickup Date</th>
                                                <th>Return Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($reservations as $res): ?>
                                                <tr>
                                                    <td><?= $res['id']; ?></td>
                                                    <td><?= $res['car_model']; ?></td>
                                                    <td><?= $res['pickup_date']; ?></td>
                                                    <td><?= $res['return_date']; ?></td>
                                                    <td><?= $res['status']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>



        <!-- Admin Profile Edit -->
        <div class="tab-content hidden" id="profile-tab">
            <div class="profile-section">
                <div class="profile-photo-container">
                    <?php if (!empty($admin_data['profile_photo_path'])): // Check if path exists in DB 
                    ?>
                        <img
                            alt="profile photo"
                            class="profile-photo"
                            height="80"
                            id="profilePhoto"
                            src="<?php echo $admin_profile_photo_display; ?>"
                            width="80" />
                    <?php else: ?>
                        <div class="profile-photo-placeholder" id="profilePhoto">
                            PROFILE PHOTO
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2 id="adminName"><?php echo $admin_name; ?></h2>
                    <p id="adminEmail"><?php echo $admin_email; ?></p>
                    <p id="adminPhone"><?php echo $admin_phone; ?></p>
                    <p id="adminLocation"><?php echo $admin_location; ?></p>
                </div>

                <button aria-label="edit admin information" class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#editAdminModal">
                    Edit
                </button>

            </div>
            <div>
                <div
                    aria-label="Driver's license document image placeholder"
                    class="license-box"
                    id="licenseBox">
                    <?php if (!empty($admin_data['license_image_path'])): // Check if path exists in DB 
                    ?>
                        <img
                            alt="Driver's license document image"
                            class="license-image"
                            id="licenseImage"
                            src="<?php echo $admin_license_image_display; ?>" />
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

            <div
                aria-describedby="popupDesc"
                aria-labelledby="popupTitle"
                class="popup"
                id="editpopup"
                role="dialog">
            </div>

            <!-- Edit Admin Modal -->
            <div class="modal fade" id="editAdminModal" tabindex="-1" aria-labelledby="editAdminModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                    <div class="modal-content">
                        <form id="editForm" method="POST" enctype="multipart/form-data">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editAdminModalLabel">Edit Admin Information</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="nameInput" class="form-label">Name</label>
                                    <input id="nameInput" name="name" type="text" class="form-control" required value="<?php echo htmlspecialchars($admin_name); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="emailInput" class="form-label">Email</label>
                                    <input id="emailInput" name="email" type="email" class="form-control" required value="<?php echo htmlspecialchars($admin_email); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="phoneInput" class="form-label">Phone</label>
                                    <input id="phoneInput" name="phone" type="tel" class="form-control" required value="<?php echo htmlspecialchars($admin_phone); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="locationInput" class="form-label">Location</label>
                                    <input id="locationInput" name="location" type="text" class="form-control" required value="<?php echo htmlspecialchars($admin_location); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="profilePhotoInputModal" class="form-label">Profile Photo</label>
                                    <input type="file" id="profilePhotoInputModal" name="profilePhotoInputModal" class="form-control" accept="image/*">
                                    <small class="form-text text-muted">Leave blank to keep current photo</small>
                                </div>

                                <div class="mb-3">
                                    <label for="licenseInputModal" class="form-label">Driver's License Image</label>
                                    <input type="file" id="licenseInputModal" name="licenseInputModal" class="form-control" accept="image/*">
                                    <small class="form-text text-muted">Leave blank to keep current image</small>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
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

            // Add active class to selected nav tab
            const clickedButton = Array.from(navTabs).find(button => button.getAttribute('onclick') === `showTab('${tabName}')`);
            if (clickedButton) {
                clickedButton.classList.add("active");
            }

            // Save the active tab to localStorage
            localStorage.setItem('admin_last_active_tab', tabName);
        }

        /**
         * Displays a custom message box at the top of the admin content area.
         * @param {string} message The message to display.
         * @param {string} type 'success' or 'error'.
         */
        function showAdminMessageBox(message, type) {
            const messageBox = document.getElementById('adminMessageBox');
            const messageText = messageBox.querySelector('span');
            messageText.textContent = message;
            messageBox.className = 'admin-message-box ' + type; // Reset classes and add type
            messageBox.style.display = 'flex'; // Show the box
            // Automatically hide the message after 5 seconds
            setTimeout(() => {
                messageBox.style.display = 'none';
            }, 5000);
        }

        // Check if there's a PHP message to display on page load for admin
        <?php if (!empty($display_admin_message)): ?>
            document.addEventListener('DOMContentLoaded', function() {
                showAdminMessageBox('<?php echo $display_admin_message; ?>', '<?php echo $admin_message_type; ?>');
            });
        <?php endif; ?>


        function addVehicle() {
            window.location.href = "add-car.php";
        }

        function updateVehicle(carId) {
            window.location.href = 'update-car.php?id=' + carId;
        }

        function deleteVehicle(carId) {
            if (confirm('Are you sure you want to delete this car?')) {
                window.location.href = 'delete-car.php?id=' + carId; // You'll need to create delete-car.php
            }
        }

        // Function to edit a reservation
        function editReservation(reservationId) {
            window.location.href = 'edit-reservation.php?id=' + reservationId;
        }

        // Function to change reservation status (e.g., complete, cancel)
        function changeReservationStatus(reservationId, newStatus) {
            if (confirm('Are you sure you want to set reservation ' + reservationId + ' to ' + newStatus.toUpperCase() + '?')) {
                window.location.href = 'update-reservation-status.php?id=' + reservationId + '&status=' + newStatus;
            }
        }

        // DROPDOWN FUNCTIONALITY
        function toggleDropdown(button, event) {
            // Close any other open dropdowns
            document.querySelectorAll('.action-dropdown-menu.show').forEach(menu => {
                if (menu !== button.nextElementSibling) { // Don't close if it's the current one
                    menu.classList.remove('show');
                }
            });

            // Toggle the current dropdown
            const dropdownMenu = button.nextElementSibling;
            dropdownMenu.classList.toggle('show');
            event.stopPropagation(); // Prevent immediate closing due to global click listener
        }

        // Close dropdowns if clicked outside
        window.onclick = function(event) {
            if (!event.target.matches('.action-dropdown-button')) {
                document.querySelectorAll('.action-dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        }

        function editAdmin() {
            document.getElementById("editPopup").classList.add("active");
            // Populate inputs with current info from the displayed elements
            document.getElementById("nameInput").value = document.getElementById("adminName").textContent;
            document.getElementById("emailInput").value = document.getElementById("adminEmail").textContent;
            document.getElementById("phoneInput").value = document.getElementById("adminPhone").textContent;
            document.getElementById("locationInput").value = document.getElementById("adminLocation").textContent;
            // No need to set value for file inputs as they are read-only for security reasons
        }

        function log_out() {
            window.location.href = "?logout=true";
        }

        function closepopup() {
            document.getElementById("editPopup").classList.remove("active");
            const carModal = document.getElementById("carModal");
            if (carModal) {
                carModal.classList.remove("active");
            }
        }

        // Close modal on Escape key
        window.addEventListener("keydown", (e) => {
            const editPopup = document.getElementById("editPopup");
            if (e.key === "Escape" && editPopup && editPopup.classList.contains("active")) {
                closepopup();
            }
        });

        // Profile photo change handler (for admin profile - client-side preview in modal)
        document.getElementById("profilePhotoInputModal").addEventListener("change", (e) => { // Updated ID here
            const file = e.target.files[0];
            if (file && file.type.startsWith("image/")) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    // Select the main profile photo img element, not the modal one
                    const profilePhotoImgElement = document.querySelector('#profile-tab .profile-section .profile-photo');
                    const profilePhotoPlaceholderElement = document.querySelector('#profile-tab .profile-section .profile-photo-placeholder');

                    if (profilePhotoImgElement) { // If the <img> tag exists
                        profilePhotoImgElement.src = evt.target.result;
                        profilePhotoImgElement.style.display = 'block'; // Ensure it's visible
                    } else if (profilePhotoPlaceholderElement) { // If the placeholder div exists
                        // If currently showing placeholder, replace it with an img element dynamically
                        const newImg = document.createElement('img');
                        newImg.src = evt.target.result;
                        newImg.alt = "profile photo";
                        newImg.className = "profile-photo"; // Add class for styling
                        newImg.height = "80"; // Set height
                        newImg.width = "80"; // Set width
                        profilePhotoPlaceholderElement.parentNode.replaceChild(newImg, profilePhotoPlaceholderElement);
                    }
                };
                reader.readAsDataURL(file);
            }
        });

        // Update nav tab active state - Initial setup
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const isFreshLogin = urlParams.has('fresh_login'); // Checks if the parameter is in the URL
            const lastActiveTab = localStorage.getItem('admin_last_active_tab');

            if (isFreshLogin) {
                // If it's a fresh login (param exists), always show dashboard
                showTab('dashboard');
                // Crucially, remove the 'fresh_login' parameter from URL to prevent reset on subsequent refreshes
                urlParams.delete('fresh_login');
                window.history.replaceState({}, document.title, window.location.pathname + urlParams.toString() + window.location.hash);
            } else if (lastActiveTab) {
                // If not a fresh login and a tab is stored, use it (for refreshes)
                showTab(lastActiveTab);
            } else {
                // Otherwise, default to dashboard (e.g., first ever visit or localStorage cleared manually)
                showTab('dashboard');
            }
        });
    </script>
</body>

</html>