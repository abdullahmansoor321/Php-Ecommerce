<?php
require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Session.php';
require_once __DIR__ . '/../../core/Csrf.php';

$page_title = "Manage Users";
require_once __DIR__ . '/../../includes/admin-header.php';

$db = Database::getInstance();

// Pagination setup
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalRows = (int)($db->fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$users = $db->fetchAll("SELECT id, name, email, role, is_active, created_at FROM users ORDER BY created_at DESC LIMIT ? OFFSET ?", [$perPage, $offset]);
?>

<div class="row">
    <div class="col-12">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="text-white text-capitalize m-0">User Management</h6>
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
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">User</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Role</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Registered At</th>
                                <th class="text-secondary opacity-7"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No users registered yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex px-3 py-1 align-items-center">
                                                <div class="avatar avatar-sm me-3 bg-gradient-secondary border-radius-lg d-flex align-items-center justify-content-center text-white font-weight-bold">
                                                    <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm"><?= htmlspecialchars($u['name']) ?></h6>
                                                    <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($u['email']) ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm bg-gradient-<?= $u['role'] === 'admin' ? 'dark' : 'info' ?>">
                                                <?= ucfirst($u['role']) ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="badge badge-sm bg-gradient-<?= $u['is_active'] == 1 ? 'success' : 'secondary' ?>">
                                                <?= $u['is_active'] == 1 ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-center">
                                            <span class="text-secondary text-xs font-weight-bold"><?= $u['created_at'] ?></span>
                                        </td>
                                        <td class="align-middle text-end pe-4">
                                            <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                                                <?php if ($u['is_active'] == 1): ?>
                                                <form method="POST" action="<?= APP_URL ?>/admin/users/toggle.php" class="d-inline" onsubmit="return confirm('Deactivate this user?');">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <?= Csrf::field() ?>
                                                    <button type="submit" class="btn btn-link text-warning px-3 mb-0 text-xs font-weight-bold">Deactivate</button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST" action="<?= APP_URL ?>/admin/users/toggle.php" class="d-inline" onsubmit="return confirm('Activate this user?');">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <?= Csrf::field() ?>
                                                    <button type="submit" class="btn btn-link text-success px-3 mb-0 text-xs font-weight-bold">Activate</button>
                                                </form>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-xs text-muted me-3">Current Admin</span>
                                            <?php endif; ?>
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
