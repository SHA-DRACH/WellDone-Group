# Deploying the WellDone Group website

This guide takes the site from this repository to a live server. It works for
a VPS, cPanel or Hostinger-style shared hosting: anywhere with PHP, MySQL and
SSH access.

Work through the sections in order. The [launch checklist](#9-launch-checklist)
at the end is the short version.

---

## 1. Server requirements

| Need | Version / detail |
|---|---|
| PHP | **8.2 or newer** (8.3 recommended). PHP 8.0/8.1 will not run it. |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd`, `intl`, `curl`, `zip`, `bcmath`, `ctype`, `tokenizer`, `xml` |
| Database | MySQL 8 or MariaDB 10.6+ |
| Composer | 2.x |
| Node.js | 18+ — only where you build the CSS/JS (can be your own PC) |
| SSH | Needed for `php artisan` commands |
| Cron | One entry, every minute (section 7) |

> `zip` is the one most often missing — it is needed for the Excel exports.
> On a control panel it is a tick-box under *PHP extensions*.

The site's **document root must be the `public/` folder**, not the project
folder. Pointing the domain at the project folder exposes `.env`. On shared
hosting where you cannot change the document root, upload the project one
level above `public_html` and make `public_html` a symlink to its `public/`.

---

## 2. Get the code onto the server

```bash
git clone https://github.com/SHA-DRACH/WellDone-Group.git welldone
cd welldone
composer install --no-dev --optimize-autoloader
```

Build the front-end assets. Do it on the server if it has Node.js:

```bash
npm ci
npm run build
```

If it does not, run `npm run build` on your own computer and upload the
generated `public/build/` folder. Without it every page fails with
*"Vite manifest not found"*.

---

## 3. Database

Create an empty MySQL database and a user with full rights on it (in a control
panel: *Databases → MySQL*). Note the database name, user and password for the
next step.

---

## 4. The `.env` file

```bash
cp .env.example .env
php artisan key:generate
```

Then edit `.env`. Every value below matters on a live site:

```dotenv
APP_NAME="WellDone Group Inc."
APP_ENV=production
APP_DEBUG=false                       # true would show error details to visitors
APP_URL=https://www.welldonegroupinc.com   # exact address, with https

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1                     # or the host your panel gives you
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true            # https only
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=public
```

`APP_URL` builds every link in every email (password links, verification,
quotation references). If it is wrong, those links lead nowhere.

### Mail (required — see section 6)

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-mail-host.com
MAIL_PORT=587
MAIL_SCHEME=tls
MAIL_USERNAME=no-reply@welldonegroupinc.com
MAIL_PASSWORD=the-mailbox-password
MAIL_FROM_ADDRESS="no-reply@welldonegroupinc.com"
MAIL_FROM_NAME="WellDone Group Inc."
```

### Optional

| Key | What it turns on |
|---|---|
| `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET` | "Continue with Google" sign-in. Hidden until both are set. Add `https://your-domain/auth/google/callback` as a redirect URI in Google Cloud. |
| `ANTHROPIC_API_KEY` | Lets the site assistant phrase answers conversationally. Without it the assistant still answers from site content. |
| `SEED_STAFF_PASSWORD` | Temporary password for the leaders' accounts (section 5). |

---

## 5. Build the database and staff accounts

Run these **before** caching the config in step 8. A cached config stops
`.env` being read, and the staff seeder reads `SEED_STAFF_PASSWORD` from it.

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

`storage:link` is not optional: without it every uploaded image (logo, team
photos, project galleries) returns 404.

### Staff accounts

`db:seed` creates one sign-in account for each of the six leaders in the
Company Profile, with their role and division:

| Leader | Role | Division dashboard |
|---|---|---|
| Gbogbah Matthew Tarr | CEO | Whole group |
| Garjay Jestin Tarr | Administrator | Whole group |
| Shadrach Jimice Jr. | Super Administrator | Whole group (based in ICT Services) |
| Philip Saywray Gongar | Administrator | Whole group |
| Stephen V. Kollie | Division Manager | Logistics & Procurement only |
| William Lee Davis | Division Manager | Medical Supply only |

**Before you seed, put each person's real email address** in
`database/seeders/UserSeeder.php` (the `leaders()` list). The profile did not
list them, so the file uses `firstname.lastname@welldonegroupinc.com`
addresses. The welcome email goes to whatever address is there.

What happens to each account on a live install:

1. It gets a password: `SEED_STAFF_PASSWORD` if you set one, otherwise its own
   random password. When random, the seeder prints a table of them **once**.
2. It is marked *must change password* — the first sign-in asks for a new one.
3. The person is emailed **"Your WellDone Group Inc. account is ready"** with a
   **Choose your password** button. No password is ever put in an email.

So each leader can either use the button in their email, or sign in with the
temporary password you give them and choose a new one.

The emails are queued, so they go out once the queue worker is running
(section 7). Re-running `db:seed` later never resets anyone's password and
never re-sends the email.

To send the email again (for example after fixing an address, or if the link
expired — links last 60 minutes):

```bash
php artisan staff:invite                                  # everyone who has not set a password yet
php artisan staff:invite william.davis@welldonegroupinc.com  # one person
```

Add more staff later from **Administration → Users → New account**; they get the
same email.

---

## 6. Email (SMTP)

The development `.env` points mail at `127.0.0.1:1025`, which is Mailpit on
your own PC. **Nothing is delivered from there.** On the server you need a real
mailbox to send from:

- **Your host's email** (Hostinger, cPanel): create
  `no-reply@welldonegroupinc.com` in the panel and copy its SMTP host, port and
  password into the mail settings in section 4.
- **A transactional service** (Brevo, Mailgun, Postmark, Amazon SES) for higher
  volume and better inbox placement.

Send *from* an address on your own domain, not the Gmail address: Gmail and
others reject mail that claims to be from `@gmail.com` but comes from another
server. Add the **SPF** and **DKIM** DNS records your mail provider gives you, or
mail lands in spam.

Test it from the server:

```bash
php artisan mail:test you@example.com
```

The site sends these emails (all share the WellDone design in
`resources/views/vendor/mail` and `resources/views/vendor/notifications`):

| When | To |
|---|---|
| Staff account created | The new staff member — "Choose your password" link |
| Someone registers | Them — verification code + link |
| Password reset / change | The user — one-time code |
| Newsletter sign-up | The subscriber — confirm subscription (double opt-in) |
| Quotation request | The client (with reference) and the sales inbox |
| Contact enquiry | The info inbox; staff replies go to the enquirer |
| Job application | The candidate (with reference) and HR |
| Leave request / decision | The manager / the employee |
| Expiring certificates | HR — weekday mornings |

The receiving inboxes (sales, HR, info) are set in **Administration → Website
Settings → Contact / System**.

---

## 7. Queue worker and scheduler

Almost every email is **queued**: it waits in the database until a worker sends
it. If no worker runs, no email goes out — the site still works, it just never
emails anyone.

Add **one** cron entry (control panel: *Advanced → Cron Jobs*, every minute):

```cron
* * * * * cd /path/to/welldone && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/welldone && php artisan queue:work --stop-when-empty --max-time=55 >> /dev/null 2>&1
```

The first runs the scheduled jobs (pruning old one-time codes daily; the HR
expiry digest on weekdays at 07:30). The second sends queued email every
minute, which is the dependable option on shared hosting.

On a VPS you can instead run `php artisan queue:work` permanently under
Supervisor or systemd; then keep only the first cron line.

Use the full path to PHP 8.2+ if the server's default `php` is older — for
example `/usr/bin/php8.3` or `/opt/alt/php83/usr/bin/php`.

---

## 8. Optimise and go live

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Make sure the web server can write to `storage/` and `bootstrap/cache/`:

```bash
chmod -R ug+rw storage bootstrap/cache
```

Then open the site and work through the checklist below.

---

## 9. Launch checklist

**Before**

- [ ] Real email addresses for the six leaders are in `UserSeeder.php`
- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` is the real https address
- [ ] Document root is `public/`
- [ ] SMTP details are in `.env` and `php artisan mail:test` arrives
- [ ] `public/build/` exists (`npm run build`)
- [ ] SSL certificate is active (https)

**Deploy**

- [ ] `migrate --force`, `db:seed --force`, `storage:link`
- [ ] Both cron lines are added
- [ ] `config:cache`, `route:cache`, `view:cache`

**After**

- [ ] Each leader received "Your account is ready" and can sign in
- [ ] Stephen and William land on their own division dashboards
- [ ] Home, About, Leadership, Divisions, Services, Contact pages load
- [ ] A test contact message and quotation request arrive in the right inboxes
- [ ] Newsletter sign-up sends the confirmation email
- [ ] Team photos and the logo show (if not, `storage:link` was missed)
- [ ] In **Website Settings**, check map coordinates, social links and inbox addresses
- [ ] Replace or unpublish the demo content that is not from the profile (see docs/LAUNCH-AUDIT.md)

---

## 10. Updating the live site later

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build          # or upload public/build
php artisan migrate --force
php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan queue:restart
php artisan up
```

Do **not** run `migrate:fresh` on the live site — it erases the database.

`db:seed` is safe to re-run: content seeders refresh their rows, the settings
seeder only adds missing settings (it never overwrites what an administrator
changed), and the staff seeder never resets passwords.

---

## 11. Backups

Back up two things, ideally daily:

- the **database** (control panel backup, or `mysqldump`)
- **`storage/app/`** — every uploaded file (CVs, documents, images)

The code itself is in GitHub.

---

## 12. Troubleshooting

| Symptom | Cause |
|---|---|
| *500 Server Error*, nothing else | Read `storage/logs/laravel.log`. Never turn `APP_DEBUG` on to find out. |
| *Vite manifest not found* | `public/build/` is missing — run `npm run build` |
| Images 404 | `php artisan storage:link` was not run |
| *Access denied for user* | Wrong `DB_*` values, or `config:cache` holds old ones — run `php artisan config:clear` |
| Emails never arrive | No queue worker (section 7), or wrong SMTP details — check the `failed_jobs` table and the log |
| Email links point to localhost | `APP_URL` is wrong; fix it, then `php artisan config:cache` |
| Changes to `.env` ignored | Config is cached — `php artisan config:clear`, then `config:cache` again |
| Google button missing | `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` not set — intended |
