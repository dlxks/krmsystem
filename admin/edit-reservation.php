<?php
session_start();
include '../conn.php';

// Function to set a message in session for the parent page to display
function set_parent_message($message, $type = 'success', $redirect_tab = 'reservation-tab')
{
    $_SESSION['response_message'] = $message;
    $_SESSION['response_type'] = $type;
    echo "<script>window.parent.location.href = 'admin.php#" . $redirect_tab . "';</script>";
    exit();
}

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    set_parent_message('Please log in to access this page.', 'error');
}

$reservation_id = null;
$reservation_data = [];
$customers_data = []; // To populate customer dropdown
$cars_data = [];      // To populate car dropdown

// Fetch all customers for the dropdown
$sql_customers = "SELECT id, name FROM customers ORDER BY name ASC";
$result_customers = mysqli_query($conn, $sql_customers);
if (!$result_customers) {
    set_parent_message('Error fetching customers: ' . mysqli_error($conn), 'error');
} else {
    while ($row = mysqli_fetch_assoc($result_customers)) {
        $customers_data[] = $row;
    }
}

// Fetch all cars for the dropdown
$sql_cars = "SELECT id, make, model, year FROM cars ORDER BY make ASC, model ASC";
$result_cars = mysqli_query($conn, $sql_cars);
if (!$result_cars) {
    set_parent_message('Error fetching cars: ' . mysqli_error($conn), 'error');
} else {
    while ($row = mysqli_fetch_assoc($result_cars)) {
        $cars_data[] = $row;
    }
}


// --- Fetch existing reservation data for pre-filling the form ---
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $reservation_id = mysqli_real_escape_string($conn, $_GET['id']);

    $sql_fetch_reservation = "
        SELECT
            r.id,
            r.customer_id,
            r.car_id,
            r.pickup_date,
            r.return_date,
            r.pickup_location,
            r.passenger_count,
            r.accommodations,
            r.special_requests,
            r.estimated_price,
            r.status
        FROM reservations r
        WHERE r.id = ?
    ";
    $stmt_fetch_reservation = mysqli_prepare($conn, $sql_fetch_reservation);

    if ($stmt_fetch_reservation) {
        mysqli_stmt_bind_param($stmt_fetch_reservation, "i", $reservation_id);
        mysqli_stmt_execute($stmt_fetch_reservation);
        $result_fetch_reservation = mysqli_stmt_get_result($stmt_fetch_reservation);

        if (mysqli_num_rows($result_fetch_reservation) > 0) {
            $reservation_data = mysqli_fetch_assoc($result_fetch_reservation);
        } else {
            set_parent_message('Reservation not found!', 'error');
        }
        mysqli_stmt_close($stmt_fetch_reservation);
    } else {
        set_parent_message('Error preparing fetch reservation statement: ' . mysqli_error($conn), 'error');
    }
} else {
    set_parent_message('No reservation ID provided for editing.', 'error');
}

// --- Handle form submission for updating the reservation ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && $reservation_id !== null) {
    // Collect and sanitize form data
    $customer_id = mysqli_real_escape_string($conn, $_POST['customer_id']);
    $car_id = mysqli_real_escape_string($conn, $_POST['car_id']);
    $pickup_date = mysqli_real_escape_string($conn, $_POST['pickup_date']);
    $return_date = mysqli_real_escape_string($conn, $_POST['return_date']);
    $pickup_location = mysqli_real_escape_string($conn, $_POST['pickup_location']);
    $passenger_count = mysqli_real_escape_string($conn, $_POST['passenger_count']);
    $accommodations = mysqli_real_escape_string($conn, $_POST['accommodations']);
    $special_requests = mysqli_real_escape_string($conn, $_POST['special_requests']);
    $estimated_price = mysqli_real_escape_string($conn, $_POST['estimated_price']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Validate new status against allowed ENUM values
    $allowed_statuses = ['pending', 'reserved', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        set_parent_message('Invalid status provided. Reverting to previous status.', 'error');
        $status = $reservation_data['status']; // Revert to old status if invalid
    }

    $sql_update_reservation = "
        UPDATE reservations
        SET
            customer_id = ?,
            car_id = ?,
            pickup_date = ?,
            return_date = ?,
            pickup_location = ?,
            passenger_count = ?,
            accommodations = ?,
            special_requests = ?,
            estimated_price = ?,
            status = ?
        WHERE id = ?
    ";
    $stmt_update_reservation = mysqli_prepare($conn, $sql_update_reservation);

    if ($stmt_update_reservation) {
        mysqli_stmt_bind_param(
            $stmt_update_reservation,
            "iississsdii", // i for int, s for string, d for double/decimal, last i for reservation_id
            $customer_id,
            $car_id,
            $pickup_date,
            $return_date,
            $pickup_location,
            $passenger_count,
            $accommodations,
            $special_requests,
            $estimated_price,
            $status,
            $reservation_id
        );

        if (mysqli_stmt_execute($stmt_update_reservation)) {
            set_parent_message('Reservation updated successfully!', 'success');
        } else {
            set_parent_message('Error updating reservation: ' . mysqli_error($conn), 'error');
        }
        mysqli_stmt_close($stmt_update_reservation);
    } else {
        set_parent_message('Error preparing update reservation statement: ' . mysqli_error($conn), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Reservation - KRM Rent a Car Services</title>
    <link rel="stylesheet" href="form.css">
    <style>
        /* This style is to remove the "Return to session" link when loaded in iframe */
        .return-section {
            display: none;
        }

        body {
            margin: 0;
            padding: 20px;
            background-color: #f4f7f6;
        }

        .form-container {
            box-shadow: none;
            border: none;
            padding: 0;
        }

        .form-card {
            box-shadow: none;
            border: none;
            padding: 0;
        }


        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .form-container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            /* Adjust max-width as needed */
            box-sizing: border-box;
        }

        .form-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .form-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .form-subtitle {
            font-size: 15px;
            color: #666;
        }

        .form-type,
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #444;
            font-size: 14px;
        }

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            color: #333;
            box-sizing: border-box;
        }

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .form-textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .form-row .form-group {
            flex: 1;
            min-width: 180px;
            /* Ensure fields don't get too small */
        }

        .submit-btn {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
            margin-top: 15px;
        }

        .submit-btn:hover {
            background-color: #0056b3;
        }

        .return-section {
            text-align: center;
            margin-top: 25px;
        }

        .return-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .return-link:hover {
            text-decoration: underline;
        }

        /* Styles for status dropdown */
        .status-option-pending {
            color: orange;
            font-weight: bold;
        }

        .status-option-reserved {
            color: blue;
            font-weight: bold;
        }

        .status-option-completed {
            color: green;
            font-weight: bold;
        }

        .status-option-cancelled {
            color: red;
            font-weight: bold;
        }

        @media (max-width: 480px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .form-row .form-group {
                min-width: unset;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <div class="form-title">Edit Reservation</div>
                <div class="form-subtitle">Modify the reservation details</div>
            </div>

            <form action="" method="POST">
                <div class="form-group">
                    <label for="customer_id" class="form-label">Customer Name</label>
                    <select id="customer_id" name="customer_id" class="form-input" required>
                        <option value="" disabled>Select a customer</option>
                        <?php foreach ($customers_data as $customer): ?>
                            <option value="<?php echo htmlspecialchars($customer['id']); ?>"
                                <?php echo ($customer['id'] == ($reservation_data['customer_id'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($customer['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="car_id" class="form-label">Rental Car</label>
                    <select id="car_id" name="car_id" class="form-input" required>
                        <option value="" disabled>Select a rental car</option>
                        <?php foreach ($cars_data as $car): ?>
                            <option value="<?php echo htmlspecialchars($car['id']); ?>"
                                <?php echo ($car['id'] == ($reservation_data['car_id'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($car['make'] . ' ' . $car['model'] . ' ' . $car['year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group form-row">
                    <div class="form-group">
                        <label for="pickup_date" class="form-label">Pickup Date</label>
                        <input type="date" id="pickup_date" name="pickup_date" class="form-input"
                            value="<?php echo htmlspecialchars($reservation_data['pickup_date'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="return_date" class="form-label">Return Date</label>
                        <input type="date" id="return_date" name="return_date" class="form-input"
                            value="<?php echo htmlspecialchars($reservation_data['return_date'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="pickup_location" class="form-label">Pickup Location</label>
                    <input type="text" id="pickup_location" name="pickup_location" class="form-input"
                        placeholder="e.g. Rosario, Cavite"
                        value="<?php echo htmlspecialchars($reservation_data['pickup_location'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="passenger_count" class="form-label">Passenger Count</label>
                    <input type="number" id="passenger_count" name="passenger_count" class="form-input"
                        placeholder="Enter maximum number of passengers"
                        value="<?php echo htmlspecialchars($reservation_data['passenger_count'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="accommodations" class="form-label">Accommodations</label>
                    <input type="text" id="accommodations" name="accommodations" class="form-input"
                        placeholder="e.g. Novotel Hotel"
                        value="<?php echo htmlspecialchars($reservation_data['accommodations'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="special_requests" class="form-label">Special Requests</label>
                    <textarea id="special_requests" name="special_requests" class="form-input form-textarea"
                        placeholder="Optional (e.g. child seat, GPS, etc., or N/A if none)"><?php echo htmlspecialchars($reservation_data['special_requests'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="estimated_price" class="form-label">Estimated Price</label>
                    <input type="number" step="0.01" id="estimated_price" name="estimated_price" class="form-input"
                        placeholder="Auto-calculated or manually entered"
                        value="<?php echo htmlspecialchars($reservation_data['estimated_price'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status</label>
                    <select id="status" name="status" class="form-input" required>
                        <?php
                        $statuses = ['pending', 'reserved', 'completed', 'cancelled'];
                        foreach ($statuses as $s) {
                            $selected = (($reservation_data['status'] ?? '') == $s) ? 'selected' : '';
                            $class = 'status-option-' . strtolower($s);
                            echo "<option value=\"{$s}\" {$selected} class=\"{$class}\">" . ucfirst($s) . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <button type="submit" class="submit-btn">Update Reservation</button>
            </form>
        </div>
    </div>
</body>

</html>