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

  $query_filter = ['type' => ['$nin' => ['regular_admin','subasset_admin']]];

  $rec = db_getAssetCount($query_filter);
  $result = array();
  if($rec >= 0) {
    $asts = db_getLastCreatedAssets($limit, $skip, $query_filter); 
    foreach($asts as $ast) {
        $m = array();

          if (!isset($ast->units)){$ast->units = $precision;}
          $supply = @cdec($ast->supply,$ast->units);
          // Timestamp
          array_push($m,"<span class='blocklist-timestamp '>".date('Y-m-d H:i:s', $ast->genesis_timestamp)."</span>");
          // Asset Name
          array_push($m,"<a class='blocklist-hash primary-text' href='".$settings["explorer_address"]."/asset/?asset_id=".$ast->key."'>".$ast->_id."</a>");
          // Type
          array_push($m,"<span class='blocklist-hash primary-text'>" .$ast->type."</span>");
          // Supply
          array_push($m,"<span class='blocklist-amount'>".$supply->toFixed($ast->units)."</span>");
          // Reissuable
          array_push($m,"<span class='primary-text'>".((isset($ast->reissuable) && $ast->reissuable > 0) ? "YES" : "NO")."</span>");
          // IPFS 
          $ipfslink = "";
          if (isset($ast->ipfs)){
            if (isset($settings["ipfs_explorer_link"])){
              $href = strtr($settings["ipfs_explorer_link"],["!#IPFS-HASH#!"=>$ast->ipfs]);
              $ipfslink = "<a href='".h($href)."' target='_blank' rel='noopener'>".h($ast->ipfs)."</a>";
            } else {
              $ipfslink = h($ast->ipfs);
            }
          }
          array_push($m,"<span class='blocklist-hash primary-text'>".$ipfslink."</span>");

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
    ui_LogPageEvent($cip,"_dp/assetsgen",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>