# BuyFirst

A PHP + MySQL athletic storefront. Built as a teaching project: one front controller, a small router, models that talk to MySQL through prepared statements, and views that never print raw user input.

**Gear up. Move first.**

---

## What you need

- Windows, with [XAMPP](https://www.apachefriends.org/) (PHP 8.2+ and MariaDB). This repo expects PHP at `C:\xampp\php\php.exe` and MySQL at `C:\xampp\mysql\bin\`.
- A browser.
- No Composer, no Node, no React.

Product photos are licensed Unsplash athletic imagery. Product names are invented — not Nike, Jordan, or Adidas lines.

---

## Run it locally

### 1. Start the project database

BuyFirst does **not** use XAMPP’s default MySQL on port 3306. That copy lived on a full C: drive and crashed during import. The app uses a **fresh data directory** at `storage/mysql-data/` on port **3307**.

After a reboot, start it:

```powershell
powershell -ExecutionPolicy Bypass -File scripts\start-mysql.ps1
```

Or by hand:

```powershell
Start-Process -FilePath "C:\xampp\mysql\bin\mysqld.exe" `
  -ArgumentList "--defaults-file=`"$PWD\storage\mysql-data\my.ini`"","--console" `
  -WindowStyle Hidden
```

Check it:

```powershell
& C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root -e "SELECT VERSION();"
```

### 2. Configure `.env`

```powershell
copy .env.example .env
```

The committed example already points at port 3307. `.env` is git-ignored — never commit real secrets.

Set `APP_DEBUG=false` on anything that is not your laptop. Debug mode prints exception text on the 500 page.

### 3. Import the database (first machine only)

If `storage/mysql-data` already has the `buyfirst` database (this laptop does), skip this. On a new machine:

```powershell
& C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root -e "CREATE DATABASE IF NOT EXISTS buyfirst CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root buyfirst < database\schema.sql"
cmd /c "C:\xampp\mysql\bin\mysql.exe -h 127.0.0.1 -P 3307 -u root buyfirst < database\seed.sql"
& C:\xampp\php\php.exe database\seed-pages.php
```

`schema.sql` is the tables. `seed.sql` is products, users, orders, reviews. `seed-pages.php` loads the legal / help HTML.

### 4. Start the PHP server

The web root is **`public/`**. That keeps `.env`, `app/`, and `storage/` off the internet.

```powershell
& C:\xampp\php\php.exe -S localhost:8000 -t public public/router.php
```

Open [http://localhost:8000](http://localhost:8000).

If you use Apache instead, point the virtual host **DocumentRoot** at `public/`. There is a project-root `.htaccess` that rewrites into `public/` if someone points Apache at the folder by mistake.

---

## Seed logins

| Role     | Email                    | Password       |
| -------- | ------------------------ | -------------- |
| Admin    | `admin@buyfirst.test`    | `Admin123!`    |
| Customer | `customer@buyfirst.test` | `Customer123!` |
| Customer | `maya.reid@example.com`  | `Customer123!` |

Admin lives at `/admin`. A customer who types that URL gets **403**, not a dashboard.

Demo promo codes: `WELCOME10` (10% off), `FREESHIP` (₦50,000 minimum), `FIRSTKIT` (₦15,000 off, ₦100,000 minimum).

---

## Project map

```
public/            web root — the only folder the browser may read
  index.php        front controller: every request starts here
  router.php       PHP built-in server helper (serves CSS, else index.php)
  assets/          CSS and JS
app/
  Controllers/     decide what to do with a request
  Models/          the only files that talk to MySQL
  Views/           HTML. They echo with e() or safe_html()
config/            turns .env into one config() array
database/          schema.sql, seed.sql, seed-pages.php
lib/               router, PDO wrapper, CSRF, auth, SMTP client, mailer, security
storage/           mail files, rate-limit files, local MySQL data (git-ignored)
scripts/           start-mysql.ps1, test-mail.php
```

**MVC in one sentence:** the router picks a controller, the controller asks a model for data, the view prints HTML.

---

## How a request is protected

1. **Web root is `public/`.** `.env` and PHP sources are not URL-reachable.
2. **Prepared statements** for every query. User text never lands in SQL.
3. **`e()`** on every dynamic HTML value. CMS copy goes through `safe_html()`.
4. **CSRF token** on every non-GET request (`lib/csrf.php`).
5. **Passwords** hashed with `password_hash()`. Reset tokens stored as SHA-256, not raw.
6. **IDOR:** account queries always include `AND user_id = ?`. Guessing `/account/orders/BF-SOMEONE-ELSE` is a 404.
7. **Admin ≠ logged in.** `require_admin()` checks `role_id === 1` (cast to int — PDO may return `"1"`).
8. **Money is recomputed on the server.** Posted totals are ignored.
9. **Login lockout** after 5 failures (15 minutes), plus an IP cap (20 / 15 min). Forgot-password, register, and contact are also rate-limited.
10. **Security headers** (CSP, no framing, no MIME sniffing). Session cookie is HttpOnly, SameSite=Lax, and `Secure` when the site is on HTTPS.

---

## Email

Two drivers, same `Mailer::send()` calls. Switch in `.env`:

| `MAIL_DRIVER`   | What happens                                                                |
| --------------- | --------------------------------------------------------------------------- |
| `log` (default) | HTML files in `storage/mail/`. Open the newest `.html` — that is the inbox. |
| `smtp`          | Real delivery through `MAIL_HOST`.                                          |

Password reset, order confirmation, “order shipped”, and the contact form all go through this.

### Send for real (SMTP)

1. Create a mailbox (Hostinger Email, Gmail, Mailtrap, …).
2. Put the mailbox address in `MAIL_FROM` **and** `MAIL_USERNAME`. Most servers reject a From that is not the authenticated account.
3. Fill the SMTP block:

```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=orders@yourdomain.com
MAIL_PASSWORD=your-mailbox-password
MAIL_FROM=orders@yourdomain.com
MAIL_FROM_NAME=BuyFirst
MAIL_CONTACT=orders@yourdomain.com
```

Port **465** + `ssl` is implicit TLS (the connection is encrypted from the first byte). Port **587** + `tls` is STARTTLS (plain connect, then upgrade). Both are fine; Hostinger supports either.

Gmail: use an [App Password](https://support.google.com/accounts/answer/185833), not your normal login. `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_ENCRYPTION=tls`.

4. Prove it:

```powershell
& C:\xampp\php\php.exe scripts\test-mail.php you@example.com
```

With `APP_DEBUG=true`, the SMTP conversation is appended to `storage/mail/smtp.log` (passwords redacted).

---

## PHP concepts this project taught

| Concept                                | Where you met it                          |
| -------------------------------------- | ----------------------------------------- |
| Front controller                       | `public/index.php`                        |
| Router + `{placeholders}`              | `lib/Router.php`                          |
| Layout vs partial vs view              | `layouts/main.php`, `partials/header.php` |
| Design tokens                          | `public/assets/css/tokens.css`            |
| `.env` and `config()`                  | `lib/helpers.php`, `config/config.php`    |
| Sessions                               | cart, login, flash messages, CSRF         |
| PDO + prepared statements              | `lib/Database.php`, every model           |
| Primary / foreign keys, JOINs          | `database/schema.sql`, catalog + reviews  |
| `password_hash` / `password_verify`    | `app/Models/User.php`                     |
| CSRF                                   | `lib/csrf.php`                            |
| XSS (`e()`, `safe_html()`)             | helpers + every view                      |
| IDOR                                   | `AccountController`, `Cart::updateQty`    |
| Transactions + `FOR UPDATE`            | `Order::place`                            |
| Auth vs authorisation                  | `require_login()` vs `require_admin()`    |
| POST-redirect-GET + flash              | every form                                |
| LIKE wildcards vs injection            | `Product::search`                         |
| Cookie consent (essential vs optional) | `lib/helpers.php`, cookie banner          |
| SMTP vs writing files                  | `lib/smtp.php`, `lib/mailer.php`          |

---

## Common mistakes this codebase is built to avoid

- Mixing SQL into HTML (models own queries; views own markup).
- Concatenating `$_GET` into SQL (use `?` placeholders; whitelist `ORDER BY`).
- Echoing `$_POST` without `e()`.
- Storing plain passwords.
- Changing state with a GET link (logout and deletes are POST).
- Trusting a hidden `<input name="total">`.
- Pointing the web server at the project root so `.env` is downloadable.

---

## Licence / content

Code is yours to learn from. Product photography: [Unsplash](https://unsplash.com) license. BuyFirst is a fictional Nigerian store (BuyFirst Ltd) for this project — the legal pages are written as if it were real, not as `[Company]` templates.
#   B u y - F i r s t  
 