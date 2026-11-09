<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  include_once __DIR__ . '/../../lib/explorerUi.php'; 
  include_once __DIR__ . '/../../lib/explorerDb.php';

  $name = "";
  $key = "";
  if (array_key_exists("name",$_GET)){
    $name = sanitizeInput('name',"GET");
    $ast = db_getAssetByName($name, true);
    if (count($ast)>0){
      $key = $ast[0]->key;
    }
  } else if (array_key_exists("asset_id",$_GET)){
    $key = sanitizeInput("asset_id","GET");
    $ast = db_getAssetByKey($key);
    if (count($ast)>0){
      $name = $ast[0]->_id;
    }

  }
  
  if(!empty($name)){ $name_str = ": $name"; } else { $name_str = ""; }

  global $description;
  $description = ui_getCoinName()." asset ".$name.": current supply, holders and transactions. View asset details and distribution.";
  global $keywords;
  $keywords = $settings["title"].", address, ".$name.", balance, transactions, statistics, charts, csv export"; 
  global $title;
  $title = ui_getCoinName()." Asset ".$name." | Balance, Holders & Transactions";

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
  <div class="d-none">
    <p><?php echo $name; ?></p>
    <p><?php echo $key; ?></p>
  </div>
</main>
<!--/Main layout-->


  <script nonce="<?php echo $csp_nonce; ?>" type='text/javascript'>
    $(document).ready(function(){
      loadPage("<?php echo $settings["title"] ?> - Asset","<?php echo $title; ?>",null,'asset.php?asset_id=<?php echo $key; ?>');
    });
  </script>
<?php
  include_once __DIR__ . '/../includes/footer.php';
?>
