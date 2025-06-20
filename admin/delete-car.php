<?php
session_start();
include '../conn.php';
include '../check_customer_session.php';

// Function to set a message in session for the parent page to display
function set_parent_message($message, $type = 'success', $redirect_tab = 'vehicles-tab')
{
    $_SESSION['response_message'] = $message;
    $_SESSION['response_type'] = $type;
    header("Location: admin.php#" . $redirect_tab);
    exit();
}

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    set_parent_message('Please log in to access this page.', 'error');
}

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $car_id = mysqli_real_escape_string($conn, $_GET['id']);

    // Optional: Delete associated image file from 'uploads/' directory
    $sql_get_image = "SELECT image_path FROM cars WHERE id = ?";
    $stmt_get_image = mysqli_prepare($conn, $sql_get_image);
    if ($stmt_get_image) {
        mysqli_stmt_bind_param($stmt_get_image, "i", $car_id);
        mysqli_stmt_execute($stmt_get_image);
        $result_image = mysqli_stmt_get_result($stmt_get_image);
        if ($row = mysqli_fetch_assoc($result_image)) {
            $image_to_delete = "../uploads/" . $row['image_path'];
            if (file_exists($image_to_delete) && is_file($image_to_delete)) {
                unlink($image_to_delete); // Delete the actual file
            }
        }
        mysqli_stmt_close($stmt_get_image);
    }

    // Prepare and execute the DELETE query
    $sql_delete_car = "DELETE FROM cars WHERE id = ?";
    $stmt_delete_car = mysqli_prepare($conn, $sql_delete_car);

    if ($stmt_delete_car) {
        mysqli_stmt_bind_param($stmt_delete_car, "i", $car_id);
        if (mysqli_stmt_execute($stmt_delete_car)) {
            set_parent_message('Car deleted successfully!');
        } else {
            set_parent_message('Error deleting car: ' . mysqli_error($conn), 'error');
        }
        mysqli_stmt_close($stmt_delete_car);
    } else {
        set_parent_message('Error preparing delete statement: ' . mysqli_error($conn), 'error');
    }
} else {
    set_parent_message('No car ID provided for deletion.', 'error');
}
