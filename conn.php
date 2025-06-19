<?php
$servername = 'localhost';
$username = 'root';
$password = '';
$db = 'krmsystem';

$conn = mysqli_connect($servername, $username, $password, $db);

if (mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}
