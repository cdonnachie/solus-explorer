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
  
  $rec = db_getLastDbBlockNumber();
  $draw = (int)sanitizeInput("draw","POST");
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  $limit = (int)sanitizeInput("length","POST");
  if($limit>500 || $limit <= 0){ $limit = 500;}

  $result = array();
  if($rec > 0) {
    //$rpc_bc = getRpcBlockCount();
    $blks = db_getLastBlocks($limit, $skip, ["type" => "PoW"]); // SIP-061 added filter
    foreach($blks as $blk) {
        $m = array();
        $rew_type = "";$winner = "";
        if (isset($blk->type)){
          if ($blk->type == "PoW"){
            $rew_type = '<i class="fas icons-pike text-info mx-1" title="PoW Block"></i>';
            $winner = $blk->pow_winner;
            $sr_split = splitFloat($blk->pow_reward,$precision,$precision);
          } else if($blk->type == "PoS") {
            $rew_type = '<i class="fas fa-tint text-info mx-1" title="PoS Block"></i>';
            $winner = $blk->pos_winner;
            $sr_split = splitFloat($blk->stake_reward,$precision,$precision);
          } else if($blk->type == "Genesis") {
            $rew_type = '<i class="fab fa-creative-commons-zero text-info mx-1" title="Genesis Block"></i>';
            $winner = $blk->pow_winner;
            $sr_split = splitFloat($blk->stake_reward,$precision,$precision);
          }
        }
          $at = db_getAddressTags($winner);
          $tag_str = "";
          if ($at->has_alias || $at->has_tags){
            if ($at->has_alias && !empty($at->has_alias)){
              $tag_str.= "<span class='badge badge-info mx-1'>".$at->address_alias."</span>&nbsp;";
            }            
            foreach ($at->group_tags as $tag){
              $tag_str.= "<span class='badge badge-primary mx-1'>".$tag."</span>&nbsp;";
            }

          }
          $br_split = splitFloat($blk->block_reward,$precision,$precision);
          $fa_split = splitFloat($blk->fee_amount,$precision,$precision);
          // Timestamp
          array_push($m,"<span class='blocklist-timestamp '>".date('Y-m-d H:i:s', $blk->block_time_sec)."</span>");
          // Height
          array_push($m,"<a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$blk->block_ix."'>".$blk->block_ix."</a>");
          // Hash
          array_push($m,"<a class='blocklist-hash primary-text mr-1' href='".$settings["explorer_address"]."/block/?blockhash=".$blk->_id."'>" .$blk->_id."</a>".$rew_type);
          // Winner
          array_push($m,"<a class='blocklist-hash primary-text mr-1' href='".$settings["explorer_address"]."/address/?address=".$winner."'>" .$winner."</a>".$tag_str);
          // Block Reward
          array_push($m,"<span class='blocklist-amount primary-text '>".$br_split->whole."<small>.".$br_split->fraction."</small></span>");
          // Fees
          array_push($m,"<span class='blocklist-amount primary-text '>".$fa_split->whole."<small>.".$fa_split->fraction."</small></span>");
          // Miner Reward
          array_push($m,"<span class='blocklist-amount primary-text '>".$sr_split->whole."<small>.".$sr_split->fraction."</small></span>");

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
    ui_LogPageEvent($cip,"_dp/mining",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>