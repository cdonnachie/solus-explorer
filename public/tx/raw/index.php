<?php
  include_once __DIR__ . '/../../../lib/common.php';
  require __DIR__ . '/../../../settings.php';
  global $settings;  
  include_once __DIR__ . '/../../../lib/explorerUi.php'; 

  global $csp_nonce;
  $csp_nonce = random_hex(8);
  
  $txid = sanitizeInput('txid',"GET");
  if(!empty($txid)){ $tx_str = ": $txid";  } 
  else { $tx_str = ""; }

  global $description;
  $description = "Transaction: ".$txid." | ".$settings["title"];
  global $keywords;
  $keywords = $settings["title"].", raw transaction, ".$txid.", raw, hex, json, decode"; 
  global $title;
  $title = ui_getCoinName()." Raw TX ".$txid." | Raw JSON";

  /*
   * As with /block/raw/: the page is a spinner and the JSON is fetched
   * client-side, so it is never indexable content. A txid that is not 64 hex
   * characters is answered 404 on syntax alone, with no lookup.
   */
  if ($txid === '' || !ctype_xdigit($txid) || strlen($txid) !== 64){
      ui_markNotIndexable(404);
  } else {
      ui_markNotIndexable();
  }

  include_once __DIR__ . '/../../includes/header.php';
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
      loadPage("<?php echo $settings["title"] ?> - Raw transaction", "<?php echo $title; ?>",null, 'rawtransaction.php?txid=<?php echo $txid; ?>');
      });
  </script>
<?php
  include_once __DIR__ . '/../../includes/footer.php';
?>
