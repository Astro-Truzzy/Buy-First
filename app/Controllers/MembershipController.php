<?php

declare(strict_types=1);

/**
 * BuyFirst Club — free to join, unlocks free standard delivery and
 * member-exclusive products. The home CTA buttons land here.
 */
class MembershipController
{
    public static function show(): string
    {
        if (isset($_GET['club'])) {
            $_SESSION['join_club'] = true;
        }

        $user = Auth::user();

        return render('membership', [
            'user'      => $user,
            'isMember'  => !empty($user['is_member']),
            'exclusives' => Product::memberExclusives(4),
        ], ['title' => 'BuyFirst Club']);
    }

    /** POST /membership/join — guests are sent to register, then joined. */
    public static function join(): never
    {
        if (!Auth::check()) {
            $_SESSION['join_club'] = true;
            $_SESSION['intended'] = '/membership';
            flash_set('info', 'Create an account or sign in to join BuyFirst Club — it’s free.');
            redirect('/register?club=1');
        }

        $user = Auth::user();
        if (!empty($user['is_member'])) {
            flash_set('info', 'You’re already in the Club.');
            redirect('/membership');
        }

        User::joinClub((int) $user['id']);
        flash_set('success', 'You’re in. Standard delivery is now free on every order.');
        redirect('/membership');
    }
}
