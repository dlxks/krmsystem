<?php
session_start();
include '../conn.php';

$car_id = null;
$car_data = [];

// Function to set a message in session for the parent page to display
function set_parent_message($message, $type = 'success', $redirect_tab = 'vehicles-tab')
{
    $_SESSION['response_message'] = $message;
    $_SESSION['response_type'] = $type;
    echo "<script>window.parent.location.href = 'admin.php#" . $redirect_tab . "';</script>";
    exit();
}

// Fetch car if ID exists
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $car_id = $_GET['id'];
    $sql_fetch_car = "SELECT * FROM cars WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql_fetch_car);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $car_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $car_data = mysqli_fetch_assoc($result);
        } else {
            set_parent_message('Car not found!', 'error');
        }
        mysqli_stmt_close($stmt);
    } else {
        set_parent_message('Error preparing fetch statement: ' . mysqli_error($conn), 'error');
    }
} else {
    set_parent_message('No car ID provided for update.', 'error');
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && $car_id !== null) {
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
    $status = mysqli_real_escape_string($conn, $_POST['status']); // new line

    $image_name = $car_data['image_path'] ?? '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/";
        $new_image_name = basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $new_image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "avif", "webp");
        if (in_array($imageFileType, $allowed_extensions)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_name = $new_image_name;
            } else {
                set_parent_message('Error uploading new image.', 'error');
            }
        } else {
            set_parent_message('Only JPG, JPEG, PNG, GIF, AVIF, & WEBP files are allowed.', 'error');
        }
    }

    $sql_update = "UPDATE cars SET make=?, model=?, year=?, color=?, engine=?, transmission=?, fuel_economy=?, seating_capacity=?, safety_features=?, additional_features=?, price=?, image_path=?, status=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql_update);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssisssssssdssi", $make, $model, $year, $color, $engine, $transmission, $fuelEconomy, $seatingCapacity, $safetyFeatures, $addtlFeatures, $price, $image_name, $status, $car_id);
        if (mysqli_stmt_execute($stmt)) {
            set_parent_message('Car updated successfully!');
        } else {
            set_parent_message('Error updating car: ' . mysqli_error($conn), 'error');
        }
        mysqli_stmt_close($stmt);
    } else {
        set_parent_message('Error preparing update: ' . mysqli_error($conn), 'error');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Update Car - KRM Rent a Car</title>
    <link rel="stylesheet" href="form.css">
    <style>
        body {
            padding: 20px;
        }

        .form-container {
            margin-top: 50px;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <div class="form-title">Update Car</div>
                <div class="form-subtitle">Fill out the car's details accurately</div>
            </div>

            <form action="" method="POST" enctype="multipart/form-data">
                <div class="form-type">
                    <label>Make</label>
                    <input type="text" name="make" class="form-input" required value="<?= htmlspecialchars($car_data['make'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Model</label>
                    <input type="text" name="model" class="form-input" required value="<?= htmlspecialchars($car_data['model'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Year</label>
                    <input type="number" name="year" class="form-input" required value="<?= htmlspecialchars($car_data['year'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Color</label>
                    <input type="text" name="color" class="form-input" required value="<?= htmlspecialchars($car_data['color'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Engine</label>
                    <input type="text" name="engine" class="form-input" required value="<?= htmlspecialchars($car_data['engine'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Transmission</label>
                    <input type="text" name="transmission" class="form-input" required value="<?= htmlspecialchars($car_data['transmission'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Fuel Economy</label>
                    <input type="text" name="fuelEconomy" class="form-input" required value="<?= htmlspecialchars($car_data['fuel_economy'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Seating Capacity</label>
                    <input type="text" name="seatingCapacity" class="form-input" required value="<?= htmlspecialchars($car_data['seating_capacity'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Safety Features</label>
                    <input type="text" name="safetyFeatures" class="form-input" required value="<?= htmlspecialchars($car_data['safety_features'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Additional Features</label>
                    <input type="text" name="addtlFeatures" class="form-input" required value="<?= htmlspecialchars($car_data['additional_features'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Car's Base Price</label>
                    <input type="number" step="0.01" name="price" class="form-input" required value="<?= htmlspecialchars($car_data['price'] ?? '') ?>">
                </div>
                <div class="form-type">
                    <label>Status</label>
                    <select name="status" class="form-input" required>
                        <option value="available" <?= ($car_data['status'] ?? '') === 'available' ? 'selected' : '' ?>>Available</option>
                        <option value="rented" <?= ($car_data['status'] ?? '') === 'rented' ? 'selected' : '' ?>>Rented</option>
                        <option value="unavailable" <?= ($car_data['status'] ?? '') === 'unavailable' ? 'selected' : '' ?>>Unavailable</option>
                    </select>
                </div>
                <div class="form-type">
                    <label>Upload New Image</label>
                    <?php if (!empty($car_data['image_path'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($car_data['image_path']) ?>" style="max-width: 150px; display:block; margin-top:10px;">
                        <small>Current Image</small>
                    <?php endif; ?>
                    <input type="file" name="image" accept="image/png, image/jpeg, image/webp, image/avif, image/gif">
                    <small>Leave blank to keep current image</small>
                </div>

                <button type="submit" class="submit-btn">Update Car</button>
                <div class="return-section">
                    <p><a href="admin.php" class="return-link">Return to Admin Panel</a></p>
                </div>
            </form>
        </div>
    </div>
</body>

</html>