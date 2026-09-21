<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';

Session::start();

if (Auth::check() && !Auth::isAdmin()) {
	header('Location: ' . FRONT_URL . '/index.php');
	exit;
}

$errors = [];
$activeTab = 'signin-2';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['login_btn'])) {
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';

		if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
			$errors[] = 'Please enter a valid email address and password.';
		} elseif (Auth::login($email, $password)) {
			if (Auth::isAdmin()) {
				Auth::logout();
				$errors[] = 'Administrators must use the admin login page.';
			} else {
				require_once __DIR__ . '/../core/Cart.php';
				Cart::mergeGuestCart($_SESSION['user_id']);
				header('Location: ' . FRONT_URL . '/index.php');
				exit;
			}
		} else {
			$errors[] = 'Invalid email or password, or the account is inactive.';
		}
	} elseif (isset($_POST['register_btn'])) {
		$activeTab = 'register-2';
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';

		if (strlen($name) < 2) {
			$errors[] = 'Please enter your full name.';
		}
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$errors[] = 'Please enter a valid email address.';
		}
		if (strlen($password) < 8) {
			$errors[] = 'Password must be at least 8 characters.';
		}

		if (empty($errors)) {
			if (Auth::register($name, $email, $password)) {
				Session::setFlash('success', 'Registration successful. You can now sign in.');
				header('Location: ' . FRONT_URL . '/login.php');
				exit;
			}
			$errors[] = 'An account with that email already exists.';
		}
	}
}

$page_title = "Login / Register - Molla eCommerce";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

        <main class="main">
            <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Login</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="login-page bg-image pt-8 pb-8 pt-md-12 pb-md-12 pt-lg-17 pb-lg-17" style="background-image: url('<?= FRONT_ASSETS ?>/images/backgrounds/login-bg.jpg')">
            	<div class="container">
            		<div class="form-box">
            			<div class="form-tab">
				<?php if ($success = Session::getFlash('success')): ?>
					<div class="alert alert-success" role="alert"><?= htmlspecialchars($success) ?></div>
				<?php endif; ?>
				<?php if (!empty($errors)): ?>
					<div class="alert alert-danger" role="alert">
						<?php foreach ($errors as $error): ?>
							<div><?= htmlspecialchars($error) ?></div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
	            			<ul class="nav nav-pills nav-fill" role="tablist">
							    <li class="nav-item">
							        <a class="nav-link <?= $activeTab === 'signin-2' ? 'active' : '' ?>" id="signin-tab-2" data-toggle="tab" href="#signin-2" role="tab" aria-controls="signin-2" aria-selected="<?= $activeTab === 'signin-2' ? 'true' : 'false' ?>">Sign In</a>
							    </li>
							    <li class="nav-item">
							        <a class="nav-link <?= $activeTab === 'register-2' ? 'active' : '' ?>" id="register-tab-2" data-toggle="tab" href="#register-2" role="tab" aria-controls="register-2" aria-selected="<?= $activeTab === 'register-2' ? 'true' : 'false' ?>">Register</a>
							    </li>
							</ul>
							<div class="tab-content">
							    <div class="tab-pane fade <?= $activeTab === 'signin-2' ? 'show active' : '' ?>" id="signin-2" role="tabpanel" aria-labelledby="signin-tab-2">
							    	<form action="<?= FRONT_URL ?>/login.php" method="POST">
							    		<div class="form-group">
							    			<label for="singin-email-2">Username or email address *</label>
							    			<input type="text" class="form-control" id="singin-email-2" name="email" required>
							    		</div><!-- End .form-group -->

							    		<div class="form-group">
							    			<label for="singin-password-2">Password *</label>
							    			<input type="password" class="form-control" id="singin-password-2" name="password" required>
							    		</div><!-- End .form-group -->

							    		<div class="form-footer">
							    			<button type="submit" name="login_btn" class="btn btn-outline-primary-2">
			                					<span>LOG IN</span>
			            						<i class="icon-long-arrow-right"></i>
			                				</button>

			                				<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="signin-remember-2" name="remember">
												<label class="custom-control-label" for="signin-remember-2">Remember Me</label>
											</div><!-- End .custom-checkbox -->

											<a href="#" class="forgot-link">Forgot Your Password?</a>
							    		</div><!-- End .form-footer -->
							    	</form>
							    </div><!-- .End .tab-pane -->

							    <div class="tab-pane fade <?= $activeTab === 'register-2' ? 'show active' : '' ?>" id="register-2" role="tabpanel" aria-labelledby="register-tab-2">
							    	<form action="<?= FRONT_URL ?>/login.php" method="POST">
							    		<div class="form-group">
							    			<label for="register-name-2">Full Name *</label>
							    			<input type="text" class="form-control" id="register-name-2" name="name" required>
							    		</div><!-- End .form-group -->

							    		<div class="form-group">
							    			<label for="register-email-2">Your email address *</label>
							    			<input type="email" class="form-control" id="register-email-2" name="email" required>
							    		</div><!-- End .form-group -->

							    		<div class="form-group">
							    			<label for="register-password-2">Password *</label>
							    			<input type="password" class="form-control" id="register-password-2" name="password" required>
							    		</div><!-- End .form-group -->

							    		<div class="form-footer">
							    			<button type="submit" name="register_btn" class="btn btn-outline-primary-2">
			                					<span>SIGN UP</span>
			            						<i class="icon-long-arrow-right"></i>
			                				</button>

			                				<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="register-policy-2" required>
												<label class="custom-control-label" for="register-policy-2">I agree to the <a href="#">privacy policy</a> *</label>
											</div><!-- End .custom-checkbox -->
							    		</div><!-- End .form-footer -->
							    	</form>
							    </div><!-- .End .tab-pane -->
							</div><!-- End .tab-content -->
						</div><!-- End .form-tab -->
            		</div><!-- End .form-box -->
            	</div><!-- End .container -->
            </div><!-- End .login-page section-bg -->
        </main><!-- End .main -->

<?php
require_once __DIR__ . '/../includes/footer.php';
?>