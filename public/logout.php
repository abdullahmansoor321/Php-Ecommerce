<?php
/**
 * Storefront logout endpoint.
 * Destroys the customer session and returns to the login page.
 */
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../core/Session.php';

Session::start();
Session::destroy();

header('Location: ' . FRONT_URL . '/login.php');
exit;
