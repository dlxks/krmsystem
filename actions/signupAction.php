<?php
session_start();
require_once("../conn.php");

$fullname = mysqli_real_escape_string($conn, $_POST["fullname"]);
$address = mysqli_real_escape_string($conn, $_POST["address"]);
$emailAddress = mysqli_real_escape_string($conn, $_POST["emailAddress"]);
$phoneNumber = mysqli_real_escape_string($conn, $_POST["phoneNumber"]);
$messengerName = mysqli_real_escape_string($conn, $_POST["messengerName"]);
$dLicense = mysqli_real_escape_string($conn, $_POST["dLicense"]);
$password = mysqli_real_escape_string($conn, $_POST["password"]);
$confirm_password = mysqli_real_escape_string($conn, $_POST["confirm_password"]);


// Secure password
$hash_password = password_hash($_POST["password"], PASSWORD_DEFAULT); // Hash the password using the default algorithm

$chk_email_stmt = mysqli_query($conn, "SELECT * FROM customers WHERE email = '$emailAddress' OR phone_number = '$phoneNumber'");
$chk_email_res = mysqli_fetch_assoc($chk_email_stmt);

$chk_dlicense_stmt = mysqli_query($conn, "SELECT * FROM customers WHERE driver_license_number  = '$dLicense'");
if (mysqli_num_rows($chk_dlicense_stmt) > 0) {
    // Check if Email exists
    $message = "The Driver's License Number you entered already belongs to another account.";
    setcookie('err_message', $message, time() + 15, '/');
    setcookie('message_class', 'alert-danger', time() + 15, '/');
    header("location: ../signup.php");
    exit();
}

if (mysqli_num_rows($chk_email_stmt) > 0) {
    // Check if Email exists
    $message = "The E-mail or Phone Number you entered already belongs to another account.";
} else {

    // If no error
    $stmt = "INSERT INTO customers (name, email, password, driver_license_number, address, messenger_name, phone_number) 
            VALUES ('$fullname', '$emailAddress', '$hash_password', '$dLicense', '$address', '$messengerName', '$phoneNumber')";
    $qry = mysqli_query($conn, $stmt) or die(mysqli_error($conn));

    $message = "Account has been created. You can now login!";
    setcookie('err_message ', $message, time() + 15, '/');
    setcookie('message_class', 'alert-success', time() + 15, '/');
    header("location: ../signup.php");
    exit();
}
