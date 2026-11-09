<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php';   

  global $description;
  $description = "Public ".ui_getCoinName()." API endpoints, formats and rate limits. JSON responses for blocks, transactions and addresses.";
  global $keywords;
  $keywords = $settings["title"].", api, api documentation, api reference, api explorer"; 
  global $title;
  $title = ui_getCoinName()." Explorer API | Endpoints & Limits ";

  global $csp_nonce;
  $csp_nonce = random_hex(8);

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
      loadPage("<?php echo $settings["title"] ?> - API","<?php echo $title; ?>","Api","api.php");
    });
  </script>
<?php
  include_once __DIR__ . '/../includes/footer.php';
?>
