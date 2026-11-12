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

  $chartFontSize = 9;
  $minGridDistance = 25;


  $summary = json_decode(apiGetMonitoringSummary());
  // CPU percent
    if (is_null($summary->cpu_load_percent)){
      $summary->cpu_load_percent = 0;
      $cpu_color = getNamedColor('primary');
      $cpu_style = 'primary';
    } else if ($summary->cpu_load_percent < 75){
      $cpu_color = getNamedColor('success');
      $cpu_style = 'success';
    } else if ($summary->cpu_load_percent < 90){
      $cpu_color = getNamedColor('warning');
      $cpu_style = 'warning';
    } else {
      $cpu_color = getNamedColor('danger');
      $cpu_style = 'danger';
    }

  // MEM percent
    if (is_null($summary->memory_used_percent)){
      $summary->memory_used_percent = 0;
      $mem_color = getNamedColor('primary');
      $mem_style = 'primary';
    } else if ($summary->memory_used_percent < 80){
      $mem_color = getNamedColor('success');
      $mem_style = 'success';
    } else if ($summary->memory_used_percent < 95){
      $mem_color = getNamedColor('warning');
      $mem_style = 'warning';
    } else {
      $mem_color = getNamedColor('danger');
      $mem_style = 'danger';
    }

  // SWAP percent
    if (is_null($summary->swap_used_percent)){
      $summary->swap_used_percent = 0;
      $swap_color = getNamedColor('primary');
      $swap_style = 'primary';
    } else if ($summary->swap_used_percent < 75){
      $swap_color = getNamedColor('success');
      $swap_style = 'success';
    } else if ($summary->swap_used_percent < 90){
      $swap_color = getNamedColor('warning');
      $swap_style = 'warning';
    } else {
      $swap_color = getNamedColor('danger');
      $swap_style = 'danger';
    }

  // DISK percent  
    if (is_null($summary->disk_used_percent)){
      $summary->disk_used_percent = 0;
      $disk_color = getNamedColor('primary');
      $disk_style = 'primary';
    } else if ($summary->disk_used_percent < 75){
      $disk_color = getNamedColor('success');
      $disk_style = 'success';
    } else if ($summary->disk_used_percent < 90){
      $disk_color = getNamedColor('warning');
      $disk_style = 'warning';
    } else {
      $disk_color = getNamedColor('danger');
      $disk_style = 'danger';
    }

  // DAEMON speed
    if (is_null($summary->daemon_response_time_sec)){
      $resp_percent = 0;
      $resp_color = getNamedColor('primary');
      $resp_style = 'primary';
    } else if ($summary->daemon_response_time_sec < 1){
      $resp_percent = max(100*(5-$summary->daemon_response_time_sec)/5,0);
      $resp_color = getNamedColor('success');
      $resp_style = 'success';
    } else if ($summary->daemon_response_time_sec < 4){
      $resp_percent = max(100*(5-$summary->daemon_response_time_sec)/5,0);
      $resp_color = getNamedColor('warning');
      $resp_style = 'warning';
    } else {
      $resp_percent = max(100*(5-$summary->daemon_response_time_sec)/5,0);
      $resp_color = getNamedColor('danger');
      $resp_style = 'danger';
    }

  // SYNC status
    if (is_null($summary->daemon_last_block) || $summary->daemon_last_block == 0 || is_null($summary->database_last_block)){
      $sync_percent = 0;
      $sync_diff = null;
    } else {
      $sync_percent = 100*$summary->database_last_block/$summary->daemon_last_block;
      $sync_diff = $summary->daemon_last_block - $summary->database_last_block;
    }
    if(is_null($sync_diff)){
      $sync_color = getNamedColor('primary');
      $sync_style = 'primary';
    } else if($sync_diff > 3*$settings['maturity']){
      $sync_color = getNamedColor('danger');
      $sync_style = 'danger';
    } else if ($sync_diff > round($settings['maturity']/5,0)){
      $sync_color = getNamedColor('warning');
      $sync_style = 'warning';
    } else {
      $sync_color = getNamedColor('success');
      $sync_style = 'success';
    }

  //Time Since Last Block
    if (!is_null($summary->time_since_last_daemon_block->diff)){
      if ($summary->time_since_last_daemon_block->diff > 7200){
        $last_block_status = "PROBLEM";
        $time_daemon_style = "danger";
      } else if($summary->time_since_last_daemon_block->diff > 3600){
        $last_block_status = "CHECK";
        $time_daemon_style = "warning";
      } else {
        $last_block_status = "OK";
        $time_daemon_style = "success";
      }
    } else {
      $last_block_status = "N/A";
      $time_daemon_style = "primary";
    }



  // DAEMON status
    if($summary->daemon_info->status == "ONLINE"){
      $daemon_style = 'success';
    } else { 
      $daemon_style = 'danger';
    }
  // CONNECTIONS count
    if(isset($summary->daemon_connection_count) && !empty($summary->daemon_connection_count)){
      if ($summary->daemon_connection_count == 0){
        $connections_style = 'danger';
      } else if ($summary->daemon_connection_count < 4){
        $connections_style = 'warning';
      } else {
        $connections_style = 'success';
      }
      
    } else { 
      $connections_style = 'primary';
    }

  // SUBSCRIPTION info
    if ($summary->subscription_status == "ENABLED") {
      $subscription_status = "ENABLED";
      $subscription_expires = $summary->subscription_expires;
      $time_till_expired = timediffToParts($summary->subscription_remains);
      $subscription_remains = $time_till_expired->string;
      if ($summary->subscription_remains <= 0 ){
        // expired
        $subscription_style = 'danger';
        $subscription_remains = "EXPIRED";
      } else if ($summary->subscription_remains <= 7*24*60*60 ) {
        // expires soon
        $subscription_style = 'warning';
      } else {
        // has time
        $subscription_style = 'success';
      }

    } else {
      $subscription_status = "DISABLED";
      $subscription_expires = "Never";
      $subscription_remains = "Infinite";
      $subscription_style = 'primary';
    }

  // host
    $cpu_percent_split = splitFloat( $summary->cpu_load_percent,1,1);
    $mem_percent_split = splitFloat( $summary->memory_used_percent,1,1);
    $swp_percent_split = splitFloat( $summary->swap_used_percent,1,1);
    $hdd_percent_split = splitFloat( $summary->disk_used_percent,1,1);
    $dmn_percent_split = splitFloat( $resp_percent,1,1);
    $syn_percent_split = splitFloat( $sync_percent,16,1);
    $observation_period = is_null($summary->observation_period_seconds) ? "---" : round($summary->observation_period_seconds,0);
    $mem_total  = ui_siOrPlaceholder($summary->memory_total_bytes);
    $mem_free   = ui_siOrPlaceholder(is_null($summary->memory_total_bytes) ? null : $summary->memory_total_bytes-$summary->memory_used_bytes);
    $swp_total  = ui_siOrPlaceholder($summary->swap_total_bytes);
    $swp_free   = ui_siOrPlaceholder(is_null($summary->swap_total_bytes) ? null : $summary->swap_total_bytes-$summary->swap_used_bytes);
    $hdd_total  = ui_siOrPlaceholder($summary->disk_total_bytes);
    $hdd_free   = ui_siOrPlaceholder($summary->disk_free_bytes);
    $mem_free_percent_split = splitFloat(100 - $mem_percent_split->number,1,1);
    $swp_free_percent_split = splitFloat(100 - $swp_percent_split->number,1,1);
    $hdd_free_percent_split = splitFloat(100 - $hdd_percent_split->number,1,1);
    $db_total   = ui_siOrPlaceholder($summary->database_data_size_bytes);
    $db_disk    = ui_siOrPlaceholder($summary->database_disk_size_bytes);
    $db_comp_percent_split  = (isset($db_total->value) && is_numeric($db_total->value) && $db_total->value!=0) ? splitFloat(100 - 100*($db_disk->value/$db_total->value),1,1) : splitFloat(null,1,1);
  // DB COMPRESSION
    if (is_null($db_comp_percent_split->number) || is_nan($db_comp_percent_split->number)){
      $db_style = 'primary';
    } else if ($db_comp_percent_split->number <5){
      $db_style = 'danger';
    } else if ($db_comp_percent_split->number <20){
      $db_style = 'warning';
    } else {
      $db_style = 'success';
    }
  //  
    $daemon_delay = is_null($summary->daemon_response_time_sec) ? normalizeSi(0,3,3) : normalizeSi( $summary->daemon_response_time_sec,3,3);

  // 25h
    $perfData = array();

    $etime = time();
    $stime = $etime - 90000;
    $hms = db_getHostMetricsByTimeRange($stime,$etime, true, true, true);
    $label_ds = array();
    $cpu_ds = array();
    $mem_ds = array();
    $hdd_ds = array();
    $dmn_ds = array();
    $nsb_ds = array(); // network sent bps
    $nrb_ds = array(); // network received bps

    // populate perfData
      foreach ($hms as $hm){
          $data = new stdClass();
          $data->date = $hm->_id*1000;
          if (!is_null($hm->system->cpu_load->idle)) {$data->cpu = (100-@cdec($hm->system->cpu_load->idle.""))->toFixed(1);}
          $data->mem = ( 100*@cdec($hm->system->memory_usage->used."")/@cdec($hm->system->memory_usage->total."") )->toFixed(1);
          $data->hdd = ( 100*@cdec($hm->system->disk_usage->free."")/@cdec($hm->system->disk_usage->total."") )->toFixed(1);
          $data->dmn = ( max(100*(5-@cdec($hm->daemon->daemon_response_time.""))/5, @cdec(0)) )->toFixed(1);
          if (isset($hm->daemon->traffic)){
            if (!is_null($hm->daemon->traffic->sent_bps)) { $data->uls = $hm->daemon->traffic->sent_bps; }
            if (!is_null($hm->daemon->traffic->recv_bps)) { $data->dls = $hm->daemon->traffic->recv_bps; }
          }
          $perfData[$hm->_id] = $data;
      }
      ksort($perfData);

    // foreach($hms as $hm){
    //   array_push($label_ds, date("M, d H:i",$hm->_id));
    //   array_push($cpu_ds, is_null($hm->system->cpu_load->idle) ? 'NaN' : round(100-$hm->system->cpu_load->idle,1));
    //   array_push($mem_ds, round(100*$hm->system->memory_usage->used/$hm->system->memory_usage->total,1));
    //   array_push($hdd_ds, round(100*$hm->system->disk_usage->free/$hm->system->disk_usage->total,1));
    //   array_push($dmn_ds, round(max(100*(5-$hm->daemon->daemon_response_time)/5,0),1));
    //   if (isset($hm->daemon->traffic)){
    //     array_push($nsb_ds, is_null($hm->daemon->traffic->sent_bps)? 'NaN' : $hm->daemon->traffic->sent_bps );
    //     array_push($nrb_ds, is_null($hm->daemon->traffic->recv_bps)? 'NaN' : $hm->daemon->traffic->recv_bps );
    //   } else {
    //     array_push($nsb_ds, 'NaN');
    //     array_push($nrb_ds, 'NaN');
    //   }
    // }
?>

<!--Section: Button icon-->
<?php
    include_once __DIR__ . '/../../includes/admin_menu.php';
?>
  <script>
    $(".menu-nav").removeClass("active");
    $("#admin-dashboard").addClass("active");
    let perfData = <?php echo json_encode(array_values($perfData)); ?>;
    var traf_chart;
    var perf_chart;

    function createCommonTooltip(amchart, cseries){
				  // Set up tooltip
				  cseries.adapter.add("tooltipText", function(ev) {
            var text = "[bold]{dateX.formatDate('yyyy-MM-dd HH:mm')}[/]\n"
            amchart.series.each(function(item) {
              text += "[" + item.stroke.hex + "]●[/] " + item.name + ": {" + item.dataFields.valueY + "} "+ item.measurement+ "\n";
            });
            return text;
				  });
			  }
    function createCommonBpsTooltip(amchart, cseries){
				  // Set up tooltip
				  cseries.adapter.add("tooltipText", function(ev) {
            var text = "[bold]{dateX.formatDate('yyyy-MM-dd HH:mm')}[/]\n"
            amchart.series.each(function(item) {
              text += "[" + item.stroke.hex + "]●[/] " + item.name + ": {" + item.dataFields.valueY + ".formatNumber('#.### a')}"+ item.measurement+ "\n";
            });
            return text;
				  });
			  }
    $(document).ready(function(){
      
      am4core.ready(function() {
        render_perf();
        render_traf();

      });
      
    });
  </script>
<section>

  <!--Grid row-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-12">
      <div class="card col-12 px-0 mb-4">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-tachometer-alt" aria-hidden="true"></i> PERFORMANCE INDICATORS </span>
        </div>
        <div class="row text-center py-3 px-2">
          <div class="col">
            <span class="min-chart m-0" id="min-cpu" data-percent="<?php echo $cpu_percent_split->whole; ?>"><span id="min-cpu-w" class="percent primary-text h6"><?php echo $cpu_percent_split->whole; ?><small>.<?php echo $cpu_percent_split->fraction; ?></small></span></span>
            <h5><span id="min-cpu-badge" class="label badge badge-<?php echo $cpu_style; ?>">CPU Load</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-ram" data-percent="<?php echo $mem_percent_split->whole; ?>"><span id="min-ram-w" class="percent primary-text h6"><?php echo $mem_percent_split->whole; ?><small>.<?php echo $mem_percent_split->fraction; ?></small></span></span>
            <h5><span id="min-ram-badge" class="label badge badge-<?php echo $mem_style; ?>">RAM Usage</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-swp" data-percent="<?php echo $swp_percent_split->whole; ?>"><span id="min-swp-w" class="percent primary-text h6"><?php echo $swp_percent_split->whole; ?><small>.<?php echo $swp_percent_split->fraction; ?></small></span></span>
            <h5><span id="min-swp-badge" class="label badge badge-<?php echo $swap_style; ?>">Swap Usage</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-hdd" data-percent="<?php echo $hdd_percent_split->whole; ?>"><span id="min-hdd-w" class="percent primary-text h6"><?php echo $hdd_percent_split->whole; ?><small>.<?php echo $hdd_percent_split->fraction; ?></small></span></span>
            <h5><span id="min-hdd-badge" class="label badge badge-<?php echo $disk_style; ?>">Disk Usage</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-dmn" data-percent="<?php echo $dmn_percent_split->whole; ?>"><span id="min-dmn-w" class="percent primary-text h6"><?php echo $dmn_percent_split->whole; ?><small>.<?php echo $dmn_percent_split->fraction; ?></small></span></span>
            <h5><span id="min-dmn-badge" class="label badge badge-<?php echo $resp_style; ?>">Daemon Speed</span></h5>
          </div>
          <div class="col">
            <span class="min-chart m-0" id="min-syn" data-percent="<?php echo $syn_percent_split->whole; ?>"><span id="min-syn-w" class="percent primary-text h6"><?php echo $syn_percent_split->whole; ?><small>.<?php echo $syn_percent_split->fraction; ?></small></span></span>
            <h5><span id="min-syn-badge" class="label badge badge-<?php echo $sync_style; ?>">Explorer Sync</span></h5>
          </div>

        </div>
      </div>
    </div>
    <script>
      $(function () {
        $('.min-chart#min-cpu').easyPieChart({
          barColor: "<?php echo $cpu_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-ram').easyPieChart({
          barColor: "<?php echo $mem_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-swp').easyPieChart({
          barColor: "<?php echo $swap_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-hdd').easyPieChart({
          barColor: "<?php echo $disk_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-dmn').easyPieChart({
          barColor: "<?php echo $resp_color;?>",
        });
      });
      $(function () {
        $('.min-chart#min-syn').easyPieChart({
          barColor: "<?php echo $sync_color;?>",
        });
      });
    </script>
    

  </div>
  
  <div class="row">
    <!--Grid column II-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <!--Masternode Earnings Card-->
      <div class="card col-12 pb-3">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color  accent-2 ml-0" ><i class="fas fa-server card-icon-text" aria-hidden="true"></i></a>
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
                    <td class='text-right p-1'><span id="mem-total"><?php echo ui_siText($mem_total); ?></span></td>
                    <td class='text-right p-1'><span id="mem-free"><?php echo ui_siText($mem_free); ?></span></td>
                    <td class='text-right p-1'><span id="mem-percent" class="badge badge-<?php echo $mem_style; ?>"><?php echo $mem_free_percent_split->whole; ?><small>.<?php echo $mem_free_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Swap</td>
                    <td class='text-right p-1'><span id="swp-total"><?php echo ui_siText($swp_total); ?></span></td>
                    <td class='text-right p-1'><span id="swp-free"><?php echo ui_siText($swp_free); ?></span></td>
                    <td class='text-right p-1'><span id="swp-percent" class="badge badge-<?php echo $swap_style; ?>"><?php echo $swp_free_percent_split->whole; ?><small>.<?php echo $swp_free_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Storage</td>
                    <td class='text-right p-1'><span id="hdd-total"><?php echo ui_siText($hdd_total); ?></span></td>
                    <td class='text-right p-1'><span id="hdd-free"><?php echo ui_siText($hdd_free); ?></span></td>
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
                    <td class='text-right p-1'><span id="db-total"><?php echo ui_siText($db_total); ?></span></td>
                    <td class='text-right p-1'><span id="db-disk"><?php echo ui_siText($db_disk); ?></span></td>
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
                  <?php if (false): ?>
                  <tr class="">
                    <td class='text-left p-1'>Daemon client version</td>
                    <td class='text-right p-1' colspan="3"><span id="dmn-version" class="badge badge-primary"><?php echo $summary->daemon_info->version; ?></span></td>
                  </tr>
                  <?php endif; ?>
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
                    <td class='text-right p-1 pt-4'>Explorer</td>
                    <td class='text-right p-1 pt-4'>Daemon</td>
                    <td class='text-right p-1 pt-4'>% Sync</td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Version</td>
                    <td class='text-right p-1'><span id="exp-version" class="badge badge-primary"><?php echo $summary->explorer_version; ?></span></td>
                    <td class='text-right p-1'><span id="dmn-version" class="badge badge-primary"><?php echo $summary->daemon_info->version; ?></span></td>
                    <td class='text-right p-1'></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Chain height</td>
                    <td class='text-right p-1'><span id="db-lastblock"><?php echo $summary->database_last_block; ?></span></td>
                    <td class='text-right p-1'><span id="dmn-lastblock"><?php echo $summary->daemon_last_block; ?></span></td>
                    <td class='text-right p-1'><span id="db-sync-percent" class="badge badge-<?php echo $sync_style; ?>"><?php echo $syn_percent_split->whole; ?><small>.<?php echo $syn_percent_split->fraction; ?>%</small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Time since last block</td>
                    <td class='text-right p-1'><span id="db-lastblocktime"><?php echo $summary->time_since_last_db_block->string; ?></span></td>
                    <td class='text-right p-1'><span id="dmn-lastblocktime"><?php echo $summary->time_since_last_daemon_block->string; ?></span></td>
                    <td class='text-right p-1'><span id="lb-satus" class="badge badge-<?php echo $time_daemon_style; ?>"><?php echo $last_block_status; ?></span></td>
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
  
  <?php if (false): ?>
  <div class="row">
    <div class="col-xl-6 col-lg-12 flex-col mb-3">
      <div class="card col-12 pt-3">
        <div class="my-2 col-12 text-right">
          <div class="chart-container text-center" style="height: 25em;">
            <canvas id="lcPerf" class="mb-3" height="100%"></canvas>
          </div>
        </div>
        <script>
          var ctxL = document.getElementById('lcPerf').getContext('2d');
          var myLineChart = new Chart(ctxL, { 
            type: 'line',
            height: '100%',
            data: { 
              spanGaps: false,
              labels: [
                <?php  
                  foreach($label_ds as $n=>$a){
                    echo "'" . $a . "',";
                  } 
                ?>
              ],
              datasets: [
                {
                  label: 'CPU Load',
                  fill: false,
                  data: [ 
                    <?php foreach ($cpu_ds as $i=>$v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor('danger'),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor('danger'),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 3,
                },{
                  label: 'Memory Used',
                  fill: false,
                  data: [ 
                    <?php foreach ($mem_ds as $i=>$v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor('warning'),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor('warning'),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 3,
                },{
                  label: 'Disk Free',
                  fill: false,
                  data: [ 
                    <?php foreach ($hdd_ds as $i=>$v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor('success'),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor('success'),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 3,
                },{
                  label: 'Daemon Speed',
                  fill: false,
                  data: [ 
                    <?php foreach ($dmn_ds as $i=>$v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor('primary'),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor('primary'),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 3,
                }
              ],
            },
            options: {
              tooltips: {
                mode: 'x',
                callbacks: {
                  label: function(tooltipItem, data) {
                    var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ' : ' + tooltipItem.yLabel.toFixed(1) + '%';
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
                      labelString: 'Percent',
                      display: true,
                    },
                    ticks: {
                      beginAtZero: true,
                      precision: 0,
                      callback: function(value, index, values) {
                        return value.toFixed(0)+'%';
                      },
                      proposedMax: 100,  
                    },
                  }
                ]
              }
            }
          });
        </script>
      </div>
    </div>
    <div class="col-xl-6 col-lg-12 flex-col mb-3">
      <div class="card col-12 pt-3">
        <div class="my-2 col-12 text-right">
          <div class="chart-container text-center" style="height: 25em;">
            <canvas id="lcNet" class="mb-3" height="100%"></canvas>
          </div>
        </div>
        <script>
          var ctxL = document.getElementById('lcNet').getContext('2d');
          var netLineChart = new Chart(ctxL, { 
            type: 'line',
            height: '100%',
            data: { 
              spanGaps: false,
              labels: [
                <?php  
                  foreach($label_ds as $n=>$a){
                    echo "'" . $a . "',";
                  } 
                ?>
              ],
              datasets: [
                {
                  label: 'Downlink Speed',
                  fill: false,
                  data: [ 
                    <?php foreach ($nrb_ds as $i=>$v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor('success'),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor('success'),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 3,
                },{
                  label: 'Uplink Speed',
                  fill: false,
                  data: [ 
                    <?php foreach ($nsb_ds as $i=>$v){ echo $v.","; } ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor('danger'),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor('danger'),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 3,
                }
              ],
            },
            options: {
              tooltips: {
                mode: 'x',
                callbacks: {
                  label: function(tooltipItem, data) {
                    var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ' : ' + tooltipItem.yLabel.toFixed(1) + ' bps';
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
                      labelString: 'Bits per Second',
                      display: true,
                    },
                    ticks: {
                      beginAtZero: true,
                      precision: 0,
                      callback: function(value, index, values) {
                        return value.toFixed(0)+' bps';
                      },
                      proposedMax: 100,  
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
  <?php endif; ?>

  <div class="row">
    <div class="col-lg-6 col-md-12 mb-4">
      <div class="card w-100">
        <div class="card card-body p-2">
            <div id="am_perf" class="stats-chart pt-2"></div>
            <script>
              function render_perf(){
                perf_chart = am4core.create("am_perf", am4charts.XYChart);
                perf_chart.colors.step = 2;
                perf_chart.fontSize = <?php echo $chartFontSize; ?>;
                perf_chart.data = perfData;
                perf_chart.paddingBottom = 0;
                perf_chart.marginBottom = 0;
                // zoomOut button
                perf_chart.zoomOutButton.scale = 0.5;
                // Create axes
                // AxisX
                var dateAxis = perf_chart.xAxes.push(new am4charts.DateAxis());
                dateAxis.startLocation = 0;
                dateAxis.endLocation = 1;
                dateAxis.renderer.grid.template.location = 0;
                dateAxis.renderer.minGridDistance = <?php echo $minGridDistance; ?>;
                dateAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                dateAxis.keepSelection = true;
                //dateAxis.tooltipDateFormat = "d MMM, yyyy";
                //dateAxis.groupData = true;
                //setDateGridIntervals(dateAxis);
                // Axis Y
                var  valueAxis = perf_chart.yAxes.push(new am4charts.ValueAxis());
                valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                valueAxis.title.text = "Percent";
                valueAxis.calculateTotals = true;
                valueAxis.min = 0;
                valueAxis.max = 100;
                valueAxis.strictMinMax = true;
                valueAxis.renderer.labels.template.adapter.add("text", function(text) {
                  return text + "%";
                });

                // CPU Series
                var s = perf_chart.series.push(new am4charts.LineSeries());
                s.dataFields.valueY = "cpu";
                //s.dataFields.valueYShow = "totalPercent";
                s.dataFields.dateX = "date";
                s.name = "CPU Load";
                //s.tooltipText = "{name}: [bold]{valueY.value}[/]";
                s.measurement = "%";
                s.tensionX = 0.95;
                s.tensionY = 0.95;

                s.stroke = am4core.color( "<?php echo $settings["named_colors"]["danger"]; ?>");
                s.strokeWidth = 1.5;
                s.tooltip.defaultState.transitionDuration = 0;
				        s.tooltip.hiddenState.transitionDuration = 0;
                s.tooltip.getFillFromObject = false;
				        s.tooltip.background.fill = am4core.color("#fff");
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                // static
                s.legendSettings.labelText = "CPU Load";
                //s.legendSettings.valueText = "{valueY.close}"+s.measurement;

                // hovering
                s.legendSettings.itemLabelText = "CPU Load";
               // s.legendSettings.itemValueText = "{valueY}"+s.measurement;

                // MEMORY Series
                var s = perf_chart.series.push(new am4charts.LineSeries());
                s.dataFields.valueY = "mem";
                //s.dataFields.valueYShow = "totalPercent";
                s.dataFields.dateX = "date";
                s.name = "Memory Usage";
                //s.tooltipText = "{name}: [bold]{valueY.value}[/]";
                s.measurement = "%";
                s.tensionX = 0.95;
                s.tensionY = 0.95;

                s.stroke = am4core.color( "<?php echo $settings["named_colors"]["warning"]; ?>");
                s.strokeWidth = 1.5;
                s.tooltip.defaultState.transitionDuration = 0;
				        s.tooltip.hiddenState.transitionDuration = 0;
                s.tooltip.getFillFromObject = false;
				        s.tooltip.background.fill = am4core.color("#fff");
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                //s.tooltip.getStrokeFromObject = true;
                //s.tooltip.background.strokeWidth = 2;

                // s.fillOpacity = 0.85;
                // s.stacked = true;

                // static
                s.legendSettings.labelText = "Memory Usage";
                //s.legendSettings.valueText = "{valueY.close}"+s.measurement;

                // hovering
                s.legendSettings.itemLabelText = "Memory Usage";
                //s.legendSettings.itemValueText = "{valueY}"+s.measurement;

                // DISK Series
                var s = perf_chart.series.push(new am4charts.LineSeries());
                s.dataFields.valueY = "hdd";
                //s.dataFields.valueYShow = "totalPercent";
                s.dataFields.dateX = "date";
                s.name = "Disk Free";
                //s.tooltipText = "{name}: [bold]{valueY.value}[/]";
                s.measurement = "%";
                s.tensionX = 0.95;
                s.tensionY = 0.95;

                s.stroke = am4core.color( "<?php echo $settings["named_colors"]["success"]; ?>");
                s.strokeWidth = 1.5;
                s.tooltip.defaultState.transitionDuration = 0;
				        s.tooltip.hiddenState.transitionDuration = 0;

                s.tooltip.getFillFromObject = false;
				        s.tooltip.background.fill = am4core.color("#fff");
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                //s.tooltip.getStrokeFromObject = true;
                //s.tooltip.background.strokeWidth = 2;

                // s.fillOpacity = 0.85;
                // s.stacked = true;

                // static
                s.legendSettings.labelText = "Disk Free";
                //s.legendSettings.valueText = "{valueY.close}"+s.measurement;

                // hovering
                s.legendSettings.itemLabelText = "Disk Free";
                //s.legendSettings.itemValueText = "{valueY}"+s.measurement;


                // DAEMON Series
                var s = perf_chart.series.push(new am4charts.LineSeries());
                s.dataFields.valueY = "dmn";
                //s.dataFields.valueYShow = "totalPercent";
                s.dataFields.dateX = "date";
                s.name = "Daemon Speed";
                //s.tooltipText = "{name}: [bold]{valueY.value}[/]";
                s.measurement = "%";
                s.tensionX = 0.95;
                s.tensionY = 0.95;

                s.stroke = am4core.color( "<?php echo $settings["named_colors"]["primary"]; ?>");
                s.strokeWidth = 1.5;
                s.tooltip.defaultState.transitionDuration = 0;
				        s.tooltip.hiddenState.transitionDuration = 0;

                s.tooltip.getFillFromObject = false;
				        s.tooltip.background.fill = am4core.color("#fff");
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                //s.tooltip.getStrokeFromObject = true;
                //s.tooltip.background.strokeWidth = 2;

                // s.fillOpacity = 0.85;
                // s.stacked = true;

                // static
                s.legendSettings.labelText = "Daemon Speed";
                //s.legendSettings.valueText = "{valueY.close}"+s.measurement;

                // hovering
                s.legendSettings.itemLabelText = "Daemon Speed";
                //s.legendSettings.itemValueText = "{valueY}"+s.measurement;

                // add ToolTip
                createCommonTooltip(perf_chart, s);

                // Add cursor
                perf_chart.cursor = new am4charts.XYCursor();

                // add legend
                perf_chart.legend = new am4charts.Legend();
                perf_chart.legend.paddingLeft = 30;
                //perf_chart.legend.markers.template.width = <?php echo $chartFontSize; ?>*1.5;
                //perf_chart.legend.markers.template.height = <?php echo $chartFontSize; ?>*1.5;
              }
            </script>
          </div>
        
      </div>
    </div>
    <div class="col-lg-6 col-md-12 mb-4">
      <div class="card w-100">
        <div class="card card-body p-2">
            <div id="am_traf" class="stats-chart pt-2"></div>
            <script>
              function render_traf(){
                traf_chart = am4core.create("am_traf", am4charts.XYChart);
                traf_chart.colors.step = 2;
                traf_chart.fontSize = <?php echo $chartFontSize; ?>;
                traf_chart.data = perfData;
                traf_chart.paddingBottom = 0;
                traf_chart.marginBottom = 0;
                // zoomOut button
                traf_chart.zoomOutButton.scale = 0.5;

                // Create axes
                // AxisX
                var dateAxis = traf_chart.xAxes.push(new am4charts.DateAxis());
                dateAxis.startLocation = 0;
                dateAxis.endLocation = 1;
                dateAxis.renderer.grid.template.location = 0;
                dateAxis.renderer.minGridDistance = <?php echo $minGridDistance; ?>;
                dateAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                dateAxis.keepSelection = true;
                // Axis Y
                var  valueAxis = traf_chart.yAxes.push(new am4charts.ValueAxis());
                valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                valueAxis.title.text = "Throughput (bps)";
                valueAxis.calculateTotals = true;
                valueAxis.min = 0;
                //valueAxis.max = 100;
                //valueAxis.strictMinMax = true;
                valueAxis.numberFormatter.numberFormat = "#.###a"; 

                // UL Series
                var s = traf_chart.series.push(new am4charts.LineSeries());
                s.dataFields.valueY = "uls";
                //s.dataFields.valueYShow = "totalPercent";
                s.dataFields.dateX = "date";
                s.name = "Uplink";
                //s.tooltipText = "{name}: [bold]{valueY.value}[/]";
                s.measurement = "bps";
                s.tensionX = 0.95;
                s.tensionY = 0.95;

                s.stroke = am4core.color( "<?php echo $settings["named_colors"]["danger"]; ?>");
                s.strokeWidth = 1.5;
                s.tooltip.defaultState.transitionDuration = 0;
				        s.tooltip.hiddenState.transitionDuration = 0;
                s.tooltip.getFillFromObject = false;
				        s.tooltip.background.fill = am4core.color("#fff");
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                // static
                s.legendSettings.labelText = "Uplink";
                //s.legendSettings.valueText = "{valueY.close}"+s.measurement;

                // hovering
                s.legendSettings.itemLabelText = "Uplink";
               // s.legendSettings.itemValueText = "{valueY}"+s.measurement;

                // Downlink Series
                var s = traf_chart.series.push(new am4charts.LineSeries());
                s.dataFields.valueY = "dls";
                //s.dataFields.valueYShow = "totalPercent";
                s.dataFields.dateX = "date";
                s.name = "Downlink";
                //s.tooltipText = "{name}: [bold]{valueY.value}[/]";
                s.measurement = "bps";
                s.tensionX = 0.95;
                s.tensionY = 0.95;

                s.stroke = am4core.color( "<?php echo $settings["named_colors"]["success"]; ?>");
                s.strokeWidth = 1.5;
                s.tooltip.defaultState.transitionDuration = 0;
				        s.tooltip.hiddenState.transitionDuration = 0;
                s.tooltip.getFillFromObject = false;
				        s.tooltip.background.fill = am4core.color("#fff");
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                // static
                s.legendSettings.labelText = "Downlink";
                //s.legendSettings.valueText = "{valueY.close}"+s.measurement;

                // hovering
                s.legendSettings.itemLabelText = "Downlink";
                //s.legendSettings.itemValueText = "{valueY}"+s.measurement;

                // add ToolTip
                createCommonBpsTooltip(traf_chart, s);

                // Add cursor
                traf_chart.cursor = new am4charts.XYCursor();

                // add legend
                traf_chart.legend = new am4charts.Legend();
                traf_chart.legend.paddingLeft = 30;
                //traf_chartlegend.markers.template.width = <?php echo $chartFontSize; ?>*1.5;
                //traf_chartlegend.markers.template.height = <?php echo $chartFontSize; ?>*1.5;
              }
            </script>
        </div>
      </div>
    </div>
  </div>

</section>
<!--/Section: Button icon-->




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

        // Explorer version
        $('#exp-version').html(data.explorer_version);

        // EXPLORER sync
        $('#db-lastblock').html(data.database_last_block);
        $('#dmn-lastblock').html(data.daemon_last_block);
        $('#db-sync-percent').html(data.sync_load.w+'<small>.'+data.sync_load.f+'%</small>');
        $('#db-sync-percent').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.sync_style);

        // Time Since Last Block
        $('#db-lastblocktime').html(data.time_since_last_db_block.string);
        $('#dmn-lastblocktime').html(data.time_since_last_daemon_block.string);
        $('#lb-satus').html(data.last_block_status);
        $('#lb-satus').removeClass('badge-primary badge-danger badge-warning badge-success').addClass('badge-'+data.time_daemon_style);

        // myLineChart.data.labels = data.lc.label_ds; // labels
        // myLineChart.data.datasets[0].data = data.lc.cpu_ds; //cpu
        // myLineChart.data.datasets[1].data = data.lc.mem_ds; // mem
        // myLineChart.data.datasets[2].data = data.lc.hdd_ds; // disk
        // myLineChart.data.datasets[3].data = data.lc.dmn_ds; // dmn
        // myLineChart.update();

        // netLineChart.data.labels = data.lc.label_ds; // labels
        // netLineChart.data.datasets[0].data = data.lc.nrb_ds; // dl bps
        // netLineChart.data.datasets[1].data = data.lc.nsb_ds; // ul bps
        // netLineChart.update();

        // if (perf_chart.data[perf_chart.data.length-1].date != data.pd[data.pd.length-1].date){
        //   console.log("Updated: "+data.pd[data.pd.length-1].date+" -> cpu: "+data.pd[data.pd.length-1].cpu);
        // }
        perf_chart.data = data.pd;
        perf_chart.invalidateRawData();
        traf_chart.data = data.pd;
        traf_chart.invalidateRawData();

      },
    });
  }
  // Autorefresh
    $(document).ready(function(){
      setInterval(updateDashboard, <?php echo $settings['dashboard_refresh_sec']; ?> * 1000);
    });
</script>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"admin/dashboard",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
