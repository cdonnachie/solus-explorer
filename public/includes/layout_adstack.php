<?php
/*
 * Ad, consent and third-party resolution, plus the public Content-Security-Policy.
 *
 * Public pages only: header-admin.php sets every flag false and builds its own
 * policy, so the admin tree never loads this file or its allowlists.
 */
  global $settings, $ads_data;

  $ads_on = (bool)$ads_data->enabled;

  // Consent: visitors from the EU/EEA/UK/CH (or of unknown origin) get the InMobi CMP when
  // cmp_enabled is set and its tag is in place, otherwise the deny-all TCF stub. Everyone else gets no TCF API, so
  // ad partners (Sevio's Prebid TCF enforcement in particular) treat them as out of scope.
  $consent_required = $ads_on && ui_ConsentRequired();
  // The CMP tag is the vendor's code, not part of this repository: the operator saves it
  // below public/ (cmp_script, default js/local/cmp.js, git-ignored). Without it in-scope
  // visitors get the deny-all stub.
  $cmp_script = (string)($settings["cmp_script"] ?? "js/local/cmp.js");
  if (!preg_match('#^[A-Za-z0-9_][A-Za-z0-9_./-]*\.js$#', $cmp_script) || strpos($cmp_script, '..') !== false || !is_file(__DIR__.'/../'.$cmp_script)) {
    $cmp_script = "";
  }
  $cmp_on = $consent_required && !empty($settings["cmp_enabled"]) && !empty($settings["cmp_property_id"]) && $cmp_script !== "";

  // Optional third parties; each one is loaded and allowed by the CSP only when configured.
  $gtm_on = getGoogleTagId() != "";
  $beacon_on = !empty($settings["cloudflare_beacon_token"]);
  $tp = $settings["trustpilot"] ?? [];
  $tp_on = !empty($tp["business_id"]) && !empty($tp["template_id"]);

  // Sevio (adx.ws) demand-partner allowlist. Sevio's loader pulls creatives,
  // bid requests and measurement pixels from any of these, so the same list has
  // to be present on script-src, frame-src and connect-src.
  // A bare host does not cover its subdomains in CSP, hence both forms.
  // Scheme is omitted throughout this policy: a host-source without a scheme
  // matches the protected resource's scheme, and every page here is https.
  $sevio_partners = !$ads_on ? "" : " " . implode(' ', [
    "cdn.adx.ws", "request.adx.ws", "config-resolver.adx.ws", "req.adx.ws",
    "eu-req.adx.ws", "embed.hel.io",
    "createjs.com", "*.createjs.com",
    "adform.net", "*.adform.net",
    "smartadserver.com", "*.smartadserver.com",
    "sascdn.com", "*.sascdn.com",
    "smilewanted.com", "*.smilewanted.com",
    "criteo.com", "*.criteo.com",
    "adnxs.com", "*.adnxs.com",
    "adnxs-simple.com", "*.adnxs-simple.com",
    "rubiconproject.com", "*.rubiconproject.com",
    "pubmatic.com", "*.pubmatic.com",
    "openx.net", "*.openx.net",
    "sharethrough.com", "*.sharethrough.com",
    "lijit.com", "*.lijit.com",
    "onetag.com", "*.onetag.com",
    "onetag-sys.com", "*.onetag-sys.com",
    "360yield.com", "*.360yield.com",
    "id5-sync.com", "*.id5-sync.com",
    "eu-1-id5-sync.com", "*.eu-1-id5-sync.com",
    "2mdn.net", "*.2mdn.net",
    "cloudfront.net", "*.cloudfront.net",
    "amxrtb.com", "*.amxrtb.com",
    "amxrtb.net", "*.amxrtb.net",
    "amx1.net", "*.amx1.net",
    "a-mx.net", "*.a-mx.net",
    "a-mo.net", "*.a-mo.net",
    "ad-m.net", "*.ad-m.net",
    "sptag2.com", "*.sptag2.com",
    "omnitagjs.com", "*.omnitagjs.com",
    "cdn.doubleverify.com", "*.cdn.doubleverify.com",
    "pixel.yabidos.com", "*.pixel.yabidos.com",
    "creativecdn.com", "*.creativecdn.com",
    "adsgravity.io", "*.adsgravity.io",
    "quantserve.com", "*.quantserve.com",
    "quantcount.com", "*.quantcount.com",
    "quantextual.net", "*.quantextual.net",
    "flashtalking.com", "*.flashtalking.com",
    "adsafeprotected.com", "*.adsafeprotected.com",
    "adobedynamictag.com", "*.adobedynamictag.com",
    "hypelab.com", "*.hypelab.com",
    "ixncdn.com", "*.ixncdn.com",
    "doubleclick.net", "*.doubleclick.net",
    "growthmate.xyz", "*.growthmate.xyz",
    "alkimi-dsp.com", "*.alkimi-dsp.com",
    "alkimi-onboarding.com", "*.alkimi-onboarding.com",
    "alkimi.org", "*.alkimi.org",
    "ampproject.org", "*.ampproject.org",
  ]);

  // Origins needed only while ads/CMP are being served.
  $ads_core = !$ads_on ? "" : " " . implode(' ', [
    "cdn.ampproject.org", "csi.gstatic.com", "www.google.com",
    "pagead2.googlesyndication.com", "tpc.googlesyndication.com",
    "googleads.g.doubleclick.net", "cm.g.doubleclick.net",
    "adservice.google.com", "www.googleadservices.com",
    "fundingchoicesmessages.google.com",
    "ep1.adtrafficquality.google", "ep2.adtrafficquality.google",
    "cdn.bmcdn6.com", "request-global.czilladx.com",
  ]) . (!$cmp_on ? "" : " " . implode(' ', [
    "cmp.inmobi.com", "api.cmp.inmobi.com", "visit-server.inmobi-choice.io",
  ]));

  $csp = [
    // Safe defaults
    "default-src 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    "form-action 'self'",
    "frame-ancestors 'self'",
    // If your site must load over HTTPS only:
    "upgrade-insecure-requests ",

    // Scripts (include AMP + ad stacks). Keep 'unsafe-inline' for third-party ad snippets.
    // script-src-elem is intentionally absent: it was a verbatim mirror of this
    // directive, and browsers fall back to script-src for element loads when it
    // is not set, so listing it twice only doubled the header size.
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
      //"'strict-dynamic' 'nonce-".$csp_nonce."' " .
      "ajax.googleapis.com cdn.amcharts.com cdnjs.cloudflare.com" .
      ($gtm_on ? " www.googletagmanager.com" : "") .
      ($beacon_on ? " static.cloudflareinsights.com" : "") .
      ($tp_on ? " widget.trustpilot.com" : "") .
      $ads_core . $sevio_partners,

    // Frames/iframed ads
    "frame-src 'self'" .
      ($tp_on ? " widget.trustpilot.com" : "") .
      $ads_core . $sevio_partners,

    // XHR/fetch/beacons (ad requests, CMP, analytics)
    "connect-src 'self'" .
      ($gtm_on ? " www.googletagmanager.com google-analytics.com *.google-analytics.com" : "") .
      ($beacon_on ? " cloudflareinsights.com static.cloudflareinsights.com" : "") .
      $ads_core . $sevio_partners,

    // Images: the blanket https: already covers every ad/measurement pixel,
    // so per-vendor hosts add nothing here.
    "img-src 'self' https: data: blob:",

    // Styles & fonts (inline styles are common with third-party widgets)
    "style-src 'self' 'unsafe-inline'" . ($ads_on ? " fonts.googleapis.com" : ""),
    "font-src 'self' data:" . ($ads_on ? " fonts.gstatic.com" : ""),

    // Workers & media (rarely needed for ads, but harmless to allow typical usage)
    "worker-src 'self' blob:",
    "media-src 'self' https:",
    //"prefetch-src 'self' https:",
  ];
