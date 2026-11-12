<?php
/*
 * Public page footer: modal, copyright, ad slots, ad waterfall, scripts.
 *
 * The admin console uses footer-admin.php, which shares both layout partials
 * below but adds neither ad slots nor the waterfall. Pair with header.php.
 */
include __DIR__ . '/layout_footer_shared.php';
include __DIR__ . '/layout_ads_slots.php';
// <!-- SCRIPTS --> ?>
  <script defer nonce="<?php echo $csp_nonce; ?>" type="text/javascript" >getMetrics('<?php echo $settings["explorer_address"];?>','/api/monitoring/uxm/',rid)</script>
  <?php
  // ADS WATERFALL
    // The trailing two spaces were inline HTML inside this conditional in the
    // single-file footer, so they are emitted only when the waterfall is.
    if ($ads_on) { include __DIR__ . '/layout_ads_waterfall.php'; echo "  "; }
  // EOF ADS WATERFALL ?>
 <?php // <!-- #3rd party scripts --> ?>
<?php include __DIR__ . '/layout_scripts.php'; ?>
