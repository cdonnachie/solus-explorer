# Operations

Day-to-day running of an installed explorer: loaders, reorgs, admin area, logs,
upgrades, backups and troubleshooting.

## 1. Loaders

Both loaders are run-once CLI scripts under `script/`. Cron starts them; a JSON pid
file in `script/tmp/` prevents overlap. They read `settings.php` relative to their
own location, so the working directory does not matter, but the cron lines in
[installation.md](installation.md#11-cron) keep `cd .../script &&` so that `loader.log` lands next to the scripts.

| Script | Cadence | What it does |
|---|---|---|
| `loaderMongo-blocks.php` | every minute | recovers unfinished blocks, detects orphans and rolls back, indexes new blocks, rebuilds overview, rich-list, group and mining caches |
| `loaderMongo-metrics.php` | every 5 minutes | host metrics (CPU, RAM, disk, daemon latency and traffic), markets and VWAP, hashrate and difficulty, masternode count or cached list, daily chain stats |

### Arguments of the block loader

| Argument | Effect |
|---|---|
| none | index to the daemon's tip |
| `N` | index at most N more blocks (use in cron during the initial sync) |
| `=H` | index up to height H; a lower H performs a rollback to H |
| `-N` | roll back N blocks, skipping the random delay and the orphan scan |

The metrics loader takes no arguments.

### Pid and stop files (`script/tmp/`)

| File | Meaning |
|---|---|
| `blockloader.pid`, `metrics.pid` | `{pid, since, name, active}` of the running instance. A pid whose process is gone, or belongs to a different program, is ignored. A pid whose `active` is older than `loader_timeout_sec` is killed with `kill -9` and replaced. |
| `stop.blocks`, `stop.metrics`, `stop.all` | Touch to pause the corresponding loader. Checked at start only; a running instance finishes its current run. Remove to resume. |
| `metrics.cpu`, `metrics.net` | Previous CPU and network counters used to compute deltas. Safe to delete. |

```bash
touch script/tmp/stop.all          # pause before maintenance
# ... wait for the pid files to disappear ...
rm script/tmp/stop.all             # resume
```

Keep `script/tmp` writable by the cron user (root in the examples).

### Reorgs and rollback

Each indexed block writes a `BLOCKROLLBACKS` record holding everything needed to
undo it, pruned to `rollback_depth` blocks. On every run the loader compares the
stored hash of the newest blocks with the daemon and rolls back until they match.
If the fork point is deeper than the available rollback data the loader exits with
"The chain has reorganised below the available rollback depth" and the database
must be re-synced from scratch (drop the database, recreate the user, run the
loader).

A manual rollback is useful after a daemon reindex or a loader bug fix:

```bash
php loaderMongo-blocks.php -100     # undo the last 100 blocks
php loaderMongo-blocks.php          # re-index them
```

`BLOCKSTATE` marks blocks whose write was interrupted (`pending`, `rollback_ready`,
`data_written`). The next run either clears the marker or rolls the block back
before continuing; no manual action is needed. A roll-back that fails part-way
leaves a `rollback_failed` marker, and the next run retries it. If that recovery
itself fails the loader prints "Re-sync from a height below #h is required".

### Loader stops

When the block loader cannot continue (daemon or database unreachable, an
unexpected block or transaction shape, a failed write or roll-back step, an
uncaught error), it prints `LOADER STOPPED` with the reason, writes a
`PROBLEMLOG` entry with `loader_stop: true`, removes its pid file and exits with
code 1. `/api/monitoring/getsummary/` reports the stop as `loader_abort`
(`time`, `time_tz`, `height`, `reason`) until a block loads again. The
explorer's status data turns the sync state red. Pausing with a stop file, an
instance that is already running, and an expired subscription exit with code 0.
The metrics loader exits with code 1 when the database is unreachable.

```bash
mongosh <db_name> --quiet --eval 'db.PROBLEMLOG.find({loader_stop:true}).sort({detected_at:-1}).limit(3).pretty()'
```

### Shadow ledger backfill

`script/shadowBackfill.php` is only needed when `shadow_accounting` is switched on
for an already indexed chain. Without `--yes` it reports the range it would roll
back; with `--yes` it rolls back to `shadow_config.start_height` so the regular
loader re-processes the blocks. It refuses to run while the block loader is active or
when the range exceeds `rollback_depth`.

### Subscription check

With `subscription_enabled` true the block loader stops once `subscription_endtime`
has passed, and the API returns 402 seven days later. `ads_enabled` true disables
the check. Self-hosted installs keep `subscription_enabled => false`.

## 2. Admin area (`/admin-console/`)

| Page | Purpose |
|---|---|
| `/admin-console/` | login |
| `/admin-console/dashboard/` | host, daemon and database health, sync status; refresh every `dashboard_refresh_sec` |
| `/admin-console/usestats/` | page, data-provider and API timing and usage statistics |
| `/admin-console/loaderlog/` | per-block loader log with timer details |
| `/admin-console/console/` | daemon RPC console for read-only methods (the list is `sec_consoleMethodAllowed()` in `lib/explorerSec.php`); `setban`, `disconnectnode` and `clearbanned` need `force=true`, everything else is refused |
| `/admin-console/labels/` | address aliases and address-group tags, see [Address labels](#address-labels) |

Accounts live in the `USERS` collection. Create or reset one with
`cd script && php createAdminUser.php`; an existing user name is updated, and the
password is asked twice without echo. Passwords are stored with argon2id (bcrypt
where PHP lacks it); accounts from before 2.0.228 keep an unsalted SHA-512 hash until
their next login, which rehashes them, so log in once with every old account. Group
names (`global_admin`, `tag_admin`) are stored but not enforced: every enabled user has
full access. The session cookie is `Secure`, `HttpOnly`, `SameSite=Strict`, host-only and
scoped to `cookie_path/admin-console/`, so login needs HTTPS and matching cookie settings.
A session ends after `session_inactivity_sec` of inactivity, after `session_max_age_sec`
in any case, when the browser's user agent changes, on *Log out*, and when the account's
password is changed. After 10 failed logins from one address, or 20 for one user name,
within 15 minutes further attempts are refused until the window passes (collection
`LOGINFAILURES`). Login, console and labels requests must carry the explorer's own `Origin`.
For defence in depth, give the explorer an RPC user limited by `rpcwhitelist`, or run
the daemon with `disablewallet=1`. Restrict `/admin-console/` by IP in Apache if the
explorer is public:

```apache
<Location /admin-console/>
    Require ip 203.0.113.0/24
</Location>
```

### Address labels

The explorer shows two kinds of labels as badges next to addresses, on the overview,
address, transaction, movements, mining, staking, burned-coins, group and asset pages:

* an **alias** names one address (`ADDRESSES.alias`);
* **tags** describe an address group and so every address in it (`ADDRESSGROUPS.tags`).
  When the loader joins two groups, their tags are combined.

`/admin-console/labels/` edits both: look up an address or a group id (`csag-<coin>-…`),
set or remove the alias, and add or remove the group's tags; each change is saved at
once. Below that is a list of every alias and tag with a filter. A label has 1 to 32 characters, no spaces at the
start or end, and none of `< > " '` or control characters; a group has at most 10 tags,
and tags that differ only in case are kept once. Every page prints labels HTML-escaped,
and CSV exports prefix a label starting with `= + - @` with `'` so spreadsheets don't
read it as a formula.

Conventions, suggested by the page but not enforced:

| Prefix | Meaning | Put it on |
|---|---|---|
| `#` | mining pool, e.g. `#2Miners` | the payout address alias; a pool's address can sit inside an exchange's group, so a group tag would label the exchange's other addresses too |
| `@` | exchange, e.g. `@Binance` | the group, as a tag; one exchange often owns several groups, tag each of them |

The mining page names a block winner by its alias first, then by its group's first tag.

The list needs two partial indexes. The loader never builds them, so create them once
in `mongosh` on the explorer's database (`use <db_name>`); the build runs in the
background without stopping the loader and takes a few minutes on a large chain. Until
then the list shows these commands, and lookup and editing work without them.

```js
db.ADDRESSES.createIndex({ alias: 1 },
  { name: "alias_labels", partialFilterExpression: { alias: { $type: "string" } } })
db.ADDRESSGROUPS.createIndex({ tags: 1 },
  { name: "tags_labels", partialFilterExpression: { tags: { $exists: true } } })
```

The same edits in `mongosh`, for scripts or bulk changes (check the label rules above
yourself; the page is the only place that enforces them):

```js
db.ADDRESSES.updateOne({ _id: "<address>" }, { $set: { alias: "#2Miners" } })
db.ADDRESSES.updateOne({ _id: "<address>" }, { $unset: { alias: "" } })
db.ADDRESSGROUPS.updateOne({ _id: "<group id>" }, { $addToSet: { tags: "@Binance" } })
db.ADDRESSGROUPS.updateOne({ _id: "<group id>" }, { $pull: { tags: "@Binance" } })
```

Known limits:

* A reorg rollback restores the earlier copy of each group the rolled-back blocks
  touched, so a tag edited on such a group after those blocks can revert. Check the
  list after a deep reorg.
* A tag edit made at the moment the loader joins that group into another can be lost.
  The page reads the record again after saving, so a lost edit is visible.
* Mining charts and the overview are cached; label changes show there after the next
  metrics run.
* No edit history is kept.

## 3. Logs and metrics

Loader output goes to stdout; cron's redirection decides whether it is kept. The
database holds the structured logs:

| Collection | Content | Retention |
|---|---|---|
| `LOADERLOG` | one record per indexed or rolled-back block with timings | `log_record_timeout_sec` |
| `PROBLEMLOG` | anomalies (rollback chain gaps, unresolved inputs) and loader stops | unlimited |
| `HOSTMETRICS`, `CHAINMETRICS`, `MARKETS` | metrics loader output | unlimited |
| `APILOG`, `APIAGGREGATES`, `PAGELOG`, `PAGEAGGREGATES` | request logs behind `/admin-console/usestats/` | 84 days |
| `PAGEMETRICS`, `ADSMETRICS` | browser vitals and ad fills | 30 and 60 days |
| `APIRATELIMITER`, `REQUESTS` | short-lived request tracking | seconds |

Useful queries:

```bash
mongosh <db_name> --quiet --eval 'db.LOADERLOG.find().sort({timestamp:-1}).limit(3).pretty()'
mongosh <db_name> --quiet --eval 'db.PROBLEMLOG.find().sort({_id:-1}).limit(5).pretty()'
mongosh <db_name> --quiet --eval 'db.BLOCKS.find({},{block_ix:1}).sort({block_ix:-1}).limit(1)'
```

`debug_enabled => true` makes the loader print every transaction and timing step;
turn it off afterwards.

## 4. Upgrading

1. Pause the loaders (`touch script/tmp/stop.all`) and wait for the pid files to go.
2. `git pull` in the checkout. The version number is `$version` in `lib/common.php`
   and every release bumps it.
3. Compare the matching `settings/settings.php.example*` template with your `settings.php` for new keys.
4. Indexes are only created together with their collection. When a release adds
   an index to an existing collection it ships a script in `tools/` (for example
   `tools/sip041-sip061_migration.php`); run it once from the repository root.
5. Run `php loaderMongo-blocks.php 10` by hand and check the output, then remove the
   stop file.
6. Reload Apache only if PHP packages changed; PHP files are picked up immediately.

Patch PHP with versioned packages only (`php8.4-*`) so the host does not drift to a
newer major version.

## 5. Backup and restore

Back up `settings.php`, any custom files in `public/img` and `public/css`, and the
database:

```bash
mongodump --db <db_name> --gzip --archive=/backup/<sym>-$(date +%F).archive
mongorestore --gzip --archive=/backup/<sym>-YYYY-MM-DD.archive
```

A database can always be rebuilt from the daemon by re-running the loader, which
takes hours to days depending on chain size. Address aliases and group tags entered
by hand are the only data that cannot be regenerated; `tools/exportTagsAndAliases.php`
(run from `tools/`) prints them as `mongosh` statements.

## 6. Utility scripts

| Script | Run from | Purpose |
|---|---|---|
| `script/create.php` | `script/` | full provisioning for a stand-alone host |
| `script/create-upstream.php` | `script/` | full provisioning for an upstream behind the front proxy |
| `script/createAdminUser.php` | `script/` | create or reset an admin user |
| `script/gencert.sh <fqdn>` | any | self-signed ED25519 certificate for an upstream listener |
| `script/shadowBackfill.php [--yes]` | any | Gold Rush backfill |
| `tools/vwap_recalc.php` | `tools/` | back-fill VWAP on old `MARKETS` records |
| `tools/exportTagsAndAliases.php` | `tools/` | export hand-entered tags and aliases |
| `tools/sip041-sip061_migration.php` | any | one-off migration: block fee totals and `type_block_ix_desc` index |
| `integrity.sh [file ...]` | repo root | print SRI hashes (sha384) for the given files, or for every file in `public/js`, after replacing a library |

## 7. Troubleshooting

| Symptom | Cause and fix |
|---|---|
| "already running" every minute, nothing indexed | A previous run hangs. Check the pid in `script/tmp/blockloader.pid`; it is killed automatically once its heartbeat is older than `loader_timeout_sec`, or kill it by hand. |
| "Daemon connection failed" | RPC credentials or port differ between `settings.php` and the daemon config, or the daemon is still starting. `curl --user u:p --data '{"method":"getblockcount"}' http://127.0.0.1:<port>/`. |
| "Service subscription expired" | `subscription_enabled` is on and the end time passed. Set it to `false`. |
| Loader exits with "reorganised below the available rollback depth" | Fork deeper than `rollback_depth`, or rollback data was pruned. Drop and re-sync the database. |
| Pages render without styles | `explorer_address` wrong (links point elsewhere) or `theme_stylesheet` names a file missing from `public/css`. |
| Admin login returns to the login page | Site not on HTTPS, or `cookie_domain` / `cookie_path` do not match the URL. |
| Version, protocol, connections show "unknown" | `getinfo_method` is not `getinfo` or `getnetworkinfo`. |
| Ads or charts blocked, console shows CSP errors | A second CSP is set by Apache or a CDN, or a provider uses a domain missing from the CSP lists in `public/includes/layout_adstack.php`. |
| API answers `429 Too Many Requests` for the coin website | Add its IP to `api_unlimited_ip_addresses`. Behind a proxy use the exact `X-Forwarded-For` string. |
| API answers 402 | Subscription "complete". Disable `subscription_enabled`. |
| `Class "Decimal\Decimal" not found` | `decimal` extension missing for the CLI or Apache SAPI; check `php -m` and `phpenmod decimal`. |
| Mongo authentication failed | User created in the wrong database; it must be created in `db_name`, not `admin`. |
| Metrics loader killed by the next run | A market endpoint hangs. Raise `loader_timeout_sec` or remove the dead exchange from `markets`. |
| Rate limit or session TTL changes have no effect | They are baked into indexes. Drop `APIRATELIMITER` or `USERSESSIONS`; the next run recreates them. |
