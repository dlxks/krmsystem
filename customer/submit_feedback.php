<?php
session_start();
include '../conn.php';
include '../check_customer_session.php';

$feedback_id = $_POST['feedback_id'];
$rating = $_POST['rating'];
$comments = $_POST['feedback'];
$name = $_POST['name'];
$phone_number = $_POST['phoneNum'];

$update_sql = "UPDATE feedbacks SET name = ?, phone_number = ?, rating = ?, comments = ?, status = 'completed' WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($stmt, "ssisi", $name, $phone_number, $rating, $comments, $feedback_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: index.php#feedback-tab");
exit();
