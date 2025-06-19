<?php
session_start();
require_once '../conn.php'; // or update to your DB include path

if (!isset($_SESSION["customer_id"])) {
    die("Unauthorized access.");
}

$customerId = $_SESSION["customer_id"];

// Get submitted data
$name = $_POST["name"];
$email = $_POST["email"];
$phone = $_POST["phone"];
$address = $_POST["address"];
$license = $_POST["license"];
$messenger = $_POST["messenger_name"];
$password = trim($_POST["password"]); // NEW

$profilePhotoPath = '';
$licenseImagePath = '';
$uploadDir = 'uploads/'; // Update if needed

// Handle profile photo upload
if (!empty($_FILES['profilePhotoInputModal']['name'])) {
    $profileName = basename($_FILES['profilePhotoInputModal']['name']);
    $profilePath = $uploadDir . 'profile_' . $customerId . '_' . time() . '.' . pathinfo($profileName, PATHINFO_EXTENSION);
    if (move_uploaded_file($_FILES['profilePhotoInputModal']['tmp_name'], $profilePath)) {
        $profilePhotoPath = $profilePath;
    }
}

// Handle license image upload
if (!empty($_FILES['licenseInputModal']['name'])) {
    $licenseName = basename($_FILES['licenseInputModal']['name']);
    $licensePath = $uploadDir . 'license_' . $customerId . '_' . time() . '.' . pathinfo($licenseName, PATHINFO_EXTENSION);
    if (move_uploaded_file($_FILES['licenseInputModal']['tmp_name'], $licensePath)) {
        $licenseImagePath = $licensePath;
    }
}

// Build dynamic SQL
$sql = "UPDATE customers SET name = ?, email = ?, phone_number = ?, address = ?, driver_license_number = ?, messenger_name = ?";
$params = [$name, $email, $phone, $address, $license, $messenger];
$types = "ssssss";

// Password update
if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql .= ", password = ?";
    $params[] = $hashedPassword;
    $types .= "s";
}

// Profile photo path
if (!empty($profilePhotoPath)) {
    $sql .= ", profile_photo_path = ?";
    $params[] = $profilePhotoPath;
    $types .= "s";
}

// License image path
if (!empty($licenseImagePath)) {
    $sql .= ", license_image_path = ?";
    $params[] = $licenseImagePath;
    $types .= "s";
}

// Finish query
$sql .= " WHERE id = ?";
$params[] = $customerId;
$types .= "i";

// Execute
$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    if (mysqli_stmt_execute($stmt)) {
        setcookie("err_message", "Profile updated successfully!", time() + 3, "/");
        setcookie("message_class", "alert-success", time() + 3, "/");
        header("Location: index.php?profile-tab");
        exit;
    } else {
        setcookie("err_message", "Update failed: " . mysqli_stmt_error($stmt), time() + 3, "/");
        setcookie("message_class", "alert-danger", time() + 3, "/");
        header("Location: index.php?profile-tab");
        exit;
    }
    mysqli_stmt_close($stmt);
} else {
    setcookie("err_message", "SQL prepare failed: " . mysqli_error($conn), time() + 3, "/");
    setcookie("message_class", "alert-danger", time() + 3, "/");
    header("Location: index.php?profile-tab");
    exit;
}

mysqli_close($conn);
