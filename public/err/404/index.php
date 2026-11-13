<?php
  
  
  include_once __DIR__ . '/../../../lib/common.php';
  global $csp_nonce;
  $csp_nonce = random_hex(8);

  http_response_code(404);
  header("X-Robots-Tag: noindex, nofollow");
  // The header already protects this page; without this the meta tag emitted by
  // layout_head.php still said "index, follow", which simply contradicted it.
  global $meta_robots;
  $meta_robots = "noindex, nofollow";
  include_once __DIR__ . '/../../includes/header.php';
  
?>
  <script nonce="<?php echo $csp_nonce; ?>" type='text/javascript'>
    // Set Page Properties
    var pn = "Page Not Found";
    document.getElementById("pageName").innerHTML = "<?php echo $settings["title"] ?> - " + pn;
    //document.getElementById(pn).className += " active";
    document.title = "<?php echo $settings["title"]; ?>" + " - " + pn;
  </script>
  <style  nonce="<?php echo $csp_nonce; ?>">
    body {
      background-attachment: fixed;
      background-repeat: no-repeat;
      background-size:  50%;
      background-position: center;
      -webkit-background-size:  50%;
      background-image: url("<?php echo $settings["explorer_address"]; ?>/img/404-light.svg");

    }
  </style>
  <!--Main layout-->
  <main>
    
    <div id="content" class="container-fluid ">

      <div class="row text-center" style="margin-top:5%;">
        <div class="col align-self-center">
          <div class="w-100 mt-3 text-center ">
            <h4 class="text-danger">Sorry, we did'n find the page you were looking for...</h4><p><?php echo htmlspecialchars((string)($_SERVER['REQUEST_URI'] ?? ''), ENT_QUOTES); ?></p>
          </div>
        </div>
      </div>

    </div>

</main>
<!--/Main layout-->

<?php
  include_once __DIR__ . '/../../includes/footer.php';
?>
