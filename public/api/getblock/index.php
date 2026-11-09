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
    // $hash = trim(strtr($_GET['hash'],"_"," "));
    // $index = trim(strtr($_GET['index'],"_"," "));
    $hash =  strtr(sanitizeInput("hash","GET"),"_"," ");
    $index =  strtr(sanitizeInput("index","GET"),"_"," ");

    if (empty($hash) && $index==""){
      header('Content-Type: text/plain');
      echo "400 Bad Request";
      exit;
    }
    if (empty($hash) && !is_numeric($index)){
      header('Content-Type: text/plain');
      echo "400 Bad Request";
      $r = 400;
    } else {
      if (empty($hash) && is_numeric($index)) {
        $hash = rpc_getblockhash((int)$index);
      }

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
        echo apiGetBlock($hash);
        apiRegisterCall($ip);
        $r = 200;
      }
    }

    $apt->stopTimer("total");
    apiLogEvent($ip,"getblock",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST, $r);
?>