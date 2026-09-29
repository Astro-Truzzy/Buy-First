-- ============================================================================
-- BuyFirst database schema
-- Import order matters: tables are created before the tables that reference
-- them via foreign keys (you can't point at a table that doesn't exist yet).
-- Every table uses InnoDB (the engine that supports foreign keys) and
-- utf8mb4 (full Unicode, including emoji in reviews).
-- ============================================================================

SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- roles: the few kinds of user that exist (admin, customer).
-- A separate table instead of a text column so a typo like "amdin" is
-- impossible — users can only point at roles that actually exist.
-- ----------------------------------------------------------------------------
CREATE TABLE roles (
    id   TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- users: everyone with an account. password_hash stores the bcrypt hash from
-- PHP's password_hash() — NEVER the actual password. failed_logins and
-- locked_until power simple login rate-limiting.
-- ----------------------------------------------------------------------------
CREATE TABLE users (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id           TINYINT UNSIGNED NOT NULL DEFAULT 2,
    first_name        VARCHAR(60)  NOT NULL,
    last_name         VARCHAR(60)  NOT NULL,
    email             VARCHAR(255) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    is_member         TINYINT(1)   NOT NULL DEFAULT 0,  -- BuyFirst Club member
    newsletter_opt_in TINYINT(1)   NOT NULL DEFAULT 0,
    email_verified_at DATETIME     NULL,
    failed_logins     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until      DATETIME     NULL,
    created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- addresses: saved delivery/billing addresses. One user can have many
-- (home, work...) — a classic one-to-many via user_id.
-- ON DELETE CASCADE: if a user deletes their account, their addresses go too.
-- ----------------------------------------------------------------------------
CREATE TABLE addresses (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED NOT NULL,
    label               VARCHAR(40)  NOT NULL DEFAULT 'Home',
    first_name          VARCHAR(60)  NOT NULL,
    last_name           VARCHAR(60)  NOT NULL,
    line1               VARCHAR(120) NOT NULL,
    line2               VARCHAR(120) NULL,
    city                VARCHAR(80)  NOT NULL,
    postcode            VARCHAR(12)  NOT NULL,
    country             CHAR(2)      NOT NULL DEFAULT 'NG',  -- ISO code; shop ships Nigeria only
    phone               VARCHAR(20)  NULL,
    is_default_shipping TINYINT(1)   NOT NULL DEFAULT 0,
    is_default_billing  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- categories: Shoes / Clothing / Accessories. parent_id allows a tree
-- (subcategories) later; we keep it flat for now. slug is the URL-safe name
-- used in addresses like /category/shoes — unique and indexed for fast lookup.
-- ----------------------------------------------------------------------------
CREATE TABLE categories (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parent_id   INT UNSIGNED NULL,
    name        VARCHAR(80)  NOT NULL,
    slug        VARCHAR(100) NOT NULL UNIQUE,
    description TEXT         NULL,
    sort_order  INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- products: one row per product PAGE (the "Velocity Runner"). Sizes, colors
-- and stock live in product_variants. price is in NGN (naira); sale_price, when set,
-- is what the customer pays (we show both + a % off badge).
-- gender and sport are simple filters used all over the storefront.
-- ----------------------------------------------------------------------------
CREATE TABLE products (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name        VARCHAR(120) NOT NULL,
    slug        VARCHAR(140) NOT NULL UNIQUE,
    description TEXT         NOT NULL,
    details     TEXT         NULL,     -- bullet points: materials, care, fit
    gender      ENUM('men','women','kids','unisex') NOT NULL DEFAULT 'unisex',
    sport       ENUM('running','training','basketball','football','lifestyle') NOT NULL DEFAULT 'lifestyle',
    price       DECIMAL(10,2) NOT NULL,          -- DECIMAL for money, never FLOAT
    sale_price  DECIMAL(10,2) NULL,
    badge       ENUM('just-in','best-seller','member-exclusive','limited-drop') NULL,
    is_featured TINYINT(1)   NOT NULL DEFAULT 0, -- shown in home page rails
    is_active   TINYINT(1)   NOT NULL DEFAULT 1, -- soft "off the shelf" switch
    -- Optional override used ONLY by the homepage Trending tile (see
    -- Product::featured()). NULL falls back to the normal product_images
    -- row, so this never touches the product page, cart, or anywhere else
    -- the product's real photography appears.
    trending_image     VARCHAR(500) NULL,
    trending_image_alt VARCHAR(200) NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_products_gender (gender),
    INDEX idx_products_sport (sport)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- product_images: many images per product. color ties an image to a colorway
-- so the gallery swaps when the shopper picks a color. sort_order controls
-- display order; the lowest is the main card image, the second the hover swap.
-- ----------------------------------------------------------------------------
CREATE TABLE product_images (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id INT UNSIGNED NOT NULL,
    url        VARCHAR(500) NOT NULL,
    alt        VARCHAR(200) NOT NULL,   -- describes the image for screen readers
    color      VARCHAR(40)  NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- product_variants: the actual BUYABLE thing — "Velocity Runner, Black, EU 43".
-- Each has its own SKU (unique inventory code) and stock count. stock = 0
-- shows as "sold out" on the size picker. price_override allows a variant to
-- cost more (rarely used, e.g. big kids' sizes).
-- ----------------------------------------------------------------------------
CREATE TABLE product_variants (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id     INT UNSIGNED NOT NULL,
    sku            VARCHAR(40)  NOT NULL UNIQUE,
    color          VARCHAR(40)  NOT NULL,
    size           VARCHAR(12)  NOT NULL,   -- "EU 43" or "M" — text fits both
    stock          INT UNSIGNED NOT NULL DEFAULT 0,
    price_override DECIMAL(10,2) NULL,
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- carts: one shopping cart per shopper. Logged-in carts have user_id; guest
-- carts are found by session_token (a random ID stored in the PHP session).
-- When a guest logs in, we merge their token cart into their user cart.
-- ----------------------------------------------------------------------------
CREATE TABLE carts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NULL,
    session_token CHAR(64)     NULL UNIQUE,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- cart_items: the lines inside a cart. UNIQUE(cart_id, variant_id) means the
-- same size/color can only appear once — adding it again bumps the quantity.
-- ----------------------------------------------------------------------------
CREATE TABLE cart_items (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cart_id    INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED NOT NULL,
    quantity   INT UNSIGNED NOT NULL DEFAULT 1,
    UNIQUE KEY uq_cart_variant (cart_id, variant_id),
    FOREIGN KEY (cart_id)    REFERENCES carts(id)            ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- wishlists: a simple "user hearts product" link table. The UNIQUE pair
-- stops the same product being hearted twice.
-- ----------------------------------------------------------------------------
CREATE TABLE wishlists (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- coupons: promo codes. type decides how value is applied — percent off,
-- fixed ₦ off, or free shipping (value ignored). min_spend and the date
-- window gate when a code works; max_uses/uses caps total redemptions.
-- ----------------------------------------------------------------------------
CREATE TABLE coupons (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code       VARCHAR(30)  NOT NULL UNIQUE,
    type       ENUM('percent','fixed','free_shipping') NOT NULL,
    value      DECIMAL(10,2) NOT NULL DEFAULT 0,
    min_spend  DECIMAL(10,2) NOT NULL DEFAULT 0,
    max_uses   INT UNSIGNED NULL,          -- NULL = unlimited
    uses       INT UNSIGNED NOT NULL DEFAULT 0,
    starts_at  DATETIME     NULL,
    expires_at DATETIME     NULL,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- orders: one row per placed order. Shipping/billing details are COPIED in
-- (not linked) so the receipt never changes if the user edits an address
-- later. idempotency_key is a random token from the checkout form — if the
-- same key arrives twice (double-click on "Place order"), the second insert
-- fails the UNIQUE check and we simply show the first order. status follows
-- the fulfilment lifecycle the admin will manage.
-- ----------------------------------------------------------------------------
CREATE TABLE orders (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_number    VARCHAR(20)  NOT NULL UNIQUE,   -- human-friendly, e.g. BF-240813-A7K2
    user_id         INT UNSIGNED NULL,              -- NULL = guest checkout
    email           VARCHAR(255) NOT NULL,
    status          ENUM('pending','paid','packed','shipped','delivered','cancelled','refunded')
                    NOT NULL DEFAULT 'pending',
    subtotal        DECIMAL(10,2) NOT NULL,
    discount        DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping_cost   DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax             DECIMAL(10,2) NOT NULL DEFAULT 0,
    total           DECIMAL(10,2) NOT NULL,
    coupon_id       INT UNSIGNED NULL,
    delivery_method VARCHAR(40)  NOT NULL DEFAULT 'standard',
    payment_method  VARCHAR(20)  NOT NULL DEFAULT 'card',  -- 'card' or 'bank_transfer'
    payment_claimed_at DATETIME NULL,  -- customer says they've sent a bank transfer; awaiting staff verification
    ship_first_name VARCHAR(60)  NOT NULL,
    ship_last_name  VARCHAR(60)  NOT NULL,
    ship_line1      VARCHAR(120) NOT NULL,
    ship_line2      VARCHAR(120) NULL,
    ship_city       VARCHAR(80)  NOT NULL,
    ship_postcode   VARCHAR(12)  NOT NULL,
    ship_country    CHAR(2)      NOT NULL DEFAULT 'NG',
    ship_phone      VARCHAR(20)  NULL,
    idempotency_key CHAR(64)     NULL UNIQUE,
    placed_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL,
    INDEX idx_orders_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- order_items: the lines on a receipt. Product name, SKU, color, size and
-- price are SNAPSHOTTED at purchase time — history must not change when the
-- catalog does. variant_id is kept as a soft link (SET NULL if the variant
-- is ever deleted) so admin can still jump to the live product.
-- ----------------------------------------------------------------------------
CREATE TABLE order_items (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id     INT UNSIGNED NOT NULL,
    variant_id   INT UNSIGNED NULL,
    product_name VARCHAR(120) NOT NULL,
    sku          VARCHAR(40)  NOT NULL,
    color        VARCHAR(40)  NOT NULL,
    size         VARCHAR(12)  NOT NULL,
    unit_price   DECIMAL(10,2) NOT NULL,
    quantity     INT UNSIGNED NOT NULL,
    line_total   DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)           ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- payments: one row per payment attempt against an order. reference holds
-- the payment provider's ID (e.g. a Stripe charge id) — we NEVER store card
-- numbers ourselves, only the provider's token/reference.
-- ----------------------------------------------------------------------------
CREATE TABLE payments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id   INT UNSIGNED NOT NULL,
    provider   VARCHAR(20)  NOT NULL DEFAULT 'card',
    amount     DECIMAL(10,2) NOT NULL,
    currency   CHAR(3)      NOT NULL DEFAULT 'NGN',
    status     ENUM('pending','succeeded','failed','refunded') NOT NULL DEFAULT 'pending',
    reference  VARCHAR(100) NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- coupon_redemptions: who used which code on which order — lets us enforce
-- "one use per customer" and audit promotions.
-- ----------------------------------------------------------------------------
CREATE TABLE coupon_redemptions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coupon_id   INT UNSIGNED NOT NULL,
    order_id    INT UNSIGNED NOT NULL,
    user_id     INT UNSIGNED NULL,
    redeemed_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id)  REFERENCES orders(id)  ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- reviews: product reviews. status starts 'pending' so admin moderates before
-- anything goes public. is_verified_purchase is set when the reviewer really
-- bought the product (we check their order history).
-- ----------------------------------------------------------------------------
CREATE TABLE reviews (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id           INT UNSIGNED NOT NULL,
    user_id              INT UNSIGNED NOT NULL,
    rating               TINYINT UNSIGNED NOT NULL,  -- 1 to 5
    title                VARCHAR(120) NOT NULL,
    body                 TEXT         NOT NULL,
    is_verified_purchase TINYINT(1)   NOT NULL DEFAULT 0,
    status               ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- password_resets: time-limited "forgot password" tokens. We store a HASH of
-- the token (same logic as passwords — a database leak must not let anyone
-- reset accounts). used_at stops a token being used twice.
-- ----------------------------------------------------------------------------
CREATE TABLE password_resets (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    token_hash CHAR(64)     NOT NULL,
    expires_at DATETIME     NOT NULL,
    used_at    DATETIME     NULL,
    created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- newsletter_subscribers: emails signed up to marketing. Kept separate from
-- users because you can subscribe without an account.
-- ----------------------------------------------------------------------------
CREATE TABLE newsletter_subscribers (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email           VARCHAR(255) NOT NULL UNIQUE,
    user_id         INT UNSIGNED NULL,
    subscribed_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME     NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- pages: CMS-style content pages (privacy policy, terms...). Storing them in
-- the database lets admin edit legal text without touching code.
-- ----------------------------------------------------------------------------
CREATE TABLE pages (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(100) NOT NULL UNIQUE,
    title      VARCHAR(160) NOT NULL,
    content    MEDIUMTEXT   NOT NULL,
    updated_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- bank_accounts: a single admin-editable row holding the account details
-- shown to customers who choose "Bank transfer" at checkout instead of card.
-- ----------------------------------------------------------------------------
CREATE TABLE bank_accounts (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_name      VARCHAR(120) NOT NULL DEFAULT '',
    account_name   VARCHAR(120) NOT NULL DEFAULT '',
    account_number VARCHAR(40)  NOT NULL DEFAULT '',
    updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO bank_accounts (id, bank_name, account_name, account_number) VALUES (1, '', '', '');

-- ----------------------------------------------------------------------------
-- campaigns: homepage hero banners, editable from admin (a mini-CMS).
-- The home page shows active campaigns ordered by sort_order.
-- ----------------------------------------------------------------------------
CREATE TABLE campaigns (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(120) NOT NULL,
    subtitle   VARCHAR(255) NULL,
    cta_text   VARCHAR(40)  NOT NULL DEFAULT 'Shop now',
    cta_url    VARCHAR(255) NOT NULL,
    image_url  VARCHAR(500) NOT NULL,
    sort_order INT          NOT NULL DEFAULT 0,
    is_active  TINYINT(1)   NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- audit_logs: a trail of who did what in admin (product edited, order status
-- changed...). Invaluable when something looks wrong and you need history.
-- ----------------------------------------------------------------------------
CREATE TABLE audit_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(60)  NOT NULL,   -- e.g. "product.update"
    entity_type VARCHAR(40)  NOT NULL,   -- e.g. "product"
    entity_id   INT UNSIGNED NULL,
    details     TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
