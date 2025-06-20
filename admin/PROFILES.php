<?php
session_start();

include '../conn.php';
include '../check_customer_session.php';

// Initialize a message variable for customer responses
$display_customer_message = '';
$customer_message_type = ''; // 'success' or 'error'

// Function to set a message in session for the customer profile page to display
function set_customer_message($message, $type = 'success')
{
  $_SESSION['customer_response_message'] = $message;
  $_SESSION['customer_response_type'] = $type;
}

// Check for messages from session and clear them after displaying
if (isset($_SESSION['customer_response_message'])) {
  $display_customer_message = $_SESSION['customer_response_message'];
  $customer_message_type = $_SESSION['customer_response_type'] ?? 'success';
  unset($_SESSION['customer_response_message']);
  unset($_SESSION['customer_response_type']);
}

// Check if a customer is logged in.
if (!isset($_SESSION['customer_id'])) {
  // If no customer is logged in, redirect to login page
  header("Location: login.php");
  exit();
}

$customer_id = $_SESSION['customer_id']; // Get the logged-in customer's ID from session

$customer_data = [];
// Change default profile photo to a local placeholder (make sure this file exists in uploads/)
$profile_photo_path_display = "../uploads/placeholder_profile.png";
$license_image_path_display = ""; // Default empty license image, will be handled by dynamic check

// --- PHP for fetching customer data ---
$sql_fetch_customer = "SELECT name, email, phone_number, address, profile_photo_path, driver_license_image_path
                       FROM customers
                       WHERE id = ?";
$stmt_fetch_customer = mysqli_prepare($conn, $sql_fetch_customer);

if ($stmt_fetch_customer) {
  mysqli_stmt_bind_param($stmt_fetch_customer, "i", $customer_id);
  mysqli_stmt_execute($stmt_fetch_customer);
  $result_fetch_customer = mysqli_stmt_get_result($stmt_fetch_customer);

  if (mysqli_num_rows($result_fetch_customer) > 0) {
    $customer_data = mysqli_fetch_assoc($result_fetch_customer);
    // Set paths for display if paths exist in DB, otherwise keep default placeholders
    if (!empty($customer_data['profile_photo_path'])) {
      $profile_photo_path_display = "../uploads/" . htmlspecialchars($customer_data['profile_photo_path']);
    }
    if (!empty($customer_data['driver_license_image_path'])) {
      $license_image_path_display = "../uploads/" . htmlspecialchars($customer_data['driver_license_image_path']);
    }
  } else {
    set_customer_message('Customer data not found. Please log in again.', 'error');
    // Handle case where customer ID in session doesn't match any record
    session_destroy();
    header("Location: login.php");
    exit();
  }
  mysqli_stmt_close($stmt_fetch_customer);
} else {
  set_customer_message('Error preparing customer fetch statement: ' . mysqli_error($conn), 'error');
}

// --- PHP for handling profile updates ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $phone = mysqli_real_escape_string($conn, $_POST['phone']);
  $location = mysqli_real_escape_string($conn, $_POST['location']);

  // Initialize new image names with current database values to ensure they are not lost if no new file is uploaded
  $new_profile_photo_name = $customer_data['profile_photo_path'] ?? '';
  $new_license_image_name = $customer_data['driver_license_image_path'] ?? '';

  // Handle profile photo upload
  if (isset($_FILES['profilePhotoInput']) && $_FILES['profilePhotoInput']['error'] == 0) {
    $target_dir = "../uploads/";
    $image_basename = basename($_FILES["profilePhotoInput"]["name"]);
    $unique_filename = uniqid() . "_" . $image_basename; // Prevent name collisions
    $target_file = $target_dir . $unique_filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif", "avif", "webp");

    if (in_array($imageFileType, $allowed_extensions)) {
      if (move_uploaded_file($_FILES["profilePhotoInput"]["tmp_name"], $target_file)) {
        $new_profile_photo_name = $unique_filename;
      } else {
        set_customer_message('Sorry, there was an error uploading your profile photo.', 'error');
      }
    } else {
      set_customer_message('Sorry, only JPG, JPEG, PNG, GIF, AVIF, & WEBP files are allowed for profile photo.', 'error');
    }
  }

  // Handle driver's license image upload
  if (isset($_FILES['licenseInput']) && $_FILES['licenseInput']['error'] == 0) {
    $target_dir = "../uploads/";
    $image_basename = basename($_FILES["licenseInput"]["name"]);
    $unique_filename = uniqid() . "_" . $image_basename; // Prevent name collisions
    $target_file = $target_dir . $unique_filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_extensions = array("jpg", "jpeg", "png", "gif", "avif", "webp");

    if (in_array($imageFileType, $allowed_extensions)) {
      if (move_uploaded_file($_FILES["licenseInput"]["tmp_name"], $target_file)) {
        $new_license_image_name = $unique_filename;
      } else {
        set_customer_message('Sorry, there was an error uploading your driver\'s license image.', 'error');
      }
    } else {
      set_customer_message('Sorry, only JPG, JPEG, PNG, GIF, AVIF, & WEBP files are allowed for driver\'s license image.', 'error');
    }
  }

  // Update customer information in the 'customers' table
  $sql_update_customer = "UPDATE customers
                            SET name=?, email=?, phone_number=?, address=?, profile_photo_path=?, driver_license_image_path=?
                            WHERE id=?";
  $stmt_update_customer = mysqli_prepare($conn, $sql_update_customer);

  if ($stmt_update_customer) {
    mysqli_stmt_bind_param($stmt_update_customer, "ssssssi", $name, $email, $phone, $location, $new_profile_photo_name, $new_license_image_name, $customer_id);
    if (mysqli_stmt_execute($stmt_update_customer)) {
      set_customer_message('Profile updated successfully!', 'success');
      // No need to re-fetch data explicitly here, as the page will redirect and load fresh data.
    } else {
      set_customer_message('Error updating profile: ' . mysqli_error($conn), 'error');
    }
    mysqli_stmt_close($stmt_update_customer);
  } else {
    set_customer_message('Error preparing update statement: ' . mysqli_error($conn), 'error');
  }
  // Always redirect back to PROFILES.php after form submission to prevent resubmission on refresh
  header("Location: PROFILES.php");
  exit();
}
?>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1" name="viewport" />
  <title>My Profile - KRM Rent a Car Services Portal</title>
  <style>
    body {
      background-color: #f3f4f6;
      color: #1f2937;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
    }

    header {
      border-bottom: 1px solid #d1d5db;
      background-color: white;
    }

    .container {
      max-width: 1120px;
      margin: 0 auto;
      padding: 12px 16px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    h1 {
      font-weight: 600;
      font-size: 14px;
      color: #1f2937;
      margin: 0;
    }

    button.admin-panel {
      background-color: #3b82f6;
      border: none;
      color: white;
      font-weight: 600;
      font-size: 14px;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
    }

    nav {
      background-color: white;
      border-top: 1px solid #d1d5db;
    }

    nav ul {
      max-width: 1120px;
      margin: 0 auto;
      padding: 0 16px;
      display: flex;
      list-style: none;
      margin-bottom: 0;
    }

    nav ul li {
      padding: 12px 12px;
      font-size: 14px;
      color: #4b5563;
      cursor: pointer;
      user-select: none;
    }

    nav ul li.active {
      border-bottom: 2px solid #3b82f6;
      color: #1f2937;
      font-weight: 600;
      cursor: default;
    }

    main {
      max-width: 1120px;
      margin: 24px auto;
      padding: 0 16px;
    }

    .profile-section {
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 32px;
      position: relative;
    }

    .profile-photo-container {
      position: relative;
      width: 80px;
      height: 80px;
      flex-shrink: 0;
    }

    .profile-photo {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 1px solid #d1d5db;
      object-fit: cover;
      display: block;
    }

    .profile-photo-placeholder {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      border: 1px solid #d1d5db;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 9px;
      color: #9ca3af;
      user-select: none;
      background-color: #fff;
    }

    .profile-photo-edit-label {
      position: absolute;
      bottom: 0;
      right: 0;
      background-color: #3b82f6;
      border-radius: 50%;
      width: 22px;
      height: 22px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      border: 2px solid white;
      color: white;
      font-size: 14px;
    }

    .profile-info {
      font-size: 12px;
      color: #374151;
      flex-grow: 1;
    }

    .profile-info h2 {
      font-weight: 600;
      color: #1f2937;
      margin: 0 0 6px 0;
      font-size: 14px;
    }

    .profile-info p {
      margin: 2px 0;
      line-height: 1.2;
    }

    .edit-button {
      background-color: #3b82f6;
      border: none;
      color: white;
      font-weight: 600;
      font-size: 12px;
      padding: 6px 12px;
      border-radius: 4px;
      cursor: pointer;
      user-select: none;
      transition: background-color 0.2s ease;
      flex-shrink: 0;
      margin-left: 16px;
    }

    .edit-button:hover {
      background-color: #2563eb;
    }

    .license-box {
      border: 1px dashed #6b7280;
      color: #6b7280;
      font-size: 14px;
      text-align: center;
      padding: 40px 0;
      min-height: 80px;
      user-select: none;
      position: relative;
      max-width: 100%;
    }

    .license-image {
      max-width: 100%;
      max-height: 200px;
      object-fit: contain;
      display: block;
      margin: 0 auto;
      border-radius: 4px;
    }

    .license-edit-label {
      position: absolute;
      top: 8px;
      right: 8px;
      background-color: #3b82f6;
      border-radius: 4px;
      padding: 4px 8px;
      color: white;
      font-size: 12px;
      cursor: pointer;
      user-select: none;
      border: 1px solid white;
    }

    /* Modal styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100vw;
      height: 100vh;
      background-color: rgba(0, 0, 0, 0.4);
      align-items: center;
      justify-content: center;
    }

    .modal.active {
      display: flex;
    }

    .modal-content {
      background-color: white;
      border-radius: 6px;
      padding: 24px;
      width: 90%;
      max-width: 400px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .modal-content h3 {
      margin-top: 0;
      margin-bottom: 16px;
      font-size: 18px;
      color: #1f2937;
    }

    .modal-content label {
      display: block;
      font-size: 12px;
      color: #374151;
      margin-bottom: 4px;
      margin-top: 12px;
    }

    .modal-content input {
      width: 100%;
      padding: 8px 10px;
      font-size: 14px;
      border: 1px solid #d1d5db;
      border-radius: 4px;
      box-sizing: border-box;
    }

    .modal-buttons {
      margin-top: 20px;
      display: flex;
      justify-content: flex-end;
      gap: 12px;
    }

    .modal-buttons button {
      padding: 8px 16px;
      font-size: 14px;
      border-radius: 4px;
      border: none;
      cursor: pointer;
      font-weight: 600;
      user-select: none;
    }

    .modal-buttons .cancel-btn {
      background-color: #e5e7eb;
      color: #374151;
    }

    .modal-buttons .cancel-btn:hover {
      background-color: #d1d5db;
    }

    .modal-buttons .save-btn {
      background-color: #3b82f6;
      color: white;
    }

    .modal-buttons .save-btn:hover {
      background-color: #2563eb;
    }

    /* Styles for the custom message box */
    .customer-message-box {
      padding: 15px 20px;
      margin: 20px auto;
      border-radius: 8px;
      font-size: 14px;
      display: none;
      justify-content: space-between;
      align-items: center;
      max-width: 1120px;
    }

    .customer-message-box.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .customer-message-box.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .customer-message-box .close-message {
      cursor: pointer;
      font-weight: bold;
      font-size: 18px;
      margin-left: 10px;
    }

    @media (max-width: 480px) {
      .profile-section {
        flex-direction: column;
        align-items: flex-start;
      }

      .edit-button {
        margin-left: 0;
        margin-top: 12px;
      }

      .profile-photo-edit-label {
        width: 20px;
        height: 20px;
        font-size: 12px;
      }

      .license-edit-label {
        font-size: 11px;
        padding: 3px 6px;
      }
    }
  </style>
</head>

<body>
  <header>
    <div class="container">
      <h1>KRM RENT A CAR SERVICES PORTAL</h1>
      <button class="admin-panel" type="button" onclick="window.location.href='login.php'">ADMIN PANEL</button>
    </div>
    <nav>
      <ul>
        <li onclick="window.location.href='index.php#home-tab'">HOME</li>
        <li onclick="window.location.href='index.php#rentals-tab'">RENTALS</li>
        <li onclick="window.location.href='index.php#reservation-tab'">RESERVE NOW</li>
        <li onclick="window.location.href='index.php#feedback-tab'">FEEDBACK &amp; RATING</li>
        <li class="active" onclick="window.location.href='PROFILES.php'">MY PROFILE</li>
      </ul>
    </nav>
  </header>
  <main>
    <!-- Customer Custom Message Box -->
    <div id="customerMessageBox" class="customer-message-box <?php echo $customer_message_type; ?>" style="display: <?php echo !empty($display_customer_message) ? 'flex' : 'none'; ?>;">
      <span><?php echo $display_customer_message; ?></span>
      <span class="close-message" onclick="this.parentElement.style.display='none';">&times;</span>
    </div>

    <section class="profile-section">
      <div class="profile-photo-container">
        <?php if (!empty($customer_data['profile_photo_path'])): // Only check if path is not empty in DB 
        ?>
          <img
            alt="profile photo"
            class="profile-photo"
            height="80"
            id="profilePhoto"
            src="<?php echo $profile_photo_path_display; ?>"
            width="80" />
        <?php else: ?>
          <div class="profile-photo-placeholder" id="profilePhoto">
            PROFILE PHOTO
          </div>
        <?php endif; ?>
        <label
          aria-label="Change profile photo"
          class="profile-photo-edit-label"
          for="profilePhotoInput"
          role="button"
          tabindex="0"
          title="Change profile photo">
          ✎
        </label>
        <input
          accept="image/*"
          id="profilePhotoInput"
          style="display: none"
          type="file"
          name="profilePhotoInput" />
      </div>
      <div class="profile-info">
        <h2 id="customerName"><?php echo htmlspecialchars($customer_data['name'] ?? 'N/A'); ?></h2>
        <p id="customerEmail"><?php echo htmlspecialchars($customer_data['email'] ?? 'N/A'); ?></p>
        <p id="customerPhone"><?php echo htmlspecialchars($customer_data['phone_number'] ?? 'N/A'); ?></p>
        <p id="customerLocation"><?php echo htmlspecialchars($customer_data['address'] ?? 'N/A'); ?></p>
      </div>
      <button
        aria-label="edit profile information"
        class="edit-button"
        id="editBtn"
        type="button">
        Edit
      </button>
    </section>
    <section>
      <div
        aria-label="Driver's license document image placeholder"
        class="license-box"
        id="licenseBox">
        <?php if (!empty($customer_data['driver_license_image_path'])): // Only check if path is not empty in DB 
        ?>
          <img
            alt="Driver's license document image"
            class="license-image"
            id="licenseImage"
            src="<?php echo $license_image_path_display; ?>" />
          <span id="licensePlaceholderText" style="display: none;">
            [DRIVER'S LICENSE DOCUMENT IMAGE]
          </span>
        <?php else: ?>
          <img
            alt="Driver's license document image"
            class="license-image"
            id="licenseImage"
            src=""
            style="display: none;" />
          <span id="licensePlaceholderText">
            [DRIVER'S LICENSE DOCUMENT IMAGE]
          </span>
        <?php endif; ?>

        <label
          aria-label="Change driver's license image"
          class="license-edit-label"
          for="licenseInput"
          role="button"
          tabindex="0"
          title="Change driver's license image">
          ✎ Edit
        </label>
        <input
          accept="image/*"
          id="licenseInput"
          style="display: none"
          type="file"
          name="licenseInput" />
      </div>
    </section>
  </main>
  <div
    aria-describedby="modalDesc"
    aria-labelledby="modalTitle"
    aria-modal="true"
    class="modal"
    id="editModal"
    role="dialog">
    <div class="modal-content">
      <h3 id="modalTitle">Edit Profile Information</h3>
      <form id="editForm" method="POST" enctype="multipart/form-data" action="PROFILES.php">
        <label for="nameInput"> Name </label>
        <input id="nameInput" name="name" required="" type="text" value="<?php echo htmlspecialchars($customer_data['name'] ?? ''); ?>" />
        <label for="emailInput"> Email </label>
        <input id="emailInput" name="email" required="" type="email" value="<?php echo htmlspecialchars($customer_data['email'] ?? ''); ?>" />
        <label for="phoneInput"> Phone </label>
        <input id="phoneInput" name="phone" required="" type="tel" value="<?php echo htmlspecialchars($customer_data['phone_number'] ?? ''); ?>" />
        <label for="locationInput"> Address </label>
        <input id="locationInput" name="location" required="" type="text" value="<?php echo htmlspecialchars($customer_data['address'] ?? ''); ?>" />
        <div class="modal-buttons">
          <button class="cancel-btn" id="cancelBtn" type="button">
            Cancel
          </button>
          <button class="save-btn" type="submit">Save</button>
        </div>
      </form>
    </div>
  </div>
  <script>
    // Function to display messages in the customer profile page
    function showCustomerMessageBox(message, type) {
      const messageBox = document.getElementById('customerMessageBox');
      const messageText = messageBox.querySelector('span');
      messageText.textContent = message;
      messageBox.className = 'customer-message-box ' + type; // Reset classes and add type
      messageBox.style.display = 'flex'; // Show the box
      // Automatically hide the message after 5 seconds
      setTimeout(() => {
        messageBox.style.display = 'none';
      }, 5000);
    }

    // Check if there's a PHP message to display on page load
    <?php if (!empty($display_customer_message)): ?>
      document.addEventListener('DOMContentLoaded', function() {
        showCustomerMessageBox('<?php echo $display_customer_message; ?>', '<?php echo $customer_message_type; ?>');
      });
    <?php endif; ?>

    const editBtn = document.getElementById("editBtn");
    const modal = document.getElementById("editModal");
    const cancelBtn = document.getElementById("cancelBtn");
    const editForm = document.getElementById("editForm");

    const customerNameElem = document.getElementById("customerName");
    const customerEmailElem = document.getElementById("customerEmail");
    const customerPhoneElem = document.getElementById("customerPhone");
    const customerLocationElem = document.getElementById("customerLocation");

    const nameInput = document.getElementById("nameInput");
    const emailInput = document.getElementById("emailInput");
    const phoneInput = document.getElementById("phoneInput");
    const locationInput = document.getElementById("locationInput");

    // Profile photo elements
    const profilePhotoInput = document.getElementById("profilePhotoInput");
    // Select the actual <img> tag for the profile photo by its class
    const profilePhotoImgElement = document.querySelector('.profile-section .profile-photo');
    const profilePhotoPlaceholderElement = document.querySelector('.profile-section .profile-photo-placeholder');

    // License image elements
    const licenseInput = document.getElementById("licenseInput");
    const licenseImage = document.getElementById("licenseImage");
    const licensePlaceholderText = document.getElementById(
      "licensePlaceholderText"
    );

    // Open modal and populate inputs with current info
    editBtn.addEventListener("click", () => {
      nameInput.value = customerNameElem.textContent;
      emailInput.value = customerEmailElem.textContent;
      phoneInput.value = customerPhoneElem.textContent;
      locationInput.value = customerLocationElem.textContent;
      modal.classList.add("active");
      nameInput.focus();
    });

    // Close modal
    cancelBtn.addEventListener("click", () => {
      modal.classList.remove("active");
    });

    // Close modal on outside click
    window.addEventListener("click", (e) => {
      if (e.target === modal) {
        modal.classList.remove("active");
      }
    });

    // Close modal on Escape key
    window.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal.classList.contains("active")) {
        modal.classList.remove("active");
      }
    });

    // Profile photo change handler (client-side preview)
    profilePhotoInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function(evt) {
          if (profilePhotoImgElement) {
            profilePhotoImgElement.src = evt.target.result;
            profilePhotoImgElement.style.display = 'block';
          }
          if (profilePhotoPlaceholderElement) {
            profilePhotoPlaceholderElement.style.display = 'none';
          }
        };
        reader.readAsDataURL(file);
      }
    });

    // License image change handler (client-side preview)
    licenseInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (file && file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function(evt) {
          licenseImage.src = evt.target.result;
          licenseImage.style.display = "block";
          licensePlaceholderText.style.display = "none";
        };
        reader.readAsDataURL(file);
      }
    });

    // Update active nav link based on current page (for this specific page)
    document.addEventListener('DOMContentLoaded', function() {
      const navItems = document.querySelectorAll('nav ul li');
      navItems.forEach(item => {
        item.classList.remove('active');
        // Check if the current URL is PROFILES.php (without query params or hash)
        if (window.location.pathname.endsWith('PROFILES.php')) {
          // If it's the PROFILES.php page, activate "MY PROFILE" tab
          if (item.textContent.includes('MY PROFILE')) {
            item.classList.add('active');
          }
        } else {
          // For other index.php tabs, use the existing logic (based on hash)
          const urlHash = window.location.hash.substring(1);
          if (urlHash && item.getAttribute('onclick') && item.getAttribute('onclick').includes(urlHash)) {
            item.classList.add('active');
          } else if (!urlHash && item.textContent.includes('HOME')) {
            item.classList.add('active');
          }
        }
      });
    });
  </script>
</body>

</html>