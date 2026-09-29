<?php
/**
 * Product catalog. Receives: $products, $categories, $filters
 */
$sorts = [
    'category' => 'Category',
    'name'     => 'Name A–Z',
    'newest'   => 'Newest',
    'price'    => 'Price',
    'stock'    => 'Stock (low first)',
];
$badgeLabels = [
    'just-in'          => 'Just In',
    'best-seller'      => 'Best Seller',
    'member-exclusive' => 'Member Exclusive',
    'limited-drop'     => 'Limited Drop',
];
?>
<div class="admin-head">
    <div>
        <h1 class="admin-h1">Products</h1>
        <p class="admin-muted"><?= count($products) ?> in this view · sorted by <?= e($sorts[$filters['sort']] ?? 'category') ?></p>
    </div>
    <a class="btn btn--primary" href="/admin/products/new">New product</a>
</div>

<form class="admin-toolbar" method="get" action="/admin/products">
    <div class="admin-toolbar__search">
        <label class="visually-hidden" for="admin-q">Search products</label>
        <input type="search" id="admin-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Search name or slug">
    </div>
    <label class="visually-hidden" for="admin-status">Status</label>
    <select id="admin-status" name="status">
        <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>>All statuses</option>
        <option value="live" <?= $filters['status'] === 'live' ? 'selected' : '' ?>>Live</option>
        <option value="hidden" <?= $filters['status'] === 'hidden' ? 'selected' : '' ?>>Hidden</option>
    </select>
    <label class="visually-hidden" for="admin-cat">Category</label>
    <select id="admin-cat" name="category_id">
        <option value="0">All categories</option>
        <?php foreach ($categories as $c): ?>
            <option value="<?= e((string) $c['id']) ?>" <?= (int) $filters['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="admin-sport">Sport</label>
    <select id="admin-sport" name="sport">
        <option value="">All sports</option>
        <?php foreach (Product::SPORTS as $s): ?>
            <option value="<?= e($s) ?>" <?= $filters['sport'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="admin-gender">Gender</label>
    <select id="admin-gender" name="gender">
        <option value="">All genders</option>
        <?php foreach (Product::GENDERS as $g): ?>
            <option value="<?= e($g) ?>" <?= $filters['gender'] === $g ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
        <?php endforeach; ?>
    </select>
    <label class="visually-hidden" for="admin-sort">Sort</label>
    <select id="admin-sort" name="sort">
        <?php foreach ($sorts as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $filters['sort'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <button class="btn btn--outline" type="submit">Apply</button>
</form>

<?php if ($products === []): ?>
    <div class="admin-empty">
        <p>Nothing matches those filters.</p>
        <a class="btn btn--primary" href="/admin/products/new">Create a product</a>
    </div>
<?php else: ?>
    <div class="admin-card admin-card--flush">
        <table class="admin-table admin-table--products">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <?php
                    $stock = (int) $p['stock'];
                    $stockClass = $stock === 0 ? ' is-out' : ($stock <= 3 ? ' is-low' : '');
                    ?>
                    <tr>
                        <td>
                            <a class="admin-product-cell" href="/admin/products/<?= e((string) $p['id']) ?>/edit">
                                <?php if ($p['image']): ?>
                                    <img src="<?= e($p['image']) ?>" alt="" width="48" height="48">
                                <?php else: ?>
                                    <span class="admin-product-cell__ph" aria-hidden="true"></span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e($p['name']) ?></strong>
                                    <small><?= e(ucfirst($p['gender'])) ?> · <?= e(ucfirst($p['sport'])) ?><?php
                                        if ($p['badge']): ?> · <?= e($badgeLabels[$p['badge']] ?? $p['badge']) ?><?php endif;
                                    ?></small>
                                </span>
                            </a>
                        </td>
                        <td><?= e($p['category_name']) ?></td>
                        <td>
                            <?php if ($p['sale_price']): ?>
                                <span class="admin-price-now"><?= money($p['sale_price']) ?></span>
                                <s class="admin-price-was"><?= money($p['price']) ?></s>
                            <?php else: ?>
                                <?= money($p['price']) ?>
                            <?php endif; ?>
                        </td>
                        <td><span class="admin-stock-pill<?= $stockClass ?>"><?= e((string) $stock) ?></span></td>
                        <td>
                            <span class="status <?= (int) $p['is_active'] ? 'status--paid' : 'status--pending' ?>">
                                <?= (int) $p['is_active'] ? 'Live' : 'Hidden' ?>
                            </span>
                            <?php if ((int) $p['is_featured']): ?>
                                <span class="status">Featured</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table__actions">
                            <a class="btn btn--outline btn--small" href="/admin/products/<?= e((string) $p['id']) ?>/edit">Edit</a>
                            <form method="post" action="/admin/products/<?= e((string) $p['id']) ?>/toggle">
                                <?= csrf_field() ?>
                                <button type="submit"><?= (int) $p['is_active'] ? 'Hide' : 'Show' ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
