<?php 
    include_once __DIR__ . '/../../../lib/common.php';
    require __DIR__ . '/../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';
    include_once __DIR__ . '/../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    sec_requireAdmin();
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();


    global $rpc;
    global $dbm;
    $tt = new TimeTracker();
    global $tt;
    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["overview_precision"];
    $def_precision = (int)$settings["default_precision"];

    header('Content-Type: application/json');

    $stime = milliseconds();
    $perfData = array();
    $result =new stdClass();
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
  $result->cpu_color = $cpu_color;
  $result->cpu_style = $cpu_style;

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
  $result->mem_color = $mem_color;
  $result->mem_style = $mem_style;

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
  $result->swap_color = $swap_color;
  $result->swap_style = $swap_style;

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
  $result->disk_color = $disk_color;
  $result->disk_style = $disk_style;

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
  $result->resp_color = $resp_color;
  $result->resp_style = $resp_style;

// SYNC status

  if (is_null($summary->daemon_last_block) || is_null($summary->database_last_block)){
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
  // A stopped loader is a failed sync, however close the heights still are.
  $result->loader_abort = isset($summary->loader_abort) ? $summary->loader_abort : null;
  if (!is_null($result->loader_abort)){
    $sync_color = getNamedColor('danger');
    $sync_style = 'danger';
    $result->loader_status = "STOPPED";
  } else {
    $result->loader_status = "OK";
  }
  $result->daemon_last_block = $summary->daemon_last_block;
  $result->database_last_block = $summary->database_last_block;
  $result->sync_color = $sync_color;
  $result->sync_style = $sync_style;

  //Time Since Last Block
  $result->time_since_last_daemon_block = new stdClass();
  $result->time_since_last_daemon_block->diff     = $summary->time_since_last_daemon_block->diff;
  $result->time_since_last_daemon_block->string   = $summary->time_since_last_daemon_block->string;
  $result->time_since_last_db_block = new stdClass();
  $result->time_since_last_db_block->diff         = $summary->time_since_last_db_block->diff;
  $result->time_since_last_db_block->string       = $summary->time_since_last_db_block->string;
  if (!is_null($result->time_since_last_daemon_block->diff)){
    if ($result->time_since_last_daemon_block->diff > 7200){
      $result->last_block_status = "PROBLEM";
      $result->time_daemon_style = "danger";
    } else if($result->time_since_last_daemon_block->diff > 3600){
      $result->last_block_status = "CHECK";
      $result->time_daemon_style = "warning";
    } else {
      $result->last_block_status = "OK";
      $result->time_daemon_style = "success";
    }
  } else {
    $result->last_block_status = "N/A";
    $result->time_daemon_style = "primary";
  }


// DAEMON status
  if($summary->daemon_info->status == "ONLINE"){
    $daemon_style = 'success';
  } else { 
    $daemon_style = 'danger';
  }
  $result->daemon_status = $summary->daemon_info->status;
  $result->daemon_style = $daemon_style;
  $result->daemon_version = $summary->daemon_info->version; 
  $result->daemon_protocolversion = $summary->daemon_info->protocolversion;
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
  $result->daemon_connection_count = $summary->daemon_connection_count;
  $result->connections_style = $connections_style;

// Explorer version
  $result->explorer_version = $summary->explorer_version;

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
  $result->subscription_status = $subscription_status;
  $result->subscription_expires = $subscription_expires;
  $result->subscription_remains = $subscription_remains;
  $result->subscription_style = $subscription_style;



// host
  $cpu_percent_split = splitFloat( $summary->cpu_load_percent,1,1);
  $result->cpu_load = new stdClass();
  $result->cpu_load->w = $cpu_percent_split->whole;
  $result->cpu_load->f = $cpu_percent_split->fraction;

  $mem_percent_split = splitFloat( $summary->memory_used_percent,1,1);
  $result->mem_load = new stdClass();
  $result->mem_load->w = $mem_percent_split->whole;
  $result->mem_load->f = $mem_percent_split->fraction;

  $swp_percent_split = splitFloat( $summary->swap_used_percent,1,1);
  $result->swap_load = new stdClass();
  $result->swap_load->w = $swp_percent_split->whole;
  $result->swap_load->f = $swp_percent_split->fraction;

  $hdd_percent_split = splitFloat( $summary->disk_used_percent,1,1);
  $result->disk_load = new stdClass();
  $result->disk_load->w = $hdd_percent_split->whole;
  $result->disk_load->f = $hdd_percent_split->fraction;

  $dmn_percent_split = splitFloat( $resp_percent,1,1);
  $result->resp_load = new stdClass();
  $result->resp_load->w = $dmn_percent_split->whole;
  $result->resp_load->f = $dmn_percent_split->fraction;

  $syn_percent_split = splitFloat( $sync_percent,16,1);
  $result->sync_load = new stdClass();
  $result->sync_load->w = $syn_percent_split->whole;
  $result->sync_load->f = $syn_percent_split->fraction;

  $observation_period = is_null($summary->observation_period_seconds) ? "---" : round($summary->observation_period_seconds,0);
  $result->observation_period = $observation_period;

  $mem_total  = ui_siOrPlaceholder($summary->memory_total_bytes);
  $result->mem_total = $mem_total;
  if (is_numeric($result->mem_total->value)) { $result->mem_total->value = fpp($result->mem_total->value,3); }
  $mem_free   = ui_siOrPlaceholder(is_null($summary->memory_total_bytes) ? null : $summary->memory_total_bytes-$summary->memory_used_bytes);
  $result->mem_free = $mem_free;
  if (is_numeric($result->mem_free->value)) { $result->mem_free->value = fpp($result->mem_free->value,3); }
  $swp_total  = ui_siOrPlaceholder($summary->swap_total_bytes);
  $result->swp_total = $swp_total;
  if (is_numeric($result->swp_total->value)) { $result->swp_total->value = fpp($result->swp_total->value,3); }
  $swp_free   = ui_siOrPlaceholder(is_null($summary->swap_total_bytes) ? null : $summary->swap_total_bytes-$summary->swap_used_bytes);
  $result->swp_free = $swp_free;
  if (is_numeric($result->swp_free->value)) { $result->swp_free->value = fpp($result->swp_free->value,3); }
  $hdd_total  = ui_siOrPlaceholder($summary->disk_total_bytes);
  $result->hdd_total = $hdd_total;
  if (is_numeric($result->hdd_total->value)) { $result->hdd_total->value = fpp($result->hdd_total->value,3); }
  $hdd_free   = ui_siOrPlaceholder($summary->disk_free_bytes);
  $result->hdd_free = $hdd_free;
  if (is_numeric($result->hdd_free->value)) { $result->hdd_free->value = fpp($result->hdd_free->value,3); }
  $mem_free_percent_split = splitFloat(100 - $mem_percent_split->number,1,1);
  $result->mem_free_percent = new stdClass();  
  $result->mem_free_percent->w = $mem_free_percent_split->whole;
  $result->mem_free_percent->f = $mem_free_percent_split->fraction;
  $swp_free_percent_split = splitFloat(100 - $swp_percent_split->number,1,1);
  $result->swp_free_percent = new stdClass();
  $result->swp_free_percent->w = $swp_free_percent_split->whole;
  $result->swp_free_percent->f = $swp_free_percent_split->fraction;
  $hdd_free_percent_split = splitFloat(100 - $hdd_percent_split->number,1,1);
  $result->hdd_free_percent = new stdClass();
  $result->hdd_free_percent->w = $hdd_free_percent_split->whole;
  $result->hdd_free_percent->f = $hdd_free_percent_split->fraction;
  $db_total   = ui_siOrPlaceholder($summary->database_data_size_bytes);
  $result->db_total = $db_total;
  if (is_numeric($result->db_total->value)) { $result->db_total->value = fpp($result->db_total->value,3); }
  $db_disk    = ui_siOrPlaceholder($summary->database_disk_size_bytes);
  $result->db_disk = $db_disk;
  if (is_numeric($result->db_disk->value)) { $result->db_disk->value = fpp($result->db_disk->value,3); }
  $db_comp_percent_split  = (isset($db_total->value) && is_numeric($db_total->value) && $db_total->value!=0) ? splitFloat(100 - 100*($db_disk->value/$db_total->value),1,1) : splitFloat(null,1,1);
  $result->db_comp_percent = new stdClass();
  $result->db_comp_percent->w = $db_comp_percent_split->whole;
  $result->db_comp_percent->f = $db_comp_percent_split->fraction;



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
    $result->db_style = $db_style;
    $daemon_delay = is_null($summary->daemon_response_time_sec) ? normalizeSi(0,3,3) : normalizeSi( $summary->daemon_response_time_sec,3,3);
    $result->daemon_delay = fpp($daemon_delay->value,3);

// 25h
  $etime = time();
  $stime = $etime - 90000;
  $hms = db_getHostMetricsByTimeRange($stime,$etime, true, true, true);
  $label_ds = array();
  $cpu_ds = array();
  $mem_ds = array();
  $hdd_ds = array();
  $dmn_ds = array();
  $nrb_ds = array();
  $nsb_ds = array();

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
  // $result->lc->label_ds = $label_ds;
  // $result->lc->cpu_ds = $cpu_ds;
  // $result->lc->mem_ds = $mem_ds;
  // $result->lc->hdd_ds = $hdd_ds;
  // $result->lc->dmn_ds = $dmn_ds;
  // $result->lc->nrb_ds = $nrb_ds;
  // $result->lc->nsb_ds = $nsb_ds;
  $result->pd = array_values($perfData);
// return query    
    $result->qtime_ms = milliseconds() - $stime;
  echo json_encode($result);
?>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_up/dashboard",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>