<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../lib/explorerCustom.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

  $stime=milliseconds();

  //parse_str($_SERVER['QUERY_STRING'], $queries);
  //$name = "";

  //$rid = (isset($_GET['rid'])) ? strtr($_GET['rid'],["_"=>""," "=>"","."=>"",","=>"","!"=>"","\\"=>"","'"=>"","\""=>"","{"=>"","}"=>"",":"=>"",";"=>""]) : "";
  $rid  = sanitizeInput("rid","GET");

  //$mn = (isset($_GET['mn'])) ? strtr($_GET['mn'],"_"," "):null;
  $mn = (isset($_GET['mn'])) ? strtr(sanitizeInput("mn","GET"),"_"," "):null;
  
  $is_valid_rid = db_validateRequestId($rid);


if ($is_valid_rid || ui_checkMagicNumber($mn)){
  $draw=(int)($_GET["draw"] ?? 0);
  $from_time = (int)$_GET["f"];
  $to_time = (int)$_GET["t"];
  $limit = (int)$_GET["l"];

  if($limit>100 || $limit <= 0){ $limit = 100;}

    $consolidations = db_getAssetConsolidationRanking($from_time, $to_time, $limit, 0);

    $co_list = array(); $r = 1;
    foreach ($consolidations as $co){
      $z = db_getAssetData($co->_id)[0];
      $co_item = [$r,'<a class="txlist-hash primary-text" href="'.$settings["explorer_address"].'/asset/?asset_id='.$z->key.'">'.$co->_id.'</a>',rvn_getAssetTypeByName($co->_id),$co->in_num,$co->out_num ];
      $r++;
      array_push($co_list,$co_item);
    }

  $ptime=milliseconds() - $stime;
  echo json_encode([
    "draw"=> $draw,
    "processingtime_ms"=>$ptime,
    "recordsTotal"=>count($co_list),
    "recordsFiltered"=>count($co_list),
    "data"=>$co_list
    ]);
  
} else {
    //header('Content-Type: application/json');
    echo json_encode([
        "draw"=> $draw,
        "processingtime_ms"=>0,
        "recordsTotal"=>0,
        "recordsFiltered"=>0,
        "data"=>[]
    ]);
}

$apt->stopTimer("total");
ui_LogPageEvent($cip,"_dp/assetconsdp",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>