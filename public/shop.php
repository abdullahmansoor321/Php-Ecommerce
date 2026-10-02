<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/ProductImage.php';

$db = Database::getInstance();
$selectedCategory = trim($_GET['category'] ?? $_GET['cat'] ?? '');
$minPrice = (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) ? (float)$_GET['min_price'] : null;
$maxPrice = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? (float)$_GET['max_price'] : null;
// Exclusive upper bound (price < X). Used by the home "Shop by Price" tiles so a
// tile's advertised count always matches the results its link returns.
$maxPriceEx = (isset($_GET['max_price_ex']) && is_numeric($_GET['max_price_ex'])) ? (float)$_GET['max_price_ex'] : null;

// Search term. The header search sends ?q=..., the mobile menu sends
// ?mobile-search=..., so both are accepted here.
$searchTerm = trim((string)($_GET['q'] ?? $_GET['mobile-search'] ?? ''));

// Sort whitelist. Anything outside this list falls back to the default, so a
// hand-edited ?sortby= can never reach the ORDER BY clause.
$sortOptions = [
    'date'       => 'Newest',
    'popularity' => 'Most Popular',
    'price_low'  => 'Price: Low to High',
    'price_high' => 'Price: High to Low',
    'name_asc'   => 'Name: A to Z',
];
$sortDefault = 'date';
$sortby = (string)($_GET['sortby'] ?? $sortDefault);
if (!isset($sortOptions[$sortby])) {
    $sortby = $sortDefault;
}

// Active filters carried over when refining one facet, so narrowing by
// category or price never silently drops the active search or sort.
$keepParams = [];
if ($searchTerm !== '') { $keepParams['q'] = $searchTerm; }
if ($sortby !== $sortDefault) { $keepParams['sortby'] = $sortby; }
if ($minPrice !== null) { $keepParams['min_price'] = $minPrice; }
if ($maxPrice !== null) { $keepParams['max_price'] = $maxPrice; }
if ($maxPriceEx !== null) { $keepParams['max_price_ex'] = $maxPriceEx; }

// All active filters (search + category + price), used to build page links so
// paginating never drops the current view.
$currentParams = $keepParams;
if ($selectedCategory !== '') { $currentParams['category'] = $selectedCategory; }
$placeholderImage = 'https://placehold.co/600x600?text=Product';

$shop_categories = $db->fetchAll("
    SELECT c.id, c.name, c.slug, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 1
    WHERE c.status = 1
    GROUP BY c.id, c.name, c.slug
    ORDER BY c.name ASC
");

// One shared WHERE clause feeds both the listing and the row count, so the
// page count can never drift away from what is actually being listed.
$fromSql = "FROM products p INNER JOIN categories c ON c.id = p.category_id";

$whereParts  = ['p.status = 1', 'c.status = 1'];
$whereParams = [];

if ($selectedCategory !== '') {
    $whereParts[] = "c.slug = ?";
    $whereParams[] = $selectedCategory;
}
if ($minPrice !== null) {
    $whereParts[] = "p.price >= ?";
    $whereParams[] = $minPrice;
}
if ($maxPrice !== null) {
    $whereParts[] = "p.price <= ?";
    $whereParams[] = $maxPrice;
}
if ($maxPriceEx !== null) {
    $whereParts[] = "p.price < ?";
    $whereParams[] = $maxPriceEx;
}
if ($searchTerm !== '') {
    // Escape LIKE wildcards so a literal % or _ in the term matches literally
    // (MySQL uses backslash as the default LIKE escape character).
    $like = '%' . addcslashes($searchTerm, '%_\\') . '%';
    $whereParts[] = "(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ? OR p.slug LIKE ?)";
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
    $whereParams[] = $like;
}
$whereSql = 'WHERE ' . implode(' AND ', $whereParts);

/* ---------- Pagination ---------- */
$perPage = 12;
$totalRows = (int)($db->fetchOne("SELECT COUNT(*) AS c $fromSql $whereSql", $whereParams)['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));

$page = (int)($_GET['page'] ?? 1);
$page = max(1, min($page, $totalPages)); // clamp out-of-range / hostile ?page= values
$offset = ($page - 1) * $perPage;

// "Most Popular" is driven by units actually sold. Cancelled orders are
// excluded and the total is aggregated in a derived table, so every product
// still appears exactly once (no join fan-out inflating the rows).
$soldJoin = ($sortby === 'popularity')
    ? "LEFT JOIN (
           SELECT oi.product_id, SUM(oi.quantity) AS sold_qty
           FROM order_items oi
           JOIN orders o ON o.id = oi.order_id
           WHERE o.order_status <> 'cancelled'
           GROUP BY oi.product_id
       ) sold ON sold.product_id = p.id"
    : '';

$orderSql = match ($sortby) {
    'popularity' => 'ORDER BY COALESCE(sold.sold_qty, 0) DESC, p.created_at DESC, p.id DESC',
    'price_low'  => 'ORDER BY p.price ASC, p.id ASC',
    'price_high' => 'ORDER BY p.price DESC, p.id DESC',
    'name_asc'   => 'ORDER BY p.name ASC, p.id ASC',
    default      => 'ORDER BY p.created_at DESC, p.id DESC',
};

$products = $db->fetchAll(
    "SELECT p.id, p.name, p.price, p.image, c.name AS category_name, c.slug AS category_slug
     $fromSql
     $soldJoin
     $whereSql
     $orderSql
     LIMIT ? OFFSET ?",
    array_merge($whereParams, [$perPage, $offset])
);

// Builds a page link that preserves every active filter.
$pageUrl = static function (int $n) use ($currentParams): string {
    $params = $currentParams;
    if ($n > 1) {
        $params['page'] = $n;
    }
    return FRONT_URL . '/shop.php' . (!empty($params) ? '?' . http_build_query($params) : '');
};

$page_title = $searchTerm !== ''
    ? 'Search results for "' . $searchTerm . '" - Molla eCommerce'
    : 'Shop - Electronics Catalog';
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
                        <div class="toolbox-left">
                            <div class="toolbox-info">
                                Showing <?= $totalRows > 0 ? $offset + 1 : 0 ?>&ndash;<?= min($offset + $perPage, $totalRows) ?> of <span><?= $totalRows ?></span> Product<?= $totalRows === 1 ? '' : 's' ?>
                                <?php if ($searchTerm !== ''): ?>
                                    for &ldquo;<strong><?= htmlspecialchars($searchTerm) ?></strong>&rdquo;
                                    <a href="<?= FRONT_URL ?>/shop.php" class="ms-2">Clear</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="toolbox-right">
                            <form action="<?= FRONT_URL ?>/shop.php" method="get" class="toolbox-search d-flex align-items-center me-4">
                                <?php if ($selectedCategory !== ''): ?><input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>"><?php endif; ?>
                                <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= (float)$minPrice ?>"><?php endif; ?>
                                <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= (float)$maxPrice ?>"><?php endif; ?>
                                <?php if ($maxPriceEx !== null): ?><input type="hidden" name="max_price_ex" value="<?= (float)$maxPriceEx ?>"><?php endif; ?>
                                <?php if ($sortby !== $sortDefault): ?><input type="hidden" name="sortby" value="<?= htmlspecialchars($sortby) ?>"><?php endif; ?>
                                <input type="search" name="q" class="form-control" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search products..." aria-label="Search products" style="width: 220px;">
                                <button class="btn btn-primary" type="submit"><i class="icon-search"></i></button>
                            </form>
                            <form action="<?= FRONT_URL ?>/shop.php" method="get" class="toolbox-sort d-flex align-items-center">
                                <?php if ($selectedCategory !== ''): ?><input type="hidden" name="category" value="<?= htmlspecialchars($selectedCategory) ?>"><?php endif; ?>
                                <?php if ($searchTerm !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($searchTerm) ?>"><?php endif; ?>
                                <?php if ($minPrice !== null): ?><input type="hidden" name="min_price" value="<?= (float)$minPrice ?>"><?php endif; ?>
                                <?php if ($maxPrice !== null): ?><input type="hidden" name="max_price" value="<?= (float)$maxPrice ?>"><?php endif; ?>
                                <?php if ($maxPriceEx !== null): ?><input type="hidden" name="max_price_ex" value="<?= (float)$maxPriceEx ?>"><?php endif; ?>
                                <label for="sortby" class="me-2 mb-0">Sort by:</label>
                                <div class="select-custom">
                                    <select name="sortby" id="sortby" class="form-control" onchange="this.form.submit()">
                                        <?php foreach ($sortOptions as $sortValue => $sortLabel): ?>
                                            <option value="<?= $sortValue ?>"<?= $sortby === $sortValue ? ' selected' : '' ?>><?= $sortLabel ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <noscript><button class="btn btn-primary btn-sm ms-2" type="submit">Go</button></noscript>
                            </form>
                        </div>
                    </div>

                    <div class="products mb-3">
                        <div class="row justify-content-center">
                            <?php if (empty($products)): ?>
                                <div class="col-12 text-center py-5">
                                    <?php if ($searchTerm !== ''): ?>
                                        <p class="text-muted mb-3">No products match &ldquo;<strong><?= htmlspecialchars($searchTerm) ?></strong>&rdquo;.</p>
                                        <a href="<?= FRONT_URL ?>/shop.php" class="btn btn-outline-dark btn-sm">Browse all products</a>
                                    <?php else: ?>
                                        <p class="text-muted mb-0">No products available.</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <div class="col-6 col-md-4 col-lg-4">
                                        <div class="product product-7 text-center">
                                            <figure class="product-media">
                                                <span class="product-label label-top">Top</span>
                                                <a href="<?= FRONT_URL ?>/product.php?id=<?= (int)$product['id'] ?>">
                                                    <?php $productImages = ProductImage::available((int)$product['id'], $product['image'] ?? null); ?>
                                                    <?php $productImage = !empty($productImages) ? ProductImage::url((int)$product['id'], $productImages[0]) : $placeholderImage; ?>
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

                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item<?= $page > 1 ? '' : ' disabled' ?>">
                                <a class="page-link page-link-prev" href="<?= $page > 1 ? htmlspecialchars($pageUrl($page - 1)) : '#' ?>" aria-label="Previous">
                                    <span aria-hidden="true"><i class="icon-long-arrow-left"></i></span>Prev
                                </a>
                            </li>

                            <?php for ($n = 1; $n <= $totalPages; $n++): ?>
                                <li class="page-item<?= $n === $page ? ' active' : '' ?>">
                                    <a class="page-link" href="<?= htmlspecialchars($pageUrl($n)) ?>"><?= $n ?></a>
                                </li>
                            <?php endfor; ?>

                            <li class="page-item<?= $page < $totalPages ? '' : ' disabled' ?>">
                                <a class="page-link page-link-next" href="<?= $page < $totalPages ? htmlspecialchars($pageUrl($page + 1)) : '#' ?>" aria-label="Next">
                                    Next <span aria-hidden="true"><i class="icon-long-arrow-right"></i></span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>

                <aside class="col-lg-3 order-lg-first">
                    <div class="sidebar sidebar-shop">
                        <div class="widget widget-clean"><label>Filters:</label><a href="<?= FRONT_URL ?>/shop.php" class="sidebar-filter-clear">Clean All</a></div>
                        <div class="widget widget-collapsible">
                            <h3 class="widget-title"><a data-toggle="collapse" href="#widget-1" role="button" aria-expanded="true" aria-controls="widget-1">Category</a></h3>
                            <div class="collapse show" id="widget-1"><div class="widget-body"><div class="filter-items filter-items-count">
                                <?php if (!empty($shop_categories)): ?>
                                    <?php foreach ($shop_categories as $category): ?>
                                        <?php $catParams = array_merge($keepParams, ['category' => $category['slug']]); $catUrl = FRONT_URL . '/shop.php?' . http_build_query($catParams); ?>
                                        <div class="filter-item">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="cat-<?= (int)$category['id'] ?>" name="category[]" value="<?= htmlspecialchars($category['slug']) ?>">
                                                <label class="custom-control-label" for="cat-<?= (int)$category['id'] ?>"><a href="<?= htmlspecialchars($catUrl) ?>"><?= htmlspecialchars($category['name']) ?></a></label>
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
                                <?php
                                $shopPriceRanges = [
                                    ['Under $100', '', 100],
                                    ['$100 - $500', 100, 500],
                                    ['$500 - $1,000', 500, 1000],
                                    ['$1,000 & Above', 1000, ''],
                                ];
                                foreach ($shopPriceRanges as [$rangeLabel, $rangeMin, $rangeMax]):
                                    $rangeQuery = [];
                                    if ($selectedCategory !== '') { $rangeQuery['category'] = $selectedCategory; }
                                    if ($searchTerm !== '') { $rangeQuery['q'] = $searchTerm; }
                                    if ($rangeMin !== '') { $rangeQuery['min_price'] = $rangeMin; }
                                    if ($rangeMax !== '') { $rangeQuery['max_price_ex'] = $rangeMax; }
                                ?>
                                    <div class="filter-item"><a href="<?= FRONT_URL ?>/shop.php?<?= htmlspecialchars(http_build_query($rangeQuery)) ?>"><?= $rangeLabel ?></a></div>
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
