<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';

$db = Database::getInstance();
$selectedCategory = trim($_GET['category'] ?? $_GET['cat'] ?? '');
$placeholderImage = 'https://placehold.co/600x600?text=Product';

$shop_categories = $db->fetchAll("
    SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
    WHERE c.status = 1
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.name ASC
");

$productSql = "
    SELECT p.id, p.name, p.price, p.image, c.name AS category_name, c.slug AS category_slug
    FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE p.status = 1 AND c.status = 1
";
$productParams = [];
if ($selectedCategory !== '') {
    $productSql .= " AND c.slug = ?";
    $productParams[] = $selectedCategory;
}
$productSql .= " ORDER BY p.created_at DESC, p.id DESC";
$products = $db->fetchAll($productSql, $productParams);

$page_title = 'Shop - Electronics Catalog';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<main class="main">
    <div class="page-header text-center" style="background-image: url('<?= FRONT_ASSETS ?>/images/page-header-bg.jpg')">
        <div class="container"><h1 class="page-title">Products<span>Electronics Store</span></h1></div>
    </div>
    <nav aria-label="breadcrumb" class="breadcrumb-nav mb-2">
        <div class="container">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= FRONT_URL ?>/index.php">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop</li>
            </ol>
        </div>
    </nav>

    <div class="page-content">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div class="toolbox">
                        <div class="toolbox-left"><div class="toolbox-info">Showing <span><?= count($products) ?></span> Products</div></div>
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
                            <?php if (empty($products)): ?>
                                <div class="col-12 text-center py-5"><p class="text-muted mb-0">No products available.</p></div>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <div class="col-6 col-md-4 col-lg-4">
                                        <div class="product product-7 text-center">
                                            <figure class="product-media">
                                                <span class="product-label label-top">Top</span>
                                                <a href="<?= FRONT_URL ?>/product.php?id=<?= (int)$product['id'] ?>">
                                                    <?php $productImage = !empty($product['image']) && is_file(BASE_PATH . '/public/uploads/products/' . basename($product['image'])) ? UPLOADS_URL . '/products/' . rawurlencode(basename($product['image'])) : $placeholderImage; ?>
                                                    <img src="<?= htmlspecialchars($productImage) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                                                </a>
                                                <div class="product-action-vertical"><a href="#" class="btn-product-icon btn-wishlist btn-expandable"><span>add to wishlist</span></a></div>
                                                <div class="product-action"><a href="<?= FRONT_URL ?>/cart.php?action=add&amp;id=<?= (int)$product['id'] ?>" class="btn-product btn-cart"><span>add to cart</span></a></div>
                                            </figure>
                                            <div class="product-body">
                                                <div class="product-cat"><a href="<?= FRONT_URL ?>/shop.php?cat=<?= htmlspecialchars($product['category_slug']) ?>"><?= htmlspecialchars($product['category_name']) ?></a></div>
                                                <h3 class="product-title"><a href="<?= FRONT_URL ?>/product.php?id=<?= (int)$product['id'] ?>"><?= htmlspecialchars($product['name']) ?></a></h3>
                                                <div class="product-price">$<?= number_format((float)$product['price'], 2) ?></div>
                                                <div class="ratings-container"><div class="ratings"><div class="ratings-val" style="width: 80%;"></div></div><span class="ratings-text">( 4 Reviews )</span></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item disabled"><a class="page-link page-link-prev" href="#" aria-label="Previous"><span aria-hidden="true"><i class="icon-long-arrow-left"></i></span>Prev</a></li>
                            <li class="page-item active"><a class="page-link" href="<?= FRONT_URL ?>/shop.php">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link page-link-next" href="#" aria-label="Next">Next <span aria-hidden="true"><i class="icon-long-arrow-right"></i></span></a></li>
                        </ul>
                    </nav>
                </div>

                <aside class="col-lg-3 order-lg-first">
                    <div class="sidebar sidebar-shop">
                        <div class="widget widget-clean"><label>Filters:</label><a href="<?= FRONT_URL ?>/shop.php" class="sidebar-filter-clear">Clean All</a></div>
                        <div class="widget widget-collapsible">
                            <h3 class="widget-title"><a data-toggle="collapse" href="#widget-1" role="button" aria-expanded="true" aria-controls="widget-1">Category</a></h3>
                            <div class="collapse show" id="widget-1"><div class="widget-body"><div class="filter-items filter-items-count">
                                <?php if (!empty($shop_categories)): ?>
                                    <?php foreach ($shop_categories as $category): ?>
                                        <div class="filter-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="cat-<?= (int)$category['id'] ?>" name="category[]" value="<?= htmlspecialchars($category['slug']) ?>">
                                                <label class="custom-control-label" for="cat-<?= (int)$category['id'] ?>"><a href="<?= FRONT_URL ?>/shop.php?cat=<?= htmlspecialchars($category['slug']) ?>"><?= htmlspecialchars($category['name']) ?></a></label>
                                            </div>
                                            <span class="item-count"><?= (int)$category['product_count'] ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-muted">No categories available.</div>
                                <?php endif; ?>
                            </div></div></div>
                        </div>
                        <div class="widget widget-collapsible">
                            <h3 class="widget-title"><a data-toggle="collapse" href="#widget-brand" role="button" aria-expanded="true" aria-controls="widget-brand">Brand</a></h3>
                            <div class="collapse show" id="widget-brand"><div class="widget-body"><div class="filter-items">
                                <?php foreach (['apple', 'samsung', 'bose', 'sony', 'google', 'microsoft'] as $brand): ?>
                                    <div class="filter-item"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="brand-<?= $brand ?>" name="brand[]" value="<?= $brand ?>"><label class="custom-control-label" for="brand-<?= $brand ?>"><?= ucfirst($brand) ?></label></div></div>
                                <?php endforeach; ?>
                            </div></div></div>
                        </div>
                        <div class="widget widget-collapsible">
                            <h3 class="widget-title"><a data-toggle="collapse" href="#widget-5" role="button" aria-expanded="true" aria-controls="widget-5">Price Range</a></h3>
                            <div class="collapse show" id="widget-5"><div class="widget-body"><div class="filter-items">
                                <?php foreach (['Under $100', '$100 - $500', '$500 - $1,000', '$1,000 & Above'] as $index => $range): ?>
                                    <div class="filter-item"><div class="custom-control custom-radio"><input type="radio" class="custom-control-input" id="price-<?= $index + 1 ?>" name="price_range"><label class="custom-control-label" for="price-<?= $index + 1 ?>"><?= $range ?></label></div></div>
                                <?php endforeach; ?>
                            </div></div></div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
