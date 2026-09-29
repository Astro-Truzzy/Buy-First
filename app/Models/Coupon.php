<?php

declare(strict_types=1);

/**
 * Coupon model — promo codes.
 *
 * Codes are always looked up by the server. The browser may submit
 * "WELCOME10", but it must never submit "give me 90% off" — we re-read
 * type, value, dates and min_spend from this table every time.
 */
class Coupon
{
    public static function findByCode(string $code): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM coupons WHERE code = ? AND is_active = 1'
        );
        $stmt->execute([strtoupper(trim($code))]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Returns an error string if the code cannot be used right now,
     * or null if it is valid for this shopper and this subtotal.
     */
    public static function rejection(?array $coupon, float $subtotal, ?int $userId): ?string
    {
        if ($coupon === null) {
            return 'That code is not recognised.';
        }

        $now = time();
        if ($coupon['starts_at'] && strtotime($coupon['starts_at']) > $now) {
            return 'That code is not active yet.';
        }
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < $now) {
            return 'That code has expired.';
        }
        if ($coupon['max_uses'] !== null && (int) $coupon['uses'] >= (int) $coupon['max_uses']) {
            return 'That code has been fully redeemed.';
        }
        if ($subtotal < (float) $coupon['min_spend']) {
            return 'Spend ' . money($coupon['min_spend']) . ' to use this code.';
        }

        // One redemption per logged-in customer. Guests skip this check
        // (we have no stable identity to pin a redemption to).
        if ($userId !== null) {
            $stmt = Database::pdo()->prepare(
                'SELECT 1 FROM coupon_redemptions WHERE coupon_id = ? AND user_id = ?'
            );
            $stmt->execute([$coupon['id'], $userId]);
            if ($stmt->fetchColumn()) {
                return 'You have already used this code.';
            }
        }

        return null;
    }

    public static function discountAmount(array $coupon, float $subtotal): float
    {
        return match ($coupon['type']) {
            'percent' => round($subtotal * ((float) $coupon['value'] / 100), 2),
            'fixed'   => min((float) $coupon['value'], $subtotal),
            default   => 0.0,
        };
    }

    /** Re-read the session code from MySQL. Drop it if it is no longer valid. */
    public static function fromSession(float $subtotal, ?int $userId): ?array
    {
        $code = $_SESSION['coupon_code'] ?? null;
        if ($code === null) {
            return null;
        }

        $coupon = self::findByCode($code);
        if (self::rejection($coupon, $subtotal, $userId)) {
            unset($_SESSION['coupon_code']);
            return null;
        }

        return $coupon;
    }

    /**
     * Apply or remove a code from a POST. Returns [flash type, message].
     * Both the bag and checkout forms call this so the rules stay in one place.
     */
    public static function handlePost(float $subtotal, ?int $userId): array
    {
        if (!empty($_POST['remove'])) {
            unset($_SESSION['coupon_code']);
            return ['info', 'Code removed.'];
        }

        $code   = strtoupper(trim($_POST['code'] ?? ''));
        $coupon = $code !== '' ? self::findByCode($code) : null;
        $error  = self::rejection($coupon, $subtotal, $userId);

        if ($error) {
            return ['error', $error];
        }

        $_SESSION['coupon_code'] = $coupon['code'];

        return ['success', 'Code ' . $coupon['code'] . ' applied.'];
    }
}
