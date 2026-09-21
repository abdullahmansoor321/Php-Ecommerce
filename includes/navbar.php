<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch categories and cart items dynamically
$nav_categories = [];
$nav_cart_items = [];
$nav_cart_total = 0.0;
$nav_cart_count = 0;

try {
    if (file_exists(__DIR__ . '/../core/Database.php')) {
        require_once __DIR__ . '/../core/Database.php';
        $db = Database::getInstance();
        $nav_categories = $db->fetchAll("SELECT id, name, slug FROM categories WHERE status = 1 ORDER BY name ASC");
    }
    if (file_exists(__DIR__ . '/../core/Cart.php')) {
        require_once __DIR__ . '/../core/Cart.php';
        $nav_cart_items = Cart::getItems();
        $nav_cart_total = Cart::getTotal();
        foreach ($nav_cart_items as $ci) {
            $nav_cart_count += (int)$ci['quantity'];
        }
    }
} catch (Exception $e) {
    // Fallback if DB not ready
}
?>
        <header class="header header-intro-clearance header-4">
            <div class="header-top">
                <div class="container">
                    <div class="header-left">
                        <a href="tel:#"><i class="icon-phone"></i>Call: +0123 456 789</a>
                    </div><!-- End .header-left -->

                    <div class="header-right">
                        <ul class="top-menu">
                            <li>
                                <a href="#">Links</a>
                                <ul>
                                    <li><a href="<?= FRONT_URL ?>/shop.php">Shop</a></li>
                                    <li><a href="<?= FRONT_URL ?>/cart.php">Cart</a></li>
                                    <li><a href="<?= FRONT_URL ?>/checkout.php">Checkout</a></li>
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <li><a href="<?= FRONT_URL ?>/logout.php">Logout (<?= htmlspecialchars($_SESSION['user_name'] ?? 'Account') ?>)</a></li>
                                    <?php else: ?>
                                        <li><a href="<?= FRONT_URL ?>/login.php">Sign in / Sign up</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                        </ul><!-- End .top-menu -->
                    </div><!-- End .header-right -->

                </div><!-- End .container -->
            </div><!-- End .header-top -->

            <div class="header-middle">
                <div class="container">
                    <div class="header-left">
                        <button class="mobile-menu-toggler">
                            <span class="sr-only">Toggle mobile menu</span>
                            <i class="icon-bars"></i>
                        </button>
                        
                        <a href="<?= FRONT_URL ?>/index.php" class="logo">
                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/logo.png" alt="Molla Logo" width="105" height="25">
                        </a>
                    </div><!-- End .header-left -->

                    <div class="header-center">
                        <div class="header-search header-search-extended header-search-visible d-none d-lg-block">
                            <a href="#" class="search-toggle" role="button"><i class="icon-search"></i></a>
                            <form action="<?= FRONT_URL ?>/shop.php" method="GET">
                                <div class="header-search-wrapper search-wrapper-wide">
                                    <label for="q" class="sr-only">Search</label>
                                    <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                                    <input type="search" class="form-control" name="q" id="q" placeholder="Search product ..." required>
                                </div><!-- End .header-search-wrapper -->
                            </form>
                        </div><!-- End .header-search -->
                    </div>

                    <div class="header-right">
                        <div class="wishlist">
                            <a href="<?= FRONT_URL ?>/shop.php" title="Shop">
                                <div class="icon">
                                    <i class="icon-heart-o"></i>
                                    <span class="wishlist-count badge">3</span>
                                </div>
                                <p>Wishlist</p>
                            </a>
                        </div><!-- End .wishlist -->

                        <div class="dropdown cart-dropdown">
                            <a href="<?= FRONT_URL ?>/cart.php" class="dropdown-toggle" role="button">
                                <div class="icon">
                                    <i class="icon-shopping-cart"></i>
                                    <span class="cart-count"><?= $nav_cart_count ?></span>
                                </div>
                                <p>Cart</p>
                            </a>

                            <div class="dropdown-menu dropdown-menu-right">
                                <div class="dropdown-cart-products">
                                    <?php if (empty($nav_cart_items)): ?>
                                        <p class="text-center py-2 text-muted mb-0">No products in cart.</p>
                                    <?php else: ?>
                                        <?php foreach ($nav_cart_items as $ci): ?>
                                            <?php 
                                            $ciImg = !empty($ci['image']) ? $ci['image'] : 'product-1.jpg';
                                            $ciPath = (strpos($ciImg, 'http') === 0) ? $ciImg : FRONT_ASSETS . '/images/demos/demo-4/products/' . $ciImg;
                                            if (!file_exists(BASE_PATH . '/public/assets/front/images/demos/demo-4/products/' . basename($ciImg))) {
                                                $ciPath = FRONT_ASSETS . '/images/demos/demo-4/products/product-1.jpg';
                                            }
                                            ?>
                                            <div class="product">
                                                <div class="product-cart-details">
                                                    <h4 class="product-title">
                                                        <a href="<?= FRONT_URL ?>/product.php?id=<?= $ci['product_id'] ?>"><?= htmlspecialchars($ci['name']) ?></a>
                                                    </h4>
                                                    <span class="cart-product-info">
                                                        <span class="cart-product-qty"><?= $ci['quantity'] ?></span>
                                                        x $<?= number_format((float)$ci['price'], 2) ?>
                                                    </span>
                                                </div>
                                                <figure class="product-image-container">
                                                    <a href="<?= FRONT_URL ?>/product.php?id=<?= $ci['product_id'] ?>" class="product-image">
                                                        <img src="<?= htmlspecialchars($ciPath) ?>" alt="product">
                                                    </a>
                                                </figure>
                                                <a href="<?= FRONT_URL ?>/cart.php?action=remove&id=<?= $ci['product_id'] ?>" class="btn-remove" title="Remove Product"><i class="icon-close"></i></a>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div><!-- End .cart-product -->

                                <div class="dropdown-cart-total">
                                    <span>Total</span>
                                    <span class="cart-total-price">$<?= number_format($nav_cart_total, 2) ?></span>
                                </div><!-- End .dropdown-cart-total -->

                                <div class="dropdown-cart-action">
                                    <a href="<?= FRONT_URL ?>/cart.php" class="btn btn-primary">View Cart</a>
                                    <a href="<?= FRONT_URL ?>/checkout.php" class="btn btn-outline-primary-2"><span>Checkout</span><i class="icon-long-arrow-right"></i></a>
                                </div><!-- End .dropdown-cart-action -->
                            </div><!-- End .dropdown-menu -->
                        </div><!-- End .cart-dropdown -->
                    </div><!-- End .header-right -->

                </div><!-- End .container -->
            </div><!-- End .header-middle -->

            <div class="header-bottom sticky-header">
                <div class="container">
                    <div class="header-left">
                        <div class="dropdown category-dropdown">
                            <a href="#" class="dropdown-toggle" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" data-display="static" title="Browse Categories">
                                <i class="icon-bars"></i>Browse Categories
                            </a>

                            <div class="dropdown-menu">
                                <div class="side-menu">
                                    <ul class="menu-vertical sf-arrows">
                                        <?php if (!empty($nav_categories)): ?>
                                            <?php foreach ($nav_categories as $cat): ?>
                                                <li><a href="<?= FRONT_URL ?>/shop.php?category=<?= htmlspecialchars($cat['slug']) ?>"><?= htmlspecialchars($cat['name']) ?></a></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li><a href="<?= FRONT_URL ?>/shop.php">Computers & Laptops</a></li>
                                            <li><a href="<?= FRONT_URL ?>/shop.php">Digital Cameras</a></li>
                                            <li><a href="<?= FRONT_URL ?>/shop.php">Smart Phones</a></li>
                                        <?php endif; ?>
                                    </ul><!-- End .menu-vertical -->
                                </div><!-- End .side-menu -->
                            </div><!-- End .dropdown-menu -->
                        </div><!-- End .category-dropdown -->
                    </div><!-- End .header-left -->

                    <div class="header-center">
                        <nav class="nav-container">
                            <ul class="menu sf-arrows">
                                <li class="megamenu-container active">
                                    <a href="<?= FRONT_URL ?>/index.php">Home</a>
                                </li>
                                <li>
                                    <a href="<?= FRONT_URL ?>/shop.php">Shop</a>
                                </li>
                                <li>
                                    <a href="<?= FRONT_URL ?>/product.php?id=1">Product</a>
                                </li>
                                <li>
                                    <a href="<?= FRONT_URL ?>/cart.php">Cart</a>
                                </li>
                                <li>
                                    <a href="<?= FRONT_URL ?>/checkout.php">Checkout</a>
                                </li>
                            </ul><!-- End .menu -->
                        </nav><!-- End .nav-container -->
                    </div><!-- End .header-center -->

                    <div class="header-right">
                        <i class="icon-lightbulb"></i><p>Clearance<span class="highlight">&nbsp;Up to 30% Off</span></p>
                    </div>
                </div><!-- End .container -->
            </div><!-- End .header-bottom -->
        </header><!-- End .header -->
