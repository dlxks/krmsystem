<?php
session_start();
require_once('../conn.php');

$error = ""; // Initialize error message

if (!$conn) {
    $error = "Database connection failed: " . mysqli_connect_error();
} else {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['email']) && isset($_POST['password'])) {
        $input = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password']; // Do not escape password

        // Support login using either email or phone number
        $sql = "SELECT id, name, email, password FROM customers WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $input);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);

                if (password_verify($password, $user['password'])) {
                    $_SESSION["customer_logged_in"] = true;
                    $_SESSION["customer_id"] = $user['id'];
                    $_SESSION["customer_name"] = $user['name'];
                    $_SESSION["customer_email"] = $user['email'];

                    header("Location: ../customer/index.php?fresh_login=true");
                    exit();
                } else {
                    $error = "Incorrect password.";
                }
            } else {
                $error = "No account found with that email.";
            }

            mysqli_stmt_close($stmt);
        } else {
            $error = "Database error: " . mysqli_error($conn);
        }
    } else {
        $error = "Please fill in both email/phone and password.";
    }
}

// Optionally handle error via session or redirect
if (!empty($error)) {
    setcookie('err_message', $error, time() + 15, '/');
    setcookie('message_class', 'alert-danger', time() + 15, '/');
    header("Location: ../login.php");
    exit();
}
