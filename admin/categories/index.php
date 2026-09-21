<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';

$page_title = "Manage Categories";
require_once __DIR__ . '/../../includes/admin-header.php';

$db = Database::getInstance();
$categories = $db->fetchAll("SELECT * FROM categories ORDER BY created_at DESC");
?>

<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center px-4">
                    <h6 class="text-white text-capitalize m-0">Category Management</h6>
                    <a href="<?= APP_URL ?>/admin/categories/create.php" class="btn btn-sm bg-gradient-light mb-0">
                        <i class="material-symbols-rounded text-sm">add</i> Add New Category
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
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Image</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Name & Slug</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Created At</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No categories found. Create your first category!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1">
                                                <div>
                                                    <?php
                                                    $catImageFile = !empty($cat['image']) ? basename($cat['image']) : '';
                                                    if ($catImageFile !== '' && is_file(BASE_PATH . '/public/uploads/categories/' . $catImageFile)):
                                                    ?>
                                                        <img src="<?= UPLOADS_URL ?>/categories/<?= rawurlencode($catImageFile) ?>" class="avatar avatar-sm me-3 border-radius-lg" alt="category image">
                                                    <?php else: ?>
                                                        <div class="avatar avatar-sm me-3 bg-gradient-secondary border-radius-lg d-flex align-items-center justify-content-center text-white">
                                                            <i class="material-symbols-rounded text-sm">category</i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <h6 class="mb-0 text-sm"><?= htmlspecialchars($cat['name']) ?></h6>
                                            <p class="text-xs text-secondary mb-0">slug: <?= htmlspecialchars($cat['slug']) ?></p>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-<?= $cat['status'] == 1 ? 'success' : 'secondary' ?>">
                                                <?= $cat['status'] == 1 ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold"><?= $cat['created_at'] ?></span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <a href="<?= APP_URL ?>/admin/categories/edit.php?id=<?= $cat['id'] ?>" class="btn btn-link text-dark px-2 mb-0">
                                                <i class="material-symbols-rounded text-sm me-1">edit</i>Edit
                                            </a>
                                            <a href="<?= APP_URL ?>/admin/categories/delete.php?id=<?= $cat['id'] ?>" class="btn btn-link text-danger px-2 mb-0" onclick="return confirm('Are you sure you want to delete this category?');">
                                                <i class="material-symbols-rounded text-sm me-1">delete</i>Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../../includes/admin-footer.php';
?>
