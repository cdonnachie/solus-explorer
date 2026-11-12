# Installation

This guide installs one explorer for one coin on a fresh Ubuntu 22.04 or 24.04 host.
If MongoDB and the coin daemon already run elsewhere, or the site sits behind
Cloudflare, read [section 14](#14-split-deployment-behind-cloudflare) first: it lists
which of the sections below to skip or replace.

Placeholders used throughout:

| Placeholder | Meaning | Example |
|---|---|---|
| `<sym>` | lower-case coin ticker, used for the OS user, daemon folder and checkout name | `doge` |
| `<coind>` / `<coin-cli>` | daemon and CLI binaries | `dogecoind` / `dogecoin-cli` |
| `<coin>` | the daemon's data folder and config name | `dogecoin` (`~doge/.dogecoin/dogecoin.conf`) |
| `<fqdn>` | public host name of the explorer | `doge.example.com` |

All commands run as root unless stated otherwise.

## 1. Requirements

* 2 vCPU, 4 GB RAM, SSD. Database size is roughly 1.5 to 3 times the chain size
  because spent outputs and rollback data are stored.
* Outbound HTTPS (market data, Cloudflare Insights, ad providers) and, only when
  `quantadex` or `escodex` markets are configured, outbound `wss://`.
* A coin daemon that answers `getblockcount`, `getblockhash`, `getblock` (verbosity 2
  or `getrawtransaction` with `txindex=1`), `getnetworkhashps`, `getnettotals`,
  `getnetworkinfo` or `getinfo`.

## 2. Operating system preparation

```bash
apt-get update && apt-get -y upgrade
apt-get -y install git curl gnupg ca-certificates fail2ban
systemctl enable --now fail2ban

# swap (skip if the host already has one)
fallocate -l 4G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile \
  && echo '/swapfile none swap sw 0 0' >> /etc/fstab

# kernel settings recommended by MongoDB
cat >> /etc/sysctl.conf <<'SYS'
vm.swappiness=1
vm.max_map_count=262144
SYS
sysctl -p
```

## 3. MongoDB

Follow the current instructions at
https://www.mongodb.com/docs/manual/administration/install-on-linux/ for your
release. For MongoDB 8.0:

```bash
curl -fsSL https://www.mongodb.org/static/pgp/server-8.0.asc | gpg --dearmor -o /usr/share/keyrings/mongodb-server-8.0.gpg
. /etc/os-release
echo "deb [ arch=amd64,arm64 signed-by=/usr/share/keyrings/mongodb-server-8.0.gpg ] https://repo.mongodb.org/apt/ubuntu ${VERSION_CODENAME}/mongodb-org/8.0 multiverse" \
  > /etc/apt/sources.list.d/mongodb-org-8.0.list
apt-get update && apt-get -y install mongodb-org
systemctl enable --now mongod
```

MongoDB 5.0 and newer need a CPU with AVX; check with `grep -c avx /proc/cpuinfo`
on a VPS. Ubuntu 24.04 has MongoDB packages from 8.0 on.

Keep `bindIp: 127.0.0.1` in `/etc/mongod.conf` unless the database runs on another
host. If you need a replica set (for example to share one MongoDB between several
explorer hosts), add `replication: { replSetName: rs0 }`, run `rs.initiate()` in
`mongosh`, and set `"db_opts" => ["replicaSet" => "rs0"]` in `settings.php`.

Shorten the TTL monitor interval so rate-limit and request records expire promptly.
`/etc/mongod.conf` has no `setParameter` section by default, so append one and
restart:

```bash
cat >> /etc/mongod.conf <<'YML'
setParameter:
  ttlMonitorSleepSecs: 5
YML
systemctl restart mongod
```

Log rotation for `/var/log/mongodb/mongod.log`:

```bash
cat > /etc/logrotate.d/mongod <<'LR'
/var/log/mongodb/mongod.log {
    rotate 4
    size 500M
    daily
    missingok
    notifempty
    compress
    delaycompress
    sharedscripts
    postrotate
        /bin/kill -SIGUSR1 $(cat /var/run/mongodb/mongod.pid 2>/dev/null) 2>/dev/null || true
    endscript
}
LR
```

## 4. PHP and Apache

The explorer needs PHP 8.1 to 8.4 with the `mongodb` and `decimal` extensions.
Pick the path that matches your release.

### Ubuntu 24.04 (PHP 8.4 from the Sury PPA)

```bash
apt-get -y install software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update
apt-get -y install apache2 libapache2-mod-php8.4 php8.4-cli php8.4-curl php8.4-mongodb php8.4-decimal php8.4-readline php8.4-xml
```

Install only the versioned `php8.4-*` packages. The versionless meta packages
(`php`, `php-curl`, `php-mongodb`) now pull in PHP 8.5, which is untested.

### Ubuntu 22.04 (stock PHP 8.1)

```bash
apt-get -y install apache2 libapache2-mod-php8.1 php8.1-cli php8.1-curl php8.1-mongodb php8.1-readline php8.1-xml \
                   php8.1-dev php-pear build-essential libmpdec-dev
pecl install decimal
cat > /etc/php/8.1/mods-available/decimal.ini <<'INI'
; configuration for php-decimal module
; priority=90
precision=64
extension=decimal.so
INI
phpenmod decimal
```

If the packaged `php8.1-mongodb` is too old for your MongoDB server, build it with
`pecl install mongodb` and create `mongodb.ini` the same way.

### Common PHP and Apache settings

```bash
PHPV=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
sed -i 's/^memory_limit = .*/memory_limit = -1/' /etc/php/${PHPV}/cli/php.ini      # loaders
sed -i 's/^;\?allow_url_fopen = .*/allow_url_fopen = On/' /etc/php/${PHPV}/cli/php.ini
sed -i 's/^;\?allow_url_fopen = .*/allow_url_fopen = On/' /etc/php/${PHPV}/apache2/php.ini

a2enmod headers ssl rewrite
sed -i 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf
systemctl restart apache2
php -m | grep -E 'mongodb|decimal|curl'
```

Do **not** add `Content-Security-Policy` or `X-Frame-Options` headers in Apache. The
application sets its own headers in `public/includes/header.php` and
`public/includes/header-admin.php`; duplicates break
ads, charts and the admin area.

## 5. Coin daemon

The repository convention is one OS user and one binary folder per coin:

```bash
useradd -m -s /bin/bash <sym>
mkdir -p /usr/local/bin/<sym>
# copy <coind> and <coin-cli> into /usr/local/bin/<sym>/
chown -R <sym>:<sym> /usr/local/bin/<sym>
```

Minimum `~<sym>/.<coin>/<coin>.conf`, owned by `<sym>`. Choose the RPC user, a long
random password and the RPC port now; the same values go into `settings.php` in
step 7.

```
rpcuser=<rpc user>
rpcpassword=<long random password>
rpcport=<port>
rpcconnect=127.0.0.1
rpcbind=127.0.0.1
rpcallowip=127.0.0.1
rpcthreads=32
daemon=1
listen=1
server=1
txindex=1
# PIVX and Dash family only; Bitcoin Core ignores these with a warning
staking=0
maxreorg=<maturity>
banscore=3600
```

On a regtest or testnet chain, Bitcoin Core reads the RPC options only from the
network's section, so put them under `[regtest]` or `[test]` (with `regtest=1` or
`testnet=1` above it).

systemd unit:

```bash
cat > /etc/systemd/system/<sym>.service <<'UNIT'
[Unit]
Description=<sym> daemon
After=network-online.target

[Service]
User=<sym>
Type=forking
ExecStart=/usr/local/bin/<sym>/<coind> -daemon
ExecStop=/usr/local/bin/<sym>/<coin-cli> stop
Restart=on-failure
RestartSec=15
TimeoutStopSec=120
StartLimitInterval=60
StartLimitBurst=10

[Install]
WantedBy=multi-user.target
UNIT
systemctl daemon-reload
systemctl enable --now <sym>
```

Let the daemon sync fully before starting the loaders. `txindex=1` is mandatory; if
it was added later, restart the daemon once with `-reindex`.

## 6. Explorer code

```bash
cd /var/www
git clone https://github.com/cryptoscope-io/solus-explorer.git <sym>
cd <sym>
```

The checkout stays owned by root: the web tier only reads it, and the loaders run as
root from cron. By convention the folder is named after the lower-case `coin_symbol`.

Notes:

* `vendor/` is git-ignored and **not used** by the code (nothing includes
  `vendor/autoload.php`). No `composer install` is needed; the code talks to MongoDB
  through the `mongodb` PHP extension directly.
* Stay on the default branch; upgrading is a `git pull` (see
  [operations.md](operations.md#4-upgrading)).
* In the sub-path topology the checkout is named `/var/www/<sym>.upstream`. See
  [integration.md](integration.md) for that topology.

## 7. settings.php

Create the root `settings.php` from the closest template. It is git-ignored, so
`git pull` never overwrites it.

```bash
cp settings/settings.php.example settings.php   # or settings.php.example-pos / settings.php.example-assets
nano settings.php
```

At minimum set these keys (full reference in [configuration.md](configuration.md)):

| Key | Value |
|---|---|
| `db_name`, `db_user`, `db_pass` | new database and user, created in step 8 |
| `rpc_port`, `rpc_user`, `rpc_pass` | from the daemon config |
| `genesis_block` | hash of block 0 (`<coin-cli> getblockhash 0`) |
| `base58`, `segwit` | address prefixes of the coin |
| `coin_symbol`, `coin_name`, `title` | ticker, name, page title |
| `maturity` | coinbase maturity in blocks (also used as reorg guard) |
| `block_schemes` | reward layout: PoW, PoW+MN, PoS+MN+DEV and so on |
| `getinfo_method` | `getnetworkinfo` for modern daemons, `getinfo` for old ones |
| `explorer_address` | public base URL **without** trailing slash, or `""` for a plain vhost |
| `cookie_domain`, `cookie_path` | host name and path prefix (`""` for a plain vhost); required for admin login |
| `magic_key` | optional secret that unlocks hidden pages via `?mn=`; empty disables it |
| `show_pages`, `load_markets`, `markets` | what to show and which exchanges to poll |
| `ads_enabled`, `subscription_enabled` | both `false` for a self-hosted install |

`settings.php` contains secrets. Keep it out of the web root (it is, the document
root is `public/`) and out of public git remotes, and make it readable by Apache only:

```bash
chown root:www-data settings.php && chmod 640 settings.php
```

## 8. Database, collections, admin user

`script/create.php` automates steps 5 to 9 on a stand-alone host: OS user, daemon
folder, database user, collections, Apache virtual hosts for the host name in
`explorer_address`, a print-out of the daemon config, and the admin user. Every step
checks first and skips what already exists, so it also works after you followed
step 5 by hand, and it can be run again. Run it as root from `script/`:

```bash
cd /var/www/<sym>/script && php create.php
```

* The `DocumentRoot` is this checkout's `public/` folder, wherever it lives.
* It asks for a folder with `origin.pem` and `privkey.pem` (default
  `/etc/cert/<fqdn>/`) and checks that they are a matching certificate and key.
  Without them it offers to write only the `:80` vhost; add TLS afterwards with
  certbot as in step 9.
* Existing vhost files are never overwritten. New sites are enabled, then
  `apache2ctl configtest` runs. If the test fails, or Apache stops at the reload,
  the new sites are disabled again and Apache keeps running with its previous sites.
* If MongoDB access control is already on, it asks for a MongoDB administrator login
  to create the database user.
* Any failed step stops the script with exit code 1.

To do it by hand:

```bash
# database and user (values from settings.php)
mongosh --eval "db.getSiblingDB('<db_name>').createUser({user:'<db_user>', pwd:'<db_pass>', roles:[{role:'readWrite', db:'<db_name>'}]})"

# collections and indexes are created by the first loader run (step 10)
```

Once the database user exists, turn on access control in `/etc/mongod.conf` and
restart MongoDB, so the credentials are enforced:

```yaml
security:
  authorization: enabled
```

Create the admin user for `/admin-console/` after the first loader run (step 10), so the
`USERS` collection already has its indexes:

```bash
cd /var/www/<sym>/script && php createAdminUser.php
```

The TTL indexes (session lifetime, API burst window, log retention) are created
together with the collections and are **not** updated later. Decide
`session_inactivity_sec`, `api_burst_period_seconds` and `log_record_timeout_sec`
before the first run, or drop the affected collection to recreate it.

## 9. Apache virtual host (stand-alone)

```bash
cat > /etc/apache2/sites-available/<fqdn>.conf <<'VH'
<VirtualHost *:80>
    ServerName <fqdn>
    DocumentRoot /var/www/<sym>/public
    ErrorDocument 404 /err/404/index.php
    ErrorLog  ${APACHE_LOG_DIR}/<sym>-error.log
    CustomLog ${APACHE_LOG_DIR}/<sym>-access.log combined
    <Directory /var/www/<sym>/public>
        AllowOverride All
        Require all granted
        Options -MultiViews
    </Directory>
</VirtualHost>
VH
a2ensite <fqdn>.conf
a2dissite 000-default.conf
systemctl reload apache2
```

Then obtain a certificate; certbot adds the `:443` vhost and the redirect:

```bash
apt-get -y install certbot python3-certbot-apache
certbot --apache -d <fqdn>
certbot renew --dry-run
```

The admin cookie is `Secure`, so the admin area works over HTTPS only. For the
sub-path layout (`https://host/<sym>/`) use [integration.md](integration.md) instead
of this section.

## 10. Initial synchronisation

Run the block loader by hand first. Every run creates any missing collections and
indexes, then indexes blocks. Limit each run so you can watch progress:

```bash
cd /var/www/<sym>/script
php loaderMongo-blocks.php 1000       # index the next 1000 blocks
php loaderMongo-metrics.php           # markets, chain and host metrics
```

Arguments of the block loader:

| Argument | Effect |
|---|---|
| none | index up to the daemon's tip |
| `N` | index at most N more blocks |
| `=H` | index up to height H (a lower H rolls back to H) |
| `-N` | roll back N blocks (bounded by stored rollback data) |

For a long initial sync it is fine to add the cron entries now; a lock file
(`script/tmp/blockloader.lock`) prevents overlapping runs and each run continues
where the previous one stopped.

## 11. Cron

Use root's crontab (or that of the user owning `script/tmp`), one pair of lines per coin:

```cron
# <sym>
*/5 * * * *   cd /var/www/<sym>/script && php loaderMongo-metrics.php > /dev/null 2>&1
* * * * *     cd /var/www/<sym>/script && php loaderMongo-blocks.php 2000 >> loader.log 2>&1
```

The numeric cap ends each run after that many blocks, so the page caches are rebuilt
regularly during a long catch-up. A running loader is only replaced when its
heartbeat in the pid file stops for longer than `loader_timeout_sec`, never because
the run is long. Drop the cap once the explorer is at the tip, or leave it, it does
no harm. Rotate `script/loader.log`
with logrotate if you keep the redirection.

## 12. Firewall

Only 80, 443, your SSH port and the coin's P2P port need to be public. MongoDB
(27017) and the daemon RPC port must stay closed. A full nftables ruleset with
placeholder addresses is in [examples/nftables.conf](examples/nftables.conf).
With ufw:

```bash
ufw default deny incoming
ufw allow <ssh port>/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow <p2p port>/tcp
ufw --force enable
```

## 13. Verification checklist

| Check | Expected |
|---|---|
| `php /var/www/<sym>/script/loaderMongo-blocks.php 10` | prints "Last loaded block" and advances |
| `curl -s https://<fqdn>/api/getblockcount/` | JSON with the indexed height |
| `curl -s https://<fqdn>/api/monitoring/ver/` | version string from `lib/common.php` |
| Browser `https://<fqdn>/` | overview page with block list and hashrate |
| Browser `https://<fqdn>/admin-console/` | login works, dashboard shows CPU, RAM and daemon status |
| `mongosh <db_name> --eval 'db.LOADERLOG.countDocuments()'` | grows with every block |

Common first-run problems are listed in [operations.md](operations.md#7-troubleshooting).

## 14. Split deployment behind Cloudflare

Sections 1 to 13 install everything on one host. This section covers the other common
layout: the explorer in its own container, with MongoDB and the coin daemon already
running elsewhere, reached from the internet through Cloudflare.

| Container | Holds | Reached on |
|---|---|---|
| explorer | Apache 2.4, mod_php 8.4, the checkout | a public address, Cloudflare in front |
| mongodb | MongoDB, possibly shared with other services | private network only |
| daemon | `<coind>` with `txindex=1` | private network only |

Follow sections 1 to 13 as written, with these changes:

| Section | Change |
|---|---|
| 3. MongoDB | Skip. See 14.2 for what the existing server must provide |
| 4. PHP and Apache | Run in the explorer container only. See 14.3 |
| 5. Coin daemon | Skip if the daemon already runs. `rpc_host` in `settings.php` is then its private address, not `127.0.0.1` |
| 9. Apache virtual host | Replaced by 14.5. Do not run certbot |
| 12. Firewall | Replaced by 14.7 |

### 14.1 Explorer container

2 vCPU and 4 GB RAM as in section 1, but the disk can be small: the database lives
elsewhere, so the checkout, logs and PHP are all that is stored. 20 GB is ample.

Use an **Ubuntu 24.04** image. The `decimal` extension is not packaged by any
distribution; the Sury repository provides it, and the Ubuntu form of that repository
(`ppa:ondrej/php`) is the one section 4 documents and tests. On Debian the same
packages come from `deb.sury.org` with a different setup, so the commands in section 4
do not apply unchanged.

### 14.2 The MongoDB server

`db_initMongoConnection()` always builds a credentialed URI, so **the server must have
authorization enabled**. A MongoDB running without authentication cannot be used, even
on a private network.

On the MongoDB server, bind to the private address as well as loopback, and create the
user from section 8:

```bash
sed -i 's/^  bindIp: .*/  bindIp: 127.0.0.1,<mongo private ip>/' /etc/mongod.conf
# security.authorization must be "enabled"
systemctl restart mongod
```

In `settings.php`, point at it:

```php
"db_host"  => "<mongo private ip>",
"db_port"  => 27017,
```

`db_initMongoConnection()` builds `<protocol>://<user>:<pass>@<host>:<port>/<db_name>`,
so the database name is in the URI and the authentication database defaults to it. With
the user created as section 8 does it, in `db_name`, no `db_opts` entry is needed. Only
a user created somewhere else, such as `admin`, needs
`"db_opts" => ["authSource" => "admin"]`. A wrong `authSource` fails as an
authentication error rather than a missing user, which is easy to misread.

**Turning on authorization affects every other user of that server.** A shared MongoDB
that other services reach without credentials will reject them the moment access
control is enabled. Check what else is there before enabling it:

```javascript
db.adminCommand({ listDatabases: 1 }).databases.map(d => d.name)
```

If there are other tenants, give the explorer its own MongoDB rather than adding
credentials to everything else. It is the only service here that cannot connect
without them.

### 14.3 PHP and Apache

Install as in [section 4](#4-php-and-apache), omitting MongoDB. Then stop PHP 8.5
being pulled in later by a dependency or an unattended upgrade:

```bash
printf 'Package: php8.5* libapache2-mod-php8.5\nPin: release *\nPin-Priority: -1\n' \
  > /etc/apt/preferences.d/no-php85
```

PHP 8.5 parses this code without errors but is untested, and raises deprecations the
supported versions do not.

### 14.4 Real client addresses

Behind Cloudflare every request arrives from a Cloudflare address. Left alone, that is
what the usage statistics, the loader log and the admin area all record.

Fix it in Apache with `mod_remoteip`, which rewrites the address before PHP sees it, so
`REMOTE_ADDR`, `client_ip()` and the access log are all correct:

```bash
a2enmod remoteip
{ echo 'RemoteIPHeader CF-Connecting-IP'
  curl -s https://www.cloudflare.com/ips-v4 https://www.cloudflare.com/ips-v6 \
    | sed 's/^/RemoteIPTrustedProxy /'
} > /etc/apache2/conf-available/cloudflare-remoteip.conf
a2enconf cloudflare-remoteip
systemctl reload apache2
```

Re-run it when Cloudflare changes its ranges, which is rare but does happen.

The alternative is to leave Apache alone and list the same ranges in
`settings["trusted_proxies"]`; `client_ip()` then takes the right-most
`X-Forwarded-For` entry that is not itself a trusted proxy. That corrects the
application but not the Apache logs, so `mod_remoteip` is preferred. Do not use both.

### 14.5 Virtual host and origin certificate

Cloudflare terminates TLS for visitors; the origin needs its own certificate for the
connection from Cloudflare. Use a Cloudflare **Origin CA** certificate (SSL/TLS →
Origin Server → Create Certificate) rather than certbot: it is free, lasts fifteen
years and needs no renewal job. It is trusted only by Cloudflare, which is the point.

Save the certificate and key as `/etc/ssl/cloudflare/<fqdn>.pem` and `.key`, with the
key `chmod 600`, then:

```bash
cat > /etc/apache2/sites-available/<fqdn>.conf <<'VH'
<VirtualHost *:443>
    ServerName <fqdn>
    DocumentRoot /var/www/<sym>/public
    ErrorDocument 404 /err/404/index.php
    ErrorLog  ${APACHE_LOG_DIR}/<sym>-error.log
    CustomLog ${APACHE_LOG_DIR}/<sym>-access.log combined

    SSLEngine on
    SSLCertificateFile    /etc/ssl/cloudflare/<fqdn>.pem
    SSLCertificateKeyFile /etc/ssl/cloudflare/<fqdn>.key

    <Directory /var/www/<sym>/public>
        AllowOverride All
        Require all granted
        Options -MultiViews
    </Directory>
</VirtualHost>
VH
a2ensite <fqdn>.conf
a2dissite 000-default.conf
systemctl reload apache2
```

**`AllowOverride All` is required, not cosmetic.** The checkout ships five `.htaccess`
files: four turn off directory listings, and `public/includes/.htaccess` denies direct
requests for the layout partials. With `AllowOverride None` those files are ignored and
`/includes/header.php` is served as a fragment of a page with status 200.

There is no `:80` vhost, because with the SSL mode below Cloudflare only ever connects
to the origin on 443, and no ACME challenge needs port 80. Add one only if the host
also answers direct requests.

For extra hardening, enable Authenticated Origin Pulls in Cloudflare and add
`SSLVerifyClient require` with Cloudflare's origin-pull CA, so the origin refuses
anything that did not arrive through Cloudflare.

### 14.6 Cloudflare settings

Four settings matter. All are available on the free plan.

| Setting | Value | Why |
|---|---|---|
| SSL/TLS → Overview | **Full (strict)** | Flexible connects to the origin over HTTP, so `$_SERVER['HTTPS']` is empty and every canonical, `og:url` and absolute URL is emitted as `http://` |
| Network → IP Geolocation | **On** | Supplies `CF-IPCountry`, which drives consent. Without it `ui_GetVisitorCountry()` returns empty, and an unknown country counts as in scope, so every visitor worldwide gets the consent wall |
| Caching → a "Cache Everything" page or cache rule | **Never add one** | The application sends no `Cache-Control` on HTML and generates a fresh CSP nonce and request id per request. Cached pages would serve one nonce to every visitor, making the policy decorative, and collide request ids in the statistics. The default, caching static assets only, is correct |
| Speed → Rocket Loader | **Off** | It defers and reorders scripts. The CMP must load synchronously in `<head>` before any vendor, and the ad waterfall binds `DOMContentLoaded` and guards itself with `dataset.waterfallDone`. Reordering them stops slots filling, silently and with no error |

Leave Email Obfuscation off as well; it injects an inline script that the
application's own Content-Security-Policy does not allow.

### 14.7 Firewall

The explorer container needs no inbound port open to the internet except 443, and that
only from Cloudflare:

```bash
ufw default deny incoming
ufw allow <ssh port>/tcp
for net in $(curl -s https://www.cloudflare.com/ips-v4 https://www.cloudflare.com/ips-v6); do
  ufw allow from $net to any port 443 proto tcp
done
ufw --force enable
```

On the MongoDB and daemon containers, allow 27017 and the RPC port only from the
explorer container's private address. Neither should be reachable from anywhere else.

### 14.8 Extra checks

In addition to the checklist in [section 13](#13-verification-checklist):

| Check | Expected |
|---|---|
| `curl -s https://<fqdn>/ \| grep -i 'rel="canonical"'` | an `https://` URL, not `http://` |
| `curl -s -o /dev/null -w '%{http_code}' https://<fqdn>/includes/header.php` | `403`, confirming `AllowOverride All` |
| `curl -s -o /dev/null -w '%{http_code}' https://<fqdn>/css/` | `403`, not a directory listing |
| `tail -1 /var/log/apache2/<sym>-access.log` | a visitor address, not a Cloudflare one |
| `/admin-console/` over HTTPS | login succeeds; the cookie is `Secure`, so plain HTTP never works |
| Ads enabled, visited from outside the EU | no consent wall, confirming `CF-IPCountry` arrives |
