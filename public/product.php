<?php
require_once __DIR__ . '/../config/constants.php';

$page_title = "MacBook Pro 13\" - Product Details";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="main">
    <nav aria-label="breadcrumb" class="breadcrumb-nav border-0 mb-0">
        <div class="container d-flex align-items-center">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/shop.php">Products</a></li>
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>/shop.php?cat=laptops">Laptops</a></li>
                <li class="breadcrumb-item active" aria-current="page">MacBook Pro 13"</li>
            </ol>
        </div>
    </nav>

    <div class="page-content">
        <div class="container">
            <div class="product-details-top">
                <div class="row">
                    
                    <!-- Left: Interactive Gallery with Zoom -->
                    <div class="col-md-6">
                        <div class="product-gallery product-gallery-vertical">
                            <div class="row">
                                <figure class="product-main-image">
                                    <img id="product-zoom" 
                                         src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-1.jpg" 
                                         data-zoom-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-1.jpg" 
                                         alt="MacBook Pro 13">

                                    <a href="#" id="btn-product-gallery" class="btn-product-gallery">
                                        <i class="icon-arrows"></i>
                                    </a>
                                </figure>

                                <div id="product-zoom-gallery" class="product-image-gallery">
                                    <a class="product-gallery-item active" href="#" 
                                       data-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-1.jpg" 
                                       data-zoom-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-1.jpg">
                                        <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-1.jpg" alt="MacBook front">
                                    </a>

                                    <a class="product-gallery-item" href="#" 
                                       data-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-2.jpg" 
                                       data-zoom-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-2.jpg">
                                        <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-2.jpg" alt="Audio accessory">
                                    </a>

                                    <a class="product-gallery-item" href="#" 
                                       data-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-3.jpg" 
                                       data-zoom-image="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-3.jpg">
                                        <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-3.jpg" alt="Display and keyboard">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Product Information & Add to Cart -->
                    <div class="col-md-6">
                        <div class="product-details">
                            <h1 class="product-title">Apple MacBook Pro 13" Display, Intel Core i5, 8GB RAM, 256GB SSD</h1>

                            <div class="ratings-container">
                                <div class="ratings">
                                    <div class="ratings-val" style="width: 100%;"></div>
                                </div>
                                <a class="ratings-text" href="#product-review-link" id="review-link">( 4 Reviews )</a>
                            </div>

                            <!-- Dynamic Pricing -->
                            <div class="product-price">
                                $1,199.99
                            </div>

                            <!-- Stock Availability Indicator (Required by Proposal) -->
                            <div class="product-stock-status mb-2">
                                <span class="badge" style="font-size: 1.2rem; background-color: #28a745; color: white; padding: 6px 12px; border-radius: 4px;">
                                    <i class="icon-check"></i> In Stock (12 units available)
                                </span>
                            </div>

                            <div class="product-content">
                                <p>Supercharged by quad-core 8th-generation Intel Core i5 processor. Features a brilliant Retina display with True Tone technology, backlit Magic Keyboard, Touch Bar with Touch ID, and ultra-fast SSD.</p>
                            </div>

                            <!-- Color Selection -->
                            <div class="details-filter-row details-row-size">
                                <label>Finish:</label>
                                <div class="product-nav product-nav-dots">
                                    <a href="#" class="active" style="background: #4c4d4f;" title="Space Gray"><span class="sr-only">Space Gray</span></a>
                                    <a href="#" style="background: #e1e3e4;" title="Silver"><span class="sr-only">Silver</span></a>
                                </div>
                            </div>

                            <!-- Storage Variant Selection -->
                            <div class="details-filter-row details-row-size">
                                <label for="storage">Storage:</label>
                                <div class="select-custom">
                                    <select name="storage" id="storage" class="form-control">
                                        <option value="256" selected="selected">256GB SSD</option>
                                        <option value="512">512GB SSD (+$200.00)</option>
                                        <option value="1000">1TB SSD (+$400.00)</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Quantity and Add to Cart Form -->
                            <form action="<?= APP_URL ?>/cart.php" method="POST">
                                <input type="hidden" name="product_id" value="1">
                                
                                <div class="details-filter-row details-row-size">
                                    <label for="qty">Qty:</label>
                                    <div class="product-details-quantity">
                                        <input type="number" id="qty" name="quantity" class="form-control" value="1" min="1" max="12" step="1" data-decimals="0" required>
                                    </div>
                                </div>

                                <div class="product-details-action">
                                    <button type="submit" name="add_to_cart" class="btn-product btn-cart" style="border: none; cursor: pointer;">
                                        <span>add to cart</span>
                                    </button>

                                    <div class="details-action-wrapper">
                                        <a href="#" class="btn-product btn-wishlist" title="Wishlist"><span>Add to Wishlist</span></a>
                                    </div>
                                </div>
                            </form>

                            <div class="product-details-footer">
                                <div class="product-cat">
                                    <span>Category:</span>
                                    <a href="<?= APP_URL ?>/shop.php?cat=laptops">Laptops</a>,
                                    <a href="<?= APP_URL ?>/shop.php?brand=apple">Apple</a>
                                </div>

                                <div class="social-icons social-icons-sm">
                                    <span class="social-label">Share:</span>
                                    <a href="#" class="social-icon" title="Facebook" target="_blank"><i class="icon-facebook-f"></i></a>
                                    <a href="#" class="social-icon" title="Twitter" target="_blank"><i class="icon-twitter"></i></a>
                                    <a href="#" class="social-icon" title="Instagram" target="_blank"><i class="icon-instagram"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Tabs: Overview, Specifications, Shipping, Reviews -->
            <div class="product-details-tab">
                <ul class="nav nav-pills justify-content-center" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="product-desc-link" data-toggle="tab" href="#product-desc-tab" role="tab" aria-controls="product-desc-tab" aria-selected="true">Description</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="product-info-link" data-toggle="tab" href="#product-info-tab" role="tab" aria-controls="product-info-tab" aria-selected="false">Specifications</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="product-shipping-link" data-toggle="tab" href="#product-shipping-tab" role="tab" aria-controls="product-shipping-tab" aria-selected="false">Shipping & Warranty</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="product-review-link" data-toggle="tab" href="#product-review-tab" role="tab" aria-controls="product-review-tab" aria-selected="false">Reviews (4)</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="product-desc-tab" role="tabpanel" aria-labelledby="product-desc-link">
                        <div class="product-desc-content">
                            <h3>Product Overview</h3>
                            <p>MacBook Pro elevates the notebook to a whole new level of performance and portability. Wherever your ideas take you, you’ll get there faster than ever with high‑performance processors and memory, advanced graphics, blazing‑fast storage, and more.</p>
                            <ul>
                                <li>Quad-Core 8th-Generation Intel Core i5 Processor 1.4GHz (Turbo Boost up to 3.9GHz)</li>
                                <li>Brilliant Retina Display with True Tone Technology and 500 nits brightness</li>
                                <li>Touch Bar and Touch ID sensor for fast authentication and Apple Pay</li>
                                <li>Intel Iris Plus Graphics 645 and ultrafast SSD storage</li>
                                <li>Two Thunderbolt 3 (USB-C) ports with support for charging and high-speed I/O</li>
                            </ul>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="product-info-tab" role="tabpanel" aria-labelledby="product-info-link">
                        <div class="product-desc-content">
                            <h3>Technical Specifications</h3>
                            <table class="table table-bordered table-striped">
                                <tbody>
                                    <tr><td><strong>Brand</strong></td><td>Apple</td></tr>
                                    <tr><td><strong>Model</strong></td><td>MacBook Pro 13" (A2159)</td></tr>
                                    <tr><td><strong>Processor</strong></td><td>Intel Core i5 (1.4GHz Quad-Core, Turbo to 3.9GHz)</td></tr>
                                    <tr><td><strong>RAM</strong></td><td>8GB 2133MHz LPDDR3 onboard memory</td></tr>
                                    <tr><td><strong>Storage</strong></td><td>256GB PCIe-based onboard SSD</td></tr>
                                    <tr><td><strong>Display</strong></td><td>13.3-inch Retina LED (2560 x 1600 native resolution)</td></tr>
                                    <tr><td><strong>Graphics</strong></td><td>Intel Iris Plus Graphics 645</td></tr>
                                    <tr><td><strong>Operating System</strong></td><td>macOS Monterey</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="product-shipping-tab" role="tabpanel" aria-labelledby="product-shipping-link">
                        <div class="product-desc-content">
                            <h3>Delivery & Warranty Information</h3>
                            <p>We provide nationwide insured express shipping within 2–4 business days. Every product comes with an official 1-year manufacturer warranty and a 7-day money-back guarantee.</p>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="product-review-tab" role="tabpanel" aria-labelledby="product-review-link">
                        <div class="reviews">
                            <h3>Customer Reviews (4)</h3>
                            <div class="review">
                                <div class="row no-gutters">
                                    <div class="col-auto">
                                        <h4><a href="#">Hamza K.</a></h4>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 100%;"></div>
                                            </div>
                                        </div>
                                        <span class="review-date">3 days ago</span>
                                    </div>
                                    <div class="col">
                                        <h4>Unbeatable performance and battery life</h4>
                                        <div class="review-content">
                                            <p>The display is gorgeous and the keyboard feels super tactile. Handles coding, Docker containers, and Photoshop with zero lag.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products Carousel -->
            <h2 class="title text-center mb-4">You May Also Like</h2>
            <div class="owl-carousel owl-simple carousel-equal-height carousel-with-shadow" data-toggle="owl" 
                data-owl-options='{
                    "nav": false, 
                    "dots": true,
                    "margin": 20,
                    "loop": false,
                    "responsive": {
                        "0": { "items": 1 },
                        "480": { "items": 2 },
                        "768": { "items": 3 },
                        "992": { "items": 4 },
                        "1200": { "items": 4, "nav": true, "dots": false }
                    }
                }'>
                
                <div class="product product-7 text-center">
                    <figure class="product-media">
                        <a href="<?= APP_URL ?>/product.php?id=2">
                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-2.jpg" alt="Bose speaker" class="product-image">
                        </a>
                        <div class="product-action">
                            <a href="<?= APP_URL ?>/cart.php" class="btn-product btn-cart"><span>add to cart</span></a>
                        </div>
                    </figure>
                    <div class="product-body">
                        <div class="product-cat"><a href="<?= APP_URL ?>/shop.php?cat=audio">Audio</a></div>
                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=2">Bose - SoundLink Bluetooth Speaker</a></h3>
                        <div class="product-price">$79.99</div>
                    </div>
                </div>

                <div class="product product-7 text-center">
                    <figure class="product-media">
                        <span class="product-label label-new">New</span>
                        <a href="<?= APP_URL ?>/product.php?id=3">
                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-3.jpg" alt="Apple iPad" class="product-image">
                        </a>
                        <div class="product-action">
                            <a href="<?= APP_URL ?>/cart.php" class="btn-product btn-cart"><span>add to cart</span></a>
                        </div>
                    </figure>
                    <div class="product-body">
                        <div class="product-cat"><a href="<?= APP_URL ?>/shop.php?cat=tablets">Tablets</a></div>
                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=3">Apple - 11 Inch iPad Pro 256GB</a></h3>
                        <div class="product-price">$899.99</div>
                    </div>
                </div>

                <div class="product product-7 text-center">
                    <figure class="product-media">
                        <span class="product-label label-sale">Sale</span>
                        <a href="<?= APP_URL ?>/product.php?id=4">
                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-4.jpg" alt="Google Pixel" class="product-image">
                        </a>
                        <div class="product-action">
                            <a href="<?= APP_URL ?>/cart.php" class="btn-product btn-cart"><span>add to cart</span></a>
                        </div>
                    </figure>
                    <div class="product-body">
                        <div class="product-cat"><a href="<?= APP_URL ?>/shop.php?cat=phones">Cell Phones</a></div>
                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=4">Google - Pixel 3 XL 128GB</a></h3>
                        <div class="product-price">$349.99</div>
                    </div>
                </div>

                <div class="product product-7 text-center">
                    <figure class="product-media">
                        <a href="<?= APP_URL ?>/product.php?id=6">
                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-6.jpg" alt="Bose SoundSport" class="product-image">
                        </a>
                        <div class="product-action">
                            <a href="<?= APP_URL ?>/cart.php" class="btn-product btn-cart"><span>add to cart</span></a>
                        </div>
                    </figure>
                    <div class="product-body">
                        <div class="product-cat"><a href="<?= APP_URL ?>/shop.php?cat=audio">Headphones</a></div>
                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=6">Bose - SoundSport Wireless</a></h3>
                        <div class="product-price">$199.99</div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>



<?php
require_once __DIR__ . '/../includes/footer.php';
?>