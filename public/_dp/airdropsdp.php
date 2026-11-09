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

  //if (!isset($settings["show_pages"]["airdrop"]) || !($settings["show_pages"]["airdrop"])){
  //  header("404 Not Found",true,404);
  //}
  
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

  $stime=milliseconds();
  $result = array();

  $uncla = db_getUnclaimedAirdrops();
  foreach($uncla as $ad) {
    //var_dump($ad);
    $m = array();
    $amount_split = splitFloat(@cdec($ad->amount),$precision,$precision);
    // Address
    array_push($m,"<a class='txlist-txid primary-text' href='". $settings["explorer_address"]."/address/?address=".$ad->address."'>".$ad->address."</a>");
    // Amount
    array_push($m,$amount_split->whole.".".$amount_split->fraction);
    // Status
    array_push($m,"<span class='badge badge-info mnlist-status h6 mb-0' style='width:8em;'>OPEN</span>");

    array_push($result, $m);
  }

  $cla = db_getClaimedAirdrops();
  foreach($cla as $ad) {
    $m = array();
    $amount_split = splitFloat(@cdec($ad->vins_amount),$precision,$precision);
    // Address
    array_push($m,"<a class='txlist-txid primary-text' href='".$settings["explorer_address"]."/address/?address=".$ad->staker."'>".$ad->staker."</a>");
    // Amount
    array_push($m,$amount_split->whole.".".$amount_split->fraction);
    // Status
    array_push($m,"<span class='badge badge-success mnlist-status h6 mb-0' style='width:8em;'>CLAIMED</span><a class='pl-1' href='".$settings["explorer_address"]."/tx/?txid=".$ad->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a>");

    array_push($result, $m);
  }

  $ptime = milliseconds() - $stime;
  header('Content-Type: application/json');
  echo json_encode(["processingtime_ms" => $processing_ms, "recordsTotal" => count($result),"recordsFiltered" => count($result),"data" => $result]);
  
  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/airdrops",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>