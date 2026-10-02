<?php
/**
 * Admin logout endpoint.
 * Destroys only the ADMINSESSID session, leaving any storefront
 * customer session signed in.
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../core/AdminAuth.php';

Session::startAdmin();
AdminAuth::logout();

header('Location: ' . APP_URL . '/admin/login.php');
exit;
