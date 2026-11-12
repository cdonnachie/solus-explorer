<?php
  include_once __DIR__ . '/../../../lib/common.php';
  require __DIR__ . '/../../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../../lib/explorerSec.php';
  include_once __DIR__ . '/../../../lib/explorerDb.php';  
  include_once __DIR__ . '/../../../lib/explorerUi.php'; 

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  global $description;
  $description = "Loader log | ".$settings["title"];
  global $keywords;
  $keywords = $settings["title"]."";
  global $title;
  $title = "Loader Log | ".$settings["title"];

  $session = sec_Authenticate(ui_GetUserIpAddress(), ui_GetUserAgent());

if (!$session->authenticated): 
    sec_InvalidateCookie();
    header("Location: ".$settings["explorer_address"]."/".ADMIN_PATH."/", true, 303);
    exit;
  else :
?>
  <?php
    include_once __DIR__ . '/../../includes/header-admin.php';
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
      loadPage("<?php echo $settings["title"] ?> - Loader Log","<?php echo $settings["title"] ?> - Loader Log",null,"loaderlog.php");
    });
  </script>
<?php
  include_once __DIR__ . '/../../includes/footer-admin.php';
?>
<?php endif; ?>