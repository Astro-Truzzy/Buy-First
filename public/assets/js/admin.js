/**
 * BuyFirst admin — product form helpers only.
 * Slug from name, image rows, library picker, size presets.
 */
'use strict';

const form = document.querySelector('[data-product-form]');
if (form) {
    const slugSource = form.querySelector('[data-slug-source]');
    const slugTarget = form.querySelector('[data-slug-target]');
    let slugTouched = Boolean(slugTarget && slugTarget.value.trim());

    const slugify = (value) =>
        value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 140);

    slugTarget?.addEventListener('input', () => {
        slugTouched = slugTarget.value.trim() !== '';
    });
    slugSource?.addEventListener('input', () => {
        if (!slugTouched && slugTarget) slugTarget.value = slugify(slugSource.value);
    });

    const reindex = (list, prefix) => {
        list.querySelectorAll(':scope > *').forEach((row, i) => {
            row.querySelectorAll('[name]').forEach((input) => {
                input.name = input.name.replace(new RegExp('^' + prefix + '\\[\\d+\\]'), prefix + '[' + i + ']');
            });
        });
    };

    const imageList = form.querySelector('[data-image-list]');
    const imageTpl = document.getElementById('image-row-tpl');
    const variantTable = form.querySelector('[data-variant-table] tbody');
    const variantTpl = document.getElementById('variant-row-tpl');

    const bindPreview = (row) => {
        const input = row.querySelector('[data-image-url]');
        const preview = row.querySelector('.admin-image__preview');
        if (!input || !preview) return;
        const paint = () => {
            const url = input.value.trim();
            const ok = url.startsWith('https://') || url.startsWith('/assets/');
            if (!ok) {
                preview.innerHTML = '<span>No image</span>';
                return;
            }
            preview.innerHTML = '';
            const img = document.createElement('img');
            img.alt = '';
            img.src = url;
            img.addEventListener('error', () => {
                preview.innerHTML = '<span>Can’t load</span>';
            });
            preview.append(img);
        };
        input.addEventListener('input', paint);
        paint();
    };

    imageList?.querySelectorAll('[data-image-row]').forEach(bindPreview);

    form.querySelector('[data-add-image]')?.addEventListener('click', () => {
        if (!imageList || !imageTpl) return;
        const node = imageTpl.content.firstElementChild.cloneNode(true);
        imageList.append(node);
        reindex(imageList, 'images');
        bindPreview(node);
        node.querySelector('[data-image-url]')?.focus();
    });

    form.querySelector('[data-add-variant]')?.addEventListener('click', () => {
        if (!variantTable || !variantTpl) return;
        const node = variantTpl.content.firstElementChild.cloneNode(true);
        variantTable.append(node);
        reindex(variantTable, 'variants');
        node.querySelector('input[name*="[color]"]')?.focus();
    });

    form.addEventListener('click', (e) => {
        const remove = e.target.closest('[data-remove-row]');
        if (!remove) return;
        const row = remove.closest('[data-image-row], tr');
        if (!row) return;
        const parent = row.parentElement;
        row.remove();
        if (parent === imageList) reindex(imageList, 'images');
        if (parent === variantTable) reindex(variantTable, 'variants');
    });

    form.querySelectorAll('[data-library-url]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!imageList) return;
            let target = Array.from(imageList.querySelectorAll('[data-image-url]')).find((input) => !input.value.trim());
            if (!target) {
                form.querySelector('[data-add-image]')?.click();
                target = imageList.querySelector('[data-image-row]:last-child [data-image-url]');
            }
            if (!target) return;
            target.value = btn.dataset.libraryUrl || '';
            const alt = target.closest('[data-image-row]')?.querySelector('input[name*="[alt]"]');
            if (alt && !alt.value.trim()) alt.value = btn.dataset.libraryAlt || '';
            target.dispatchEvent(new Event('input'));
        });
    });

    form.querySelectorAll('[data-fill-sizes]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (!variantTable || !variantTpl) return;
            let sizes = [];
            try {
                sizes = JSON.parse(btn.getAttribute('data-fill-sizes') || '[]');
            } catch {
                return;
            }
            const color = (form.querySelector('[data-preset-color]')?.value || '').trim() || 'Default';
            const first = variantTable.querySelector('tr');
            if (first && !form.hasAttribute('data-editing')) {
                const emptyColor = (first.querySelector('input[name*="[color]"]')?.value || '').trim();
                const emptySize = (first.querySelector('input[name*="[size]"]')?.value || '').trim();
                if (!emptyColor && !emptySize) first.remove();
            }
            sizes.forEach((size) => {
                const node = variantTpl.content.firstElementChild.cloneNode(true);
                node.querySelector('input[name*="[color]"]').value = color;
                node.querySelector('input[name*="[size]"]').value = size;
                variantTable.append(node);
            });
            reindex(variantTable, 'variants');
        });
    });
}

const toolbar = document.querySelector('.admin-toolbar');
toolbar?.addEventListener('change', (e) => {
    if (e.target.matches('select')) toolbar.submit();
});
