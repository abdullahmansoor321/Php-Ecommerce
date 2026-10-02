<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/FileUploader.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/AdminAuth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Slug.php';
require_once __DIR__ . '/../../core/ProductImage.php';

Session::startAdmin();
AdminAuth::requireAdmin();

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
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Session::setFlash('error', 'Security check failed. Please try again.');
        header('Location: ' . APP_URL . '/admin/products/create.php');
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = Slug::generate($name);
    }
    $categoryId = trim($_POST['category_id'] ?? '');
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
            $imageNames = [];
            $imageError = null;

            if (isset($_FILES['images'])) {
                $oversize = count(array_filter($_FILES['images']['name'] ?? [])) > FileUploader::MAX_IMAGES;
                if ($oversize) {
                    $imageError = 'You can upload at most ' . FileUploader::MAX_IMAGES . ' images at once.';
                } else {
                    [$storedByIndex, $rejected] = FileUploader::uploadImagesIndexed($_FILES['images'], BASE_PATH . '/public/uploads/products');
                    $order = is_array($_POST['image_order'] ?? null) ? $_POST['image_order'] : [];
                    $galleryManaged = ($_POST['image_manager'] ?? '0') === '1';
                    foreach ($order as $token) {
                        if (is_string($token) && preg_match('/^new:(\d+)$/', $token, $match)) {
                            $index = (int)$match[1];
                            if (isset($storedByIndex[$index])) {
                                $imageNames[] = $storedByIndex[$index];
                                unset($storedByIndex[$index]);
                            }
                        }
                    }
                    foreach ($storedByIndex as $storedName) {
                        if ($galleryManaged) {
                            $staged = BASE_PATH . '/public/uploads/products/' . $storedName;
                            if (is_file($staged)) {
                                @unlink($staged);
                            }
                        } else {
                            $imageNames[] = $storedName;
                        }
                    }

                    if (empty($imageNames)) {
                        $imageError = 'No valid images. Use JPEG, PNG, WEBP or GIF, max 2MB each.';
                    } elseif ($rejected > 0) {
                        Session::setFlash('error', $rejected . ' image(s) were skipped: not a supported image type or over 2MB.');
                    }
                }
            } else {
                $imageError = 'At least one product image is required.';
            }

            if (empty($errors) && $imageError !== null) {
                $errors['image'] = $imageError;
            }

            if (empty($errors)) {
                $connection = $db->getConnection();
                $newProductId = 0;
                $productFolder = '';
                $transactionStarted = false;

                try {
                    $connection->begin_transaction();
                    $transactionStarted = true;
                    $db->query(
                        "INSERT INTO products (category_id, name, slug, description, price, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                        [$categoryId !== '' ? (int)$categoryId : 0, $name, $slug, $description, (float)$price, $stock, ProductImage::encode([]), $status]
                    );

                    $newProductId = $db->lastInsertId();
                    $productFolder = UPLOADS_PATH . '/products/' . $newProductId;
                    if (!is_dir($productFolder) && !mkdir($productFolder, 0755, true) && !is_dir($productFolder)) {
                        throw new RuntimeException('Unable to create the product image folder.');
                    }

                    $finalNames = [];
                    foreach ($imageNames as $stagedName) {
                        $staged = BASE_PATH . '/public/uploads/products/' . $stagedName;
                        if (!is_file($staged) || !@rename($staged, $productFolder . '/' . $stagedName)) {
                            throw new RuntimeException('Unable to move a product image.');
                        }
                        $finalNames[] = $stagedName;
                    }

                    if (empty($finalNames)) {
                        throw new RuntimeException('No product images were saved.');
                    }

                    $db->query(
                        "UPDATE products SET image = ? WHERE id = ?",
                        [ProductImage::encode($finalNames), $newProductId]
                    );
                    $connection->commit();

                    Session::setFlash('success', 'Product created successfully!');
                    header('Location: ' . APP_URL . '/admin/products/index.php');
                    exit;
                } catch (Throwable $exception) {
                    if ($transactionStarted) {
                        $connection->rollback();
                    }
                    foreach ($imageNames as $stagedName) {
                        $staged = BASE_PATH . '/public/uploads/products/' . $stagedName;
                        $final = $productFolder !== '' ? $productFolder . '/' . $stagedName : '';
                        if (is_file($staged)) {
                            @unlink($staged);
                        }
                        if ($final !== '' && is_file($final)) {
                            @unlink($final);
                        }
                    }
                    if ($productFolder !== '' && is_dir($productFolder)) {
                        @rmdir($productFolder);
                    }
                    $errors['image'] = 'The product and its images could not be saved. Please try again.';
                }
            }
        }
    }
}

$page_title = "Add Product";
$page_stylesheets = [ADMIN_ASSETS . '/css/product-image-manager.css'];
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

                <form role="form" action="" method="POST" enctype="multipart/form-data" novalidate>
                    <?= Csrf::field() ?>
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

                    <div class="mb-4" data-product-image-manager data-max-images="<?= FileUploader::MAX_IMAGES ?>" data-max-bytes="<?= FileUploader::MAX_BYTES ?>" data-required="true">
                        <label class="form-label text-dark text-xs font-weight-bold mb-2 d-block">Product Images *</label>
                        <div class="product-image-dropzone" data-image-dropzone>
                            <label class="product-image-picker">
                                Add images
                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-image-input>
                            </label>
                            <span class="text-xs text-secondary ms-2">or drop files here. JPEG, PNG, WEBP or GIF; up to 2MB each.</span>
                            <p class="product-image-feedback mb-0" data-image-feedback role="status" aria-live="polite"></p>
                            <p class="product-image-errors" data-image-errors role="alert"></p>
                            <div class="product-image-grid" data-image-grid></div>
                            <input type="hidden" name="image_manager" value="0" data-image-manager-state>
                            <div data-image-order></div>
                        </div>
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

<script src="<?= ADMIN_ASSETS ?>/js/product-image-manager.js"></script>
<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
