<?php
// Base domain URL
define('APP_URL', 'http://ecommerce.local');

// Filesystem root path
define('BASE_PATH', dirname(__DIR__));

// Storefront (user front) base URL - public/ is the web root for customer pages
define('FRONT_URL', APP_URL . '/public');

// Front assets URL
define('FRONT_ASSETS', FRONT_URL . '/assets/front');

// Admin assets URL
define('ADMIN_ASSETS', APP_URL . '/admin/assets');

// Upload storage (matches the folder structure in the project brief)
define('UPLOADS_PATH', BASE_PATH . '/public/uploads');
define('UPLOADS_URL', FRONT_URL . '/uploads');

/*
|--------------------------------------------------------------------------
| DEMO_MODE
|--------------------------------------------------------------------------
| true  -> storefront reads rows from config/seed-data.php (no database needed)
| false -> the same repository methods run the prepared MySQL statements
|          documented in core/Catalog.php / core/Cart.php / core/CustomerAuth.php
|
| Nothing else in the storefront has to change when you switch this to false.
*/
define('DEMO_MODE', true);

