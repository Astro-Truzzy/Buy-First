<?php

declare(strict_types=1);

/**
 * Controller for the home page.
 *
 * The MVC flow in action: ask Models for data, pass it to the View.
 * No SQL here, no HTML here — just coordination.
 */
class HomeController
{
    public static function index(): string
    {
        return render('home', [
            'campaigns'   => Campaign::active(),
            'trending'    => Product::featured(12),
            'justIn'      => Product::justIn(8),
            'bestSellers' => Product::bestSellers(4),
        ], [
            'title'             => 'Gear up. Move first.',
            'transparentHeader' => true,
        ]);
    }
}
