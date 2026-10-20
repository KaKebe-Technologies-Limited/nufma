# NUFA website — Northern Uganda Filmmakers Association

> **Our Stories. Our Culture. Our Future.**
> Public website and admin dashboard for NUFA — awards archive, programmes, membership, news, resources, partnerships and enquiries.

## 1. Technology

| Layer | Choice | Why |
|---|---|---|
| Server | PHP 8 (procedural + small helpers) | The site was already custom PHP on Hostinger shared hosting — no Node server needed |
| Database | MySQL / MariaDB (`mysqli`, prepared statements) | Available on shared hosting |
| Front end | Hand-written CSS design system (`assets/css/site.css`) + ~150 lines of vanilla JS (`assets/js/main.js`) | Fast, no build step, no framework lock-in |
| Fonts | Manrope (headings) + Inter (body) via Google Fonts | Per the brief |
| Palette | Charcoal `#111`, near-black `#080808`, warm gold `#D4AF63`, white, soft grey | Per the brief |

There is **no build step**. Upload the files and the site runs.

## 2. Folder structure

```
index.php, about.php, awards.php, award.php, programmes.php, programme.php,
sacco.php, network.php, news.php, post.php, resources.php, gallery.php,
partners.php (Partner With Us), contact.php, membership.php, card.php,
policy.php, 404.php, sitemap.php, robots.txt, .htaccess
includes/        shared code (db, header/footer, forms, settings, schema, content CMS …)
assets/css|js|images|resources   styles, scripts, photographs, uploaded resource documents
admin/           dashboard (login, members, enquiries, programmes, resources, videos, settings …)
storage/members/ PRIVATE member uploads (photos, payment proofs) — web access denied
database/        schema.sql (reference only — tables create themselves, see §4)
scripts/         one-off helper scripts (local only)
docs/            README extras: CONTENT-HANDOVER.md, DEPLOYMENT.md, image-archive/
```

## 3. Local development

1. Install XAMPP, start **Apache** and **MySQL**.
2. Put this folder at `C:\xampp\htdocs\nufma`.
3. Create the database `nufa_db` (phpMyAdmin → New) — or import `database/schema.sql`.
4. Open `http://localhost/nufma/`. Tables and default content are created on first load.
5. Admin: `http://localhost/nufma/admin/login.php`. Create the first admin with `scripts` or directly in the `admins` table (`password_hash` = PHP `password_hash()` output).

`includes/db.php` detects local vs live automatically. To override credentials on any server, copy `includes/db.config.sample.php` to `includes/db.config.php` (git-ignored). **Never commit real credentials.**

## 4. Database

All tables the application needs are created automatically by `includes/schema.php` and `includes/membership.php` the first time a page loads, and only *add* missing tables/columns — existing rows are never altered. Key tables:

`posts`, `award_editions`, `award_winners`, `programmes`, `resources`, `videos`, `gallery_images`, `hero_slides`, `team_members`, `partners`, `members`, `guilds`, `enquiries`, `member_emails`, `page_content`, `settings`, `admins`, `admin_login_log`.

## 5. Editing content (no code needed)

Sign in to `/admin/`. Everything a staff member needs is in the sidebar:

| Need to change… | Go to |
|---|---|
| Headings and paragraphs on any page, policies, SACCO FAQ | **Page Content** |
| News articles (categories, captions, credits, featured story) | **News & Stories** |
| Programme pages and status labels | **Programmes** |
| Documents and calls for submissions | **Resources** |
| Photographs / videos | **Gallery & Videos** |
| Homepage hero photographs | **Hero Images** |
| Award editions, winners, nominees, sponsors, press | **Award Editions** |
| Leadership profiles (name, role, bio, portrait) | **Leadership** |
| Partners (current vs previous supporters) | **Partners** |
| Contact details, social links, approvals, verified impact figures | **Site Settings** |
| Membership fee, reg-number format, card signature, e-mail wording, guilds | **Membership** |
| Form submissions (contact, partnership, SACCO, network) | **Enquiries** |

Clearing a Page Content field restores the approved default wording.

## 6. Forms and spam protection

Contact, Partnership, SACCO interest, Industry Network and Membership forms are all **genuinely connected**: server-side validation, CSRF token, hidden honeypot field, minimum fill time, per-IP rate limit (5/hour), consent capture. Submissions are stored in the database (admin → Enquiries / Membership) and the NUFA inbox is e-mailed using PHP `mail()`.

* Set **admin → Membership settings → Emails** "Send from" to an address on your hosting domain so e-mail is delivered reliably.
* No third-party form service or API key is required. If you later add one (e.g. reCAPTCHA, SMTP), keep its secret in `includes/db.config.php` or hosting environment variables — never in front-end code.

## 7. Security notes

* Admin: login throttling, CSRF on every form/destructive link, idle timeout, hardened session cookie, one-click password change.
* `.htaccess` denies access to `includes/`, `database/`, `scripts/`, `docs/`, `storage/` and SQL/log/env files, adds security headers and disables directory listing.
* Member photos and payment proofs are stored privately and streamed only to a signed-in admin.
* Use HTTPS in production (Hostinger offers free SSL).

## 8. Deployment, backup and rollback

See **`docs/DEPLOYMENT.md`**.

## 9. Known limitations

* E-mail relies on the host's `mail()`; if it is disabled, e-mails are logged in the admin but not delivered.
* The industry directory is a **registration-of-interest** landing page; a public directory is planned for a later phase.
* No automated analytics are installed (privacy-first). See `docs/DEPLOYMENT.md` for adding them with consent.
* Photographs are large; compress them (WebP, max ~1600 px wide) before uploading for best performance.

## 10. Outstanding content and approvals

Every placeholder and every item awaiting NUFA approval is listed in **`docs/CONTENT-HANDOVER.md`**.
