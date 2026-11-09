<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php'; 

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  global $description;
  $description = "Live ".ui_getCoinName()." masternode metrics including counts, payouts, and status with historical trends.";
  global $keywords;
  $keywords = $settings["title"].", masternodes, MN list, rewards, masternode status, collateral"; 
  global $title;
  $title = ui_getCoinName()." Masternodes | Counts, Rewards & Status";

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
      loadPage("<?php echo $settings["title"] ?> - Masternodes","<?php echo $title; ?>","Masternodes","masternodes.php");
    });
  </script>
<?php
  include_once __DIR__ . '/../includes/footer.php';
?>
