<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php'; 

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  global $description;
  $description = "Track ".ui_getCoinName()." coin movements between addresses to understand on-chain flows and trends.";
  global $keywords;
  $keywords = $settings["title"].", transactions, movements, movement list, movement details, movement statistics"; 
  global $title;
  $title = ui_getCoinName()." Coin Movements | Inflows & Outflows";

  include_once __DIR__ . '/../includes/header.php';
?>
  <!--Main layout-->
  <main>

    <div id="content" class="container-fluid">
      
      <div class="row text-center" style="margin-top:5%;">
        <div class="col align-self-center">
          <div class="preloader-wrapper small active">
            <div class="spinner-layer spinner-blue-only">
              <div class="circle-clipper left">
                <div class="circle"></div>
              </div>
              <div class="gap-patch">
                <div class="circle"></div>
              </div>
              <div class="circle-clipper right">
                <div class="circle"></div>
              </div>
            </div>
          </div>
        
          <span class="h6 primary-text ml-3">Loading, please wait...</span>
        </div>
      </div>

    </div>
</main>
<!--/Main layout-->

  <script nonce="<?php echo $csp_nonce; ?>" type='text/javascript'>
    $(document).ready(function(){
      loadPage("<?php echo $settings["title"] ?> - Coin Movements","<?php echo $title; ?>",'Movements',null);
      $("#content").load('movements'.toLowerCase() + ".php", <?php
        $mv_params = array();
        foreach (["qType", "pDuration", "bStart", "bEnd"] as $mv_key) {
          if (isset($_POST[$mv_key]) && is_string($_POST[$mv_key])) { $mv_params[$mv_key] = $_POST[$mv_key]; }
        }
        echo json_encode((object)$mv_params, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
      ?>, function() {});
    });
  </script>
<?php
  include_once __DIR__ . '/../includes/footer.php';
?>
