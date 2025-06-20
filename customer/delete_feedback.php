<?php
session_start();
include '../conn.php';
include '../check_customer_session.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if feedback ID is provided and valid
if (isset($_POST['id']) && is_numeric($_POST['id'])) {
    $feedback_id = (int) $_POST['id'];
    $customer_id = $_SESSION['customer_id'];

    // Make sure the feedback belongs to the logged-in customer
    $check_sql = "SELECT id FROM feedbacks WHERE id = ? AND customer_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "ii", $feedback_id, $customer_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        mysqli_stmt_close($stmt_check);

        // Delete the feedback
        $delete_sql = "DELETE FROM feedbacks WHERE id = ?";
        $stmt_delete = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($stmt_delete, "i", $feedback_id);
        mysqli_stmt_execute($stmt_delete);
        mysqli_stmt_close($stmt_delete);

        $_SESSION['feedback_deleted'] = "Feedback deleted successfully.";
    } else {
        $_SESSION['feedback_error'] = "Unauthorized delete attempt.";
    }
} else {
    $_SESSION['feedback_error'] = "Invalid request.";
}

// Redirect back to dashboard feedback tab
header("Location: index.php#feedback-tab");
exit();
