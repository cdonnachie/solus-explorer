<?php
/*
 * Public page header: security headers, document head, side navigation, navbar.
 *
 * The admin console uses header-admin.php instead. That split is deliberate:
 * only this file loads the ad stack (layout_adstack.php) with its demand-partner
 * allowlists, so the admin tree cannot reach that code at all. Everything both
 * variants share lives in the layout_*.php partials.
 *
 * Pair with footer.php. Pages set $title, $description, $keywords, $page_name,
 * $page_id, $csp_nonce and optionally $json_ld before including this file.
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

  // Ads, consent, the optional third parties and the public CSP. Sets $ads_on,
  // $consent_required, $cmp_on, $cmp_script, $gtm_on, $beacon_on, $tp, $tp_on
  // and $csp, all of which footer.php and the partials below read.
  include __DIR__ . '/layout_adstack.php';

  header("Content-Security-Policy: " . implode('; ', $csp));
  if ($ads_on) {
    header("X-Consent-Mode: " . (ui_GetVisitorCountry() ?: "unknown") . "; " .
      ($cmp_on ? "cmp" : ($consent_required ? "deny-stub" : "none")));
  }
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
