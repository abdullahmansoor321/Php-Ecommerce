<!DOCTYPE html>
<html lang="en">


<!-- molla/index-4.html  22 Nov 2019 09:53:08 GMT -->
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Molla eCommerce' ?></title>
    <meta name="keywords" content="HTML5 Template">
    <meta name="description" content="Molla - Bootstrap eCommerce Template">
    <meta name="author" content="p-themes">
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= FRONT_ASSETS ?>/images/icons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= FRONT_ASSETS ?>/images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= FRONT_ASSETS ?>/images/icons/favicon-16x16.png">
    <link rel="manifest" href="<?= FRONT_ASSETS ?>/images/icons/site.html">
    <link rel="mask-icon" href="<?= FRONT_ASSETS ?>/images/icons/safari-pinned-tab.svg" color="#666666">
    <link rel="shortcut icon" href="<?= FRONT_ASSETS ?>/images/icons/favicon.ico">
    <meta name="apple-mobile-web-app-title" content="Molla">
    <meta name="application-name" content="Molla">
    <meta name="msapplication-TileColor" content="#cc9966">
    <meta name="msapplication-config" content="<?= FRONT_ASSETS ?>/images/icons/browserconfig.xml">
    <meta name="theme-color" content="#ffffff">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/vendor/line-awesome/line-awesome/line-awesome/css/line-awesome.min.css">
    <!-- Plugins CSS File -->
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/plugins/owl-carousel/owl.carousel.css">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/plugins/magnific-popup/magnific-popup.css">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/plugins/jquery.countdown.css">
    <!-- Main CSS File -->
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/style.css">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/skins/skin-demo-4.css">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/demos/demo-4.css">
    <link rel="stylesheet" href="<?= FRONT_ASSETS ?>/css/storefront-layout.css">
    <?php foreach (($page_stylesheets ?? []) as $pageStylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($pageStylesheet, ENT_QUOTES) ?>">
    <?php endforeach; ?>
</head>

<body>
    <div class="page-wrapper">
