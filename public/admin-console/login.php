<?php 
  include_once __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerSec.php';
  include_once __DIR__ . '/../../lib/explorerDb.php'; 
  include_once __DIR__ . '/../../lib/explorerUi.php'; 
  include_once __DIR__ . '/../../lib/timeTracker.php';
  
  $apt = new TimeTracker;
  $apt->createTimer("total",0);
  $cip = ui_GetUserIpAddress();

  
  $session = sec_Authenticate($cip, ui_GetUserAgent());

  $err = "";

    if (empty($_POST['u'])){
        $auth = false;
        $err = "Username missing.";
    }
  
  if (!$session->authenticated){
    if (!sec_sameOrigin()){
        $auth = false;
        $err = "Request refused.";
    } else if (empty($_POST['u'])){
        $auth = false;
        $err = "Username missing.";
    } else {
        // here check user/password
        $auth = $session->authenticateSession(sanitizeInput('u',"POST"), sanitizeInput('p',"POST"), $cip, ui_GetUserAgent());
        if (!$auth) {
            $err = $session->throttled ? "Too many failed attempts, try again in 15 minutes." : "Authentication failed.";
        }
    }
   } else {
    $auth = true;
   }

    $code = (int) $auth;
    // return result
    echo json_encode([
        "rcode"  => $code,
        "rerr"   => $err
        ]);


?>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"admin/login",(int)$apt->getDurationMicroSeconds("total"));
?>