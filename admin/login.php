<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';

Session::start();

// If already logged in as admin, redirect to dashboard
if (Auth::isAdmin()) {
    header('Location: ' . APP_URL . '/admin/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        $error = 'Security check failed. Please refresh the page and try again.';
    } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        if (Auth::login($email, $password)) {
            if (Auth::isAdmin()) {
                Session::setFlash('success', 'Welcome back, Administrator!');
                header('Location: ' . APP_URL . '/admin/index.php');
                exit;
            } else {
                Auth::logout();
                $error = 'Access denied. Administrator privileges required.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
    }
}

$page_title = 'Admin Login - Material Dashboard';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="<?= ADMIN_ASSETS ?>/img/apple-icon.png">
  <link rel="icon" type="image/png" href="<?= ADMIN_ASSETS ?>/img/favicon.png">
  <title><?= $page_title ?></title>
  <!-- Fonts and icons -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="<?= ADMIN_ASSETS ?>/css/nucleo-icons.css" rel="stylesheet" />
  <link href="<?= ADMIN_ASSETS ?>/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link id="pagestyle" href="<?= ADMIN_ASSETS ?>/css/material-dashboard.css?v=3.2.0" rel="stylesheet" />
</head>

<body class="bg-gray-200">
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg blur border-radius-xl top-0 z-index-3 shadow position-absolute my-3 py-2 start-0 end-0 mx-4">
          <div class="container-fluid ps-2 pe-0">
            <a class="navbar-brand font-weight-bolder ms-lg-0 ms-3" href="<?= APP_URL ?>">
              Molla eCommerce Store
            </a>
            <ul class="navbar-nav d-lg-flex d-none">
              <li class="nav-item">
                <a href="<?= APP_URL ?>" class="btn btn-sm mb-0 me-1 bg-gradient-dark">Back to Store</a>
              </li>
            </ul>
          </div>
        </nav>
        <!-- End Navbar -->
      </div>
    </div>
  </div>
  <main class="main-content mt-0">
    <div class="page-header align-items-start min-vh-100" style="background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1950&q=80');">
      <span class="mask bg-gradient-dark opacity-6"></span>
      <div class="container my-auto">
        <div class="row">
          <div class="col-lg-4 col-md-8 col-12 mx-auto">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                  <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Admin Sign In</h4>
                  <p class="text-white text-sm text-center mb-0">Protected Dashboard Access</p>
                </div>
              </div>
              <div class="card-body">
                <?php if ($error): ?>
                  <div class="alert alert-danger text-white font-weight-normal mb-3" role="alert">
                    <?= htmlspecialchars($error) ?>
                  </div>
                <?php endif; ?>

                <?php if ($flashError = Session::getFlash('error')): ?>
                  <div class="alert alert-danger text-white font-weight-normal mb-3" role="alert">
                    <?= htmlspecialchars($flashError) ?>
                  </div>
                <?php endif; ?>

                <form role="form" class="text-start" action="" method="POST">
                  <?= Csrf::field() ?>
                  <div class="input-group input-group-outline my-3">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                  </div>
                  <div class="input-group input-group-outline mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                  </div>
                  <div class="form-check form-switch d-flex align-items-center mb-3">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label mb-0 ms-3" for="rememberMe">Remember me</label>
                  </div>
                  <div class="text-center">
                    <button type="submit" class="btn bg-gradient-dark w-100 my-4 mb-2">Sign in</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
      <footer class="footer position-absolute bottom-2 py-2 w-100">
        <div class="container">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-12 col-md-6 my-auto">
              <div class="copyright text-center text-sm text-white text-lg-start">
                © <script>document.write(new Date().getFullYear())</script>,
                E-Commerce Admin Panel.
              </div>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </main>
  <!-- Core JS Files -->
  <script src="<?= ADMIN_ASSETS ?>/js/core/popper.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/core/bootstrap.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>
