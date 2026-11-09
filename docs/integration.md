# Integration

This document covers how to publish the explorer (stand-alone host or sub-path
behind a shared reverse proxy), how to brand it, how to wire ads, consent and
analytics, and how a coin website links to or embeds explorer data.

## 1. Deployment topologies

### 1.1 Stand-alone virtual host

One host name per coin (`https://<fqdn>/`). Apache serves `public/` directly, see
[installation.md](installation.md#9-apache-virtual-host-stand-alone).

Settings:

```php
"explorer_address" => "",                 // or "https://<fqdn>"
"cookie_domain"    => "<fqdn>",
"cookie_path"      => "",
```

### 1.2 Sub-path behind a shared front proxy

This serves several coins as `https://explorer.example.com/<coin>/`. A **front proxy**
(one Apache host) owns the public name and TLS certificate and
reverse-proxies `/<coin>/` to an **upstream** Apache on the host that runs the coin
daemon, loaders and database. Upstreams listen on a dedicated TLS port with a
self-signed certificate and accept connections only from the front proxy.

```
browser ── https://explorer.example.com/<coin>/ ──▶ front proxy ── https://<node>:<port>/ ──▶ upstream Apache
                                               /var/www/upstreams.d/<coin>.conf           /var/www/<coin>.upstream/public
```

PHP never sees the `/<coin>` prefix. Every absolute link and asset URL is built from
`explorer_address`, and canonical URLs and the admin cookie from `cookie_domain` plus
`cookie_path`. The settings therefore carry the public address:

```php
"explorer_address" => "https://explorer.example.com/<coin>",   // no trailing slash
"cookie_domain"    => "explorer.example.com",
"cookie_path"      => "/<coin>",
"trusted_proxies"  => ["<front proxy IP>"],   // add the CDN ranges if a CDN sits in front
```

Without `trusted_proxies` the upstream sees every visitor with the front proxy's
address, so API rate limits, login throttling and usage statistics would treat all
visitors as one client. With Cloudflare in front, list its published IP ranges
(https://www.cloudflare.com/ips/) as well.

#### Upstream host

1. Check the code out as `/var/www/<coin>.upstream`, create `settings.php`, database
   and admin user as in the installation guide.
2. Create a long-lived self-signed certificate for the node name (once per host):

   ```bash
   mkdir -p /etc/cert/<node> && cd /etc/cert/<node>
   bash /var/www/<coin>.upstream/script/gencert.sh <node>.example.com
   ```

3. Run `script/create-upstream.php` as root from `script/`. Like `create.php` it
   skips what already exists: OS user, daemon folder, database user, collections,
   vhost and admin user. It asks for the listener port, refuses a port that is
   already in use, and stops if the certificate from step 2 is missing. It writes
   and enables `/etc/apache2/sites-available/<coin>.upstream.conf` with this
   checkout's `public/` as `DocumentRoot`, reloads Apache only after a passing
   `apache2ctl configtest`, and prints the firewall rule, the front-proxy snippet and
   the daemon config. The vhost it writes is the template in
   [examples/upstream.vhost.conf](examples/upstream.vhost.conf).
4. Open the port to the front proxy only:

   ```bash
   ufw allow from <front proxy IP> to any port <port> proto tcp
   # or add <port> to set upstream-ports and the proxy IP to upstream-allowed-ips in nftables
   ```

Pick a port per coin (for example from a 505xx range) and keep a list; the port is the only
coupling between the two hosts.

#### Front proxy host

The public vhost enables `proxy`, `proxy_http`, `ssl`, `headers` and `rewrite`,
sets `SSLProxyEngine On`, `ProxyPreserveHost On`, forwards `X-Forwarded-Proto` and
`X-Forwarded-Host`, and includes one file per coin:

```apache
Include "/var/www/upstreams.d/*.conf"
```

A complete public vhost is in [examples/front-proxy.vhost.conf](examples/front-proxy.vhost.conf).
Each `/var/www/upstreams.d/<coin>.conf` (printed by `create-upstream.php`) mounts the
upstream under `/<coin>/`, rewrites cookie paths and fixes `Location` headers that
the upstream emits without the prefix:
[examples/upstream-mount.conf](examples/upstream-mount.conf). After adding a file:

```bash
apachectl configtest && systemctl reload apache2
curl -sI https://explorer.example.com/<coin>/ | head -1
```

Because `ProxyPreserveHost` is on, the upstream sees `Host: explorer.example.com`. Its
`ServerName` does not have to match.

#### Optional legacy host name

If the coin used to live on `https://<coin>.example.com/`, keep a tiny vhost that
redirects everything to the new path (HTTP 301, `RewriteRule ^/(.*)$ https://explorer.example.com/<coin>/$1 [R=301,L]`)
so old links and search engines follow.

### 1.3 Several coins on one host

Each coin is an independent checkout, database and crontab pair. Nothing is shared
except Apache, PHP and MongoDB. Use `loader_random_delay` (default on) so the
per-minute loaders of different coins do not start at the same second.

## 2. Security headers and TLS

The application emits its own `Content-Security-Policy`, `X-Frame-Options`,
`Referrer-Policy`, `Permissions-Policy` and `X-Content-Type-Options` from
`public/includes/header.php`. The CSP always allows amCharts, jQuery (Google
Hosted Libraries) and cdnjs, and adds Google Tag Manager, Cloudflare Insights and
Trustpilot only when their settings are filled in, and the Sevio, Bitmedia,
AdSense and InMobi domains only when ads are on. The host list is in
[configuration.md](configuration.md#external-hosts-and-csp). Do not set these headers in Apache or at
a CDN; a second CSP is intersected with the first and blocks scripts.

Apache hardening that is compatible: `ServerTokens Prod`, `ServerSignature Off`,
TLS 1.2+ only (`SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1`), HSTS
(`Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"`).

Behind Cloudflare or another CDN set `explorer_address` to the public URL and make
sure `X-Forwarded-For` reaches the upstream, otherwise API rate limiting applies to
the proxy address.

## 3. Branding a new coin

1. Put the logo in `public/img/` and reference it with `logo_image`
   (`logo_height` / `logo_width` are CSS sizes). The same file is used for Open
   Graph previews.
2. Create a skin from `public/css/style-n.css` as described in
   [configuration.md](configuration.md#creating-a-skin).
3. Optionally replace `nav_image` and the `chart_colors` / `named_colors` palettes.
4. Set `title`, `coin_name`, `meta_keywords`, `meta_description`,
   `copyright_message`, `social_buttons`, `custom_links`.
5. Set `operator_name`, `operator_url` and optionally `operator_logo` to show who
   runs the explorer. `is_oem => true` removes the Solus Explorer name from the meta
   keywords. The footer text is whatever `copyright_message` says.
6. `themes/_default.png` shows the default skin for comparison.

## 4. Ads, consent and analytics

The `ads` branch ships a client-side waterfall: five footer slots are filled when
they scroll into view, trying the providers in `ads_priority` with
`ads_fallback_ms` per provider. Fills are reported to `/api/monitoring/adsmetrics/` and
stored in `ADSMETRICS` (60-day TTL).

```php
"ads_enabled"     => true,
"cmp_enabled"     => true,                       // InMobi Choice consent banner
"cmp_property_id" => "<InMobi property id>",
"cmp_script"      => "js/local/cmp.js",          // InMobi's tag, saved by you (not in git)
"google_tag_id"   => "GTM-XXXXXXX",
"ads_priority"    => ["sevio", "bitmedia", "adsense"],
"ads_fallback_ms" => ["sevio" => 3500, "default" => 900],
"ads_config"      => [
    "adsense_client_id"  => "ca-pub-...",
    "sevio_account_id"   => "<uuid>",
    "sevio_inventory_id" => "<uuid>",
    "units" => [
        "ftr-l-728" => ["sevio_id" => "<uuid>", "bitmedia_id" => "<id>", "adsense_id" => "<id>"],
        "ftr-r-728" => [...], "ftr-l-320" => [...], "ftr-m-320" => [...], "ftr-r-320" => [...],
    ],
],
```

Rules:

* With `ads_enabled` true the loader skips the subscription check.
* Consent is decided per visitor: countries in `consent_countries` (default EU, EEA,
  UK, Switzerland) and visitors of unknown origin get the consent banner, everyone
  else gets no TCF API. The country comes from Cloudflare's `CF-IPCountry` header,
  so behind another proxy every visitor counts as in scope.
* With `cmp_enabled` false, or without the tag file, in-scope visitors get a deny-all
  TCF stub meant for development only. A public site with ads needs
  `cmp_enabled => true`, `cmp_property_id`, an InMobi Choice property configured for
  the public host name, and InMobi's universal tag saved as `public/js/local/cmp.js`
  (the JavaScript between the `<script>` tags in the InMobi Choice portal). The tag is
  InMobi's code under InMobi's terms, so it is not shipped here; the folder is
  git-ignored and survives `git pull`. Check the result with the
  `X-Consent-Mode` response header (`cmp` or `deny-stub`).
* `google_tag_id` injects Google Tag Manager on all pages, including `/admin-console/`,
  independent of consent. Handle consent inside the GTM container.
* Ads and consent scripts are never loaded on `/admin-console/`.
* New third-party domains used by a provider must be added to the CSP lists in
  `public/includes/header.php` (`$ads_core`, `$sevio_partners`), otherwise the
  browser blocks them silently.

## 5. Linking to the explorer

Every page is a folder with query-string parameters. Stable URLs for external sites
and wallets:

| Object | URL |
|---|---|
| block by height | `/block/?blockheight=<n>` |
| block by hash | `/block/?blockhash=<hash>` |
| transaction | `/tx/?txid=<txid>` |
| address | `/address/?address=<addr>` |
| address group | `/addressgroup/?groupid=<id>` |
| asset | `/asset/?name=<asset name>` |
| rich list | `/addresses/` |
| masternodes, mining, staking, markets, stats | `/masternodes/`, `/mining/`, `/staking/`, `/markets/`, `/stats/` |
| search | no GET URL; the search box POSTs `q` to `/_dp/searchdp.php`, which answers `{objectType, objectValue}` and the page then opens one of the URLs above |

Hidden pages (`show_pages` false) open with `?mn=<magic_key>` appended.

Machine-readable outputs:

* JSON API under `/api/`, see [api.md](api.md). Add the IP of the coin website to
  `api_unlimited_ip_addresses` so its widgets are not rate limited.
* `/sitemap/` returns an XML sitemap of the static pages built from
  `explorer_address`.
* CSV export (`export_csv_enabled`): `/_dp/addressmovescsv.php?addr=<addr>`,
  `/_dp/addressgroupcsv.php?groupid=<id>`, `/_dp/assetmovescsv.php?asset_id=<id>`.
* `/api/monitoring/getsummary/` gives host, daemon and database health for an
  external uptime monitor; `/api/monitoring/ver/` gives the version.

The `_dp/` and `_up/` folders are internal data providers for the pages' tables and
live refresh; they are not a supported interface.

## 6. Daemon integration notes

* The explorer only reads from the daemon, except `/api/sendrawtransaction/`, which
  relays signed transactions, and the admin console, which only runs read-only methods.
* `rpcthreads=32` and `maxconnections=72` in the daemon config keep page loads
  responsive while the loader runs.
* The loader fetches each block with `getblock` and every transaction with
  `getrawtransaction`, which is why `txindex=1` is mandatory.
* Masternode and DIP2 chains: set `list_masternodes_method`,
  `count_masternodes_method`, `dip2_support` and `getchainsync_method => "mnsync"`.
