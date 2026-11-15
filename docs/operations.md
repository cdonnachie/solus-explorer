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
| `#` | mining pool, e.g. `#2Miners` | the payout address alias; a pool's address can sit inside an exchange's group, so a group tag would label the exchange's other addresses too. A pool that pays to a fresh address every block has no address to label — name it under **Mining pools** below instead |
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
* Mining charts and the overview are cached; alias and tag changes show there after
  the next metrics run. Pool names are not cached — see **Mining pools** below.
* No edit history is kept.

### Mining pools

A pool that pays to a fresh address every block cannot be labelled by address. What it
does leave behind is the miner tag in the coinbase script, which the loader keeps on
every block it indexes as `BLOCKS.coinbase_tag`. `/admin-console/labels/`, under
**Mining pools**, maps a piece of that tag to a name:

| Field | Meaning |
|---|---|
| Text in the coinbase tag | 2 to 64 characters, matched case-insensitively **anywhere** in the tag. Stored lower-cased |
| Pool name | the badge the explorer shows; the same rules as an alias — 1 to 32 characters, no `< > " '` or control characters |

**Match the stable part of the tag, not a whole tag.** A tag arrives with the
extranonce around it and the extranonce changes every block, so `/avian-sv2/` matches
every block from that pool while `-R/avian-sv2/yyz/1054` matches one. The admin page's
**Tags with no pool name** list shows the tags from the last 2,000 blocks that nothing
matches, commonest first, so the list to work through is the real one rather than a
guess; picking one fills the form with the whole tag to trim.

Where two patterns both match a tag, **the longer one wins**. So `/avian-sv2/yyz/` can
name one site of a pool that `/avian-sv2/` names generally, and the result does not
depend on the order the two were added — there is no priority to maintain.

Pools appear on `/mining/` as *Blocks by Pool (24H)* with a share chart, as a column in
the *Top Miners* table, as the *Pool* column on `/blocks/`, and as *Mined by* on each
block page. A tag no pattern matches is shown raw and muted, with the whole tag on
hover — which is how an operator spots what is left to name. A block with no tag shows
a dash, and untagged blocks are counted as their own row rather than dropped, so the
shares always add up.

Naming a pool also gives it a colour, which it keeps through a rename and on every
page; the share chart paints each slice to match its badge, and everything still
unnamed is grey in both. The colour is there to be recognised, never to be read on
its own — the name is on every badge and in the legend, and past the width of the
palette the colours repeat.

**Click a pool's swatch to change its colour.** The one in use is ringed, and a
colour another pool already holds is dimmed and says whose it is — sharing one is
allowed, and with more pools than colours it has to be. The change shows on the
next page view. This is worth doing when the automatic choice lands badly: the
colour handed out first goes to the first pool named, not to the busiest one, so
the pool with the largest share of the chart can end up with a colour you would
not have picked for it.

`pool_colors` in `settings.php` replaces the whole set; see **Pool colours** in
[configuration.md](configuration.md). **That list is positional** — the colour a
pool holds is an index into it, so replacing an entry recolours whichever pool
holds that slot, while *removing* one shifts every slot after it and recolours
several pools at once. Change entries in place.

**Resolution happens when a page is drawn, not when the loader runs.** The cache stores
raw tags only, so a name added or corrected here shows on the next page view. The
*counts* behind the pool table still come from the mining cache and move with it.

At most 200 pools. The collection is small and read whole once per request, so the
`Pool` column costs one query however long the page is. No index is needed, and no
migration: `POOLS` is created on the first pool you name. There is nothing to backfill
either — the names are applied to whatever tags the blocks already carry.

The same edits in `mongosh`, remembering that `_id` must be lower case and that the
page is the only thing enforcing the rules above:

```js
db.POOLS.updateOne({ _id: "/avian-sv2/" },
                   { $set: { name: "Avian SV2", updated_sec: Math.floor(Date.now()/1000) } },
                   { upsert: true })
db.POOLS.deleteOne({ _id: "/avian-sv2/" })
db.POOLS.find().sort({ _id: 1 })
```

Blocks indexed before the loader began keeping the tag carry none, and a pool cannot be
attributed on those. `script/refreshCoinbaseTags.php` fills them in from the daemon
without a reindex — see **Refreshing coinbase tags** below.

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
   **2.0.287 adds one to `ADDRESSMOVES`** — see below.
5. Run `php loaderMongo-blocks.php 10` by hand and check the output, then remove the
   stop file.
6. Reload Apache only if PHP packages changed; PHP files are picked up immediately.

Patch PHP with versioned packages only (`php8.4-*`) so the host does not drift to a
newer major version.

### 2.0.287 — the address chart index

The address movement chart sums `amount`, `vouts_amount` and `vins_amount` per time
bucket. The old `address_asc_time_desc_amount_asc` index answered the match but carried
only `amount`, so the server read one document per movement to add the other two up —
about 0.25 ms each when those documents are cached, and far more when they are not. On
an address with 45,000 movements that is over ten seconds, so the chart gave up and
reported itself unavailable.

`address_asc_time_desc_amounts` carries all four fields, so the aggregation is answered
from index keys alone with no document reads. The new key is a superset of the old one
and the old one's key is a prefix of it, so everything that index served is still
served; it can be dropped afterwards.

```bash
touch script/tmp/stop.all          # pause the loaders, wait for the pid files to go
php tools/addressChartIndex_migration.php              # report only, changes nothing
php tools/addressChartIndex_migration.php --create     # build it
php tools/addressChartIndex_migration.php --drop-old   # remove the superseded index
rm script/tmp/stop.all
```

The report prints the query plan before and after, and the line to check is
`documents fetched`, which should go from roughly the address's movement count to **0**.
`--drop-old` refuses unless the covering index exists *and* the plan is confirmed
covered, so it cannot leave the chart worse off than it started. The index is recognised
by its key rather than its name, so a deployment where it was already built by hand
under a different name is reported as needing nothing — MongoDB rejects a second index
with the same key, so matching on the name would fail trying to repeat work already done. Build it with the loaders paused:
`ADDRESSMOVES` is the largest collection in the database and the build competes with the
loader for disk.

Related, and worth checking at the same time if the explorer's MongoDB is on ZFS: a
dataset with `primarycache=metadata` caches no data blocks at all, so every read that
misses the WiredTiger cache goes to the physical disk. Setting `primarycache=all` on it
measured 15x on this query and 7x on block loader throughput. Cap `zfs_arc_max` first if
the host runs other guests.

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
| `script/refreshCoinbaseTags.php` | any | recompute `BLOCKS.coinbase_tag` on indexed blocks, no reindex |
| `tools/vwap_recalc.php` | `tools/` | back-fill VWAP on old `MARKETS` records |
| `tools/exportTagsAndAliases.php` | `tools/` | export hand-entered tags and aliases |
| `tools/sip041-sip061_migration.php` | any | one-off migration: block fee totals and `type_block_ix_desc` index |
| `integrity.sh [file ...]` | repo root | print SRI hashes (sha384) for the given files, or for every file in `public/js`, after replacing a library |

### Refreshing coinbase tags

`BLOCKS.coinbase_tag` holds the miner tag extracted from the coinbase input script
while the block was indexed. Only the extracted text is stored, so when the
extraction improves, blocks already in the database keep the older value. This
script recomputes the field in place against the daemon, with no reindex:

```bash
cd /var/www/<coin>
php script/refreshCoinbaseTags.php --dry-run            # report, write nothing
php script/refreshCoinbaseTags.php                      # apply
php script/refreshCoinbaseTags.php --only-missing       # top up blocks with no tag
php script/refreshCoinbaseTags.php --from=0 --to=100000 # a height range
```

Points worth knowing before a long run:

- **The daemon is required.** The coinbase script itself is not stored anywhere
  (a `VINS` record keeps only the type and the literal address `Coinbase`), so the
  tag cannot be recovered from the database alone. Nor is it in the coinbase
  `OP_RETURN`: on a segwit chain that output is the BIP141 witness commitment and
  carries no miner identity.
- **One RPC call per block**, using `getblock(hash, 2)`. A daemon too old for
  verbosity 2 is detected on the first block and falls back to
  `getblock` + `getrawtransaction`, at roughly twice the cost.
- **Safe to run while the loader runs.** Every write is matched on the block hash,
  never the height, so a block replaced by a reorg mid-run updates nothing rather
  than stamping a tag onto the wrong block.
- **Only `coinbase_tag` is written**, and only on blocks whose recomputed value
  differs, so a second run over an up-to-date range writes nothing.
- **The mining page reads a cache** that `loaderMongo-blocks.php` rebuilds at the
  end of every run, so a tag recovered here reaches the pool table on the next cron
  pass. The blocks list and the block page read the block directly and show it at
  once. Naming the pool behind a recovered tag is a separate step — see
  **Mining pools** above.

Expect roughly the RPC round-trip time per block: on a local node a few hundred
blocks a second, so a multi-million-block chain is hours, not minutes. Use
`--from`/`--to` to split it across runs.

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
