<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
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
  //$groupid= $_GET['groupid'];
  $groupid = strtr(sanitizeInput("groupid","GET"),"_"," ");
  //$draw=(int)$_POST["draw"];
  $draw = (int)sanitizeInput("draw","POST");
  //$rec = db_getAddressGroupAggregates($groupid)[0]->countAddresses;
  $rec = db_getAddressGroupById($groupid)[0]->address_count; // <<
  //$skip = (int)$_POST["start"];
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  //$limit = (int)$_POST["length"];
  $limit = (int)sanitizeInput("length","POST");

  if($limit>500 || $limit <= 0){ $limit = 500;}

  $result = array();
  if(!empty($groupid)) {
    $ams = db_getAddressesByGroupId($groupid, $limit, $skip, array(), ['_id'=> 1, 'last_block'=> 1, 'balance' => 1, 'alias' => 1]); // <<
    foreach($ams as $am) {
        $atag = '';
        if (isset($am->alias) && !empty($am->alias)) {
          $atag = "<span class='badge badge-info ml-1 mb-1'>".h($am->alias)."</span>&nbsp;";
        }
        $m = array();
          $bal_split = splitFloat($am->balance,$precision,$precision);
          array_push($m,"<a class='txlist-txid primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->_id."'>".$am->_id."</a>".$atag);
          array_push($m,"<a class='txlist-conf' href='".$settings["explorer_address"]."/block/?blockheight=".$am->last_block."'>".$am->last_block."</a>");
          array_push($m,"<span class='txlist-amount'>".$bal_split->whole."<small>.".$bal_split->fraction."</small></span>");
          array_push($result, $m);
        }
    }
  $ptime=milliseconds() - $stime;
  echo json_encode([
    "draw"=> $draw,
    "processingtime_ms"=>$ptime,
    "recordsTotal"=>$rec,
    "recordsFiltered"=>$rec,
    "data"=>$result
    ]);
  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/addressgroup",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>