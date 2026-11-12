<?php
/*
 * Third-party scripts, optional amCharts and Cloudflare beacon, document close.
 *
 * Shared layout partial, included by footer.php (public) and footer-admin.php.
 * Not reachable on its own - see includes/.htaccess.
 */
?>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/popper.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/bootstrap.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/addons/jquery.dataTables.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/addons/dataTables.bootstrap4.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdnjs.cloudflare.com/ajax/libs/jquery.perfect-scrollbar/1.4.0/perfect-scrollbar.min.js" integrity="sha384-kUOB3zJp3LsOIl65Vg10pHRNJUtopY0kS34T7UkILEgYVzN7fT77uxXWpH2Wa8jJ" crossorigin="anonymous"></script>
  <?php if (!empty($use_amcharts)) : ?>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdn.amcharts.com/lib/version/4.10.40/core.js" integrity="sha384-ordGF512qZ28dR6Fu0XRiEDmJ2hlTRqB4CUbHADX01rzXhP7hfuURgnA83jHsPAu" crossorigin="anonymous"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdn.amcharts.com/lib/version/4.10.40/charts.js" integrity="sha384-IrVbZoOciaDw6EuKzbIx3dkSw3QyUm/YyYS2Q6GUGXOyZkDoaqZNOuwDpopQymF8" crossorigin="anonymous"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="https://cdn.amcharts.com/lib/version/4.10.40/themes/animated.js" integrity="sha384-50bHMQnvdDZTCzEAXttOJXnmLXdJbzKx5ERCSfx+a4I6zyCcNNrnEaTbdNqyGaAI" crossorigin="anonymous"></script>
  <?php if (!empty($settings["amcharts_license_key"])) : ?>
  <script nonce="<?php echo $csp_nonce; ?>">am4core.addLicense(<?php echo json_encode((string)$settings["amcharts_license_key"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);</script>
  <?php endif; ?>
  <?php endif; ?>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/Chart.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/jquery.easypiechart.min.js"></script>
  <script defer nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/addons/download.min.js"></script>
  <script nonce="<?php echo $csp_nonce; ?>" src="<?php echo $settings["explorer_address"]; ?>/js/sidenav.js?v=<?php echo $version; ?>"></script>
  <?php if ($beacon_on) :?>
  <script defer nonce="<?php echo $csp_nonce; ?>" src='https://static.cloudflareinsights.com/beacon.min.js' data-cf-beacon='<?php echo htmlspecialchars(json_encode(["token" => $settings["cloudflare_beacon_token"]]), ENT_QUOTES); ?>'></script>
  <?php endif; 
   // <!--Custom scripts-->
  ?>
  <script nonce="<?php echo $csp_nonce;?>">
    $(document).ready(function() {
      setMenuItemActive("<?php echo $page_id; ?>");
      const sideNavScrollbar = document.querySelector('.custom-scrollbar');
      const ps = new PerfectScrollbar(sideNavScrollbar);
    });
  </script>
</body>

</html>
