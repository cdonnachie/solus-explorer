<?php   
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

  // The ad stack is only reachable from pages that actually render ads, so
  // the admin area and ads-disabled deployments ship a much smaller header.
  // Decided by the script's file, not the URL, which may carry a proxy prefix.
  $admin_dir = realpath(__DIR__ . '/../' . ADMIN_PATH);
  $script_file = realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
  $is_admin_area = ($admin_dir !== false && $script_file !== false && strpos($script_file, $admin_dir . DIRECTORY_SEPARATOR) === 0);
  $ads_on = ($ads_data->enabled && !$is_admin_area);

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
  $gtm_on = !$is_admin_area && getGoogleTagId() != "";
  $beacon_on = !$is_admin_area && !empty($settings["cloudflare_beacon_token"]);
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

  // The admin area loads no ads, analytics or widgets, so it gets a policy that
  // only allows the explorer itself and the script libraries: an injected script
  // could neither pull in third-party code nor send data to another host.
  // 'unsafe-inline' stays because the admin pages load HTML fragments whose
  // inline scripts carry no nonce.
  if ($is_admin_area) {
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
  }

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

  // $is_admin_area is set above, before the CSP header is emitted.
  $expired = ui_getExpiredStatus();
  global $is_admin_area;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php if ($gtm_on) : ?>
  <script nonce="<?php echo $csp_nonce; ?>">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','<?php echo getGoogleTagId(); ?>');</script>
  <?php endif; ?>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="robots" content="index, follow">
  <?php if (isset($keywords) && !empty($keywords)) : ?>
  <meta data-hid="keywords" name="keywords" content="<?php echo $keywords; ?>" data-n-head="true">
  <?php else : ?>
  <meta data-hid="keywords" name="keywords" content="<?php if (isset($settings["meta_keywords"])) { echo $settings["meta_keywords"]."," ;} if (!isset($settings["is_oem"]) || !$settings["is_oem"]) { echo " solus, solus explorer,";}?> blockchain explorer, block size, hashrate, mining diffculity, mining rewards, transaction fee, transaction amounts" data-n-head="true">
  <?php endif; ?>
  <?php if (isset($description) && !empty($description)) : ?>
  <meta data-n-head="true" data-hid="description" name="description" content="<?php echo $description; ?>">
  <?php else : ?>
  <meta data-n-head="true" data-hid="description" name="description" content="<?php if (isset($settings["meta_description"])) {echo $settings["meta_description"];} else {echo $settings["coin_symbol"]." Blockchain Explorer.";} ?>">
  <?php endif; ?>
  
  <?php if (!empty($settings["operator_name"])) : ?>
  <meta name="author" content="<?php echo htmlspecialchars($settings["operator_name"]); ?>">
  <?php endif; ?>
  <meta http-equiv="Feature-Policy" content="microphone 'none'; geolocation 'none'; camera 'none'; accelerometer: 'self'">

  <meta property="og:title" content="<?php echo $title; ?>">
  <meta property="og:description" content="<?php echo $description; ?>">
  <meta property="og:url" content="<?php echo ui_getCanonical();?>">
  <meta property="og:image" content="<?php echo ui_getCanonicalRootURL();?>/img/<?php if(array_key_exists("logo_image",$settings)){ echo $settings['logo_image'];}else{echo 'logo.svg';}?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta property="twitter:image" content="<?php echo ui_getCanonicalRootURL();?>/img/<?php if(array_key_exists("logo_image",$settings)){ echo $settings['logo_image'];}else{echo 'logo.svg';}?>">

  <meta name="title" content="<?php echo htmlspecialchars($title, ENT_QUOTES); ?>">
  <title><?php echo $title; ?></title>
  
  <link rel="canonical" href="<?php echo ui_getCanonical(); ?>">

  <link rel="preload" href="<?php echo $settings["explorer_address"]; ?>/font/roboto/Roboto-Light.woff2"    crossorigin as="font" type="font/woff2" />
  <link rel="preload" href="<?php echo $settings["explorer_address"]; ?>/font/roboto/Roboto-Regular.woff2"  crossorigin as="font" type="font/woff2" />
  <link rel="preload" href="<?php echo $settings["explorer_address"]; ?>/font/roboto/Roboto-Medium.woff2"   crossorigin as="font" type="font/woff2" />
  <link rel="preload" href="<?php echo $settings["explorer_address"]; ?>/font/roboto/Roboto-Bold.woff2"     crossorigin as="font" type="font/woff2" />
  
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/bootstrap.min.css">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/all.min.css">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/addons/dataTables.bootstrap4.min.css">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/sidenav.css?v=<?php echo $version; ?>">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/compat.css?v=<?php echo $version; ?>">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/icons.css?v=<?php echo $version; ?>">
  <?php if (in_array($expired, ["yes", "complete", "soon"], true)) : ?>
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/toastr.min.css?v=2.1.4">
  <?php endif; ?>
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/json.css?v=<?php echo $version; ?>">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/common.css?v=<?php echo $version; ?>">
  <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/<?php echo $settings["theme_stylesheet"] ?? "style-n.css"; ?>?v=<?php echo $version; ?>">

  <?php if($ads_data->enabled && !$is_admin_area) : ?>
    <link  nonce="<?php echo $csp_nonce; ?>" rel="stylesheet" href="<?php echo $settings["explorer_address"]; ?>/css/_mix.css?v=<?php echo $version; ?>">
    <?php if($cmp_on): ?>
      <script nonce="<?php echo $csp_nonce; ?>" type="text/javascript" src="<?php echo $settings["explorer_address"]."/".$cmp_script; ?>?v=<?php echo filemtime(__DIR__.'/../'.$cmp_script); ?>" data-property-id="<?php echo htmlspecialchars($settings["cmp_property_id"]); ?>"></script>
    <?php elseif($consent_required): ?>
      <script nonce="<?php echo $csp_nonce; ?>" type="text/javascript" src="<?php echo $settings["explorer_address"]; ?>/js/consent-deny.js?v=<?php echo $version; ?>"></script>
    <?php endif; ?>
  <?php endif; ?>

  <?php if($tp_on) : ?>
    <script nonce="<?php echo $csp_nonce; ?>" src="https://widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js" async></script>
  <?php endif; ?>
  

  <link   nonce="<?php echo $csp_nonce; ?>" rel="shortcut icon" href="<?php if(array_key_exists("favicon_path", $settings)) { echo $settings["explorer_address"]."/".$settings["favicon_path"]; } else { echo $settings["explorer_address"]."/img/favicon.ico"; }?>">
  <script nonce="<?php echo $csp_nonce; ?>" src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha384-1H217gwSVyLSIfaLxHbE7dRb3v4mYCKbpQvzx0cegeju1MVsGrX5xXxAvs/HgeFs" crossorigin="anonymous"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/solus-2.21.js?v=<?php echo $version; ?>"></script>
  <script nonce="<?php echo $csp_nonce; ?>">const rid='<?php echo $rid;?>';</script>
    
  <?php if (!empty($json_ld)) { echo $json_ld; } ?>

  <style  nonce="<?php echo $csp_nonce; ?>">    
  <?php 
    echo ".sn-bg-4 {";
    if (!isset($settings["nav_image"])) {
      if (isset($settings["is_oem"]) && $settings["is_oem"]) {
        echo 'background-image: url("'.$settings["explorer_address"].'/img/_navback.png"); ';
      } else {
        echo 'background-image: url("'.$settings["explorer_address"].'/img/navback.jpg"); ';
      }
    } else {
      echo 'background-image: url("'.$settings["explorer_address"].'/'.$settings["nav_image"].'"); ';
    }
    echo "}";
  ?>
  </style> 
</head>

<body class="fixed-sn <?php echo $settings["explorer_skin"];?>">  

  <?php if ($gtm_on) : ?>
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo getGoogleTagId(); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <?php endif; ?>

  <!--Main Navigation-->
  <!-- <header> -->

    <!-- Sidebar navigation -->
    <div id="slide-out" class="side-nav fixed wide sn-bg-4">
      <div class="side-nav-content <?php if ($tp_on){ echo "side-nav-content-tp";} ?>">
        <ul class="custom-scrollbar mb-0">
          <!-- Logo -->
          <li class="logo-sn">
            <div class="ml-3 mr-3 text-center">
              <a href="<?php echo $settings["explorer_address"];?>/" class="pl-0 logo">
                <img src="<?php echo $settings["explorer_address"];?>/img/<?php if(array_key_exists("logo_image",$settings)){ echo $settings['logo_image'];}else{echo 'logo.svg';}?>" 
                  height="<?php echo $settings['logo_height']; ?>" 
                  width="<?php echo $settings['logo_width']; ?>" 
                  alt="<?php echo ui_getCoinName(); ?> explorer logo"
                  loading="lazy">
              </a>
            </div>
          </li>
          <!--/. Logo -->

          <!--Search Form-->
          <li>
            <form class="search-form" role="search" id="searchForm">
              <div class="form-group md-form mt-0 pt-1">
                <input type="text" class="form-control text-left" placeholder="Search" id="inputSearch">
              </div>
            </form>
          </li>
          <script type="text/javascript" nonce="<?php echo $csp_nonce;?>">
            $( document ).ready(function() {
              $( "#searchForm" ).submit(function( event ) {
                doSearch('<?php echo $settings["explorer_address"];?>', $("#inputSearch").val().trim());
              });  
            });
 
          </script>

          <!--/.Search Form-->
          <!-- Side navigation links -->
          <li>
            <ul class="collapsible collapsible-accordion pb-2">
              <li class="nav-text"> <a id="Overview" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/"><i class="fas fa-tachometer-alt wrem-2"></i>
                  Overview</a>
              </li>
              <li class="nav-text"> <a id="Blocks" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/blocks/"><i class="fas fa-cubes wrem-2"></i>
                  Blocks</a>
              </li>
              <li class="nav-text"> <a id="Coins" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/groups/"><i class="fas fa-coins wrem-2"></i>
                  Coin Distribution</a>
              </li>
              <?php if (isset($settings["show_pages"]["airdrop"]) && $settings["show_pages"]["airdrop"]) : ?>
              <li class="nav-text"> <a id="Airdrop" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/airdrop/"><i class="fas fa-hand-holding-water wrem-2"></i>
                  Airdrop</a>
              </li>
              <?php endif; ?>  
              <?php if (isset($settings['show_pages']['addresses']) && $settings['show_pages']['addresses']) :?>
              <li class="nav-text"> <a id="Addresses" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/addresses/"><i class="fas fa-at wrem-2"></i>
                  Top Addresses</a>
              </li>
              <?php endif; ?>
              <li class="nav-text"> <a id="Movements" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/movements/"><i class="fas fa-exchange-alt wrem-2"></i>
                  Coin Movements</a>
              </li>
              <?php if (isset($settings["show_pages"]["burned"]) && $settings["show_pages"]["burned"]) : ?>
                <li class="nav-text"> <a id="Burned" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/burnedcoins/"><i class="fas fa-fire-alt wrem-2"></i>
                  Burned Coins</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings["show_pages"]["assets"]) && $settings["show_pages"]["assets"]) : ?>
                <li class="nav-text"> <a id="Assets" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/assets/"><i class="fas fa-box-open wrem-2"></i>
                  Assets</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
              <li class="nav-text"> <a id="Masternodes" class="collapsible-header" href="<?php echo $settings["explorer_address"]; ?>/masternodes/"><i class="fab fa-hubspot wrem-2"></i>
                  Masternodes</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['mining']) && $settings['show_pages']['mining']) : ?>
              <li class="nav-text"> <a id="Mining" class="collapsible-header" href="<?php echo$settings["explorer_address"];?>/mining/"><i class="fas icons-pike wrem-2"></i>
                  Mining</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['staking']) && $settings['show_pages']['staking']):?>
              <li class="nav-text"> <a id="Staking" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/staking/"><i class="fas fa-tint wrem-2"></i>
                  Staking</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings['show_pages']['goldrush']) && $settings['show_pages']['goldrush'] && shadow_enabled()):?>
              <li class="nav-text"> <a id="GoldRush" class="collapsible-header" href="<?php echo $settings["explorer_address"];?>/goldrush/"><i class="fas fa-bolt wrem-2"></i>
                  Gold Rush</a>
              </li>
              <?php endif; ?>
              <li class="nav-text"> <a id="Network" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/network/"><i class="fas fa-network-wired wrem-2"></i>
                  Network</a>
              </li>
              <?php if ((isset($settings['show_pages']['markets']) && $settings['show_pages']['markets']) || !isset($settings['show_pages']['markets']) ) :  ?>
              <li class="nav-text"> <a id="Markets" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/markets/"><i class="fas fa-chart-line wrem-2"></i>
                  Markets</a>
              </li>
              <?php endif; ?>
              <?php if (isset($settings["show_pages"]["stats"]) && $settings["show_pages"]["stats"]) : ?>
                <li class="nav-text"> <a id="Stats" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/stats/"><i class="fas fa-chart-area wrem-2"></i>
                  Chain Statistics</a>
              </li>
              <?php endif; ?>
              <?php '
              // <!-- 
              <li class="nav-text"> <a id="Daemon" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/daemon/"><i class="fas fa-cog wrem-2"></i>
                  Daemon Information</a>
              </li> --> 
              '?>
              <?php 
                if (isset($settings["custom_links"]) && count($settings["custom_links"])>0){
                  echo "<hr>";
                  foreach ($settings["custom_links"] as $link){
                    echo '<li class="nav-text"> <a class="collapsible-header" href="'.$link["href"].'" target="_blank" rel="noopener"><i class="'.$link["icon"].' wrem-2"></i>'.$link["text"].'</a>
                    </li>';
                  }
                }
              ?>

              <hr>
              <li class="nav-text"> <a id="Api" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/api/"><i class="fas fa-code-branch wrem-2"></i>
                API</a>
              </li>
              <?php if (isset($settings['show_pages']['about']) && $settings['show_pages']['about']) : ?>
              <li class="nav-text"> <a id="About" class="collapsible-header" href="<?php echo $settings["explorer_address"]?>/about/"><i class="fas fa-info-circle wrem-2"></i>
                About</a>
              </li> 
              <?php endif; ?>
              

            </ul>
            <?php 
                if (isset($settings["show_socials"]) && $settings["show_socials"] && isset($settings["social_buttons"]) && count($settings["social_buttons"])>0) {
                echo "<hr>";
                echo "<div class='row justify-content-center ml-0 mr-1'>";
                  foreach ($settings["social_buttons"] as $btn){
                    echo '<span class="p-0 m-0"><a class="collapsible-header nav-text btn-social" href="'.$btn['href'].'" title="'.$btn['title'].'" target="_blank" rel="noopener"><i class="'.$btn['icon'].'"></i></a></span>';
                  }
                
                echo "</div>";
                }
              ?>
          </li>
          <!--/. Side navigation links -->
        </ul>
          <?php 
            if (isset($settings["menu_banners"]) && count($settings["menu_banners"])>0){
              //echo "<hr>";
              foreach ($settings["menu_banners"] as $banner){ ?>
                <div class="m-0 text-center p-0 w-100">
                  <a href="<?php echo $banner["href"];?>" title="<?php echo $banner["title"];?>" class="p-0 m-0"
                    style="height:inherit!important;line-height:inherit!important;" target="_blank">
                    <img class="p-0 m-0" 
                      height="<?php echo (!empty($banner["height"])?$banner["height"]:"")?>" 
                      width="<?php echo (!empty($banner["width"])?$banner["width"]:"")?>" 
                      src="<?php echo $settings["explorer_address"].$banner["img"];?>">
                  </a>
                </div>
                  
              <?php }
            }
          ?>
      </div>
        
      <div class="side-nav-footer <?php if ($tp_on){ echo "side-nav-footer-tp"; } ?>"> 
        <?php if ($tp_on) : ?>
          <div class="trustpilot-widget" data-locale="en-US" data-template-id="<?php echo htmlspecialchars($tp["template_id"]); ?>" data-businessunit-id="<?php echo htmlspecialchars($tp["business_id"]); ?>" data-style-height="52px" data-style-width="100%"<?php if (!empty($tp["token"])) { echo ' data-token="'.htmlspecialchars($tp["token"]).'"'; } ?>>
            <a href="<?php echo htmlspecialchars($tp["review_url"] ?? "https://www.trustpilot.com/"); ?>" target="_blank" rel="noopener">Trustpilot</a>
          </div>
        <?php endif; ?>
        <?php if (!empty($settings["operator_name"]) || !empty($settings["operator_logo"])) : ?>
          <div class="row align-items-end">
            <?php ui_RenderMenuBadge($settings["operator_label"] ?? "Operated by", $settings["operator_name"] ?? "", $settings["operator_url"] ?? "", $settings["operator_logo"] ?? "", "25px", "col-12 pr-0 mr-0"); ?>
          </div>
        <?php endif; ?>
        <?php if (!empty($settings["show_menu_reflinks"]) && (!empty($settings["hosting_ref"]) || !empty($settings["wallet_ref"]))) : ?>
        <div class="row align-items-end">
          <?php foreach (["hosting_ref" => "col-6 pr-0 mr-0", "wallet_ref" => "col-6 pl-2 ml-0"] as $ref_key => $ref_col) {
            $ref = $settings[$ref_key] ?? [];
            if (!empty($ref)) {
              ui_RenderMenuBadge($ref["text"] ?? "", $ref["name"] ?? "", $ref["href"] ?? "", $ref["img_src"] ?? "", "18px", $ref_col);
            }
          } ?>
        </div>
        <?php endif; ?>          
      </div> 

      <div class="sidenav-bg mask-strong"></div>
    </div>
    <!--/. Sidebar navigation -->

  <?php if (in_array($expired, ["yes", "complete", "soon"], true)) : ?>
    <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/toastr.min.js?v=2.1.4"></script>
  <?php endif; ?>
  <?php if ($expired == "yes" || $expired == "complete") : ?>
    <script type='text/javascript' nonce="<?php echo $csp_nonce; ?>">
      $(document).ready(function(){
        toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": 300,
        "hideDuration": 1000,
        "timeOut": 0,
        "extendedTimeOut": 1000,
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
        }
        toastr["error"]("Database synchronization disabled.", "Explorer subscription expired!")
      });
    </script>
  <?php elseif (($expired == "soon") ) : ?>
    <script type='text/javascript' nonce="<?php echo $csp_nonce; ?>">
      $(document).ready(function(){
        toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": true,
        "onclick": null,
        "showDuration": 300,
        "hideDuration": 1000,
        "timeOut": 5000,
        "extendedTimeOut": 1000,
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
        }
        toastr["warning"]("Explorer service subscription expires soon.", "Subscription Expiration Notice")
      });
    </script>
  <?php endif; ?>


  <div class="content <?php echo (($ads_data->enabled && !$is_admin_area) ? 'content-mix' : ''); ?>">
    <!-- Navbar -->
    <nav class="navbar fixed-top navbar-expand-lg scrolling-navbar double-nav">
        <!-- SideNav slide-out button -->
        <div class="float-left">
          <a href="#" data-activates="slide-out" class="button-collapse"><i class="fas fa-bars nav-text"></i></a>
        </div>
        <!-- Breadcrumb-->
        <div class="breadcrumb-dn mr-auto">
          <p id="pageName" class="h4-responsive"><?php if(isset($page_name)&&!empty($page_name)){echo $page_name;} ?></p>
        </div>
        <div class="float-right search-bar-trigger">
          <a id="top-search-show" type="button"> <i class="fas fa-search pointer"></i></a>
        </div>
      <!--
      
      -->
      <div id="topSearch" class="search-bar primary-color px-3">
				<form class="d-flex md-form w-100 justify-content-center" method="GET">
            <input id="inputTopSearch" class="align-self-center search-input form-control card-header-text" type="text" placeholder="  Search for address, block, tx<?php if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){echo ", asset";}?> or address group">
            <button type="submit" class="primary-color align-self-center nav-text pl-2 pr-0 m-0" title="Search"><i class="fas fa-search pointer"></i></button>
            <a type="button" id="tsClose" class="primary-color align-self-center search-bar-close nav-text pl-2 pr-0 m-0" title="Close"><i class="fas fa-times pointer"></i></a>
				</form>
			</div>
      <script  nonce="<?php echo $csp_nonce; ?>">
        $(document).ready(function(){
          $('#top-search-show').on('click', function (e) {
            $('.search-bar').fadeIn();
            e.preventDefault();
          });
          $(document).mouseup(function (e) {
            var container = $('.search-bar form');
            if (!container.is(e.target) && container.has(e.target).length === 0) {
              $('.search-bar').fadeOut();
            }
          });
          $( "#topSearch" ).submit(function( event ) {
            doSearch('<?php echo $settings["explorer_address"];?>', $("#inputTopSearch").val().trim());
          });  
          $("#tsClose").on("click", function(event){
            $('.search-bar').fadeOut();
          });
        })

      </script>
    </nav>

    <!-- /.Navbar -->
  <!-- </header> -->
  <!--Main Navigation-->