<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;

  include_once __DIR__ . '/../../lib/explorerUi.php'; 
  include_once __DIR__ . '/../../lib/explorerDb.php';
  
  global $csp_nonce;
  $csp_nonce = random_hex(8);

  $address = strtr(sanitizeInput('address','GET')," ","_");
  $groupid = strtr(sanitizeInput('groupid','GET')," ","_");

  if (!empty($groupid)){ 
    $q = "groupid"; 
    $q_val=$groupid; 
    $q_str = " - group: $groupid"; 
  } else if(!empty($address)) {
    // default search by address
    $q = "address"; 
    $q_val=$address; 
    $q_str = " - address: $address"; 
    
    // try to find group id
    $ars=db_getAddressData($address); 
    if (count($ars)>0){
      // found it, using group id
      $groupid = $ars[0]->group_id;
      $q = "groupid"; 
      $q_val=$groupid; 
      $q_str = " - group: $groupid"; 
    }
  } else { $q =""; $q_val=""; $q_str = ""; }

  global $description;
  $description = ui_getCoinName()." address group ".$q_val.": consolidated balance and group members.";
  global $keywords;
  $keywords = $settings["title"].", address group, ".$q_val.", balance, group members, addresses, csv export"; 
  global $title;
  $title = ui_getCoinName()." Address Group ".$q_val." | Balance & Members";

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
      loadPage("<?php echo $settings["title"] ?> - Address Group","<?php echo $title; ?>",null,'addressgroup.php?<?php echo $q."=".$q_val; ?>');
    });
  </script>
<?php
  include_once __DIR__ . '/../includes/footer.php';
?>
