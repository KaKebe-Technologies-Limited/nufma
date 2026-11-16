# Deployment — Hostinger shared hosting (PHP + MySQL)

The existing site is a custom PHP application, so it is deployed by uploading files — no build step, Node server or framework is required. Verify the PHP version (8.0+) in hPanel → *Advanced → PHP Configuration*.

> ⚠️ **Never overwrite the live site without a verified backup.** Do not claim deployment has succeeded until the checklist at the end passes on the live domain.

## Automatic deployment from GitHub (recommended)

Every time code is pushed to the `main` branch, GitHub:

1. **Checks every PHP file for errors.** If anything is broken, the deployment stops and the live site is untouched.
2. **Uploads only the files that changed** to Hostinger over FTPS.

Things that belong to the live site are never touched: the database login (`includes/db.config.php`), the
`storage/` folder (member, learner, film and SACCO uploads), photos uploaded in the admin (`assets/images/uploads/`),
and the server's `.htaccess`.

### One-time setup (about 10 minutes)

### 1. Create an FTP account in Hostinger
hPanel → **Files → FTP Accounts** → create an account whose folder is your website's `public_html`.
Note the **FTP host name**, the **username** and the **password**.

### 2. Add three secrets in GitHub
Repository → **Settings → Secrets and variables → Actions → Secrets → New repository secret**:

| Name | Value |
|---|---|
| `FTP_SERVER` | the FTP host name from hPanel (for example `ftp.nufawards.com` or the server IP) |
| `FTP_USERNAME` | the FTP username |
| `FTP_PASSWORD` | the FTP password |

Optional **Variables** (same page, *Variables* tab) — only if the defaults do not work:

| Name | Default | Use |
|---|---|---|
| `FTP_PROTOCOL` | `ftps` | set to `ftp` if the connection is refused |
| `FTP_PORT` | `21` | change only if Hostinger shows a different port |
| `FTP_DIR` | `/public_html/` | the folder on the server that holds the site (for a domain folder, e.g. `/domains/nufawards.com/public_html/`) |

### 3. Do a dry run first
GitHub → **Actions → Deploy to Hostinger → Run workflow** → keep **Dry run** ticked → *Run workflow*.
It lists every file it would upload but changes nothing. If it connects and lists files, the setup is correct.

### 4. Run it for real
Run the workflow again with **Dry run unticked**. The first real run uploads everything and can take 15–30 minutes
(the repository holds a few hundred photos). After that, each deployment sends only what changed and takes a minute or two.

From then on, **pushing to `main` deploys automatically**.

### Day to day
- Make changes, then push to `main` (or merge a pull request into it).
- Watch **Actions** on GitHub: a green tick means the live site is updated; a red cross means nothing was uploaded and shows why.
- Open any page of the site once after a deployment that adds database features — the site updates its own database the first time a page loads.

### Notes
- `.htaccess` is not deployed so a change to the server rules cannot lock you out. Change it on the server when needed.
- The deployment keeps a small state file (`.ftp-deploy-sync-state.log`) on the server so it knows what it already uploaded. The site's `.htaccess` already blocks `*.log` files from the public. Do not delete it, or the next run will re-upload everything.
- To roll back: revert the commit on GitHub (`Revert` button on the commit/PR), push, and the previous version is deployed.
- Never commit passwords or `includes/db.config.php`; both stay out of the repository.

---

## Manual deployment (backup method)

### 1. Before you start
1. hPanel → **Files → Backups** (or File Manager → select `public_html` → Compress → download the .zip).
2. hPanel → **Databases → phpMyAdmin** → select the live database → **Export** (Quick, SQL) and keep the file.
3. Check the live `includes/db.config.php` (if present) — it holds live credentials. **Leave it untouched.** If it does not exist, `includes/db.php` uses the live credentials it already contains.

### 2. Upload
Option A — **Hostinger's built-in Git**: only works into an **empty** folder, so it is not suitable for this live site — use the automatic deployment above instead.
Option B — **File Manager / FTP**: upload the project zip into `public_html`, extract and *overwrite*.
Do **not** upload `database/`, `scripts/` or `docs/` if you prefer a lean server (they are blocked from web access anyway).

### 3. First load
Open the live site once. Missing tables/columns are created automatically (see README §4) and the new default content (programmes, hero images, contact details) is seeded. Existing posts, editions, members and settings are **not** modified, except: news categories are mapped to the brief's category list, the 2027 edition placeholder text is neutralised and the 2025 date is aligned to 26 April 2025 (one-time, only if the old text is unchanged).

### 4. Configure
Sign in at `/admin/login.php` and:
1. **Admin Users** → add your own admin, then delete the shared/default one. Use a unique strong password.
2. **Membership → Membership settings** → fee, payment instructions, reg-number format, president's name & signature, **"Send from" e-mail on your domain**.
3. **Site Settings** → check contact details, social links; leave approval switches off until approved.
4. **Page Content / Programmes / Leadership / Partners** → complete items from `docs/CONTENT-HANDOVER.md`.

### 5. Domain, HTTPS and SEO
* hPanel → **Security → SSL** → install/force HTTPS.
* Edit `robots.txt` → set the correct domain on the `Sitemap:` line.
* **Google Search Console**: add the domain property → verify (DNS TXT) → submit `https://YOUR-DOMAIN/sitemap.php`.
* **Analytics** (optional): none is installed. If you add one, choose a privacy-friendly tool, add it only after cookie consent where required, and update the Cookie Policy (Page Content → Policies).

### 6. Post-deployment test checklist
- [ ] Home, About, Awards (+ each edition), Programmes (+ detail), SACCO, Network, News (+ an article), Resources, Gallery, Partner With Us, Contact, Membership, each Policy open without errors
- [ ] A non-existent URL shows the custom 404 page
- [ ] Every form shows a success message and the submission appears in admin (Enquiries / Membership)
- [ ] Notification e-mail reaches the NUFA inbox; membership approval e-mail reaches a test address
- [ ] Mobile menu opens/closes; keyboard Tab shows focus outlines
- [ ] `https://YOUR-DOMAIN/includes/db.php`, `/storage/members/` and `/database/schema.sql` return **403/404**
- [ ] `https://YOUR-DOMAIN/sitemap.php` lists pages with the live domain
- [ ] Delete test submissions/members

### 7. Backup & maintenance
* Monthly: database export + download `assets/images/uploads/`, `assets/resources/` and `storage/members/`.
* Keep the admin accounts list short; review *Dashboard → Recent sign-ins*.
* Data retention: delete enquiries and rejected applications once handled (admin → Enquiries / Membership → Delete).

### 8. Rollback
1. Restore the file backup from step 1 (extract over `public_html`).
2. If the database must be reverted, import the exported SQL file in phpMyAdmin (the new tables are additive and harmless if left in place).
3. Clear any CDN/browser cache and re-run the checklist.
