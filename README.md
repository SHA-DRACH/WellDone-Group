# WellDone Group Inc. — Corporate Website & CMS

Laravel 12 · PHP 8.3 · MySQL 8.4 · Bootstrap 5 · Laragon

An enterprise corporate website and content management system for WellDone
Group Inc., covering four business divisions: Mining & Quarry, Procurement &
Logistics, Medical Equipment & Supplies, and ICT Services & Systems.

---

## First run

```bash
composer install && npm install && npm run build && php artisan storage:link && php artisan migrate --seed
```

`storage:link` is not optional. Uploaded media — avatars, team photos,
project galleries, the logo — is written to `storage/app/public` and served
from `public/storage`. Without the symlink every uploaded image returns 404
while the files sit safely on disk, which looks exactly like a broken upload.
Run it again after any fresh clone or deploy.

The database (`welldone`) is already created, migrated and seeded on this
machine. To rebuild it from scratch:

```bash
php artisan migrate:fresh --seed
```

### Serving the site

`APP_URL` is set to `http://127.0.0.1:8000` to match `php artisan serve`. If
you switch to Laragon's virtual host at `http://welldone.test`, change
`APP_URL` to match.

It has to match the address you actually browse, because `APP_URL` is what
builds the links inside emails — verification, password reset — and any
absolute URL generated outside a web request, such as from a queued job. A
mismatch produces verification links that lead nowhere.

Uploaded media is *not* affected: the `public` disk URL is deliberately
root-relative (`/storage`), so images resolve against whatever host served
the page and keep working across localhost, the vhost and production.

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
team size. Group staff get a division switcher.

### What a division head can and cannot see

Division staff are confined to their own division across **every** module.
The restriction is applied to the database query, not just hidden in the
navigation, so it holds even against a guessed or hand-typed URL:

| Module | Scoped by |
|---|---|
| Division dashboard | their own division only |
| Quote requests | `division_id` on the request |
| Contact enquiries | `division_id` chosen by the sender on the contact form |
| Job applications | the division of the vacancy applied for |

Anything belonging to another division — or a general enquiry with no
division set — returns **403**. Group-level roles see everything.

Division heads can reply to their own division's enquiries from inside the
panel; the reply is emailed to the sender and kept on the record.

### Registered visitors

Members of the public register at `/register`, which creates a **Client**
account. Client accounts carry no admin permissions whatsoever, so they can
never reach the control panel.

Submitting a quotation request, a job application or a contact enquiry
requires a **verified** account. Visitors can browse everything freely; only
the act of submitting is gated. This means every lead is traceable to a
confirmed email address, and the sender can follow it at `/account`.

The account area shows their quotation requests (with status and any quoted
amount), their job applications and status, their enquiries with our replies,
and their own details — which pre-fill the forms.

## Email

Three mailers are available. Switch with `MAIL_MAILER` in `.env` — nothing
else changes, because they all plug in behind Laravel's mailer:

| `MAIL_MAILER` | Behaviour |
|---|---|
| `emailjs` | Sends through EmailJS (see below) |
| `smtp` | Mailpit locally, or any real SMTP host |
| `log` | Writes messages to `storage/logs` instead of sending |

**Currently configured: Gmail SMTP, sending for real.** Mail goes out through
`smtp.gmail.com:587` authenticated as `sjimice6@gmail.com`.

Two Gmail constraints worth knowing:

- `MAIL_PASSWORD` must be a **Google App Password**, not the account
  password. Generate one at Google Account → Security → 2-Step Verification →
  App passwords.
- Gmail only lets you send **as the authenticated account** or a verified
  alias, so `MAIL_FROM_ADDRESS` is the Gmail address. Recipients see the
  Gmail address, not `@welldonegroup.com`. To send as the company domain you
  need Google Workspace (or any host) with a mailbox on that domain — then
  just change `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS`.
- Free Gmail allows roughly **500 messages a day**; Workspace allows 2,000.

To go back to capturing mail locally instead of sending it, point the SMTP
settings at Mailpit (`127.0.0.1:1025`, no auth) and read it at
<http://localhost:8025>. The commented block in `.env` has the exact values.

Check the configuration at any time with:

```bash
php artisan mail:test you@example.com
```

### Hiding the personal Gmail address

`MAIL_FROM_NAME` is set to **WellDone Group Inc.**, so that is the sender name
every normal mail client displays. The underlying Gmail address is still
visible if a recipient expands the sender details — Gmail refuses to send as
an address it has not verified, so this cannot be fixed in code.

Two ways to remove it properly:

1. **Gmail alias.** Gmail → Settings → Accounts and Import → *Send mail as* →
   add `info@welldonegroup.com`, complete the verification email, then set
   `MAIL_FROM_ADDRESS` to it.
2. **A mailbox on the company domain** (Google Workspace, cPanel, any host).
   Change `MAIL_USERNAME`, `MAIL_PASSWORD` and `MAIL_FROM_ADDRESS` to match.

Option 2 is the better answer for a company site: it also improves
deliverability, because the sending domain then matches the brand.

### Email branding

Messages use a branded template rather than Laravel's default:

- royal-blue header carrying the WellDone logo, company name and slogan
- gold call-to-action buttons
- footer with the registered name, address, phone and website

The template lives in `resources/views/vendor/mail/html`, and the colours in
`html/themes/welldone.css` (selected by `MAIL_THEME`).

**The logo will not load while the site runs on `127.0.0.1`.** Mail clients
fetch images over the public internet and cannot reach your machine. It
appears correctly as soon as the site is on a public domain; until then the
company name and slogan carry the branding on their own.

## Sign in with Google

Visitors can register or sign in with a Google account. Accounts created this
way get the **Client** role, exactly like the registration form — social
sign-in never grants staff access. The email is treated as verified, because
Google has already confirmed it.

The button only appears once credentials are configured, so it never leads to
a dead end.

### Setting it up

1. **Google Cloud Console** → create or select a project.
2. **APIs & Services → OAuth consent screen** → configure it (External is
   fine) and add your email as a test user while in testing mode.
3. **APIs & Services → Credentials → Create credentials → OAuth client ID** →
   application type **Web application**.
4. Under *Authorised redirect URIs*, add this **exactly** — scheme, host, port
   and path all have to match or Google returns `redirect_uri_mismatch`:

   ```
   http://127.0.0.1:8000/auth/google/callback
   ```

   Add the production URL alongside it when you deploy.
5. Copy the client ID and secret into `.env`:

   ```
   GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxxxxxxxxxx
   ```

### How accounts are matched

An existing account is matched on the provider's own user id first, then on
email address. Matching on email is safe here because Google has verified
ownership of it — so a visitor who registered with a password can later use
the Google button and reach the same account rather than a duplicate.

### EmailJS

Set `MAIL_MAILER=emailjs` and fill in the four values in `.env`:

```
MAIL_MAILER=emailjs

EMAILJS_SERVICE_ID=service_xxxxxxx
EMAILJS_TEMPLATE_ID=template_xxxxxxx
EMAILJS_PUBLIC_KEY=xxxxxxxxxxxxxxxx
EMAILJS_PRIVATE_KEY=xxxxxxxxxxxxxxxx
```

Then check it works:

```bash
php artisan mail:test you@example.com --mailer=emailjs
```

#### Required setup in the EmailJS dashboard

1. **Email Services** → connect the sending account → copy the **Service ID**.
2. **Account → API Keys** → copy the **Public Key** and the **Private Key**.
3. **Account → Security** → enable
   **"Allow EmailJS API for non-browser applications"**. Without this the REST
   endpoint refuses server-side calls and every message fails.
4. **Email Templates** → create a template → copy the **Template ID**.

The template must be built around the variables this application sends.
The critical one is `message_html`, which must use **triple** braces so the
HTML renders instead of being escaped:

| Variable | Contains |
|---|---|
| `{{to_email}}` | Recipient address — put this in the template's **To** field |
| `{{to_name}}` | Recipient name |
| `{{from_name}}` | Sender name |
| `{{reply_to}}` | Monitored inbox — put this in the template's **Reply To** field |
| `{{subject}}` | Subject line — put this in the template's **Subject** field |
| `{{{message_html}}}` | The full rendered email body |
| `{{message}}` | Plain-text fallback |

A minimal template body is just:

```
{{{message_html}}}
```

One template serves every message the site sends, because the whole body is
rendered by Laravel and passed through.

#### Worth knowing before you rely on it

- EmailJS's free tier allows **200 emails a month**. A busy careers or
  quotation period will exceed that; check your plan against expected volume.
- It sends **one recipient per API call**, so a message addressed to several
  people costs several sends against your quota.
- It is built for contact forms rather than bulk transactional mail. If you
  later outgrow it, switching to SMTP or a transactional provider is a
  one-line change to `MAIL_MAILER` — no application code is affected.
- The **private key is server-side only**. It lives in `.env`, is read in
  `config/services.php`, and never reaches the browser. Do not put it in any
  Blade template or JavaScript file.

### Using SMTP instead

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=no-reply@welldonegroup.com
MAIL_PASSWORD=your-smtp-password
MAIL_SCHEME=tls
MAIL_FROM_ADDRESS="no-reply@welldonegroup.com"
MAIL_FROM_NAME="WellDone Group Inc."
```

Most providers require `MAIL_FROM_ADDRESS` to match an address you own and
have verified, or mail will be rejected or land in spam. Set SPF and DKIM
records for the domain at the same time.

Outbound mail is sent from the no-reply address, but every client-facing
message carries a **Reply-To** pointing at a monitored inbox — sales for
quotations, HR for applications, and the department that answered for enquiry
replies. Those addresses are configured in
**Admin → Website Settings → Contact**, so replies always reach a real person.

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
| Staff replies to an enquiry | Enquirer | `ContactMessageReply` |

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

### Admin panel — complete

165 routes across 26 modules, all verified.

**Dashboards** — group dashboard (traffic sparkline, lead tiles, recent
activity) and a per-division dashboard for each business division.

**Leads & enquiries** — Quote Requests (queue, detail, assignment, status
pipeline, attachment download), Contact Messages (with in-panel reply that
emails the enquirer), Job Applications (review workflow, résumé download),
Newsletter Subscribers (with CSV export of confirmed addresses only).

**Corporate structure** — Divisions, Services, Clients.

**Website content** — Hero Slides, Projects (with gallery management),
Galleries, News & Blog (rich text, tags, scheduling), Blog Categories,
Comment moderation, Testimonials, Partners, Downloads.

**Company profile** — Leadership & Team, Milestones, Certificates, CSR
Initiatives, FAQs.

**Recruitment** — Vacancies.

**Administration** — Users, Roles & Permissions (grouped matrix), Media
Library, SEO Metadata, Website Settings, Activity Log, My Profile.

Around twenty of these are thin subclasses of `Admin\BaseCrudController`,
which handles listing, search, filters, uploads, rich-text sanitising and
authorisation. They declare a column set and a field set, and
`admin/generic/{index,form}.blade.php` render both — so a new module is
roughly eighty lines and no markup. `Admin\DivisionController` is the worked
example; `Admin\ProjectController` shows how to extend the generic form with
bespoke sections.

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
