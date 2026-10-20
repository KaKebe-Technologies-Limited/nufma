# Deployment — Hostinger shared hosting (PHP + MySQL)

The existing site is a custom PHP application, so it is deployed by uploading files — no build step, Node server or framework is required. Verify the PHP version (8.0+) in hPanel → *Advanced → PHP Configuration*.

> ⚠️ **Never overwrite the live site without a verified backup.** Do not claim deployment has succeeded until the checklist at the end passes on the live domain.

## 1. Before you start
1. hPanel → **Files → Backups** (or File Manager → select `public_html` → Compress → download the .zip).
2. hPanel → **Databases → phpMyAdmin** → select the live database → **Export** (Quick, SQL) and keep the file.
3. Check the live `includes/db.config.php` (if present) — it holds live credentials. **Leave it untouched.** If it does not exist, `includes/db.php` uses the live credentials it already contains.

## 2. Upload
Option A — **Git**: hPanel → *Websites → Git* → connect the repository (branch `main`) → deploy to `public_html`.
Option B — **File Manager / FTP**: upload the project zip into `public_html`, extract and *overwrite*.
Do **not** upload `database/`, `scripts/` or `docs/` if you prefer a lean server (they are blocked from web access anyway).

## 3. First load
Open the live site once. Missing tables/columns are created automatically (see README §4) and the new default content (programmes, hero images, contact details) is seeded. Existing posts, editions, members and settings are **not** modified, except: news categories are mapped to the brief's category list, the 2027 edition placeholder text is neutralised and the 2025 date is aligned to 26 April 2025 (one-time, only if the old text is unchanged).

## 4. Configure
Sign in at `/admin/login.php` and:
1. **Admin Users** → add your own admin, then delete the shared/default one. Use a unique strong password.
2. **Membership → Membership settings** → fee, payment instructions, reg-number format, president's name & signature, **"Send from" e-mail on your domain**.
3. **Site Settings** → check contact details, social links; leave approval switches off until approved.
4. **Page Content / Programmes / Leadership / Partners** → complete items from `docs/CONTENT-HANDOVER.md`.

## 5. Domain, HTTPS and SEO
* hPanel → **Security → SSL** → install/force HTTPS.
* Edit `robots.txt` → set the correct domain on the `Sitemap:` line.
* **Google Search Console**: add the domain property → verify (DNS TXT) → submit `https://YOUR-DOMAIN/sitemap.php`.
* **Analytics** (optional): none is installed. If you add one, choose a privacy-friendly tool, add it only after cookie consent where required, and update the Cookie Policy (Page Content → Policies).

## 6. Post-deployment test checklist
- [ ] Home, About, Awards (+ each edition), Programmes (+ detail), SACCO, Network, News (+ an article), Resources, Gallery, Partner With Us, Contact, Membership, each Policy open without errors
- [ ] A non-existent URL shows the custom 404 page
- [ ] Every form shows a success message and the submission appears in admin (Enquiries / Membership)
- [ ] Notification e-mail reaches the NUFA inbox; membership approval e-mail reaches a test address
- [ ] Mobile menu opens/closes; keyboard Tab shows focus outlines
- [ ] `https://YOUR-DOMAIN/includes/db.php`, `/storage/members/` and `/database/schema.sql` return **403/404**
- [ ] `https://YOUR-DOMAIN/sitemap.php` lists pages with the live domain
- [ ] Delete test submissions/members

## 7. Backup & maintenance
* Monthly: database export + download `assets/images/uploads/`, `assets/resources/` and `storage/members/`.
* Keep the admin accounts list short; review *Dashboard → Recent sign-ins*.
* Data retention: delete enquiries and rejected applications once handled (admin → Enquiries / Membership → Delete).

## 8. Rollback
1. Restore the file backup from step 1 (extract over `public_html`).
2. If the database must be reverted, import the exported SQL file in phpMyAdmin (the new tables are additive and harmless if left in place).
3. Clear any CDN/browser cache and re-run the checklist.
