<?php

if (!isset($_SESSION['customer_id'])) {
    // Set cookie-based alert (expires in 5 seconds)
    setcookie('err_message', 'You must log in first to continue.', time() + 5, '/');
    setcookie('message_class', 'alert-danger', time() + 5, '/');

    // Redirect to landing page (adjust path if needed)
    header("Location: ../index.php");
    exit();
}
