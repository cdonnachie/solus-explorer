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
  // $draw= isset($_POST["draw"]) ? (int)$_POST["draw"] : 0;
  // $skip = isset($_POST["start"]) ? (int)$_POST["start"] : 0;
  // $limit = isset($_POST["length"]) ? (int)$_POST["length"] : 0;

  $draw= isset($_POST["draw"]) ? (int)sanitizeInput("draw","POST") : 0;
  $skip = isset($_POST["start"]) ? (int)sanitizeInput("start","POST") : 0;
  $limit = isset($_POST["length"]) ? (int)sanitizeInput("length","POST") : 0;

  //$oid = isset($_GET["oid"]) ? $_GET["oid"] : null; 
  $oid = (isset($_GET["oid"]) && is_string($_GET["oid"]) && preg_match('/^[0-9a-f]{24}$/i', $_GET["oid"])) ? $_GET["oid"] : null;
  
  if($limit>100 || $limit <= 0){ $limit = 100;}
  $result = array();
  $rec = is_null($oid) ? array() : db_getLoaderLogTimers($oid);

  if (count($rec) > 0) {
    $tc = $rec[0]->timers;
    $i = 1;
    foreach($tc as $timer){
      $row = array();
      if ($timer->level == 0){
        $lvl_class = 'font-weight-bold text-info';
      } else if ($timer->level == 1){
        $lvl_class = 'font-weight-bold';
      } else if ($timer->level >= 3){
        $lvl_class = 'font-italics';
      } else {
        $lvl_class ='';
      }
      array_push($row,"<span class='blocklist-timestamp ".$lvl_class."'>".$i."</span>");
      array_push($row,"<span class='blocklist-timestamp pl-".($timer->level+1)." ".$lvl_class."'>".htmlspecialchars((string)$timer->name, ENT_QUOTES)."</span>");
      $d_split = splitFloat($timer->duration_usec/1000000,6,6);
      array_push($row,"<span class='blocklist-amount primary-text  ".$lvl_class."'>".$d_split->whole.".".substr($d_split->fraction,0,3)."<small>".substr($d_split->fraction,3,3)."</small></span>");
      array_push($row,"<span class='blocklist-timestamp px-2 ".$lvl_class."'>".htmlspecialchars((string)$timer->info, ENT_QUOTES)."</span>");
      array_push($result, $row);
      $i++;
    }
  }

  $ptime=milliseconds() - $stime;
  echo json_encode([
    "draw"=> $draw,
    "processingtime_ms"=>$ptime,
    "recordsTotal"=>count($result),
    "recordsFiltered"=>count($result),
    "data"=>$result
    ]);

  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/timers",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>