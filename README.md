# WellDone Group Inc. — Corporate Website & CMS

Laravel 12 · PHP 8.3 · MySQL 8.4 · Bootstrap 5 · Laragon

An enterprise corporate website and content management system for WellDone
Group Inc., covering four business divisions: Mining & Quarry, Procurement &
Logistics, Medical Equipment & Supplies, and ICT Services & Systems.

---

## First run

```bash
composer install && npm install && npm run build && php artisan migrate --seed
```

The database (`welldone`) is already created, migrated and seeded on this
machine. To rebuild it from scratch:

```bash
php artisan migrate:fresh --seed
```

### Serving the site

Laragon publishes this project at **http://welldone.test**, which matches
`APP_URL` in `.env`. If you run `php artisan serve` instead, change `APP_URL`
to `http://127.0.0.1:8000` first — uploaded media URLs are built from it.

### Development accounts

All seeded accounts use the password **`WellDone@2026`**. Change every one of
them before this goes anywhere near production.

**Group-level staff** — these land on `/admin`:

| Email | Role |
|---|---|
| superadmin@welldonegroup.com | Super Administrator |
| admin@welldonegroup.com | Administrator |
| content@welldonegroup.com | Content Manager |
| hr@welldonegroup.com | HR Manager |
| marketing@welldonegroup.com | Marketing Officer |
| sales@welldonegroup.com | Sales Officer |
| ict@welldonegroup.com | ICT Manager |

**Division officers** — each lands on its own division dashboard and is
refused the other three:

| Email | Division | Lands on |
|---|---|---|
| mining@welldonegroup.com | Mining & Quarry | `/admin/division/mining-quarry` |
| logistics@welldonegroup.com | Procurement & Logistics | `/admin/division/procurement-logistics` |
| medical@welldonegroup.com | Medical Equipment & Supplies | `/admin/division/medical-equipment` |
| ictdesk@welldonegroup.com | ICT Services & Systems | `/admin/division/ict-services` |

### Creating a division account

**Admin → Users → New account.** Set the **Division** field and give the user a
role that is not group-level (Sales Officer suits most division staff). On
save the system sends two emails — a welcome message with a link to set their
own password, and an email verification link. No password is ever emailed.

The new user then signs in at the same `/login` as everyone else and is taken
straight to their division's dashboard.

### Brand assets — action required

Save the official logo to `public/images/brand/logo.png` (and optionally
`logo-mark.png` for the favicon and `logo-light.png` for dark backgrounds).
See `public/images/brand/README.md`.

Until that file exists, every surface falls back to a built-in "WD" mark
rather than a broken image. Once it is there, the logo appears automatically
in the site header and footer, the admin sidebar, the sign-in screen, email
templates, the browser tab, and as a watermark on page banners.

---

## Sign-in and dashboards

There is **one** sign-in form for the whole organisation, at `/login`.
Where a user goes next is decided by `App\Support\Navigation`:

- **Group-level roles** (Super Administrator, Administrator, ICT, Content,
  Marketing, HR) → `/admin`, the group dashboard.
- **Division-scoped staff** (a user with a division assigned and no group
  role) → `/admin/division/{slug}`, their own division's dashboard.
- **Clients** → the public website.

A division dashboard shows that division's requests and orders, pipeline
breakdown, most-requested services, active projects, recruitment activity and
team size. Division staff can only open their own division — enforced on the
query, not just hidden in the navigation. Group staff get a division switcher.

## Email

Mail is sent server-side through Laravel's mailer, not a browser service like
EmailJS — a client-side sender cannot be trusted with internal recipients and
would expose credentials in the page. Locally it goes to Laragon's **Mailpit**
(`127.0.0.1:1025`, inbox at <http://localhost:8025>); point `MAIL_*` in `.env`
at your real SMTP host for production.

Notifications are queued (`QUEUE_CONNECTION=database`), so run a worker:

```bash
php artisan queue:work
```

Mail sent automatically:

| Trigger | To | Notification |
|---|---|---|
| Staff account created | The new user | `StaffAccountCreated` — link to set their own password. **No password is ever emailed.** |
| Staff account created | The new user | Laravel email verification |
| Email address changed | The user | Re-verification |
| Quote request submitted | Requester | `QuoteRequestSubmitted` with reference number |
| Quote request submitted | Sales inbox | `QuoteRequestReceived` |
| Contact form submitted | Info inbox | `ContactMessageReceived` (reply-to is the enquirer) |
| Job application submitted | Candidate | `JobApplicationSubmitted` with reference |
| Job application submitted | HR inbox | `JobApplicationReceived` |
| Newsletter signup | Subscriber | `NewsletterConfirmation` (double opt-in) |

Internal recipients are configured in **Admin → Website Settings → System**.

---

## What is built

### Public website — complete

Home (hero slider, counters, divisions, why-us, projects, testimonials,
partners, news, CTA, newsletter), About, Leadership, CSR, Certifications,
FAQs, Privacy, Terms, Divisions index + detail, Services index + detail,
Projects index (filterable) + detail, Gallery, News/blog index + detail with
moderated comments, Careers index + detail with résumé upload, Contact,
multi-step Request a Quote, Downloads, `sitemap.xml`, `robots.txt`.

All 30 public routes return 200, and the contact and quote forms were verified
end to end.

### Admin panel — foundation plus core modules

Built and verified: group dashboard (traffic sparkline, lead tiles, recent
activity), per-division dashboards, Divisions, Services, Users (with the
account-creation email flow), Quote Requests (queue, detail, assignment,
attachment download), Website Settings, My Profile.

### Still to build

Their tables, models, policies, permissions and the shared `BaseCrudController`
are all in place — each remaining module is a thin controller subclass, and
most need no bespoke views because `admin/generic/{index,form}.blade.php`
render from a column/field definition. `Admin\DivisionController` is the
worked example.

```
projects (+ images)   blogs (+ categories, comments)   galleries (+ images)
testimonials          partners                         sliders
downloads             clients                          careers
applications          messages                         newsletters
team-members          certificates                     milestones
csr-initiatives       faqs                             roles
media library         seo                              activity log
```

The routes for these are deliberately absent from `routes/admin.php` rather
than pointing at classes that do not exist.

---

## Architecture notes

- **Divisions are data, not code.** Adding a fifth subsidiary is a CMS task:
  navigation, the mega menu, quote form, filters and dashboards all follow.
- **Authorisation** is spatie/laravel-permission plus policies. A super
  administrator is granted everything via a `Gate::before` hook, so new
  permissions reach it without a re-seed. Deactivated accounts are denied in
  the same hook — not in `Gate::after`, which cannot overturn a granted result.
- **Private uploads** (résumés, quote attachments) live on the `local` disk,
  outside the web root, and are streamed through authorised controller actions.
  Public media goes to the `public` disk and is resized on upload.
- **Rich text** is sanitised with HTMLPurifier on save, even though only
  trusted staff reach those forms.
- **Settings** are one cached map per request; writes flush it automatically.
- **Caching**: homepage blocks, navigation and route-level SEO are cached for
  hours, and invalidated centrally in `AppServiceProvider` when content changes.
- `Model::preventLazyLoading()` is on outside production, so an N+1 fails loudly
  in development rather than shipping.

## Security

CSRF on every form; HTMLPurifier on rich text and `strip_tags` on public
submissions; per-endpoint rate limiting (`RouteServiceProvider`); honeypot
fields on all public forms; MIME-sniffed upload validation with regenerated
filenames; encrypted 60-minute sessions; hashed visitor IPs in analytics;
audit logging via spatie/laravel-activitylog; security headers middleware.

No Content-Security-Policy is set — the site embeds Google Maps and admins can
paste analytics snippets, so a CSP needs authoring against the final
deployment. That is a deliberate gap, marked in
`app/Http/Middleware/SetSecurityHeaders.php`.

## Before going live

1. Save the real logo to `public/images/brand/`.
2. Change every seeded password; delete unused accounts.
3. Set `APP_ENV=production`, `APP_DEBUG=false`, and a fresh `APP_KEY`.
4. Point `MAIL_*` at real SMTP; run a queue worker under a process supervisor.
5. Have the Privacy Policy and Terms of Use reviewed — they ship as templates.
6. Replace the seeded demo projects, articles and testimonials with real ones.
7. `php artisan config:cache route:cache view:cache` and `npm run build`.
