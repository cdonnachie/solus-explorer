# Solus (ngex) Explorer Manual

Solus is a PHP block explorer for Bitcoin-derived chains (PoW, PoS, masternode and
DIP2 coins, Ravencoin-style assets). One checkout serves one coin. The code lives in
`lib/`, `public/` and `script/`; everything coin-specific lives in a single
`settings.php` at the repository root.

| Document | Read it when you |
|---|---|
| [installation.md](installation.md) | set up a new host or a new coin from scratch |
| [configuration.md](configuration.md) | need the meaning of a `settings.php` key |
| [integration.md](integration.md) | publish the explorer behind a reverse proxy, brand it, enable ads or analytics, link to it from a coin website |
| [operations.md](operations.md) | run the loaders, handle reorgs, read logs, upgrade, troubleshoot |
| [api.md](api.md) | consume the public HTTP API |
| [json-contract.md](json-contract.md) | add or rewrite an internal JSON endpoint (`_dp/`, `_up/`) |
| [examples/](examples/) | copy a firewall or Apache template |
| [THIRD-PARTY-NOTICES.md](../THIRD-PARTY-NOTICES.md) | need the version or license of a bundled or CDN-loaded library |

## How the pieces fit

```
coin daemon (RPC, txindex=1)
        │  JSON-RPC over 127.0.0.1
        ▼
script/loaderMongo-blocks.php   ── every minute (cron) ──▶  MongoDB
script/loaderMongo-metrics.php  ── every 5 minutes      ──▶  MongoDB
        ▲                                                    │
        │ settings.php                                       │
        ▼                                                    ▼
Apache + mod_php  ◀── public/   (pages, /api/, /admin-console/)  ◀── lib/
```

* **Loaders** are run-once CLI scripts started by cron. They index blocks, keep
  rollback data for reorgs, collect market, chain and host metrics, and rebuild the
  page caches.
* **Web tier** is plain Apache with mod_php. Every page is a folder with an
  `index.php`; no URL rewriting is needed.
* **Database** is MongoDB (6.0 or newer, 8.0 recommended), the only supported backend.

## Supported platforms

| Component | Supported | Notes |
|---|---|---|
| OS | Ubuntu 22.04, Ubuntu 24.04 | Debian works the same way |
| PHP | 8.1 to 8.4 | minimum 8.0; 8.5 is untested |
| PHP extensions | mongodb, decimal, curl, json, ctype, hash, readline | `decimal` needs libmpdec |
| MongoDB | 6.0, 7.0, 8.0 | replica set optional (`db_opts`) |
| Web server | Apache 2.4 + mod_php (prefork) | nginx and PHP-FPM are not supported |

The version string is in `lib/common.php` and shown at `/api/monitoring/ver/`.
