<?php
/*
 * Admin console header: security headers, document head, side navigation, navbar.
 *
 * The counterpart of header.php for pages under ADMIN_PATH. The admin area serves
 * no ads, no consent banner and no analytics, so this file never loads
 * layout_adstack.php: the demand-partner allowlists and the waterfall are not
 * merely switched off here, they are absent. Every ad/third-party flag the shared
 * partials read is set false below.
 *
 * The policy allows only the explorer itself plus the script libraries, so an
 * injected script could neither pull in third-party code nor send data elsewhere.
 * 'unsafe-inline' stays because the admin pages load HTML fragments whose inline
 * scripts carry no nonce.
 *
 * Pair with footer-admin.php.
 */
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/common.php';
  include_once __DIR__ . '/../../lib/explorerUi.php';
  include_once __DIR__ . '/../../lib/explorerDb.php';

  global $rpc;
  global $dbm;
  global $csp_nonce;
  global $title;
  global $description;
  global $keywords;
  global $page_name;
  global $page_id;
  global $json_ld;

  $menu_item = (isset($page_id)) ?? "null";

  $rid = db_createRequest();

  global $ads_data;
  $ads_data = ui_GetWaterfallAdsData();

  date_default_timezone_set($settings["timezone"]);

  // Nothing third-party in the admin area. The shared layout partials and
  // footer-admin.php read these, so they must all be defined.
  $ads_on           = false;
  $consent_required = false;
  $cmp_script       = "";
  $cmp_on           = false;
  $gtm_on           = false;
  $beacon_on        = false;
  $tp               = [];
  $tp_on            = false;

  $csp = [
    "default-src 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    "form-action 'self'",
    "frame-ancestors 'none'",
    "script-src 'self' 'unsafe-inline' ajax.googleapis.com cdnjs.cloudflare.com cdn.amcharts.com",
    "connect-src 'self'",
    "img-src 'self' data:",
    "style-src 'self' 'unsafe-inline'",
    "font-src 'self' data:",
    "frame-src 'none'",
    "worker-src 'self' blob:",
  ];

  header("Content-Security-Policy: " . implode('; ', $csp));
  header("Referrer-Policy: strict-origin-when-cross-origin");
  header("X-Content-Type-Options: nosniff");
  header("X-Frame-Options: SAMEORIGIN");
  header("Accept-CH: Sec-CH-UA, Sec-CH-UA-Mobile, Sec-CH-UA-Platform, Sec-CH-UA-Arch, Sec-CH-UA-Bitness, Sec-CH-UA-Model, Sec-CH-UA-Platform-Version");

  header("Permissions-Policy: geolocation=(), camera=(), microphone=(), ".
    "gyroscope=(self), magnetometer=(), accelerometer=(self), usb=(), payment=(), ".
    "fullscreen=(self), shared-storage=() ");

  $expired = ui_getExpiredStatus();
?>
<?php include __DIR__ . '/layout_head.php'; ?>
<?php include __DIR__ . '/layout_sidenav.php'; ?>
<?php include __DIR__ . '/layout_toasts.php'; ?>
<?php include __DIR__ . '/layout_navbar.php'; ?>
