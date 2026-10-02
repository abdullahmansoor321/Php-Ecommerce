<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/FileUploader.php';
require_once __DIR__ . '/../../core/Validator.php';
require_once __DIR__ . '/../../core/AdminAuth.php';
require_once __DIR__ . '/../../core/Csrf.php';
require_once __DIR__ . '/../../core/Slug.php';

Session::startAdmin();
AdminAuth::requireAdmin();

$db = Database::getInstance();
$id = (int)($_GET['id'] ?? 0);

$category = $db->fetchOne("SELECT * FROM categories WHERE id = ?", [$id]);
if (!$category) {
    Session::setFlash('error', 'Category not found.');
    header('Location: ' . APP_URL . '/admin/categories/index.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
        Session::setFlash('error', 'Security check failed. Please try again.');
        header('Location: ' . APP_URL . '/admin/categories/edit.php?id=' . $id);
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = Slug::generate($name);
    }
    $status = isset($_POST['status']) ? 1 : 0;

    $validator = new Validator(['name' => $name, 'slug' => $slug]);
    if (!$validator->validate(['name' => 'required|min:2', 'slug' => 'required'])) {
        $errors = $validator->errors();
    } else {
        $existing = $db->fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $id]);
        if ($existing) {
            $errors['slug'] = 'Category slug already exists for another category.';
        } else {
            $imageName = $category['image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = BASE_PATH . '/public/uploads/categories';
                $uploaded = FileUploader::uploadImage($_FILES['image'], $uploadDir);
                if ($uploaded) {
                    if (!empty($category['image']) && file_exists($uploadDir . '/' . $category['image'])) {
                        @unlink($uploadDir . '/' . $category['image']);
                    }
                    $imageName = $uploaded;
                } else {
                    $errors['image'] = 'Invalid image file or size exceeds 2MB limit.';
                }
            }

            if (empty($errors)) {
                $db->query(
                    "UPDATE categories SET name = ?, slug = ?, image = ?, status = ? WHERE id = ?",
                    [$name, $slug, $imageName, $status, $id]
                );

                Session::setFlash('success', 'Category updated successfully!');
                header('Location: ' . APP_URL . '/admin/categories/index.php');
                exit;
            }
        }
    }
}

$page_title = "Edit Category";
require_once __DIR__ . '/../../includes/admin-header.php';
?>

<div class="row">
    <div class="col-lg-8 col-md-10 mx-auto">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">Edit Category: <?= htmlspecialchars($category['name']) ?></h6>
                    <a href="<?= APP_URL ?>/admin/categories/index.php" class="btn btn-sm bg-gradient-light mb-0">Back to List</a>
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
                    <div class="input-group input-group-outline mb-3 is-filled">
                        <label class="form-label">Category Name *</label>
                        <input type="text" class="form-control" name="name" required value="<?= htmlspecialchars($_POST['name'] ?? $category['name']) ?>">
                    </div>

                    <div class="input-group input-group-outline mb-3 is-filled">
                        <label class="form-label">Slug (Optional - auto generated)</label>
                        <input type="text" class="form-control" name="slug" value="<?= htmlspecialchars($_POST['slug'] ?? $category['slug']) ?>">
                    </div>

                    <?php
                    $currentImageFile = !empty($category['image']) ? basename($category['image']) : '';
                    if ($currentImageFile !== '' && is_file(BASE_PATH . '/public/uploads/categories/' . $currentImageFile)):
                    ?>
                        <div class="mb-3">
                            <label class="form-label text-xs text-secondary d-block">Current Image:</label>
                            <img src="<?= UPLOADS_URL ?>/categories/<?= rawurlencode($currentImageFile) ?>" alt="Category Image" class="border-radius-lg" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label text-xs text-secondary">Replace Image (Max 2MB)</label>
                        <input type="file" class="form-control border px-2 py-1" name="image" accept="image/*">
                    </div>

                    <div class="form-check form-switch ps-0 ms-0 mb-4">
                        <input class="form-check-input ms-auto" type="checkbox" id="statusCheck" name="status" value="1" <?= (($_POST['status'] ?? $category['status']) == 1) ? 'checked' : '' ?>>
                        <label class="form-check-label text-body ms-3 text-truncate w-80" for="statusCheck">Active Status</label>
                    </div>

                    <div class="text-center">
                        <button type="submit" class="btn bg-gradient-dark w-100 my-2">Update Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
