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

    if (!isset($settings["rvn_assets"]) || !$settings["rvn_assets"]) {
      header($_SERVER["SERVER_PROTOCOL"]." 404 Not Found", true, 404);
      $apt->stopTimer("total");
      apiLogEvent($ip,"getlastcreatedassets",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
      die();
    }

    $to_time = time();
    $from_time = $to_time - 86400;
    $limit = 0;


    $bl = apiCheckIpBlackListed($ip);
    $re = apiCheckApiRateLimitExceeded($ip);
    if ($bl){
      header($_SERVER["SERVER_PROTOCOL"]." 551 Request cannot be processed", true, 551);
      $r = 551;
    } else if ($re) {
      echo "429 Too Many Requests";
      $r = 429;
    } else {
      apiCheckSubscriptionExpired();

      $la = db_getLastCreatedAssets($limit, 0, ['genesis_timestamp' => ['$gte' => $from_time]] );

      header('Content-Type: application/json');
      echo json_encode($la);
      //echo apiGetAssetInfo($name, $withaddresslist, $use_key);
      apiRegisterCall($ip);
      $r = 200;
    }

    $apt->stopTimer("total");
    apiLogEvent($ip,"getlastcreatedassets",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST,$r);
?>