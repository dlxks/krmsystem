<?php
session_start();
include '../conn.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['status'])) {
    $reservation_id = (int) $_GET['id'];
    $new_status = mysqli_real_escape_string($conn, $_GET['status']);

    $allowed_statuses = ['pending', 'reserved', 'completed', 'cancelled', 'in-route'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo "<script>alert('Invalid status provided.');</script>";
        header("Location: admin.php");
        exit();
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        // Update reservation status
        $update_res_sql = "UPDATE reservations SET status = ? WHERE id = ?";
        $stmt_res = mysqli_prepare($conn, $update_res_sql);
        mysqli_stmt_bind_param($stmt_res, "si", $new_status, $reservation_id);
        mysqli_stmt_execute($stmt_res);
        mysqli_stmt_close($stmt_res);

        // Get car_id related to this reservation
        $car_query = "SELECT car_id FROM reservations WHERE id = ?";
        $stmt_car = mysqli_prepare($conn, $car_query);
        mysqli_stmt_bind_param($stmt_car, "i", $reservation_id);
        mysqli_stmt_execute($stmt_car);
        mysqli_stmt_bind_result($stmt_car, $car_id);
        mysqli_stmt_fetch($stmt_car);
        mysqli_stmt_close($stmt_car);

        // Apply status rules to the car
        if ($car_id) {
            $new_car_status = null;

            if ($new_status === 'completed' || $new_status === 'cancelled') {
                $new_car_status = 'available';
            } elseif ($new_status === 'reserved' || $new_status === 'in-route') {
                $new_car_status = 'rented';
            }

            if ($new_car_status) {
                $update_car_sql = "UPDATE cars SET status = ? WHERE id = ?";
                $stmt_update_car = mysqli_prepare($conn, $update_car_sql);
                mysqli_stmt_bind_param($stmt_update_car, "si", $new_car_status, $car_id);
                mysqli_stmt_execute($stmt_update_car);
                mysqli_stmt_close($stmt_update_car);
            }
        }

        mysqli_commit($conn);
        echo "<script>alert('Reservation status updated to " . strtoupper($new_status) . ".');</script>";
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo "<script>alert('Transaction failed: " . $e->getMessage() . "');</script>";
    }
} else {
    echo "<script>alert('Invalid request.');</script>";
}

header("Location: admin.php#reservation-tab");
exit();
