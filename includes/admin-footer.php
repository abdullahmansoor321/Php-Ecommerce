<?php
/**
 * Shared admin footer.
 *
 * Renders the dashboard footer navigation, then closes the containers that
 * includes/admin-header.php opened (<div class="container-fluid"> and
 * <main class="main-content">) before loading the core JS files.
 */
$adminFooterYear = date('Y');
?>
      <footer class="footer py-4">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-5 mb-lg-0 mb-3">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                &copy; <?= $adminFooterYear ?> <span class="font-weight-bold">Molla E-Commerce</span> &middot; Admin Dashboard
              </div>
            </div>
            <div class="col-lg-7">
              <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                <li class="nav-item">
                  <a href="<?= APP_URL ?>/admin/index.php" class="nav-link text-muted">Dashboard</a>
                </li>
                <li class="nav-item">
                  <a href="<?= APP_URL ?>/admin/orders/index.php" class="nav-link text-muted">Orders</a>
                </li>
                <li class="nav-item">
                  <a href="<?= APP_URL ?>/admin/products/index.php" class="nav-link text-muted">Products</a>
                </li>
                <li class="nav-item">
                  <a href="<?= APP_URL ?>/admin/categories/index.php" class="nav-link text-muted">Categories</a>
                </li>
                <li class="nav-item">
                  <a href="<?= APP_URL ?>/admin/reports.php" class="nav-link text-muted">Reports</a>
                </li>
                <li class="nav-item">
                  <a href="<?= APP_URL ?>" class="nav-link pe-0 text-muted" target="_blank" rel="noopener">Storefront</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </footer>
    </div><!-- /.container-fluid (opened in admin-header.php) -->
  </main><!-- /.main-content (opened in admin-header.php) -->
  <!--   Core JS Files   -->
  <script src="<?= ADMIN_ASSETS ?>/js/core/popper.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/core/bootstrap.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="<?= ADMIN_ASSETS ?>/js/material-dashboard.min.js?v=3.2.0"></script>
</body>

</html>
