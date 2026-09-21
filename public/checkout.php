<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Cart.php';

Session::start();

// Ensure customer is logged in
if (!Auth::check()) {
    Session::setFlash('error', 'Please sign in to proceed with checkout.');
    header('Location: ' . FRONT_URL . '/login.php');
    exit;
}

$cartItems = Cart::getItems();
if (empty($cartItems)) {
    header('Location: ' . FRONT_URL . '/cart.php');
    exit;
}

$cartTotal = Cart::getTotal();
$userData = Auth::user();
$checkoutError = Session::getFlash('error');
if (($_GET['payment'] ?? '') === 'cancelled') {
    $checkoutError = 'Payment was cancelled. Your cart is still available.';
}

$page_title = "Checkout - Molla eCommerce";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

        <main class="main">
        	<div class="page-header text-center" style="background-image: url('<?= FRONT_ASSETS ?>/images/page-header-bg.jpg')">
        		<div class="container">
        			<h1 class="page-title">Checkout<span>Shop</span></h1>
        		</div><!-- End .container -->
        	</div><!-- End .page-header -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/shop.php">Shop</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Checkout</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
            	<div class="checkout">
	                <div class="container">
                        <div id="checkout-alert" class="alert <?= $checkoutError ? 'alert-danger' : 'd-none' ?> text-white mb-3" role="alert"><?= htmlspecialchars($checkoutError ?? '') ?></div>

            			<div class="row">
            		        <div class="col-lg-9">
            			        <h2 class="checkout-title">Billing & Shipping Details</h2>
                                <form id="checkout-form">
            				        <div class="row">
            					        <div class="col-sm-6">
            						        <label>Full Name *</label>
            						        <input type="text" class="form-control" id="shipping_name" value="<?= htmlspecialchars($userData['name']) ?>" required>
            					        </div><!-- End .col-sm-6 -->

            					        <div class="col-sm-6">
            						        <label>Email Address *</label>
            						        <input type="email" class="form-control" id="shipping_email" value="<?= htmlspecialchars($userData['email']) ?>" required>
            					        </div><!-- End .col-sm-6 -->
            				        </div><!-- End .row -->

            						<label>Street Address *</label>
            						<input type="text" class="form-control" id="shipping_street" placeholder="House number and Street name" required>

            						<div class="row">
    		        					<div class="col-sm-6">
    		        						<label>Town / City *</label>
    		        						<input type="text" class="form-control" id="shipping_city" required>
    		        					</div><!-- End .col-sm-6 -->

    		        					<div class="col-sm-6">
    		        						<label>State / Province *</label>
    		        						<input type="text" class="form-control" id="shipping_state" required>
    		        					</div><!-- End .col-sm-6 -->
    		        				</div><!-- End .row -->

    		        				<div class="row">
    		        					<div class="col-sm-6">
    		        						<label>Postcode / ZIP *</label>
    		        						<input type="text" class="form-control" id="shipping_zip" required>
    		        					</div><!-- End .col-sm-6 -->

    		        					<div class="col-sm-6">
    		        						<label>Phone *</label>
    		        						<input type="tel" class="form-control" id="shipping_phone" required>
    		        					</div><!-- End .col-sm-6 -->
    		        				</div><!-- End .row -->
                                </form>
            		        </div><!-- End .col-lg-9 -->

            		        <aside class="col-lg-3">
            			        <div class="summary">
            				        <h3 class="summary-title">Your Order</h3>

            				        <table class="table table-summary">
            					        <thead>
            						        <tr>
            							        <th>Product</th>
            							        <th>Total</th>
            						        </tr>
            					        </thead>

            					        <tbody>
                                            <?php foreach ($cartItems as $item): ?>
                                                <tr>
                                                    <td><a href="#"><?= htmlspecialchars($item['name']) ?> (<?= $item['quantity'] ?>x)</a></td>
                                                    <td>$<?= number_format((float)$item['price'] * (int)$item['quantity'], 2) ?></td>
                                                </tr>
                                            <?php endforeach; ?>

            						        <tr class="summary-subtotal">
            							        <td>Subtotal:</td>
            							        <td>$<?= number_format($cartTotal, 2) ?></td>
            						        </tr>
            						        <tr class="summary-total">
            							        <td>Total:</td>
            							        <td>$<?= number_format($cartTotal, 2) ?></td>
            						        </tr>
            					        </tbody>
            				        </table><!-- End .table table-summary -->

                                    <div class="payment-methods mt-4">
                                        <h4 class="mb-3">Payment Method</h4>
                                        <div class="p-3 bg-light rounded border text-center">
                                            <p class="text-sm mb-3">Pay securely with Stripe Test Mode</p>
                                            <button type="button" id="stripe-checkout-button" class="btn btn-primary btn-block">Pay with Stripe</button>
                                            <small class="d-block mt-2 text-muted">No real charge will be made in test mode.</small>
                                            <hr>
                                            <p class="text-sm mb-2">Or pay when your order arrives.</p>
                                            <button type="button" id="cod-button" class="btn btn-outline-dark btn-block">Cash on Delivery</button>
                                        </div>
                                    </div><!-- End .payment-methods -->
            			        </div><!-- End .summary -->
            		        </aside><!-- End .col-lg-3 -->
            			</div><!-- End .row -->
	                </div><!-- End .container -->
                </div><!-- End .checkout -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

<script>
    const stripeButton = document.getElementById('stripe-checkout-button');
    const checkoutForm = document.getElementById('checkout-form');
    const alertBox = document.getElementById('checkout-alert');
    const codButton = document.getElementById('cod-button');

    function shippingPayload() {
        const fields = ['name', 'email', 'street', 'city', 'state', 'zip', 'phone'];
        const payload = {};
        fields.forEach(function (field) {
            payload[field] = document.getElementById('shipping_' + field).value.trim();
        });
        return payload;
    }

    function showCheckoutError(message) {
        alertBox.className = 'alert alert-danger text-white mb-3';
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
    }

    stripeButton.addEventListener('click', function () {
        if (!checkoutForm.checkValidity()) {
            checkoutForm.reportValidity();
            return;
        }

        stripeButton.disabled = true;
        stripeButton.textContent = 'Opening Stripe...';

        const payload = shippingPayload();

        fetch('<?= FRONT_URL ?>/create-stripe-checkout.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        })
        .then(function (response) { return response.json(); })
        .then(function (result) {
            if (!result.success) {
                throw new Error(result.message || 'Unable to start Stripe checkout.');
            }
            window.location.href = result.url;
        })
        .catch(function (error) {
            showCheckoutError(error.message);
            stripeButton.disabled = false;
            stripeButton.textContent = 'Pay with Stripe';
        });
    });

    codButton.addEventListener('click', function () {
        if (!checkoutForm.checkValidity()) {
            checkoutForm.reportValidity();
            return;
        }

        codButton.disabled = true;
        codButton.textContent = 'Placing order...';
        fetch('<?= FRONT_URL ?>/process-cod.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(shippingPayload())
        })
        .then(function (response) { return response.json(); })
        .then(function (result) {
            if (!result.success) {
                throw new Error(result.message || 'Unable to place COD order.');
            }
            window.location.href = result.redirect_url;
        })
        .catch(function (error) {
            showCheckoutError(error.message);
            codButton.disabled = false;
            codButton.textContent = 'Cash on Delivery';
        });
    });
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
