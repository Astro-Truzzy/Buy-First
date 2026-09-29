<?php

declare(strict_types=1);

/**
 * Cart model — the shopping bag.
 *
 * A cart is a DB row (carts) with line rows (cart_items). Identity:
 *   - logged-in shopper  -> carts.user_id
 *   - guest shopper      -> carts.session_token, a random 64-char string
 *                           that we keep in the PHP session
 *
 * We deliberately store ONLY variant_id + quantity. Prices are looked up
 * fresh every time the bag is displayed (and again at checkout), so a sale
 * starting or ending is always reflected — and nobody can tamper with a
 * price that was "remembered" in their cart.
 */
class Cart
{
    /** A sane per-line maximum so nobody "buys" 9999 of anything. */
    private const MAX_PER_LINE = 10;

    // ------------------------------------------------------------------
    // Finding / creating the shopper's cart
    // ------------------------------------------------------------------

    /**
     * The current shopper's cart row, or null if they don't have one yet.
     * Pass $create = true to make one on the spot (used when adding).
     */
    public static function current(bool $create = false): ?array
    {
        $pdo = Database::pdo();

        if (Auth::check()) {
            $stmt = $pdo->prepare('SELECT * FROM carts WHERE user_id = ?');
            $stmt->execute([Auth::id()]);
            $cart = $stmt->fetch();

            if ($cart === false && $create) {
                $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)')
                    ->execute([Auth::id()]);
                return self::current();
            }

            return $cart ?: null;
        }

        // Guest: the token in the session is the "key" to their cart row.
        $token = $_SESSION['cart_token'] ?? null;

        if ($token !== null) {
            $stmt = $pdo->prepare('SELECT * FROM carts WHERE session_token = ?');
            $stmt->execute([$token]);
            $cart = $stmt->fetch();
            if ($cart !== false) {
                return $cart;
            }
        }

        if (!$create) {
            return null;
        }

        // First item for this guest: mint a token and a cart together.
        $token = bin2hex(random_bytes(32)); // 32 bytes -> 64 hex chars
        $_SESSION['cart_token'] = $token;
        $pdo->prepare('INSERT INTO carts (session_token) VALUES (?)')->execute([$token]);

        return self::current();
    }

    // ------------------------------------------------------------------
    // Mutations: add / change quantity / remove
    // ------------------------------------------------------------------

    /**
     * Add a variant to the bag. Returns a short status string the
     * controller turns into a flash message:
     *   'added' | 'capped' (hit stock or per-line limit) | 'unavailable'
     */
    public static function add(int $variantId, int $qty = 1): string
    {
        $pdo = Database::pdo();

        // Look the variant up ourselves — never trust the form to tell us
        // what is in stock.
        $stmt = $pdo->prepare('SELECT id, stock FROM product_variants WHERE id = ?');
        $stmt->execute([$variantId]);
        $variant = $stmt->fetch();

        if ($variant === false || (int) $variant['stock'] === 0) {
            return 'unavailable';
        }

        $cart = self::current(create: true);

        // Existing quantity for this exact size/colour, if any.
        $stmt = $pdo->prepare(
            'SELECT quantity FROM cart_items WHERE cart_id = ? AND variant_id = ?'
        );
        $stmt->execute([$cart['id'], $variantId]);
        $existing = (int) ($stmt->fetchColumn() ?: 0);

        // Clamp: never more than stock, never more than the per-line cap.
        $ceiling = min((int) $variant['stock'], self::MAX_PER_LINE);
        $newQty  = min($existing + max(1, $qty), $ceiling);

        // UNIQUE(cart_id, variant_id) turns a duplicate INSERT into an
        // UPDATE — "upsert". One statement handles both first-add and re-add.
        $pdo->prepare(
            'INSERT INTO cart_items (cart_id, variant_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        )->execute([$cart['id'], $variantId, $newQty]);

        return $newQty < $existing + max(1, $qty) ? 'capped' : 'added';
    }

    /**
     * Set a line's quantity (0 removes it). The WHERE clause pins the item
     * to the CURRENT shopper's cart, so nobody can edit someone else's bag
     * by guessing item IDs.
     */
    public static function updateQty(int $itemId, int $qty): void
    {
        $cart = self::current();
        if ($cart === null) {
            return;
        }

        $pdo = Database::pdo();

        if ($qty <= 0) {
            $pdo->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')
                ->execute([$itemId, $cart['id']]);
            return;
        }

        // Clamp to available stock via a subquery, and to the per-line cap.
        $pdo->prepare(
            'UPDATE cart_items ci
             JOIN product_variants v ON v.id = ci.variant_id
             SET ci.quantity = LEAST(?, v.stock, ?)
             WHERE ci.id = ? AND ci.cart_id = ?'
        )->execute([$qty, self::MAX_PER_LINE, $itemId, $cart['id']]);
    }

    /** Remove one line from the bag. Same ownership check as updateQty. */
    public static function remove(int $itemId): void
    {
        $cart = self::current();
        if ($cart === null) {
            return;
        }

        Database::pdo()
            ->prepare('DELETE FROM cart_items WHERE id = ? AND cart_id = ?')
            ->execute([$itemId, $cart['id']]);
    }

    // ------------------------------------------------------------------
    // Reads: the hydrated bag, the badge count, the totals
    // ------------------------------------------------------------------

    /**
     * The bag's lines, "hydrated": each cart_items row joined to its
     * variant, product and primary image, with the effective unit price
     * computed in SQL:
     *
     *   COALESCE(price_override, sale_price, price)
     *   = first non-NULL of: variant special price, product sale price,
     *     product full price.
     */
    public static function items(): array
    {
        $cart = self::current();
        if ($cart === null) {
            return [];
        }

        $stmt = Database::pdo()->prepare(
            "SELECT
                ci.id, ci.quantity,
                v.id AS variant_id, v.sku, v.size, v.color, v.stock,
                p.name, p.slug, p.price AS full_price,
                COALESCE(v.price_override, p.sale_price, p.price) AS unit_price,
                (SELECT url FROM product_images pi
                 WHERE pi.product_id = p.id
                 ORDER BY pi.sort_order ASC LIMIT 1) AS image
             FROM cart_items ci
             JOIN product_variants v ON v.id = ci.variant_id
             JOIN products p         ON p.id = v.product_id
             WHERE ci.cart_id = ?
             ORDER BY ci.id DESC"
        );
        $stmt->execute([$cart['id']]);

        return $stmt->fetchAll();
    }

    /** Total number of items — the little number on the bag icon. */
    public static function count(): int
    {
        $cart = self::current();
        if ($cart === null) {
            return 0;
        }

        $stmt = Database::pdo()->prepare(
            'SELECT COALESCE(SUM(quantity), 0) FROM cart_items WHERE cart_id = ?'
        );
        $stmt->execute([$cart['id']]);

        return (int) $stmt->fetchColumn();
    }

    /** Sum of line totals. Takes items() output so we only query once. */
    public static function subtotal(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += (float) $item['unit_price'] * (int) $item['quantity'];
        }

        return $sum;
    }

    // ------------------------------------------------------------------
    // Login-time merge
    // ------------------------------------------------------------------

    /**
     * Called right after login/registration. If the shopper filled a bag
     * as a guest, fold it into their account cart so nothing is lost.
     */
    public static function mergeGuestIntoUser(int $userId): void
    {
        $token = $_SESSION['cart_token'] ?? null;
        if ($token === null) {
            return;
        }
        unset($_SESSION['cart_token']); // the token has done its job

        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT id FROM carts WHERE session_token = ?');
        $stmt->execute([$token]);
        $guestCartId = $stmt->fetchColumn();
        if ($guestCartId === false) {
            return; // token existed but the cart is gone — nothing to merge
        }

        $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ?');
        $stmt->execute([$userId]);
        $userCartId = $stmt->fetchColumn();

        if ($userCartId === false) {
            // Easiest merge in the world: the guest cart BECOMES the user's
            // cart — just swap the identifier on the row.
            $pdo->prepare('UPDATE carts SET user_id = ?, session_token = NULL WHERE id = ?')
                ->execute([$userId, $guestCartId]);
            return;
        }

        // Both carts exist: move the guest lines over one by one. If the
        // same variant is in both bags the quantities are added together,
        // still clamped to stock and the per-line cap.
        $stmt = $pdo->prepare(
            'SELECT ci.variant_id, ci.quantity, v.stock
             FROM cart_items ci
             JOIN product_variants v ON v.id = ci.variant_id
             WHERE ci.cart_id = ?'
        );
        $stmt->execute([$guestCartId]);

        $existingStmt = $pdo->prepare(
            'SELECT quantity FROM cart_items WHERE cart_id = ? AND variant_id = ?'
        );
        $upsert = $pdo->prepare(
            'INSERT INTO cart_items (cart_id, variant_id, quantity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)'
        );

        foreach ($stmt->fetchAll() as $line) {
            $existingStmt->execute([$userCartId, $line['variant_id']]);
            $existing = (int) ($existingStmt->fetchColumn() ?: 0);

            $ceiling = min((int) $line['stock'], self::MAX_PER_LINE);
            $newQty  = min($existing + (int) $line['quantity'], $ceiling);

            $upsert->execute([$userCartId, $line['variant_id'], $newQty]);
        }

        $pdo->prepare('DELETE FROM carts WHERE id = ?')->execute([$guestCartId]);
    }

    /** Empty the bag after a successful checkout. The cart row stays. */
    public static function clear(): void
    {
        $cart = self::current();
        if ($cart === null) {
            return;
        }

        Database::pdo()
            ->prepare('DELETE FROM cart_items WHERE cart_id = ?')
            ->execute([$cart['id']]);
    }
}
