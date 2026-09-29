<?php

declare(strict_types=1);

/**
 * CMS pages + the contact form.
 *
 * GET /{slug} is registered last in the router so real routes (/cart, /men)
 * win. We only render a page when the slug exists in the pages table.
 */
class PageController
{
    public static function show(string $slug): string
    {
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Page not found']);
        }

        $page = Page::bySlug($slug);
        if ($page === null) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Page not found']);
        }

        return render('page', [
            'page'   => $page,
            'group'  => Page::groupFor($slug),
            'errors' => [],
            'old'    => [],
        ], ['title' => $page['title']]);
    }

    /** POST /contact — we do not store the message; we acknowledge it. */
    public static function contact(): string
    {
        $page = Page::bySlug('contact');
        if ($page === null) {
            http_response_code(404);
            return render('errors/404', [], ['title' => 'Page not found']);
        }

        // Honeypot: bots fill every field. Humans never see "website".
        if (trim($_POST['website'] ?? '') !== '') {
            flash_set('success', 'Thanks — we’ll get back to you within two working days.');
            redirect('/contact');
        }

        $name    = trim($_POST['name'] ?? '');
        $email   = strtolower(trim($_POST['email'] ?? ''));
        $message = trim($_POST['message'] ?? '');
        $errors  = [];

        if ($name === '' || mb_strlen($name) > 80) {
            $errors[] = 'Please enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (mb_strlen($message) < 10) {
            $errors[] = 'Tell us a little more (at least 10 characters).';
        }
        if (!rate_allow('contact', 5, 900)) {
            $errors[] = 'Too many messages from this network. Try again in a few minutes.';
        }

        if ($errors) {
            return render('page', [
                'page'   => $page,
                'group'  => Page::groupFor('contact'),
                'errors' => $errors,
                'old'    => ['name' => $name, 'email' => $email, 'message' => $message],
            ], ['title' => $page['title']]);
        }

        Mailer::contact($name, $email, $message);

        flash_set('success', 'Thanks — we’ll get back to you within two working days.');
        redirect('/contact');
    }

    /** POST /consent — Accept all, essential-only, or reset so the banner returns. */
    public static function consent(): never
    {
        $choice = $_POST['choice'] ?? '';
        if ($choice === 'reset') {
            clear_consent();
        } else {
            set_consent($choice === 'all' ? 'all' : 'essential');
        }
        redirect_back('/');
    }
}
