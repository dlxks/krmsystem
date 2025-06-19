<?php
session_start();

include '../conn.php';

// Function to set a message in session for the parent page to display
function set_parent_message($message, $type = 'success', $redirect_tab = 'vehicles-tab')
{
    $_SESSION['response_message'] = $message;
    $_SESSION['response_type'] = $type;
    echo "<script>window.parent.location.href = 'admin.php#" . $redirect_tab . "';</script>";
    exit();
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize form data
    $make = mysqli_real_escape_string($conn, $_POST['make']);
    $model = mysqli_real_escape_string($conn, $_POST['model']);
    $year = mysqli_real_escape_string($conn, $_POST['year']);
    $color = mysqli_real_escape_string($conn, $_POST['color']);
    $engine = mysqli_real_escape_string($conn, $_POST['engine']);
    $transmission = mysqli_real_escape_string($conn, $_POST['transmission']);
    $fuelEconomy = mysqli_real_escape_string($conn, $_POST['fuelEconomy']);
    $seatingCapacity = mysqli_real_escape_string($conn, $_POST['seatingCapacity']);
    $safetyFeatures = mysqli_real_escape_string($conn, $_POST['safetyFeatures']);
    $addtlFeatures = mysqli_real_escape_string($conn, $_POST['addtlFeatures']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);

    // Handle image upload (basic example, consider a dedicated upload directory and security)
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/"; // Make sure this directory exists and is writable
        $image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Allow certain file formats
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "avif", "webp");
        if (in_array($imageFileType, $allowed_extensions)) {
            if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                set_parent_message('Sorry, there was an error uploading your image.', 'error');
            }
        } else {
            set_parent_message('Sorry, only JPG, JPEG, PNG, GIF, AVIF, & WEBP files are allowed.', 'error');
        }
    }

    // SQL query to insert data into the 'cars' table
    $sql = "INSERT INTO cars (make, model, year, color, engine, transmission, fuel_economy, seating_capacity, safety_features, additional_features, price, image_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // Prepare the statement to prevent SQL injection
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        // Bind parameters
        mysqli_stmt_bind_param($stmt, "ssisssssssds", $make, $model, $year, $color, $engine, $transmission, $fuelEconomy, $seatingCapacity, $safetyFeatures, $addtlFeatures, $price, $image_name);

        // Execute the statement
        if (mysqli_stmt_execute($stmt)) {
            set_parent_message('New car added successfully!');
        } else {
            set_parent_message('Error adding new car: ' . mysqli_error($conn), 'error');
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        set_parent_message('Error preparing statement: ' . mysqli_error($conn), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add car - KRM Rent a Car Services</title>
    <link rel="stylesheet" href="form.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
        }

        .form-container {
            box-shadow: none;
            border: none;
            padding: 0;
        }

        .form-card {
            box-shadow: none;
            border: none;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <div class="form-title">Add a new car</div>
                <div class="form-subtitle">Please fill in with the correct information</div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="">
                <div class="form-type">
                    <label for="make" class="form-label">Make</label>
                    <input type="text" class="form-input" name="make" placeholder="e.g. Mitsubishi" required>
                </div>
                <div class="form-type">
                    <label for="model" class="form-label">Model</label>
                    <input type="text" class="form-input" name="model" placeholder="e.g. Mirage" required>
                </div>
                <div class="form-type">
                    <label for="year" class="form-label">Year</label>
                    <input type="number" class="form-input" name="year" placeholder="e.g. 2023" required>
                </div>
                <div class="form-type">
                    <label for="color" class="form-label">Color</label>
                    <input type="text" class="form-input" name="color" placeholder="e.g. Silver" required />
                </div>
                <div class="form-type">
                    <label for="engine" class="form-label">Engine</label>
                    <input type="text" class="form-input" name="engine" placeholder="e.g. 1.2-liter MIVEC DOHC 3-cylinder" required />
                </div>
                <div class="form-type">
                    <label for="transmission" class="form-label">Transmission</label>
                    <input type="text" class="form-input" name="transmission" placeholder="e.g. Continuously Variable Transmission (CVT)" required />
                </div>
                <div class="form-type">
                    <label for="fuelEconomy" class="form-label">Fuel Economy</label>
                    <input type="text" class="form-input" name="fuelEconomy" placeholder="e.g. 37 combined mpg" required />
                </div>
                <div class="form-type">
                    <label for="seatingCapacity" class="form-label">Seating Capacity</label>
                    <input type="text" class="form-input" name="seatingCapacity" placeholder="e.g. 5 passengers" required />
                </div>
                <div class="form-type">
                    <label for="safetyFeatures" class="form-label">Safety Features</label>
                    <input type="text" class="form-input" name="safetyFeatures" placeholder="e.g. Anti-lock Braking System, Front SRS airbags, etc." required />
                </div>
                <div class="cont">
                    <label for="addtlFeatures" class="form-label">Additional Features</label>
                    <input type="text" class="form-input" name="addtlFeatures" placeholder="e.g. Cruise control, ECO indicator, Bluetooth wireless technology" required />
                </div>
                <div class="form-type">
                    <label for="price" class="form-label">Car's base price</label>
                    <input type="number" step="0.01" class="form-input" name="price" placeholder="e.g. 1234.00" required>
                </div>
                <div class="cont">
                    <label for="image" class="form-label">Please insert image of the car</label>
                    <input type="file" name="image" class="form-input" accept="image/png, image/jpeg, image/avif, image/webp" />
                </div>

                <button type="submit" class="submit-btn">Add new car</button>
                <div class="return-section">
                    <p class="return-text">
                        <a href="admin.php" class="return-link">Return to Admin Panel</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

</body>

</html>