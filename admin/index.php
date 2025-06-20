<?php
session_start();
include("../conn.php");
include '../check_customer_session.php';

$error = ""; // Initialize error message

// Check if database connection was successful
if (!$conn) {
    $error = "Database connection failed: " . mysqli_connect_error();
} else {
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (isset($_POST["username"]) && isset($_POST["password"])) {
            $input_username = mysqli_real_escape_string($conn, $_POST['username']);
            $input_password = $_POST['password']; // Password not sanitized yet as it's for password_verify

            // Attempt Admin Login by querying the 'admins' table
            // Try fetching admin by both 'name' and 'email' for flexibility
            $sql_admin = "SELECT id, name, email, password FROM admins WHERE name = ? OR email = ?";
            $stmt_admin = mysqli_prepare($conn, $sql_admin);

            if ($stmt_admin) {
                mysqli_stmt_bind_param($stmt_admin, "ss", $input_username, $input_username);
                mysqli_stmt_execute($stmt_admin);
                $result_admin = mysqli_stmt_get_result($stmt_admin);

                if ($result_admin && mysqli_num_rows($result_admin) > 0) {
                    $admin_row = mysqli_fetch_assoc($result_admin);

                    // Check if the 'password' column exists in the fetched row
                    if (isset($admin_row['password'])) {
                        $hashed_password_from_db = $admin_row['password'];

                        if (password_verify($input_password, $hashed_password_from_db)) {
                            $_SESSION["admin_logged_in"] = true;
                            $_SESSION["admin_id"] = $admin_row['id']; // Store admin ID
                            $_SESSION["admin_name"] = $admin_row['name']; // Store admin name
                            $_SESSION["admin_email"] = $admin_row['email']; // Store admin email
                            // Redirect to admin.php with a parameter to indicate a fresh login
                            header("Location: admin.php?fresh_login=true");
                            exit();
                        } else {
                            $error = "Invalid password.";
                        }
                    } else {
                        $error = "Admin table structure error: 'password' column not found.";
                    }
                } else {
                    // Check if the table itself exists
                    $check_table_sql = "SHOW TABLES LIKE 'admins'";
                    $table_exists_result = mysqli_query($conn, $check_table_sql);
                    if ($table_exists_result && mysqli_num_rows($table_exists_result) == 0) {
                        $error = "Error: 'admins' table does not exist in the database.";
                    } else {
                        $error = "Invalid username or email.";
                    }
                }
                mysqli_stmt_close($stmt_admin);
            } else {
                $error = "Database error: Could not prepare admin login statement: " . mysqli_error($conn);
            }
        } else {
            $error = "Please enter both username and password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - KRM Rent a Car Services</title>
</head>
<style>
    /* ... (CSS remains the same as before) ... */
    * {
        box-sizing: border-box;
    }

    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
        color: #333;
        padding: 20px;
        overflow: hidden;
    }

    .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 90vh;
        margin: 15px 0;
        padding: 2rem;
    }

    .login-card {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        width: 100%;
        max-width: 500px;
        max-height: 80vh;
        overflow-y: auto;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .login-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .login-title {
        font-size: 28px;
        font-weight: bold;
        margin: 0 0 10px 0;
        color: #2c3e50;
    }

    .login-subtitle {
        font-size: 16px;
        color: #7f8c8d;
        margin: 0;
        line-height: 1.4;
    }

    .login-form {
        width: 100%;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #333;
        margin-bottom: 8px;
    }

    .form-input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        color: #333;
        background: white;
        transition: all 0.3s ease;
    }

    .form-input:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }

    .form-input::placeholder {
        color: #999;
    }

    .submit-btn {
        width: 100%;
        background: #3498db;
        color: white;
        border: none;
        padding: 15px;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .submit-btn:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }

    .submit-btn:active {
        background: #2271a3;
        transform: translateY(0);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .error {
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #e74c3c;
        background-color: #fceceb;
        border: 1px solid #e74c3c;
        border-radius: 5px;
        padding: 10px;
        margin-top: 20px;
        text-align: center;
    }

    .return-section {
        text-align: center;
        margin-top: 25px;
        padding-top: 25px;
        border-top: 1px solid #eee;
    }

    .return-text {
        font-size: 14px;
        color: #666;
        margin: 0;
    }

    .return-link {
        color: #3498db;
        text-decoration: none;
        font-weight: 500;
        transition: color 0.3s ease;
    }

    .return-link:hover {
        color: #2980b9;
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        body {
            padding: 10px;
        }

        .login-container {
            margin: 0;
            padding: 10px;
            min-height: calc(100vh - 20px);
        }

        .login-card {
            padding: 20px;
            margin: 0;
            max-height: 90vh;
        }

        .login-title {
            font-size: 24px;
        }

        .login-subtitle {
            font-size: 14px;
        }
    }
</style>

<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Login Header -->
            <div class="login-header">
                <h2 class="login-title">Admin Login</h2>
                <p class="login-subtitle">Access the admin panel to manage the system.</p>
            </div>

            <!-- Login Form -->
            <form class="login-form" action="" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <input type="text" class="form-input" name="username" placeholder="Enter admin username" required />
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" class="form-input" name="password" placeholder="Enter admin password" required />
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">LOGIN</button>

                <!-- Return to public site -->
                <div class="return-section">
                    <p class="return-text">
                        <a href="../" class="return-link">Return to Public Site</a>
                    </p>
                </div>
            </form>
            <?php if (!empty($error)) {
                echo "<p class='error'>$error</p>";
            } ?>
        </div>
    </div>
</body>

</html>