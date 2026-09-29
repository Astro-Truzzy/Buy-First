<?php
/**
 * Create / edit product.
 * Receives: $product, $variants, $images, $categories, $library, $errors, $old
 */
$editing = $product !== null;
$action  = $editing ? '/admin/products/' . $product['id'] : '/admin/products';
$groups  = [];
foreach ($library as $shot) {
    $groups[$shot['group']][] = $shot;
}
if ($images === []) {
    $images = [['url' => '', 'alt' => '', 'color' => '', 'sort_order' => 0]];
}
if ($variants === []) {
    $variants = [['id' => 0, 'sku' => '', 'color' => '', 'size' => '', 'stock' => 10]];
}
?>
<div class="admin-head">
    <div>
        <p class="admin-crumb"><a href="/admin/products">Products</a> / <?= $editing ? e($product['name']) : 'New' ?></p>
        <h1 class="admin-h1"><?= $editing ? 'Edit product' : 'New product' ?></h1>
        <p class="admin-muted">
            <?php if ($editing): ?>
                <?= !empty($old['is_active']) ? 'Live on the store' : 'Hidden from the store' ?>
                <?php if (!empty($product['updated_at'])): ?>
                    · last saved <?= e(date('j M Y, H:i', strtotime((string) $product['updated_at']))) ?>
                <?php endif; ?>
            <?php else: ?>
                Name, sizes, price and at least one image — then it can go on the shelf.
            <?php endif; ?>
        </p>
    </div>
    <div class="admin-head__actions">
        <?php if ($editing): ?>
            <a class="btn btn--outline" href="/product/<?= e($old['slug'] ?? $product['slug']) ?>" target="_blank" rel="noopener">View storefront</a>
            <button class="btn btn--primary" type="submit" form="product-form">Save product</button>
        <?php endif; ?>
    </div>
</div>

<?php if ($errors): ?>
    <div class="form-errors" role="alert">
        <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="admin-product" id="product-form" data-product-form<?= $editing ? ' data-editing' : '' ?>>
    <?= csrf_field() ?>

    <div class="admin-product__main">
        <section class="admin-card">
            <h2>Identity</h2>
            <div class="field">
                <label for="name">Name</label>
                <input id="name" name="name" maxlength="120" required value="<?= e($old['name'] ?? '') ?>" data-slug-source>
            </div>
            <div class="field">
                <label for="slug">Slug</label>
                <input id="slug" name="slug" maxlength="140" value="<?= e($old['slug'] ?? '') ?>" data-slug-target
                       placeholder="generated-from-name">
            </div>
            <div class="admin-fields-3">
                <div class="field">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" required>
                        <?php if (!$editing): ?>
                            <option value="">Choose…</option>
                        <?php endif; ?>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e((string) $c['id']) ?>" <?= (int) $c['id'] === (int) ($old['category_id'] ?? 0) ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender">
                        <?php foreach (Product::GENDERS as $g): ?>
                            <option value="<?= e($g) ?>" <?= ($old['gender'] ?? '') === $g ? 'selected' : '' ?>><?= e(ucfirst($g)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label for="sport">Sport</label>
                    <select id="sport" name="sport">
                        <?php foreach (Product::SPORTS as $s): ?>
                            <option value="<?= e($s) ?>" <?= ($old['sport'] ?? '') === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5" required><?= e($old['description'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="details">Details <span class="field__optional">(one bullet per line)</span></label>
                <textarea id="details" name="details" rows="4"><?= e($old['details'] ?? '') ?></textarea>
            </div>
        </section>

        <section class="admin-card">
            <div class="admin-card__head">
                <h2>Sizes &amp; stock</h2>
            </div>
            <p class="admin-hint">Each row is a buyable SKU. Leave SKU blank and we’ll generate one. Size presets add rows — they do not replace what is already here.</p>
            <div class="admin-preset">
                <label for="preset-color">Colour to stamp</label>
                <input id="preset-color" type="text" value="" placeholder="e.g. Crimson Volt" data-preset-color maxlength="40">
                <div class="admin-preset__btns">
                    <?php foreach (Product::SIZE_PRESETS as $key => $sizes): ?>
                        <button type="button" class="btn btn--outline btn--small" data-fill-sizes="<?= e(json_encode($sizes)) ?>">
                            <?= e($key === 'one' ? 'One size' : ucfirst($key)) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="admin-table-wrap">
                <table class="admin-table" data-variant-table>
                    <thead>
                        <tr><th>SKU</th><th>Colour</th><th>Size</th><th>Stock</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variants as $i => $v): ?>
                            <tr>
                                <td>
                                    <input type="hidden" name="variants[<?= (int) $i ?>][id]" value="<?= e((string) ($v['id'] ?? '')) ?>">
                                    <input name="variants[<?= (int) $i ?>][sku]" value="<?= e($v['sku'] ?? '') ?>" placeholder="Auto">
                                </td>
                                <td><input name="variants[<?= (int) $i ?>][color]" value="<?= e($v['color'] ?? '') ?>" required></td>
                                <td><input name="variants[<?= (int) $i ?>][size]" value="<?= e($v['size'] ?? '') ?>" required></td>
                                <td><input type="number" min="0" name="variants[<?= (int) $i ?>][stock]" value="<?= e((string) ($v['stock'] ?? 0)) ?>" class="admin-stock"></td>
                                <td><button type="button" class="admin-row-remove" data-remove-row aria-label="Remove size">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn--outline btn--small" data-add-variant>Add size</button>
        </section>
    </div>

    <aside class="admin-product__side">
        <section class="admin-card">
            <h2>Pricing</h2>
            <div class="field">
                <label for="price">Price (₦)</label>
                <input id="price" name="price" inputmode="decimal" required value="<?= e((string) ($old['price'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="sale_price">Sale price <span class="field__optional">(blank = none)</span></label>
                <input id="sale_price" name="sale_price" inputmode="decimal" value="<?= e((string) ($old['sale_price'] ?? '')) ?>">
            </div>
            <div class="field">
                <label for="badge">Badge</label>
                <select id="badge" name="badge">
                    <option value="">None</option>
                    <?php foreach (Product::BADGES as $b): ?>
                        <option value="<?= e($b) ?>" <?= ($old['badge'] ?? '') === $b ? 'selected' : '' ?>><?= e(str_replace('-', ' ', $b)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="field field--checkbox">
                <input type="checkbox" name="is_featured" <?= !empty($old['is_featured']) ? 'checked' : '' ?>> Featured on home
            </label>
            <label class="field field--checkbox">
                <input type="checkbox" name="is_active" <?= !empty($old['is_active']) ? 'checked' : '' ?>> Live on the store
            </label>
        </section>

        <section class="admin-card">
            <h2>Images</h2>
            <p class="admin-hint">Paste an https URL, or pick a shot from the library. First image is the product card; the rest appear in the gallery.</p>
            <div data-image-list>
                <?php foreach ($images as $i => $img): ?>
                    <div class="admin-image" data-image-row>
                        <div class="admin-image__preview">
                            <?php if (!empty($img['url'])): ?>
                                <img src="<?= e($img['url']) ?>" alt="">
                            <?php else: ?>
                                <span>No image</span>
                            <?php endif; ?>
                        </div>
                        <div class="admin-image__fields">
                            <input name="images[<?= (int) $i ?>][url]" value="<?= e($img['url'] ?? '') ?>" placeholder="https://…" data-image-url>
                            <input name="images[<?= (int) $i ?>][alt]" value="<?= e($img['alt'] ?? '') ?>" placeholder="Alt text">
                            <div class="admin-image__row">
                                <input name="images[<?= (int) $i ?>][color]" value="<?= e($img['color'] ?? '') ?>" placeholder="Colourway">
                                <input type="number" min="0" name="images[<?= (int) $i ?>][sort_order]" value="<?= e((string) ($img['sort_order'] ?? $i)) ?>" aria-label="Sort order">
                                <button type="button" class="admin-row-remove" data-remove-row aria-label="Remove image">Remove</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn--outline btn--small" data-add-image>Add image</button>

            <details class="admin-library">
                <summary>Image library</summary>
                <?php foreach ($groups as $group => $shots): ?>
                    <p class="admin-library__label"><?= e($group) ?></p>
                    <div class="admin-library__grid">
                        <?php foreach ($shots as $shot): ?>
                            <button type="button" class="admin-library__shot" data-library-url="<?= e($shot['url']) ?>" data-library-alt="<?= e($shot['label']) ?>">
                                <img src="<?= e($shot['url']) ?>" alt="">
                                <span><?= e($shot['label']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </details>
        </section>

        <button class="btn btn--primary admin-product__save" type="submit"><?= $editing ? 'Save product' : 'Create product' ?></button>
    </aside>
</form>

<template id="image-row-tpl">
    <div class="admin-image" data-image-row>
        <div class="admin-image__preview"><span>No image</span></div>
        <div class="admin-image__fields">
            <input name="images[__i__][url]" placeholder="https://…" data-image-url>
            <input name="images[__i__][alt]" placeholder="Alt text">
            <div class="admin-image__row">
                <input name="images[__i__][color]" placeholder="Colourway">
                <input type="number" min="0" name="images[__i__][sort_order]" value="0" aria-label="Sort order">
                <button type="button" class="admin-row-remove" data-remove-row aria-label="Remove image">Remove</button>
            </div>
        </div>
    </div>
</template>
<template id="variant-row-tpl">
    <tr>
        <td>
            <input type="hidden" name="variants[__i__][id]" value="">
            <input name="variants[__i__][sku]" placeholder="Auto">
        </td>
        <td><input name="variants[__i__][color]"></td>
        <td><input name="variants[__i__][size]"></td>
        <td><input type="number" min="0" name="variants[__i__][stock]" value="10" class="admin-stock"></td>
        <td><button type="button" class="admin-row-remove" data-remove-row aria-label="Remove size">Remove</button></td>
    </tr>
</template>
