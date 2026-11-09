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
    $hex = sanitizeInput('hex',"POST");
    $allowhighfees = sanitizeInput('allowhighfees',"POST");
      $hex = strtolower(clearWebParameter($hex));
      if (!empty($allowhighfees)){
        (clearWebParameter($allowhighfees) == "true") ? $hf = true : $hf = false;
      } else {
        $hf = null;
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
        if (empty($hex)){
          header('Content-Type: application/json');
          $res = new stdClass();
          $res->result = null;
          $res->error = new stdClass();
          $res->error->code = 101;
          $res->error->message = "tx data missing";
          echo json_encode($res);
          $r = 501;
      } else if (!preg_match('/^[a-fA-F0-9]+$/', $hex)) {
          header('Content-Type: application/json');
          $res = new stdClass();
          $res->result = null;
          $res->error = new stdClass();
          $res->error->code = 102;
          $res->error->message = "wrong tx data";
          echo json_encode($res);
          $r = 502;
      } else {
        apiCheckSubscriptionExpired();
        echo apiSendRawTransaction($hex,$hf);
        apiRegisterCall($ip);
        $r = 200;
      }
    }
    
    $apt->stopTimer("total");
    apiLogEvent($ip,"sendrawtransaction",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST, $r);
?>