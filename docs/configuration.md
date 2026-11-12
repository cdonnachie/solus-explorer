# Configuration reference (`settings.php`)

`settings.php` is a PHP file at the repository root that fills the global `$settings`
array. It is not tracked by git; create it from one of the annotated templates in
`settings/`:

| Template | Use for |
|---|---|
| `settings.php.example` | plain proof-of-work chain (Bitcoin-like) |
| `settings.php.example-pos` | proof-of-stake chain with masternodes (PIVX/Dash family) |
| `settings.php.example-assets` | asset-enabled chain (Ravencoin family) |

Keys marked **required** have no fallback in the code.

Edits take effect on the next page request and the next loader run. Three groups of
settings are baked into MongoDB indexes when a collection is first created and need
the collection to be recreated to change: `session_inactivity_sec`,
`api_burst_period_seconds`, `log_record_timeout_sec`.

Older settings files may still contain `genesis_tx`, `rpc_timeout`,
`gethashps_method`, `max_coin_supply`, `fiat_price` or `show_bittube`. The code does
not read them; they can be deleted. RPC calls use fixed 5 s connect and 30 s request
timeouts, and the hashrate always comes from `getnetworkhashps`.

## Database

| Key | Default | Meaning |
|---|---|---|
| `db_type` | `mongodb` | Must be `mongodb`, the only supported backend. Any other value stops the explorer and the loaders at startup with an error message. |
| `db_protocol` | `mongodb` | URI scheme for the Mongo driver. |
| `db_host`, `db_port` | `127.0.0.1`, `27017` | Database server. |
| `db_user`, `db_pass`, `db_name` | **required** | User with `readWrite` on `db_name`. |
| `db_opts` | none | Extra driver options array, for example `["replicaSet" => "rs0"]`. |

## Daemon RPC

| Key | Default | Meaning |
|---|---|---|
| `rpc_protocol`, `rpc_host`, `rpc_port` | `http`, `127.0.0.1`, **required** | Daemon RPC endpoint. |
| `rpc_user`, `rpc_pass` | **required** | Must match `rpcuser`/`rpcpassword` of the daemon. |
| `getinfo_method` | | `getinfo` (old daemons) or `getnetworkinfo` (Bitcoin Core 0.16+). Any other value leaves version, protocol and connection count as "unknown". |
| `getchainsync_method` | | `getblockchaininfo` (synced when headers minus blocks is below `maturity`) or `mnsync` (Dash family). Other values mean "always synced". |
| `gethashps_multiplier` | 1.0 | Multiplies the reported hashrate (for daemons that report in a different unit). |

## Chain definition

| Key | Meaning |
|---|---|
| `genesis_block` | Hash of block 0. Used as the sync anchor. **required** |
| `base58` | `["nw_bytes" => "00", "prefix" => "1", "alphabet" => "..."]` for P2PKH address rendering. |
| `segwit` | `["supported" => bool, "bech32_prefix" => "bc", "bech32_separator" => 1]`. |
| `coin_symbol` | Ticker. Lower-cased, also the OS user and daemon folder name used by `script/create.php`. |
| `coin_name` | Human-readable name; falls back to the upper-case symbol. |
| `maturity` | Coinbase maturity in blocks. Also the "synced" threshold and the suggested `maxreorg`. |
| `z_supply_mark`, `z_txid` | Zerocoin: property name of the zerocoin supply in `getblock`/`getinfo`, and the vin txid that marks a zerocoin spend. |
| `burn_addresses` | Addresses and script types (`nulldata`, `Fee Collector`) whose balance counts as burned. |

### Block reward schemes (`block_schemes`)

Each block is classified PoW or PoS from its reward transaction. The scheme name
selects how the coinbase/coinstake outputs are split between miner or staker,
masternode and developer fee. Names understood by the loader:

`PoW`, `PoW+DEV`, `PoW+MN`, `PoW+MN+DEV`, `PoW+MLC`, `PoS`, `PoS+DEV`, `PoS+MN`,
`PoS+MN+DEV`, `PoS+MN+MLC`, `SPoW`.

| Option | Values | Meaning |
|---|---|---|
| `MN_vout` | `first`, `last` | Position of the masternode payment. `middle` is not implemented. |
| `DEV_vout` | `first`, `second`, `last`, or anything else | Position of the dev fee. Any other value makes the loader search `DEV_wallets` instead. |
| `DEV_wallets` | array of addresses | Dev-fee recipients. An output is counted as dev fee only if its address is listed here. |
| `PoS_vout` | `first`, `last` | `PoS+MN+DEV` only: whether the staker's outputs come before or after the masternode output. |
| `SPOW_wallets` | array | `SPoW` only. |

Example for a PIVX-like chain:

```php
"block_schemes" => [
    "PoW" => [],
    "PoS+MN" => ["MN_vout" => "last"],
    "PoS+MN+DEV" => ["PoS_vout" => "first", "DEV_vout" => "last",
                     "DEV_wallets" => ["D7...", "D8..."]],
],
```

## Loader behaviour

| Key | Default | Meaning |
|---|---|---|
| `timezone` | `UTC` | PHP timezone for all date handling. |
| `supply_chart_source` | `coincirculation` | `coincirculation` (computed by the explorer) or `coinsupply` (reported by the daemon) for the supply chart. |
| `loader_random_delay` | true | Sleep 0 to 10 s before each loader run (de-synchronises several coins on one host). |
| `processing_precision` | 8 | Decimal places of amounts written to the database. |
| `arbitrary_precision` | 21 | Significant digits of the `Decimal` type. |
| `market_precision` | none | Decimal places of VWAP. Set it when `load_markets` is on; a missing key logs a PHP warning. |
| `storage_improvement` | true | Do not store movements inside block documents and delete spent outputs from `VOUTS` (kept in rollback records). Leave on. |
| `rollback_depth` | 50001 | Blocks of rollback data to keep. 0 disables rollback data, which makes reorgs unrecoverable. |
| `loader_timeout_sec` | 120 | A pid file older than this is treated as stale and the owning process is killed with `kill -9`. The block loader refreshes its pid file; the metrics loader does not, so keep this above the longest metrics run. |
| `pidfile_update_interval` | 5 | Seconds between pid-file refreshes while loading. |
| `log_record_timeout_sec` | 1209600 | TTL of `LOADERLOG` records (baked into the index). |
| `stop_on_missing_vins` | false | Abort the loader on an unresolved input instead of logging it. Debug aid. |
| `debug_enabled` | false | Verbose per-tx output in the loader and ad-slot logging in the browser console. |
| `calculate_chain_stats` | true | Build daily `CHAINSTATS` buckets (stats page). |
| `genesis_airdrop_enabled` | false | Track genesis airdrop claims (Evrmore). Creates `AIRDROPS`. |
| `shadow_accounting`, `shadow_config` | false | Blackcoin "Gold Rush" shadow ledger. `shadow_config` keys read: `start_height`, `end_height`, `page_size`, `include_in_supply`. Enable before the initial sync, or use `script/shadowBackfill.php`. |

## Masternodes

| Key | Meaning |
|---|---|
| `list_masternodes_method` | `listmasternodes`, `masternode_list`, `masternodelist` (DIP2), `smartnode_list_json`, `masternode_list_full`, `masternode_list-details`. Any other string (the templates use a `!` prefix for this) disables the list. |
| `count_masternodes_method` | `masternode_count`, `getmasternodecount`, `smartnode_count`. Anything else falls back to `masternodecount`. |
| `list_masternodes_columnmap` | Column index map for `masternode_list_full`. |
| `masternode_collateral` | Collateral amount, or `-1` to use `variable_collateral`. |
| `variable_collateral` | `[start_height => amount, ...]` listed **highest height first**. |
| `masternode_caching_enabled` | Cache the masternode list in `MASTERNODES` (24 h TTL) from the metrics loader instead of calling the daemon on each page view. |
| `dip2_support` | Decode ProRegTx, ProUpServTx, AssetLock and similar special transactions. `show_dip2` toggles display only. |

## Pages and features

`show_pages` is an array of booleans: `about`, `addresses`, `assets`, `burned`,
`goldrush`, `markets`, `masternodes`, `mining`, `staking`, `stats`,
`airdrop`. Pages not listed (blocks, transactions, mempool, network,
movements, addresses) are always on. A hidden page can still be opened with
`?mn=<magic_key>`.

| Key | Default | Meaning |
|---|---|---|
| `rvn_assets` | false | Ravencoin-style assets: creates `ASSETS`, `ASSETMOVES`, enables asset pages and API. |
| `show_asset_messages` | false | Show IPFS messages attached to assets. |
| `ipfs_explorer_link` | none | Gateway URL with `!#IPFS-HASH#!` placeholder, for example `https://cloudflare-ipfs.com/ipfs/!#IPFS-HASH#!`. |
| `show_op_return` | true | Show OP_RETURN payloads. |
| `show_zerocoin` | false | Zerocoin columns. |
| `show_shield`, `show_burned`, `show_illegal_supply` | false, true, true | Overview cards. |
| `export_csv_enabled` | true | CSV download buttons on address, group and asset pages. |
| `movements_overview_days` | 7 | Days of movements shown on the overview. |
| `special_features` | none | Coin-specific hooks (`MDAO_API`, `MDAO_balance`). |

## Markets

| Key | Default | Meaning |
|---|---|---|
| `load_markets` | true | Poll exchanges from the metrics loader. |
| `markets` | `[]` | List of `["exchange" => ..., "base" => "BTC", "quote" => "<SYM>", "link" => "<url>"]`. |
| `base_prices` | coinbase-pro BTC-USD and BTC-EUR | Fiat conversion of the base. Only `coinbase-pro` is implemented. |
| `markets_overview_days` | 14 | Chart window. |
| `vwap_enabled`, `vwap_color` | true | Volume-weighted average price line. |

Exchange identifiers accepted by the loader: `coinbase-pro`, `binance`, `bittrex`,
`bitfinex`, `bitforex`, `birake`, `coinex`, `coinsbit.io`, `crex24`, `deliondex`,
`dex-trade`, `escodex`, `exchangeassets`, `exmo`, `finexbox`, `graviex`, `hitbtc`,
`hotbit`, `huobi`, `kukoin`, `livecoin`, `mercatox`, `okex`, `poloniex`, `probit`,
`quan2um`, `quantadex`, `southxchange`, `stex`, `tradeogre`, `tradesatoshi`,
`vcc`, `whitebit`, `xeggex`, `xt.com`. Many of these venues no longer exist; a dead
market only produces an empty data point. `quantadex` and `escodex` use a websocket
to `bitshares.openledger.info`.

## Appearance and branding

| Key | Default | Meaning |
|---|---|---|
| `title` | | Page title and header. |
| `explorer_address` | | Absolute base URL without trailing slash (`https://host` or `https://host/<prefix>`). Empty string means "derive from the request", which only works for a plain virtual host. |
| `cookie_domain`, `cookie_path` | **required for /admin-console/** | Host and path prefix used for the admin session cookie and canonical URLs. `cookie_path` is `""` for a plain host and `/<sym>` behind a path prefix; the cookie path becomes `cookie_path . "/admin-console/"`. |
| `favicon_path` | `img/favicon.ico` | Relative to `public/`. |
| `logo_image` | `logo.svg` | File in `public/img/`. `logo_height`, `logo_width` are CSS sizes. |
| `theme_stylesheet` | `style-n.css` | File in `public/css/`. See [Creating a skin](#creating-a-skin). |
| `explorer_skin` | `solus-skin` | Body class. Keep `solus-skin`. |
| `nav_image` | `img/navback_hex_hd.jpg` | Side-menu background. |
| `skin_colors` | shipped skin values | The skin's colour tokens, emitted as `:root` custom properties before the stylesheets. Every token is optional; set only what you change, with no CSS edit. Full list in `ui_getSkinTokenDefaults()` (`lib/explorerUi.php`). See [Creating a skin](#creating-a-skin). Invalid values are ignored and fall back to the default. |
| `chart_colors`, `named_colors` | | Palettes used by charts. `chart_colors` is also published to CSS as `--chart-1` … `--chart-N`. `named_colors` is read by chart code only and does **not** set the skin — use `skin_colors` for that. |
| `amcharts_license_key` | `""` | Commercial amCharts 4 license key. Empty keeps the small amCharts logo on every chart, as the amCharts free license requires. Set it only if you hold a commercial amCharts 4 license; the footer then calls `am4core.addLicense()` before any chart is drawn. |
| `is_oem` | false | Remove the Solus Explorer name from meta keywords and use the neutral side-menu background. |
| `*_symbol` | FontAwesome classes | Icons for OP_RETURN, asset messages, dev fee, SPoW, masternode, DIP2, cold stake, airdrop, shield, MLC. |
| `meta_keywords`, `meta_description` | | HTML meta tags. |
| `copyright_message` | "Powered by Solus Explorer" | Footer HTML; `!#CY#!` becomes the year range. The misspelled key `copyright_mesage` found in old settings files is ignored. `copyright_enabled` toggles it. |
| `social_buttons`, `custom_links` | `[]` | Menu entries `["icon" => "fab fa-x", "title" => ..., "href" => ...]`. `show_socials` toggles the block. |
| `operator_name`, `operator_url`, `operator_logo`, `operator_label` | `""`, `""`, `""`, "Operated by" | Who runs this explorer. When a name or logo is set the side menu shows `operator_label` with the logo (path under `public/`) or the name, linked to `operator_url`. The name is also used for the `author` meta tag and the JSON-LD `provider` on the overview page. |
| `hosting_ref`, `wallet_ref`, `show_menu_reflinks` | `[]`, `[]`, false | Sponsor badges in the menu, `["name" => ..., "img_src" => "/img/...", "href" => "https://...", "text" => "Hosted by"]`. `href` is used as is; `img_src` is relative to `explorer_address`. Shown only when `show_menu_reflinks` is true. `show_donate` and `donate_wallets` in old settings files are not read. |
| `overview_precision`, `default_precision` | 2, 8 | Decimal places on overview and detail pages. |
| `gui_refresh_sec` | 10 | Auto-refresh of live pages. |

### Creating a skin

Skin colours live in `settings.php`, not in CSS. `public/css/style-n.css` paints with
`var(--token)` throughout, and the tokens are emitted into every page from
`skin_colors` before the stylesheets load. **Changing colours needs no CSS edit:**

1. Set the tokens you want in `settings.php`:
   ```php
   "skin_colors" => [
       "primary"     => "#3d799c",   // brand blue
       "btn-primary" => "#ff4a67",   // pink action buttons
       "navbar"      => "#3f5c80",
   ],
   ```
   Every token is optional and defaults to the shipped value, so set only what changes.
   The full list is `ui_getSkinTokenDefaults()` in `lib/explorerUi.php`.
2. Put the logo and favicon in `public/img/` and set `logo_image` and `favicon_path`.
   Optionally set `nav_image` to a side-menu background in the same folder.
3. Match `chart_colors` to the new palette so charts fit the skin. `chart_colors` is
   also published to CSS as `--chart-1` … `--chart-N`.

> **Two different "primary" colours.** `primary` is the brand blue used by
> `.primary-color` and link hovers. `btn-primary` is the **pink** action-button accent,
> because the skin re-skins Bootstrap's `.btn-primary`. To put the brand blue on a
> control, use `.primary-color` or `var(--primary)` — not `.btn-primary`.

A handful of single-use shades (focus rings and a few badge variants) are still literal
values in `style-n.css`; they are listed nowhere because each is used once. Change them
in a copy of the stylesheet if you need to.

To restyle beyond colours, copy the stylesheet
(`cp public/css/style-n.css public/css/<sym>-style-n.css`), set
`"theme_stylesheet" => "<sym>-style-n.css"` and keep `explorer_skin` at `solus-skin`.
A copied stylesheet may also declare its own `:root` to override the tokens from
`settings.php`, since it loads after them.

Skins and logos are configuration, like `settings.php`: keep them in your own
deployment rather than in this repository.

## Admin area

| Key | Default | Meaning |
|---|---|---|
| `session_inactivity_sec` | 86400 | Idle TTL of admin sessions (baked into the `USERSESSIONS` index, and checked on every request). |
| `session_max_age_sec` | 259200 | Absolute lifetime of an admin session, however active. |
| `dashboard_refresh_sec` | 10 | Admin dashboard refresh. |
| `magic_key` | | Secret for `?mn=` URL parameter that reveals hidden pages and sections. Empty disables it. |

## API

| Key | Default | Meaning |
|---|---|---|
| `api_max_requests_per_burst_period` | 15 | Successful calls per IP per burst window. 0 disables limiting. |
| `api_burst_period_seconds` | 5 | Burst window (TTL of `APIRATELIMITER`, baked into the index). |
| `api_unlimited_ip_addresses` | `["127.0.0.1", "::1"]` | IPs exempt from limits, for example the coin website. |
| `api_blacklisted_ip_addresses` | `[]` | IPs that get HTTP 551 on every call. |
| `trusted_proxies` | `[]` | Proxies (IPs or CIDR ranges) whose `X-Forwarded-For` is believed. Empty: the client address is `REMOTE_ADDR`. Behind a front proxy or CDN, list the proxy and the CDN ranges; the client is then the right-most `X-Forwarded-For` entry that is not a listed proxy. The address is used for API rate limits, the API allow and block lists, admin sessions, login throttling and usage statistics. `Client-IP` is never used. |

Behind a reverse proxy the client IP is taken from `X-Forwarded-For` as a raw string.
List the exact string that arrives at PHP.

## Ads, analytics, consent

| Key | Default | Meaning |
|---|---|---|
| `ads_enabled` | false | Master switch for the ad waterfall. Also bypasses the subscription check. |
| `ads_priority` | `["sevio","bitmedia","adsense"]` | Provider order. |
| `ads_config` | | `adsense_client_id`, `sevio_account_id`, `sevio_inventory_id`, and `units` keyed by slot (`ftr-l-728`, `ftr-r-728`, `ftr-l-320`, `ftr-m-320`, `ftr-r-320`), each with `sevio_id`, `bitmedia_id`, `adsense_id`. |
| `ads_fallback_ms` | 1000 | Time in ms a provider gets to fill a slot before the next one is tried. Either a number for all providers or a map such as `["sevio" => 3500, "bitmedia" => 900, "adsense" => 900, "default" => 900]`. Missing entries fall back to `default` (1000); Sevio gets 3500 unless set, because its Prebid wrapper runs an auction. |
| `cmp_enabled`, `cmp_property_id`, `cmp_script` | false, `""`, `js/local/cmp.js` | With ads on: show the InMobi Choice consent banner to visitors in consent scope. InMobi's tag is not part of this repository: save the universal tag JavaScript from the InMobi Choice portal as `public/<cmp_script>` (the default folder `public/js/local/` is git-ignored). The tag gets `data-property-id` from `cmp_property_id`. When `cmp_enabled` is false, the property id is empty or the file is missing, those visitors get a deny-all TCF stub (`js/consent-deny.js`), which is **not acceptable in production**. Visitors outside consent scope get no TCF API at all. |
| `consent_countries` | EU, EEA (IS, LI, NO), GB, CH | ISO 3166-1 alpha-2 codes of the countries in consent scope. The visitor's country is read from Cloudflare's `CF-IPCountry` header; a missing or unknown country (`XX`) counts as in scope, so without Cloudflare every visitor gets the banner or the stub. With ads on, the response header `X-Consent-Mode: <country>; cmp|deny-stub|none` shows the decision. |
| `google_tag_id` | `""` | Google Tag Manager container; injected on every page when non-empty, regardless of consent. |
| `cloudflare_beacon_token` | `""` | Cloudflare Web Analytics token; the beacon loads on every page except `/admin-console/` when set. |
| `trustpilot` | `[]` | Trustpilot widget in the side menu: `["business_id" => ..., "template_id" => ..., "token" => ..., "review_url" => ...]`. Shown when `business_id` and `template_id` are set. Replaces `trustpilot_enabled`. |
| `subscription_enabled`, `subscription_endtime` | | Hosted-service expiry. After the end time the block loader stops; 7 days later the API answers 402. Keep `false` on a self-hosted explorer. |

Older per-coin files contain flat `provider`, `sevio_account_id`, `ads_units` and
`ads_providers` keys. The current code ignores them; migrate to `ads_priority` plus
`ads_config`.

## Layout files

Pages include one of two layout pairs. Everything both pairs share lives in
`layout_*.php` partials, so markup is edited in one place.

| File | Used by | Contains |
|---|---|---|
| `header.php` / `footer.php` | the 25 public pages | public CSP, ad slots, ad waterfall |
| `header-admin.php` / `footer-admin.php` | `/admin-console/` | the strict admin CSP, no third parties |
| `layout_head.php` | both | `<head>`: meta, title, canonical, Open Graph, skin tokens, stylesheets, jQuery |
| `layout_sidenav.php` | both | side menu: logo, search, page links, socials, banners, operator badges |
| `layout_toasts.php` | both | subscription-expiry notices |
| `layout_navbar.php` | both | content wrapper, top navbar, top search bar |
| `layout_footer_shared.php` | both | search-not-found modal, copyright |
| `layout_scripts.php` | both | Bootstrap, DataTables, amCharts, beacon, document close |
| `layout_adstack.php` | public only | ad/consent resolution and the public CSP, incl. `$ads_core` and `$sevio_partners` |
| `layout_ads_slots.php` | public only | the five footer ad containers |
| `layout_ads_waterfall.php` | public only | the per-slot provider waterfall |

The split is why the admin console cannot serve ads: it is not a flag, the admin pair
simply never loads the three ad files. Nothing in `public/includes/` is a page, and its
`.htaccess` denies direct requests.

## External hosts and CSP

The explorer sends its own `Content-Security-Policy`. The public pages build it in
`public/includes/layout_adstack.php`, included by `public/includes/header.php`; the admin
console builds its own in `public/includes/header-admin.php`. Browsers of visitors must be
able to reach these hosts;
if a corporate proxy, a CDN rule or a second CSP filters requests, allow them:

| Host | Loaded | What |
|---|---|---|
| `ajax.googleapis.com` | every page | jQuery 3.7.1 |
| `cdnjs.cloudflare.com` | every page | perfect-scrollbar 1.4.0 (side menu) |
| `cdn.amcharts.com` | overview, address, assets, stats, admin dashboard | amCharts 4.10.40 |
| `www.googletagmanager.com` | `google_tag_id` set | Google Tag Manager |
| `static.cloudflareinsights.com`, `cloudflareinsights.com` | `cloudflare_beacon_token` set | Cloudflare Web Analytics |
| `widget.trustpilot.com` | `trustpilot` set | Trustpilot widget |
| ad, consent and font hosts listed in `$ads_core` and `$sevio_partners` (`layout_adstack.php`) | `ads_enabled` true, never on `/admin-console/` | Ad waterfall and InMobi Choice |

The three library CDNs are pinned to exact versions and carry Subresource Integrity
hashes, so a changed file is refused by the browser. To upgrade one, change the URL
and the `integrity` value in `public/includes/layout_head.php` (jQuery) or
`public/includes/layout_scripts.php` (perfect-scrollbar, amCharts). Both are shared by the
public and admin layouts. A page that draws amCharts charts sets `$use_amcharts = true;`
before including the footer.

The public pages allow `'unsafe-inline'` and `'unsafe-eval'` scripts, because the ad
and consent scripts need them. On an explorer without ads, the policy can be tightened
in `layout_adstack.php`. The admin area always gets a separate, strict policy: only the explorer
itself and the three library CDNs, no frames, `connect-src 'self'`, no `'unsafe-eval'`,
and it cannot be embedded in other pages. Its inline scripts are still allowed, because
the admin pages load HTML fragments that carry no nonce.
The Cloudflare Web Analytics script has no integrity hash, because Cloudflare
updates it in place.

## Known quirks

* `getinfo_method` must be `getinfo` or `getnetworkinfo`; any other value shows
  version, protocol and connections as "unknown".
* `burn_addresses` is read through a misspelled `isset` check, so burned coins are
  always computed from the `Fee Collector` pseudo-address.
* Dropping a provider from the waterfall because its IDs are missing does not work;
  edit `ads_priority` explicitly.
* A `theme_stylesheet` that does not exist in `public/css` is not reported; the page
  renders unstyled.
