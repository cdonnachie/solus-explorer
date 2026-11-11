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

  // $draw=(int)$_POST["draw"];
  // $skip = (int)$_POST["start"];
  // $limit = (int)$_POST["length"];
  $draw = (int)sanitizeInput("draw","POST");
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  $limit = (int)sanitizeInput("length","POST");

  if($limit>500 || $limit <= 0){ $limit = 500;}

  $result = array();
  if($rec > 0) {
    //$rpc_bc = getRpcBlockCount();
    $blks = db_getLastBlocks($limit, $skip, ["type" => "PoS"]);  // SIP-061 assed $filter
    foreach($blks as $blk) {
        $m = array();
        // $conf = $rpc_bc - $blk->block_ix;
        // if ($conf <= 0) {
        //   $conf_class="danger";
        // } else if ($conf >= (int)$settings["maturity"]) {
        //   $conf_class="success";
        // } else {
        //   $conf_class="warning";
        // }
        $rew_type = "";$winner = "";
        if (isset($blk->type)){
          if ($blk->type == "PoW"){
            $rew_type = '<i class="fas icons-pike text-info ml-1 mb-1 p-1" title="PoW Block"></i>';
            $winner = $blk->pow_winner;
            $sr_split = splitFloat($blk->pow_reward,$precision,$precision);
          } else if($blk->type == "PoS") {
            $rew_type = '<i class="fas fa-tint text-info ml-1 mb-1 p-1" title="PoS Block"></i>';
            $winner = $blk->pos_winner;
            $sr_split = splitFloat($blk->stake_reward,$precision,$precision);
          } else if($blk->type == "Genesis") {
            $rew_type = '<i class="fab fa-creative-commons-zero text-info ml-1 mb-1 p-1" title="Genesis Block"></i>';
            $winner = $blk->pow_winner;
            $sr_split = splitFloat($blk->stake_reward,$precision,$precision);
          }
        }
        $at = db_getAddressTags($winner);
        $tag_str = "";
        $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
        $or_tag = '';
        if (isset($settings['show_op_return']) && $settings['show_op_return'] && isset($blk->has_op_return) && $blk->has_op_return) {
          if (isset($settings['op_return_symbol']) && !empty($settings['op_return_symbol'])) {
            $or_tag = "<i class='".$settings['op_return_symbol']." text-info ml-1 mb-1 p-1 pt-2' title='OP_RETURN Message'></i>&nbsp;";
          } else {
            $or_tag = "<i class='far fa-comment-dots text-info ml-1 mb-1 p-1' title='OP_RETURN Message'></i>&nbsp;";
          }
        }
          $br_split = splitFloat($blk->block_reward,$precision,$precision);
          $fa_split = splitFloat($blk->fee_amount,$precision,$precision);
          $sr_split = splitFloat($blk->stake_reward,$precision,$precision);
          $sb_split = splitFloat($blk->stake_base,$precision,$precision);
          // Timestamp
          array_push($m,"<span class='blocklist-timestamp '>".date('Y-m-d H:i:s', $blk->block_time_sec)."</span>");
          // Height
          array_push($m,"<a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$blk->block_ix."'>".$blk->block_ix."</a>");
          // Hash
          array_push($m,"<a class='blocklist-hash primary-text ' href='".$settings["explorer_address"]."/block/?blockhash=".$blk->_id."'>" .$blk->_id."</a>".$rew_type.$or_tag);
          // Winner
          array_push($m,"<a class='blocklist-hash primary-text ' href='".$settings["explorer_address"]."/address/?address=".$winner."'>" .$winner."</a>".$tag_str);
          // Block Reward
          array_push($m,"<span class='blocklist-amount primary-text '>".$br_split->whole."<small>.".$br_split->fraction."</small></span>");
          // Fees
          array_push($m,"<span class='blocklist-amount primary-text '>".$fa_split->whole."<small>.".$fa_split->fraction."</small></span>");
          // Stake Reward
          array_push($m,"<span class='blocklist-amount primary-text '>".$sr_split->whole."<small>.".$sr_split->fraction."</small></span>");
          // Stake Base
          array_push($m,"<span class='blocklist-amount primary-text '>".$sb_split->whole."<small>.".$sb_split->fraction."</small></span>");

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
    ui_LogPageEvent($cip,"_dp/staking",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>