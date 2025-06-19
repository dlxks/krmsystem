<?php
$your_admin_plain_password = "admin"; // <--- REPLACE THIS
echo password_hash($your_admin_plain_password, PASSWORD_DEFAULT);
?>