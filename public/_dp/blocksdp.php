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
  // $draw=(int)$_POST["draw"];
  // $skip = (int)$_POST["start"];
  // $limit = (int)$_POST["length"];
  $rec = db_getLastDbBlockNumber();
  $draw = (int)sanitizeInput("draw","POST");
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  $limit = (int)sanitizeInput("length","POST");

  if($limit>500 || $limit <= 0){ $limit = 500;}

  $result = array();
  if($rec >= 0) {
    $rpc_bc = getRpcBlockCount();
    $blks = db_getLastBlocks($limit, $skip); 
    foreach($blks as $blk) {
        $m = array();
        $conf = $rpc_bc - $blk->block_ix +1;
        if ($conf <= 0) {
          $conf_class="danger";
        } else if ($conf >= (int)$settings["maturity"]) {
          $conf_class="success";
        } else {
          $conf_class="warning";
        }
        $rew_type = "";
        if (isset($blk->type)){
          if ($blk->type == "PoW"){
            $rew_type = '<i class="fas icons-pike text-info mx-1" title="PoW Block"></i>';
          } else if($blk->type == "PoS") {
            $rew_type = '<i class="fas fa-tint text-info mx-1" title="PoS Block"></i>';
          } else if ($blk->type == "Genesis"){
            $rew_type = '<i class="fab fa-creative-commons-zero text-info mx-1" title="Genesis Block"></i>';
          }
        }

        $or_tag = "";
        if (isset($settings['show_op_return']) && $settings['show_op_return'] && isset($blk->has_op_return) && $blk->has_op_return) {
          if (isset($settings['op_return_symbol']) && !empty($settings['op_return_symbol'])) {
            $or_tag = "<i class='".$settings['op_return_symbol']." text-info mx-1' title='OP_RETURN Message'></i>";
          } else {
            $or_tag = "<i class='far fa-comment-dots text-info mx-1' title='OP_RETURN Message'></i>";
          }
        }

          $move_split = splitFloat($blk->move_amount,$precision,$precision);
          $nsize = normalizeSiToScale($blk->block_size,"k",3);
          $size_split = splitFloat($nsize->value, 3, 3);
          // Timestamp
          array_push($m,"<span class='blocklist-timestamp '>".date('Y-m-d H:i:s', $blk->block_time_sec)."</span>");
          // Height
          array_push($m,"<a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$blk->block_ix."' title='Navigate to block ".$blk->block_ix."'>".$blk->block_ix."</a>");
          // Hash
          array_push($m,"<a class='blocklist-hash primary-text mr-1' href='".$settings["explorer_address"]."/block/?blockhash=".$blk->_id."' title='Navigate to block $blk->_id'>" .$blk->_id."</a>".$rew_type.$or_tag);
          // Confirmations
          array_push($m,"<span class='blocklist-confirmations badge badge-".$conf_class." h6 mb-0'>".$conf."</span>");
          // Block Size
          array_push($m,"<span class='blocklist-txcount '>".$size_split->whole.".<small>".$size_split->fraction."</small></span>");
          // TX Count
          array_push($m,"<span class='blocklist-txcount '>".$blk->tx_count."</span>");
          // Moved Amount
          array_push($m,"<span class='blocklist-amount primary-text '>".$move_split->whole."<small>.".$move_split->fraction."</small></span>");

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
    ui_LogPageEvent($cip,"_dp/blocks",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>