<?php

declare(strict_types=1);

/**
 * Wishlist page and the heart toggle.
 *
 * Toggle is POST (state change). Guests are sent to /login with intended
 * set to the page they were on — not to /wishlist/toggle, which is POST-only.
 */
class WishlistController
{
    public static function index(): string
    {
        if (!Auth::check()) {
            $_SESSION['intended'] = '/wishlist';
            flash_set('info', 'Sign in to see your saved items.');
            redirect('/login');
        }

        return render('wishlist', [
            'user'     => Auth::user(),
            'products' => Wishlist::products((int) Auth::id()),
        ], ['title' => 'Wishlist']);
    }

    public static function toggle(): never
    {
        $return = safe_internal_path((string) ($_POST['return'] ?? '/wishlist'), '/wishlist');

        if (!Auth::check()) {
            $_SESSION['intended'] = $return;
            flash_set('info', 'Sign in to save items to your wishlist.');
            redirect('/login');
        }

        $id      = (int) ($_POST['product_id'] ?? 0);
        $product = $id > 0 ? Product::find($id) : null;

        if ($product === null || (int) $product['is_active'] !== 1) {
            flash_set('error', 'That product is no longer available.');
            redirect($return);
        }

        $result = Wishlist::toggle((int) Auth::id(), (int) $product['id']);
        flash_set(
            'success',
            $result === 'added' ? 'Saved to your wishlist.' : 'Removed from your wishlist.'
        );
        redirect($return);
    }
}
