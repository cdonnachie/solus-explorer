<?php
/*
 * Document head and opening <body>.
 *
 * Shared layout partial, included by header.php (public) and header-admin.php.
 * Not reachable on its own - see includes/.htaccess.
 */
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
  
  <?php /* Skin colour tokens from settings.php ("skin_colors"), emitted before the
          stylesheets so a custom theme_stylesheet can still override them. */ ?>
  <?php echo ui_renderSkinTokens(); ?>

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

  <?php if($ads_on) : ?>
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

