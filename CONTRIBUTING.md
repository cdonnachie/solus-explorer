# Contributing

Thanks for helping. Bug reports, chain support, fixes and documentation are all
welcome. To report a security problem, follow [SECURITY.md](SECURITY.md) instead of
opening an issue.

Support for this code base is ending (see [Project status](README.md#project-status)).
Issues and pull requests are reviewed until 30 November 2026, and best-effort from
1 December 2026. They are no longer reviewed after 31 January 2027 at the latest. The
conventions below still apply to forks that continue the code.

## Issues

Include:

* the explorer version (`/api/monitoring/ver/` or `$version` in `lib/common.php`);
* the coin and its daemon version;
* PHP and MongoDB versions;
* what you did, what you expected and what happened.

For loader problems, attach the console output around `LOADER STOPPED` and the
matching entry from
`db.PROBLEMLOG.find({loader_stop:true}).sort({detected_at:-1}).limit(1)`. Remove
RPC credentials, host names and IP addresses before you post anything.

## Branches and pull requests

1. Fork the repository and branch from the default branch. Use one branch per
   change, for example `fix/orphan-rollback` or `coin/<sym>`.
2. Keep each pull request to one topic. Do not mix reformatting with behaviour
   changes.
3. Bump `$version` in `lib/common.php` (the last number) in every pull request that
   changes code. Operators and `/api/monitoring/ver/` rely on it to tell builds apart.
4. Start the commit subject with the new version, then say what changed and why, for
   example `2.0.231 loader: keep the BLOCKSTATE marker when ...`.
5. Describe in the pull request how you tested: which chain, which pages and which
   loader runs.
6. Never commit a `settings.php`, credentials, operator logos or skins, or coin
   data. `settings.php` is git-ignored for this reason.

## Coding style

* PHP 8.1 to 8.4, with no framework, no Composer dependencies and no JavaScript
  build step. The pages are plain PHP templates under `public/`. Shared code lives in
  `lib/`, and CLI scripts in `script/`.
* All database access goes through the `db_*` functions in `lib/explorerDb.php`,
  which call `mongo_*` in `lib/explorerDbMongo.php`. All daemon access goes through
  `rpc_*` in `lib/explorerRpc.php`. Do not use the MongoDB driver or the RPC client
  directly in pages.
* Coin amounts are `Decimal` values (`ext-decimal`, created with `cdec()`); never use
  floats for balances.
* Follow the style of the surrounding code: 4-space indents, `snake_case` settings
  keys, and prefixes such as `ui_`, `api_`, `db_` and `ldr_` for functions.
* Comments explain why, not what.
* The loader must not stop with a bare `die()` or `exit()`. Use `ldr_abort()` or
  `ldr_abortBlock()` for faults and `ldr_quietExit()` for deliberate exits. These
  keep loader stops logged and visible to monitoring.
* Escape all output with `htmlspecialchars()` or `json_encode()`. Inline scripts
  need the page's CSP nonce (`$csp_nonce`). New external hosts must be added to the
  CSP (see [docs/configuration.md](docs/configuration.md#external-hosts-and-csp)).
* New third-party code must have a permissive license and its own row in
  [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
* Run `php -l` on every changed PHP file.

## Running against a testnet

There is no automated test suite. Changes are tested on a running instance.
`lib/tests/` holds three scripts that rebuild the overview, mining and address
caches by hand (`php lib/tests/test_overview_cache.php` and so on, from the
repository root of a configured instance). A testnet or regtest daemon is the quickest place to try loader
changes, because its chain is small and reorgs are cheap.

1. Run the coin's daemon with `-testnet` (or `-regtest`), `txindex=1`, and RPC on
   127.0.0.1 ([docs/installation.md](docs/installation.md#5-coin-daemon)).
2. Set up the explorer as in [docs/installation.md](docs/installation.md), with its own
   checkout, database and `settings.php`. In `settings.php` use the testnet values:
   `rpc_port`, `genesis_block` (`<coin-cli> -testnet getblockhash 0`), the testnet
   `base58` and `segwit` prefixes, and `maturity`.
3. Index with small steps (`php loaderMongo-blocks.php 100`) and watch the console.
   Use `php loaderMongo-blocks.php -N` to roll back N blocks and check that they
   re-index the same way.
4. On regtest, `<coin-cli> -regtest invalidateblock <hash>` followed by new blocks
   produces a reorg that the loader must roll back by itself.
5. Before you open the pull request, open the pages your change touches with the
   browser console open. Check that there are no PHP errors in the Apache error log
   and no new `PROBLEMLOG` entries.

## Adding a skin

Skins are a stylesheet plus images, selected in `settings.php`. The steps are in
[docs/configuration.md](docs/configuration.md#creating-a-skin). Changes to the
default `public/css/style-n.css` are welcome when they fix layout or accessibility
for every skin. Coin-specific skins and logos belong in your own deployment, not in
this repository.

## Adding a chain or a settings template

Most chains need only a `settings.php` built from the closest template in
`settings/`. A new template, or a change to the loader, is needed when a chain has a
reward layout or transaction type the loader does not know yet.

* If the loader stops with "Unexpected number of reward VOUTs", "Unexpected content
  of the reward TX" or "Unprocessed VIN type", note the block height and the
  `getblock <hash> 2` output. That is the starting point for a new `block_schemes`
  variant in `lib/loaderBlock.php`.
* A new template goes in `settings/settings.php.example-<family>`. Copy the closest
  existing template, use placeholder values for every credential, host name and
  address, and comment each key that differs from the base template. Add the template
  to the table in [docs/configuration.md](docs/configuration.md).
* Document every new `settings.php` key in
  [docs/configuration.md](docs/configuration.md) with its default, and add it to all
  three templates.
