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
    /* Say what this is. The export button fetches it with XHR and names the file
       itself, so these matter only when the URL is opened directly -- which it
       can be, and without them the browser renders a CSV as a page. */
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.
           preg_replace('/[^A-Za-z0-9_.-]/', '', (string)$addr).'_movements.csv"');
    header('X-Robots-Tag: noindex, nofollow');

    //$result = '"Timestamp","Block","Transaction","Confirmations","Is reward","Flag","Amount"'.PHP_EOL;
    if ($addr == "Airdrop"){
      echo '"Timestamp","Block","Transaction","Owner Address","Confirmations","Is reward","Flag","Amount"'.PHP_EOL;
    } else {
      echo '"Timestamp","Block","Transaction","Confirmations","Is reward","Flag","Amount"'.PHP_EOL;
    }
    if(!empty($addr)) {
      $rpc_bc = getRpcBlockCount();
      /* Streamed from the cursor rather than read into an array first. The array
         form held every row in memory at once, which is why this endpoint used to
         raise memory_limit to 512M; batches keep it flat instead, whatever the cap
         is set to, and the reader starts receiving rows immediately.

         Wrapped because the query carries a time limit and a breach throws. By
         then the header row has been sent and the status line is long gone, so
         there is no 500 left to give: the only honest ending is to stop and say
         in the file itself that it stopped. */
      $cap = db_getCsvExportMaxRows();
      $rows = 0;
      $truncated = false;
      $failed = null;
      try {
        $ams = db_getAddressMovesCursorByAddress($addr, $cap, 0);
        foreach($ams as $am) {
          $rows++;
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
        /* A movement with no timestamp has to export as blank. date() given null
           returns the current time, so this column used to carry the moment of
           export dressed up as the moment of the movement -- two exports of the
           same address disagreed, and neither was right. (Passing null here is
           also deprecated from PHP 8.1.) The loader stores null when the daemon
           omits "time" from getrawtransaction; see the A.6 defect. */
        $m .= isset($am->move_time_sec)
            ? '"'.date('Y-m-d H:i:s', $am->move_time_sec).'",'
            : '"",';
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
        if ($cap > 0 && $rows >= $cap) { $truncated = true; }
      } catch (Throwable $e) {
        $failed = $e;
      }

      /* A short file and a complete one look identical once downloaded, so when
         this one is short it has to say so. Written as a row rather than a
         comment because CSV has no comments: a reader that ignores it still
         parses, and a human opening the file sees it at the bottom. */
      if (!is_null($failed)) {
        echo '"Export stopped after '.$rows.' rows: the query exceeded its time limit. '.
             'Rows above are complete and in order, newest first.",'.PHP_EOL;
      } else if ($truncated) {
        echo '"Export limited to the '.$rows.' most recent movements. '.
             'This address has more; raise export_csv_max_rows or use the API for the full history.",'.PHP_EOL;
      }
    }
  } else {
    echo "export to csv disabled";
  }
  //echo $result;
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"_dp/addressmovescsv",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>