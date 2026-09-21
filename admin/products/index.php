<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';

$page_title = "Manage Products";
require_once __DIR__ . '/../../includes/admin-header.php';

$db = Database::getInstance();

// Pagination setup
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalRows = (int)($db->fetchOne("SELECT COUNT(*) as c FROM products")['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

// Fetch products with their category names
$products = $db->fetchAll("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
", [$perPage, $offset]);
?>

<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">Product Catalog Management</h6>
                    <a href="<?= APP_URL ?>/admin/products/create.php" class="btn btn-sm bg-gradient-light mb-0">
                        <i class="material-symbols-rounded text-sm">add</i> Add New Product
                    </a>
                </div>
            </div>
            
            <div class="card-body px-0 pb-2">
                <?php if ($success = Session::getFlash('success')): ?>
                    <div class="alert alert-success text-white mx-4" role="alert"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                <?php if ($error = Session::getFlash('error')): ?>
                    <div class="alert alert-danger text-white mx-4" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="table-responsive p-0">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Product</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Category</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Price</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Stock</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No products found. Add your first product!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $prod): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1 align-items-center">
                                                <div>
                                                    <?php
                                                    $prodImageFile = !empty($prod['image']) ? basename($prod['image']) : '';
                                                    if ($prodImageFile !== '' && is_file(BASE_PATH . '/public/uploads/products/' . $prodImageFile)):
                                                    ?>
                                                        <img src="<?= UPLOADS_URL ?>/products/<?= rawurlencode($prodImageFile) ?>" class="avatar avatar-sm me-3 border-radius-lg" alt="product image" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="avatar avatar-sm me-3 bg-gradient-secondary border-radius-lg d-flex align-items-center justify-content-center text-white">
                                                            <i class="material-symbols-rounded text-sm">inventory_2</i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($prod['name']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0">slug: <?= htmlspecialchars($prod['slug']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary text-xs font-weight-bold"><?= htmlspecialchars($prod['category_name']) ?></span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="text-secondary text-xs font-weight-bold">$<?= number_format((float)$prod['price'], 2) ?></span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-<?= $prod['stock'] > 0 ? 'success' : 'danger' ?>">
                                                <?= $prod['stock'] ?> units
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-<?= $prod['status'] == 1 ? 'success' : 'secondary' ?>">
                                                <?= $prod['status'] == 1 ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <a href="<?= APP_URL ?>/admin/products/edit.php?id=<?= $prod['id'] ?>" class="btn btn-link text-dark px-2 mb-0">
                                                <i class="material-symbols-rounded text-sm me-1">edit</i>Edit
                                            </a>
                                            <a href="<?= APP_URL ?>/admin/products/delete.php?id=<?= $prod['id'] ?>" class="btn btn-link text-danger px-2 mb-0" onclick="return confirm('Are you sure you want to delete this product?');">
                                                <i class="material-symbols-rounded text-sm me-1">delete</i>Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php require __DIR__ . '/../../includes/admin-pagination.php'; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
