<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/Cart.php';
require_once __DIR__ . '/../core/ProductImage.php';

Session::start();

// Handle Cart Actions
$action = $_GET['action'] ?? '';
$productId = (int)($_GET['id'] ?? $_POST['product_id'] ?? 0);

if ($action === 'add' && $productId > 0) {
	$qty = max(1, (int)($_GET['qty'] ?? $_POST['qty'] ?? $_POST['quantity'] ?? 1));
	$added = Cart::add($productId, $qty);

	if (strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest') {
		$cartItems = Cart::getItems();
		$cartCount = array_sum(array_map(static function ($item) {
			return (int)$item['quantity'];
		}, $cartItems));
		$responseItems = array_map(static function ($item) {
			$images = ProductImage::available((int)$item['product_id'], $item['image'] ?? null);
			return [
				'product_id' => (int)$item['product_id'],
				'name' => (string)$item['name'],
				'quantity' => (int)$item['quantity'],
				'price' => (float)$item['price'],
				'image' => !empty($images)
					? ProductImage::url((int)$item['product_id'], $images[0])
					: FRONT_ASSETS . '/images/demos/demo-4/products/product-1.jpg',
			];
		}, $cartItems);

		if (!$added) {
			http_response_code(409);
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'success' => $added,
			'count' => $cartCount,
			'total' => Cart::getTotal(),
			'items' => $responseItems,
		], JSON_INVALID_UTF8_SUBSTITUTE);
		exit;
	}

    header('Location: ' . FRONT_URL . '/cart.php');
    exit;
}

if ($action === '' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart']) && $productId > 0) {
	$qty = max(1, (int)($_POST['quantity'] ?? 1));
	Cart::add($productId, $qty);
	header('Location: ' . FRONT_URL . '/cart.php');
	exit;
}

if ($action === 'remove' && $productId > 0) {
    Cart::remove($productId);
    header('Location: ' . FRONT_URL . '/cart.php');
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['qty'] ?? [];
    foreach ($quantities as $prodId => $qty) {
        Cart::update((int)$prodId, (int)$qty);
    }
    header('Location: ' . FRONT_URL . '/cart.php');
    exit;
}

$cartItems = Cart::getItems();
$cartTotal = Cart::getTotal();

$page_title = "Shopping Cart - Molla eCommerce";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

        <main class="main">
        	<div class="page-header text-center" style="background-image: url('<?= FRONT_ASSETS ?>/images/page-header-bg.jpg')">
        		<div class="container">
        			<h1 class="page-title">Shopping Cart<span>Shop</span></h1>
        		</div><!-- End .container -->
        	</div><!-- End .page-header -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <div class="container">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/index.php">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/shop.php">Shop</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
                    </ol>
                </div><!-- End .container -->
            </nav><!-- End .breadcrumb-nav -->

            <div class="page-content">
            	<div class="cart">
	                <div class="container">
                        <form action="<?= FRONT_URL ?>/cart.php?action=update" method="POST">
    	                	<div class="row">
    	                		<div class="col-lg-9">
    	                			<table class="table table-cart table-mobile">
    									<thead>
    										<tr>
    											<th>Product</th>
    											<th>Price</th>
    											<th>Quantity</th>
    											<th>Total</th>
    											<th></th>
    										</tr>
    									</thead>

    									<tbody>
                                            <?php if (empty($cartItems)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-5">
                                                        <p class="mb-3">Your shopping cart is empty.</p>
                                                        <a href="<?= FRONT_URL ?>/shop.php" class="btn btn-outline-primary-2"><span>GO TO SHOP</span><i class="icon-long-arrow-right"></i></a>
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($cartItems as $item): ?>
                                                    <?php
													$itemImages = ProductImage::available((int)$item['product_id'], $item['image'] ?? null);
													$imgPath = !empty($itemImages)
														? ProductImage::url((int)$item['product_id'], $itemImages[0])
														: 'https://placehold.co/120x120?text=Product';
                                                    $itemSubtotal = (float)$item['price'] * (int)$item['quantity'];
                                                    ?>
                                                    <tr>
                                                        <td class="product-col">
                                                            <div class="product">
                                                                <figure class="product-media">
                                                                    <a href="<?= FRONT_URL ?>/product.php?id=<?= $item['product_id'] ?>">
                                                                        <img src="<?= htmlspecialchars($imgPath) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                                                    </a>
                                                                </figure>

                                                                <h3 class="product-title">
                                                                    <a href="<?= FRONT_URL ?>/product.php?id=<?= $item['product_id'] ?>"><?= htmlspecialchars($item['name']) ?></a>
                                                                </h3><!-- End .product-title -->
                                                            </div><!-- End .product -->
                                                        </td>
                                                        <td class="price-col">$<?= number_format((float)$item['price'], 2) ?></td>
                                                        <td class="quantity-col">
                                                            <div class="cart-product-quantity">
                                                                <input type="number" class="form-control" name="qty[<?= $item['product_id'] ?>]" value="<?= (int)$item['quantity'] ?>" min="1" max="<?= max(1, (int)$item['stock']) ?>" step="1" data-decimals="0" required>
                                                            </div><!-- End .cart-product-quantity -->
                                                        </td>
                                                        <td class="total-col">$<?= number_format($itemSubtotal, 2) ?></td>
                                                        <td class="remove-col">
                                                            <a href="<?= FRONT_URL ?>/cart.php?action=remove&id=<?= $item['product_id'] ?>" class="btn-remove" title="Remove Product"><i class="icon-close"></i></a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
    									</tbody>
    								</table><!-- End .table table-wishlist -->

                                    <?php if (!empty($cartItems)): ?>
    	                			<div class="cart-bottom">
    			            			<div class="cart-discount">
    			            				<div class="input-group">
    				        					<input type="text" class="form-control" placeholder="coupon code">
    				        					<div class="input-group-append">
    												<button class="btn btn-outline-primary-2" type="button"><i class="icon-long-arrow-right"></i></button>
    											</div><!-- .End .input-group-append -->
    			        					</div><!-- End .input-group -->
    			            			</div><!-- End .cart-discount -->

    			            			<button type="submit" class="btn btn-outline-dark-2"><span>UPDATE CART</span><i class="icon-refresh"></i></button>
    		            			</div><!-- End .cart-bottom -->
                                    <?php endif; ?>
    	                		</div><!-- End .col-lg-9 -->

    	                		<aside class="col-lg-3">
    	                			<div class="summary summary-cart">
    	                				<h3 class="summary-title">Cart Total</h3><!-- End .summary-title -->

    	                				<table class="table table-summary">
    	                					<tbody>
    	                						<tr class="summary-subtotal">
    	                							<td>Subtotal:</td>
    	                							<td>$<?= number_format($cartTotal, 2) ?></td>
    	                						</tr><!-- End .summary-subtotal -->
    	                						<tr class="summary-shipping">
    	                							<td>Shipping:</td>
    	                							<td>&nbsp;</td>
    	                						</tr>

    	                						<tr class="summary-shipping-row">
    	                							<td>
    													<div class="custom-control custom-radio">
    														<input type="radio" id="free-shipping" name="shipping" class="custom-control-input" checked>
    														<label class="custom-control-label" for="free-shipping">Free Shipping</label>
    													</div><!-- End .custom-control -->
    	                							</td>
    	                							<td>$0.00</td>
    	                						</tr><!-- End .summary-shipping-row -->

    	                						<tr class="summary-shipping-row">
    	                							<td>
    	                								<div class="custom-control custom-radio">
    														<input type="radio" id="standart-shipping" name="shipping" class="custom-control-input">
    														<label class="custom-control-label" for="standart-shipping">Standard:</label>
    													</div><!-- End .custom-control -->
    	                							</td>
    	                							<td>$10.00</td>
    	                						</tr><!-- End .summary-shipping-row -->

    	                						<tr class="summary-shipping-row">
    	                							<td>
    	                								<div class="custom-control custom-radio">
    														<input type="radio" id="express-shipping" name="shipping" class="custom-control-input">
    														<label class="custom-control-label" for="express-shipping">Express Courier:</label>
    													</div><!-- End .custom-control -->
    	                							</td>
    	                							<td>$20.00</td>
    	                						</tr><!-- End .summary-shipping-row -->

    	                						<tr class="summary-shipping-estimate">
    	                							<td>Estimate for Your Country<br> <a href="<?= FRONT_URL ?>/checkout.php">Change address</a></td>
    	                							<td>&nbsp;</td>
    	                						</tr><!-- End .summary-shipping-estimate -->

    	                						<tr class="summary-total">
    	                							<td>Total:</td>
    	                							<td>$<?= number_format($cartTotal, 2) ?></td>
    	                						</tr><!-- End .summary-total -->
    	                					</tbody>
    	                				</table><!-- End .table table-summary -->

    	                				<a href="<?= FRONT_URL ?>/checkout.php" class="btn btn-outline-primary-2 btn-order btn-block">PROCEED TO CHECKOUT</a>
    	                			</div><!-- End .summary -->

    		            			<a href="<?= FRONT_URL ?>/shop.php" class="btn btn-outline-dark-2 btn-block mb-3"><span>CONTINUE SHOPPING</span><i class="icon-refresh"></i></a>
    	                		</aside><!-- End .col-lg-3 -->
    	                	</div><!-- End .row -->
                        </form>
	                </div><!-- End .container -->
                </div><!-- End .cart -->
            </div><!-- End .page-content -->
        </main><!-- End .main -->

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
