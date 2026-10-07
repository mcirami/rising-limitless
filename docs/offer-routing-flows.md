# Offer routing flows

Open **Offers → Offer Routing Flows** (`/offer/routing-flows`) as a Network Admin
with the existing Edit Offer Rules permission.

1. Create a named flow and select its entry offer.
2. Add offers in priority order, with accepted country codes for each row.
   The entry offer belongs in the first row if it should receive matching clicks.
3. Choose a final fallback offer for unmatched and unknown countries.
4. Test countries using the current unsaved edits, then enable and save.
5. Duplicate the flow to build other sequences. Copies start disabled and unassigned.

Country codes can be pasted with commas, spaces, tabs, line breaks or slashes, or added using the
country picker. UK is normalized to GB by the editor. Suggestions extract complete
country-only segments after separators in offer names; review them before saving.
Repeated countries are allowed: the first row containing the country wins.
Each row also has an **Allow all countries** toggle. It catches every remaining
click, including unknown locations, and makes later rows and the final fallback
unreachable. The editor warns about those later rows. Turning the toggle off
before saving restores the country text entered in that editing session. Saved
allow-all rows store the flag and an empty list, so they cover new countries too.
This is stored in the existing steps JSON and requires no additional migration.
Drag handles and up/down buttons reorder rows, including their country lists.

## Runtime behavior

One flow may be assigned to each entry offer, including paused flows. A flow may
use the same destinations as other flows. Destination membership alone never
changes direct traffic to that destination. Country selection resolves once,
server-side, before click registration; a destination's assigned flow is not
recursively evaluated. Tracking records and URL replacement use the selected
offer, with affiliate and sub-ID values preserved. The original request URL is
retained in the existing click-variable log.

For flow traffic, the flow replaces GEO checks on the selected destination.
Existing country rules remain stored and still apply to direct traffic outside a
flow. Destination device/repeat-click rules and offer/affiliate caps still apply
and may produce their usual redirects. The tester only previews country selection,
not affiliate eligibility or dynamic caps. Both the entry and selected destination
must be active and assigned to the affiliate. An inaccessible, deleted or inactive
destination rejects the click using the existing failure path; it does not silently
select a different country row. Pausing/deleting a flow restores the entry offer's
existing behavior.

## Deployment

Run the additive migration in the application's normal PHP/database environment:

```sh
php artisan migrate --path=database/migrations/2026_10_07_000001_create_offer_routing_flows_table.php
```

The migration creates `offer_routing_flows` without altering existing offers or
rules. No live flows are seeded or enabled. Tracking continues using existing
rules if the new table has not been created yet. The management screen requires
the migration. Run normal route-cache refresh steps if deployment caches routes.

## Verification

```sh
php scripts/verify-offer-flows.php
php scripts/verify-offer-countries.php
node --check public/js/offer-flows.js
```

The flow suite uses isolated SQLite and checks the example country sequence,
priority, unknown-country fallback, persistence, validation, copies, pause/delete,
route authorization, entry/destination eligibility, single-pass click resolution,
sub-ID preservation, retained non-GEO checks and migration rollback. Passing a
temporary output directory renders a fixture of the actual Blade editor with a
minimal shell for browser QA; it adds one rendering assertion. The access lookup
uses `fetchColumn()` because PDO does not guarantee `rowCount()` for SELECTs.

Browser QA exercised reorder controls, name suggestions, country chips and country
tests with the real resolver in a disposable fixture. Desktop and 390px layouts
were inspected; no browser errors or mobile horizontal overflow were observed.
The configured `risinglimitless.test` app/Docker stack was unavailable during this
change, so its database migration and a full live click were not executed.
The broader existing `verify-network-ui.php` suite stops at its uploaded-sidebar-
logo fixture assertion in this checkout, before testing the new feature.
