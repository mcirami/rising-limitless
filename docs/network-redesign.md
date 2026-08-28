# Rising Limitless network redesign

## Design and coverage

The two supplied HubWeb references informed the dark sidebar, coral accents,
gray workspace, white cards, compact tables, and profile layout.

- `resources/views/layouts/partials/network-shell.blade.php` is shared by the
  Laravel master layout and `legacy/header.php`.
- `NavBar::getVisibleMenu()` reuses the existing role and permission checks.
- `public/css/network.css` themes the shared shell, reports, tables, and legacy
  form classes. No frontend build is required.
- `public/js/network.js` handles the theme preference, mobile navigation,
  keyboard interaction, page breadcrumbs, and copy feedback.
- Account, offers, users, login, report controls, signup, signup confirmation,
  and password recovery have updated presentation.
- `NETWORK_NAME` optionally overrides the default “Rising Limitless” label.
  Existing tenant data and tracking logic are unchanged. The root homepage
  update is described below. Tenant-selected custom login themes still take precedence.

Offers use server-rendered, escaped rows. Search, type filters, page size,
sorting, and pagination operate on the currently authorized inventory. Metrics
refer to that inventory (active or inactive), not an unqueried network total.
Actual offer types are shown; the app has CPA, CPC, blacklisted, and pending
conversion types, rather than the mockup's invented CPL inventory.

No migrations or database configuration changes were made. Existing staged
landing-page deletions were left alone. No demo data or preview routes were
added to the application.

## Verification

Run from the project root:

```sh
php scripts/verify-network-ui.php
node --check public/js/network.js
node --check public/js/network-offers.js
```

The CLI-only verifier creates isolated HTML fixtures in the system temporary
directory. It renders the actual Blade views without booting application
providers, connecting to the configured network databases, or creating an authenticated web session.
It checks role restrictions, payout visibility, escaped offer names, empty
inventories, postback permissions, tracking links, user actions, reports, and
login field/CSRF contracts. It also compiles all Blade templates.

Browser checks covered offer search, combined type/search filtering, pagination,
numeric sorting, user search, theme persistence, the account page at a phone
width, and mobile navigation open/Escape-close behavior. Actual local login and
password recovery pages loaded. Report and user views were checked using test
fixtures; legacy form CSS was checked using representative markup.

## Remaining integration checks

The user confirmed the database copy is complete. Sign in to
`http://risinglimitless.test/login` for live integration checks.
Verify the redesign with actual accounts and offer data, including:

- admin, manager, and agent navigation;
- offer edit/rules/access and confirmation before deletion;
- requesting an offer and copying tracking/postback links;
- report date filters, sorting, exports, and pagination;
- legacy save forms, account edits, and impersonated-user navigation.

Do not infer successful database writes from the isolated UI checks. No real
account changes, offer requests, deletions, or financial actions were submitted.
The user confirmed `risinglimitless` as the intended database name. Its configuration was left unchanged.

## Homepage and manager-directory references

The additional landing-page and manager-user references were implemented in a
second pass:

- Regular root visits render `landing-page.blade.php` with the dark marketing
  hero, a working form posting to `/login`, feature sections, application links,
  theme switching, and a password visibility control. Existing click and
  postback branches still run before the public page. The previous tenant-lander
  redirect is removed from normal root visits.
- Managers receive `user.manager-directory` with four real summary cards,
  avatars, status badges, Edit/Login controls, search, and sorting by ID,
  username, manager, and original join timestamp. Admins retain their existing
  user-directory layout.
- `UserDirectorySummary` aggregates only the existing `User::myUsers()` scope.
  Cards cover all users in that scope, including inactive users; the table still
  applies the selected role and status. The fourth card shows Managers because
  advertisers are campaign records in this application, not a user role.
- Summary query tests use disposable in-memory SQLite data, including an
  out-of-scope account and duplicate privilege rows. Neither network database
  is used by the verifier. Dispatch tests stub the click/postback handlers, so
  no tracking events are written.
- The homepage does not copy fictional network totals, certifications, an SLA,
  unsupported login roles, or a nonfunctional remember-me control from the
  visual reference.

Additional JavaScript checks:

```sh
node --check public/js/network-landing.js
node --check public/js/network-directory.js
```

## Reports reference

The Reports reference adds summary cards, a continuous filter/export/table
panel, compact rows, numeric emphasis, and a distinct totals footer. This is
implemented for affiliate reports and both staff and agent offer reports;
other report screens inherit the shared panel, filters, and table styling.

- Summaries read the existing filtered Total row. Each report is fetched once;
  repositories, calculation filters, account scoping, and export handlers are
  unchanged. The totals footer cannot sort into the detail rows.
- Existing financial permissions apply to both cards and columns. Managers
  receive Pending Conversions instead of the reference's revenue card. Agents
  retain their own offer earnings. Unsupported advertiser/all-rep filter
  options were not copied from the mockup.
- Existing date presets and drill-down links remain. Search validates custom
  dates and preserves other URL filters; affiliate exports include the selected
  account role. Empty reports have an explicit no-activity state.
- Browser checks use isolated fixtures: light/dark and 390px layouts, contained
  horizontal scrolling, date presets, reversed-range validation, search URLs,
  and numeric sorting.

### Live verification after sign-in

The network-admin session was checked in Codex's browser with August 25–31,
2025. Offer and affiliate reports both show 15,044 raw clicks, 8,484 uniques,
43 conversions, and $1,545 sales revenue. Offer detail rows sum to those values;
the report includes 27 offers and the affiliate report includes 15 reps.

Verified numeric sorting, totals remaining in the footer, date-filter navigation,
and the 13-conversion offer drill-down with its dates retained. Live dark/light
switching displays the new SVG sun. Long report names now wrap so all offer
columns fit at a 1440px viewport; phone layouts retain contained table scrolling.
Both export links were clicked with the historical range, but downloaded file
contents were not available for inspection. Manager and agent visibility remain
covered by isolated checks, not separate live sign-ins. No business records
were edited during these checks.

```sh
node --check public/js/network-report-dates.js
node --check public/js/network-reports.js
```

## Offer country badges

Offer IDs now have gray country badges with blue text. A single active GEO
rule supplies the countries where available; deny rules are explicitly labeled
Excludes. Multiple active GEO rules fall back to title countries with an explicit
Title label and Multiple GEO rules note; the rules are never merged into an
allowlist. Incomplete active rules retain a note. With no active GEO rules,
complete uppercase comma/slash lists in the title supply the badges. UK is displayed as GB; ambiguous or unknown
codes are left untouched. IR is not silently changed to IE.

Title cleanup is for display only. Saved offer names and tracking rules are not
changed. Search includes the original title plus badge codes and country names.
A single scoped query reads country rules for the visible inventory, including
requestable offers; it does not load unrelated offers or query per row.

Verification: `php scripts/verify-offer-countries.php` passes 36 assertions using
isolated SQLite. Live checks covered rule-based and title-based offers, Cherry TV
#982 with multiple GEO rules, comma lists, country-name search, and dark mode.
The homepage test now expects no signup links, as requested by the user; no
signup links were restored. All 71 full UI checks pass.

## Network settings and theme integration

The settings crash was caused by a stale native-session company object loaded
before the copied database was ready. The current company record already has a
complete eleven-color palette. The legacy bootstrap now reloads company settings
once per request (one additional master query), so existing signed-in sessions
also receive saved changes. Missing/malformed color slots have validated defaults;
a missing company is no longer marked loaded or allowed to save.

`/settings.php` and the two legacy asset-upload URLs now dispatch through Laravel
controllers with legacy authentication, Network Admin authorization, CSRF,
validation, and explicit success/failure feedback. Forms are separate rather than
nested. Saving uses the matching company model and refreshes the native session.
PNG/ICO uploads validate content/size and replace files atomically only after
validation. Uploaded logos appear in the workspace sidebar; favicon URLs include
an asset timestamp. No database migration is required.

The existing eleven-slot palette remains compatible with legacy pages. It also
supplies workspace background/text colors in light mode, plus brand/link/button/
sidebar colors in both modes. Dark backgrounds/text stay fixed and dark link
colors are lightened for readability. Button text uses contrasting black/white.
The browser's light/dark preference is independent of company settings. The
public marketing landing page retains its separate palette and mode preference.
"Use redesign colors" only fills the form; saving is explicit. The copied green
palette and all company database values were left unchanged during verification.

Verification: 42 isolated settings assertions (SQLite, temporary PNG/ICO uploads,
save/readback, invalid input, missing tenant, company scoping, role restrictions,
authentication and CSRF), plus 71 UI and 36 country-badge assertions. Live browser
checks cover the repaired page, light/dark appearance, separate token-bearing
forms, unsaved reset/reload, and a 390px viewport without horizontal overflow.
Live saves/uploads were deliberately not performed. Address fields are stored
and validated; DNS, domain routing changes, and outbound email delivery were not
changed or tested.

```sh
php scripts/verify-network-settings.php
```

## Full-width account and legacy form cleanup

My Account now uses the full workspace width. At 1200px and above, Profile Details
and the supporting Security / Network Access / Postback cards sit in adjacent
columns; smaller screens retain the stacked layout. Manager signup links remain
in the support column and existing postback permissions are unchanged.

Legacy form columns now share a transparent background within the main card,
consistent labels, section dividers, spacing, multi-select heights and action
buttons. Both wrapped create forms and direct-column edit forms use responsive
grid layouts, stacking below 1001px. These overrides are scoped to forms inside
legacy cards, avoiding report tables and read-only detail sections. Create Offer
also has explicit section headings and a matching action row. No form field names,
submission handlers, or business records were changed.

Verified My Account, Create User, Create Offer, Create Advertiser and Edit User at
1440px, 1000px and 390px: no page overflow and no gray inner form panels. Inspected
light/dark appearance and exercised Offer Cap and Bonus Offer reveals without
submitting. The account view tests now verify support-column structure and link
placement: 75 UI assertions, 42 settings assertions and 36 country assertions pass.
