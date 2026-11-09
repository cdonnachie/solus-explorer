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
  //$addr= strtr($_GET['addr'],"_"," ");
  $addr =  strtr(sanitizeInput("addr","GET"),"_"," ");

  //$draw=(int)$_POST["draw"];
  $draw = (int)sanitizeInput("draw","POST");

  $rec = db_getAddressMovesCountByAddress($addr);
  //$skip = (int)$_POST["start"];
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  //$limit = (int)$_POST["length"];
  $limit = (int)sanitizeInput("length","POST");

  if($limit>500 || $limit <= 0){ $limit = 500;}

  $result = array();
  if(!empty($addr)) {
    $rpc_bc = getRpcBlockCount();
    $ams = db_getAddressMovesDataByAddress($addr, $limit, $skip); 
    foreach($ams as $am) {
        $m = array();
        $conf = $rpc_bc - $am->block_ix;
        $am->amount = @cdec($am->amount);

        $reward_tag = '';
        if (isset($am->is_reward) && $am->is_reward){
          if ($am->is_pow_reward){
            $reward_tag .= "<span class='text-info mx-1' title='PoW Reward'><i class='fas icons-pike'></i></span>";
          } 
          if ($am->is_pos_reward) {
            $reward_tag .= "<span class='text-info mx-1' title='PoS Reward'><i class='fas fa-tint'></i></span>";
            if (isset($am->is_coldstake) && $am->is_coldstake) {
              $reward_tag = $reward_tag. "<span class='text-info mx-1' title='Coldstake Reward'><i class='".$settings["coldstake_symbol"]."'></i></span>";
            } 
          } 
          if ($am->is_mn_reward) {
            $reward_tag .= "<span class='text-info mx-1' title='MN Reward'><i class='fab fa-hubspot'></i></span>";
          } 
          if ($am->is_dev_reward) {
            if (isset($settings["dev_symbol"])){
              // custom dev_symbol
              $reward_tag = $reward_tag. "<span class='text-info mx-1' title='Developer Fee'><i class='".$settings["dev_symbol"]."'></i></span>";
            } else {
              $reward_tag = $reward_tag. "<span class='text-info mx-1' title='Developer Fee'><i class='fas fa-cog'></i></span>";
            }
            
          }
          if (isset($am->type)){
            // custom vout type
            if ($am->type == "spow" && isset($settings["spow_symbol"])){
              $reward_tag = $reward_tag. "<span class='text-info mx-1' title='SPoW Reward'><i class='".$settings["spow_symbol"]."'></i></span>";
            } else if ($am->type == "mn_ops" && isset($settings["mnops_symbol"])){
              $reward_tag = $reward_tag. "<span class='text-info mx-1' title='MN Operations Reward'><i class='".$settings["mnops_symbol"]."'></i></span>";
            } else if ($am->type == "mlc" && isset($settings["mlc_symbol"])){
              $reward_tag = $reward_tag. "<span class='text-info mx-1' title='MLC Reward'><i class='".$settings["mlc_symbol"]."'></i></span>";
            }
          }
        }
        if (isset($am->type) && $am->type == "airdrop") {
          $reward_tag .= "<i class='".$settings["airdrop_symbol"]." text-info mx-1' title='Airdrop Movement'></i>";
        }
        if (isset($am->suspect) && $am->suspect) {
          $reward_tag .= "<i class='fas fa-exclamation-triangle text-danger mx-1' title='Suspected Movement'></i>";
        }
        $or_tag = '';
        if (isset($settings['show_op_return']) && $settings['show_op_return']) {
          $tr = db_getTxByHash($am->tx_id);
          if (count($tr)>0){
            $tr = $tr[0];
            if (isset($tr->op_return)) {
              if (isset($settings['op_return_symbol']) && !empty($settings['op_return_symbol'])) {
                $or_tag = "<i class='".$settings['op_return_symbol']." text-info mx-1' title='OP_RETURN Message'></i>";
              } else {
                $or_tag = "<i class='far fa-comment-dots text-info mx-1' title='OP_RETURN Message'></i>";
              }
            }
          }
        }
        if($conf < 0){$conf="---";}
            if ($am->amount < 0) {
                $am_class="danger";
            } else if ($am->amount > 0) {
                $am_class="success";
            } else {
                $am_class="muted";
            }
          $amount_split = splitFloat($am->amount,$precision,$precision);
          array_push($m,"<span class='txlist-timestamp '>".date('Y-m-d H:i:s', $am->move_time_sec)."</span>");
          array_push($m,"<a class='txlist-txid primary-text mr-1' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."' title='Navigate to transaction ".$am->tx_id."'>".$am->tx_id."</a>".$reward_tag.$or_tag);
          if ($addr == "Airdrop"){
            $owner = (isset($am->staker)) ? $am->staker : null;
            if (!empty($owner)){
              array_push($m,"<a class='txlist-conf' href='".$settings["explorer_address"]."/address/?address=".$owner."' title='Navigate to address ".$owner."'>".$owner."</a>");
            } else {
              array_push($m,"");
            }
          }
          array_push($m,"<a class='txlist-conf' href='".$settings["explorer_address"]."/block/?blockheight=".$am->block_ix."' title='Navigate to block ".$am->block_ix."'>".$conf."</a>");
          array_push($m,"<span class='txlist-amount text-".$am_class."'>".$amount_split->whole."<small>.".$amount_split->fraction."</small></span>");
          array_push($result, $m);
        }
    }
  $ptime=milliseconds() - $stime;
  echo json_encode([
    "draw"=> $draw,
    "processingtime_ms"=>$ptime,
    "addr" => $addr,
    "recordsTotal"=>$rec,
    "recordsFiltered"=>$rec,
    "data"=>$result
    ]);
  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/addressmoves",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>