<?php 
    require_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    $stime = milliseconds();

    global $rpc;
    global $dbm;
    $tt = new TimeTracker();
    global $tt;
    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["overview_precision"];
    $def_precision = (int)$settings["default_precision"];
    header('Content-Type: application/json');

    $result = array();

// get data from database
    $apt->createTimer("ui_GetOverviewData()",1);
    $data = ui_GetOverviewData();
    $apt->stopTimer("ui_GetOverviewData();");

// Block Count
    $result['bc']['text'] = $data->last_block->number;

// Explorer sync status
    $rpc_bc = getRpcBlockCount();
    if ($data->last_block->number < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }
    $result['ess']['status'] = $bcs;

// Avg block spacing
    $result['abs']['text'] = $data->block_spacing;

// Daemon blockchain sync status
    $bss = getBlockchainSyncStatus();
    $result['bss']['status'] = $bss;

// MnSync status
    if (isset($settings["show_pages"]["masternodes"]) && $settings["show_pages"]["masternodes"]){
        $mss = getMnSyncStatus();
        $result['mss']['status'] = $mss;
    } else {
        $result['mss']['status'] = true;
    }

// Coinsupply
    $cs_split = splitFloat($data->coin_supply->coincirculation,$precision,$precision);
    $result['cs']['whole'] = $cs_split->whole;
    $result['cs']['fraction'] = $cs_split->fraction;

// Zerocoins / Shielded
    if (isset($settings["show_zerocoin"]) && $settings["show_zerocoin"]) {
        if ($cs->zerocoinsupply != "not available"){
            $result['zs']['text'] = (splitFloat( $data->coin_supply->zerocoinsupply,$precision,$precision))->string;
            $result['zs']['lead'] = "Zerocoins";
        } else { $result['zs']['text'] = null; }
    } else if(isset($settings["show_shield"]) && $settings["show_shield"] == true) {
        if ($cs->shieldsupply != "not available"){
            $result['zs']['text'] = (splitFloat($data->coin_supply->shieldsupply,$precision,$precision))->string;
            $result['zs']['lead'] = "Shielded";
        } else { $result['zs']['text'] = null; }
    } else if (shadow_enabled()) {
        // portion of the circulating supply that was issued through the shadow ledger.
        // Taken live from the daemon: it is a single counter, unlike an aggregation over
        // every indexed payout, so it is cheap enough for the overview refresh.
        $grs = rpc_getgoldrushstate();
        if (!is_null($grs) && isset($grs->claimed_amount)){
            $result['zs']['text'] = (splitFloat(@cdec($grs->claimed_amount)/@cdec(100000000),$precision,$precision))->string;
            $result['zs']['lead'] = "Gold Rush";
        } else { $result['zs']['text'] = null; }
    } else { $result['zs']['text'] = null; }

// Hashrate <- TODO: check whether to use rpc or db
    $nhr = getNetworkHashrate();
    $nhr_split = splitFloat($nhr->value,3,3);       
    $result['hr']['whole'] = $nhr_split->whole;
    $result['hr']['fraction'] = $nhr_split->fraction;
    $result['hr']['dim'] = $nhr->dimension;

// Difficulty <- TODO: check whether to use rpc or db
    $nd = getDifficulty();
    $nd_split = splitFloat($nd->value,3,3);
    $result['dif']['whole'] = $nd_split->whole;
    $result['dif']['fraction'] = $nd_split->fraction;
    $result['dif']['dim'] = $nd->dimension;

// Masternode count
    if (isset($settings["show_pages"]["masternodes"]) && $settings["show_pages"]["masternodes"]){
        $result['mnc']['total'] = $data->counts->mn_total;
        $result['mnc']['enabled'] = $data->counts->mn_enabled;
    } else {
        $rpc_mp = ui_getMempoolInfo();
        $result['mnc']['total'] = $data->mempool->size;
        $result['mnc']['enabled'] = $data->mempool->bytes_string;
        $result['mss']['status'] = $bss;
    }
// Address count
    $result['ac']['text'] = ($data->counts->addresses < 0) ? 0 : $data->counts->addresses;

// Addressgroup count
    $result['agc']['text'] = ($data->counts->addressgroups < 0) ? 0 : $data->counts->addressgroups;

// Tx count
    $result['txc']['text'] = ($data->counts->txs < 0) ? 0 : $data->counts->txs;

// UTXO count
    $result['utxoc']['text'] = ($data->counts->vouts < 0) ? 0 : $data->counts->vouts;

// Market data
    $mm_upd = getTimeSinceTimestamp($data->market_data->timestamp);
    $cmc_split = splitFloat(
      isset($data->market_data->capitalization->in_usd) ? $data->market_data->capitalization->in_usd : 0,
      $precision,
      $precision
    );
    $avp_split = splitFloat(
      isset($data->market_data->avg_prices->in_btc) ? $data->market_data->avg_prices->in_btc : 0,
      $def_precision,
      $def_precision
    );
    $cng_split = splitFloat(
      isset($data->market_data->avg_percent_change) ? @cdec($data->market_data->avg_percent_change) : 0,
      2,
      2
    );
    if ($cng_split->number > 0) {
        $p_class = "text-success";
        $trend = '<i class="fas fa-long-arrow-alt-up ml-2" aria-hidden="true"></i>';
      } else if ($cng_split->number < 0){
        $p_class = "text-danger";
        $trend = '<i class="fas fa-long-arrow-alt-down ml-2" aria-hidden="true"></i>';
      } else {
        $p_class = "grey-text";
        $trend = '<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>';
      }
      if (!$bcs) {$p_class = "grey-text";}
    $result['mkt']['cmc']['whole'] = '$&nbsp;'.$cmc_split->whole; // coin market cap whole
    $result['mkt']['cmc']['fraction'] = $cmc_split->fraction; // coin market cap fraction
    $result['mkt']['avp']['whole'] = $avp_split->whole; // avg price whole
    $result['mkt']['avp']['fraction'] = $avp_split->fraction; // avg price fraction
    $result['mkt']['cng']['whole'] = $cng_split->whole; // change percent whole
    $result['mkt']['cng']['fraction'] = $cng_split->fraction; // change percent fraction
    $result['mkt']['cng']['trend'] = $trend; // change percent trend (arrow)
    $result['mkt']['cng']['class'] = $p_class; // change percent class
    $result['mkt']['tsu']['text'] = $mm_upd->string; // time since update
// Coinsupply chart
    $gcs = ui_calculateCoinCirculationChartData($apt);
    $result['csc']['data'] = $gcs->blockStats_data;
    $apt = $gcs->apt;

// Markets table
    $ms = '';
    foreach ($data->market_data->metrics as $m){
        $p_split = splitFloat($m->latest,$def_precision,$def_precision);
        $v_split = splitFloat($m->quote_volume, $precision, $precision);
        $c_split = splitFloat(@cdec($m->percent_change),2,2);
        $m_link = getMarketLink($m->exchange,$m->base,$m->quote);
        if ($c_split->number > 0) {
        $p_class = "text-success";
        $p_trend = '<i class="fas fa-long-arrow-alt-up ml-2" aria-hidden="true"></i>';
        } else if ($c_split->number < 0){
        $p_class = "text-danger";
        $p_trend = '<i class="fas fa-long-arrow-alt-down ml-2" aria-hidden="true"></i>';
        } else {
        $p_class = "grey-text";
        $p_trend = '<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>';
        }

        $ms = $ms."<tr><td class='text-left p-1'><a class='primary-text' ".((!empty($m_link)) ? "href='".$m_link."' target='_blank'" : "" )." >".$m->exchange."</a></td>
        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : $p_class )."'>". $p_split->whole.".".$p_split->fraction."</td>
        <td class='text-center p-1 " . ((!$bcs) ? "grey-text" : $p_class )."'>". $p_trend."&nbsp;".$c_split->whole.".".$c_split->fraction."%</td>
        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : "" )."'>". $v_split->whole."<small>.".$v_split->fraction."</small></td>
        </tr>";
    } 
    $result['mtb']['tbody'] = $ms;

// Top spent
    $sa = '';
    foreach ($data->top_spent as $am){
        $am_split = splitFloat($am->amount,$def_precision,$def_precision);
        $tag_str = "";
        if (isset($am->address_alias) || isset($am->tags)){
          if (isset($am->address_alias) && !empty($am->address_alias)){
            $tag_str.= "<span class='badge badge-info ml-1 mb-1'>".h($am->address_alias)."</span>&nbsp;";
          }
          if (isset($am->tags) && count($am->tags)>0){
            foreach ($am->group_tags as $tag){
                $tag_str.= "<span class='badge badge-primary ml-1 mb-1'>".h($tag)."</span>&nbsp;";
            }
        }
        } 
        if (isset($am->suspect) && $am->suspect){
            $tag_str.= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movement'></i>&nbsp;";
        }
        $sa= $sa . "<tr><td class='text-left py-1 px-2 text-truncate'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str."&nbsp;
        <small><a class='' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a></small></td>
        <td class='text-right py-1 px-2 text-danger'>".$am_split->whole."<small>.".$am_split->fraction."</small></td></tr>";
    }
    $result['sa']['tbody'] = $sa;

// Top received
    $ra = '';
    foreach ($data->top_received as $am){
        $am_split = splitFloat($am->amount,$def_precision,$def_precision);
        $tag_str = "";
        if (isset($am->address_alias) || isset($am->tags)){
          if (isset($am->address_alias) && !empty($am->address_alias)){
            $tag_str.= "<span class='badge badge-info ml-1 mb-1'>".h($am->address_alias)."</span>&nbsp;";
          }            
          if (isset($am->tags) && count($am->tags)>0){
            foreach ($am->group_tags as $tag){
              $tag_str.= "<span class='badge badge-primary ml-1 mb-1'>".h($tag)."</span>&nbsp;";
            }
          }
        } 
        if (isset($am->suspect) && $am->suspect){
            $tag_str.= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movement'></i>&nbsp;";
          }
        $ra = $ra . "<tr><td class='text-left py-1 px-2 text-truncate'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str."&nbsp;
        <small><a class='' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a></small></td>
        <td class='text-right py-1 px-2 text-success'>".$am_split->whole."<small>.".$am_split->fraction."</small></td></tr>";
      }
    $result['ra']['tbody'] = $ra;
 
// return query    
    $result['qtime_ms'] = milliseconds() - $stime;
  echo json_encode($result);
?>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_up/overview",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>