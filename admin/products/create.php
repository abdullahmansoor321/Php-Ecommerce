<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/FileUploader.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/Auth.php';

Session::start();
Auth::requireAdmin();

$db = Database::getInstance();
$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");

$errors = [];
$name = '';
$categoryId = '';
$description = '';
$price = '';
$stock = '';
$status = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
    }
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $stock = (int)($_POST['stock'] ?? 0);
    $status = isset($_POST['status']) ? 1 : 0;

    $validator = new Validator([
        'name' => $name,
        'slug' => $slug,
        'price' => $price,
        'category_id' => $categoryId
    ]);

    if (!$validator->validate([
        'name' => 'required|min:2',
        'slug' => 'required',
        'price' => 'required|numeric',
        'category_id' => 'required'
    ])) {
        $errors = $validator->errors();
    } else {
        $existing = $db->fetchOne("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existing) {
            $errors['slug'] = 'Product slug already exists. Choose another.';
        } else {
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = BASE_PATH . '/public/uploads/products';
                $imageName = FileUploader::uploadImage($_FILES['image'], $uploadDir);
                if (!$imageName) {
                    $errors['image'] = 'Invalid image file or size exceeds 2MB limit (allowed: JPEG, PNG, WEBP).';
                }
            } else {
                $errors['image'] = 'Product image is required.';
            }

            if (empty($errors)) {
                $db->query(
                    "INSERT INTO products (category_id, name, slug, description, price, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                    [$categoryId, $name, $slug, $description, (float)$price, $stock, $imageName, $status]
                );

                Session::setFlash('success', 'Product created successfully!');
                header('Location: ' . APP_URL . '/admin/products/index.php');
                exit;
            }
        }
    }
}

$page_title = "Add Product";
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row">
    <div class="col-lg-10 col-md-12 mx-auto">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">Add New Product</h6>
                    <a href="<?= APP_URL ?>/admin/products/index.php" class="btn btn-sm bg-gradient-light mb-0">Back to List</a>
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger text-white mb-4" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form role="form" action="" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group input-group-outline mb-3 <?= !empty($name) ? 'is-filled' : '' ?>">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($name) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-outline mb-3">
                                <label class="form-label">Slug (Optional - auto generated)</label>
                                <input type="text" class="form-control" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group input-group-outline mb-3 is-filled">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= $categoryId == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-outline mb-3 <?= !empty($price) ? 'is-filled' : '' ?>">
                                <label class="form-label">Price ($) *</label>
                                <input type="number" step="0.01" class="form-control" name="price" required value="<?= htmlspecialchars($price) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-outline mb-3 <?= $stock !== '' ? 'is-filled' : '' ?>">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock" required value="<?= htmlspecialchars($stock !== '' ? $stock : '10') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="input-group input-group-outline mb-3 <?= !empty($description) ? 'is-filled' : '' ?>">
                        <textarea class="form-control" name="description" rows="4" placeholder="Product description..."><?= htmlspecialchars($description) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs text-secondary">Product Image * (Max 2MB)</label>
                        <input type="file" class="form-control border px-2 py-1" name="image" accept="image/*" required>
                    </div>

                    <div class="form-check form-switch ps-0 ms-0 mb-4">
                        <input class="form-check-input ms-auto" type="checkbox" id="statusCheck" name="status" value="1" <?= $status == 1 ? 'checked' : '' ?>>
                        <label class="form-check-label text-body ms-3 text-truncate w-80" for="statusCheck">Active Status</label>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn bg-gradient-dark w-100 my-2">Create Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
