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

  $blockhash = $_GET['blockhash'];
  $blockhash = sanitizeInput('blockhash',"GET");

  $result = array();
  
  $stime = milliseconds();
  $blk = db_getBlockByHash($blockhash); 
  if (count($blk)>0){
    $br = $blk[0];
      //$txs = $br->txs;
  //} 
  
    $txs = db_getTxsByBlockHeight($br->block_ix, ['tx_ix' => 1]);
  //foreach($txs as $tx){    
    foreach ($txs as $tr){
    //$trs = db_getTxByHash($tx);
    //if (count($trs)>0){
        //$tr = $trs[0];

        $m = array(); 
        $t_size = normalizeSi($tr->tx_size);
        $t_vouts = splitFloat($tr->vouts_amount, $precision,$precision);
        $t_fees = splitFloat($tr->fee_amount, $precision,$precision);
        $t_move = splitFloat($tr->move_amount, $precision,$precision);
        $reward_tag = '';
        if ($tr->is_reward){
          $reward_tag = $reward_tag."<span class='text-info mx-1' title='Reward TX'><i class='fas fa-star'></i></span>";
        }
        if (isset($tr->op_return) && isset($settings['show_op_return']) && $settings['show_op_return']) {
          if (isset($settings['op_return_symbol']) && !empty($settings['op_return_symbol'])) {
            $reward_tag .= "<span class='text-info mx-1' title='OP_RETURN Message'><i class='".$settings['op_return_symbol']."'></i></span>";
          } else {
            $reward_tag .= "<span class='text-info mx-1' title='OP_RETURN Message'><i class='far fa-comment-dots'></i></span>";
          }
        }

        if (isset($settings["dip2_support"]) && $settings["dip2_support"] && isset($settings["show_dip2"]) && $settings["show_dip2"] && isset($tr->dip2_type) && isset($tr->dip2_msg)) {
          if (isset($settings['dip2_symbol']) && !empty($settings['dip2_symbol'])) {
            $reward_tag .= "<span class='text-info mx-1' title='DIP2 Transaction'><i class='".$settings['dip2_symbol']."'></i></span>";
          } else {
          $reward_tag .= "<span class='text-info mx-1' title='DIP2 Transaction'><i class='fas fa-envelope-open-text'></i></span>";
          }
        }

        if (isset($tr->suspect) && $tr->suspect) {
          $reward_tag .= "<span class='text-danger mx-1' title='Suspected Movements'><i class='fas fa-exclamation-triangle'></i></span>";
        }

        array_push($m, $tr->tx_ix);
        array_push($m,"<a class='primary-text txlist-id mr-1' href='".$settings["explorer_address"]."/tx/?txid=$tr->_id' title='Navigate to transaction ".$tr->_id."'>$tr->_id</a>".$reward_tag);
        array_push($m,"<span class='txlist-size '>". fpp($t_size->value,3) . " " . $t_size->dimension . "bytes</span>");
        array_push($m,"<span class='txlist-vinvout '>". $tr->vins_count."/".$tr->vouts_count."</span>");
        array_push($m,"<span class='txlist-vouts '>". $t_vouts->whole .".<small>".$t_vouts->fraction."</small></span>");
        array_push($m,"<span class='txlist-move '>". $t_move->whole .".<small>".$t_move->fraction."</small></span>");
        array_push($result, $m);

    //}
    }
  }
  $processing_ms = milliseconds() - $stime;
  $res = json_encode(["processingtime_ms" => $processing_ms, "recordsTotal" => count($txs),"recordsFiltered" => count($result),"data" => $result]);
  echo $res;
  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/txlist",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>