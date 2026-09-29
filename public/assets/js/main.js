/**
 * BuyFirst front-end foundation.
 * Vanilla JavaScript only — small, independent behaviours.
 */

'use strict';

/* ---------------------------------------------------------------------------
 * 0. Dark mode toggle.
 * The inline <head> script already applied any saved preference before
 * paint (see layouts/main.php and layouts/admin.php) — this just wires up
 * the button(s) to flip [data-theme] on <html> and persist the choice.
 * ------------------------------------------------------------------------- */
const themeToggles = document.querySelectorAll('[data-theme-toggle]');

if (themeToggles.length) {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)');

    const currentTheme = () =>
        document.documentElement.getAttribute('data-theme') || (prefersDark.matches ? 'dark' : 'light');

    const reflect = () => {
        const isDark = currentTheme() === 'dark';
        themeToggles.forEach((btn) => btn.setAttribute('aria-pressed', String(isDark)));
    };

    themeToggles.forEach((btn) => {
        btn.addEventListener('click', () => {
            const next = currentTheme() === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try {
                localStorage.setItem('bf-theme', next);
            } catch (e) {
                // Private browsing / storage blocked — theme still applies for this page view.
            }
            reflect();
        });
    });

    reflect();
}

/* ---------------------------------------------------------------------------
 * 1. Sticky header: transparent over the hero, solid after scrolling.
 * We only toggle a CSS class here — the visual change lives in CSS.
 * ------------------------------------------------------------------------- */
const header = document.getElementById('site-header');

if (header && header.classList.contains('site-header--transparent')) {
    const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 24);
    };
    onScroll(); // set the correct state on page load (e.g. after a refresh mid-page)
    window.addEventListener('scroll', onScroll, { passive: true });
}

/* ---------------------------------------------------------------------------
 * 2. Mobile navigation drawer.
 * ------------------------------------------------------------------------- */
const navToggle = document.querySelector('.nav-toggle');
const mobileNav = document.getElementById('mobile-nav');
const overlay = document.querySelector('.nav-overlay');

if (navToggle && mobileNav && overlay) {
    const setOpen = (open) => {
        mobileNav.classList.toggle('is-open', open);
        overlay.hidden = !open;
        navToggle.setAttribute('aria-expanded', String(open));
        navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        // Stop the page behind the drawer from scrolling while it is open.
        document.body.style.overflow = open ? 'hidden' : '';
    };

    navToggle.addEventListener('click', () =>
        setOpen(!mobileNav.classList.contains('is-open'))
    );
    overlay.addEventListener('click', () => setOpen(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });
}

/* ---------------------------------------------------------------------------
 * Destructive-action confirmations.
 * These were inline onsubmit="return confirm(…)" attributes, which our CSP
 * (script-src 'self', no 'unsafe-inline') BLOCKS — so the prompt never
 * appeared and the action went ahead unconfirmed: the bag emptied, an order
 * cancelled, a payment refunded. Hashes cannot whitelist event handlers
 * either, so the prompt has to come from a real script. Mark the form with
 * data-confirm="…" and we ask here.
 *
 * Capture phase on purpose: a declined prompt stops the event before any
 * later submit handler (the cart drawer's fetch, the checkout double-submit
 * lock) can act on it.
 * ------------------------------------------------------------------------- */
document.addEventListener(
    'submit',
    (e) => {
        const message = e.target?.dataset?.confirm;
        if (message && !window.confirm(message)) {
            e.preventDefault();
            e.stopPropagation();
        }
    },
    true
);

/* ---------------------------------------------------------------------------
 * Cart toast: the "Added to your bag" confirmation, unmissable and
 * self-dismissing. On a plain form submit (phones — see the PDP handler
 * below) the server renders one straight into the page (layouts/main.php).
 * The desktop drawer flow never reloads the page, so showCartToast() builds
 * the same markup on the fly instead.
 * ------------------------------------------------------------------------- */
const CART_TOAST_MS = 5000; // long enough to read either message, short enough to get out of the way

const wireCartToastDismiss = (toast) => {
    const dismiss = () => {
        toast.classList.add('is-leaving');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    };

    toast.querySelector('[data-flash-close]')?.addEventListener('click', dismiss);

    // Auto-dismiss on both laptop and phone. Hovering (laptop only) pauses
    // the clock instead of cancelling it, so it still leaves on its own once
    // the shopper moves away — it just never vanishes mid-read.
    let timer = setTimeout(dismiss, CART_TOAST_MS);
    toast.addEventListener('mouseenter', () => clearTimeout(timer));
    toast.addEventListener('mouseleave', () => {
        timer = setTimeout(dismiss, CART_TOAST_MS);
    });
};

const showCartToast = (message) => {
    // One at a time — a second add-to-bag replaces rather than stacks.
    document.querySelector('[data-flash].flash--cart')?.remove();

    const toast = document.createElement('div');
    toast.className = 'flash flash--cart';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.setAttribute('data-flash', '');
    toast.innerHTML = `
        <div class="container flash__inner">
            <svg class="flash__icon" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <circle cx="10" cy="10" r="10" fill="currentColor" opacity="0.18" />
                <path d="M5.5 10.5l3 3 6-6.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span class="flash__message"></span>
            <button type="button" class="flash__close" data-flash-close aria-label="Dismiss">&times;</button>
        </div>
    `;
    toast.querySelector('.flash__message').textContent = message; // textContent — never trust server copy as markup

    document.body.appendChild(toast);
    wireCartToastDismiss(toast);
};

// A toast the server already rendered into this page load still needs its
// dismiss behaviour wired up.
const existingCartToast = document.querySelector('[data-flash].flash--cart');
if (existingCartToast) wireCartToastDismiss(existingCartToast);

/* ---------------------------------------------------------------------------
 * Cart drawer: the header bag icon opens an off-canvas summary of the bag
 * instead of navigating away. Quantity/remove forms inside it submit via
 * fetch() so the drawer just refreshes in place; "View Bag" still links to
 * the full /cart page for anyone who wants it (and to checkout).
 * ------------------------------------------------------------------------- */
const cartToggle = document.getElementById('cart-toggle');
const cartDrawer = document.getElementById('cart-drawer');
const cartOverlay = document.getElementById('cart-overlay');

if (cartToggle && cartDrawer && cartOverlay) {
    const cartClose = document.getElementById('cart-close');
    const cartContinue = document.getElementById('cart-continue');
    const cartView = document.getElementById('cart-view');
    const cartBody = document.getElementById('cart-drawer-body');
    const cartCount = document.getElementById('cart-count');
    const cartTitle = document.getElementById('cart-drawer-title');
    const cartFooter = document.getElementById('cart-drawer-footer');
    const cartSubtotal = document.getElementById('cart-drawer-subtotal');

    // The body's scrollbar is hidden (see CSS), so the edge fades are the
    // only cue that more of the bag is reachable. Coalesced into a frame:
    // reading scrollHeight on every scroll event would otherwise force a
    // fresh layout each time.
    let cartScrollQueued = false;
    const syncCartScrollHints = () => {
        if (!cartBody || cartScrollQueued) return;
        cartScrollQueued = true;
        requestAnimationFrame(() => {
            cartScrollQueued = false;
            const reach = cartBody.scrollHeight - cartBody.clientHeight;
            // The 1px slacks absorb sub-pixel scroll positions on zoomed
            // displays, which would otherwise leave a fade on at the end.
            cartDrawer.classList.toggle('can-scroll-up', cartBody.scrollTop > 1);
            cartDrawer.classList.toggle('can-scroll-down', cartBody.scrollTop < reach - 1);
        });
    };

    const setCartOpen = (open) => {
        cartDrawer.classList.toggle('is-open', open);
        cartDrawer.setAttribute('aria-hidden', String(!open));
        cartOverlay.hidden = !open;
        cartToggle.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';

        // Never show the cart drawer and the mobile nav drawer at once.
        if (open && mobileNav?.classList.contains('is-open')) {
            mobileNav.classList.remove('is-open');
            overlay.hidden = true;
            navToggle?.setAttribute('aria-expanded', 'false');
        }

        if (open) syncCartScrollHints();
    };

    // Patches the drawer's count/title/footer/subtotal/items from a
    // {count, subtotal, html} JSON payload — shared by the drawer's own
    // qty/remove/clear forms and by the PDP's add-to-bag handler below.
    const applyCartPayload = (payload) => {
        if (cartBody) cartBody.innerHTML = payload.html;

        if (cartCount) {
            cartCount.textContent = payload.count > 9 ? '9+' : String(payload.count);
            cartCount.hidden = payload.count === 0;
        }
        if (cartTitle) {
            cartTitle.innerHTML = payload.count > 0
                ? `Your Bag <span>(${payload.count})</span>`
                : 'Your Bag';
        }
        if (cartFooter) {
            cartFooter.hidden = payload.count === 0;
            if (cartSubtotal) cartSubtotal.textContent = payload.subtotal;
        }

        // The lines just changed, so whether the bag still overflows — and
        // where the shopper is within it — may have changed too.
        syncCartScrollHints();
    };

    cartToggle.addEventListener('click', (e) => {
        e.preventDefault(); // progressive enhancement: no-JS still lands on /cart
        setCartOpen(!cartDrawer.classList.contains('is-open'));
    });
    cartClose?.addEventListener('click', () => setCartOpen(false));
    cartContinue?.addEventListener('click', () => setCartOpen(false));
    // "View Cart" is a real link to /cart, but close the drawer before
    // following it: otherwise a Back-navigation restores this page from the
    // bfcache with the drawer still open and body scroll still locked.
    cartView?.addEventListener('click', () => setCartOpen(false));
    cartOverlay.addEventListener('click', () => setCartOpen(false));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setCartOpen(false);
    });

    cartBody?.addEventListener('scroll', syncCartScrollHints, { passive: true });
    // The drawer is width-clamped, so a resize can reflow a long product name
    // onto another line and change the scroll height. `load` catches the
    // lazy-loaded bag thumbnails settling in after the first paint.
    window.addEventListener('resize', syncCartScrollHints);
    window.addEventListener('load', syncCartScrollHints);
    syncCartScrollHints();

    // Quantity/remove forms in the body and the footer's "Cancel" form all
    // submit via fetch() so the drawer refreshes in place.
    cartDrawer.addEventListener('submit', async (e) => {
        const form = e.target;
        // "Cancel" asks for confirmation via data-confirm, which the capture
        // handler above answers. It also prevents the event, so this is a
        // backstop for any submit some other handler has already cancelled.
        if (e.defaultPrevented) return;
        e.preventDefault();

        const data = new FormData(form);
        if (e.submitter?.name) data.append(e.submitter.name, e.submitter.value);

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: data,
            });
            if (!res.ok) return;
            applyCartPayload(await res.json());
        } catch {
            // Network blip — the bag itself is still correct on /cart.
        }
    });

    // Add-to-bag on the PDP: on a phone, a plain form submit already lands
    // on /cart (see CartController::add's default redirect). On a laptop or
    // bigger screen, intercept it instead and open the drawer in place.
    const pdpForm = document.querySelector('.pdp__form');
    pdpForm?.addEventListener('submit', async (e) => {
        if (window.matchMedia('(max-width: 920px)').matches) return;
        e.preventDefault();

        try {
            const res = await fetch(pdpForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(pdpForm),
            });
            const payload = await res.json();
            if (!res.ok || !payload.ok) {
                pdpForm.submit(); // fall back to the classic flash + redirect
                return;
            }

            applyCartPayload(payload);
            setCartOpen(true);
            if (payload.toast) showCartToast(payload.toast);
        } catch {
            pdpForm.submit();
        }
    });
}

/* ---------------------------------------------------------------------------
 * Hero carousel: rotate campaign slides every 8 seconds.
 * Pauses while hovered (so people can read) and skips auto-rotation
 * entirely for users who prefer reduced motion.
 * ------------------------------------------------------------------------- */
const carousel = document.querySelector('[data-carousel]');

if (carousel) {
    const slides = carousel.querySelectorAll('.hero-slide');
    const dots = carousel.querySelectorAll('.hero-carousel__dot');
    let current = 0;
    let timer = null;

    const show = (index) => {
        slides[current].classList.remove('is-active');
        dots[current]?.classList.remove('is-active');
        current = index;
        slides[current].classList.add('is-active');
        dots[current]?.classList.add('is-active');
    };

    const next = () => show((current + 1) % slides.length);

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            show(Number(dot.dataset.slide));
            restart(); // manual choice resets the 8s clock
        });
    });

    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const start = () => {
        if (!reduced && slides.length > 1) timer = setInterval(next, 8000);
    };
    const stop = () => clearInterval(timer);
    const restart = () => { stop(); start(); };

    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    start();
}

/* ---------------------------------------------------------------------------
 * Catalog filters: auto-submit the GET form whenever a filter changes,
 * so the URL always reflects the current view. The Apply button remains
 * as the no-JavaScript fallback — we just hide it when JS is running.
 * ------------------------------------------------------------------------- */
const filterForm = document.getElementById('filter-form');

if (filterForm) {
    filterForm.querySelector('.filters__apply')?.style.setProperty('display', 'none');

    filterForm.addEventListener('change', (e) => {
        // Only react to filter inputs, not (future) unrelated controls.
        if (e.target.matches('input, select')) filterForm.submit();
    });

    // Mobile: show/hide the filter panel.
    const toggle = filterForm.querySelector('.filters__toggle');
    const groups = document.getElementById('filter-groups');
    toggle?.addEventListener('click', () => {
        const open = groups.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(open));
    });
}

/* ---------------------------------------------------------------------------
 * Bag page: quantity <select> submits its own form when changed.
 * The <noscript> Update button covers browsers without JavaScript.
 * ------------------------------------------------------------------------- */
document.querySelectorAll('[data-autosubmit]').forEach((el) => {
    el.addEventListener('change', () => el.form.submit());
});

/* Checkout: hide the typed-address fields when a saved address is chosen.
 * Disabled inputs are skipped by HTML5 validation, so a hidden required
 * field cannot block Place Order. */
const addressFields = document.getElementById('address-fields');
if (addressFields) {
    const sync = () => {
        const saved = document.querySelector('[data-toggle-address="saved"]:checked');
        const hide = !!saved;
        addressFields.hidden = hide;
        addressFields.querySelectorAll('input, select').forEach((el) => {
            el.disabled = hide;
        });
    };
    document.querySelectorAll('[data-toggle-address]').forEach((radio) => {
        radio.addEventListener('change', sync);
    });
    sync();
}

/* Checkout: swap card fields for bank transfer details depending on the
 * chosen payment method. Hidden card inputs are disabled so their
 * `required` attribute cannot block submitting a bank transfer order. */
const payCardFields = document.getElementById('pay-card-fields');
const payBankFields = document.getElementById('pay-bank-fields');
if (payCardFields && payBankFields) {
    const syncPayment = () => {
        const bank = document.querySelector('[data-toggle-payment="bank_transfer"]:checked');
        payCardFields.hidden = !!bank;
        payBankFields.hidden = !bank;
        payCardFields.querySelectorAll('input').forEach((el) => {
            el.disabled = !!bank;
        });
    };
    document.querySelectorAll('[data-toggle-payment]').forEach((radio) => {
        radio.addEventListener('change', syncPayment);
    });
    syncPayment();
}

/* ---------------------------------------------------------------------------
 * PDP gallery: clicking a thumbnail swaps the main image.
 * ------------------------------------------------------------------------- */
const mainImg = document.getElementById('pdp-main-img');

if (mainImg) {
    const thumbs = document.querySelectorAll('.pdp__thumb');
    thumbs.forEach((thumb) => {
        thumb.addEventListener('click', () => {
            mainImg.src = thumb.dataset.src;
            mainImg.alt = thumb.dataset.alt;
            thumbs.forEach((t) => t.classList.remove('is-active'));
            thumb.classList.add('is-active');
        });
    });
}

/* ---------------------------------------------------------------------------
 * Product card thumbnails: hovering (or focusing) a thumbnail swaps the
 * card's main image. Leaving the card restores the original shot.
 * ------------------------------------------------------------------------- */
document.querySelectorAll('.product-card__thumbs').forEach((list) => {
    const card = list.closest('.product-card');
    const mainImg = card?.querySelector('[data-card-main]');
    if (!mainImg) return;

    const thumbs = Array.from(list.querySelectorAll('.product-card__thumb'));
    const defaultSrc = mainImg.src;
    const defaultAlt = mainImg.alt;

    const swap = (thumb) => {
        mainImg.src = thumb.dataset.swapSrc;
        mainImg.alt = thumb.dataset.swapAlt;
        thumbs.forEach((t) => t.classList.toggle('is-active', t === thumb));
    };

    thumbs.forEach((thumb) => {
        thumb.addEventListener('mouseenter', () => swap(thumb));
        thumb.addEventListener('focus', () => swap(thumb));
    });

    card.addEventListener('mouseleave', () => {
        mainImg.src = defaultSrc;
        mainImg.alt = defaultAlt;
        thumbs.forEach((t, i) => t.classList.toggle('is-active', i === 0));
    });
});

/* ---------------------------------------------------------------------------
 * Dialogs (size guide): native <dialog> with showModal() gives us focus
 * trapping, Escape-to-close and a ::backdrop for free.
 * ------------------------------------------------------------------------- */
document.querySelectorAll('[data-open-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => {
        document.getElementById(btn.dataset.openDialog)?.showModal();
    });
});

document.querySelectorAll('[data-close-dialog]').forEach((btn) => {
    btn.addEventListener('click', () => btn.closest('dialog')?.close());
});

// Clicking the dimmed backdrop closes the dialog too.
document.querySelectorAll('dialog').forEach((dialog) => {
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) dialog.close();
    });
});

/* ---------------------------------------------------------------------------
 * 3. Scroll-reveal via IntersectionObserver.
 * The observer watches every .reveal element and fires our callback when
 * one crosses into the viewport — far cheaper than listening to scroll.
 * ------------------------------------------------------------------------- */
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if (!prefersReducedMotion && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target); // reveal once, then stop watching
                }
            }
        },
        { threshold: 0.15 } // fire when 15% of the element is visible
    );

    document.querySelectorAll('.reveal').forEach((el) => observer.observe(el));
} else {
    // Reduced motion or very old browser: just show everything.
    document.querySelectorAll('.reveal').forEach((el) => el.classList.add('is-visible'));
}

/* ---------------------------------------------------------------------------
 * Header search: the icon expands into a field. Typeahead is unchanged —
 * debounce keystrokes, then fetch /search/suggest.
 * ------------------------------------------------------------------------- */
const searchForm = document.querySelector('[data-header-search]');
const searchInput = document.getElementById('search-q');
const suggestBox = document.getElementById('search-suggest');

if (searchForm && searchInput && suggestBox) {
    const setSearchOpen = (open) => {
        searchForm.classList.toggle('is-open', open);
        if (open) searchInput.focus();
        else {
            suggestBox.hidden = true;
            suggestBox.replaceChildren();
            searchInput.blur();
        }
    };

    searchInput.addEventListener('focus', () => setSearchOpen(true));
    document.addEventListener('pointerdown', (e) => {
        if (!searchForm.contains(e.target)) setSearchOpen(false);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && searchForm.classList.contains('is-open')) {
            setSearchOpen(false);
        }
    });

    let suggestTimer = 0;
    searchInput.addEventListener('input', () => {
        window.clearTimeout(suggestTimer);
        const q = searchInput.value.trim();
        if (q.length < 2) {
            suggestBox.hidden = true;
            suggestBox.replaceChildren();
            return;
        }

        suggestTimer = window.setTimeout(async () => {
            try {
                const res = await fetch('/search/suggest?q=' + encodeURIComponent(q));
                if (!res.ok) return;
                const data = await res.json();
                suggestBox.replaceChildren();

                if (!data.results?.length) {
                    const empty = document.createElement('p');
                    empty.className = 'search-suggest__empty';
                    empty.textContent = 'No matches — press Enter to search the full page.';
                    suggestBox.append(empty);
                    suggestBox.hidden = false;
                    return;
                }

                data.results.forEach((item) => {
                    const a = document.createElement('a');
                    a.href = item.url;

                    const img = document.createElement('img');
                    img.src = item.image || '';
                    img.alt = '';
                    img.width = 48;
                    img.height = 48;

                    const text = document.createElement('span');
                    const name = document.createElement('strong');
                    name.textContent = item.name;
                    const price = document.createElement('span');
                    price.textContent = item.price;
                    text.append(name, price);

                    a.append(img, text);
                    suggestBox.append(a);
                });
                suggestBox.hidden = false;
            } catch {
                // Network blip — the full /search page still works.
            }
        }, 280);
    });
}

/* ---------------------------------------------------------------------------
 * Custom select menus.
 * Native <option> popups are drawn by the OS — CSS cannot restyle the
 * blue Windows highlight. We keep the real <select> for form submit and
 * no-JS, then paint our own listbox over the top.
 * ------------------------------------------------------------------------- */
const closeSelects = (except) => {
    document.querySelectorAll('.bf-select.is-open').forEach((wrap) => {
        if (wrap === except) return;
        wrap.classList.remove('is-open', 'bf-select--up');
        const list = wrap.querySelector('.bf-select__list');
        const btn = wrap.querySelector('.bf-select__btn');
        if (list) list.hidden = true;
        if (btn) btn.setAttribute('aria-expanded', 'false');
    });
};

const enhanceSelect = (select) => {
    if (select.dataset.enhanced === '1' || select.multiple) return;
    select.dataset.enhanced = '1';

    const wrap = document.createElement('div');
    wrap.className = 'bf-select';
    select.before(wrap);
    wrap.append(select);
    select.tabIndex = -1;
    select.setAttribute('aria-hidden', 'true');

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'bf-select__btn';
    btn.setAttribute('aria-haspopup', 'listbox');
    btn.setAttribute('aria-expanded', 'false');
    if (select.id) btn.id = select.id + '-btn';

    const list = document.createElement('ul');
    list.className = 'bf-select__list';
    list.hidden = true;
    list.setAttribute('role', 'listbox');
    list.id = 'bf-sel-' + Math.random().toString(36).slice(2, 9);
    btn.setAttribute('aria-controls', list.id);

    wrap.append(btn, list);

    let activeIndex = Math.max(0, select.selectedIndex);

    const currentLabel = () => {
        const opt = select.selectedOptions[0];
        return opt ? opt.textContent.trim() : '';
    };

    const paint = () => {
        btn.textContent = currentLabel();
        btn.disabled = select.disabled;
        wrap.classList.toggle('is-disabled', select.disabled);
        list.replaceChildren();
        Array.from(select.options).forEach((opt, i) => {
            const li = document.createElement('li');
            li.className = 'bf-select__option';
            li.setAttribute('role', 'option');
            li.dataset.index = String(i);
            li.textContent = opt.textContent.trim();
            if (opt.disabled) {
                li.classList.add('is-disabled');
                li.setAttribute('aria-disabled', 'true');
            }
            if (i === select.selectedIndex) {
                li.classList.add('is-selected');
                li.setAttribute('aria-selected', 'true');
            }
            list.append(li);
        });
    };

    const setActive = (index) => {
        const items = list.querySelectorAll('.bf-select__option');
        const next = Math.max(0, Math.min(items.length - 1, index));
        items.forEach((el) => el.classList.remove('is-active'));
        const item = items[next];
        if (item) {
            item.classList.add('is-active');
            item.scrollIntoView({ block: 'nearest' });
            activeIndex = next;
        }
    };

    const close = () => closeSelects();

    const open = () => {
        if (select.disabled) return;
        paint();
        closeSelects(wrap);
        list.hidden = false;
        wrap.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        wrap.classList.remove('bf-select--up');
        const rect = list.getBoundingClientRect();
        if (rect.bottom > window.innerHeight - 8 && rect.top > rect.height + 8) {
            wrap.classList.add('bf-select--up');
        }
        setActive(Math.max(0, select.selectedIndex));
    };

    const choose = (index) => {
        const opt = select.options[index];
        if (!opt || opt.disabled) return;
        select.selectedIndex = index;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        paint();
        close();
        btn.focus();
    };

    btn.addEventListener('click', (e) => {
        e.preventDefault();
        if (list.hidden) open();
        else close();
    });

    list.addEventListener('click', (e) => {
        const li = e.target.closest('.bf-select__option');
        if (!li || li.getAttribute('aria-disabled') === 'true') return;
        choose(Number(li.dataset.index));
    });

    let typeBuf = '';
    let typeTimer = 0;

    btn.addEventListener('keydown', (e) => {
        const openNow = !list.hidden;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            if (!openNow) open();
            else setActive(activeIndex + (e.key === 'ArrowDown' ? 1 : -1));
        } else if ((e.key === 'Enter' || e.key === ' ') && openNow) {
            e.preventDefault();
            choose(activeIndex);
        } else if (e.key === 'Escape') {
            close();
        } else if (e.key === 'Home' && openNow) {
            e.preventDefault();
            setActive(0);
        } else if (e.key === 'End' && openNow) {
            e.preventDefault();
            setActive(select.options.length - 1);
        } else if (e.key.length === 1 && !e.ctrlKey && !e.metaKey && !e.altKey) {
            typeBuf += e.key.toLowerCase();
            window.clearTimeout(typeTimer);
            typeTimer = window.setTimeout(() => { typeBuf = ''; }, 500);
            const idx = Array.from(select.options).findIndex((o) =>
                o.textContent.trim().toLowerCase().startsWith(typeBuf)
            );
            if (idx < 0) return;
            if (openNow) setActive(idx);
            else {
                select.selectedIndex = idx;
                paint();
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    });

    const label = select.closest('label');
    if (label) {
        label.addEventListener('click', (e) => {
            e.preventDefault();
            if (e.target.closest('.bf-select__btn, .bf-select__list')) return;
            if (!select.disabled) open();
        });
    }

    select.addEventListener('change', () => {
        btn.textContent = currentLabel();
    });

    new MutationObserver(paint).observe(select, {
        attributes: true,
        attributeFilter: ['disabled'],
    });

    paint();
};

document.querySelectorAll('select').forEach(enhanceSelect);

document.addEventListener('pointerdown', (e) => {
    if (!e.target.closest('.bf-select')) closeSelects();
});

/* ---------------------------------------------------------------------------
 * Checkout: format card fields as the shopper types, and lock Place Order
 * after the first submit so a double-click cannot fire twice.
 * ------------------------------------------------------------------------- */
const cardNumber = document.getElementById('card_number');
if (cardNumber) {
    cardNumber.addEventListener('input', () => {
        const digits = cardNumber.value.replace(/\D/g, '').slice(0, 16);
        cardNumber.value = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim();
    });
}

const cardExp = document.getElementById('card_exp');
if (cardExp) {
    cardExp.addEventListener('input', () => {
        let v = cardExp.value.replace(/\D/g, '').slice(0, 4);
        if (v.length >= 3) v = v.slice(0, 2) + ' / ' + v.slice(2);
        cardExp.value = v;
    });
}

const checkoutForm = document.getElementById('checkout-form');
if (checkoutForm) {
    checkoutForm.addEventListener('submit', () => {
        document.querySelectorAll('[type="submit"][form="checkout-form"], #checkout-form [type="submit"]')
            .forEach((btn) => {
                btn.disabled = true;
                btn.textContent = 'Placing order…';
            });
    });
}

/* ---------------------------------------------------------------------------
 * Login carousel: auto-rotating product imagery, with dots/arrows and
 * pause-on-hover/focus. Advances every 4.5s unless the visitor prefers
 * reduced motion, in which case it stays put and waits for manual input.
 * ------------------------------------------------------------------------- */
document.querySelectorAll('[data-carousel]').forEach((root) => {
    const slides = Array.from(root.querySelectorAll('.carousel__slide'));
    const dots = Array.from(root.querySelectorAll('.carousel__dot'));
    const prevBtn = root.querySelector('[data-carousel-prev]');
    const nextBtn = root.querySelector('[data-carousel-next]');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = slides.findIndex((slide) => slide.classList.contains('is-active'));
    if (index < 0) index = 0;
    let timer = null;

    const show = (next) => {
        const prevIndex = index;
        index = (next + slides.length) % slides.length;

        if (prevIndex !== index && !reduceMotion) {
            const leavingSlide = slides[prevIndex];
            leavingSlide.classList.add('is-leaving');
            window.setTimeout(() => leavingSlide.classList.remove('is-leaving'), 1850);
        }

        slides.forEach((slide, i) => {
            const active = i === index;
            slide.classList.toggle('is-active', active);
            if (active) slide.classList.remove('is-leaving');
        });
        dots.forEach((dot, i) => {
            dot.classList.toggle('is-active', i === index);
            dot.setAttribute('aria-selected', String(i === index));
        });
    };

    const start = () => {
        if (reduceMotion || slides.length < 2) return;
        stop();
        timer = window.setInterval(() => show(index + 1), 4500);
    };
    const stop = () => {
        if (timer) window.clearInterval(timer);
        timer = null;
    };

    prevBtn?.addEventListener('click', () => { show(index - 1); start(); });
    nextBtn?.addEventListener('click', () => { show(index + 1); start(); });
    dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); start(); }));

    root.addEventListener('mouseenter', stop);
    root.addEventListener('mouseleave', start);
    root.addEventListener('focusin', stop);
    root.addEventListener('focusout', start);

    start();
});

/* ---------------------------------------------------------------------------
 * Password visibility toggle (login/register).
 * ------------------------------------------------------------------------- */
document.querySelectorAll('[data-password-toggle]').forEach((btn) => {
    const input = document.getElementById(btn.getAttribute('data-password-toggle'));
    if (!input) return;

    btn.addEventListener('click', () => {
        const shown = input.type === 'text';
        input.type = shown ? 'password' : 'text';
        btn.textContent = shown ? 'Show' : 'Hide';
        btn.setAttribute('aria-pressed', String(!shown));
    });
});
