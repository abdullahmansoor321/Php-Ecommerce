<?php
/**
 * Shared storefront footer.
 *
 * Renders the CTA banner, footer link columns and payment strip, then closes
 * the .page-wrapper opened in includes/header.php and outputs the global
 * overlays (mobile menu, auth modal, newsletter popup) and page scripts.
 */
$footer_year = date('Y');

// Data-driven "Shop by Category" column. Falls back to a static list when the
// database is unavailable (same defensive pattern as includes/navbar.php).
$footer_categories = [];
try {
    require_once __DIR__ . '/../core/Database.php';
    $footer_categories = Database::getInstance()->fetchAll(
        "SELECT name, slug FROM categories WHERE status = 1 ORDER BY name ASC LIMIT 6"
    );
} catch (Throwable $e) {
    $footer_categories = [];
}
?>
        <footer class="footer">
            <div class="cta bg-image bg-dark pt-4 pb-5 mb-0" style="background-image: url(<?= FRONT_ASSETS ?>/images/demos/demo-4/bg-5.jpg);">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-sm-10 col-md-8 col-lg-6">
                            <div class="cta-heading text-center">
                                <h3 class="cta-title text-white">Get The Latest Deals</h3><!-- End .cta-title -->
                                <p class="cta-desc text-white">and receive <span class="font-weight-normal">$20 coupon</span> for first shopping</p><!-- End .cta-desc -->
                            </div><!-- End .text-center -->
                        
                            <form action="<?= FRONT_URL ?>/shop.php">
                                <div class="input-group input-group-round">
                                    <input type="email" class="form-control form-control-white" placeholder="Enter your Email Address" aria-label="Email Adress" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="submit"><span>Subscribe</span><i class="icon-long-arrow-right"></i></button>
                                    </div><!-- .End .input-group-append -->
                                </div><!-- .End .input-group -->
                            </form>
                        </div><!-- End .col-sm-10 col-md-8 col-lg-6 -->
                    </div><!-- End .row -->
                </div><!-- End .container -->
            </div><!-- End .cta -->
        	<div class="footer-middle">
	            <div class="container">
	            	<div class="row">
	            		<div class="col-sm-6 col-lg-3">
	            			<div class="widget widget-about">
	            				<img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/logo-footer.png" class="footer-logo" alt="Footer Logo" width="105" height="25">
	            				<p>Your one-stop shop for laptops, cameras, phones, audio and more &mdash; curated quality with fast, reliable delivery.</p>

	            				<div class="widget-call">
                                    <i class="icon-phone"></i>
                                    Got Question? Call us 24/7
                                    <a href="tel:#">+0123 456 789</a>         
                                </div><!-- End .widget-call -->
	            			</div><!-- End .widget about-widget -->
	            		</div><!-- End .col-sm-6 col-lg-3 -->

	            		<div class="col-sm-6 col-lg-3">
	            			<div class="widget">
	            				<h4 class="widget-title">Useful Links</h4><!-- End .widget-title -->

				<ul class="widget-list">
					<li><a href="<?= FRONT_URL ?>/index.php">Home</a></li>
					<li><a href="<?= FRONT_URL ?>/shop.php">Shop All Products</a></li>
					<li><a href="<?= FRONT_URL ?>/cart.php">Shopping Cart</a></li>
					<li><a href="<?= FRONT_URL ?>/checkout.php">Checkout</a></li>
					<li><a href="<?= FRONT_URL ?>/login.php">Login / Register</a></li>
				</ul><!-- End .widget-list -->
	            			</div><!-- End .widget -->
	            		</div><!-- End .col-sm-6 col-lg-3 -->

	            		<div class="col-sm-6 col-lg-3">
	            			<div class="widget">
	            				<h4 class="widget-title">Shop by Category</h4><!-- End .widget-title -->

				<ul class="widget-list">
					<?php if (!empty($footer_categories)): ?>
						<?php foreach ($footer_categories as $footer_category): ?>
							<li><a href="<?= FRONT_URL ?>/shop.php?category=<?= htmlspecialchars($footer_category['slug']) ?>"><?= htmlspecialchars($footer_category['name']) ?></a></li>
						<?php endforeach; ?>
					<?php else: ?>
						<li><a href="<?= FRONT_URL ?>/shop.php?category=computers-laptops">Computers &amp; Laptops</a></li>
						<li><a href="<?= FRONT_URL ?>/shop.php?category=digital-cameras">Digital Cameras</a></li>
						<li><a href="<?= FRONT_URL ?>/shop.php?category=smart-phones">Smart Phones</a></li>
						<li><a href="<?= FRONT_URL ?>/shop.php?category=televisions">Televisions</a></li>
						<li><a href="<?= FRONT_URL ?>/shop.php?category=audio">Audio &amp; Speakers</a></li>
					<?php endif; ?>
				</ul><!-- End .widget-list -->
	            			</div><!-- End .widget -->
	            		</div><!-- End .col-sm-6 col-lg-3 -->

	            		<div class="col-sm-6 col-lg-3">
	            			<div class="widget">
	            				<h4 class="widget-title">My Account</h4><!-- End .widget-title -->

				<ul class="widget-list">
					<li><a href="<?= FRONT_URL ?>/login.php">Sign In / Register</a></li>
					<li><a href="<?= FRONT_URL ?>/cart.php">View Cart</a></li>
					<li><a href="<?= FRONT_URL ?>/checkout.php">Checkout</a></li>
					<li><a href="<?= FRONT_URL ?>/order-confirmation.php">Order Confirmation</a></li>
					<li><a href="<?= FRONT_URL ?>/logout.php">Logout</a></li>
				</ul><!-- End .widget-list -->
	            			</div><!-- End .widget -->
	            		</div><!-- End .col-sm-6 col-lg-3 -->
	            	</div><!-- End .row -->
	            </div><!-- End .container -->
	        </div><!-- End .footer-middle -->

	        <div class="footer-bottom">
	        	<div class="container">
	        		<p class="footer-copyright">Copyright &copy; <?= $footer_year ?> Molla Store. All Rights Reserved.</p><!-- End .footer-copyright -->
	        		<figure class="footer-payments">
	        			<img src="<?= FRONT_ASSETS ?>/images/payments.png" alt="Payment methods" width="272" height="20">
	        		</figure><!-- End .footer-payments -->
	        	</div><!-- End .container -->
	        </div><!-- End .footer-bottom -->
        </footer><!-- End .footer -->
    </div><!-- End .page-wrapper -->
    <button id="scroll-top" title="Back to Top"><i class="icon-arrow-up"></i></button>

    <!-- Mobile Menu -->
    <div class="mobile-menu-overlay"></div><!-- End .mobil-menu-overlay -->

    <div class="mobile-menu-container mobile-menu-light">
        <div class="mobile-menu-wrapper">
            <span class="mobile-menu-close"><i class="icon-close"></i></span>
            
            <form action="<?= FRONT_URL ?>/shop.php" method="get" class="mobile-search">
                <label for="mobile-search" class="sr-only">Search</label>
                <input type="search" class="form-control" name="mobile-search" id="mobile-search" placeholder="Search in..." required>
                <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
            </form>

            <ul class="nav nav-pills-mobile nav-border-anim" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="mobile-menu-link" data-toggle="tab" href="#mobile-menu-tab" role="tab" aria-controls="mobile-menu-tab" aria-selected="true">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="mobile-cats-link" data-toggle="tab" href="#mobile-cats-tab" role="tab" aria-controls="mobile-cats-tab" aria-selected="false">Categories</a>
                </li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="mobile-menu-tab" role="tabpanel" aria-labelledby="mobile-menu-link">
                    <nav class="mobile-nav">
                        <ul class="mobile-menu">
                        <li class="active">
                            <a href="<?= FRONT_URL ?>/index.php">Home</a>
                        </li>
                        <li>
                            <a href="<?= FRONT_URL ?>/shop.php">Shop</a>

                            <ul>
                                <li><a href="<?= FRONT_URL ?>/shop.php?cat=computers-laptops">Computers &amp; Laptops</a></li>
                                <li><a href="<?= FRONT_URL ?>/shop.php?cat=digital-cameras">Digital Cameras</a></li>
                                <li><a href="<?= FRONT_URL ?>/shop.php?cat=smart-phones">Smart Phones</a></li>
                                <li><a href="<?= FRONT_URL ?>/shop.php?cat=televisions">Televisions</a></li>
                                <li><a href="<?= FRONT_URL ?>/shop.php?cat=audio">Audio &amp; Speakers</a></li>
                            </ul>
                        </li>
                        <li>
                            <a href="<?= FRONT_URL ?>/cart.php">Cart</a>
                        </li>
                        <li>
                            <a href="<?= FRONT_URL ?>/checkout.php">Checkout</a>
                        </li>
                        <li>
                            <a href="<?= FRONT_URL ?>/login.php">Login / Register</a>
                        </li>
                        <li>
                            <a href="<?= FRONT_URL ?>/order-confirmation.php">Order Confirmation</a>
                        </li>
                    </ul>
                    </nav><!-- End .mobile-nav -->
                </div><!-- .End .tab-pane -->
                <div class="tab-pane fade" id="mobile-cats-tab" role="tabpanel" aria-labelledby="mobile-cats-link">
                    <nav class="mobile-cats-nav">
                        <ul class="mobile-cats-menu">
                            <li><a class="mobile-cats-lead" href="<?= FRONT_URL ?>/shop.php">All Products</a></li>
                            <li><a href="<?= FRONT_URL ?>/shop.php?cat=computers-laptops">Computers &amp; Laptops</a></li>
                            <li><a href="<?= FRONT_URL ?>/shop.php?cat=digital-cameras">Digital Cameras</a></li>
                            <li><a href="<?= FRONT_URL ?>/shop.php?cat=smart-phones">Smart Phones</a></li>
                            <li><a href="<?= FRONT_URL ?>/shop.php?cat=televisions">Televisions</a></li>
                            <li><a href="<?= FRONT_URL ?>/shop.php?cat=audio">Audio &amp; Speakers</a></li>
                        </ul><!-- End .mobile-cats-menu -->
                    </nav><!-- End .mobile-cats-nav -->
                </div><!-- .End .tab-pane -->
            </div><!-- End .tab-content -->

            <div class="social-icons">
                <a href="#" class="social-icon" target="_blank" title="Facebook"><i class="icon-facebook-f"></i></a>
                <a href="#" class="social-icon" target="_blank" title="Twitter"><i class="icon-twitter"></i></a>
                <a href="#" class="social-icon" target="_blank" title="Instagram"><i class="icon-instagram"></i></a>
                <a href="#" class="social-icon" target="_blank" title="Youtube"><i class="icon-youtube"></i></a>
            </div><!-- End .social-icons -->
        </div><!-- End .mobile-menu-wrapper -->
    </div><!-- End .mobile-menu-container -->

    <!-- Sign in / Register Modal -->
    <div class="modal fade" id="signin-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="icon-close"></i></span>
                    </button>

                    <div class="form-box">
                        <div class="form-tab">
                            <ul class="nav nav-pills nav-fill nav-border-anim" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="signin-tab" data-toggle="tab" href="#signin" role="tab" aria-controls="signin" aria-selected="true">Sign In</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="register-tab" data-toggle="tab" href="#register" role="tab" aria-controls="register" aria-selected="false">Register</a>
                                </li>
                            </ul>
                            <div class="tab-content" id="tab-content-5">
                                <div class="tab-pane fade show active" id="signin" role="tabpanel" aria-labelledby="signin-tab">
                                    <form action="<?= FRONT_URL ?>/login.php" method="POST">
                                        <div class="form-group">
                                            <label for="singin-email">Username or email address *</label>
                                            <input type="text" class="form-control" id="singin-email" name="singin-email" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-group">
                                            <label for="singin-password">Password *</label>
                                            <input type="password" class="form-control" id="singin-password" name="singin-password" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-footer">
                                            <button type="submit" class="btn btn-outline-primary-2">
                                                <span>LOG IN</span>
                                                <i class="icon-long-arrow-right"></i>
                                            </button>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="signin-remember">
                                                <label class="custom-control-label" for="signin-remember">Remember Me</label>
                                            </div><!-- End .custom-checkbox -->

                                            <a href="#" class="forgot-link">Forgot Your Password?</a>
                                        </div><!-- End .form-footer -->
                                    </form>
                                    <div class="form-choice">
                                        <p class="text-center">or sign in with</p>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login btn-g">
                                                    <i class="icon-google"></i>
                                                    Login With Google
                                                </a>
                                            </div><!-- End .col-6 -->
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login btn-f">
                                                    <i class="icon-facebook-f"></i>
                                                    Login With Facebook
                                                </a>
                                            </div><!-- End .col-6 -->
                                        </div><!-- End .row -->
                                    </div><!-- End .form-choice -->
                                </div><!-- .End .tab-pane -->
                                <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                                    <form action="<?= FRONT_URL ?>/login.php" method="POST">
                                        <div class="form-group">
                                            <label for="register-email">Your email address *</label>
                                            <input type="email" class="form-control" id="register-email" name="register-email" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-group">
                                            <label for="register-password">Password *</label>
                                            <input type="password" class="form-control" id="register-password" name="register-password" required>
                                        </div><!-- End .form-group -->

                                        <div class="form-footer">
                                            <button type="submit" class="btn btn-outline-primary-2">
                                                <span>SIGN UP</span>
                                                <i class="icon-long-arrow-right"></i>
                                            </button>

                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="register-policy" required>
                                                <label class="custom-control-label" for="register-policy">I agree to the <a href="#">privacy policy</a> *</label>
                                            </div><!-- End .custom-checkbox -->
                                        </div><!-- End .form-footer -->
                                    </form>
                                    <div class="form-choice">
                                        <p class="text-center">or sign in with</p>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login btn-g">
                                                    <i class="icon-google"></i>
                                                    Login With Google
                                                </a>
                                            </div><!-- End .col-6 -->
                                            <div class="col-sm-6">
                                                <a href="#" class="btn btn-login  btn-f">
                                                    <i class="icon-facebook-f"></i>
                                                    Login With Facebook
                                                </a>
                                            </div><!-- End .col-6 -->
                                        </div><!-- End .row -->
                                    </div><!-- End .form-choice -->
                                </div><!-- .End .tab-pane -->
                            </div><!-- End .tab-content -->
                        </div><!-- End .form-tab -->
                    </div><!-- End .form-box -->
                </div><!-- End .modal-body -->
            </div><!-- End .modal-content -->
        </div><!-- End .modal-dialog -->
    </div><!-- End .modal -->

    <div class="container newsletter-popup-container mfp-hide" id="newsletter-popup-form">
        <div class="row justify-content-center">
            <div class="col-10">
                <div class="row no-gutters bg-white newsletter-popup-content">
                    <div class="col-xl-3-5col col-lg-7 banner-content-wrap">
                        <div class="banner-content text-center">
                            <img src="<?= FRONT_ASSETS ?>/images/popup/newsletter/logo.png" class="logo" alt="logo" width="60" height="15">
                            <h2 class="banner-title">get <span>25<light>%</light></span> off</h2>
                            <p>Subscribe to the Molla eCommerce newsletter to receive timely updates from your favorite products.</p>
                            <form action="<?= FRONT_URL ?>/shop.php">
                                <div class="input-group input-group-round">
                                    <input type="email" class="form-control form-control-white" placeholder="Your Email Address" aria-label="Email Adress" required>
                                    <div class="input-group-append">
                                        <button class="btn" type="submit"><span>go</span></button>
                                    </div><!-- .End .input-group-append -->
                                </div><!-- .End .input-group -->
                            </form>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="register-policy-2" required>
                                <label class="custom-control-label" for="register-policy-2">Do not show this popup again</label>
                            </div><!-- End .custom-checkbox -->
                        </div>
                    </div>
                    <div class="col-xl-2-5col col-lg-5 ">
                        <img src="<?= FRONT_ASSETS ?>/images/popup/newsletter/img-1.jpg" class="newsletter-img" alt="newsletter">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Plugins JS File -->
    <script src="<?= FRONT_ASSETS ?>/js/jquery.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/bootstrap.bundle.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/jquery.hoverIntent.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/jquery.waypoints.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/superfish.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/owl.carousel.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/bootstrap-input-spinner.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/jquery.plugin.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/jquery.magnific-popup.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/jquery.countdown.min.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/main.js"></script>
    <script src="<?= FRONT_ASSETS ?>/js/demos/demo-4.js"></script>
    <script>
        function renderCartDropdown(items) {
            const products = document.querySelector('.cart-dropdown .dropdown-cart-products');
            if (!products) {
                return;
            }

            products.replaceChildren();
            if (items.length === 0) {
                const emptyMessage = document.createElement('p');
                emptyMessage.className = 'text-center py-2 text-muted mb-0';
                emptyMessage.textContent = 'No products in cart.';
                products.append(emptyMessage);
                return;
            }

            items.forEach(function (item) {
                const product = document.createElement('div');
                product.className = 'product';

                const details = document.createElement('div');
                details.className = 'product-cart-details';
                const title = document.createElement('h4');
                title.className = 'product-title';
                const titleLink = document.createElement('a');
                titleLink.href = '<?= FRONT_URL ?>/product.php?id=' + item.product_id;
                titleLink.textContent = item.name;
                title.append(titleLink);

                const info = document.createElement('span');
                info.className = 'cart-product-info';
                const quantity = document.createElement('span');
                quantity.className = 'cart-product-qty';
                quantity.textContent = item.quantity;
                info.append(quantity, document.createTextNode(' x $' + Number(item.price).toFixed(2)));
                details.append(title, info);

                const imageContainer = document.createElement('figure');
                imageContainer.className = 'product-image-container';
                const imageLink = document.createElement('a');
                imageLink.className = 'product-image';
                imageLink.href = titleLink.href;
                const image = document.createElement('img');
                image.src = item.image;
                image.alt = item.name;
                imageLink.append(image);
                imageContainer.append(imageLink);

                const removeLink = document.createElement('a');
                removeLink.className = 'btn-remove';
                removeLink.href = '<?= FRONT_URL ?>/cart.php?action=remove&id=' + item.product_id;
                removeLink.title = 'Remove Product';
                const closeIcon = document.createElement('i');
                closeIcon.className = 'icon-close';
                removeLink.append(closeIcon);

                product.append(details, imageContainer, removeLink);
                products.append(product);
            });
        }

        document.addEventListener('click', function (event) {
            const cartLink = event.target.closest('.btn-product.btn-cart');
            if (!(cartLink instanceof HTMLAnchorElement)) {
                return;
            }

            let addUrl = new URL(cartLink.href, window.location.href);
            if (addUrl.searchParams.get('action') !== 'add') {
                const productLink = cartLink.closest('.product')?.querySelector('a[href*="product.php?id="]');
                const productId = productLink
                    ? new URL(productLink.href, window.location.href).searchParams.get('id')
                    : null;
                if (!productId) {
                    return;
                }
                addUrl = new URL('<?= FRONT_URL ?>/cart.php');
                addUrl.searchParams.set('action', 'add');
                addUrl.searchParams.set('id', productId);
            }

            if (cartLink.dataset.adding === 'true') {
                return;
            }

            event.preventDefault();
            cartLink.dataset.adding = 'true';
            const label = cartLink.querySelector('span');
            const originalLabel = label?.textContent;

            fetch(addUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        if (!response.ok || !data.success) {
                            throw new Error('Unable to add this product to the cart.');
                        }
                        return data;
                    });
                })
                .then(function (data) {
                    const count = document.querySelector('.cart-count');
                    const total = document.querySelector('.cart-total-price');
                    if (count) count.textContent = data.count;
                    if (total) total.textContent = '$' + Number(data.total).toFixed(2);
                    renderCartDropdown(data.items);
                    if (label) label.textContent = 'Added to cart';
                })
                .catch(function () {
                    if (label) label.textContent = 'Unable to add';
                })
                .finally(function () {
                    window.setTimeout(function () {
                        if (label && originalLabel !== null) label.textContent = originalLabel;
                        delete cartLink.dataset.adding;
                    }, 1400);
                });
        });
    </script>
</body>


<!-- molla/index-4.html  22 Nov 2019 09:54:18 GMT -->
</html>