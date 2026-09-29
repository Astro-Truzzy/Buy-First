<?php

declare(strict_types=1);

/**
 * Order model — turning a cart into a paid order.
 *
 * Two ideas that keep money honest:
 *
 * 1. SNAPSHOTTING. order_items copies name, SKU, size, colour and price at
 *    the moment of purchase. If we later rename a product or change its
 *    price, old receipts stay exactly as the customer saw them.
 *
 * 2. TRANSACTIONS. Placing an order touches several tables (orders,
 *    order_items, payments, stock, cart, coupons). A transaction means
 *    they all succeed together or none of them do — we never want an
 *    order with no stock deducted, or stock deducted with no order.
 */
class Order
{
    public const VAT_RATE           = 0.075; // Nigeria VAT
    public const STANDARD_FEE       = 4990.00;
    public const EXPRESS_FEE        = 9990.00;
    public const FREE_STANDARD_FROM = 75000.00;

    /** Methods the form is allowed to send. Anything else is ignored. */
    public const METHODS = ['standard', 'express'];

    /**
     * Recalculate every figure from trusted data. The browser's hidden
     * "total" field, if it even exists, is never read.
     */
    public static function quote(
        array $items,
        string $method,
        ?array $coupon,
        ?array $user
    ): array {
        $subtotal = Cart::subtotal($items);

        $member   = !empty($user['is_member']);
        $shipping = $method === 'express'
            ? self::EXPRESS_FEE
            : (($subtotal >= self::FREE_STANDARD_FROM || $member) ? 0.0 : self::STANDARD_FEE);

        $discount = 0.0;
        if ($coupon !== null) {
            $discount = match ($coupon['type']) {
                'percent' => round($subtotal * ((float) $coupon['value'] / 100), 2),
                'fixed'   => min((float) $coupon['value'], $subtotal),
                default   => 0.0,
            };
            if ($coupon['type'] === 'free_shipping') {
                $shipping = 0.0;
            }
        }

        $taxable = $subtotal - $discount + $shipping;
        $tax     = round($taxable * self::VAT_RATE, 2);
        $total   = round($taxable + $tax, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping' => $shipping,
            'tax'      => $tax,
            'total'    => $total,
        ];
    }

    /**
     * Place the order. Returns the new order row, or a previous one if the
     * same idempotency key is submitted twice (double-click on Place Order).
     *
     * @throws RuntimeException when stock is gone or the coupon just died
     */
    public static function place(array $payload): array
    {
        $pdo = Database::pdo();

        // Idempotency: if this exact checkout form was already processed,
        // return the first order instead of inserting a second one.
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE idempotency_key = ?');
        $stmt->execute([$payload['idempotency_key']]);
        $existing = $stmt->fetch();
        if ($existing) {
            return $existing;
        }

        $pdo->beginTransaction();

        try {
            // Lock each variant row so two checkouts cannot both take the
            // last item. FOR UPDATE holds the row until we COMMIT or ROLLBACK.
            $lock = $pdo->prepare(
                'SELECT v.id, v.sku, v.size, v.color, v.stock,
                        p.name,
                        COALESCE(v.price_override, p.sale_price, p.price) AS unit_price
                 FROM product_variants v
                 JOIN products p ON p.id = v.product_id
                 WHERE v.id = ?
                 FOR UPDATE'
            );
            $decrement = $pdo->prepare(
                'UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?'
            );

            $lines = [];
            foreach ($payload['items'] as $item) {
                $lock->execute([$item['variant_id']]);
                $variant = $lock->fetch();
                $qty     = (int) $item['quantity'];

                if ($variant === false || (int) $variant['stock'] < $qty) {
                    throw new RuntimeException(
                        ($variant['name'] ?? 'An item') . ' in ' . ($item['size'] ?? 'your size')
                        . ' is no longer available in that quantity.'
                    );
                }

                $unit = (float) $variant['unit_price'];
                $lines[] = [
                    'variant_id'   => (int) $variant['id'],
                    'product_name' => $variant['name'],
                    'sku'          => $variant['sku'],
                    'color'        => $variant['color'],
                    'size'         => $variant['size'],
                    'unit_price'   => $unit,
                    'quantity'     => $qty,
                    'line_total'   => round($unit * $qty, 2),
                ];
            }

            $quote = self::quote($lines, $payload['method'], $payload['coupon'], $payload['user']);

            $number = self::newNumber();

            $pdo->prepare(
                'INSERT INTO orders (
                    order_number, user_id, email, status,
                    subtotal, discount, shipping_cost, tax, total,
                    coupon_id, delivery_method, payment_method,
                    ship_first_name, ship_last_name, ship_line1, ship_line2,
                    ship_city, ship_postcode, ship_country, ship_phone,
                    idempotency_key
                 ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?
                 )'
            )->execute([
                $number,
                $payload['user']['id'] ?? null,
                $payload['email'],
                $payload['paid'] ? 'paid' : 'pending',
                $quote['subtotal'],
                $quote['discount'],
                $quote['shipping'],
                $quote['tax'],
                $quote['total'],
                $payload['coupon']['id'] ?? null,
                $payload['method'],
                $payload['payment_method'],
                $payload['ship']['first_name'],
                $payload['ship']['last_name'],
                $payload['ship']['line1'],
                $payload['ship']['line2'] !== '' ? $payload['ship']['line2'] : null,
                $payload['ship']['city'],
                $payload['ship']['postcode'],
                $payload['ship']['country'],
                $payload['ship']['phone'] !== '' ? $payload['ship']['phone'] : null,
                $payload['idempotency_key'],
            ]);

            $orderId = (int) $pdo->lastInsertId();

            $insertItem = $pdo->prepare(
                'INSERT INTO order_items
                    (order_id, variant_id, product_name, sku, color, size, unit_price, quantity, line_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($lines as $line) {
                $insertItem->execute([
                    $orderId,
                    $line['variant_id'],
                    $line['product_name'],
                    $line['sku'],
                    $line['color'],
                    $line['size'],
                    $line['unit_price'],
                    $line['quantity'],
                    $line['line_total'],
                ]);

                $decrement->execute([$line['quantity'], $line['variant_id'], $line['quantity']]);
                if ($decrement->rowCount() !== 1) {
                    throw new RuntimeException('Stock changed while you were checking out. Please try again.');
                }
            }

            $pdo->prepare(
                'INSERT INTO payments (order_id, provider, amount, currency, status, reference)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                $orderId,
                $payload['payment_method'],
                $quote['total'],
                'NGN',
                $payload['paid'] ? 'succeeded' : 'pending',
                $payload['payment_ref'],
            ]);

            if ($payload['coupon'] !== null) {
                $cap = $pdo->prepare(
                    'UPDATE coupons SET uses = uses + 1
                     WHERE id = ? AND (max_uses IS NULL OR uses < max_uses)'
                );
                $cap->execute([$payload['coupon']['id']]);
                if ($cap->rowCount() !== 1) {
                    throw new RuntimeException('That code has just been fully redeemed.');
                }

                $pdo->prepare(
                    'INSERT INTO coupon_redemptions (coupon_id, order_id, user_id) VALUES (?, ?, ?)'
                )->execute([
                    $payload['coupon']['id'],
                    $orderId,
                    $payload['user']['id'] ?? null,
                ]);
            }

            Cart::clear();

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return self::findByNumber($number);
    }

    public const STATUSES = ['pending', 'paid', 'packed', 'shipped', 'delivered', 'cancelled', 'refunded'];

    /** Fulfilment steps the admin can move between — not cancel/refund. */
    public const FULFILMENT_STATUSES = ['pending', 'paid', 'packed', 'shipped', 'delivered'];

    /** Still needs a staff action — including marking a shipment delivered. */
    public const ATTENTION_STATUSES = ['pending', 'paid', 'packed', 'shipped'];

    public static function all(?string $status = null, int $limit = 100): array
    {
        $sql    = 'SELECT o.*,
                          (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS line_count
                   FROM orders o';
        $params = [];
        if ($status !== null && in_array($status, self::STATUSES, true)) {
            $sql     .= ' WHERE o.status = ?';
            $params[] = $status;
        }
        $sql .= ' ORDER BY o.placed_at DESC LIMIT ' . (int) $limit;

        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $lock = $pdo->prepare('SELECT status FROM orders WHERE id = ? FOR UPDATE');
            $lock->execute([$id]);
            $from = $lock->fetchColumn();
            if ($from === false || $from === $status) {
                $pdo->commit();
                return;
            }

            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $id]);

            $wasHalted = in_array($from, ['cancelled', 'refunded'], true);
            $willHalt  = in_array($status, ['cancelled', 'refunded'], true);

            if ($willHalt && !$wasHalted) {
                self::restoreStock($pdo, $id);
            }

            $paidStatuses = ['paid', 'packed', 'shipped', 'delivered'];
            if (
                ($status === 'refunded' || ($status === 'cancelled' && in_array($from, $paidStatuses, true)))
            ) {
                $pdo->prepare(
                    "UPDATE payments SET status = 'refunded' WHERE order_id = ? AND status = 'succeeded'"
                )->execute([$id]);
            }

            // Admin confirming a pending bank transfer: mark it received.
            if ($status === 'paid' && $from === 'pending') {
                $pdo->prepare(
                    "UPDATE payments SET status = 'succeeded' WHERE order_id = ? AND status = 'pending'"
                )->execute([$id]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Customer says they've sent a bank transfer. Only records the claim —
     * the order stays "pending" until a human checks the account and
     * presses "Mark as paid" (setStatus() above). Returns false if the
     * order isn't an unpaid bank transfer, or was already claimed.
     */
    public static function claimPayment(int $id): bool
    {
        $stmt = Database::pdo()->prepare(
            "UPDATE orders
             SET payment_claimed_at = NOW()
             WHERE id = ? AND payment_method = 'bank_transfer'
               AND status = 'pending' AND payment_claimed_at IS NULL"
        );
        $stmt->execute([$id]);

        return $stmt->rowCount() === 1;
    }

    /** Bank transfers a customer has claimed that nobody has verified yet. */
    public static function paymentClaimsCount(): int
    {
        return (int) Database::pdo()->query(
            "SELECT COUNT(*) FROM orders
             WHERE payment_method = 'bank_transfer' AND status = 'pending'
               AND payment_claimed_at IS NOT NULL"
        )->fetchColumn();
    }

    public static function canCancel(string $status): bool
    {
        return in_array($status, ['pending', 'paid', 'packed'], true);
    }

    public static function canRefund(string $status): bool
    {
        return in_array($status, ['paid', 'packed', 'shipped', 'delivered'], true);
    }

    /** Put deducted units back. Skips lines whose variant has been deleted. */
    private static function restoreStock(\PDO $pdo, int $orderId): void
    {
        $lines = $pdo->prepare(
            'SELECT variant_id, quantity FROM order_items WHERE order_id = ? AND variant_id IS NOT NULL'
        );
        $lines->execute([$orderId]);
        $bump = $pdo->prepare('UPDATE product_variants SET stock = stock + ? WHERE id = ?');
        foreach ($lines as $line) {
            $bump->execute([(int) $line['quantity'], (int) $line['variant_id']]);
        }
    }

    /** Counts + revenue grouped by status, for the dashboard. */
    public static function stats(): array
    {
        return Database::pdo()
            ->query('SELECT status, COUNT(*) AS n, COALESCE(SUM(total), 0) AS revenue FROM orders GROUP BY status')
            ->fetchAll();
    }

    /** How many orders still need a staff action (pay / pack / ship). */
    public static function attentionCount(): int
    {
        $placeholders = implode(',', array_fill(0, count(self::ATTENTION_STATUSES), '?'));
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM orders WHERE status IN ({$placeholders})");
        $stmt->execute(self::ATTENTION_STATUSES);

        return (int) $stmt->fetchColumn();
    }

    /** Per-status counts for the orders filter tabs. */
    public static function countsByStatus(): array
    {
        $out = array_fill_keys(self::STATUSES, 0);
        foreach (Database::pdo()->query('SELECT status, COUNT(*) AS n FROM orders GROUP BY status') as $row) {
            if (isset($out[$row['status']])) {
                $out[$row['status']] = (int) $row['n'];
            }
        }

        return $out;
    }

    public static function findByNumber(string $number): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM orders WHERE order_number = ?');
        $stmt->execute([$number]);

        return $stmt->fetch() ?: null;
    }

    /** The most recent payment attempt on this order, if any. */
    public static function paymentFor(int $orderId): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC LIMIT 1'
        );
        $stmt->execute([$orderId]);

        return $stmt->fetch() ?: null;
    }

    public static function itemsFor(int $orderId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT oi.*,
                    p.slug,
                    (SELECT url FROM product_images pi
                     WHERE pi.product_id = p.id
                     ORDER BY pi.sort_order ASC LIMIT 1) AS image
             FROM order_items oi
             LEFT JOIN product_variants v ON v.id = oi.variant_id
             LEFT JOIN products p ON p.id = v.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id'
        );
        $stmt->execute([$orderId]);

        return $stmt->fetchAll();
    }

    /** Every order this customer has placed, newest first. */
    public static function forUser(int $userId, int $limit = 50): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT o.*,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS line_count,
                    (SELECT pi.url
                     FROM order_items oi
                     JOIN product_variants v ON v.id = oi.variant_id
                     JOIN product_images pi ON pi.product_id = v.product_id
                     WHERE oi.order_id = o.id
                     ORDER BY oi.id ASC, pi.sort_order ASC
                     LIMIT 1) AS thumb
             FROM orders o
             WHERE o.user_id = ?
             ORDER BY o.placed_at DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    public static function countForUser(int $userId): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $stmt->execute([$userId]);

        return (int) $stmt->fetchColumn();
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'pending'   => 'Payment pending',
            'paid'      => 'Paid',
            'packed'    => 'Processing',
            'shipped'   => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded'  => 'Refunded',
            default     => ucfirst($status),
        };
    }

    /**
     * The natural next fulfilment step, or null if the order is finished
     * (delivered) or stopped (cancelled / refunded).
     *
     * @return ?array{status: string, label: string}
     */
    public static function nextAction(string $status): ?array
    {
        return match ($status) {
            'pending'   => ['status' => 'paid',      'label' => 'Mark as paid'],
            'paid'      => ['status' => 'packed',    'label' => 'Start processing'],
            'packed'    => ['status' => 'shipped',   'label' => 'Mark as shipped'],
            'shipped'   => ['status' => 'delivered', 'label' => 'Mark as delivered'],
            default     => null,
        };
    }

    /**
     * Can this visitor see this order? Logged-in owner, or the guest who
     * just placed it (order number stored in their session).
     */
    public static function visibleTo(array $order): bool
    {
        if (Auth::id() !== null && (int) $order['user_id'] === Auth::id()) {
            return true;
        }

        return ($_SESSION['last_order'] ?? '') === $order['order_number'];
    }

    /** Did this customer buy this product on a non-cancelled order? */
    public static function userBought(int $productId, int $userId): bool
    {
        $stmt = Database::pdo()->prepare(
            "SELECT 1
             FROM orders o
             JOIN order_items oi ON oi.order_id = o.id
             JOIN product_variants v ON v.id = oi.variant_id
             WHERE o.user_id = ? AND v.product_id = ?
               AND o.status NOT IN ('cancelled', 'refunded')
             LIMIT 1"
        );
        $stmt->execute([$userId, $productId]);

        return (bool) $stmt->fetchColumn();
    }

    /** BF-260813-A7K2 — date plus 4 random hex chars. */
    private static function newNumber(): string
    {
        return 'BF-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
    }
}
