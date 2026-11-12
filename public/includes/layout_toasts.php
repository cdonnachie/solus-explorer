<?php
/*
 * Subscription-expiry toastr notices.
 *
 * Shared layout partial, included by header.php (public) and header-admin.php.
 * Not reachable on its own - see includes/.htaccess.
 */
?>
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


