<?php 
    require __DIR__ . '/../../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../../lib/explorerDb.php';
    global $dbm;
    date_default_timezone_set($settings["timezone"]);

      $ip = apiGetUserIpAddress();
      $bl = apiCheckIpBlackListed($ip);
      $re = apiCheckApiRateLimitExceeded($ip);
      if ($bl){
        header($_SERVER["SERVER_PROTOCOL"]." 551 Request cannot be processed", true, 551);
      } else if ($re) {
        header($_SERVER["SERVER_PROTOCOL"]." 429 Too Many Requests", true, 429);
      } else {
        header('Content-Type: application/json');
        echo json_encode(array("t"=>time(),"version"=>$version,"coin"=>$settings["coin_symbol"]));
      }
?>