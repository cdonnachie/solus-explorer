<?php
/*
 * Provisions a stand-alone explorer host (docs/installation.md section 8):
 * OS user, daemon folder, database user, collections, Apache virtual hosts
 * and the admin user. Steps whose result exists are skipped, so it is safe
 * to run after a manual install or a second time. Run it as root from script/.
 */
require '../lib/provision.php';
prov_requireRoot();
require '../settings.php';
global $settings;
require '../lib/explorerDb.php';
require '../lib/explorerUi.php';
require '../lib/explorerSec.php';
global $dbm;

$u = prov_coinUser();
$docroot = prov_documentRoot();

prov_osUser($u);
prov_daemonFolder($u);
prov_databaseUser();
prov_collections();

prov_step("Apache virtual hosts");
$fqdn = (string)parse_url($settings["explorer_address"], PHP_URL_HOST);
if ($fqdn == ""){ $fqdn = prov_ask("Explorer host name"); }
if (!preg_match('/^[A-Za-z0-9.-]+$/', $fqdn)){ prov_fail("'".$fqdn."' is not a host name."); }
prov_note("host ".$fqdn.", DocumentRoot ".$docroot);

$vhost_body = "    ServerName ".$fqdn."
    ServerAdmin webmaster@localhost
    DocumentRoot \"".$docroot."\"
    ErrorDocument 404 /err/404/index.php
    ErrorLog \${APACHE_LOG_DIR}/".$u."-error.log
    CustomLog \${APACHE_LOG_DIR}/".$u."-access.log combined
    <Directory \"".$docroot."\">
        AllowOverride All
        Require all granted
        Options -MultiViews
    </Directory>
";
prov_writeVhost($fqdn.".conf", "<VirtualHost *:80>\n".$vhost_body."</VirtualHost>\n");
prov_enableSite($fqdn.".conf");

$certbot_hint = false;
if (file_exists("/etc/apache2/sites-available/".$fqdn."-le-ssl.conf")){
    prov_note($fqdn."-le-ssl.conf from certbot exists, no TLS vhost written");
} else if (file_exists("/etc/apache2/sites-available/".$fqdn."-ssl.conf")){
    prov_writeVhost($fqdn."-ssl.conf", null);
    prov_enableSite($fqdn."-ssl.conf");
} else {
    $cert_dir = rtrim(prov_ask("TLS certificate folder (origin.pem, privkey.pem)", "/etc/cert/".$fqdn), "/");
    $cert = $cert_dir."/origin.pem";
    $key  = $cert_dir."/privkey.pem";
    if (is_readable($cert) && is_readable($key)){
        prov_checkCertPair($cert, $key);
        prov_enableModule("ssl");
        prov_writeVhost($fqdn."-ssl.conf", "<VirtualHost *:443>\n".$vhost_body."    SSLEngine On\n    SSLCertificateFile ".$cert."\n    SSLCertificateKeyFile ".$key."\n</VirtualHost>\n");
        prov_enableSite($fqdn."-ssl.conf");
    } else {
        prov_note("No readable ".$cert." and ".$key.".");
        if (!prov_confirm("Continue with the HTTP vhost only and add TLS later with certbot?", true)){
            prov_fail("Put the certificate files in place and run this script again.");
        }
        $certbot_hint = true;
    }
}
prov_reloadApache();
if ($certbot_hint){
    prov_note("Add TLS later: certbot --apache -d ".$fqdn." (the admin area needs HTTPS).");
}

prov_daemonConfig();

exit(prov_adminUser());
?>
