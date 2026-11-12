<?php
/*
 * parityCheck.php - SEO/metadata parity harness.
 *
 * The migration oracle: records what every public route returns today, then proves a
 * rewritten stack returns the same. Checks HTTP status, <title>, meta description,
 * canonical, robots, Open Graph title/description and the JSON-LD block.
 *
 * It talks HTTP only, so it needs no database, no settings.php and no PHP extensions
 * beyond curl and json. Run it against any deployment, local or remote.
 *
 * Route parameters (a block height, a txid, an address) are discovered from the public
 * API at run time, so it works on any chain without hand-editing.
 *
 *   # record a baseline from the current PHP stack
 *   php tools/parityCheck.php --base=https://explorer.example.com --save=parity.json
 *
 *   # later, prove the new stack matches it
 *   php tools/parityCheck.php --base=https://new.example.com --check=parity.json
 *
 *   # or compare two live deployments directly (mixed-period cutover check)
 *   php tools/parityCheck.php --base=https://old.example.com --compare=https://new.example.com
 *
 * Options:
 *   --base=URL       deployment to probe (required), no trailing slash
 *   --save=FILE      write a baseline JSON file
 *   --check=FILE     compare against a baseline JSON file
 *   --compare=URL    compare --base against a second live deployment
 *   --only=a,b,c     restrict to these route ids
 *   --timeout=SEC    per-request timeout (default 20)
 *   --insecure       skip TLS verification (self-signed dev certs)
 *   --quiet          only print failures and the summary
 *
 * Exit code is 0 when everything matches, 1 on any difference or request failure, so it
 * can gate a cutover from CI or a shell loop.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "parityCheck.php is a CLI tool.\n"); exit(2); }
foreach (['curl', 'json'] as $ext) {
    if (!extension_loaded($ext)) { fwrite(STDERR, "The $ext extension is required.\n"); exit(2); }
}

/* ------------------------------------------------------------------ arguments */

$opt = [
    'base' => '', 'save' => '', 'check' => '', 'compare' => '',
    'only' => '', 'timeout' => '20', 'insecure' => false, 'quiet' => false,
];
foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--([a-z]+)=(.*)$/', $arg, $m) && array_key_exists($m[1], $opt)) {
        $opt[$m[1]] = $m[2];
    } elseif ($arg === '--insecure') { $opt['insecure'] = true;
    } elseif ($arg === '--quiet')    { $opt['quiet'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        // print the header comment as usage
        $src = file(__FILE__);
        foreach ($src as $line) {
            if (preg_match('/^\s*\*\/?/', $line)) { echo preg_replace('/^\s*\*ic?\/?\s?/', '', $line); }
            if (str_starts_with(ltrim($line), '*/')) { break; }
        }
        exit(0);
    } else {
        fwrite(STDERR, "Unknown argument: $arg (try --help)\n"); exit(2);
    }
}
$base = rtrim((string)$opt['base'], '/');
if ($base === '') { fwrite(STDERR, "--base=URL is required (try --help)\n"); exit(2); }
$timeout = max(1, (int)$opt['timeout']);
$quiet   = (bool)$opt['quiet'];

/* ------------------------------------------------------------------ http */

/**
 * One GET. Returns status, body and the headers parity cares about.
 * Never throws: a transport failure is reported as status 0 with the curl error.
 */
function fetch(string $url, int $timeout, bool $insecure): array
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,   // a redirect is itself a parity difference
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_HEADER         => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_USERAGENT      => 'solus-parity-check/1.0',
        CURLOPT_SSL_VERIFYPEER => !$insecure,
        CURLOPT_SSL_VERIFYHOST => $insecure ? 0 : 2,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        // no curl_close(): it has had no effect since PHP 8.0 and is deprecated in 8.5;
        // the handle is released when $ch goes out of scope
        return ['status' => 0, 'error' => curl_error($ch), 'body' => '', 'headers' => []];
    }
    $status   = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $hdrSize  = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    $headers = [];
    foreach (explode("\n", substr((string)$raw, 0, $hdrSize)) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = explode(':', $line, 2);
            $headers[strtolower(trim($k))] = trim($v);
        }
    }
    return ['status' => $status, 'error' => '', 'body' => substr((string)$raw, $hdrSize), 'headers' => $headers];
}

/* ------------------------------------------------------------------ extraction */

function firstMatch(string $re, string $s): ?string
{
    return preg_match($re, $s, $m) ? html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
}

function metaByName(string $body, string $name): ?string
{
    $n = preg_quote($name, '/');
    // attribute order varies, so try content-last and content-first
    return firstMatch('/<meta[^>]+name=["\']' . $n . '["\'][^>]+content=["\']([^"\']*)["\']/i', $body)
        ?? firstMatch('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']' . $n . '["\']/i', $body);
}

function metaByProperty(string $body, string $prop): ?string
{
    $p = preg_quote($prop, '/');
    return firstMatch('/<meta[^>]+property=["\']' . $p . '["\'][^>]+content=["\']([^"\']*)["\']/i', $body)
        ?? firstMatch('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . $p . '["\']/i', $body);
}

/**
 * The parity fingerprint of one response. Deliberately excludes anything that changes
 * per request (nonces, timings, block height) so reruns are stable.
 */
function fingerprint(array $res): array
{
    $body = $res['body'];
    $fp = [
        'status'         => $res['status'],
        'title'          => firstMatch('/<title[^>]*>(.*?)<\/title>/is', $body),
        'description'    => metaByName($body, 'description'),
        'keywords_set'   => metaByName($body, 'keywords') !== null,
        'canonical'      => firstMatch('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']*)["\']/i', $body),
        'robots'         => metaByName($body, 'robots') ?? ($res['headers']['x-robots-tag'] ?? null),
        'og_title'       => metaByProperty($body, 'og:title'),
        'og_description' => metaByProperty($body, 'og:description'),
        'content_type'   => strtolower(explode(';', (string)($res['headers']['content-type'] ?? ''))[0]),
    ];

    // JSON-LD: compare the parsed @type/@context, not the raw string, so whitespace and
    // key order do not register as differences.
    if (preg_match('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $body, $m)) {
        $ld = json_decode(trim($m[1]), true);
        $fp['ld_json'] = is_array($ld)
            ? ['valid' => true, 'type' => $ld['@type'] ?? null, 'keys' => (function ($a) { $k = array_keys($a); sort($k); return $k; })($ld)]
            : ['valid' => false];
    } else {
        $fp['ld_json'] = null;
    }
    return $fp;
}

/* ------------------------------------------------------------------ routes */

/**
 * Ask the public API for live identifiers, so the route list adapts to any chain.
 * Falls back to null and the dependent routes are skipped rather than failing.
 */
function discover(string $base, int $timeout, bool $insecure, bool $quiet): array
{
    $d = ['height' => null, 'blockhash' => null, 'txid' => null, 'address' => null, 'groupid' => null];

    $r = fetch("$base/api/getblockcount/", $timeout, $insecure);
    if ($r['status'] === 200) {
        $j = json_decode($r['body'], true);
        if (isset($j['blockcount'])) {
            // step back a little so the tip cannot move mid-run
            $d['height'] = max(0, (int)$j['blockcount'] - 10);
        }
    }
    if ($d['height'] !== null) {
        $r = fetch("$base/api/getblockhash/?index=" . $d['height'], $timeout, $insecure);
        if ($r['status'] === 200) {
            $j = json_decode($r['body'], true);
            if (!empty($j['hash'])) { $d['blockhash'] = $j['hash']; }
        }
        if ($d['blockhash'] !== null) {
            $r = fetch("$base/api/getblock/?hash=" . urlencode($d['blockhash']), $timeout, $insecure);
            if ($r['status'] === 200) {
                $j = json_decode($r['body'], true);
                if (!empty($j['tx'][0])) { $d['txid'] = is_array($j['tx'][0]) ? ($j['tx'][0]['txid'] ?? null) : $j['tx'][0]; }
            }
        }
    }
    $r = fetch("$base/api/getrichlist/?n=1", $timeout, $insecure);
    if ($r['status'] === 200) {
        $j = json_decode($r['body'], true);
        if (!empty($j[0]['addr'])) { $d['address'] = $j[0]['addr']; }
    }
    if ($d['address'] !== null) {
        $r = fetch("$base/api/getaddress/?address=" . urlencode($d['address']), $timeout, $insecure);
        if ($r['status'] === 200) {
            $j = json_decode($r['body'], true);
            if (!empty($j['groupid'])) { $d['groupid'] = (string)$j['groupid']; }
        }
    }

    if (!$quiet) {
        echo "Discovered parameters:\n";
        foreach ($d as $k => $v) { printf("  %-10s %s\n", $k, $v === null ? '(none - dependent routes skipped)' : (string)$v); }
        echo "\n";
    }
    return $d;
}

/**
 * The public route list. Query-string URLs are a published contract
 * (docs/integration.md), so they are asserted exactly as documented.
 */
function routes(array $d): array
{
    $r = [
        'overview'   => '/',
        'blocks'     => '/blocks/',
        'addresses'  => '/addresses/',
        'groups'     => '/groups/',
        'movements'  => '/movements/',
        'mining'     => '/mining/',
        'staking'    => '/staking/',
        'masternodes'=> '/masternodes/',
        'network'    => '/network/',
        'stats'      => '/stats/',
        'markets'    => '/markets/',
        'burnedcoins'=> '/burnedcoins/',
        'assets'     => '/assets/',
        'airdrop'    => '/airdrop/',
        'goldrush'   => '/goldrush/',
        'about'      => '/about/',
        'apidocs'    => '/api/',
        'sitemap'    => '/sitemap/',
        'err404'     => '/err/404/',
        'err402'     => '/err/402/',
        // negative cases: these must stay "not found" pages, not 500s
        'block_missing'   => '/block/?blockheight=999999999',
        'address_missing' => '/address/?address=thisisnotanaddress',
        'block_badparam'  => '/block/?blockheight=notanumber',
    ];
    if ($d['height'] !== null)    { $r['block_by_height'] = '/block/?blockheight=' . $d['height']; }
    if ($d['blockhash'] !== null) { $r['block_by_hash']   = '/block/?blockhash=' . rawurlencode($d['blockhash']);
                                    $r['block_raw']       = '/block/raw/?hash=' . rawurlencode($d['blockhash']); }
    if ($d['txid'] !== null)      { $r['tx']              = '/tx/?txid=' . rawurlencode($d['txid']);
                                    $r['tx_raw']          = '/tx/raw/?txid=' . rawurlencode($d['txid']); }
    if ($d['address'] !== null)   { $r['address']         = '/address/?address=' . rawurlencode($d['address']); }
    if ($d['groupid'] !== null)   { $r['addressgroup']    = '/addressgroup/?groupid=' . rawurlencode($d['groupid']); }
    return $r;
}

/* ------------------------------------------------------------------ comparison */

function flatten(array $fp, string $prefix = ''): array
{
    $out = [];
    foreach ($fp as $k => $v) {
        $key = $prefix === '' ? (string)$k : "$prefix.$k";
        if (is_array($v)) { $out += flatten($v, $key); }
        else { $out[$key] = $v; }
    }
    return $out;
}

function render($v): string
{
    if ($v === null)  { return '(absent)'; }
    if ($v === true)  { return 'true'; }
    if ($v === false) { return 'false'; }
    return (string)$v;
}

/** Field-by-field diff of two fingerprints. */
function diff(array $a, array $b): array
{
    $fa = flatten($a); $fb = flatten($b);
    $out = [];
    foreach (array_keys($fa + $fb) as $k) {
        $va = $fa[$k] ?? null; $vb = $fb[$k] ?? null;
        if ($va !== $vb) { $out[$k] = [render($va), render($vb)]; }
    }
    return $out;
}

/* ------------------------------------------------------------------ run */

$insecure = (bool)$opt['insecure'];
$disc     = discover($base, $timeout, $insecure, $quiet);
$all      = routes($disc);

if ($opt['only'] !== '') {
    $want = array_filter(array_map('trim', explode(',', (string)$opt['only'])));
    $all  = array_intersect_key($all, array_flip($want));
    if (!$all) { fwrite(STDERR, "--only matched no routes\n"); exit(2); }
}

$baseline = null;
if ($opt['check'] !== '') {
    if (!is_file((string)$opt['check'])) { fwrite(STDERR, "Baseline not found: {$opt['check']}\n"); exit(2); }
    $baseline = json_decode((string)file_get_contents((string)$opt['check']), true);
    if (!is_array($baseline) || !isset($baseline['routes'])) { fwrite(STDERR, "Baseline is not valid parity JSON\n"); exit(2); }
}
$other = rtrim((string)$opt['compare'], '/');

$results = [];
$fail = 0; $okCount = 0;

foreach ($all as $id => $path) {
    $res = fetch($base . $path, $timeout, $insecure);
    if ($res['status'] === 0) {
        printf("FAIL  %-18s request failed: %s\n", $id, $res['error']);
        $fail++; continue;
    }
    $fp = fingerprint($res);
    $results[$id] = ['path' => $path, 'fingerprint' => $fp];

    $expected = null; $label = '';
    if ($baseline !== null) {
        $expected = $baseline['routes'][$id]['fingerprint'] ?? null;
        $label = 'baseline';
        if ($expected === null) {
            if (!$quiet) { printf("NEW   %-18s not in baseline (status %d)\n", $id, $fp['status']); }
            continue;
        }
    } elseif ($other !== '') {
        $res2 = fetch($other . $path, $timeout, $insecure);
        if ($res2['status'] === 0) {
            printf("FAIL  %-18s comparison request failed: %s\n", $id, $res2['error']);
            $fail++; continue;
        }
        $expected = fingerprint($res2);
        $label = 'compare';
    }

    if ($expected === null) {
        // record-only mode
        if (!$quiet) { printf("ok    %-18s %d  %s\n", $id, $fp['status'], substr((string)($fp['title'] ?? '(no title)'), 0, 54)); }
        $okCount++;
        continue;
    }

    $d = diff($expected, $fp);
    if (!$d) {
        if (!$quiet) { printf("ok    %-18s matches %s\n", $id, $label); }
        $okCount++;
    } else {
        printf("DIFF  %-18s %d field(s) differ from %s\n", $id, count($d), $label);
        foreach ($d as $field => [$was, $now]) {
            printf("        %-16s %s: %s\n", $field, $label, $was);
            printf("        %-16s now:%*s%s\n", '', max(1, strlen($label) - 3), ' ', $now);
        }
        $fail++;
    }
}

if ($opt['save'] !== '') {
    $payload = [
        'generated_at' => gmdate('c'),
        'base'         => $base,
        'discovered'   => $disc,
        'routes'       => $results,
    ];
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false || file_put_contents((string)$opt['save'], $json . "\n") === false) {
        fwrite(STDERR, "Could not write {$opt['save']}\n"); exit(2);
    }
    printf("\nBaseline written to %s (%d routes)\n", (string)$opt['save'], count($results));
}

printf("\n%d checked, %d ok, %d problem(s)\n", count($all), $okCount, $fail);
exit($fail > 0 ? 1 : 0);
