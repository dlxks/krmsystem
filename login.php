<?php

include("conn.php");

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

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
                            <h3 class="mb-4 pb-2 pb-md-0 mb-md-5">Login</h3>
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

                            <form method="post" action="actions/loginAction.php">
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="login_wrap">
                                            <div class="col-md-12 col-sm-6">
                                                <div class="row">
                                                    <div class="col-md-12 pb-2 mb-2">
                                                        <input type="email" class="form-control" name="email" placeholder="Email address*" required>
                                                        <label class=" form-label" for="email">Email</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 pb-2 mb-2">
                                                        <input type="password" class="form-control" name="password" placeholder="Password*" required>
                                                        <label class=" form-label" for="password">Password</label>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="form-group d-grid gap-2 pb-4">
                                                        <input type="submit" name="login" value="Login" class="btn btn-primary">
                                                        <a href="admin/" class="btn btn-info text-light">Admin Login</a>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div data-mdb-input-init class="form-outline">
                                                            <div class="align-items-center text-center">
                                                                Don't have an account? <a href="signup.php">Signup Here</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
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

</body>

</html>