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

  $qtype = strtr(sanitizeInput('qType',"POST"),"_"," ");
  $pduration = (int)strtr(sanitizeInput('pDuration',"POST"),"_"," ");
  if (!in_array($pduration, [1, 7, 30, -1], true)) { $pduration = 7; }
  $bstart = strtr(sanitizeInput('bStart',"POST"),"_"," ");
  $bend = strtr(sanitizeInput('bEnd',"POST"),"_"," ");
  $rhide = strtr(sanitizeInput('rHide',"POST"),"_"," ");

  ($rhide == "true") ? $hide_rewards = true : $hide_rewards = false;

  // $draw=(int)$_POST["draw"];
  // $skip = (int)$_POST["start"];
  // $limit = (int)$_POST["length"];
  $draw = (int)sanitizeInput("draw","POST");
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  $limit = (int)sanitizeInput("length","POST");
  if($limit>500 || $limit <= 0){ $limit = 500;}
  $rec = 0;
  $result = array();

  if(!empty($qtype)) {
    try {
      $db_bc = db_getLastDbBlockNumber();
      try {
        $rpc_bc = getRpcBlockCount();
      } catch (Exception $ex){
        $rpc_bc = $db_bc;
      }
      
      if (isset($qtype)){
        if ($qtype == 'period') {
          $end_time = time();
          if (isset($pduration)){
            ($pduration >= 0) ? $start_time = $end_time - $pduration*86400 : $start_time = 0;
          } else {
            $start_time = $end_time - 7*86400;
          }
          if ($start_time == 0){
            $filter = ['amount' => [ '$ne' => 0 ]];
          } else {
            $filter = ['move_time_sec' => ['$gte' => $start_time, '$lte' => $end_time], 'amount' => [ '$ne' => 0 ]];
          }
          
        } else if ($qtype == 'block') {
          if (isset($bstart) && isset($bend)){
            if (is_numeric($bstart) && is_numeric($bend) && (int)$bend >= (int)$bstart && (int)$bend - (int)$bstart <= 100000){        
              $filter = ['block_ix' => ['$gte' => (int)$bstart, '$lte' => (int)$bend], 'amount' => [ '$ne' => 0 ]];
            } else {
              $filter = ['block_ix' => ['$gte' => $db_bc - 1440, '$lte' => $db_bc], 'amount' => [ '$ne' => 0 ]];
            }
          }
        }
        if ($hide_rewards){
           $filter['is_reward'] = false;     
        }
      }
    
      $rec = db_getAddressMovesCountWithFilter($filter);
      $ams = db_getAddressMovesDataWithFilter($filter, $limit, $skip); 
      foreach($ams as $am) {
        $m = array();
        $conf = $rpc_bc - $am->block_ix;
        $am->amount = @cdec($am->amount);

        $reward_tag = '';
        if (isset($am->is_reward) && $am->is_reward){
          if ($am->is_pow_reward){
            $reward_tag .= "<span class='text-info ml-1 mb-1 p-1' title='PoW Reward'><i class='fas icons-pike'></i></span>&nbsp;";
          } 
          if ($am->is_pos_reward) {
            $reward_tag .= "<span class='text-info ml-1 mb-1 p-1' title='PoS Reward'><i class='fas fa-tint'></i></span>&nbsp;";
            if (isset($am->is_coldstake) && $am->is_coldstake) {
              $reward_tag = $reward_tag. "<span class='text-info mb-1 p-1' title='Coldstake Reward'><i class='".$settings["coldstake_symbol"]."'></i></span>&nbsp;";
            } 
          } 
          if ($am->is_mn_reward) {
            $reward_tag .= "<span class='text-info ml-1 mb-1 p-1' title='MN Reward'><i class='fab fa-hubspot'></i></span>&nbsp;";
          } 
          if ($am->is_dev_reward) {
            if (isset($settings["dev_symbol"])){
              // custom dev_symbol
              $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='Developer Fee'><i class='".$settings["dev_symbol"]."'></i></span>&nbsp;";
            } else {
              $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='Developer Fee'><i class='fas fa-cog'></i></span>&nbsp;";
            }
            
          }
          if (isset($am->type)){
            // custom vout type
            if ($am->type == "spow" && isset($settings["spow_symbol"])){
              $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='SPoW Reward'><i class='".$settings["spow_symbol"]."'></i></span>&nbsp;";
            } else if ($am->type == "mn_ops" && isset($settings["mnops_symbol"])){
              $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='MN Operations Reward'><i class='".$settings["mnops_symbol"]."'></i></span>&nbsp;";
            } else if ($am->type == "mlc" && isset($settings["mlc_symbol"])){
              $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='MLC Reward'><i class='".$settings["mlc_symbol"]."'></i></span>&nbsp;";
            }
          }
        }
        if (isset($am->suspect) && $am->suspect) {
          $reward_tag .= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movements'></i>&nbsp;";
        }
        $or_tag = '';
        if (isset($settings['show_op_return']) && $settings['show_op_return']) {
          $tr = db_getTxByHash($am->tx_id);
          if (count($tr)>0){
            $tr = $tr[0];
            if (isset($tr->op_return)) {
              if (isset($settings['op_return_symbol']) && !empty($settings['op_return_symbol'])) {
                $or_tag = "<i class='".$settings['op_return_symbol']." text-info ml-1 mb-1 p-1 pt-2' title='OP_RETURN Message'></i>&nbsp;";
              } else {
                $or_tag = "<i class='far fa-comment-dots text-info ml-1 mb-1 p-1' title='OP_RETURN Message'></i>&nbsp;";
              }
            }
          }
        }
        if($conf < 0){$conf="---";}
          if ($am->amount<0) {
            $am_class="danger";
          } else if ($am->amount>0) {
            $am_class="success";
          } else {
            $am_class="muted";
          }
        $amount_split = splitFloat($am->amount,$precision,$precision);
        //$ai = db_getAddressData($am->address)[0];
        $at = db_getAddressTags($am->address);
        $tag_str = "";
        if ($at->has_alias || $at->has_tags){
          if ($at->has_alias && !empty($at->has_alias)){
            $tag_str.= "<span class='badge badge-info ml-1 mb-1'>".$at->address_alias."</span>&nbsp;";
          }            
          foreach ($at->group_tags as $tag){
            $tag_str.= "<span class='badge badge-primary ml-1 mb-1'>".$tag."</span>&nbsp;";
          }
        }

        array_push($m,"<span class='txlist-timestamp '>".date('Y-m-d H:i:s', $am->move_time_sec)."</span>");
        array_push($m,"<a class='txlist-txid text-primary' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str);
        array_push($m,"<a class='txlist-txid primary-text' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."'>".$am->tx_id."</a>".$reward_tag.$or_tag);
        array_push($m,"<a class='txlist-conf' href='".$settings["explorer_address"]."/block/?blockheight=".$am->block_ix."'>".$conf."</a>");
        array_push($m,"<span class='txlist-amount text-".$am_class."'>".$amount_split->whole."<small>.".$amount_split->fraction."</small></span>");
        array_push($result, $m);
      }
    } catch(Exception $e) {}
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
    ui_LogPageEvent($cip,"_dp/movements",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
  ?>