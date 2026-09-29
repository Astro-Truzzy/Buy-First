<?php

declare(strict_types=1);

/**
 * Cart controller — the shopping bag page and its three POST actions.
 *
 * Every mutation follows POST -> Redirect -> GET: the browser lands on a
 * normal GET page afterwards, so refreshing never re-submits the form.
 */
class CartController
{
    /** GET /cart — the bag itself. */
    public static function index(): string
    {
        $items    = Cart::items();
        $subtotal = Cart::subtotal($items);
        $coupon   = Coupon::fromSession($subtotal, Auth::id());
        $discount = $coupon ? Coupon::discountAmount($coupon, $subtotal) : 0.0;

        $member   = !empty(Auth::user()['is_member']);
        $delivery = ($subtotal >= Order::FREE_STANDARD_FROM || $member || $items === [])
            ? 0.0
            : Order::STANDARD_FEE;

        if ($coupon && $coupon['type'] === 'free_shipping') {
            $delivery = 0.0;
        }

        return render('cart', [
            'items'    => $items,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'delivery' => $delivery,
            'total'    => max(0, $subtotal - $discount) + $delivery,
            'freeFrom' => Order::FREE_STANDARD_FROM,
            'coupon'   => $coupon,
        ], ['title' => 'Your Bag']);
    }

    /** POST /cart/coupon — same rules as checkout, stays on the bag. */
    public static function coupon(): never
    {
        [$type, $message] = Coupon::handlePost(Cart::subtotal(Cart::items()), Auth::id());
        flash_set($type, $message);
        redirect('/cart');
    }

    /**
     * POST /cart/add — from the PDP size picker. Non-AJAX (no-JS, or a
     * failed add) lands on the bag or bounces back to the product; the AJAX
     * path (desktop drawer) gets the same JSON `finish()` gives update/remove.
     */
    public static function add(): never
    {
        $variantId = (int) ($_POST['variant_id'] ?? 0);
        // The slug is only used to send the shopper back where they came
        // from on failure, so sanitize it before it goes anywhere near a header.
        $slug = preg_replace('/[^a-z0-9\-]/', '', $_POST['slug'] ?? '');

        $result = $variantId > 0 ? Cart::add($variantId) : 'unavailable';

        if ($result === 'unavailable') {
            if (is_ajax()) {
                json_response(['ok' => false, 'message' => 'Sorry, that size is no longer available.'], 422);
            }
            flash_set('error', 'Sorry, that size is no longer available.');
            redirect($slug !== '' ? '/product/' . $slug : '/cart');
        }

        $message = $result === 'capped' ? 'Added — quantity limited by available stock.' : 'Added to your bag.';

        if (is_ajax()) {
            // No page load to render the server-side toast on — the drawer
            // flow builds an identical one client-side from this field.
            self::finish(['toast' => $message]);
        }

        flash_set('cart', $message);
        self::finish();
    }

    /** POST /cart/clear — empties the bag, from the drawer or the bag page. */
    public static function clear(): never
    {
        Cart::clear();
        if (!is_ajax()) {
            flash_set('info', 'Your bag has been cleared.');
        }

        self::finish();
    }

    /** POST /cart/update — quantity stepper on the bag, at checkout, and in the cart drawer. */
    public static function update(): never
    {
        $qty = (int) ($_POST['quantity'] ?? 1);
        $id  = (int) ($_POST['item_id'] ?? 0);

        if ($qty <= 0) {
            Cart::remove($id);
            if (!is_ajax()) {
                flash_set('info', 'Item removed from your bag.');
            }
        } else {
            Cart::updateQty($id, $qty);
        }

        self::finish();
    }

    /** POST /cart/remove — the bag line, including from checkout and the cart drawer. */
    public static function remove(): never
    {
        Cart::remove((int) ($_POST['item_id'] ?? 0));
        if (!is_ajax()) {
            flash_set('info', 'Item removed from your bag.');
        }

        self::finish();
    }

    /**
     * The cart drawer's forms submit via fetch(): reply with the refreshed
     * drawer contents instead of redirecting. Everything else (the bag page,
     * checkout) still gets the usual POST -> Redirect -> GET. $extra merges
     * into the JSON payload — e.g. add() uses it to carry the toast message.
     */
    private static function finish(array $extra = []): never
    {
        if (is_ajax()) {
            $items = Cart::items();
            $count = 0;
            foreach ($items as $item) {
                $count += (int) $item['quantity'];
            }

            json_response($extra + [
                'ok'       => true,
                'count'    => $count,
                'subtotal' => money(Cart::subtotal($items)),
                'html'     => view('partials/cart-drawer-items', ['items' => $items]),
            ]);
        }

        self::bounce();
    }

    /**
     * Stay on checkout when the shopper edits the bag there.
     * Only /cart and /checkout are allowed — anything else is ignored.
     */
    private static function bounce(): never
    {
        $return = (string) ($_POST['return'] ?? '/cart');
        if (!in_array($return, ['/cart', '/checkout'], true)) {
            $return = '/cart';
        }
        if ($return === '/checkout' && Cart::items() === []) {
            flash_set('info', 'Your bag is empty.');
            $return = '/cart';
        }
        redirect($return);
    }
}
