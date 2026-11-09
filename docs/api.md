# Public HTTP API

Base path: `<explorer_address>/api/`. Every method is a folder with a trailing
slash, for example `https://explorer.example.com/doge/api/getblockcount/`. All methods
are `GET` unless noted; parameters go in the query string. Responses are JSON
(`application/json`) unless noted; most carry a Unix `timestamp`.

A browsable description with live examples is served at `/api/`.

## Access rules

| Rule | Behaviour |
|---|---|
| Rate limit | `api_max_requests_per_burst_period` successful calls per client IP per `api_burst_period_seconds` (defaults 15 per 5 s). When exceeded the body is `429 Too Many Requests`; the HTTP status stays 200 except on `/api/monitoring/ver/`, which sends a real 429. |
| Exempt IPs | `api_unlimited_ip_addresses` (defaults to localhost). Put the coin website here. |
| Blacklist | `api_blacklisted_ip_addresses` receive HTTP 551 with an empty body. |
| Missing parameter | HTTP 400, body `400 Bad Request`. |
| Subscription expired | HTTP 402 once a hosted subscription is more than 7 days past its end. |
| Client IP | `HTTP_CLIENT_IP`, then the raw `X-Forwarded-For` header, then `REMOTE_ADDR`. |

`legacy=1` on the chain methods switches the response to plain text containing only
the value.

## Chain

| Method | Parameters | Response |
|---|---|---|
| `getblockcount/` | `legacy` | `{"timestamp":…, "blockcount":278456}` |
| `getdifficulty/` | `legacy` | `{"timestamp", "difficulty_raw", "difficulty_hr", "dimension"}` |
| `getnetworkhashps/` | `legacy` | `{"timestamp", "hashrate_raw", "hashrate_hr", "dimension"}` |
| `getconnectioncount/` | `legacy` | `{"timestamp", "total", "inbound", "outbound", "ipv4", "ipv6"}` |
| `getblockhash/` | `index` (required), `legacy` | `{"timestamp", "index", "hash", "block_time"}`; text `Error: Block Not Found` |
| `getblock/` | `hash` or `index` | raw daemon `getblock` result |
| `getblocktemplate/` | none | raw daemon `getblocktemplate` result |
| `getrawtransaction/` | `txid` (required), `decode=1` | hex as `text/plain`, or the decoded transaction as JSON with `decode=1` |
| `sendrawtransaction/` **POST** | `hex` (required), `allowhighfees=true` | daemon result; `{"error":{"code":101}}` when `hex` is missing, `102` when it is not hex |
| `getaddnodelist/` | none | `text/plain`, one `addnode=ip:port` line per known peer |

## Supply and masternodes

| Method | Parameters | Response |
|---|---|---|
| `getcoinsupply/` | `legacy` | `{"timestamp", "coinsupply":"3082556.11798273"}` |
| `getcoinemission/` | `legacy` | currently identical to `getcoinsupply/` |
| `getmoneysupply/` | `p` (decimals, default 8) | legacy endpoint, always `text/plain`, bare number |
| `getmasternodecount/` | none | `{"timestamp", "masternodecount":{"total","enabled","ipv4","ipv6","onion"}}` |

## Addresses

| Method | Parameters | Response |
|---|---|---|
| `getbalance/` | `address` (required), `legacy` | `{"timestamp", "address", "balance"}`; text `Error: Address Not Found` |
| `getaddress/` | `address` (required) | `{"timestamp", "address", "balance", "received", "sent", "groupid", "assets":{…}, "last_txs":[{"tx_time","block_ix","txid","amount","is_reward"}]}` |
| `getaddressgroup/` | `groupid` (required), `withaddresslist=1` | `{"timestamp", "groupid", "balance", "address_count", "masternode_count", "masternode_list", "address_list"}` |
| `getrichlist/` | `n` (default 100, max 5000) | `[{"rank", "addr", "bal"}, …]` |

## Markets

Available when `load_markets` is true; otherwise the body is `not supported`.

| Method | Response |
|---|---|
| `getmarketcapitalization/` (also `getmarketcapitalisation/`) | `{"timestamp", "currencies":{"BTC", "USD", "EUR"}}` |
| `getmarketinfo/` | `{"timestamp", "max_price", "avg_price", "volume_base", "volume_coin", "percent_change", "metrics":[{"time","base","quote","latest","lowest_ask","highest_bid","percent_change","base_volume","quote_volume","exchange"}]}` |

## Assets (Ravencoin family)

Return HTTP 404 unless `rvn_assets` is enabled.

| Method | Parameters | Response |
|---|---|---|
| `getassetcount/` | none | `{"result", "timestamp", "assets":{…}}` counts per asset type |
| `getassetinfo/` | `name` or `key`, `withtopholders=1` | `{"result", "timestamp", "data":{…, "holders_count", "top100_holders"}}` |
| `getassetholders/` | `name` or `key` | full `holders` map |
| `getaddressmessages/` | `address`, `since` (Unix time, default 24 h ago) | `{"result", "data":[{"timestamp","tx","asset","message"}]}` |
| `getassetmovements/` | `name`, `strict=true` | movements of the last 24 h |
| `getlastcreatedassets/` | none | assets created in the last 24 h |
| `getassetdistributionranking/`, `getassetconsoligationranking/` | none | top 100 assets by holder gain or loss over 24 h |

## Monitoring

| Method | Response |
|---|---|
| `monitoring/ver/` | `{"t", "version", "coin"}` |
| `monitoring/getsummary/` | host CPU, memory, swap and disk, daemon height, hashrate and connections, database height, explorer version, subscription status, and `loader_abort` (`time`, `time_tz`, `height`, `reason`) while the block loader is stopped, otherwise `null`. Rate-limited, no subscription check. |
| `monitoring/uxm/` **POST JSON** | browser vitals beacon sent by the pages; not for external use |
| `monitoring/adsmetrics/` **POST JSON** | ad-fill beacon sent by the pages; not for external use |

## Examples

```bash
curl -s https://explorer.example.com/doge/api/getblockcount/
# {"timestamp":1761130000,"blockcount":5612345}

curl -s 'https://explorer.example.com/doge/api/getblockcount/?legacy=1'
# 5612345

curl -s 'https://explorer.example.com/doge/api/getaddress/?address=<address>'

curl -s -X POST -d 'hex=0200000001…' https://explorer.example.com/doge/api/sendrawtransaction/
```

Every call is logged to the `APILOG` collection and summarised under
`/admin-console/usestats/`.
