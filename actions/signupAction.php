<?php
session_start();
require_once("../conn.php");

// Sanitize input
$fullname        = mysqli_real_escape_string($conn, $_POST["fullname"]);
$address         = mysqli_real_escape_string($conn, $_POST["address"]);
$emailAddress    = mysqli_real_escape_string($conn, $_POST["emailAddress"]);
$phoneNumber     = mysqli_real_escape_string($conn, $_POST["phoneNumber"]);
$messengerName   = mysqli_real_escape_string($conn, $_POST["messengerName"]);
$dLicense        = mysqli_real_escape_string($conn, $_POST["dLicense"]);
$password        = mysqli_real_escape_string($conn, $_POST["password"]);
$confirmPassword = mysqli_real_escape_string($conn, $_POST["confirm_password"]);

// Validate password match
if ($password !== $confirmPassword) {
    setcookie('err_message', 'Passwords do not match.', time() + 15, '/');
    setcookie('message_class', 'alert-danger', time() + 15, '/');
    header("Location: ../signup.php");
    exit();
}

// Check for existing email or phone
$chk_email_stmt = mysqli_query($conn, "SELECT id FROM customers WHERE email = '$emailAddress' OR phone_number = '$phoneNumber'");
if (mysqli_num_rows($chk_email_stmt) > 0) {
    setcookie('err_message', 'The E-mail or Phone Number you entered already belongs to another account.', time() + 15, '/');
    setcookie('message_class', 'alert-danger', time() + 15, '/');
    header("Location: ../signup.php");
    exit();
}

// Check for existing driver license
$chk_dlicense_stmt = mysqli_query($conn, "SELECT id FROM customers WHERE driver_license_number = '$dLicense'");
if (mysqli_num_rows($chk_dlicense_stmt) > 0) {
    setcookie('err_message', "The Driver's License Number you entered already belongs to another account.", time() + 15, '/');
    setcookie('message_class', 'alert-danger', time() + 15, '/');
    header("Location: ../signup.php");
    exit();
}

// Hash the password
$hash_password = password_hash($password, PASSWORD_DEFAULT);

// Insert new customer
$stmt = "INSERT INTO customers (name, email, password, driver_license_number, address, messenger_name, phone_number) 
         VALUES ('$fullname', '$emailAddress', '$hash_password', '$dLicense', '$address', '$messengerName', '$phoneNumber')";

$qry = mysqli_query($conn, $stmt);
if ($qry) {
    setcookie('err_message', 'Account has been created. You can now login!', time() + 15, '/');
    setcookie('message_class', 'alert-success', time() + 15, '/');
} else {
    setcookie('err_message', 'Something went wrong. Please try again later.', time() + 15, '/');
    setcookie('message_class', 'alert-danger', time() + 15, '/');
}
header("Location: ../signup.php");
exit();
