-- ============================================================================
-- integrity-migration.sql
-- ----------------------------------------------------------------------------
-- 1) FK safety: block category deletes that still have products, and block
--    user deletes that still have orders (was ON DELETE CASCADE = silent wipe).
-- 2) Stock triggers: inventory stays correct no matter WHERE the change comes
--    from (PHP checkout, admin panel, phpMyAdmin, seed scripts).
--
-- Run once against ecommerce_db (phpMyAdmin > SQL tab, or mysql CLI).
-- Re-runnable: statements are idempotent (DROP IF EXISTS first).
--
-- SEEDING BYPASS: to insert orders WITHOUT touching stock (history fill):
--     SET @skip_stock_adjustments = 1;
--     ... your INSERTs ...
--     SET @skip_stock_adjustments = NULL;
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1A) products.category_id : CASCADE -> RESTRICT
-- ----------------------------------------------------------------------------
ALTER TABLE products DROP FOREIGN KEY fk_products_category;
ALTER TABLE products
    ADD CONSTRAINT fk_products_category
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT;

-- ----------------------------------------------------------------------------
-- 1B) orders.user_id : CASCADE -> RESTRICT
-- ----------------------------------------------------------------------------
ALTER TABLE orders DROP FOREIGN KEY fk_orders_user;
ALTER TABLE orders
    ADD CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT;

-- ----------------------------------------------------------------------------
-- 2) Stock triggers
-- ----------------------------------------------------------------------------
DELIMITER //

-- T1: deduct stock whenever an order item is inserted (checkout OR seed script)
DROP TRIGGER IF EXISTS trg_deduct_stock_on_item //
CREATE TRIGGER trg_deduct_stock_on_item
AFTER INSERT ON order_items
FOR EACH ROW
BEGIN
    IF @skip_stock_adjustments IS NULL THEN
        UPDATE products
        SET stock = stock - NEW.quantity
        WHERE id = NEW.product_id AND status = 1 AND stock >= NEW.quantity;

        IF ROW_COUNT() = 0 THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock for product.';
        END IF;
    END IF;
END //

-- T2: restock when an order is cancelled, re-deduct if it is reactivated
DROP TRIGGER IF EXISTS trg_restock_on_cancel //
CREATE TRIGGER trg_restock_on_cancel
AFTER UPDATE ON orders
FOR EACH ROW
BEGIN
    IF @skip_stock_adjustments IS NULL THEN
        IF NEW.order_status = 'cancelled' AND OLD.order_status != 'cancelled' THEN
            UPDATE products p
            JOIN order_items oi ON oi.product_id = p.id
            SET p.stock = p.stock + oi.quantity
            WHERE oi.order_id = NEW.id;
        ELSEIF OLD.order_status = 'cancelled' AND NEW.order_status != 'cancelled' THEN
            UPDATE products p
            JOIN order_items oi ON oi.product_id = p.id
            SET p.stock = GREATEST(p.stock - oi.quantity, 0)
            WHERE oi.order_id = NEW.id;
        END IF;
    END IF;
END //

-- T3: restore stock if a non-cancelled order row is ever deleted
DROP TRIGGER IF EXISTS trg_restock_on_order_delete //
CREATE TRIGGER trg_restock_on_order_delete
BEFORE DELETE ON orders
FOR EACH ROW
BEGIN
    IF @skip_stock_adjustments IS NULL AND OLD.order_status != 'cancelled' THEN
        UPDATE products p
        JOIN order_items oi ON oi.product_id = p.id
        SET p.stock = p.stock + oi.quantity
        WHERE oi.order_id = OLD.id;
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- ROLLBACK (only if you ever need to undo this migration):
--   DROP TRIGGER IF EXISTS trg_deduct_stock_on_item;
--   DROP TRIGGER IF EXISTS trg_restock_on_cancel;
--   DROP TRIGGER IF EXISTS trg_restock_on_order_delete;
--   ALTER TABLE products DROP FOREIGN KEY fk_products_category;
--   ALTER TABLE products ADD CONSTRAINT fk_products_category
--       FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE;
--   ALTER TABLE orders DROP FOREIGN KEY fk_orders_user;
--   ALTER TABLE orders ADD CONSTRAINT fk_orders_user
--       FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
-- ============================================================================
