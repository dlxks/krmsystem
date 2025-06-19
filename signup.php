<?php

include("conn.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>

    <link rel="stylesheet" href="css/bootstrap.min.css">
    <style>
        .gradient-custom {
            /* fallback for old browsers */
            background: #3498db;

            /* Chrome 10-25, Safari 5.1-6 */
            background: -webkit-linear-gradient(to bottom right, rgba(95, 191, 255, 1), rgba(255, 243, 245, 1));

            /* W3C, IE 10+/ Edge, Firefox 16+, Chrome 26+, Opera 12+, Safari 7+ */
            background: linear-gradient(to bottom right, rgba(95, 191, 255, 1), rgba(0, 83, 139, 1))
        }

        .card-registration .select-input.form-control[readonly]:not([disabled]) {
            font-size: 1rem;
            line-height: 2.15;
            padding-left: .75em;
            padding-right: .75em;
        }

        .card-registration .select-arrow {
            top: 13px;
        }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <!-- Container wrapper -->
        <div class="container">
            <!-- Toggle button -->
            <button
                data-mdb-collapse-init
                class="navbar-toggler"
                type="button"
                data-mdb-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent"
                aria-expanded="false"
                aria-label="Toggle navigation">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Collapsible wrapper -->
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Navbar brand -->
                <a href="index.php" class="fw-bolder link-dark link-underline link-underline-opacity-0">KRM RENT A CAR SERVICES PORTAL</a>
            </div>
            <!-- Collapsible wrapper -->

            <!-- Right elements -->
            <div class="d-flex align-items-center">

                <a href="index.php" class="btn btn-danger">Return to Home</a>
            </div>
            <!-- Right elements -->
        </div>
        <!-- Container wrapper -->
    </nav>
    <!-- Navbar -->

    <section class="vh-100 gradient-custom">
        <div class="container py-5 h-100">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-12 col-lg-9 col-xl-7">
                    <div class="card shadow-2-strong card-registration" style="border-radius: 15px;">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="mb-4 pb-2 pb-md-0 mb-md-5">Signup Form</h3>
                            <!-- Alert Banner -->
                            <?php if (isset($_COOKIE['err_message'])) {
                            ?>
                                <div class="alert <?= $_COOKIE['message_class']; ?> alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($_COOKIE['err_message']); ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                                    </button>
                                </div>
                            <?php
                            }
                            ?>
                            <!-- End Alert Banner -->
                            <form method="post" action="actions/signupAction.php">

                                <div class="row">
                                    <div class="col-md-12 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="text" id="fullname" name="fullname" class="form-control" value="<?php if (isset($_POST['fullname'])) echo $_POST['fullname']; ?>" required />
                                            <label class=" form-label" for="fullname">Full Name</label>
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <textarea class="form-control" placeholder="Enter your address here" id="address" name="address" style="height: 100px" value="<?php if (isset($_POST['address'])) echo $_POST['address']; ?>"></textarea>
                                            <label class="form-label" for="address">Address</label>
                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="email" id="emailAddress" name="emailAddress" class="form-control" value="<?php if (isset($_POST['emailAddress'])) echo $_POST['emailAddress']; ?>" required />
                                            <label class="form-label" for="emailAddress">Email</label>
                                        </div>

                                    </div>
                                    <div class="col-md-6 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="tel" id="phoneNumber" name="phoneNumber" class="form-control" value="<?php if (isset($_POST['phoneNumber'])) echo $_POST['phoneNumber']; ?>" required />
                                            <label class="form-label" for="phoneNumber">Phone Number</label>
                                        </div>

                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="text" id="messengerName" name="messengerName" class="form-control" value="<?php if (isset($_POST['messengerName'])) echo $_POST['messengerName']; ?>" required />
                                            <label class="form-label" for="messengerName">Facebook/Messenger Name</label>
                                        </div>

                                    </div>
                                    <div class="col-md-6 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="text" id="dLicense" name="dLicense" class="form-control" value="<?php if (isset($_POST['dLicense'])) echo $_POST['dLicense']; ?>" required />
                                            <label class="form-label" for="dLicense">Driver's License No.</label>
                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="password" id="password" name="password" class="form-control" required />
                                            <label class="form-label" for="password">Password</label>
                                        </div>

                                    </div>
                                    <div class="col-md-6 pb-2 mb-2">

                                        <div data-mdb-input-init class="form-outline">
                                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required />
                                            <label class="form-label" for="confirm_password">Confirm Password</label>
                                            <span id="message"></span>
                                        </div>

                                    </div>
                                </div>

                                <div class="row">
                                    <div class=" d-grid gap-2 pb-4">
                                        <input data-mdb-ripple-init class="btn btn-primary" type="submit" value="Submit" />
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">

                                        <div data-mdb-input-init class="form-outline">
                                            <div class="align-items-center text-center">
                                                Already have an account? <a href="login.php">Login Here</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="js/bootstrap.bundle.min.js" type="text/javascript"></script>
    <script src="js/jquery-3.7.1.min.js" type="text/javascript"></script>

    <script>
        $(document).ready(function() {
            // disable submit button until all required fields are filled
            $('form input[type="submit"]').prop('disabled', true);
            $('form input[required]').keyup(function() {
                var empty = false;
                $('form input[required]').each(function() {
                    if ($(this).val() == '') {
                        empty = true;
                    }
                });
                if (empty) {
                    $('form input[type="submit"]').prop('disabled', true);
                } else {
                    $('form input[type="submit"]').prop('disabled', false);
                }
            });

            // check password length and confirmation
            $('#password, #confirm_password').on('keyup', function() {
                if ($('#password').val().length >= 8 && $('#confirm_password').val().length >= 8) {
                    if ($('#password').val() == $('#confirm_password').val()) {
                        $('#message').html('Passwords match.').css('color', 'green');
                        $('form input[type="submit"]').prop('disabled', false);
                    } else {
                        $('#message').html('Passwords do not match.').css('color', 'red');
                        $('form input[type="submit"]').prop('disabled', true);
                    }
                } else {
                    $('#message').html('Password must be at least 8 characters long.').css('color', 'red');
                    $('form input[type="submit"]').prop('disabled', true);
                }
            });
        });
    </script>

</body>

</html>