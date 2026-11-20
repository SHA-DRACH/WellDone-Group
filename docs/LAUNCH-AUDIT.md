# Pre-launch audit — 20 November 2026

What was checked before hosting, what was fixed, and what still needs a
decision or content from the company.

## Summary

The site is ready to deploy. Every page, form route and dashboard was
exercised and none returned a server error. Before launch, three things are
still needed from the company. Then follow [DEPLOYMENT.md](DEPLOYMENT.md):

1. The six leaders' mailboxes created in cPanel (firstname@welldonegroupinc.com)
2. SMTP details for a mailbox on the company domain
3. A decision on the demo content listed under *Open items*

---

## What was checked

| Area | How | Result |
|---|---|---|
| Automated tests | `php artisan test`: 18 test files, 80+ tests | All pass |
| Public routes | Every GET route requested as a visitor (162 checked) | All load; every `/staff`, `/portal` and `/account` page correctly redirects to sign-in |
| Staff panel | Every GET route requested as Super Administrator | All load; no server errors |
| Role permissions | Same crawl as HR, Content Manager and a Division Manager | No server errors; pages outside a role correctly return 403 |
| Division confinement | Division Managers signed in | Each lands on and is confined to their own division |
| Sign-in | Each leader's account; a retired placeholder account | Leaders sign in and land on the right dashboard; placeholders are refused |
| Email | Every notification sent to a mail catcher and inspected, desktop and phone width | Render correctly with the new design |
| Mail delivery | `php artisan mail:test` | Sending works; no live SMTP server configured yet (see open items) |
| Site assistant | Request shape against the current Claude API | Valid (`claude-opus-5`, effort `low`, no removed parameters) |
| Unfinished-code markers | Search for TODO / FIXME / placeholder text in code | None found |

## Fixed in this pass

| Issue | Risk | Fix |
|---|---|---|
| 14 placeholder staff accounts shared one password that is published in this public repository | Anyone could sign in to the staff panel of a live site seeded from it | Replaced with the six real leaders. On a live install each gets a private password, must change it at first sign-in, and is emailed a set-password link. The placeholders are removed wherever they exist. |
| Re-running `db:seed` reset every staff password | A routine re-seed would lock everyone out and restore the public password | Passwords are now set only when an account is first created |
| `public/storage` link missing locally | Uploaded images return 404 | Created; the deployment guide makes it a required step |
| Stale scaffold test (`ExampleTest`) always failed | A permanently red test suite hides real failures | Removed; `HomePageTest` already covers the home page |
| Some emails read "WellDone Group Inc.." (double full stop) | Looks careless | Fixed in four emails |
| Emails used Laravel's stock body text | Generic look; codes hard to read | New design: gold accent bar, branded card, large code box for one-time codes, proper sign-off with slogan, tidy fallback link, footer listing the five divisions and contact details |
| No deployment documentation | — | [DEPLOYMENT.md](DEPLOYMENT.md) |

New command: `php artisan staff:invite [email…]` re-sends the
"choose your password" email.

---

## Open items

### Needed from the company before launch

- **Leaders' mailboxes.** The accounts use each person's company mailbox,
  `firstname@welldonegroupinc.com`. Create the six mailboxes in cPanel before
  deploying, or the welcome emails bounce.
- **SMTP mailbox.** Mail is currently sent to Mailpit on the development PC
  and never delivered. Create `no-reply@welldonegroupinc.com` (or similar)
  with your host and add SPF/DKIM DNS records — see DEPLOYMENT.md section 6.
- **Philip Gongar's role.** Set to *Administrator* (group-wide), as strategy
  spans every division. Change it in **Administration → Users** if it should be
  narrower.

### Content that is still demo material

These sections were seeded with realistic examples before the Company Profile
existed. They are not in the profile and describe projects and clients the
company may not have. Replace them with real content, or deactivate them in the
staff panel, before launch:

| Section | Where to manage it (staff panel sidebar) |
|---|---|
| Clients | Corporate Structure → Clients |
| Projects, testimonials | Website Content → Projects / Testimonials |
| News articles (7) | Website Content → News & Blog |
| Gallery albums (4, no photos) | Website Content → Galleries |
| Job vacancies (7) | Human Resources → Vacancies |
| Certificates / registrations (6) | Company Profile → Certificates |
| CSR initiatives (5) | Company Profile → CSR Initiatives |
| FAQs (11) | Company Profile → FAQs |
| HR departments and positions | Human Resources → Departments / Positions |

### Smaller gaps

- **Mining Services and Trading & Investment have no division manager.**
  Stephen Kollie's title covers General Trading, but an account can belong to
  one division only. He is attached to Logistics & Procurement.
- **Partners** have no logos or profiles yet, and Mr. Mamadou Camara's company
  name was blank in the profile, so he is listed under his own name.
- **Map location** still points to the earlier Sinkor coordinates. Update
  latitude/longitude (or the Google Maps embed URL) for Thinkers Village,
  Paynesville in Website Settings → Contact.
- **Social media links** are empty and hidden until filled in.
- **Company registration number and TIN** settings are empty.
- **Downloads**: the Company Profile PDF and capability statements are listed
  but hidden until a file is uploaded.

### Local development notes

- On this PC a separate MySQL Server 8.0 service holds port 3306, so Laragon's
  MySQL runs on **3307** (`DB_PORT=3307` in the local `.env`). This does not
  affect the live server.
