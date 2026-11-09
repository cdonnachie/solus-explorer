<?php 
    include_once __DIR__ . '/../../../lib/common.php';
    require __DIR__ . '/../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    sec_requireAdmin();
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  
  date_default_timezone_set($settings["timezone"]);
  $precision = 3;

  $stime=milliseconds();
  $draw= isset($_POST["draw"]) ? (int)sanitizeInput("draw","POST") : 0;
  $skip = isset($_POST["start"]) ? (int)sanitizeInput("start","POST") : 0;
  $limit = isset($_POST["length"]) ? (int)sanitizeInput("length","POST") : 0;

  if($limit>100 || $limit <= 0){ $limit = 100;}

  $r_total = db_getLoaderLogCount();

  $result = array();
  $rec = db_getLoaderLogList($limit, $skip); 

  foreach ($rec as $log){
    $row = array();
    $rid = (string) $log->_id;
    $ts = splitFloat(((string) $log->timestamp)/1000,3,3);
    $tsstr = date('Y-m-d H:i:s',$ts->whole).'.'.$ts->fraction;
    array_push($row,"<span class='blocklist-timestamp '>".$tsstr."</span>");
    array_push($row,"<a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$log->block_ix."'>".$log->block_ix."</a>");
    if ($log->operation == 'load') {
      $op_color = 'success';
    } else if ($log->operation == 'roll-back'){
      $op_color = 'danger';
    } else {
        $op_color = 'secondary';
    }
    array_push($row,"<span class='badge badge-$op_color mnlist-status h6 mb-0'>$log->operation</span>");
    $time_split = splitFloat($log->processing_time,6,6);
    array_push($row,"<span class='blocklist-amount primary-text '><a class='announce' data-toggle='modal' data-target='#addnodeModal' data-id='".$rid."'>".$time_split->whole.".".substr($time_split->fraction,0,3)."<small>".substr($time_split->fraction,3,3)."</small></a></span>");
    array_push($row,"<a class='blocklist-hash primary-text ' href='".$settings["explorer_address"]."/block/?blockhash=".$log->block_hash."'>" .$log->block_hash."</a>");
    array_push($result, $row);
  }

  $ptime=milliseconds() - $stime;
  echo json_encode([
    "draw"=> $draw,
    "processingtime_ms"=>$ptime,
    "recordsTotal"=>$r_total,
    "recordsFiltered"=>$r_total,
    "data"=>$result
    ]);

  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/loaderlog",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>