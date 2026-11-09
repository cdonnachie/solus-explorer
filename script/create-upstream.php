<?php
/*
 * Provisions an upstream node for the sub-path layout (docs/integration.md):
 * OS user, daemon folder, database user, collections, the upstream Apache
 * vhost and the admin user. Steps whose result exists are skipped, so it is safe
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

prov_step("Apache upstream virtual host");
$vhost_name = $u.".upstream.conf";
$vhost_path = "/etc/apache2/sites-available/".$vhost_name;
$h = trim((string)exec("hostname"));
$fqdn = prov_ask("This node's FQDN", trim((string)exec("hostname -f")));
if (!preg_match('/^[A-Za-z0-9.-]+$/', $fqdn)){ prov_fail("'".$fqdn."' is not a host name."); }
$default_port = '';
if (file_exists($vhost_path) && preg_match('/^\s*Listen\s+(\d+)/m', (string)file_get_contents($vhost_path), $mm)){
    $default_port = $mm[1];
}
$uport = prov_ask("Upstream listener port", $default_port);
if (!ctype_digit($uport) || (int)$uport < 1024 || (int)$uport > 65535){ prov_fail("'".$uport."' is not a port between 1024 and 65535."); }
$pub = (string)parse_url($settings["explorer_address"], PHP_URL_HOST);
if ($pub == ""){ $pub = prov_ask("Public host name of the front proxy"); }
prov_note("DocumentRoot ".$docroot);

if (!file_exists($vhost_path)){
    $cert = "/etc/cert/".$h."/".$fqdn.".pem";
    $key  = "/etc/cert/".$h."/".$fqdn.".key";
    if (!is_readable($cert) || !is_readable($key)){
        prov_fail("No readable ".$cert." and ".$key.". Create them with: mkdir -p /etc/cert/".$h." && cd /etc/cert/".$h." && bash ".realpath(__DIR__)."/gencert.sh ".$fqdn);
    }
    prov_checkCertPair($cert, $key);
    if (prov_portTaken((int)$uport, $vhost_path)){
        prov_fail("Port ".$uport." is already in use or has a Listen line elsewhere in /etc/apache2. Pick another port.");
    }
    prov_enableModule("ssl");
    prov_enableModule("rewrite");
    prov_writeVhost($vhost_name, "Listen ".$uport."

<VirtualHost *:".$uport.">
    ServerName ".$fqdn."

    DocumentRoot \"".$docroot."\"
    DirectoryIndex index.html index.php

    <Directory \"".$docroot."\">
        AllowOverride All
        Require all granted
        Options -MultiViews
    </Directory>

    ErrorDocument 404 /err/404/index.php
    ErrorLog  \${APACHE_LOG_DIR}/".$u."-error.log
    CustomLog \${APACHE_LOG_DIR}/".$u."-access.log combined

    SSLEngine On
    SSLCertificateFile      ".$cert."
    SSLCertificateKeyFile   ".$key."

    DirectorySlash On
    RewriteEngine On

    # Serve existing files/dirs as-is (must be first)
    RewriteCond %{REQUEST_FILENAME} -f [OR]
    RewriteCond %{REQUEST_FILENAME} -d
    RewriteRule ^ - [L]

    # If /path.php exists, serve it for /path
    RewriteCond %{REQUEST_FILENAME}\\.php -f
    RewriteRule ^(.+)\$ $1.php [L]

    # Else if /path/ exists, add trailing slash
    RewriteCond %{REQUEST_FILENAME}/ -d
    RewriteRule ^(.+[^/])\$ $1/ [R=302,L]
</VirtualHost>
");
} else {
    prov_writeVhost($vhost_name, null);
}
prov_enableSite($vhost_name);
prov_reloadApache();

prov_step("Firewall (do this by hand)");
$proxy_ip = prov_ask("Front proxy IP address, blank prints a placeholder");
if ($proxy_ip == ""){ $proxy_ip = "<FRONT-PROXY-IP>"; }
print("ufw allow from ".$proxy_ip." to any port ".$uport." proto tcp".PHP_EOL);
print("or add the port to upstream-ports and the address to upstream-allowed-ips in /etc/nftables.conf".PHP_EOL);

prov_step("Front proxy config (run on the front proxy)");
$us_srv='cat <<EOF > /var/www/upstreams.d/'.$u.'.conf
# --- mount /'.$u.'/ ---
# canonicalize /'.$u.' -> /'.$u.'/
RedirectMatch 301 ^/'.$u.'$ /'.$u.'/

SSLProxyEngine On
ProxyPreserveHost On

# Tell upstream it’s mounted under /'.$u.'
RequestHeader set X-Forwarded-Proto "https"
RequestHeader set X-Forwarded-Prefix "/'.$u.'"

ProxyPass        /'.$u.'/ https://'.$fqdn.':'.$uport.'/ connectiontimeout=5 timeout=60 keepalive=On
ProxyPassReverse /'.$u.'/ https://'.$fqdn.':'.$uport.'/

# Cookies: Path=/  ->  Path=/'.$u.'/
# ProxyPassReverseCookiePath / /'.$u.'/

# --- Redirect fixes from upstream ---

# 1) If upstream ever points to its own host, map to /'.$u.'/
Header edit Location "^https://'.preg_quote($fqdn).':'.$uport.'/(.*)\$" "/'.$u.'/\$1" \\
  "expr=%{REQUEST_URI} =~ m#^/'.$u.'/#"

# 2) **Absolute self URL** without /'.$u.' -> prefix it
Header edit Location "^https://'.preg_quote($pub).'/(?!'.$u.'/)(.*)\$" "https://'.$pub.'/'.$u.'/\$1" \\
  "expr=%{REQUEST_URI} =~ m#^/'.$u.'/#"

# 3) **Path-only** Location (root-relative) -> prefix it
Header edit Location "^/(.*)\$" "/'.$u.'/\$1" \\
  "expr=%{REQUEST_URI} =~ m#^/'.$u.'/#"

# (optional) Append trailing slash only when there is no ? or # and no trailing /
Header edit Location "^(/[^?#]*[^/?#])\$" "\$1/" \\
  "expr=%{REQUEST_URI} =~ m#^/'.$u.'/#"
EOF';

print($us_srv);

prov_daemonConfig(["maxconnections=72"]);

exit(prov_adminUser());
?>
