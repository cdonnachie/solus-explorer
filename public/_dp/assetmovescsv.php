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

  $name = "";
  //$key= ($_GET['asset_id']);
  $key = sanitizeInput("asset_id","GET");


  if (!empty($key)){
    $ast = db_getAssetByKey($key);
    if (count($ast)>0) {
      $name = $ast[0]->_id;
    } 
  }

  $result = array();
  if(isset($settings["export_csv_enabled"]) && $settings["export_csv_enabled"]){ 
    if (!empty($name)) {
      $rpc_bc = getRpcBlockCount();
      ini_set('memory_limit', '512M');
      $rec = db_getAssetMovesCountByName($name);

      if ($rec > 0 ){ 
        header('Content-Type: text/plain');
        echo '"Timestamp","Block","Transaction","Address","Address Tags","Confirmations","Amount"'.PHP_EOL;
      }
      //run procedure for each 1000 lines
      $i = 0;
      $tag_cache = array();
      while ($i*10000 < $rec && $i < 10){
        $ams = db_getAssetMovementsByName($name, 10000,$i*10000); 
        $i++;
        foreach($ams as $am) {
          //$result = '"Timestamp","Block","Transaction","Address","Address Tags","Confirmations","Amount"'.PHP_EOL;
          $m = "";
          $amount = @cdec($am->amount);

          if (!isset($tag_cache[$am->address])) { $tag_cache[$am->address] = db_getAddressTags($am->address); }
          $at = $tag_cache[$am->address];
          $tag_str = "";
          if ($at->has_alias || $at->has_tags){
            if ($at->has_alias && !empty($at->has_alias)){
              $tag_str.= $at->address_alias;
            }            
            foreach ($at->group_tags as $tag){
              if (strlen($tag_str)>0){$tag_str .= "; ";}
              $tag_str.= $tag;
            }
          }
          $conf = $rpc_bc - $am->block_ix;

          $m .= '"'.date('Y-m-d H:i:s', $am->move_time_sec).'",';
          $m .= $am->block_ix.",";
          $m .= '"'.$am->tx_id.'",';
          $m .= '"'.$am->address.'",';
          $m .= ui_csvText($tag_str).",";
          $m .= $conf.",";
          $m .= $amount->toFixed($precision, false).",";

          echo $m.PHP_EOL;

        }
      }
    }
  }
  ini_set('memory_limit', '128M');
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"_dp/assetmovescsv",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>