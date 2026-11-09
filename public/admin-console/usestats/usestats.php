<?php
    require __DIR__ . '/../../../settings.php';    
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';
    
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    $session = sec_Authenticate($cip, ui_GetUserAgent());

    //if (!$acc->authenticated) {
    if (!$session->authenticated) {
        // not authorized
        header('HTTP/1.0 401 Unauthorized', true, 401);
        header('Content-Type: application/json');
        echo '{"status":"error","error":{"code": 401,"message":"Unauthorized"}}'; 
        exit;
    }
    
  global $rpc;
  global $dbm;

  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["overview_precision"];
  $def_precision = (int)$settings["default_precision"];

  $hours = 25;
  $target = time();
  $target = $target - ($target % 300);
  $since = $target - (3600*$hours);
  $pg_stats = db_getPageAggregates($since);
  $api_stats = db_getApiAggregates($since);


  $labels = array();
  $api_num = array();
  $api_time = array();


  // create labels
    $a = $since;
    while ($a <= $target) {
      # code...
      $labels[$a] = date("M, j H:i",$a);
      $a += 300;
    }
  // process page aggregates
    $pages = array_map('set_zero', $labels);
    $dps = array_map('set_zero', $labels);
    $ups = array_map('set_zero', $labels);
    $page_num = array();
    $page_time = array();
    $page_avgs = array_map('set_zero', $labels);
    $dp_num = array();
    $dp_time = array();
    $dp_avgs = array_map('set_zero', $labels);
    $up_num = array();
    $up_time = array();
    $up_avgs = array_map('set_zero', $labels);
    foreach ($pg_stats as $val){
      foreach ($val as $k=>$r){
        if (isset($r->num)){
          if (strpos($k,"_dp") === 0 ){
            $dps[$val->_id] += $r->num;
            $dp_num[$k] = (isset($dp_num[$k]) ?  $dp_num[$k] + $r->num : $r->num );
          } else if (strpos($k,"_up") === 0){
            $ups[$val->_id] += $r->num;
            $up_num[$k] = (isset($up_num[$k]) ?  $up_num[$k] + $r->num : $r->num );
          } else {
            $pages[$val->_id] += $r->num;
            $page_num[$k] = (isset($page_num[$k]) ?  $page_num[$k] + $r->num : $r->num );
          }
        }
        if (isset($r->total)){
          if (strpos($k,"_dp") === 0 ){
            $dp_time[$k] = (isset($dp_time[$k]) ?  $dp_time[$k] + $r->total : $r->total );
            $dp_avgs[$val->_id] = (!is_numeric($dp_avgs[$val->_id]) ? $r->total : $dp_avgs[$val->_id] + $r->total);
          } else if (strpos($k,"_up") === 0){
            $up_time[$k] = (isset($up_time[$k]) ?  $up_time[$k] + $r->total : $r->total );
            $up_avgs[$val->_id] = (!is_numeric($up_avgs[$val->_id]) ? $r->total : $up_avgs[$val->_id] + $r->total);
          } else {
            $page_time[$k] = (isset($page_time[$k]) ?  $page_time[$k] + $r->total : $r->total );
            $page_avgs[$val->_id] = (!is_numeric($page_avgs[$val->_id]) ? $r->total : $page_avgs[$val->_id] + $r->total);
          }
        }
      }
      if ($pages[$val->_id] != 0){
        $page_avgs[$val->_id] = number_format(($page_avgs[$val->_id]/1000000)/$pages[$val->_id],3,".","");
      }
      if ($dps[$val->_id] != 0){
        $dp_avgs[$val->_id] = number_format(($dp_avgs[$val->_id]/1000000)/$dps[$val->_id],3,".","");
      }
      if ($ups[$val->_id] != 0){
        $up_avgs[$val->_id] = number_format(($up_avgs[$val->_id]/1000000)/$ups[$val->_id],3,".","");
      }
    }
    arsort($page_num);
    $spg_avg = array_map('set_zero', $page_num); // per page average time
    foreach ($page_num as $p=>$n){
      if ($n!=0 && isset($page_time[$p])){
        $spg_avg[$p] = round(($page_time[$p]/1000000)/$n,3);
      }
    }
    $page_n = array_sum($page_num);
    $page_t = array_sum($page_time);
    $page_avg = ($page_n!=0 ? ($page_t/1000000)/$page_n : 0);

    $page_percent = floor(100*(2 - $page_avg)/2);
    $page_avg_split = splitFloat($page_avg,3,3);
    if ($page_avg > 2){
      $page_style = "danger";
      $page_color = getNamedColor('danger');
    } else if ($page_avg > 1){
      $page_style = "warning";
      $page_color = getNamedColor('warning');
    } else {
      $page_style = "success";
      $page_color = getNamedColor('success');
    }

    $dp_n = array_sum($dp_num);
    $dp_t = array_sum($dp_time);
    $dp_avg = ($dp_n!=0 ? ($dp_t/1000000)/$dp_n : 0);

    $dp_percent = floor(100*(5 - $dp_avg)/5);
    $dp_avg_split = splitFloat($dp_avg,3,3);
    if ($dp_avg > 5){
      $dp_style = "danger";
      $dp_color = getNamedColor('danger');
    } else if ($dp_avg > 3){
      $dp_style = "warning";
      $dp_color = getNamedColor('warning');
    } else {
      $dp_style = "success";
      $dp_color = getNamedColor('success');
    }

    $up_n = array_sum($up_num);
    $up_t = array_sum($up_time);
    $up_avg = ($up_n!=0 ? ($up_t/1000000)/$up_n : 0);

    $up_percent = floor(100*(5 - $up_avg)/5);
    $up_avg_split = splitFloat($up_avg,3,3);
    if ($up_avg > 5){
      $up_style = "danger";
      $up_color = getNamedColor('danger');
    } else if ($up_avg > 3){
      $up_style = "warning";
      $up_color = getNamedColor('warning');
    } else {
      $up_style = "success";
      $up_color = getNamedColor('success');
    }





  // process api aggregates
    $apis = array_map('set_zero', $labels);
    $api_num = array();
    $api_time = array();
    $api_avgs = array_map('set_zero', $labels);
    foreach ($api_stats as $val){
      foreach ($val as $k=>$r){
        if (isset($r->num)){
          $apis[$val->_id] += $r->num;
          $api_num[$k] = (isset($api_num[$k]) ?  $api_num[$k] + $r->num : $r->num );
        }
        if (isset($r->total)){
            $api_time[$k] = (isset($api_time[$k]) ?  $api_time[$k] + $r->total : $r->total );
            $api_avgs[$val->_id] = (!is_numeric($api_avgs[$val->_id]) ? $r->total : $api_avgs[$val->_id] + $r->total);
        }
      }
      if ($apis[$val->_id] != 0){
        $api_avgs[$val->_id] = number_format(($api_avgs[$val->_id]/1000000)/$apis[$val->_id],3,".","");
      }
    }
    arsort($api_num);

    $sap_avg = array_map('set_zero', $api_num); // per api average time
    foreach ($api_num as $a=>$n){
      if ($n!=0 && isset($api_time[$a])){
        $sap_avg[$a] = round(($api_time[$a]/1000000)/$n,3);
      }
    }
    $api_n = array_sum($api_num);
    $api_t = array_sum($api_time);
    $api_avg = ($api_n!=0 ? ($api_t/1000000)/$api_n : 0);

    $api_percent = floor(100*(2 - $api_avg)/2);
    $api_avg_split = splitFloat($api_avg,3,3);
    if ($api_avg > 2){
      $ap_style = "danger";
      $ap_color = getNamedColor('danger');
    } else if ($api_avg > 1){
      $ap_style = "warning";
      $ap_color = getNamedColor('warning');
    } else {
      $ap_style = "success";
      $ap_color = getNamedColor('success');
    }


// $summary = json_decode(apiGetMonitoringSummary());
  // // CPU percent
  //   if (is_null($summary->cpu_load_percent)){
  //     $summary->cpu_load_percent = 0;
  //     $cpu_color = getNamedColor('primary');
  //     $cpu_style = 'primary';
  //   } else if ($summary->cpu_load_percent < 75){
  //     $cpu_color = getNamedColor('success');
  //     $cpu_style = 'success';
  //   } else if ($summary->cpu_load_percent < 90){
  //     $cpu_color = getNamedColor('warning');
  //     $cpu_style = 'warning';
  //   } else {
  //     $cpu_color = getNamedColor('danger');
  //     $cpu_style = 'danger';
  //   }

  // // MEM percent
  //   if (is_null($summary->memory_used_percent)){
  //     $summary->memory_used_percent = 0;
  //     $mem_color = getNamedColor('primary');
  //     $mem_style = 'primary';
  //   } else if ($summary->memory_used_percent < 80){
  //     $mem_color = getNamedColor('success');
  //     $mem_style = 'success';
  //   } else if ($summary->memory_used_percent < 95){
  //     $mem_color = getNamedColor('warning');
  //     $mem_style = 'warning';
  //   } else {
  //     $mem_color = getNamedColor('danger');
  //     $mem_style = 'danger';
  //   }

  // // SWAP percent
  //   if (is_null($summary->swap_used_percent)){
  //     $summary->swap_used_percent = 0;
  //     $swap_color = getNamedColor('primary');
  //     $swap_style = 'primary';
  //   } else if ($summary->swap_used_percent < 75){
  //     $swap_color = getNamedColor('success');
  //     $swap_style = 'success';
  //   } else if ($summary->swap_used_percent < 90){
  //     $swap_color = getNamedColor('warning');
  //     $swap_style = 'warning';
  //   } else {
  //     $swap_color = getNamedColor('danger');
  //     $swap_style = 'danger';
  //   }

  // // DISK percent  
  //   if (is_null($summary->disk_used_percent)){
  //     $summary->disk_used_percent = 0;
  //     $disk_color = getNamedColor('primary');
  //     $disk_style = 'primary';
  //   } else if ($summary->disk_used_percent < 75){
  //     $disk_color = getNamedColor('success');
  //     $disk_style = 'success';
  //   } else if ($summary->disk_used_percent < 90){
  //     $disk_color = getNamedColor('warning');
  //     $disk_style = 'warning';
  //   } else {
  //     $disk_color = getNamedColor('danger');
  //     $disk_style = 'danger';
  //   }

  // // DAEMON speed
  //   if (is_null($summary->daemon_response_time_sec)){
  //     $resp_percent = 0;
  //     $resp_color = getNamedColor('primary');
  //     $resp_style = 'primary';
  //   } else if ($summary->daemon_response_time_sec < 1){
  //     $resp_percent = max(100*(5-$summary->daemon_response_time_sec)/5,0);
  //     $resp_color = getNamedColor('success');
  //     $resp_style = 'success';
  //   } else if ($summary->daemon_response_time_sec < 4){
  //     $resp_percent = max(100*(5-$summary->daemon_response_time_sec)/5,0);
  //     $resp_color = getNamedColor('warning');
  //     $resp_style = 'warning';
  //   } else {
  //     $resp_percent = max(100*(5-$summary->daemon_response_time_sec)/5,0);
  //     $resp_color = getNamedColor('danger');
  //     $resp_style = 'danger';
  //   }

  // // SYNC status
  //   if (is_null($summary->daemon_last_block) || is_null($summary->database_last_block)){
  //     $sync_percent = 0;
  //     $sync_diff = null;
  //   } else {
  //     $sync_percent = 100*$summary->database_last_block/$summary->daemon_last_block;
  //     $sync_diff = $summary->daemon_last_block - $summary->database_last_block;
  //   }
  //   if(is_null($sync_diff)){
  //     $sync_color = getNamedColor('primary');
  //     $sync_style = 'primary';
  //   } else if($sync_diff > 3*$settings['maturity']){
  //     $sync_color = getNamedColor('danger');
  //     $sync_style = 'danger';
  //   } else if ($sync_diff > round($settings['maturity']/5,0)){
  //     $sync_color = getNamedColor('warning');
  //     $sync_style = 'warning';
  //   } else {
  //     $sync_color = getNamedColor('success');
  //     $sync_style = 'success';
  //   }

  //   //<< add time since last db block

  // // DAEMON status
  //   if($summary->daemon_info->status == "ONLINE"){
  //     $daemon_style = 'success';
  //   } else { 
  //     $daemon_style = 'danger';
  //   }
  // // CONNECTIONS count
  //   if(isset($summary->daemon_connection_count) && !empty($summary->daemon_connection_count)){
  //     if ($summary->daemon_connection_count == 0){
  //       $connections_style = 'danger';
  //     } else if ($summary->daemon_connection_count < 4){
  //       $connections_style = 'warning';
  //     } else {
  //       $connections_style = 'success';
  //     }
      
  //   } else { 
  //     $connections_style = 'primary';
  //   }

  // // SUBSCRIPTION info
  //   if ($summary->subscription_status == "ENABLED") {
  //     $subscription_status = "ENABLED";
  //     $subscription_expires = $summary->subscription_expires;
  //     $time_till_expired = timediffToParts($summary->subscription_remains);
  //     $subscription_remains = $time_till_expired->string;
  //     if ($summary->subscription_remains <= 0 ){
  //       // expired
  //       $subscription_style = 'danger';
  //       $subscription_remains = "EXPIRED";
  //     } else if ($summary->subscription_remains <= 7*24*60*60 ) {
  //       // expires soon
  //       $subscription_style = 'warning';
  //     } else {
  //       // has time
  //       $subscription_style = 'success';
  //     }

  //   } else {
  //     $subscription_status = "DISABLED";
  //     $subscription_expires = "Never";
  //     $subscription_remains = "Infinite";
  //     $subscription_style = 'primary';
  //   }

  // // host
  //   $cpu_percent_split = splitFloat( $summary->cpu_load_percent,1,1);
  //   $mem_percent_split = splitFloat( $summary->memory_used_percent,1,1);
  //   $swp_percent_split = splitFloat( $summary->swap_used_percent,1,1);
  //   $hdd_percent_split = splitFloat( $summary->disk_used_percent,1,1);
  //   $dmn_percent_split = splitFloat( $resp_percent,1,1);
  //   $syn_percent_split = splitFloat( $sync_percent,16,1);
  //   $observation_period = is_null($summary->observation_period_seconds) ? "---" : round($summary->observation_period_seconds,0);
  //   $mem_total  = is_null($summary->memory_total_bytes) ? "-.---" : normalizeSiToScale( $summary->memory_total_bytes,"G",3);
  //   $mem_free   = is_null($summary->memory_total_bytes) ? "-.---" : normalizeSiToScale( $summary->memory_total_bytes-$summary->memory_used_bytes,"G",3);
  //   $swp_total  = is_null($summary->swap_total_bytes) ? "-.---" : normalizeSiToScale( $summary->swap_total_bytes,"G",3);
  //   $swp_free   = is_null($summary->swap_total_bytes) ? "-.---" : normalizeSiToScale( $summary->swap_total_bytes-$summary->swap_used_bytes,"G",3);
  //   $hdd_total  = is_null($summary->disk_total_bytes) ? "-.---" : normalizeSiToScale( $summary->disk_total_bytes,"G",3);
  //   $hdd_free   = is_null($summary->disk_free_bytes) ? "-.---" : normalizeSiToScale( $summary->disk_free_bytes,"G",3);
  //   $mem_free_percent_split = splitFloat(100 - $mem_percent_split->number,1,1);
  //   $swp_free_percent_split = splitFloat(100 - $swp_percent_split->number,1,1);
  //   $hdd_free_percent_split = splitFloat(100 - $hdd_percent_split->number,1,1);
  //   $db_total   = is_null($summary->database_data_size_bytes) ? "-.---" : normalizeSiToScale( $summary->database_data_size_bytes,"G",3);
  //   $db_disk    = is_null($summary->database_disk_size_bytes) ? "-.---" : normalizeSiToScale( $summary->database_disk_size_bytes,"G",3);
  //   $db_comp_percent_split  = isset($db_total->value) ? splitFloat(100 - 100*($db_disk->value/$db_total->value),1,1) : splitFloat(null,1,1);
  // // DB COMPRESSION
  //   if (is_null($db_comp_percent_split->number) || is_nan($db_comp_percent_split->number)){
  //     $db_style = 'primary';
  //   } else if ($db_comp_percent_split->number <5){
  //     $db_style = 'danger';
  //   } else if ($db_comp_percent_split->number <20){
  //     $db_style = 'warning';
  //   } else {
  //     $db_style = 'success';
  //   }
  // //  
  //   $daemon_delay = is_null($summary->daemon_response_time_sec) ? normalizeSi(0,3,3) : normalizeSi( $summary->daemon_response_time_sec,3,3);

  // // 25h
  //   $etime = time();
  //   $stime = $etime - 90000;
  //   $hms = db_getHostMetricsByTimeRange($stime,$etime, true, true, true);
  //   $label_ds = array();
  //   $cpu_ds = array();
  //   $mem_ds = array();
  //   $hdd_ds = array();
  //   $dmn_ds = array();
  //   foreach($hms as $hm){
  //     array_push($label_ds, date("M, d H:i",$hm->_id));
  //     array_push($cpu_ds, is_null($hm->system->cpu_load->idle) ? 'NaN' : round(100-$hm->system->cpu_load->idle,1));
  //     array_push($mem_ds, round(100*$hm->system->memory_usage->used/$hm->system->memory_usage->total,1));
  //     array_push($hdd_ds, round(100*$hm->system->disk_usage->free/$hm->system->disk_usage->total,1));
  //     array_push($dmn_ds, round(max(100*(5-$hm->daemon->daemon_response_time)/5,0),1));
  //   }
?>
<!--Section: Button icon-->
<?php
    include_once __DIR__ . '/../../includes/admin_menu.php';
?>
  <script>
    $(".menu-nav").removeClass("active");
    $("#admin-usestats").addClass("active");
  </script>
<section>

  <!--Grid row-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-12">
      <div class="card col-12 px-0 mb-4">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-tachometer-alt pr-1" aria-hidden="true"></i> INTERFACE RESPONSIVENESS </span>
        </div>
        <div class="row text-center py-3 px-2">
          <div class="col">
            <span class="min-chart m-0" id="min-pg" data-percent="<?php echo $page_percent; ?>"><span id="min-pg-w" class="value primary-text h6"><?php echo $page_avg_split->whole; ?><small>.<?php echo $page_avg_split->fraction; ?> s</small></span></span>
            <h5><span id="min-pg-badge" class="label badge badge-<?php echo $page_style; ?>">Avg. Page Time</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-dp" data-percent="<?php echo $dp_percent; ?>"><span id="min-ram-w" class="value primary-text h6"><?php echo $dp_avg_split->whole; ?><small>.<?php echo $dp_avg_split->fraction; ?> s</small></span></span>
            <h5><span id="min-dp-badge" class="label badge badge-<?php echo $dp_style; ?>">Avg. DPI Time</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-up" data-percent="<?php echo $up_percent; ?>"><span id="min-swp-w" class="value primary-text h6"><?php echo $up_avg_split->whole; ?><small>.<?php echo $up_avg_split->fraction; ?> s</small></span></span>
            <h5><span id="min-up-badge" class="label badge badge-<?php echo $up_style; ?>">Avg. UPI Time</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-api" data-percent="<?php echo $api_percent; ?>"><span id="min-hdd-w" class="value primary-text h6"><?php echo $api_avg_split->whole; ?><small>.<?php echo $api_avg_split->fraction; ?> s</small></span></span>
            <h5><span id="min-api-badge" class="label badge badge-<?php echo $ap_style; ?>">Avg. API Time</span></h5>
          </div>
        </div>
      </div>
    </div>
    <script>
      $(function () {
        $('.min-chart#min-pg').easyPieChart({
          barColor: "<?php echo $page_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-dp').easyPieChart({
          barColor: "<?php echo $dp_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-up').easyPieChart({
          barColor: "<?php echo $up_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-api').easyPieChart({
          barColor: "<?php echo $ap_color;?>",
        });
      });
    </script>
    

  </div>
  <div class="row d-none d-md-block">
    <div class="col-12 flex-col mb-3">
      <div class="card col-12 pt-3">
        <div class="my-2 col-12 text-right">
          <div class="chart-container text-center" style="height: 25em;">
            <canvas id="lcNum" class="mb-3" height="100%"></canvas>
          </div>
        </div>
        <script>
          var ctxB = document.getElementById('lcNum').getContext('2d');
          var myBarChart = new Chart(ctxB, { 
            type: 'bar',
            height: '100%',
            data: { 
              spanGaps: false,
              labels: [
                <?php  
                  foreach($labels as $a){
                    echo "'" . $a . "',";
                  } 
                ?>
              ],
              datasets: [
                {
                  label: 'Page loads',
                  fill: false,
                  data: [ 
                    <?php foreach ($pages as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[0],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[0],0.6); ?>',
                  borderWidth: 2,
                },{
                  label: 'DPI Calls',
                  fill: false,
                  hidden: true,
                  data: [ 
                    <?php foreach ($dps as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[1],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[1],0.6); ?>',
                  borderWidth: 2,
                },{
                  label: 'UPI Calls',
                  fill: false,
                  hidden: true,
                  data: [ 
                    <?php foreach ($ups as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[2],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[2],0.6); ?>',
                  borderWidth: 2,
                },{
                  label: 'API Calls',
                  fill: false,
                  data: [ 
                    <?php foreach ($apis as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[3],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[3],0.6); ?>',
                  borderWidth: 2,
                }
              ],
            },
            options: {
              tooltips: {
                mode: 'x',
                callbacks: {
                  label: function(tooltipItem, data) {
                    var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ' : ' + tooltipItem.yLabel.toFixed(0) + '';
                  }
                }
              },
              responsive: true,
              maintainAspectRatio: false,
              spanGaps: false,
              animation: {
                duration: 0,
              },  
              legend:{
                display: true,
                position: 'top',
              },
              scales: {
                yAxes: [
                  {
                    scaleLabel: {
                      labelString: 'Number',
                      display: true,
                    },
                    ticks: {
                      beginAtZero: true,
                      precision: 0,
                      // callback: function(value, index, values) {
                      //   return value.toFixed(0)+'%';
                      // },
                      //proposedMax: 100,  
                    },
                  }
                ]
              }
            }
          });
        </script>
      </div>
    </div>
  </div>  
  <div class="row d-none d-md-block">
    <div class="col-12 flex-col mb-3">
      <div class="card col-12 pt-3">
        <div class="my-2 col-12 text-right">
          <div class="chart-container text-center" style="height: 25em;">
            <canvas id="lcTime" class="mb-3" height="100%"></canvas>
          </div>
        </div>
        <script>
          var ctxL = document.getElementById('lcTime').getContext('2d');
          var myLineChart = new Chart(ctxL, { 
            type: 'line',
            height: '100%',
            data: { 
              spanGaps: false,
              labels: [
                <?php  
                  foreach($labels as $a){
                    echo "'" . $a . "',";
                  } 
                ?>
              ],
              datasets: [
                {
                  label: 'Avg. Page time',
                  fill: false,
                  spanGaps: true,
                  data: [ 
                    <?php foreach ($page_avgs as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[0],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[0],0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 2,
                },{
                  label: 'Avg. DPI time',
                  fill: false,
                  //hidden: true,
                  data: [ 
                    <?php foreach ($dp_avgs as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[1],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[1],0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 2,
                },{
                  label: 'Avg. UPI time',
                  fill: false,
                  //hidden: true,
                  data: [ 
                    <?php foreach ($up_avgs as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[2],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[2],0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 2,
                },{
                  label: 'Avg. API time',
                  fill: false,
                  data: [ 
                    <?php foreach ($api_avgs as $v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(4)[3],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[3],0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 2,
                }
              ],
            },
            options: {
              tooltips: {
                mode: 'x',
                callbacks: {
                  label: function(tooltipItem, data) {
                    var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ' : ' + tooltipItem.yLabel.toFixed(3) + '';
                  }
                }
              },
              responsive: true,
              maintainAspectRatio: false,
              spanGaps: false,
              animation: {
                duration: 0,
              },  
              legend:{
                display: true,
                position: 'top',
              },
              scales: {
                yAxes: [
                  {
                    scaleLabel: {
                      labelString: 'Seconds',
                      display: true,
                    },
                    ticks: {
                      beginAtZero: true,
                      precision: 3,
                      // callback: function(value, index, values) {
                      //   return value.toFixed(0)+'%';
                      // },
                      //proposedMax: 100,  
                    },
                  }
                ]
              }
            }
          });
        </script>
      </div>
    </div>
  </div>  

  <div class="row">
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <div class="card col-12 px-0 mb-4">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-desktop pr-1" aria-hidden="true"></i> PAGES USAGE </span>
        </div>
        <div class="card-body col-12 text-center py-3 px-2">
          <div class="chart-container text-center" style="height: 25em;">
            <canvas id="lcPage" class="mb-3 px-3" height="100%"></canvas>
            <script>
              var ctxL = document.getElementById('lcPage').getContext('2d');
              var myLineChart = new Chart(ctxL, { 
                type: 'horizontalBar',
                height: '100%',
                data: {
                  labels:<?php echo json_encode(array_keys($page_num));?>,
                  datasets: [
                    {
                      label: 'Page Loads',
                      data: <?php echo json_encode(array_values($page_num));?>,
                      borderColor: '<?php echo hex2rgba(getChartColorArray(4)[0],0.7); ?>',
                      backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[0],0.6); ?>',
                    },
                  ],
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  legend:{
                    display: false,
                  },

                  scales: {
                    xAxes: [
                      {
                        scaleLabel: {
                          labelString: 'Number',
                          display: true,
                        },
                        ticks: {
                          beginAtZero: true,
                          precision: 0,
                          // callback: function(value, index, values) {
                          //   return value.toFixed(0)+'%';
                          // },
                          //proposedMax: 100,  
                        },
                      }
                    ]
                  }
                }
              });
            </script>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <div class="card col-12 px-0 mb-4">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-code-branch pr-1" aria-hidden="true"></i> API USAGE </span>
        </div>
        <div class="card-body col-12 text-center py-3 px-2">
          <div class="chart-container text-center" style="height: 25em;">
            <canvas id="lcApi" class="mb-3 px-3" height="100%"></canvas>
            <script>
              var ctxL = document.getElementById('lcApi').getContext('2d');
              var myLineChart = new Chart(ctxL, { 
                type: 'horizontalBar',
                height: '100%',
                data: {
                  labels:<?php echo json_encode(array_keys($api_num));?>,
                  datasets: [
                    {
                      label: 'API calls',
                      data: <?php echo json_encode(array_values($api_num));?>,
                      borderColor: '<?php echo hex2rgba(getChartColorArray(4)[3],0.7); ?>',
                      backgroundColor: '<?php echo hex2rgba(getChartColorArray(4)[3],0.6); ?>',
                    },
                  ],
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  legend:{
                    display: false,
                  },

                  scales: {
                    xAxes: [
                      {
                        scaleLabel: {
                          labelString: 'Number',
                          display: true,
                        },
                        ticks: {
                          beginAtZero: true,
                          precision: 0,
                          // callback: function(value, index, values) {
                          //   return value.toFixed(0)+'%';
                          // },
                          //proposedMax: 100,  
                        },
                      }
                    ]
                  }
                }
              });
            </script>
          </div>
        </div>
      </div>
    </div>
  </div>

</section>
<!--/Section: Button icon-->
<?php 
  die();
?>

  <div class="row">
    <!--Grid column II-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <!--Masternode Earnings Card-->
      <div class="card col-12 pb-3">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-server card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 id="cpu-text" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold text-<?php echo $cpu_style; ?>"><?php echo $cpu_percent_split->whole; ?><small>.<?php echo $cpu_percent_split->fraction;?>&nbsp;%</small></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "AVERAGE CPU LOAD (<span id='op-text'>".round($summary->observation_period_seconds,0)."</span> sec)"; ?></p>
          </div>
        </div>

        <div id="hostContainer"> 
          <div class="row px-3 pb-3">
            <div class="table-container col-12" id="countTableContainer">
              <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                <thead> 
                  <th class="text-left">Resource</th>
                  <th class="text-right">Total</th>
                  <th class="text-right">Free</th>
                  <th class="text-right">% Free</th>
                </thead>
                <tbody>
                  <tr class="">
                    <td class='text-left p-1'>Memory</td>
                    <td class='text-right p-1'><span id="mem-total"><?php echo fpp($mem_total->value,3)." ".$mem_total->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="mem-free"><?php echo fpp($mem_free->value,3)." ".$mem_free->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="mem-percent" class="badge badge-<?php echo $mem_style; ?>"><?php echo $mem_free_percent_split->whole; ?><small>.<?php echo $mem_free_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Swap</td>
                    <td class='text-right p-1'><span id="swp-total"><?php echo fpp($swp_total->value,3)." ".$swp_total->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="swp-free"><?php echo fpp($swp_free->value,3)." ".$swp_free->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="swp-percent" class="badge badge-<?php echo $swap_style; ?>"><?php echo $swp_free_percent_split->whole; ?><small>.<?php echo $swp_free_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Storage</td>
                    <td class='text-right p-1'><span id="hdd-total"><?php echo fpp($hdd_total->value,3)." ".$hdd_total->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="hdd-free"><?php echo fpp($hdd_free->value,3)." ".$hdd_free->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="hdd-percent" class="badge badge-<?php echo $disk_style; ?>"><?php echo $hdd_free_percent_split->whole; ?><small>.<?php echo $hdd_free_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1 pt-4'>Database Name</td>
                    <td class='text-right p-1 pt-4'>Data Size</td>
                    <td class='text-right p-1 pt-4'>Disk Size</td>
                    <td class='text-right p-1 pt-4'>% Compression</td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'><?php echo $settings['db_name']; ?></td>
                    <td class='text-right p-1'><span id="db-total"><?php echo fpp($db_total->value,3)." ".$db_total->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="db-disk"><?php echo fpp($db_disk->value,3)." ".$db_disk->dimension."B"; ?></span></td>
                    <td class='text-right p-1'><span id="db-percent" class="badge badge-<?php echo $db_style; ?>"><?php echo $db_comp_percent_split->whole; ?><small>.<?php echo $db_comp_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1 pt-4'>Subscription</td>
                    <td class='text-right p-1 pt-4'>Status</td>
                    <td class='text-right p-1 pt-4'>Expires At</td>
                    <td class='text-right p-1 pt-4'>Remaining Time</td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Solus Explorer</td>
                    <td class='text-right p-1'><span id="sub-status"><?php echo $subscription_status; ?></span></td>
                    <td class='text-right p-1'><span id="sud-expires"><?php echo $subscription_expires; ?></span></td>
                    <td class='text-right p-1'><span id="sub-remains" class="badge badge-<?php echo $subscription_style; ?>"><?php echo $subscription_remains; ?></span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!--/.Card Data-->
      </div>
      <!--/.Masternode Earnings Card -->
    </div>
    <!--Grid column-->
    <!--Grid column II-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <!--Masternode Earnings Card-->
      <div class="card col-12 pb-3">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-wave-square card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 id="dmn-status" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold text-<?php echo $daemon_style; ?>"><?php echo $summary->daemon_info->status; ?></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "DAEMON STATUS"; ?></p>
          </div>
        </div>

        <div id="daemonontainer"> 
          <div class="row px-3 pb-3">
            <div class="table-container col-12" id="countTableContainer">
              <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                <thead> 
                  <th class="text-left">Daemon info</th>
                  <th class="text-right" colspan="3">Value</th>
                </thead>
                <tbody>
                  <tr class="">
                    <td class='text-left p-1'>Daemon response time</td>
                    <td class='text-right p-1' colspan="3"><span id="dmn-delay" class="badge badge-<?php echo $resp_style?>"><?php echo fpp($daemon_delay->value,3); ?>&nbsp;sec</span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Daemon client version</td>
                    <td class='text-right p-1' colspan="3"><span id="dmn-version" class="badge badge-primary"><?php echo $summary->daemon_info->version; ?></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Daemon protocol version</td>
                    <td class='text-right p-1' colspan="3"><span id="dmn-protocol" class="badge badge-primary"><?php echo $summary->daemon_info->protocolversion; ?></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Connection count</td>
                    <td class='text-right p-1' colspan="3"><span id="dmn-connections" class="badge badge-<?php echo $connections_style?>"><?php echo $summary->daemon_connection_count; ?></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1 pt-4'>Status</td>
                    <td class='text-right p-1 pt-4'>Explorer block</td>
                    <td class='text-right p-1 pt-4'>Daemon block</td>
                    <td class='text-right p-1 pt-4'>% Sync</td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Explorer synchronization</td>
                    <td class='text-right p-1'><span id="db-lastblock"><?php echo $summary->database_last_block; ?></span></td>
                    <td class='text-right p-1'><span id="dmn-lastblock"><?php echo $summary->daemon_last_block; ?></span></td>
                    <td class='text-right p-1'><span id="db-sync-percent" class="badge badge-<?php echo $sync_style; ?>"><?php echo $syn_percent_split->whole; ?><small>.<?php echo $syn_percent_split->fraction; ?>%</small></span></td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!--/.Card Data-->
      </div>
      <!--/.Masternode Earnings Card -->
    </div>
    <!--Grid column-->
  </div>
    



<script type='text/javascript'>

  function updateDashboard(){
    $.ajax({
      url: '../data/dashboardup.php',
      success: function(data) {
        // CPU Load
        $('.min-chart#min-cpu').data('easyPieChart').options.barColor=data.cpu_color;        
        $('#min-cpu-badge').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.cpu_style);
        $("#min-cpu-w").html(data.cpu_load.w.toString()+"<small>."+data.cpu_load.f.toString()+"</small>");
        $("#min-cpu").attr("data-percent", data.cpu_load.w.toString());
        $('#min-cpu').data('easyPieChart').update(data.cpu_load.w); 

        // MEM Load
        $('.min-chart#min-ram').data('easyPieChart').options.barColor=data.mem_color;        
        $('#min-ram-badge').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.mem_style);
        $("#min-ram-w").html(data.mem_load.w.toString()+"<small>."+data.mem_load.f.toString()+"</small>");
        $("#min-ram").attr("data-percent", data.mem_load.w.toString());
        $('#min-ram').data('easyPieChart').update(data.mem_load.w); 

        // SWAP Load
        $('.min-chart#min-swp').data('easyPieChart').options.barColor=data.swap_color;        
        $('#min-swp-badge').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.swap_style);
        $("#min-swp-w").html(data.swap_load.w.toString()+"<small>."+data.swap_load.f.toString()+"</small>");
        $("#min-swp").attr("data-percent", data.swap_load.w.toString());
        $('#min-swp').data('easyPieChart').update(data.swap_load.w); 

        // DISK Load
        $('.min-chart#min-hdd').data('easyPieChart').options.barColor=data.disk_color;        
        $('#min-hdd-badge').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.disk_style);
        $("#min-hdd-w").html(data.disk_load.w.toString()+"<small>."+data.disk_load.f.toString()+"</small>");
        $("#min-hdd").attr("data-percent", data.disk_load.w.toString());
        $('#min-hdd').data('easyPieChart').update(data.disk_load.w); 

        // DAEMON Load
        $('.min-chart#min-dmn').data('easyPieChart').options.barColor=data.resp_color;        
        $('#min-dmn-badge').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.resp_style);
        $("#min-dmn-w").html(data.resp_load.w.toString()+"<small>."+data.resp_load.f.toString()+"</small>");
        $("#min-dmn").attr("data-percent", data.resp_load.w.toString());
        $('#min-dmn').data('easyPieChart').update(data.resp_load.w); 

        // SYNC Load
        $('.min-chart#min-syn').data('easyPieChart').options.barColor=data.sync_color;        
        $('#min-syn-badge').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.sync_style);
        $("#min-syn-w").html(data.sync_load.w.toString()+"<small>."+data.sync_load.f.toString()+"</small>");
        $("#min-syn").attr("data-percent", data.sync_load.w.toString());
        $('#min-syn').data('easyPieChart').update(data.sync_load.w); 

        // CPU text
        $('#cpu-text').html(data.cpu_load.w+'<small>.'+data.cpu_load.f+'&nbsp;%</small>');
        $('#cpu-text').removeClass('text-primary text-danger text-warning text-success').addClass('text-'+data.cpu_style);
        $('#op-text').html(data.observation_period);

        // HOST - memory
        $('#mem-total').html(data.mem_total.value+' '+data.mem_total.dimension+'B');
        $('#mem-free').html(data.mem_free.value+' '+data.mem_free.dimension+'B');      
        $('#mem-percent').html(data.mem_free_percent.w+'<small>.'+data.mem_free_percent.f+'%</small>'); 
        $('#mem-percent').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.mem_style);

        // HOST - swap
        $('#swp-total').html(data.swp_total.value+' '+data.swp_total.dimension+'B');
        $('#swp-free').html(data.swp_free.value+' '+data.swp_free.dimension+'B');      
        $('#swp-percent').html(data.swp_free_percent.w+'<small>.'+data.swp_free_percent.f+'%</small>'); 
        $('#swp-percent').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.swap_style);

        // HOST - disk
        $('#hdd-total').html(data.hdd_total.value+' '+data.hdd_total.dimension+'B');
        $('#hdd-free').html(data.hdd_free.value+' '+data.hdd_free.dimension+'B');      
        $('#hdd-percent').html(data.hdd_free_percent.w+'<small>.'+data.hdd_free_percent.f+'%</small>'); 
        $('#hdd-percent').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.disk_style);

        // HOST - database
        $('#db-total').html(data.db_total.value+' '+data.db_total.dimension+'B');
        $('#db-disk').html(data.db_disk.value+' '+data.db_disk.dimension+'B');      
        $('#db-percent').html(data.db_comp_percent.w+'<small>.'+data.db_comp_percent.f+'%</small>'); 
        $('#db-percent').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.db_style);

        // HOST - subscription
        $('#sub-status').html(data.subscription_status);
        $('#sub-expires').html(data.subscription_expires);      
        $('#sub-remains').html(data.subscription_remains); 
        $('#sub-remains').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.subscription_style);

        // DAEMON status
        $('#dmn-status').html(data.daemon_status);
        $('#cpu-text').removeClass('text-primary text-danger text-warning text-success').addClass('text-'+data.daemon_style);

        // DAEMON delay
        $('#dmn-delay').html(data.daemon_delay+'&nbsp;sec');
        $('#dmn-delay').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.resp_style);

        // DAEMON connections
        $('#dmn-connections').html(data.daemon_connection_count);
        $('#dmn-connections').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.connections_style);

        // DAEMON version
        $('#dmn-version').html(data.daemon_version);
        $('#dmn-protocol').html(data.daemon_protocolversion);

        // EXPLORER sync
        $('#db-lastblock').html(data.database_last_block);
        $('#dmn-lastblock').html(data.daemon_last_block);
        $('#db-sync-percent').html(data.sync_load.w+'<small>.'+data.sync_load.f+'%</small>');
        $('#db-sync-percent').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.sync_style);

        myLineChart.data.labels = data.lc.label_ds; // labels
        myLineChart.data.datasets[0].data = data.lc.cpu_ds; //cpu
        myLineChart.data.datasets[1].data = data.lc.mem_ds; // mem
        myLineChart.data.datasets[2].data = data.lc.hdd_ds; // disk
        myLineChart.data.datasets[3].data = data.lc.dmn_ds; // dmn
        myLineChart.update();
      },
    });
  }
  // // Autorefresh
  //   $(document).ready(function(){
  //     setInterval(updateDashboard, <?php echo $settings['dashboard_refresh_sec']; ?> * 1000);
  //   });
</script>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"admin/usestats",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>