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
  $draw = (int)sanitizeInput("draw","POST");
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  $limit = (int)sanitizeInput("length","POST");
  if($limit>500 || $limit <= 0){ $limit = 500;}

  $result = array();

  $rec = db_getAssetMovesCount();

  $rpc_bc = getRpcBlockCount();
  $ams = db_getAssetMovesDataWithFilter([], $limit, $skip); 
  foreach($ams as $am) {
    $m = array();
    $amount = @cdec($am->amount);

    if ($am->address != "New Asset") {
      $at = db_getAddressTags($am->address);
      $tag_str = "";
      $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
          
      $addr_cell = "<a class='txlist-txid primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str;
      
    } else { 
      $addr_cell = "<span class='txlist-txid primary-text'>New Asset</span>";    
    }
        
    $suspect_tag="";
    if (isset($am->suspect) && $am->suspect) {
          $suspect_tag .= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movements'></i>&nbsp;";
    }
    $conf = $rpc_bc - $am->block_ix;
    if($conf < 0){$conf="---";}
    if ($amount < 0) {
        $am_class="danger";
      } else if ($amount > 0) {
        $am_class="success";
      } else {
        $am_class="muted";
    }
    $ast = db_getAssetByName($am->name)[0];

    $units = isset($ast->units)?$ast->units:0;
    $amount_split = splitFloat($amount,$units,$units);
    
    // Timestamp
    array_push($m,"<span class='txlist-timestamp '>".date('Y-m-d H:i:s', $am->move_time_sec)."</span>");
    // Asset Name
    array_push($m,"<a class='txlist-hash primary-text' href='".$settings["explorer_address"]."/asset/?asset_id=".$ast->key."'>".h($am->name)."</a>");
    // Address
    array_push($m,$addr_cell);
    // TX Hash
    array_push($m,"<a class='txlist-txid primary-text' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."'>".$am->tx_id."</a>".$suspect_tag);
    // Confirmations
    array_push($m,"<a class='txlist-conf' href='".$settings["explorer_address"]."/block/?blockheight=".$am->block_ix."'>".$conf."</a>");
    // Amount
    array_push($m,"<span class='txlist-amount text-".$am_class."'>".$amount_split->whole.($amount_split->fraction != "--" ? "<small>.".$amount_split->fraction."</small>":"")."</span>");

    array_push($result, $m);
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
    ui_LogPageEvent($cip,"_dp/assetsmoves",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>