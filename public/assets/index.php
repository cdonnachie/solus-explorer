<?php 
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';    
  global $settings;  
  include_once __DIR__ . '/../../lib/explorerUi.php'; 

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  $mn_str = null;
  $mn = null;
  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

  global $description;
  $description = ui_getCoinName()." assets statistics: new assets, asset transactions and asset types.";
  global $keywords;
  $keywords = $settings["title"].", assets, asset list, asset movements, ipfs, top 100 assets, asset types, statistics";
  global $title;
  $title = ui_getCoinName()." Assets | Statistics & Transactions";

 if ( (isset($settings["show_pages"]["assets"]) && $settings["show_pages"]["assets"]) || (isset($mn) && !empty($mn) && isset($settings["magic_key"]) && $mn == $settings["magic_key"])) : ?>
  <?php
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
      loadPage("<?php echo $settings["title"] ?> - Assets Overview", "<?php echo $title;?>", "Assets", "assets.php<?php echo $mn_str;?>");
    });
  </script>
  <?php
    $use_amcharts = true;
    include_once __DIR__ . '/../includes/footer.php';
  ?>
<?php else :?>
  <?php header("Location: /err/404/");
    exit(0);
  ?>
<?php endif; ?>

