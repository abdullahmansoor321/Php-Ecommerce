<?php
require_once __DIR__ . '/Database.php';

class Cart
{
    /**
     * Get or generate a secure 30-day persistent guest cart token cookie.
     */
    public static function getCartToken(): string
    {
        if (!isset($_COOKIE['cart_token'])) {
            $token = bin2hex(random_bytes(32));
            setcookie('cart_token', $token, time() + (86400 * 30), '/', '', false, true);
            $_COOKIE['cart_token'] = $token;
        }
        return $_COOKIE['cart_token'];
    }

    /**
     * Fetch all cart items for the current session/user along with product details.
     */
    public static function getItems(): array
    {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        $cartToken = self::getCartToken();

        if ($userId) {
            $sql = "SELECT ci.id as cart_item_id, ci.quantity, p.id as product_id, p.name, p.price, p.image, p.stock, p.slug 
                    FROM cart_items ci 
                    JOIN products p ON ci.product_id = p.id 
                    WHERE ci.user_id = ?";
            return $db->fetchAll($sql, [$userId]);
        } else {
            $sql = "SELECT ci.id as cart_item_id, ci.quantity, p.id as product_id, p.name, p.price, p.image, p.stock, p.slug 
                    FROM cart_items ci 
                    JOIN products p ON ci.product_id = p.id 
                    WHERE ci.cart_token = ?";
            return $db->fetchAll($sql, [$cartToken]);
        }
    }

    /**
     * Add or increment a product in the cart.
     */
    public static function add(int $productId, int $quantity = 1): bool
    {
        $db = Database::getInstance();
        $product = $db->fetchOne("SELECT stock, status FROM products WHERE id = ?", [$productId]);
        if (!$product || (int)$product['status'] !== 1 || (int)$product['stock'] < 1) {
            return false;
        }

        $userId = $_SESSION['user_id'] ?? null;
        $cartToken = self::getCartToken();
        $quantity = min(max(1, $quantity), (int)$product['stock']);

        // Check if item already exists in cart
        if ($userId) {
            $existing = $db->fetchOne("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
        } else {
            $existing = $db->fetchOne("SELECT id, quantity FROM cart_items WHERE cart_token = ? AND product_id = ?", [$cartToken, $productId]);
        }

        if ($existing) {
            $newQty = min((int)$existing['quantity'] + $quantity, (int)$product['stock']);
            $db->query("UPDATE cart_items SET quantity = ? WHERE id = ?", [$newQty, $existing['id']]);
        } else {
            if ($userId) {
                $db->query("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)", [$userId, $productId, $quantity]);
            } else {
                $db->query("INSERT INTO cart_items (cart_token, product_id, quantity) VALUES (?, ?, ?)", [$cartToken, $productId, $quantity]);
            }
        }

        return true;
    }

    /**
     * Update quantity of a cart item.
     */
    public static function update(int $productId, int $quantity): void
    {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        $cartToken = self::getCartToken();

        if ($quantity <= 0) {
            self::remove($productId);
            return;
        }

        if ($userId) {
            $db->query("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?", [$quantity, $userId, $productId]);
        } else {
            $db->query("UPDATE cart_items SET quantity = ? WHERE cart_token = ? AND product_id = ?", [$quantity, $cartToken, $productId]);
        }
    }

    /**
     * Remove an item from the cart.
     */
    public static function remove(int $productId): void
    {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        $cartToken = self::getCartToken();

        if ($userId) {
            $db->query("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?", [$userId, $productId]);
        } else {
            $db->query("DELETE FROM cart_items WHERE cart_token = ? AND product_id = ?", [$cartToken, $productId]);
        }
    }

    /**
     * Calculate total cart amount.
     */
    public static function getTotal(): float
    {
        $items = self::getItems();
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float)$item['price'] * (int)$item['quantity'];
        }
        return $total;
    }

    /**
     * Merge guest cart into user cart upon login.
     */
    public static function mergeGuestCart(int $userId): void
    {
        $db = Database::getInstance();
        if (!isset($_COOKIE['cart_token'])) {
            return;
        }
        $cartToken = $_COOKIE['cart_token'];

        $guestItems = $db->fetchAll("SELECT product_id, quantity FROM cart_items WHERE cart_token = ?", [$cartToken]);

        foreach ($guestItems as $item) {
            $existing = $db->fetchOne("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?", [$userId, $item['product_id']]);
            if ($existing) {
                $newQty = $existing['quantity'] + $item['quantity'];
                $db->query("UPDATE cart_items SET quantity = ? WHERE id = ?", [$newQty, $existing['id']]);
            } else {
                $db->query("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)", [$userId, $item['product_id'], $item['quantity']]);
            }
        }

        // Clean up guest cart token rows
        $db->query("DELETE FROM cart_items WHERE cart_token = ?", [$cartToken]);
    }
}
