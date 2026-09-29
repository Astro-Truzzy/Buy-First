<?php

declare(strict_types=1);

/**
 * Admin back office. Every action starts with require_admin().
 *
 * Privilege rule: being logged in is not enough. role_id must be 1.
 * A customer guessing /admin/orders/BF-… still gets a 403.
 */
class AdminController
{
    public static function dashboard(): string
    {
        require_admin();

        $stats = [];
        foreach (Order::stats() as $row) {
            $stats[$row['status']] = $row;
        }

        return render_admin('admin/dashboard', [
            'stats'     => $stats,
            'lowStock'  => Product::lowStock(),
            'pending'   => Review::pendingCount(),
            'campaigns' => Campaign::all(),
            'audit'     => Audit::recent(8),
        ], ['title' => 'Dashboard']);
    }

    // ---------------------------------------------------------------- orders

    public static function orders(): string
    {
        require_admin();
        $status = $_GET['status'] ?? null;
        if ($status !== null && !in_array($status, Order::STATUSES, true)) {
            $status = null;
        }

        return render_admin('admin/orders', [
            'orders' => Order::all($status),
            'status' => $status,
            'counts' => Order::countsByStatus(),
        ], ['title' => 'Orders']);
    }

    public static function order(string $number): string
    {
        require_admin();
        $order = Order::findByNumber($number);
        if ($order === null) {
            http_response_code(404);
            return render_admin('errors/404', [], ['title' => 'Order not found']);
        }

        return render_admin('admin/order', [
            'order'   => $order,
            'lines'   => Order::itemsFor((int) $order['id']),
            'payment' => Order::paymentFor((int) $order['id']),
        ], ['title' => $order['order_number']]);
    }

    public static function orderStatus(string $number): never
    {
        require_admin();
        $order  = Order::findByNumber($number);
        $status = $_POST['status'] ?? '';
        if ($order === null || !in_array($status, Order::STATUSES, true)) {
            flash_set('error', 'Could not update that order.');
            redirect('/admin/orders');
        }

        $from = $order['status'];
        Order::setStatus((int) $order['id'], $status);
        Audit::log('order.status', 'order', (int) $order['id'], "{$from} → {$status}");

        if ($status === 'shipped' && $from !== 'shipped') {
            $fresh = Order::findByNumber($number);
            Mailer::orderShipped($fresh ?? $order, Order::itemsFor((int) $order['id']));
        }

        flash_set('success', 'Order ' . $order['order_number'] . ' is now ' . Order::statusLabel($status) . '.');
        redirect_back('/admin/orders/' . $order['order_number']);
    }

    // ---------------------------------------------------------------- products

    public static function products(): string
    {
        require_admin();

        $filters = self::productFiltersFromGet();

        return render_admin('admin/products', [
            'products'   => Product::adminList($filters),
            'categories' => Product::categories(),
            'filters'    => $filters,
        ], ['title' => 'Products']);
    }

    public static function productNew(): string
    {
        require_admin();

        return render_admin('admin/product-form', [
            'product'    => null,
            'variants'   => [],
            'images'     => [['url' => '', 'alt' => '', 'color' => '', 'sort_order' => 0]],
            'categories' => Product::categories(),
            'library'    => Product::imageLibrary(),
            'errors'     => [],
            'old'        => [
                'name'        => '',
                'slug'        => '',
                'category_id' => 0,
                'gender'      => 'unisex',
                'sport'       => 'lifestyle',
                'price'       => '',
                'sale_price'  => '',
                'badge'       => '',
                'description' => '',
                'details'     => '',
                'is_featured' => 0,
                'is_active'   => 1,
            ],
        ], ['title' => 'New product']);
    }

    public static function productCreate(): string
    {
        require_admin();

        $data     = self::productFromPost();
        $images   = self::imagesFromPost();
        $variants = self::variantsFromPost();
        $errors   = array_merge(
            self::productErrors($data, 0),
            self::mediaErrors($images, $variants)
        );

        if ($errors) {
            return render_admin('admin/product-form', [
                'product'    => null,
                'variants'   => $variants,
                'images'     => $images ?: [['url' => '', 'alt' => '', 'color' => '', 'sort_order' => 0]],
                'categories' => Product::categories(),
                'library'    => Product::imageLibrary(),
                'errors'     => $errors,
                'old'        => $data,
            ], ['title' => 'New product']);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $id = Product::create($data);
            Product::replaceImages($id, $images);
            Product::syncVariants($id, $variants);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Audit::log('product.create', 'product', $id, $data['name']);
        flash_set('success', $data['name'] . ' is on the shelf.');
        redirect('/admin/products/' . $id . '/edit');
    }

    public static function productEdit(string $id): string
    {
        require_admin();
        $product = Product::find((int) $id);
        if ($product === null) {
            http_response_code(404);
            return render_admin('errors/404', [], ['title' => 'Product not found']);
        }

        return render_admin('admin/product-form', [
            'product'    => $product,
            'variants'   => Product::variantsFor((int) $product['id']),
            'images'     => Product::imagesFor((int) $product['id']),
            'categories' => Product::categories(),
            'library'    => Product::imageLibrary(),
            'errors'     => [],
            'old'        => self::productOld($product),
        ], ['title' => 'Edit ' . $product['name']]);
    }

    public static function productSave(string $id): string
    {
        require_admin();
        $product = Product::find((int) $id);
        if ($product === null) {
            http_response_code(404);
            return render_admin('errors/404', [], ['title' => 'Product not found']);
        }

        $data     = self::productFromPost();
        $images   = self::imagesFromPost();
        $variants = self::variantsFromPost();
        $errors   = array_merge(
            self::productErrors($data, (int) $product['id']),
            self::mediaErrors($images, $variants)
        );

        if ($errors) {
            return render_admin('admin/product-form', [
                'product'    => $product,
                'variants'   => $variants,
                'images'     => $images ?: [['url' => '', 'alt' => '', 'color' => '', 'sort_order' => 0]],
                'categories' => Product::categories(),
                'library'    => Product::imageLibrary(),
                'errors'     => $errors,
                'old'        => $data + ['id' => $product['id']],
            ], ['title' => 'Edit ' . $product['name']]);
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Product::update((int) $product['id'], $data);
            Product::replaceImages((int) $product['id'], $images);
            Product::syncVariants((int) $product['id'], $variants);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        Audit::log('product.update', 'product', (int) $product['id'], $data['name']);
        flash_set('success', $data['name'] . ' saved.');
        redirect('/admin/products/' . $product['id'] . '/edit');
    }

    public static function productToggle(string $id): never
    {
        require_admin();
        $product = Product::find((int) $id);
        if ($product === null) {
            redirect('/admin/products');
        }

        $next = !((int) $product['is_active'] === 1);
        Product::setActive((int) $product['id'], $next);
        Audit::log('product.toggle', 'product', (int) $product['id'], $next ? 'activated' : 'deactivated');
        flash_set('success', $product['name'] . ($next ? ' is on the shelf.' : ' is hidden from the store.'));
        redirect('/admin/products');
    }

    // ---------------------------------------------------------------- pages

    public static function pages(): string
    {
        require_admin();

        return render_admin('admin/pages', ['pages' => Page::all()], ['title' => 'Pages']);
    }

    public static function pageEdit(string $id): string
    {
        require_admin();
        $page = Page::find((int) $id);
        if ($page === null) {
            http_response_code(404);
            return render_admin('errors/404', [], ['title' => 'Page not found']);
        }

        return render_admin('admin/page-form', [
            'page'   => $page,
            'errors' => [],
        ], ['title' => 'Edit ' . $page['title']]);
    }

    public static function pageSave(string $id): never
    {
        require_admin();
        $page = Page::find((int) $id);
        if ($page === null) {
            redirect('/admin/pages');
        }

        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        if ($title === '' || $content === '') {
            flash_set('error', 'Title and content are required.');
            redirect('/admin/pages/' . $id . '/edit');
        }

        Page::update((int) $page['id'], $title, $content);
        Audit::log('page.update', 'page', (int) $page['id'], $page['slug']);
        flash_set('success', $title . ' saved. View it on the storefront.');
        redirect('/admin/pages/' . $page['id'] . '/edit');
    }

    // ---------------------------------------------------------------- payment settings

    public static function paymentSettings(): string
    {
        require_admin();

        return render_admin('admin/payment-settings', [
            'account' => BankAccount::get(),
            'errors'  => [],
        ], ['title' => 'Payment settings']);
    }

    public static function paymentSettingsSave(): never
    {
        require_admin();

        $bankName      = trim($_POST['bank_name'] ?? '');
        $accountName   = trim($_POST['account_name'] ?? '');
        $accountNumber = trim($_POST['account_number'] ?? '');

        $errors = [];
        if ($bankName === '' || mb_strlen($bankName) > 120) {
            $errors[] = 'Enter the bank name.';
        }
        if ($accountName === '' || mb_strlen($accountName) > 120) {
            $errors[] = 'Enter the account name.';
        }
        if ($accountNumber === '' || mb_strlen($accountNumber) > 40) {
            $errors[] = 'Enter the account number.';
        }

        if ($errors) {
            flash_set('error', implode(' ', $errors));
            redirect('/admin/payment-settings');
        }

        BankAccount::update($bankName, $accountName, $accountNumber);
        Audit::log('payment_settings.update', 'bank_account', 1, $bankName);
        flash_set('success', 'Bank transfer details saved.');
        redirect('/admin/payment-settings');
    }

    // ---------------------------------------------------------------- reviews + campaigns + audit

    public static function reviews(): string
    {
        require_admin();

        return render_admin('admin/reviews', [
            'reviews' => Review::pending(),
        ], ['title' => 'Reviews']);
    }

    public static function reviewStatus(string $id): never
    {
        require_admin();
        $status = $_POST['status'] ?? '';
        Review::setStatus((int) $id, $status);
        Audit::log('review.status', 'review', (int) $id, $status);
        flash_set('success', 'Review ' . $status . '.');
        redirect('/admin/reviews');
    }

    public static function campaignToggle(string $id): never
    {
        require_admin();
        $active = ($_POST['active'] ?? '') === '1';
        Campaign::setActive((int) $id, $active);
        Audit::log('campaign.toggle', 'campaign', (int) $id, $active ? 'on' : 'off');
        redirect('/admin');
    }

    public static function audit(): string
    {
        require_admin();

        return render_admin('admin/audit', [
            'logs' => Audit::recent(100),
        ], ['title' => 'Audit log']);
    }

    // ---------------------------------------------------------------- helpers

    /** Map a stored product row onto the form fields used by product-form.php. */
    private static function productOld(array $product): array
    {
        return [
            'id'          => $product['id'],
            'name'        => $product['name'],
            'slug'        => $product['slug'],
            'category_id' => $product['category_id'],
            'gender'      => $product['gender'],
            'sport'       => $product['sport'],
            'price'       => self::decimalInput($product['price'] ?? ''),
            'sale_price'  => self::decimalInput($product['sale_price'] ?? ''),
            'badge'       => $product['badge'] ?? '',
            'description' => $product['description'] ?? '',
            'details'     => $product['details'] ?? '',
            'is_featured' => (int) ($product['is_featured'] ?? 0),
            'is_active'   => (int) ($product['is_active'] ?? 0),
        ];
    }

    private static function decimalInput(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        $n = (float) $value;
        if (abs($n - round($n)) < 0.00001) {
            return (string) (int) round($n);
        }

        return number_format($n, 2, '.', '');
    }

    private static function productFromPost(): array
    {
        $sale  = trim($_POST['sale_price'] ?? '');
        $badge = $_POST['badge'] ?? '';
        $name  = trim($_POST['name'] ?? '');
        $slug  = strtolower(trim($_POST['slug'] ?? ''));
        if ($slug === '' && $name !== '') {
            $slug = Product::slugify($name);
        }

        return [
            'category_id'  => (int) ($_POST['category_id'] ?? 0),
            'name'         => $name,
            'slug'         => $slug,
            'description'  => trim($_POST['description'] ?? ''),
            'details'      => trim($_POST['details'] ?? ''),
            'gender'       => $_POST['gender'] ?? '',
            'sport'        => $_POST['sport'] ?? '',
            'price'        => $_POST['price'] ?? '',
            'sale_price'   => $sale === '' ? null : $sale,
            'badge'        => $badge === '' ? null : $badge,
            'is_featured'  => isset($_POST['is_featured']) ? 1 : 0,
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ];
    }

    /** @return list<array{url: string, alt: string, color: string, sort_order: int}> */
    private static function imagesFromPost(): array
    {
        $posted = $_POST['images'] ?? [];
        if (!is_array($posted)) {
            return [];
        }

        $images = [];
        foreach ($posted as $i => $row) {
            if (!is_array($row)) {
                continue;
            }
            $url = trim((string) ($row['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $images[] = [
                'url'        => $url,
                'alt'        => trim((string) ($row['alt'] ?? '')),
                'color'      => trim((string) ($row['color'] ?? '')),
                'sort_order' => (int) ($row['sort_order'] ?? $i),
            ];
        }

        return array_slice($images, 0, 8);
    }

    /** @return list<array{id: int, sku: string, color: string, size: string, stock: int}> */
    private static function variantsFromPost(): array
    {
        $posted = $_POST['variants'] ?? [];
        if (!is_array($posted)) {
            return [];
        }

        $slug = Product::slugify(trim($_POST['slug'] ?? '') ?: trim($_POST['name'] ?? ''));
        $rows = [];
        foreach ($posted as $row) {
            if (!is_array($row)) {
                continue;
            }
            $color = trim((string) ($row['color'] ?? ''));
            $size  = trim((string) ($row['size'] ?? ''));
            if ($color === '' && $size === '') {
                continue;
            }
            $id  = (int) ($row['id'] ?? 0);
            $sku = strtoupper(trim((string) ($row['sku'] ?? '')));
            if ($sku === '') {
                $sku = Product::uniqueSku($slug !== '' ? $slug : 'prod', $color, $size, $id);
            }
            $rows[] = [
                'id'    => $id,
                'sku'   => $sku,
                'color' => $color,
                'size'  => $size,
                'stock' => max(0, (int) ($row['stock'] ?? 0)),
            ];
        }

        return array_slice($rows, 0, 40);
    }

    private static function productFiltersFromGet(): array
    {
        $sort = $_GET['sort'] ?? 'category';
        if (!in_array($sort, ['category', 'name', 'newest', 'price', 'stock'], true)) {
            $sort = 'category';
        }
        $status = $_GET['status'] ?? 'all';
        if (!in_array($status, ['all', 'live', 'hidden'], true)) {
            $status = 'all';
        }

        return [
            'q'           => trim($_GET['q'] ?? ''),
            'status'      => $status,
            'category_id' => (int) ($_GET['category_id'] ?? 0),
            'sport'       => (string) ($_GET['sport'] ?? ''),
            'gender'      => (string) ($_GET['gender'] ?? ''),
            'sort'        => $sort,
        ];
    }

    /**
     * @param list<array{url: string, alt: string, color: string, sort_order: int}> $images
     * @param list<array{id: int, sku: string, color: string, size: string, stock: int}> $variants
     * @return list<string>
     */
    private static function mediaErrors(array $images, array $variants): array
    {
        $errors = [];
        if ($images === []) {
            $errors[] = 'Add at least one image — paste an https URL or pick from the library.';
        }
        foreach ($images as $i => $image) {
            if (!Product::isUsableImageUrl($image['url'])) {
                $errors[] = 'Image ' . ($i + 1) . ' needs a valid https URL or an /assets/ path.';
            }
            if ($image['alt'] === '') {
                $errors[] = 'Image ' . ($i + 1) . ' needs alt text.';
            }
        }

        if ($variants === []) {
            $errors[] = 'Add at least one size/colour so the product can be bought.';
        }
        $skus = [];
        foreach ($variants as $i => $row) {
            $n = $i + 1;
            if ($row['color'] === '' || mb_strlen($row['color']) > 40) {
                $errors[] = 'Variant ' . $n . ' needs a colour name.';
            }
            if ($row['size'] === '' || mb_strlen($row['size']) > 12) {
                $errors[] = 'Variant ' . $n . ' needs a size (e.g. EU 43 or M).';
            }
            if ($row['sku'] === '' || !preg_match('/^[A-Z0-9-]{4,40}$/', $row['sku'])) {
                $errors[] = 'Variant ' . $n . ' SKU must be 4–40 letters, numbers or hyphens.';
            } elseif (isset($skus[$row['sku']])) {
                $errors[] = 'SKU ' . $row['sku'] . ' is used twice on this product.';
            } elseif (Product::skuTaken($row['sku'], $row['id'])) {
                $errors[] = 'SKU ' . $row['sku'] . ' is already on another product.';
            }
            $skus[$row['sku']] = true;
        }

        return $errors;
    }

    private static function productErrors(array $data, int $id): array
    {
        $errors = [];
        if ($data['name'] === '' || mb_strlen($data['name']) > 120) {
            $errors[] = 'Enter a product name.';
        }
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $data['slug'])) {
            $errors[] = 'Slug must be lowercase letters, numbers and hyphens.';
        } elseif (Product::slugTakenByOther($data['slug'], $id)) {
            $errors[] = 'That slug is already used by another product.';
        }
        if ($data['description'] === '') {
            $errors[] = 'Enter a description.';
        }
        if (!in_array($data['gender'], Product::GENDERS, true)) {
            $errors[] = 'Pick a gender.';
        }
        if (!in_array($data['sport'], Product::SPORTS, true)) {
            $errors[] = 'Pick a sport.';
        }
        if (!is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors[] = 'Enter a valid price.';
        }
        if ($data['sale_price'] !== null && (!is_numeric($data['sale_price']) || (float) $data['sale_price'] < 0)) {
            $errors[] = 'Sale price must be a number, or blank.';
        }
        if ($data['badge'] !== null && !in_array($data['badge'], Product::BADGES, true)) {
            $errors[] = 'Pick a valid badge, or none.';
        }
        $catIds = array_map('intval', array_column(Product::categories(), 'id'));
        if (!in_array($data['category_id'], $catIds, true)) {
            $errors[] = 'Pick a category.';
        }

        return $errors;
    }
}
