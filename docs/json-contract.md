# JSON contract

How this explorer's JSON endpoints must be shaped. It governs the endpoints the
frontend migration introduces and the ones it rewrites — today's DataTables providers in
`public/_dp/` and the polling endpoints in `public/_up/`.

It does **not** govern `public/api/`. Those 27 endpoints are a published contract,
documented in [api.md](api.md) and consumed by wallets and coin websites. Their quirks
(`Content-Type: text/plain`, the non-standard `551` status, bare scalar responses) are
frozen. Do not "fix" them; new consumers use the endpoints described here.

The rules below exist because the current server-to-client contract is HTML, not data:
`_dp` responses carry pre-rendered markup in every cell and `_up` responses carry whole
`<tbody>` strings. A typed client cannot be built against that, and no renderer can be
replaced while the server decides how values look.

## 1. Money is a decimal string. Never a JSON number.

```json
{ "balance": "21000000.12345678", "fee": "0.00001000" }
```

A JSON number is an IEEE-754 double in every mainstream parser. `JSON.parse` on
`21000000.12345678` already loses the last digit on some chains, and a coin with a large
supply and 8 decimals exceeds a double's 15–17 significant digits outright. Silent,
unreproducible balance drift is the single worst failure this project can ship, so the
wire type for every amount is a string.

This applies to balances, amounts, rewards, fees, supply figures, hashrate and
difficulty — anything that is not a count.

**Where to read the value from.** Amounts live in MongoDB as `Decimal128`. Cast that
directly:

```php
$out["balance"] = (string)$doc->balance;      // correct
```

**Do not route amounts through `splitFloat()`.** It is a *display* helper. Both of its
branches pass the value through a PHP float — the numeric branch uses `round()` and
float arithmetic, and the `Decimal128` branch calls `number_format()`, whose first
parameter is a `float`. So `->number` is a float by construction and `->string` has been
through one. Neither is a safe source for an exact amount.

**Do not round on the way out.** Emit the stored value at full `processing_precision` and
let the client format. `overview_precision` and `default_precision` are presentation
settings and belong to the renderer, not the payload.

**Arithmetic stays in PHP**, using `cdec()` / `Decimal\Decimal` as the loaders do. Never
re-implement coin arithmetic in JavaScript against these strings.

### The `splitFloat` fields in the caches

`ui_CreateOverviewCache()` and its siblings store `splitFloat()` *objects*
(`{whole, fraction, number, string}`), not numbers, for **24 distinct fields across 50
assignment sites** — listed in Appendix A.1 of the migration plan. For those, either add
the raw value alongside in the loader (backwards compatible, preferred) or read
`->string` and accept that it has been through a float.

Do **not** rewrite the cache schema: it would force a cache rebuild on every operator's
deployment.

## 2. Time is unix seconds, as a JSON number

```json
{ "time": 1794413375 }
```

Integer seconds UTC, the daemon's own unit. No ISO strings, no milliseconds, no
pre-formatted "3 hours ago" — relative times are a rendering decision and they make
responses uncacheable and untestable. The server may add a sibling field for a value the
client cannot derive, never instead of the timestamp.

## 3. No HTML in payloads

No tags, no `<a href>`, no badge markup, no icon classes, no `&nbsp;`. Emit the data the
markup was built from and let the client render it:

```json
{ "type": "PoW", "confirmations": 212, "has_op_return": true }
```

not

```json
{ "type": "<i class=\"fas icons-pike text-info mx-1\" title=\"PoW Block\"></i>" }
```

This is the rule the whole migration rests on. Values are never pre-escaped either:
escaping belongs to the renderer, which knows the context. A payload that is both
escaped and then rendered as text shows `&amp;` to the user.

## 4. One envelope

Every response is a JSON object — never a bare array or scalar, so fields can be added
later without breaking parsers.

```json
{
  "status": "ok",
  "data": { },
  "meta": { "processingtime_ms": 14 }
}
```

- `status` — `"ok"` or `"error"`.
- `data` — the payload. An object, or an array for a collection.
- `meta` — optional, non-essential: timings, totals, cache age. A client must work
  without it.

Paged collections carry paging in `meta`:

```json
{
  "status": "ok",
  "data": [ ],
  "meta": { "total": 5382763, "offset": 0, "limit": 100, "processingtime_ms": 14 }
}
```

`total` is a **count of rows**, not the highest id. `_dp/blocksdp.php` used to set
`recordsTotal` from `db_getLastDbBlockNumber()`, the highest block *index*, and so
undercounted by one; it now reports the count. That is not cosmetic where paging is
computed from it — one out makes the final page unreachable.

Where a provider caps paging, keep the cap: `blocksdp.php` bounds `skip` at 100,000
because a MongoDB skip walks and discards, and the endpoint is public and
unauthenticated, so an uncapped skip lets a client make the database walk millions of
documents per request. But a cap on a newest-first list falls on the *oldest* rows,
which is where "last page" points — so do not simply clamp and serve whatever that
lands on. `blocksdp.php` answers pages near the far end from the opposite sort order,
where they are a skip of a few, and reverses the rows; only the unreachable middle is
clamped. A new endpoint should do the same, or expose a cursor instead of an offset.

The existing DataTables envelope (`draw` / `recordsTotal` / `recordsFiltered` / `data`)
stays for as long as DataTables is the consumer. While an endpoint serves both, put the
DataTables keys at the top level and the new shape under `data`; drop them with the
table.

## 5. One error shape

```json
{ "status": "error", "error": "Unauthorized" }
```

A non-2xx HTTP status and that body. `error` is a short, stable, human-readable
string; it must never contain a file path, a stack trace, a query or a daemon message,
all of which leak implementation detail to an unauthenticated caller. Log the detail
server-side and return something generic.

Use the status the situation means: `400` for a malformed parameter, `404` for a resource
that does not exist, `401` for a failed origin or session check, `429` when rate-limited,
`500` only for a genuine server fault.

**A missing parameter is a `400`, not a crash.** `_dp/txlistdp.php` currently reads
`blockhash` unguarded and then calls `count()` on an undefined variable, so a request
without it returns **HTTP 500 with an empty body**. Validate every parameter before use:

```php
$hash = (string)($_GET["blockhash"] ?? "");
if (!preg_match('/^[0-9a-f]{64}$/', $hash)) {
    http_response_code(400);
    echo '{"status":"error","error":"Invalid blockhash"}';
    exit;
}
```

Guard the parent, not only the child. `_up/overviewup.php` tests
`isset($data->market_data->capitalization->in_usd)` one line after dereferencing
`$data->market_data->timestamp` unguarded — so with `load_markets => false`, the default
in every shipped template, each poll logs a warning.

## 6. Headers

Every endpoint sends:

| Header | Value | Why |
|---|---|---|
| `Content-Type` | `application/json` | Only 6 of the 24 `_dp` providers set it today. |
| `X-Robots-Tag` | `noindex` | These are not pages and must not be indexed. |
| `Cache-Control` | `no-store` | See below. |

`no-store` is deliberate on anything reachable from an ad-bearing page: a cached response
breaks the per-request `rid`, the ad auction and the consent decision, which reads
`CF-IPCountry` per request. Where a value is genuinely static — a finished block, a
confirmed transaction — a short `max-age` is fine, but state the reason in a comment.

## 7. Who may call

These endpoints exist for this site's own pages, so they check the caller:

```php
if (!sec_sameOrigin() || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(401);
    echo '{"status":"error","error":"Unauthorized"}';
    exit;
}
```

None of the 24 `_dp` providers does this today. [labelsup.php](../public/admin-console/labels/labelsup.php)
is the pattern to copy.

Two cautions:

- `public/admin-console/loaderlog/` consumes `_dp` endpoints too. Tightening the origin
  check must not lock the admin console out.
- Admin endpoints additionally require `sec_Authenticate()`. The admin cookie is
  path-scoped to `/admin-console/`, so it never reaches a public endpoint — a public
  endpoint can therefore never be authenticated, and must never be given a privileged
  code path.

No CORS headers. If a third party needs data, that is what `public/api/` is for.

## 8. Naming

`snake_case`, matching the database field names, so a payload can be read against the
collection it came from. Counts end `_count`, timestamps `_time` or `time`, booleans read
as assertions (`has_op_return`, `is_reward`). Absent is `null`, never `""`, `0` or `-1`:
`0` is a real balance and `-1` is a sentinel a client will eventually render.

Field names are additive. Adding a field is safe at any time; renaming or removing one
is a breaking change and needs a new endpoint.

## 9. Checklist for a new or rewritten endpoint

- [ ] `Content-Type: application/json`, `X-Robots-Tag: noindex`, `Cache-Control: no-store`
- [ ] Origin and method checked; admin endpoints also authenticated
- [ ] Every parameter validated, with `400` on bad input and no uncaught error anywhere
- [ ] Amounts as decimal strings, read straight from `Decimal128`, unrounded
- [ ] Timestamps as unix seconds
- [ ] No HTML and no pre-escaped text
- [ ] Object envelope, `status`, `data`, optional `meta`
- [ ] `meta.total` is a row count
- [ ] Errors use the single error shape and leak nothing
- [ ] Covered by the parity harness ([../tools/parityCheck.php](../tools/parityCheck.php))
