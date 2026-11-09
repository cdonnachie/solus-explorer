<?php
include_once 'common.php';
use Decimal\Decimal;
  // --- ADDRESS QUERIES ---
    function apiGetBalance($address, $legacy = false){
        global $settings;
        $ab = new stdClass();
        $ab->timestamp = time();
        $addr = db_getAddressData($address);
        if (count($addr)>0){
            $ab->address = $addr[0]->_id;
            $ab->balance = fpp($addr[0]->balance);
        } else {
            header('Content-Type: text/plain');
            return "Error: Address Not Found";   
        }
        if ($legacy){
            header('Content-Type: text/plain');
            return fpp($ab->balance);
        } else{
            header('Content-Type: application/json');
            return json_encode($ab); 
        }
    }

    function apiGetRichList($limit){
        $rl = [];
        $top = db_getAddresesWithTopBalance($limit,0);
        $rank = 1;
        foreach ($top as $addr){
            $a = new stdClass();
            $a->rank = $rank;
            $a->addr = $addr->_id;
            $a->bal = @cdec($addr->balance);
            array_push($rl, $a);
            $rank++;
        }
        header('Content-Type: application/json');
        return json_encode($rl); 
    }

    function apiGetAddress($address){
        $ab = new stdClass();
        $ab->timestamp = time();
        $addr = db_getAddressData($address);
        if (count($addr)>0){
            $ab->address = $addr[0]->_id;
            $ab->balance = @cdec($addr[0]->balance)->toString();
            $ab->received = @cdec($addr[0]->received)->toString();
            $ab->sent = @cdec($addr[0]->sent)->toString();
            $ab->groupid = $addr[0]->group_id;
            if (isset($addr[0]->assets) && ((is_array($addr[0]->assets) && count($addr[0]->assets)>0) || (is_object($addr[0]->assets)) && count((array)$addr[0]->assets))){ 
                $ab->assets = array();
                foreach ($addr[0]->assets as $key=>$asset){
                    $value = @cdec($asset->value)->toString();
                    if ($value > 0 ){
                        $ab->assets[$asset->name] = $value;
                    }
                }
                //$ab->assets = $addr[0]->assets; 
            }
            $ab->last_txs = array();
            $lam = db_getAddressMovesDataByAddress($address);
            foreach ($lam as $am){
                $mv = new stdClass();
                $mv->tx_time = $am->move_time_sec;
                $mv->block_ix = $am->block_ix;
                $mv->txid = $am->tx_id;
                $mv->amount = @cdec($am->amount)->toString();
                $mv->is_reward = $am->is_reward;
                array_push($ab->last_txs, $mv);
            }
        } else {
            header('Content-Type: text/plain');
            return "Error: Address Not Found";   
        }

        header('Content-Type: application/json');
        return json_encode($ab); 

    }

  // --- ADDRESSGROUP QUERIES ---
    function apiGetAddressGroup($groupid, $withaddresslist = false){
        global $settings;
        $ag = new stdClass();
        $ag->timestamp = time();
        //$ars=getAddressGroupAggregates($groupid); 
        $ars = db_getAddressGroupById($groupid);

        if (count($ars)>0){
            $ag->groupid = $ars[0]->_id;
            //$ag->balance = $ars[0]->totalBalance;
            //$ag->address_count = $ars[0]->countAddresses;
            $ag->balance = fpp($ars[0]->balance);
            $ag->address_count = $ars[0]->address_count;

            if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) {
                $ag->masternode_count = 0;
                $ag->masternode_list = array();
                
                try {
                    $gms = getAddressGroupMasternodes('groupid', $groupid);
                    if (count($gms->mns)>0){$ag->masternode_list = $gms->mns; $ag->masternode_count = count($gms->mns);}
                } catch (Exception $e){
                    header('Content-Type: text/plain');
                }
            }

            if ($withaddresslist){
                $ag->address_list = array();
                try {
                    //$ams = db_getAddressesByGroupId($groupid, $ars[0]->countAddresses+1, 0, array(), ["_id" => 1,"balance" => 1]);
                    $ams = db_getAddressesByGroupId($groupid, $ars[0]->address_count+1, 0, array(), ["_id" => 1,"balance" => 1]);
                    foreach ($ams as $am){
                        $addr = new stdClass();
                        $addr->address = $am->_id;
                        $addr->balance = fpp($am->balance);
                        array_push($ag->address_list, $addr);
                    }
                } catch (Exception $e){
                    header('Content-Type: text/plain');
                    //return "Error: " . $e->getMessage();  

                }
            }

        } else {
            header('Content-Type: text/plain');
            return "Error: Address Group Not Found";   
        }

        header('Content-Type: application/json');
        return json_encode($ag); 

    }
  // --- ASSET QUERIES ---
    function apiGetAssetInfo($name, $withholderslist = false, $use_key = false){
        global $settings;
        $res = new stdClass();
        $res->result = "error";
        $res->timestamp = time();

        if ($use_key){
            $ast = db_getAssetByKey($name); 
        } else {
            $ast = db_getAssetByName($name); 
        }

        if (count($ast)>0){
            $res->result = "success";
            $res->data = $ast[0];
            if (isset($res->data->_id)){$res->data->name = $res->data->_id;unset($res->data->_id);}
            if (isset($res->data->supply)){$res->data->supply = @cdec($res->data->supply)->toString();}
            
            $res->data->holders_count = db_getAssetHoldersCountByKey($res->data->key); 

            if ($withholderslist){
                $res->data->top100_holders = array();
                try {
                    $holders = db_getAssetHoldersByKey($res->data->key, 100);
                    $key = $res->data->key;
                    foreach ($holders as $addr){
                        if (property_exists($addr->assets, $res->data->key)){
                            $res->data->top100_holders[$addr->_id] = @cdec(($addr->assets->$key)->value)->toString();
                        }
                    }
                    
                } catch (Exception $e){
                    // header('Content-Type: text/plain');
                    //return "Error: " . $e->getMessage();  
                }
            }

        } else {
            header('Content-Type: application/json');
            $res->error = "asset not found";
            return json_encode($res);   
        }

        header('Content-Type: application/json');
        return json_encode($res); 

    }

    function apiGetAssetHolders($name, $use_key = false){
        global $settings;
        $res = new stdClass();
        $res->result = "error";
        $res->timestamp = time();

        if ($use_key){
            $ast = db_getAssetByKey($name); 
        } else {
            $ast = db_getAssetByName($name); 
        }

        if (count($ast)>0){
            $res->result = "success";
            $res->data = $ast[0];
            if (isset($res->data->_id)){$res->data->name = $res->data->_id;unset($res->data->_id);}
            if (isset($res->data->supply)){$res->data->supply = @cdec($res->data->supply)->toString();}
            
            $res->data->holders_count = db_getAssetHoldersCountByKey($res->data->key); 

            if ($res->data->holders_count > 0){
                $res->data->holders = array();
                try {
                    $holders = db_getAssetHoldersByKey($res->data->key, $res->data->holders_count);
                    $key = $res->data->key;
                    foreach ($holders as $addr){
                        if (property_exists($addr->assets, $res->data->key)){
                            $res->data->holders[$addr->_id] = @cdec(($addr->assets->$key)->value)->toString();
                        }
                    }
                    
                } catch (Exception $e){
                    // header('Content-Type: text/plain');
                    //return "Error: " . $e->getMessage();  
                }
            }

        } else {
            header('Content-Type: application/json');
            $res->error = "asset not found";
            return json_encode($res);   
        }

        header('Content-Type: application/json');
        return json_encode($res); 

    }

    function apiGetAddressMessages($address, $since = -1){
        global $settings;
        $res = new stdClass();
        $res->result = "error";
        $res->timestamp = time();

        if ($since < 0 ){
            $since = time() - 86400; // by default last 24H
        }

        $am = db_getAssetMovesDataWithFilter(["address" => $address, "move_time_sec" => ['$gte'=>$since],"message" => ['$exists'=>true]], $limit = 10000, $skip = 0); 

        $res->result = "success";
        $data = array();

        foreach ($am as $move) {
            $msg = new stdClass();
            $msg->timestamp = $move->move_time_sec;
            $msg->tx = $move->tx_id;
            $msg->asset = $move->name;
            $msg->message = $move->message;

            array_push($data, $msg);
        }

        $res->data = $data;
        header('Content-Type: application/json');
        return json_encode($res); 

    }
    function apiGetAssetCount(){
        global $settings;
        $res = new stdClass();
        $res->result = "error";
        $res->timestamp = time();

        $lcs = db_getLastChainStats();
        $assetTypes = [];
        if (count($lcs)>0){
            $res->result = "success";
          $lcs = $lcs[0];
          if (isset($lcs->assetTypes) && is_object($lcs->assetTypes)){
            $assetTypes = (array)$lcs->assetTypes;
            ksort($assetTypes);
            $res->assets = $assetTypes;
          } else {
            $res->assets["non-admin"] = 0;
            $res->assets["total"] = 0;
          }
        }

        header('Content-Type: application/json');
        return json_encode($res); 

    }

  // --- TRANSACTION QUERIES ---
    function apiGetRawTransaction($txid, $decode=true){
        if ($decode) {
            $df = 1;
            header('Content-Type: application/json');
            return json_encode(rpc_getrawtransaction($txid,$df));
        } else{
            $df = 0; 
            header('Content-Type: text/plain');
            return rpc_getrawtransaction($txid,$df);
        } 
    }

    function apiSendRawTransaction($hex, $allowhighfees){
        header('Content-Type: application/json');
        $res = rpc_sendrawtransaction($hex,$allowhighfees);
        unset($res->id);
        return json_encode($res);

    }
  // --- BLOCKS QUERIES ---
    function apiGetBlockCount($legacy = false){
        global $settings;
        $bc = new stdClass();
        $bc->timestamp = time();
        // $dbbc = getLastDbBlockNumber();
        // if ($dbbc>0){
        //     $bc->blockcount = $dbbc;
        // } else {
            $rpcbc = getRpcBlockCount();
            $bc->blockcount = $rpcbc;
        // }
        if ($legacy){
            return $bc->blockcount;
        } else{
            return json_encode($bc); 
        }
    }
    function apiGetBlockHash($block_ix, $legacy = false){
        global $settings;
        $bi = new stdClass();
        $bi->timestamp = time();

        try{
            $bi->index = (int)$block_ix;
            $blk = db_getBlockByHeight($bi->index); 
        } catch (Exception $e){
            $blk = array();
        }
        
        //var_dump($blk);
        if (count($blk)>0){
            // block found in the DB
            $bi->hash = $blk[0]->_id;
            $bi->block_time = $blk[0]->block_time_sec;
        } else {
            // block not in DB, check daemon
            $blk = null;
            try {
              $blk = rpc_getblockhash($bi->index);  
            } catch (Exception $e) {}
            
            if (!is_null($blk)){
                $bi->hash = $blk;
            } else {
                header('Content-Type: text/plain');
                return "Error: Block Not Found";   
            }
        }
        if ($legacy){
            header('Content-Type: text/plain');
            return $bi->hash;
        } else{
            header('Content-Type: application/json');
            return json_encode($bi); 
        }
    }
    function apiGetBlock($hash){
        header('Content-Type: application/json');
        return json_encode(rpc_getblock($hash));
    }
    function apiGetBlockTemplate(){
        header('Content-Type: application/json');
        return json_encode(rpc_getblocktemplate());
    }
  // --- COINS QUERIES ---
    function apiGetCoinEmission($legacy = false){
        global $settings;
        $cs = new stdClass();
        $cs->timestamp = time();
        $dbcs = db_getDbCoinSupply();
        if (isset($dbcs->moneysupply)){
            $cs->coinemission = fpp($dbcs->coincirculation);
        } else {
            $rpccs = getRpcCoinSupply();
            $cs->coinemission = fpp($rpccs->moneysupply);
        }
        if ($legacy){
            header('Content-Type: text/plain');
            return fpp($cs->coinemission);
        } else{
            header('Content-Type: application/json');
            return json_encode($cs); 
        }
    }
    function apiGetCoinSupply($legacy = false){
        global $settings;
        $cs = new stdClass();
        $cs->timestamp = time();
        $dbcs = db_getDbCoinSupply();
        if (isset($dbcs->coincirculation)){
            $cs->coinsupply = fpp($dbcs->coincirculation);
        } else {
            $rpccs = getRpcCoinSupply();
            $cs->coinsupply = fpp($rpccs->moneysupply);
        }
        if ($legacy){
            header('Content-Type: text/plain');
            return fpp($cs->coinsupply);
        } else{
            header('Content-Type: application/json');
            return json_encode($cs); 
        }
    }
    function apiGetMoneySupply($decimals){
        global $settings;
        $cs = new stdClass();
        $cs->timestamp = time();
        $dbcs = db_getDbCoinSupply();
        if (is_null($decimals)){
            $decimals = 8;
        }
        if (isset($dbcs->coincirculation)){
            $cs->coinsupply = dflt($dbcs->coincirculation);
        } else {
            $rpccs = getRpcCoinSupply();
            $cs->coinsupply = rpp($rpccs->moneysupply);
        }
        return number_format($cs->coinsupply,$decimals,'.','');
    }

  // --- MASTERNODE QUERIES ---
    function apiGetMasternodeCount($legacy = false){
        $mc = new stdClass();
        $mc->timestamp = time();
        $cms = db_getLastChainMetric();
        if (count($cms)>0){
            // get from db
            $cm = $cms[0];
            $mc->timestamp = $cm->_id;
            $mc->masternodecount = $cm->metrics->masternode_count;
        } else {
            // get from daemon
            $mc->masternodecount=ui_getMasternodeCount();
        }
        if ($legacy){
            return json_encode($mc->masternodecount);
        } else {
            return json_encode($mc);
        }
    }

  // --- MARKETS QUERIES ---
    function apiGetMarketCapitalization(){

        global $settings;
        $mcap = new stdClass();
        (int)$mcap->timestamp = 0;
        $mcap->currencies = array();
        (float)$max_price = 0;
        (float)$avg_price = 0;
        $precision = $settings["default_precision"];
        if (array_key_exists("load_markets",$settings)){
            if ($settings["load_markets"]){
                try {

                    $cs = db_getDbCoinSupply();
                    $coin_circulation = @cdec($cs->coincirculation);
                    $db_mm = getLatestMarketsData();
                    $mcap->timestamp = $db_mm->timestamp;
                    $btc = cdec(0); $usd = cdec(0); $eur = cdec(0);
                    if(!empty($db_mm)){
                      $btc = @cdec($coin_circulation) * @cdec($db_mm->avg_price);
                      $mcap->currencies['BTC'] = $btc->toFixed($precision);
                      if (isset($db_mm->base_prices["USD"]) && !is_null($db_mm->base_prices["USD"])){
                        $usd = $btc * @cdec($db_mm->base_prices["USD"]);
                        $mcap->currencies['USD'] = $usd->toFixed(2);
                      }
                      if (isset($db_mm->base_prices["EUR"]) && !is_null($db_mm->base_prices["EUR"])){
                        $eur = $btc * @cdec($db_mm->base_prices["EUR"]);
                        $mcap->currencies['EUR'] = $eur->toFixed(2);
                      }
                    }
                    return json_encode($mcap);

                    // OLD PROCEDURE
                    // // get latest market metric
                    // $mm = db_getLastMarketsMetric();
                    // if (count($mm)>0){
                    //     $mm = $mm[0];
                    //     $mcap->timestamp = $mm->_id;
                    //     (float)$price_num = 0;
                    //     (float)$volume_base= 0;
                    //     if (count($mm->metrics)>0){
                    //       foreach($mm->metrics as $metric){
                    //           if (rpp($metric->latest) > rpp($max_price) && $metric->base == 'BTC'){
                    //               $max_price = rpp($metric->latest);
                    //           }
                    //           $price_num = round(round($price_num,16) + round(rpp($metric->latest)*rpp($metric->base_volume),16),16);
                    //           $volume_base = rpp(rpp($volume_base) + rpp($metric->base_volume));
                    //       }
                    //       try {
                    //         // calculate mean price
                    //         $avg_price = rpp($price_num/$volume_base);
                    //       } catch (Exception $e) {
                    //         $avg_price =  null;
                    //       }
                    //     }                          
                        
                    //     // get coin supply
                    //     $cs = db_getDbCoinSupply();

                    //     if ($settings["fiat_price"] == "indirect"){
                    //         // add capitalization in base currency
                    //         $cap = fpp($avg_price * dflt($cs->coincirculation));
                    //         $mcap->currencies['BTC'] = $cap;
                    //         // get latest base price
                    //         if (count((array)$mm->base_prices)>0){
                    //             foreach((array)$mm->base_prices as $base => $price){
                    //                 // cap(base) = base_price * avg_price * coin_supply
                    //                 $cap = fpp($price * $avg_price * dflt($cs->coincirculation));
                    //                 $mcap->currencies[$base] = $cap;
                    //             }
                    //         }
                    //         // return results
                    //         return json_encode($mcap);
                    //     } else if ($settings["fiat_price"] == "direct") {

                    //     } else {
                    //         // add capitalization in base currency
                    //         $cap = fpp($avg_price * dflt($cs->coincirculation));
                    //         $mcap->currencies['BTC'] = $cap;
                    //         return json_encode($mcap);
                    //     }
                    // }
                    // END OF OLD PROCEDURE
                    
                } catch (Exception $e) {
                    return json_encode("request failed");
                }
            } else {
                return json_encode("not supported");
            }
        } else {
            return json_encode("not supported");
        }
    }
    function apiGetMarketInfo(){
        $mi = getLatestMarketsData();

        if (count($mi->metrics) > 0){
            for ($i=0; $i <= count($mi->metrics)-1; $i++){
                $mm = $mi->metrics[$i];

                (!is_null($mm->latest)) ? $mm->latest = @cdec($mm->latest)->toString() : $mm->latest = null;
                (!is_null($mm->percent_change)) ? $mm->percent_change = @cdec($mm->percent_change)->toString() : $mm->percent_change = null;
                (!is_null($mm->base_volume)) ? $mm->base_volume = @cdec($mm->base_volume)->toString() : $mm->base_volume = null; 
                (!is_null($mm->quote_volume)) ? $mm->quote_volume = @cdec($mm->quote_volume)->toString() : $mm->quote_volume = null; 
                (!is_null($mm->lowest_ask)) ? $mm->lowest_ask = @cdec($mm->lowest_ask)->toString() : $mm->lowest_ask = null;
                (!is_null($mm->highest_bid)) ? $mm->highest_bid = @cdec($mm->highest_bid)->toString() : $mm->highest_bid = null;
                $mi->metrics[$i] = $mm;
            }
        }

        return json_encode($mi);
    }

  // --- MONITORING ---
    /**
     * The latest loader stop (ldr_abort) when no block has been loaded since,
     * i.e. the loader is still stopped; null otherwise.
     */
    function apiGetLoaderAbort(){
        try {
            $stop = db_getLastLoaderStop();
            if (is_null($stop) || !isset($stop->detected_at)){ return null; }
            $loaded = db_getLastBlockLoadTime();
            if (!is_null($loaded) && $loaded > (int)$stop->detected_at){ return null; }
            $abort = new stdClass();
            $abort->time        = (int)$stop->detected_at;
            $abort->time_tz     = isset($stop->detectiontime_tz) ? $stop->detectiontime_tz : null;
            $abort->height      = (isset($stop->block_ix) && $stop->block_ix >= 0) ? (int)$stop->block_ix : null;
            $abort->reason      = isset($stop->description) ? $stop->description : null;
            return $abort;
        } catch (Exception $e) {
            return null;
        }
    }
    function apiGetMonitoringSummary(){
        global $settings;
        $response = new stdClass();
        $response->timestamp = time();

        $hm = db_getLastHostMetric();

        if (count($hm) <= 0){
            $response->error = "No host metrics found";

            $response->report_timestamp = null;

            $response->cpu_load_percent = null;
            $response->observation_period_seconds = null;

            $response->memory_total_bytes = null;
            $response->memory_used_bytes = null;
            $response->memory_used_percent = null;

            $response->swap_total_bytes = null;
            $response->swap_used_bytes = null;
            $response->swap_used_percent = null;
            
            $response->disk_total_bytes = null;
            $response->disk_free_bytes = null;
            $response->disk_used_percent = null;

            $response->daemon_response_time_sec  = null;

            try{
                $response->daemon_last_block = rpc_getblockcount();
                $response->daemon_last_block_hash = rpc_getblockhash($response->daemon_last_block);
            } catch (Exception $e) {
                $response->daemon_last_block = null;
                $response->daemon_last_block_hash = null;
            }
            try{
                $response->daemon_connection_count = rpc_getconnectioncount();
            } catch (Exception $e) {
                $response->daemon_connection_count = null;
            }
            $response->daemon_info = getDaemonInfo();
            $response->database_last_block = db_getLastDbBlockNumber();
            $response->loader_abort = apiGetLoaderAbort();

            $response->database_data_size_bytes   = null;
            $response->database_disk_size_bytes   = null;

            if (isset($settings["subscription_enabled"]) && $settings["subscription_enabled"]) {
                $response->subscription_status = "ENABLED";
                $response->subscription_expires = $settings["subscription_endtime"];
                $exp_ts = strtotime($settings["subscription_endtime"]);
                (int)$tdif = ($exp_ts -time());
                $response->subscription_remains = $tdif;
              } else {
                $response->subscription_status = "DISABLED";
              }

            return json_encode($response);
        }
        $hm = $hm[0];
        $response->report_timestamp = $hm->_id;

        if (isset($hm->system->cpu_load) && !is_null($hm->system->cpu_load->idle)){
            $response->cpu_load_percent             = 100 - $hm->system->cpu_load->idle;
            $response->observation_period_seconds   = $hm->system->cpu_load->interval;
        } else {
            $response->cpu_load_percent = null;
            $response->observation_period_seconds = null;
        }        
       
        if (isset($hm->system->memory_usage) && !is_null($hm->system->memory_usage->total) && ($hm->system->memory_usage->total != 0)){
            $response->memory_total_bytes   = $hm->system->memory_usage->total;
            $response->memory_used_bytes    = $hm->system->memory_usage->used;
            $response->memory_used_percent  = 100*$hm->system->memory_usage->used/$hm->system->memory_usage->total;
        } else {
            $response->memory_total_bytes = null;
            $response->memory_used_bytes = null;
            $response->memory_used_percent = null;
        }
        if (isset($hm->system->swap_usage) && !is_null($hm->system->swap_usage->total) && ($hm->system->swap_usage->total != 0)){
            $response->swap_total_bytes   = $hm->system->swap_usage->total;
            $response->swap_used_bytes    = $hm->system->swap_usage->used;
            $response->swap_used_percent  = 100*$hm->system->swap_usage->used/$hm->system->swap_usage->total;
        } else {
            $response->swap_total_bytes = null;
            $response->swap_used_bytes = null;
            $response->swap_used_percent = null;
        }
        if (isset($hm->system->disk_usage) && !is_null($hm->system->disk_usage->total) && ($hm->system->disk_usage->total != 0)){
            $response->disk_total_bytes   = $hm->system->disk_usage->total;
            $response->disk_free_bytes    = $hm->system->disk_usage->free;
            $response->disk_used_percent  = 100 - (100*$hm->system->disk_usage->free/$hm->system->disk_usage->total);
        } else {
            $response->disk_total_bytes = null;
            $response->disk_free_bytes = null;
            $response->disk_used_percent = null;
        }
        if (isset($hm->daemon->daemon_response_time) && !is_null($hm->daemon->daemon_response_time)){
            $response->daemon_response_time_sec   = $hm->daemon->daemon_response_time;
        } else {
            $response->daemon_response_time_sec  = null;
        }
        try{
            $response->daemon_last_block = rpc_getblockcount();
            $response->daemon_last_block_hash = rpc_getblockhash($response->daemon_last_block);
        } catch (Exception $e) {
            $response->daemon_last_block = null;
            $response->daemon_last_block_hash = null;
        }
        try{
            $response->daemon_connection_count = rpc_getconnectioncount();
        } catch (Exception $e) {
            $response->daemon_connection_count = null;
        }
        $response->daemon_info = getDaemonInfo();
        $response->database_last_block = db_getLastDbBlockNumber();
        $response->loader_abort = apiGetLoaderAbort();
        global $version;
        $response->explorer_version=$version;

        $tsldb = getTimeSinceLastExplorerBlock();
        $response->time_since_last_db_block = new stdClass();
        $response->time_since_last_db_block->string = $tsldb->string;
        $response->time_since_last_db_block->diff = $tsldb->diff;

        $tslmb = getTimeSinceLastDaemonBlock();
        $response->time_since_last_daemon_block = new stdClass();
        $response->time_since_last_daemon_block->string = $tslmb->string;
        $response->time_since_last_daemon_block->diff = $tslmb->diff;

        if (isset($hm->database->db_size) && !is_null($hm->database->db_size->data)){
            $response->database_data_size_bytes   = $hm->database->db_size->data+$hm->database->db_size->index;
            $response->database_disk_size_bytes   = $hm->database->db_size->storage+$hm->database->db_size->index;
        } else {
            $response->database_data_size_bytes   = null;
            $response->database_disk_size_bytes   = null;
        }
        if (isset($settings["subscription_enabled"]) && $settings["subscription_enabled"]) {
            $response->subscription_status = "ENABLED";
            $response->subscription_expires = $settings["subscription_endtime"];
            $exp_ts = strtotime($settings["subscription_endtime"]);
            (int)$tdif = ($exp_ts -time());
            $response->subscription_remains = $tdif;
          } else {
            $response->subscription_status = "DISABLED";
          }
        return json_encode($response);
    }
  // --- NETWORK QUERIES ---
    function apiGetAddnodeList(){
        $res = '';
        $peers = getPeersInfo();
        foreach ($peers->outbound as $peer){
            $res = $res."addnode=".$peer->ip.":".$peer->port."\r\n"; // << CHANGED from PHP_EOL
        }
        return $res;
    }
    function apiGetNetworkHashPs($legacy = false){
        global $settings;
        $hr = new stdClass();
        $hr->timestamp = time();
        $nhr = getNetworkHashrate();
        $hr->hashrate_raw = $nhr->hashrate;
        $hr->hashrate_hr = $nhr->value;
        $hr->dimension = $nhr->dimension;
        if ($legacy){
            return $hr->hashrate_raw;
        } else{
            return json_encode($hr); 
        }
    }
    function apiGetDifficulty($legacy = false){
        $nd = new stdClass();
        $nd->timestamp = time();
        $ndif = getDifficulty();
        $nd->difficulty_raw = $ndif->difficulty;
        $nd->difficulty_hr = $ndif->value;
        $nd->dimension = $ndif->dimension;
        if ($legacy){
            header('Content-Type: text/plain');
            return $nd->difficulty_raw;
        } else{
            header('Content-Type: application/json');
            return json_encode($nd); 
        }
    }
    function apiGetConnectionCount($legacy = false){
        $cc = new stdClass();
        $cc->timestamp = time();
        $peers = getPeersInfo();
        $cc->total = count($peers->outbound)+count($peers->inbound);
        $cc->inbound = count($peers->inbound);
        $cc->outbound = count($peers->outbound);
        $cc->ipv4 = $peers->ipv4count;
        $cc->ipv6 = $peers->ipv6count;
        if ($legacy){
            header('Content-Type: text/plain');
            return $cc->total;
        } else{
            header('Content-Type: application/json');
            return json_encode($cc); 
        }
    }

  // --- RATE LIMITER ---
    function apiCheckApiRateLimitExceeded($ip_address){
        global $settings;
        if (isset($settings['api_unlimited_ip_addresses'])){
            try {
                if (in_array($ip_address,$settings['api_unlimited_ip_addresses'])){
                    return false;
                }
            } catch (Exception $e){}
        }
        if (isset($settings['api_max_requests_per_burst_period']) && $settings['api_max_requests_per_burst_period']>0){
            $rc = db_getApiCallsCountByIpAddress($ip_address);
            if ($rc >= $settings['api_max_requests_per_burst_period']) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    function apiCheckIpBlackListed($ip_address){
        global $settings;
        if (isset($settings['api_blacklisted_ip_addresses'])){
            try {
                if (in_array($ip_address,$settings['api_blacklisted_ip_addresses'])){
                    return true;
                }
            } catch (Exception $e){}
        }
        return false;
    }
    function apiRegisterCall($ip_address){
        global $settings;
        // log call

        // register in rate limiter
        if (isset($settings['api_max_requests_per_burst_period'])){
            if ($settings['api_max_requests_per_burst_period']>0){
                db_recordApiCall($ip_address);   
            }
        }
    }
  // --- LOGGING --- 
    function apiLogEvent($ip, $call, $duration, $args = array(), $result = null){
        $event = new stdClass();
        $event->timestamp = time();
        $event->ip = $ip;
        $event->call = $call;
        $event->duration = $duration;
        $event->args = log_args($args);
        $event->result = $result;

        db_recordApiLog($event);

    }
  // --- GENERAL ---
    function apiGetUserIpAddress(){
        return client_ip();
    }

    function apiCheckSubscriptionExpired(){
        // 402 Payment Required
        $expired = ui_getExpiredStatus();
        if ($expired == "complete"){
            header('Content-Type: text/plain');
            header($_SERVER["SERVER_PROTOCOL"]." 402 Payment Required", true, 402);
            echo "402 Payment Required";
            exit(0); 
        }
    }
?>