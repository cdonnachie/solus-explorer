<?php
    require __DIR__ . '/../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    
    global $rpc;
    global $dbm;
    date_default_timezone_set($settings["timezone"]);
    $ip = apiGetUserIpAddress();
    $address = trim(strtr(sanitizeInput('address',"GET"),"_"," "));
    $r = 0;
    if (empty($address)){
      header('Content-Type: text/plain');
      echo "400 Bad Request";
      $r = 400;
    } else {
      $bl = apiCheckIpBlackListed($ip);
      $re = apiCheckApiRateLimitExceeded($ip);
      if ($bl){
        header($_SERVER["SERVER_PROTOCOL"]." 551 Request cannot be processed", true, 551);
        //echo "551 Request cannot be processed";
        $r = 551;
      } else if ($re) {
        echo "429 Too Many Requests";
        $r = 429;
      } else {
        apiCheckSubscriptionExpired();
        echo apiGetAddress($address);
        apiRegisterCall($ip);
        $r = 200;
      }
    }

    
    $apt->stopTimer("total");
    apiLogEvent($ip,"getaddress",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST, $r);
?>