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

  //$addr= strtr($_GET['addr'],"_"," ");
  $addr =  strtr(sanitizeInput("addr","GET"),"_"," ");

  /* The count that used to sit here was never read. It ran before a single byte of
     CSV was written, so on a large address it spent seconds, and on a very large one
     it blew the Mongo time budget and turned the download into an uncaught exception
     -- a 500 for a result nothing consumed. Removed rather than made safe. */
  //$skip = (int)$_POST["start"];
  //$limit = (int)$_POST["length"];
  
  if (isset($settings["export_csv_enabled"]) && $settings["export_csv_enabled"]) {
    //$result = '"Timestamp","Block","Transaction","Confirmations","Is reward","Flag","Amount"'.PHP_EOL;
    if ($addr == "Airdrop"){
      echo '"Timestamp","Block","Transaction","Owner Address","Confirmations","Is reward","Flag","Amount"'.PHP_EOL;
    } else {
      echo '"Timestamp","Block","Transaction","Confirmations","Is reward","Flag","Amount"'.PHP_EOL;
    }
    if(!empty($addr)) {
      $rpc_bc = getRpcBlockCount();
      ini_set('memory_limit', '512M');
      $ams = db_getAddressMovesDataByAddress($addr, 100000, 0); // newest 100 000 movements
      foreach($ams as $am) {
          $m = "";
          $conf = $rpc_bc - $am->block_ix;
          $am->amount = @cdec($am->amount, $precision);

          $reward_tag = '';
          if (isset($am->is_reward) && $am->is_reward){
            if ($am->is_pow_reward){
              $reward_tag = "PoW Reward";
            } 
            if ($am->is_pos_reward) {
              if (isset($am->is_coldstake) && $am->is_coldstake) {
                $reward_tag = "ColdStake Reward";
              } else {
                $reward_tag = "PoS Reward";
              }
            } 
            if ($am->is_mn_reward) {
              $reward_tag = "MN Reward";
            } 
            if ($am->is_dev_reward) {
              if (isset($settings["dev_symbol"])){
                $reward_tag = "Developer Fee";
              }
            }
            if (isset($am->type)){
              // custom vout type
              if ($am->type == "spow" && isset($settings["spow_symbol"])){
                $reward_tag = $reward_tag. "SPoW Reward";
              } else if ($am->type == "mn_ops" && isset($settings["mnops_symbol"])){
                $reward_tag = $reward_tag. "MN Operations Reward";
              }
            }
          }
          $flag = '';
          if (isset($am->suspect) && $am->suspect) {
            $flag = "Suspected Movement";
          }

        if($conf < 0){$conf="";}
        // "Timestamp,Block,Transaction,Confirmations,Is reward,Flag,Amount".PHP_EOL;
        $m .= '"'.date('Y-m-d H:i:s', $am->move_time_sec).'",';
        $m .= $am->block_ix.",";
        $m .= '"'.$am->tx_id.'",';
        if ($addr == "Airdrop") {
          $owner = (isset($am->staker)) ? $am->staker : null;
          if (!empty($owner)){
            $m .= '"'.$owner.'",';
          } else {
            $m .= ',';
          }
        }
        $m .= $conf.',';
        $m .= '"'.$reward_tag.'",';
        $m .= '"'.$flag.'",';
        $m .= $am->amount->toFixed($precision,false);
            
        //$result .= $m.PHP_EOL;
        echo $m.PHP_EOL;
      }
    }
  } else {
    echo "export to csv disabled";
  }
  //echo $result;
  ini_set('memory_limit', '128M');
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"_dp/addressmovescsv",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>