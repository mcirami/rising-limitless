# MySQL primary-key repair

DigitalOcean requires primary keys for reliable replication. See [its guidance](https://docs.digitalocean.com/products/databases/mysql/how-to/create-primary-keys/).

The legacy installation templates omit keys on `click_geo`, `click_has_bonus`, `click_vars`, `offer_caps`, `referrals`, `user_has_bonus`, and `user_has_notification`. The October 1, 2026 migration adds a `_replication_id` unsigned BIGINT auto-increment primary key to each existing table without a primary key. It preserves existing rows (including duplicates), business identifiers, indexes, and foreign keys. A separate name avoids interfering with legacy queries that use unqualified `id` columns. Both installation templates now include these keys.

## Completed production repair — October 1, 2026

Applied to all seven tables in both `risinglimitless` and the active `risinglimitlesscutover` database on `risinglimitless-db` (MySQL 8.4.8). Final cluster-wide audit found zero tables without primary keys. All 10,468 original rows in the affected tables were compared against the pre-change backup and preserved. The migration is recorded in both databases.

The original affected-table SQL backup is retained privately on the application server at `/home/forge/primary-key-repair-20261001/before-20261001-142600.sql` (1,468,601 bytes; SHA-256 `93668fcd12ed8a1bb284f138c55b4bcb49bc855eb78cbd2fc327e33fdd3ec071`). It contains schema and data for the 14 affected tables, not a full-cluster backup. Review its `USE` statements before restoring into an isolated recovery database; do not import it over production.

Existing access: `ssh -i ~/.ssh/id_ed25519 -o IdentitiesOnly=yes forge@159.65.247.225`. The application is at `/home/forge/risinglimitless-network.on-forge.com/current`. No SSH keys or permissions were changed. The tested migration was staged outside the release directory; repository changes to the templates and audit command still need the usual deployment.

When deriving a Laravel connection configuration with `getConfig()`, remove its internal `name` field before registering the configuration under a different connection name. Otherwise the migrator can switch to the source connection while writing its migration record in the target database. The repair runner encountered this, stopped at verification, corrected the routing, and verified both database states and migration records.

## Production procedure

1. Use the deployed application's production connection, with the managed cluster's TLS settings. Confirm the host is `risinglimitless-db`. Run the read-only audit with a database user able to see every application schema:

   ```sh
   php artisan db:audit-primary-keys --all-schemas
   ```

   Exit status 1 means missing keys; row counts are estimates. This checks all accessible databases, not only the active tenant. Limited permissions can hide databases. Review any additional tables not covered by this migration separately.

2. Confirm a recent restorable backup and sufficient free disk space. Schedule a maintenance window based on actual table sizes. Adding an AUTO_INCREMENT column can rebuild a table and block writes for the duration; the 15-second metadata-lock timeout does not limit rebuild duration. Pause application writers and background workers before applying the change to large tables.

3. Deploy the code and run only this migration against each affected application database. Set `DB_DATABASE` to the actual schema name, which is not necessarily the cluster name. Ensure the effective configuration targets that schema (cached Laravel configuration ignores environment overrides):

   ```sh
   php artisan migrate --database=mysql --path=database/migrations/2026_10_01_000001_add_primary_keys_to_legacy_tables.php --force
   ```

   Repeat using the correctly configured connection for every affected tenant. The existing `migrate:all` command runs unrelated pending migrations and omits the managed cluster's port/TLS settings in its generated connections, so do not use it for this repair.

4. Re-run the cluster-wide audit, check application inserts, and resume writers. Completion requires no missing keys in **every** non-system schema, including old or unused databases.

MySQL DDL commits per table. If interrupted, rerun the migration: tables already having a primary key are skipped. Existing `_replication_id` or AUTO_INCREMENT columns without a primary key require manual review before any table is changed. Rollback deliberately throws rather than removing replication protection; use a reviewed forward migration for corrections.

## Isolated verification

Run `php scripts/verify-primary-keys.php` with `PK_TEST_PORT` pointing to a disposable MySQL server on localhost and optional `PK_TEST_USER` / `PK_TEST_PASSWORD`. The script creates and drops only its own randomly named schemas. Never point it at production.
