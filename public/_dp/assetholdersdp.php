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
  //$key= ($_GET['asset_id']);
  $key = sanitizeInput("asset_id","GET");
  $rec = 0;

  // $draw=(int)$_POST["draw"];
  // $skip = (int)$_POST["start"];
  // $limit = (int)$_POST["length"];

  $draw = (int)sanitizeInput("draw","POST");
  $skip = (int)sanitizeInput("start","POST");
  $skip = max(0, min($skip, 100000));
  $limit = (int)sanitizeInput("length","POST");

  if($limit>500 || $limit <= 0){ $limit = 500;}
  $ag_tags = array();
  $result = array();
  if(!empty($key)) {
      $ast = db_getAssetByKey($key);
      if (count($ast)>0){$ast = $ast[0];}
      if (substr($ast->_id,strlen($ast->_id)-1,1) == "!"){
        $is_admin_asset = true;
      } else {
        $is_admin_asset = false;
      }
      $asupply = @cdec($ast->supply);
      $rec = db_getAssetHoldersCountByKey($ast->key);
      $adr = db_getAssetHoldersByKey($ast->key,$limit,$skip);
      foreach ($adr as $ar){
        $ar->assets = (array) $ar->assets;
        $m = array();
        $tag_str = "";
        $amount = @cdec($ar->assets[$ast->key]->value);
        if (isset($ar->alias) && !empty($ar->alias)){
          $tag_str .= "<span class='badge badge-primary ml-1 mb-1'>".h($ar->alias)."</span>&nbsp;";
        }
        if (array_key_exists($ar->group_id,$ag_tags)){
            $agt = $ag_tags[$ar->group_id];
        } else {
            $agt = db_getAddressGroupTags($ar->group_id);
            $ag_tags[$ar->group_id] = $agt;
        }
        
        if (isset($agt->tags) && count($agt->tags)>0){
          foreach ($agt->tags as $tag){
            $tag_str .= "<span class='badge badge-primary ml-1 mb-1'>".h($tag)."</span>&nbsp;";
          }
        }
        $pcnt_split = splitFloat(100*$amount/$asupply,16,2);
        $amount_split = splitFloat($amount,$precision,$precision);
        array_push($m,"<a class='txlist-txid primary-text' href='".$settings["explorer_address"]."/address/?address=".$ar->_id."'>".$ar->_id."</a>".$tag_str);
        if ($is_admin_asset){
          array_push($m,"<span class='txlist-amount'>".$amount_split->whole."</span>");
        } else {
          array_push($m,"<span class='txlist-amount'>".$amount_split->whole."<small>.".$amount_split->fraction."</small></span>");
        }
        array_push($m,"<span class='txlist-amount'>".$pcnt_split->whole."<small>.".$pcnt_split->fraction."</small> %</span>");
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
    ui_LogPageEvent($cip,"_dp/assetholders",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>