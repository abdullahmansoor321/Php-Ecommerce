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
$id = (int)($_GET['id'] ?? 0);

$product = $db->fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
if (!$product) {
    Session::setFlash('error', 'Product not found.');
    header('Location: ' . APP_URL . '/admin/products/index.php');
    exit;
}

$categories = $db->fetchAll("SELECT id, name FROM categories WHERE status = 1 ORDER BY name ASC");
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Session::setFlash('error', 'Security check failed. Please try again.');
        header('Location: ' . APP_URL . '/admin/products/edit.php?id=' . $id);
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
        $existing = $db->fetchOne("SELECT id FROM products WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $errors['slug'] = 'Product slug already exists for another product.';
        } else {
            $existingImages = ProductImage::all($product['image'] ?? null);
            $galleryManaged = ($_POST['image_manager'] ?? '0') === '1';
            $imageError = null;
            $submittedOrder = $_POST['image_order'] ?? array_map(function ($name) {
                return 'existing:' . $name;
            }, $existingImages);
            $submittedOrder = is_array($submittedOrder) ? $submittedOrder : [];
            $orderedExisting = [];
            $seenTokens = [];

            foreach ($submittedOrder as $token) {
                if (!is_string($token) || isset($seenTokens[$token])) {
                    $imageError = 'The image order is invalid. Please review the gallery and try again.';
                    break;
                }
                $seenTokens[$token] = true;

                if (strncmp($token, 'existing:', 9) === 0) {
                    $filename = substr($token, 9);
                    if (!in_array($filename, $existingImages, true)) {
                        $imageError = 'The image order is invalid. Please review the gallery and try again.';
                        break;
                    }
                    $orderedExisting[] = $filename;
                } elseif (strncmp($token, 'new:', 4) !== 0 || !ctype_digit(substr($token, 4))) {
                    $imageError = 'The image order is invalid. Please review the gallery and try again.';
                    break;
                }
            }

            $incoming = count(array_filter($_FILES['images']['name'] ?? []));
            $room = FileUploader::MAX_IMAGES - count($orderedExisting);
            if ($imageError === null && $incoming > $room) {
                $imageError = 'This gallery has ' . count($orderedExisting) . ' image(s). You can add ' . max(0, $room)
                    . ' more (limit ' . FileUploader::MAX_IMAGES . '). Remove an image or choose fewer files.';
            }

            $storedByIndex = [];
            $uploadedImageNames = [];
            if ($imageError === null && $incoming > 0) {
                [$storedByIndex, $rejected] = FileUploader::uploadImagesIndexed($_FILES['images'], UPLOADS_PATH . '/products/' . $id);
                $uploadedImageNames = array_values($storedByIndex);
                if (empty($storedByIndex) && $rejected > 0 && empty($orderedExisting)) {
                    $imageError = 'No valid images. Use JPEG, PNG, WEBP or GIF, max 2MB each.';
                } elseif ($rejected > 0) {
                    Session::setFlash('error', $rejected . ' image(s) were skipped: not a supported image type or over 2MB.');
                }
            }

            $imageNames = [];
            $remainingExisting = array_fill_keys($orderedExisting, true);
            foreach ($submittedOrder as $token) {
                if (!is_string($token)) {
                    continue;
                }
                if (strncmp($token, 'existing:', 9) === 0) {
                    $filename = substr($token, 9);
                    if (isset($remainingExisting[$filename])) {
                        $imageNames[] = $filename;
                        unset($remainingExisting[$filename]);
                    }
                } elseif (preg_match('/^new:(\d+)$/', $token, $match)) {
                    $index = (int)$match[1];
                    if (isset($storedByIndex[$index])) {
                        $imageNames[] = $storedByIndex[$index];
                        unset($storedByIndex[$index]);
                    }
                }
            }
            foreach ($orderedExisting as $filename) {
                if (isset($remainingExisting[$filename])) {
                    $imageNames[] = $filename;
                }
            }
            foreach ($storedByIndex as $filename) {
                if ($galleryManaged) {
                    $unusedPath = ProductImage::path($id, $filename);
                    if ($unusedPath !== '' && is_file($unusedPath)) {
                        @unlink($unusedPath);
                    }
                } else {
                    $imageNames[] = $filename;
                }
            }
            $imageNames = array_slice($imageNames, 0, FileUploader::MAX_IMAGES);

            if ($imageError === null && empty($imageNames)) {
                $imageError = 'Keep or add at least one product image.';
            }

            if (empty($errors) && $imageError !== null) {
                $errors['image'] = $imageError;
            }

            if (empty($errors)) {
                try {
                    $db->query(
                        "UPDATE products SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, stock = ?, image = ?, status = ? WHERE id = ?",
                        [$categoryId !== '' ? (int)$categoryId : 0, $name, $slug, $description, (float)$price, $stock, ProductImage::encode($imageNames), $status, $id]
                    );

                    foreach ($existingImages as $oldImage) {
                        if (!in_array($oldImage, $imageNames, true)) {
                            $oldPath = ProductImage::path($id, $oldImage);
                            if ($oldPath !== '' && is_file($oldPath)) {
                                @unlink($oldPath);
                            }
                        }
                    }

                    Session::setFlash('success', 'Product updated successfully!');
                    header('Location: ' . APP_URL . '/admin/products/index.php');
                    exit;
                } catch (Throwable $exception) {
                    foreach ($uploadedImageNames as $uploadedName) {
                        $uploadedPath = ProductImage::path($id, $uploadedName);
                        if ($uploadedPath !== '' && is_file($uploadedPath)) {
                            @unlink($uploadedPath);
                        }
                    }
                    $errors['image'] = 'The product and its images could not be updated. Please try again.';
                }
            }
        }
    }
}

$page_title = "Edit Product";
$page_stylesheets = [ADMIN_ASSETS . '/css/product-image-manager.css'];
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row">
    <div class="col-lg-10 col-md-12 mx-auto">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">Edit Product: <?= htmlspecialchars($product['name']) ?></h6>
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
                            <div class="input-group input-group-outline mb-3 is-filled">
                                <label class="form-label">Product Name *</label>
                                <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="input-group input-group-outline mb-3 is-filled">
                                <label class="form-label">Slug (Optional - auto generated)</label>
                                <input type="text" class="form-control" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? $product['slug']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="input-group input-group-outline mb-3 is-filled">
                                <label class="form-label">Category *</label>
                                <select name="category_id" class="form-control" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ((int)($_POST['category_id'] ?? $product['category_id']) === (int)$cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-outline mb-3 is-filled">
                                <label class="form-label">Price ($) *</label>
                                <input type="number" step="0.01" class="form-control" name="price" required value="<?= htmlspecialchars($_POST['price'] ?? $product['price']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group input-group-outline mb-3 is-filled">
                                <label class="form-label">Stock Quantity *</label>
                                <input type="number" class="form-control" name="stock" required value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>">
                            </div>
                        </div>
                    </div>

                    <div class="input-group input-group-outline mb-3 is-filled">
                        <textarea class="form-control" name="description" rows="4" placeholder="Product description..."><?= htmlspecialchars($_POST['description'] ?? $product['description']) ?></textarea>
                    </div>

                    <?php $currentImages = ProductImage::all($product['image'] ?? null); ?>
                    <div class="mb-4" data-product-image-manager data-max-images="<?= FileUploader::MAX_IMAGES ?>" data-max-bytes="<?= FileUploader::MAX_BYTES ?>" data-required="true">
                        <label class="form-label text-dark text-xs font-weight-bold mb-2 d-block">Product Images *</label>
                        <div class="product-image-dropzone" data-image-dropzone>
                            <label class="product-image-picker">
                                Add images
                                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple data-image-input>
                            </label>
                            <span class="text-xs text-secondary ms-2">JPEG, PNG, WEBP or GIF; up to 2MB each. Move images to change the thumbnail.</span>
                            <p class="product-image-feedback mb-0" data-image-feedback role="status" aria-live="polite"></p>
                            <p class="product-image-errors" data-image-errors role="alert"></p>
                            <div class="product-image-grid" data-image-grid>
                                <?php foreach ($currentImages as $imageIndex => $currentName): ?>
                                    <div class="product-image-card" data-token="existing:<?= htmlspecialchars($currentName, ENT_QUOTES) ?>" data-label="Saved image <?= $imageIndex + 1 ?>">
                                        <img src="<?= htmlspecialchars(ProductImage::url($id, $currentName)) ?>" alt="Product image <?= $imageIndex + 1 ?>">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="image_manager" value="0" data-image-manager-state>
                            <div data-image-order>
                                <?php foreach ($currentImages as $currentName): ?>
                                    <input type="hidden" name="image_order[]" value="existing:<?= htmlspecialchars($currentName, ENT_QUOTES) ?>">
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-check form-switch ps-0 ms-0 mb-4">
                        <input class="form-check-input ms-auto" type="checkbox" id="statusCheck" name="status" value="1" <?= (($_POST['status'] ?? $product['status']) == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-body ms-3 text-truncate w-80" for="statusCheck">Active Status</label>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn bg-gradient-dark w-100 my-2">Update Product</button>
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
