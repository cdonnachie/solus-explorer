<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    include_once __DIR__ . '/../../lib/explorerApi.php';
    if (apiCheckApiRateLimitExceeded(apiGetUserIpAddress())) { http_response_code(429); echo "429 Too Many Requests"; exit; }
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

  //$groupid= $_GET['groupid'];
  $groupid= sanitizeInput('groupid',"GET");

  $rec = db_getAddressGroupById($groupid)[0]->address_count; // <<

  if (isset($settings["export_csv_enabled"]) && $settings["export_csv_enabled"]) {
    if(!empty($groupid)) {
      ini_set('memory_limit', '512M');
      $ams = db_getAddressesByGroupId($groupid, 100000, 0, array(), ['_id'=> 1, 'last_block'=> 1, 'balance' => 1, 'alias' => 1, 'passive_in' => 1, 'sent' => 1, 'received' => 1]); 
      echo '"Address","Alias","Last Block","Balance","Received","Sent","Passive Income"';
      foreach($ams as $am) {
          $m = '';
          $atag = '';
          if (isset($am->alias) && !empty($am->alias)) {
            $atag = '"'.$am->alias.'"';
          }
          $m .= '"'.$am->_id.'",';
          $m .= $atag.",";
          $m .= $am->last_block.",";
          $m .= @cdec($am->balance, $precision)->toFixed($precision,false).",";
          $m .= @cdec($am->received, $precision)->toFixed($precision,false).",";
          $m .= @cdec($am->sent, $precision)->toFixed($precision,false).",";
          $m .= @cdec($am->passive_in, $precision)->toFixed($precision,false);

          echo $m.PHP_EOL;
      }
    }
    ini_set('memory_limit', '128M');
  } else {
    echo "export to csv disabled";
  }
    
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"_dp/addressgroupcsv",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>