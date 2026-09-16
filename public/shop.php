<?php
require_once __DIR__ . '/../config/constants.php';

$page_title = "Shop - Electronics Catalog";

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="main">
	<div class="page-header text-center" style="background-image: url('<?= FRONT_ASSETS ?>/images/page-header-bg.jpg')">
		<div class="container">
			<h1 class="page-title">Products<span>Electronics Store</span></h1>
		</div>
	</div>
    <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= APP_URL ?>">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </div>
    </nav>

    <div class="page-content">
        <div class="container">
        	<div class="row">
        		<div class="col-lg-9">
        			<div class="toolbox">
        				<div class="toolbox-left">
        					<div class="toolbox-info">
        						Showing <span>9 of 24</span> Products
        					</div>
        				</div>

        				<div class="toolbox-right">
        					<div class="toolbox-sort">
        						<label for="sortby">Sort by:</label>
        						<div class="select-custom">
									<select name="sortby" id="sortby" class="form-control">
										<option value="popularity" selected="selected">Most Popular</option>
										<option value="rating">Most Rated</option>
										<option value="date">Date</option>
										<option value="price_low">Price: Low to High</option>
										<option value="price_high">Price: High to Low</option>
									</select>
								</div>
        					</div>
        				</div>
        			</div>

                    <div class="products mb-3">
                        <div class="row justify-content-center">
                            
                            <!-- Product 1: MacBook Pro -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <span class="product-label label-top">Top</span>
                                        <a href="<?= APP_URL ?>/product.php?id=1">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-1.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=laptops">Laptops</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=1">MacBook Pro 13" Display, i5</a></h3>
                                        <div class="product-price">
                                            $1,199.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 100%;"></div>
                                            </div>
                                            <span class="ratings-text">( 4 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 2: Bose Speaker -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <a href="<?= APP_URL ?>/product.php?id=2">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-2.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=audio">Audio</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=2">Bose - SoundLink Bluetooth Speaker</a></h3>
                                        <div class="product-price">
                                            $79.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 60%;"></div>
                                            </div>
                                            <span class="ratings-text">( 6 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 3: Apple iPad Pro -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <span class="product-label label-new">New</span>
                                        <a href="<?= APP_URL ?>/product.php?id=3">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-3.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=tablets">Tablets</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=3">Apple - 11 Inch iPad Pro Wi-Fi 256GB</a></h3>
                                        <div class="product-price">
                                            $899.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 80%;"></div>
                                            </div>
                                            <span class="ratings-text">( 4 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 4: Google Pixel 3 XL -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <span class="product-label label-sale">Sale</span>
                                        <a href="<?= APP_URL ?>/product.php?id=4">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-4.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=phones">Cell Phones</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=4">Google - Pixel 3 XL 128GB</a></h3>
                                        <div class="product-price">
                                            <span class="new-price">$349.99</span>
                                            <span class="old-price">$419.99</span>
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 100%;"></div>
                                            </div>
                                            <span class="ratings-text">( 10 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 5: Samsung Smart TV -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <span class="product-label label-top">Top</span>
                                        <a href="<?= APP_URL ?>/product.php?id=5">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-5.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=tvs">TV & Home Theater</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=5">Samsung - 55" Class LED 2160p Smart 4K</a></h3>
                                        <div class="product-price">
                                            $899.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 60%;"></div>
                                            </div>
                                            <span class="ratings-text">( 5 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 6: Bose Headphones -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <a href="<?= APP_URL ?>/product.php?id=6">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-6.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=audio">Headphones</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=6">Bose - SoundSport Wireless Headphones</a></h3>
                                        <div class="product-price">
                                            $199.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 100%;"></div>
                                            </div>
                                            <span class="ratings-text">( 4 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 7: Xbox One S -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <a href="<?= APP_URL ?>/product.php?id=7">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-7.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=gaming">Video Games</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=7">Microsoft - Xbox One S 500GB Console</a></h3>
                                        <div class="product-price">
                                            $279.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 60%;"></div>
                                            </div>
                                            <span class="ratings-text">( 6 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 8: Apple Watch Series 4 -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <span class="product-label label-new">New</span>
                                        <a href="<?= APP_URL ?>/product.php?id=8">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-8.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=watches">Smartwatches</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=8">Apple Watch Series 4 Gold Aluminum</a></h3>
                                        <div class="product-price">
                                            $499.99
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 80%;"></div>
                                            </div>
                                            <span class="ratings-text">( 4 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Product 9: Sony Ultra HD 4K -->
                            <div class="col-6 col-md-4 col-lg-4">
                                <div class="product product-7 text-center">
                                    <figure class="product-media">
                                        <span class="product-label label-top">Top</span>
                                        <a href="<?= APP_URL ?>/product.php?id=9">
                                            <img src="<?= FRONT_ASSETS ?>/images/demos/demo-4/products/product-9.jpg" alt="Product image" class="product-image">
                                        </a>

                                        <div class="product-action-vertical">
                                            <a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a>
                                        </div>

                                        <div class="product-action">
                                            <a href="#" class="btn-product btn-cart"><span>add to cart</span></a>
                                        </div>
                                    </figure>

                                    <div class="product-body">
                                        <div class="product-cat">
                                            <a href="<?= APP_URL ?>/shop.php?cat=tvs">TV & Home Theater</a>
                                        </div>
                                        <h3 class="product-title"><a href="<?= APP_URL ?>/product.php?id=9">Sony - 65" Class LED Smart 4K Ultra HD</a></h3>
                                        <div class="product-price">
                                            <span class="new-price">$1,699.99</span>
                                            <span class="old-price">$1,999.99</span>
                                        </div>
                                        <div class="ratings-container">
                                            <div class="ratings">
                                                <div class="ratings-val" style="width: 80%;"></div>
                                            </div>
                                            <span class="ratings-text">( 10 Reviews )</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

        			<!-- Pagination -->
        			<nav aria-label="Page navigation">
					    <ul class="pagination justify-content-center">
					        <li class="page-item disabled">
					            <a class="page-link page-link-prev" href="#" aria-label="Previous">
					                <span aria-hidden="true"><i class="icon-long-arrow-left"></i></span>Prev
					            </a>
					        </li>
					        <li class="page-item active"><a class="page-link" href="#">1</a></li>
					        <li class="page-item"><a class="page-link" href="#">2</a></li>
					        <li class="page-item">
					            <a class="page-link page-link-next" href="#" aria-label="Next">
					                Next <span aria-hidden="true"><i class="icon-long-arrow-right"></i></span>
					            </a>
					        </li>
					    </ul>
					</nav>
        		</div>

        		<!-- Sidebar Filter -->
        		<aside class="col-lg-3 order-lg-first">
        			<div class="sidebar sidebar-shop">
        				<div class="widget widget-clean">
        					<label>Filters:</label>
        					<a href="<?= APP_URL ?>/shop.php" class="sidebar-filter-clear">Clean All</a>
        				</div>

        				<!-- Category Filter Widget -->
        				<div class="widget widget-collapsible">
							<h3 class="widget-title">
							    <a data-toggle="collapse" href="#widget-1" role="button" aria-expanded="true" aria-controls="widget-1">
							        Category
							    </a>
							</h3>

							<div class="collapse show" id="widget-1">
								<div class="widget-body">
									<div class="filter-items filter-items-count">
										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="cat-1">
												<label class="custom-control-label" for="cat-1">Computers & Laptops</label>
											</div>
											<span class="item-count">6</span>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="cat-2">
												<label class="custom-control-label" for="cat-2">Smartphones & Tablets</label>
											</div>
											<span class="item-count">8</span>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="cat-3">
												<label class="custom-control-label" for="cat-3">Televisions</label>
											</div>
											<span class="item-count">5</span>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="cat-4">
												<label class="custom-control-label" for="cat-4">Digital Cameras</label>
											</div>
											<span class="item-count">4</span>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="cat-5">
												<label class="custom-control-label" for="cat-5">Audio & Headphones</label>
											</div>
											<span class="item-count">7</span>
										</div>
									</div>
								</div>
							</div>
						</div>

                        <!-- Brand Filter Widget -->
						<div class="widget widget-collapsible">
							<h3 class="widget-title">
							    <a data-toggle="collapse" href="#widget-brand" role="button" aria-expanded="true" aria-controls="widget-brand">
							        Brand
							    </a>
							</h3>

							<div class="collapse show" id="widget-brand">
								<div class="widget-body">
									<div class="filter-items">
										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="brand-1" name="brand[]" value="apple">
												<label class="custom-control-label" for="brand-1">Apple</label>
											</div>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="brand-2" name="brand[]" value="samsung">
												<label class="custom-control-label" for="brand-2">Samsung</label>
											</div>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="brand-3" name="brand[]" value="bose">
												<label class="custom-control-label" for="brand-3">Bose</label>
											</div>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="brand-4" name="brand[]" value="sony">
												<label class="custom-control-label" for="brand-4">Sony</label>
											</div>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="brand-5" name="brand[]" value="google">
												<label class="custom-control-label" for="brand-5">Google</label>
											</div>
										</div>

										<div class="filter-item">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="brand-6" name="brand[]" value="microsoft">
												<label class="custom-control-label" for="brand-6">Microsoft</label>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<!-- Price Filter Widget -->
						<div class="widget widget-collapsible">
							<h3 class="widget-title">
							    <a data-toggle="collapse" href="#widget-5" role="button" aria-expanded="true" aria-controls="widget-5">
							        Price Range
							    </a>
							</h3>

							<div class="collapse show" id="widget-5">
								<div class="widget-body">
									<div class="filter-items">
										<div class="filter-item">
											<div class="custom-control custom-radio">
												<input type="radio" class="custom-control-input" id="price-1" name="price_range">
												<label class="custom-control-label" for="price-1">Under $100</label>
											</div>
										</div>
										<div class="filter-item">
											<div class="custom-control custom-radio">
												<input type="radio" class="custom-control-input" id="price-2" name="price_range">
												<label class="custom-control-label" for="price-2">$100 - $500</label>
											</div>
										</div>
										<div class="filter-item">
											<div class="custom-control custom-radio">
												<input type="radio" class="custom-control-input" id="price-3" name="price_range">
												<label class="custom-control-label" for="price-3">$500 - $1,000</label>
											</div>
										</div>
										<div class="filter-item">
											<div class="custom-control custom-radio">
												<input type="radio" class="custom-control-input" id="price-4" name="price_range">
												<label class="custom-control-label" for="price-4">$1,000 & Above</label>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
        			</div>
        		</aside>
        	</div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>