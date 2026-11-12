<?php 
  require_once __DIR__ . '/../../lib/common.php';
  include_once __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerSec.php';
  include_once __DIR__ . '/../../lib/explorerDb.php';
  include_once __DIR__ . '/../../lib/explorerUi.php'; 
  
  global $csp_nonce;
  $csp_nonce = random_hex(8);

  
  global $description;
  $description = "Admin access | ".$settings["title"];
  global $keywords;
  $keywords = $settings["title"].""; 
  global $title;
  $title = "Admin Access | ".$settings["title"];

  $session = sec_Authenticate(ui_GetUserIpAddress(), ui_GetUserAgent());
  if ($session->authenticated) :

    header("Location: ".$settings["explorer_address"]."/".ADMIN_PATH."/dashboard/", true, 303);
    exit;
  else :
?>
      <?php include_once __DIR__ . '/../includes/header-admin.php'; ?>
      <script  nonce="<?php echo $csp_nonce; ?>" type='text/javascript'>
        // Set Page Properties
        document.getElementById("pageName").innerHTML = "<?php echo $settings["title"] ?> - Administrative Access";
        document.title = "<?php echo $settings["title"]; ?> - Administrative Access";
      </script>
      <!-- Material form login -->
      <main>
        <div id="content" class="container-fluid">
          <div class="col-xl-5 col-lg-6 col-md-10 col-sm-12 mx-auto mt-auto">
            <div class="card">

              <h5 class="card-header primary-color white-text text-center py-4">
                <strong>Sign in</strong>
              </h5>

              <!--Card content-->
              <div class="card-body px-lg-5 pt-0">

                <!-- Form -->
                <form id="loginForm" class="text-center" style="color: #757575;" >

                  <!-- Email -->
                  <div class="md-form">
                    <label for="materialLoginFormUser">Username</label>
                    <input type="text" id="materialLoginFormUser" class="form-control" >
                  </div>

                  <!-- Password -->
                  <div class="md-form pb-2">
                    <label for="materialLoginFormPassword">Password</label>
                    <input type="password" id="materialLoginFormPassword" class="form-control">
                  </div>

                  <div class="md-form pb-2 text-center" style='height: 2em;'>
                    <span class="w-100 text-danger" id="errorText"></span>
                  </div>

                  <!-- Sign in button -->
                  <button class="btn btn-info btn-rounded btn-block my-4 z-depth-0" type="submit">Sign in</button>

                </form>
                <!-- Form -->

              </div>

            </div>
          </div>
        </div>
      </main>
      <script nonce="<?php echo $csp_nonce; ?>" type="text/javascript">
            $( "#loginForm" ).submit(function( event ) {
              $('#errorText').html('');
              var user = $("#materialLoginFormUser").val().trim();
              var phash = Promise.resolve(sha512( $( "#materialLoginFormPassword" ).val() )).then(function(value){ 
                $.ajax({
                  url : 'login.php',
                  type : 'POST',
                  dataType : 'json',
                  data : {
                    u: user,
                    p: value
                  },
                  success : (function ( json ) {
                    if (json.rcode == 1){
                      window.location = '<?php echo $settings["explorer_address"]."/".ADMIN_PATH; ?>/dashboard/';
                    } else {
                      // process error
                      $('#errorText').html(json.rerr);
                    }
                  }),
                });
              });
              
              event.preventDefault();
            });   
          </script>
      <!-- Material form login -->   
    <?php endif; ?>
<?php

?>

<?php
  include_once __DIR__ . '/../includes/footer-admin.php';
?>
