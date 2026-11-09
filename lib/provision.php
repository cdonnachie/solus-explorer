<?php
/*
 * Shared steps of script/create.php and script/create-upstream.php.
 *
 * Every step first checks whether its result already exists and skips it, so
 * the scripts can run after a manual install or a second time. Any failure
 * stops with exit code 1, and Apache is reloaded only after a config test.
 */

$prov_step = 0;
$prov_apache_changed = false;
$prov_enabled_now = array();

function prov_step(string $title){
    global $prov_step;
    $prov_step++;
    print(PHP_EOL.$prov_step.". ".$title.PHP_EOL);
}

function prov_note(string $text){
    print("   ".$text.PHP_EOL);
}

function prov_fail(string $message, array $output = array()){
    foreach ($output as $line){ prov_note("| ".$line); }
    print("   ERROR: ".$message.PHP_EOL);
    exit(1);
}

function prov_exec(string $cmd, &$output = null) : int {
    $output = array();
    $ec = -1;
    exec($cmd." 2>&1", $output, $ec);
    return $ec;
}

function prov_run(string $cmd, string $what) : array {
    $ec = prov_exec($cmd, $output);
    if ($ec !== 0){ prov_fail($what." failed (exit code ".$ec.").", $output); }
    return $output;
}

function prov_ask(string $prompt, string $default = '') : string {
    $in = trim((string)readline("   ".$prompt.($default !== '' ? " [".$default."]" : "").": "));
    return ($in === '') ? $default : $in;
}

function prov_confirm(string $prompt, bool $default) : bool {
    $in = strtolower(prov_ask($prompt." (".($default ? "Y/n" : "y/N").")"));
    return ($in === '') ? $default : ($in[0] === 'y');
}

function prov_requireRoot(){
    if (PHP_SAPI !== 'cli'){ exit(1); }
    if (trim((string)shell_exec('id -u')) !== '0'){ prov_fail("Run this script as root."); }
    if (!file_exists(__DIR__.'/../settings.php')){ prov_fail("settings.php is missing; create it first (docs/installation.md section 7)."); }
}

function prov_coinUser() : string {
    global $settings;
    $u = strtolower((string)$settings["coin_symbol"]);
    if (!preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $u)){
        prov_fail("coin_symbol '".$settings["coin_symbol"]."' cannot be used as an OS user name.");
    }
    return $u;
}

/* The web root of this checkout, whatever its folder is called. */
function prov_documentRoot() : string {
    $root = realpath(__DIR__.'/../public');
    if ($root === false || !is_dir($root)){ prov_fail("Cannot find public/ next to lib/."); }
    if (strpbrk($root, "\"\\\n") !== false){ prov_fail("The checkout path ".$root." contains characters Apache cannot take."); }
    return $root;
}

function prov_osUser(string $u){
    prov_step("OS user '".$u."'");
    if (prov_exec("id -u ".escapeshellarg($u)) === 0){
        prov_note("exists, skipped");
        return;
    }
    prov_run("useradd -m -s /bin/bash ".escapeshellarg($u), "useradd");
    prov_note("created");
}

function prov_daemonFolder(string $u){
    $df = '/usr/local/bin/'.$u;
    prov_step("Daemon folder '".$df."'");
    if (is_dir($df)){
        prov_note("exists");
    } else {
        if (!@mkdir($df, 0755, true)){ prov_fail("Cannot create ".$df."."); }
        prov_note("created");
    }
    prov_run("chown ".escapeshellarg($u.":".$u)." ".escapeshellarg($df), "chown");
    prov_note("owned by ".$u);
}

function prov_mongoManager(array $auth = array()) : MongoDB\Driver\Manager {
    global $settings;
    $opts = (isset($settings['db_opts']) && is_array($settings['db_opts'])) ? $settings['db_opts'] : array();
    $opts['serverSelectionTimeoutMS'] = 5000;
    return new MongoDB\Driver\Manager($settings['db_protocol']."://".$settings['db_host'].":".$settings['db_port'], array_merge($opts, $auth));
}

function prov_createDbUser(MongoDB\Driver\Manager $m){
    global $settings;
    $m->executeCommand($settings['db_name'], new MongoDB\Driver\Command([
        'createUser' => $settings['db_user'],
        'pwd'        => $settings['db_pass'],
        'roles'      => [['role' => 'readWrite', 'db' => $settings['db_name']]],
    ]));
}

function prov_databaseUser(){
    global $settings;
    prov_step("Database user '".$settings['db_user']."' on '".$settings['db_name']."'");
    if (db_checkDbConnected()){
        prov_note("logs in with the settings.php credentials, skipped");
        return;
    }
    try {
        prov_createDbUser(prov_mongoManager());
    } catch (MongoDB\Driver\Exception\ConnectionTimeoutException $e){
        prov_fail("MongoDB is not reachable at ".$settings['db_host'].":".$settings['db_port'].". Start it (docs/installation.md section 3) and run this script again.");
    } catch (MongoDB\Driver\Exception\Exception $e){
        if ($e->getCode() == 51003){
            prov_fail("The user exists, but db_pass in settings.php does not match its password. Fix db_pass, or reset it with db.getSiblingDB('".$settings['db_name']."').changeUserPassword(...).");
        }
        // Access control is on and the localhost exception no longer applies.
        prov_note("MongoDB needs an administrator login to create the user (".$e->getMessage().").");
        $admin = prov_ask("MongoDB admin user, blank to stop");
        if ($admin === ''){ prov_fail("Create the database user by hand (docs/installation.md section 8) and run this script again."); }
        $pw = sec_readSecret("   Password for ".$admin.": ");
        try {
            prov_createDbUser(prov_mongoManager(['username' => $admin, 'password' => $pw, 'authSource' => 'admin']));
        } catch (MongoDB\Driver\Exception\Exception $e2){
            if ($e2->getCode() == 51003){
                prov_fail("The user exists, but db_pass in settings.php does not match its password.");
            }
            prov_fail("createUser failed: ".$e2->getMessage());
        }
    }
    if (!db_checkDbConnected()){
        prov_fail("The user was created but cannot log in; check db_host, db_port and db_name in settings.php.");
    }
    prov_note("created");
}

function prov_collections(){
    global $dbm, $settings;
    prov_step("Database collections");
    db_checkCollections($dbm, $settings['db_name']);
}

/*
 * Writes a vhost file only if it does not exist. An existing file is never
 * overwritten, because operators (and certbot) edit these files.
 */
function prov_writeVhost(string $name, ?string $content){
    global $prov_apache_changed;
    $path = "/etc/apache2/sites-available/".$name;
    if (file_exists($path)){
        prov_note($path.(file_get_contents($path) === $content ? " exists, unchanged" : " exists, left as it is"));
        return;
    }
    if ($content === null){ prov_fail($path." is missing."); }
    if (@file_put_contents($path, $content) === false){ prov_fail("Cannot write ".$path."."); }
    $prov_apache_changed = true;
    prov_note($path." written");
}

function prov_enableSite(string $name){
    global $prov_apache_changed, $prov_enabled_now;
    if (file_exists("/etc/apache2/sites-enabled/".$name)){
        prov_note($name." already enabled");
        return;
    }
    prov_run("a2ensite -q ".escapeshellarg($name), "a2ensite ".$name);
    $prov_enabled_now[] = $name;
    $prov_apache_changed = true;
    prov_note($name." enabled");
}

function prov_enableModule(string $mod){
    global $prov_apache_changed;
    if (file_exists("/etc/apache2/mods-enabled/".$mod.".load")){ return; }
    prov_run("a2enmod -q ".escapeshellarg($mod), "a2enmod ".$mod);
    $prov_apache_changed = true;
    prov_note("module ".$mod." enabled");
}

function prov_disableEnabledNow() : string {
    global $prov_enabled_now;
    foreach ($prov_enabled_now as $name){ prov_exec("a2dissite -q ".escapeshellarg($name)); }
    return empty($prov_enabled_now) ? "" : "; ".implode(", ", $prov_enabled_now)." disabled again";
}

function prov_apacheActive() : bool {
    sleep(2);
    return prov_exec("systemctl is-active --quiet apache2") === 0;
}

/*
 * A graceful reload with a broken config stops Apache, so test first. Some
 * faults (a bad certificate, a port in use) pass configtest and only show at
 * the reload; then the sites enabled by this run are disabled again and
 * Apache is started with the config it had before.
 */
function prov_reloadApache(){
    global $prov_apache_changed;
    if (!$prov_apache_changed){
        prov_note("nothing changed, Apache not reloaded");
        return;
    }
    if (prov_exec("apache2ctl configtest", $output) !== 0){
        prov_fail("Apache config test failed".prov_disableEnabledNow().". Apache was not reloaded.", $output);
    }
    $was_active = (prov_exec("systemctl is-active --quiet apache2") === 0);
    prov_exec($was_active ? "systemctl reload apache2" : "systemctl start apache2");
    if (prov_apacheActive()){
        prov_note($was_active ? "Apache reloaded" : "Apache started");
        return;
    }
    prov_exec("tail -n 5 /var/log/apache2/error.log", $log);
    $undo = prov_disableEnabledNow();
    prov_exec("systemctl start apache2");
    prov_fail("Apache stopped after the ".($was_active ? "reload" : "start").$undo."; Apache ".(prov_apacheActive() ? "is running again with its previous sites" : "is still down, check 'systemctl status apache2'").".", $log);
}

/* Checks the pair before Apache sees it: a bad certificate passes configtest. */
function prov_checkCertPair(string $cert, string $key){
    if (!function_exists('openssl_x509_read')){ return; }
    $x = @openssl_x509_read((string)file_get_contents($cert));
    if ($x === false){ prov_fail($cert." is not a PEM certificate."); }
    $k = @openssl_pkey_get_private((string)file_get_contents($key));
    if ($k === false){ prov_fail($key." is not an unencrypted PEM private key."); }
    if (!openssl_x509_check_private_key($x, $k)){ prov_fail($key." does not belong to ".$cert."."); }
}

/* True if something other than this vhost file already uses the port. */
function prov_portTaken(int $port, string $own_file) : bool {
    if (prov_exec("ss -Hltn ".escapeshellarg("sport = :".$port), $out) === 0 && count(array_filter($out, 'trim')) > 0){
        return true;
    }
    prov_exec("grep -rlE ".escapeshellarg("^\\s*Listen\\s+([0-9.:\\[\\]]*:)?".$port."\\b")." /etc/apache2", $files);
    foreach ($files as $f){
        if (realpath($f) !== realpath($own_file)){ return true; }
    }
    return false;
}

function prov_adminUser(){
    global $dbm, $settings;
    prov_step("Web admin user");
    $n = 0;
    try {
        $n = (int)$dbm->executeCommand($settings['db_name'], new MongoDB\Driver\Command(['count' => 'USERS']))->toArray()[0]->n;
    } catch (MongoDB\Driver\Exception\Exception $e){}
    if ($n > 0 && !prov_confirm("USERS already has ".$n." account(s). Create or reset one now?", false)){
        prov_note("skipped");
        return 0;
    }
    return sec_promptCreateAdminUser();
}

function prov_daemonConfig(array $extra = array()){
    global $settings;
    $u = prov_coinUser();
    $lines = array_merge([
        "rpcuser=".$settings["rpc_user"],
        "rpcpassword=".$settings["rpc_pass"],
        "rpcport=".$settings["rpc_port"],
        "rpcconnect=127.0.0.1",
        "rpcbind=127.0.0.1",
        "rpcallowip=127.0.0.1",
        "rpcthreads=32",
    ], $extra, [
        "daemon=1",
        "listen=1",
        "server=1",
        "txindex=1",
        "# PIVX and Dash family only; Bitcoin Core ignores these with a warning",
        "staking=0",
        "maxreorg=".$settings["maturity"],
        "banscore=3600",
    ]);
    prov_step("Daemon config: ~".$u."/.<coin>/<coin>.conf, owned by ".$u);
    print(implode(PHP_EOL, $lines).PHP_EOL);
}
?>
