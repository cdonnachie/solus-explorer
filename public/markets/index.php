<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php'; 

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  global $description;
  $description = ui_getCoinName()." market overview across supported exchanges with price, pairs and 24-hour volume.";
  global $keywords;
  $keywords = $settings["title"].", markets, price evolution, capitalization"; 
  global $title;
  $title = ui_getCoinName()." Markets | Prices & Volume";

  include_once __DIR__ . '/../includes/header.php';
?>
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
      loadPage("<?php echo $settings["title"] ?> - Market Info","<?php echo $title; ?>","Markets","markets.php");
    });
  </script>
<?php
  include_once __DIR__ . '/../includes/footer.php';
?>
