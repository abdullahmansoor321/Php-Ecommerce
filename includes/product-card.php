<?php
/**
 * Reusable storefront product card (matches the home "New Arrivals" markup).
 *
 * Expects the caller to set, just before including this file:
 *   $cardProduct      array   product row: id, name, price, stock, image,
 *                             category_name (opt), category_slug (opt)
 *   $cardPlaceholder  string  fallback image URL when the product has none
 *   $cardBadge        string? optional corner label text (null = none)
 *   $cardBadgeClass   string? label modifier class (default 'label-top';
 *                             use 'label-sale' / 'label-new' for variants)
 *   $cardMetaText     string? small caption next to the stars
 *   $cardMetaWidth    int?    star fill percentage (default 80)
 */

$cardImages = ProductImage::available((int)$cardProduct['id'], $cardProduct['image'] ?? null);
$cardImage  = !empty($cardImages) ? ProductImage::url((int)$cardProduct['id'], $cardImages[0]) : $cardPlaceholder;

$cardBadge      = $cardBadge ?? null;
$cardBadgeClass = $cardBadgeClass ?? 'label-top';
$cardMetaText   = $cardMetaText ?? ('Available: ' . (int)$cardProduct['stock']);
$cardMetaWidth  = $cardMetaWidth ?? 80;

$cardUrl = FRONT_URL . '/product.php?id=' . (int)$cardProduct['id'];
?>
<div class="product product-2">
    <figure class="product-media">
        <?php if ($cardBadge !== null && $cardBadge !== ''): ?>
            <span class="product-label <?= htmlspecialchars($cardBadgeClass) ?>"><?= htmlspecialchars($cardBadge) ?></span>
        <?php endif; ?>
        <a href="<?= $cardUrl ?>"><img src="<?= htmlspecialchars($cardImage) ?>" alt="<?= htmlspecialchars($cardProduct['name']) ?>" class="product-image"></a>
        <div class="product-action-vertical"><a href="#" class="btn-product-icon btn-wishlist" title="Add to wishlist"></a></div>
        <div class="product-action">
            <a href="<?= FRONT_URL ?>/cart.php?action=add&amp;id=<?= (int)$cardProduct['id'] ?>" class="btn-product btn-cart" title="Add to cart"><span>add to cart</span></a>
            <a href="<?= $cardUrl ?>" class="btn-product" title="View product"><span>view product</span></a>
        </div>
    </figure>
    <div class="product-body">
        <div class="product-cat"><a href="<?= FRONT_URL ?>/shop.php?category=<?= htmlspecialchars($cardProduct['category_slug'] ?? '') ?>"><?= htmlspecialchars($cardProduct['category_name'] ?? '') ?></a></div>
        <h3 class="product-title"><a href="<?= $cardUrl ?>"><?= htmlspecialchars($cardProduct['name']) ?></a></h3>
        <div class="product-price">$<?= number_format((float)$cardProduct['price'], 2) ?></div>
        <div class="ratings-container"><div class="ratings"><div class="ratings-val" style="width: <?= (int)$cardMetaWidth ?>%;"></div></div><span class="ratings-text"><?= htmlspecialchars($cardMetaText) ?></span></div>
    </div>
</div>
