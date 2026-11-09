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

  //$address = $_GET['address'];
  $address =  strtr(sanitizeInput("address","GET"),"_"," ");

  $stime = milliseconds();
  $mns = ui_getMasternodeStatus($address); 
  $result = array();
  foreach($mns as $key => $mn){
      $m = array();
      if (in_array($mn->status,["ENABLED"])) {
      $badge_class="success";
      } else if (in_array($mn->status,["ACTIVE", "PRE-ENABLED", "WATCHDOG_EXPIRED"])) {
      $badge_class="warning";
      } else {
      $badge_class="danger";
      }
      // mn tier badge
      $tier_str = "";
      if (isset($mn->tier)){
        if (is_numeric($mn->tier)){
          $tier_str = "<span class='badge ml-2' style='background-color:".getChartColorArray(3)[(int)$mn->tier].";'><i class='fab fa-hubspot'></i>&nbsp;Tier ".$mn->tier."</span>";
        } else{
          $tier_str = "<span class='badge badge-info ml-2'><i class='fab fa-hubspot'></i>&nbsp;".$mn->tier."</span>";
        }
      }

      if (isset($settings["dip2_support"]) && $settings["dip2_support"]){
        array_push($m,"<span class='mnlist-key '>".$key.$tier_str."</span>");
        array_push($m,"<span class='badge badge-$badge_class mnlist-status h6 mb-0'>$mn->status</span>");
        array_push($m,"<a class='blocklist-height ' href='".$settings["explorer_address"]."/block/?blockheight=".$mn->lastpaidblock."'>".$mn->lastpaidblock."</a>");
        array_push($m,"<span class='mnlist-lastseen'>".date('Y-m-d H:i:s', $mn->lastpaidtime)."</span>");
        array_push($m,"<a class='primary-text mnlist-address' href='".$settings["explorer_address"]."/address/?address=$mn->colladdr'>$mn->colladdr</a>");
      } else {
        //array_push($m,"<span class='mnlist-key '>".substr($key,0,8)."******".substr($key, strlen($key)-10, 10)."</span>");
        array_push($m,"<span class='mnlist-key '>".$key.$tier_str."</span>");
        array_push($m,"<span class='badge badge-$badge_class mnlist-status h6 mb-0'>$mn->status</span>");
        array_push($m,"<span class='mnlist-version '>$mn->version</span>");
        array_push($m,"<span class='mnlist-lastseen'>".date('Y-m-d H:i:s', $mn->lastseen)."</span>");
        array_push($m,"<span class='mnlist-activetime'>".timediffToParts($mn->activetime)->string."</span>");
      }
      array_push($result, $m);
  }
  $processing_ms = milliseconds() - $stime;
  $res = json_encode(["processingtime_ms" => $processing_ms, "recordsTotal" => count($result),"recordsFiltered" => count($result),"data" => $result]);
  //$res = strtr($rstr,"[]","{}");
  echo $res;
  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/masternodestatus",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>