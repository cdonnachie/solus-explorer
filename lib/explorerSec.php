<?php

use Decimal\Decimal;
include_once __DIR__ . '/common.php';

class Account {
    public $name;
    public $authenticated;
    public $enabled;
    public $groups;
    public $sid;
    public $ip;
    public $ua;
    public $throttled = false;

    public function __construct()
    {
        $this->name = null;
        $this->enabled = false;
        $this->authenticated = false;
        $this->groups = array();
        $this->sid = '';
        $this->ip = null;
        $this->ua = null;
    }

    public function __destruct()
    {}

    public function createAccount(string $name, string $client_hash, array $groups = []) : bool
    {
        $name = strtolower(trim($name));
        $client_hash = sec_normalizeClientHash($client_hash);
        if ($name === '' || $client_hash === null) return false;
        if (db_createUserAccount($name, sec_hashPassword($client_hash), $groups) !== true) return false;
        // A new password ends every session of the account.
        db_deleteUserSessions(['user' => $name]);
        return true;
    }



    public function authenticateSession(string $login, string $password, ?string $ip, ?string $ua) : bool
    {
        $this->name = strtolower(trim($login));
        $this->groups = array();
        $this->authenticated = false;
        $this->enabled = false;

        if (sec_loginThrottled((string)$ip, $this->name)) {
            $this->throttled = true;
            return false;
        }

        $acc = db_getUserAccountByName($this->name);
        $acc = (count($acc) > 0) ? $acc[0] : null;
        if (is_null($acc) || empty($acc->enabled)) {
            // Same work as a real check, so the response time does not reveal user names.
            password_verify(substr((string)$password, 0, 72), sec_dummyHash());
            db_recordLoginFailure((string)$ip, $this->name);
            return false;
        }
        $this->enabled = true;
        $this->groups = isset($acc->groups) ? $acc->groups : array();

        if (!sec_verifyPassword($this->name, $password, $acc)) {
            db_recordLoginFailure((string)$ip, $this->name);
            return false;
        }

        $this->authenticated = true;
        $this->sid = bin2hex(random_bytes(32));
        $this->ip = $ip;
        $this->ua = $ua;
        setcookie(sec_cookieName(), $this->sid, sec_cookieOptions(time() + sec_sessionMaxAge()));
        db_recordSession($this);
        return true;
    }

    public function destroySession() : bool
    {
        $sid = $_COOKIE[sec_cookieName()] ?? '';
        if (is_string($sid) && $sid !== '') {
            db_deleteUserSessions(['_id' => $sid]);
        }
        setcookie(sec_cookieName(), '', sec_cookieOptions(time() - 3600));
        return true;
    }

    public function checkSessionAuthenticated(?string $ip, ?string $ua) : bool
    {
        $sid = $_COOKIE[sec_cookieName()] ?? '';
        if (!is_string($sid) || !preg_match('/^[0-9a-f]{64}$/', $sid)) return false;

        $session = db_getUserSession($sid);
        if (count($session) == 0) return false;
        $session = $session[0];

        // Checked here as well as by the TTL index, which may be missing or
        // run late; sessions also have an absolute lifetime.
        $now = time();
        $last = isset($session->last_active) ? (int)($session->last_active->toDateTime()->getTimestamp()) : 0;
        $created = isset($session->created) ? (int)($session->created->toDateTime()->getTimestamp()) : 0;
        $bound_ua = isset($session->ua) ? (string)$session->ua : '';
        if ($now - $last > sec_sessionIdle() || $now - $created > sec_sessionMaxAge()
            || ($bound_ua !== '' && !hash_equals($bound_ua, (string)$ua))) {
            db_deleteUserSessions(['_id' => $sid]);
            return false;
        }

        $acc = db_getUserAccountByName((string)$session->user);
        if (count($acc) == 0 || empty($acc[0]->enabled)) return false;
        $acc = $acc[0];

        $this->name = $acc->_id;
        $this->enabled = true;
        $this->groups = isset($acc->groups) ? $acc->groups : array();
        $this->authenticated = true;
        $this->sid = $sid;
        $this->ip = $ip;
        $this->ua = $ua;
        db_recordSession($this);
        return true;
    }

}

/*
 * The browser (admin-console/index.php) and the CLI scripts send SHA-512 hex of the
 * password, never the password itself; that value is what gets hashed here.
 * Its first 72 hex digits (288 bits) are used because bcrypt reads at most
 * 72 bytes, so argon2id and the bcrypt fallback hash the same input.
 */
function sec_normalizeClientHash(string $client_hash) : ?string
{
    $client_hash = strtolower(trim($client_hash));
    return (strlen($client_hash) == 128 && ctype_xdigit($client_hash)) ? $client_hash : null;
}

function sec_passwordAlgo()
{
    return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
}

function sec_hashPassword(string $client_hash) : string
{
    return password_hash(substr($client_hash, 0, 72), sec_passwordAlgo());
}

/*
 * Accounts created before 2.0.228 store unsalted sha512(client hash). Those
 * still verify, and are rehashed on the first successful login.
 */
function sec_verifyPassword(string $name, string $client_hash, $acc) : bool
{
    $client_hash = sec_normalizeClientHash($client_hash);
    $stored = isset($acc->passwd) ? (string)$acc->passwd : '';
    if ($client_hash === null || $stored === '') return false;

    if (password_get_info($stored)['algoName'] !== 'unknown') {
        if (!password_verify(substr($client_hash, 0, 72), $stored)) return false;
        if (password_needs_rehash($stored, sec_passwordAlgo())) {
            db_setUserPassword($name, sec_hashPassword($client_hash));
        }
        return true;
    }

    if (!hash_equals($stored, hash('sha512', $client_hash, false))) return false;
    db_setUserPassword($name, sec_hashPassword($client_hash));
    return true;
}

function sec_readSecret(string $prompt) : string
{
    $tty = function_exists('stream_isatty') && stream_isatty(STDIN);
    if ($tty) shell_exec('stty -echo');
    print($prompt);
    $line = fgets(STDIN);
    if ($tty) { shell_exec('stty echo'); print(PHP_EOL); }
    return ($line === false) ? '' : rtrim($line, "\r\n");
}

/*
 * CLI admin-user prompt shared by createAdminUser.php and the create scripts.
 * Returns the process exit code.
 */
function sec_promptCreateAdminUser() : int
{
    $user = strtolower(trim((string)readline("Enter new username: ")));
    if ($user === '') { print("ERROR: The username is empty.\n"); return 1; }

    for ($try = 0; $try < 3; $try++) {
        $pass1 = sec_readSecret("Enter new password: ");
        if (strlen($pass1) < 8) { print("The password needs at least 8 characters.\n"); continue; }
        if (sec_readSecret("Repeat the password: ") !== $pass1) { print("The passwords do not match.\n"); continue; }

        $acc = new Account();
        if ($acc->createAccount($user, hash("sha512", $pass1, false), ['global_admin','tag_admin'])) {
            print("User ".$user." saved.\n");
            return 0;
        }
        print("ERROR: Saving user ".$user." failed; see the message above.\n");
        return 1;
    }
    print("ERROR: No valid password entered, user not saved.\n");
    return 1;
}

function sec_Authenticate(?string $ip, ?string $ua) : Account
{
    $acc = new Account(); 
    $acc->checkSessionAuthenticated($ip, $ua);
    return $acc;

}

function sec_InvalidateCookie() : bool
{
    $acc = new Account(); 
    return $acc->destroySession();
}


function sec_cookieName() : string
{
    global $settings;
    return hash('sha256', $settings['cookie_domain'].$settings['cookie_path'].'/'.ADMIN_PATH, false);
}

/* Host-only cookie (no Domain attribute), so other subdomains never receive it. */
function sec_cookieOptions(int $expires) : array
{
    global $settings;
    return [
        'expires'  => $expires,
        'path'     => $settings['cookie_path'].'/'.ADMIN_PATH.'/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Strict',
    ];
}

function sec_sessionIdle() : int
{
    global $settings;
    return (int)($settings['session_inactivity_sec'] ?? 86400);
}

function sec_sessionMaxAge() : int
{
    global $settings;
    return (int)($settings['session_max_age_sec'] ?? 259200);
}

/* At most 10 failed logins per address and 20 per user name in 15 minutes. */
function sec_loginThrottled(string $ip, string $user) : bool
{
    try {
        return db_countLoginFailures('ip', $ip, 900) >= 10 || db_countLoginFailures('user', $user, 900) >= 20;
    } catch (Throwable $e) {
        return false;
    }
}

/*
 * Hashes of random strings with PHP's default cost settings, verified against
 * for unknown user names so that they take as long as a wrong password.
 */
function sec_dummyHash() : string
{
    return (sec_passwordAlgo() === PASSWORD_BCRYPT)
        ? '$2y$10$TaK2Ht0fPSvjS/M/7IsuTe59AXHuJGWaXsVANddlFi1YMrmaWOA/m'
        : '$argon2id$v=19$m=65536,t=4,p=1$Y2Z1YWQxbW9yYWVqeHE4Zg$aLLitkR9uE7hwJawqcMw6FFF79qZSVpbW2aTWp+sjfc';
}

/*
 * Data endpoints that serve only the admin pages (in ADMIN_PATH/data/) call
 * this before doing anything else.
 */
function sec_requireAdmin()
{
    $session = sec_Authenticate(ui_GetUserIpAddress(), ui_GetUserAgent());
    if ($session->authenticated) return;
    http_response_code(401);
    header('Content-Type: application/json');
    echo '{"status":"error","error":{"code":401,"message":"Unauthorized"}}';
    exit;
}

/*
 * SameSite=Strict keeps the admin cookie off cross-site requests, but other
 * hosts of the same site (sibling subdomains) still get it. State-changing
 * admin requests must therefore come from the explorer's own origin.
 */
function sec_sameOrigin() : bool
{
    global $settings;
    $own = parse_url((string)($settings['explorer_address'] ?? ''));
    $src = parse_url((string)($_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '')));
    if (empty($own['host']) || empty($src['host'])) return false;
    $port = function($u){ return $u['port'] ?? ((($u['scheme'] ?? '') === 'http') ? 80 : 443); };
    return strcasecmp($own['host'], $src['host']) === 0
        && strtolower($own['scheme'] ?? '') === strtolower($src['scheme'] ?? '')
        && $port($own) === $port($src);
}

/*
 * The admin console runs read-only daemon methods only, so neither a stolen
 * session nor script running in the admin's browser can touch the wallet or
 * the chain. Methods that change peer state also need force=true.
 */
function sec_consoleMethodAllowed(string $method, array $params, bool $force) : bool
{
    static $read = [
        'help', 'uptime', 'getinfo', 'getmemoryinfo', 'getrpcinfo', 'getindexinfo', 'getzmqnotifications',
        'getblockchaininfo', 'getblockcount', 'getbestblockhash', 'getblock', 'getblockhash', 'getblockheader',
        'getblockstats', 'getchaintips', 'getchaintxstats', 'getdifficulty', 'getdeploymentinfo',
        'getmempoolinfo', 'getrawmempool', 'getmempoolentry', 'getmempoolancestors', 'getmempooldescendants',
        'gettxout', 'gettxoutsetinfo', 'gettxoutproof', 'verifytxoutproof', 'verifychain',
        'getrawtransaction', 'decoderawtransaction', 'decodescript', 'decodepsbt', 'validateaddress', 'estimatesmartfee',
        'getnetworkinfo', 'getpeerinfo', 'getconnectioncount', 'getnettotals', 'getaddednodeinfo', 'listbanned',
        'getnodeaddresses', 'getnetworkhashps', 'getmininginfo', 'getstakinginfo', 'getstakingstatus',
        'getaddressbalance', 'getaddresstxids', 'getaddressutxos', 'getaddressdeltas', 'getaddressmempool', 'getspentinfo',
        'getassetdata', 'listassets', 'listaddressesbyasset', 'listassetbalancesbyaddress', 'getcacheinfo',
        'listmasternodes', 'getmasternodecount', 'getmasternodewinners', 'getmasternodestatus', 'masternodelist',
        'getbudgetinfo', 'getbudgetprojection', 'getgovernanceinfo', 'getsupplyinfo', 'getblockindexstats',
    ];
    static $sub = [
        'masternode' => ['count', 'list', 'current', 'winners', 'winner'],
        'protx'      => ['list', 'info', 'diff'],
        'gobject'    => ['list', 'get', 'count'],
        'quorum'     => ['list', 'info'],
        'mnsync'     => ['status'],
        'spork'      => ['show', 'active'],
    ];
    static $peer = ['setban', 'disconnectnode', 'clearbanned'];

    if (in_array($method, $read, true)) return true;
    if (isset($sub[$method])) return in_array((string)($params[0] ?? ''), $sub[$method], true);
    return $force && in_array($method, $peer, true);
}
?>