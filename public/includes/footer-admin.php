<?php
/*
 * Admin console footer: modal, copyright, scripts.
 *
 * The counterpart of footer.php for pages under ADMIN_PATH. It shares both
 * layout partials with the public footer and adds no ad slots and no waterfall,
 * so none of that code is reachable from the admin tree. Pair with header-admin.php.
 */
include __DIR__ . '/layout_footer_shared.php';
// <!-- SCRIPTS --> ?>
  <script defer nonce="<?php echo $csp_nonce; ?>" type="text/javascript" >getMetrics('<?php echo $settings["explorer_address"];?>','/api/monitoring/uxm/',rid)</script>
<?php include __DIR__ . '/layout_scripts.php'; ?>
