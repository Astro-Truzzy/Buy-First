<?php

declare(strict_types=1);

/**
 * BuyFirst front controller.
 *
 * EVERY request to the site enters through this single file.
 * Its job, in order:
 *   1. Load helpers and configuration.
 *   2. Start the session (secure cookie settings).
 *   3. Define the routes.
 *   4. Hand the request to the router and print the result.
 */

// __DIR__ is the folder this file lives in (public/), so dirname(__DIR__)
// is the project root — one level up, safely outside the web root.
$root = dirname(__DIR__);

require $root . '/lib/helpers.php';
require $root . '/lib/security.php';
require $root . '/lib/Router.php';
require $root . '/lib/Database.php';
require $root . '/lib/csrf.php';
require $root . '/lib/auth.php';
require $root . '/lib/smtp.php';
require $root . '/lib/mailer.php';

load_env($root . '/.env');
require $root . '/config/config.php';

send_security_headers();

// While developing, show errors on screen so we can fix them.
// In production APP_DEBUG=false hides them (error details leak information).
if (config()['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// A session lets the server remember a visitor between requests (PHP gives
// the browser a random-ID cookie and stores data server-side under that ID).
// We'll use it for the cart and for "who is logged in".
session_start([
    'use_strict_mode' => true,   // reject uninitialized session IDs (fixation)
    'cookie_httponly' => true,   // JavaScript cannot read the session cookie (XSS protection)
    'cookie_samesite' => 'Lax',  // cookie is not sent on cross-site POSTs (CSRF protection)
    'cookie_secure'   => is_https(),
]);

// ---------------------------------------------------------------------------
// Routes. As we build features, new lines are added here — this list becomes
// a readable table of contents for the entire site.
// ---------------------------------------------------------------------------

require $root . '/app/Models/Product.php';
require $root . '/app/Models/Campaign.php';
require $root . '/app/Models/Review.php';
require $root . '/app/Models/User.php';
require $root . '/app/Models/Cart.php';
require $root . '/app/Models/Address.php';
require $root . '/app/Models/Coupon.php';
require $root . '/app/Models/Order.php';
require $root . '/app/Models/BankAccount.php';
require $root . '/app/Models/PasswordReset.php';
require $root . '/app/Controllers/HomeController.php';
require $root . '/app/Controllers/CatalogController.php';
require $root . '/app/Controllers/ProductController.php';
require $root . '/app/Controllers/AuthController.php';
require $root . '/app/Controllers/CartController.php';
require $root . '/app/Controllers/CheckoutController.php';
require $root . '/app/Controllers/AccountController.php';
require $root . '/app/Models/Page.php';
require $root . '/app/Controllers/PageController.php';
require $root . '/app/Models/Journal.php';
require $root . '/app/Controllers/JournalController.php';
require $root . '/app/Models/Audit.php';
require $root . '/app/Controllers/AdminController.php';
require $root . '/app/Controllers/SearchController.php';
require $root . '/app/Models/Wishlist.php';
require $root . '/app/Controllers/WishlistController.php';
require $root . '/app/Controllers/MembershipController.php';

// Sitewide CSRF enforcement: every POST request, no exceptions.
// One line in the front controller protects every form we will ever add.
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
    csrf_verify();
}

$router = new Router();

// Home
$router->get('/', fn() => HomeController::index());

// Catalog listings
$router->get('/new', fn() => CatalogController::newIn());
$router->get('/sale', fn() => CatalogController::sale());
$router->get('/men', fn() => CatalogController::gender('men'));
$router->get('/women', fn() => CatalogController::gender('women'));
$router->get('/kids', fn() => CatalogController::gender('kids'));
$router->get('/men/{category}', fn(string $c) => CatalogController::gender('men', $c));
$router->get('/women/{category}', fn(string $c) => CatalogController::gender('women', $c));
$router->get('/kids/{category}', fn(string $c) => CatalogController::gender('kids', $c));
$router->get('/sport/{sport}', fn(string $s) => CatalogController::sport($s));

// Product detail
$router->get('/product/{slug}', fn(string $slug) => ProductController::show($slug));
$router->post('/product/{slug}/review', fn(string $slug) => ProductController::review($slug));

// Search — before the /{slug} catch-all
$router->get('/search', fn() => SearchController::index());
$router->get('/search/suggest', fn() => SearchController::suggest());

// Bag
$router->get('/cart', fn() => CartController::index());
$router->post('/cart/add', fn() => CartController::add());
$router->post('/cart/update', fn() => CartController::update());
$router->post('/cart/remove', fn() => CartController::remove());
$router->post('/cart/clear', fn() => CartController::clear());
$router->post('/cart/coupon', fn() => CartController::coupon());

// Wishlist — before the /{slug} catch-all
$router->get('/wishlist', fn() => WishlistController::index());
$router->post('/wishlist/toggle', fn() => WishlistController::toggle());

// Checkout
$router->get('/checkout', fn() => CheckoutController::show());
$router->post('/checkout', fn() => CheckoutController::place());
$router->post('/checkout/coupon', fn() => CheckoutController::coupon());
$router->post('/checkout/delivery', fn() => CheckoutController::delivery());
$router->get('/checkout/success/{number}', fn(string $n) => CheckoutController::success($n));
$router->post('/checkout/success/{number}/claim-payment', fn(string $n) => CheckoutController::claimPayment($n));

// Auth + account
$router->get('/login', fn() => AuthController::showLogin());
$router->post('/login', fn() => AuthController::login());
$router->get('/register', fn() => AuthController::showRegister());
$router->post('/register', fn() => AuthController::register());
$router->post('/logout', fn() => AuthController::logout());
$router->get('/forgot-password', fn() => AuthController::showForgot());
$router->post('/forgot-password', fn() => AuthController::sendForgot());
$router->get('/reset-password/{token}', fn(string $t) => AuthController::showReset($t));
$router->post('/reset-password/{token}', fn(string $t) => AuthController::reset($t));

$router->get('/account', fn() => AccountController::index());
$router->get('/account/orders', fn() => AccountController::orders());
$router->get('/account/orders/{number}', fn(string $n) => AccountController::order($n));
$router->post('/account/orders/{number}/buy-again', fn(string $n) => AccountController::buyAgain($n));
$router->get('/account/addresses', fn() => AccountController::addresses());
$router->get('/account/addresses/new', fn() => AccountController::addressForm());
$router->post('/account/addresses', fn() => AccountController::addressSave());
$router->post('/account/addresses/delete', fn() => AccountController::addressDelete());
$router->post('/account/addresses/default', fn() => AccountController::addressDefault());
$router->get('/account/addresses/{id}/edit', fn(string $id) => AccountController::addressForm($id));
$router->post('/account/addresses/{id}', fn(string $id) => AccountController::addressSave($id));
$router->get('/account/profile', fn() => AccountController::profile());
$router->post('/account/profile', fn() => AccountController::profileSave());
$router->post('/account/password', fn() => AccountController::passwordSave());

$router->get('/membership', fn() => MembershipController::show());
$router->post('/membership/join', fn() => MembershipController::join());

// Contact form (the GET page is the CMS catch-all below)
$router->post('/contact', fn() => PageController::contact());
$router->post('/consent', fn() => PageController::consent());

// Admin — MUST sit above the /{slug} catch-all
$router->get('/admin', fn() => AdminController::dashboard());
$router->get('/admin/orders', fn() => AdminController::orders());
$router->get('/admin/orders/{number}', fn(string $n) => AdminController::order($n));
$router->post('/admin/orders/{number}/status', fn(string $n) => AdminController::orderStatus($n));
$router->get('/admin/products', fn() => AdminController::products());
$router->get('/admin/products/new', fn() => AdminController::productNew());
$router->post('/admin/products', fn() => AdminController::productCreate());
$router->post('/admin/products/{id}/toggle', fn(string $id) => AdminController::productToggle($id));
$router->get('/admin/products/{id}/edit', fn(string $id) => AdminController::productEdit($id));
$router->post('/admin/products/{id}', fn(string $id) => AdminController::productSave($id));
$router->get('/admin/pages', fn() => AdminController::pages());
$router->get('/admin/pages/{id}/edit', fn(string $id) => AdminController::pageEdit($id));
$router->post('/admin/pages/{id}', fn(string $id) => AdminController::pageSave($id));
$router->get('/admin/payment-settings', fn() => AdminController::paymentSettings());
$router->post('/admin/payment-settings', fn() => AdminController::paymentSettingsSave());
$router->get('/admin/reviews', fn() => AdminController::reviews());
$router->post('/admin/reviews/{id}', fn(string $id) => AdminController::reviewStatus($id));
$router->post('/admin/campaigns/{id}/toggle', fn(string $id) => AdminController::campaignToggle($id));
$router->get('/admin/audit', fn() => AdminController::audit());

// Journal — before the /{slug} catch-all so /journal is not a CMS page
$router->get('/journal', fn() => JournalController::index());
$router->get('/journal/{slug}', fn(string $slug) => JournalController::show($slug));

// CMS pages last: /privacy, /help, /about, … First-match wins, so /cart
// and /men never fall through to this.
$router->get('/{slug}', fn(string $slug) => PageController::show($slug));

// Run the request and send the resulting HTML to the browser.
try {
    echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (Throwable $e) {
    // 500 = our bug. Log the real details; show a branded page to the visitor.
    error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    try {
        echo render('errors/500', [
            'debug'   => config()['app']['debug'],
            'message' => $e->getMessage(),
        ], ['title' => 'Something went wrong']);
    } catch (Throwable) {
        echo '<!DOCTYPE html><html><body><h1>Something went wrong.</h1></body></html>';
    }
}
