<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    global $dbm;
    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["default_precision"];

    //$mn = (isset($_GET['mn'])) ? strtr($_GET['mn'],"_"," "):null;
    //$rid = (isset($_GET['rid'])) ? strtr($_GET['rid'],["_"=>""," "=>"","."=>"",","=>"","!"=>"","\\"=>"","'"=>"","\""=>"","{"=>"","}"=>"",":"=>"",";"=>""]) : "";
    $rid  = sanitizeInput("rid","GET");
    $mn = (isset($_GET['mn'])) ? strtr(sanitizeInput("mn","GET"),"_"," "):null;

    function calculateAllOnDemand($ref_timestamps,$min_timestamp,$max_timestamp,$granularity_sec){
        global $res;
        global $settings;
        
        // populate timestamps
        $res->blockStats_data       = $ref_timestamps;
        $res->blockTypes_data       = $ref_timestamps;
        $res->blockVersions_data   = $ref_timestamps;
        //$res->moveTypes_data        = $ref_timestamps;      
        $res->txTypes_data          = $ref_timestamps;
        $res->voutTypes_data        = $ref_timestamps;
        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
            $res->assetTypes_data   = $ref_timestamps;
        }

        $odres = ui_calculateChainStatsOnDemand($min_timestamp,$max_timestamp,$granularity_sec);

        // merge blockStats_data data
        foreach ($odres->blockStats_data as $ts=>$data){
            //$data["date"]=$ts*1000;
            $res->blockStats_data[$ts] = $data;
        }

        // merge asset types data
        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"] && isset($odres->assetTypes_data)){
            foreach ($odres->assetTypes_data as $ts=>$data){
                //$data["date"]=$ts*1000;
                $res->assetTypes_data[$ts] = $data;
            }
        }

        // merge block types data
        foreach ($odres->blockTypes_data as $ts=>$data){
            //$data["date"]=$ts*1000;
            $res->blockTypes_data[$ts] = $data;
        }

        // merge block versions data
        foreach ($odres->blockVersions_data as $ts=>$data){
            //$data["date"]=$ts*1000;
            $res->blockVersions_data[$ts] = $data;
        }

        // merge tx types data
        foreach ($odres->txTypes_data as $ts=>$data){
            //$data["date"]=$ts*1000;
            $res->txTypes_data[$ts] = $data;
        }

        // merge vout types data
        foreach ($odres->voutTypes_data as $ts=>$data){
            //$data["date"]=$ts*1000;
            $res->voutTypes_data[$ts] = $data;
        }

        // merge block types
          //    currently not in use

        // merge block versions
        $res->blockVersions = array_merge($res->blockVersions, $odres->blockVersions);

        // merge move types
        foreach($odres->moveTypes as $idx=>$move){
            if (!array_key_exists($idx,$res->moveTypes)){
                $res->moveTypes[$idx] = $move;
            }
        }

        // merge tx types
        $res->txTypes = array_merge($res->txTypes, $odres->txTypes);

        // merge vout types
        $res->voutTypes = array_merge($res->voutTypes, $odres->voutTypes);

        // merge asset types
        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
            $res->assetTypes = array_merge($res->assetTypes, $odres->assetTypes);
        }
    }

    $res = new stdClass();
    global $res;
    $res->blockStats_data = array();
    $res->blockTypes_data = array();
    $res->blockTypes = array();
    $res->blockVersions_data = array();
    $res->blockVersions = array();  
    //$res->moveTypes_data = array();
    $res->moveTypes = array();      
    $res->txTypes_data = array();
    $res->txTypes = array();
    $res->voutTypes_data = array();
    $res->voutTypes = array();
    if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
        $res->assetTypes_data = array();
        $res->assetTypes = array();
    }

    $is_valid_rid = db_validateRequestId($rid);
    //$is_valid_rid = true;

    if ($is_valid_rid || ui_checkMagicNumber($mn)){   
        $last_block = db_getLastBlocks(1,0);
        $genesis = db_getBlockByHeight(0);
        if (count($last_block)>0 && count($genesis)>0){
            $to_time = time();//1515654000;
            $granularity_sec = 86400;

            $min_timestamp = ($genesis[0]->block_time_sec - $genesis[0]->block_time_sec % $granularity_sec);
            $max_timestamp = ($to_time - $to_time % $granularity_sec) + $granularity_sec;
            $last_timestamp = $last_block[0]->block_time_sec - $last_block[0]->block_time_sec % $granularity_sec; 
            // create ref timestamps from genesis block time
            $ref_timestamps = array();

            // fill reference timestamps
            $apt->createTimer("fill_ref_timestamps",1);
            for ($ts = $min_timestamp; $ts < $max_timestamp; $ts += $granularity_sec){
                $ts_ms = $ts*1000;
                $ref_timestamps[$ts] = ["date" => $ts_ms];
            }
            ksort($ref_timestamps);
            $apt->stopTimer("fill_ref_timestamps");
            //$z = $apt->getDurationSeconds("fill_ref_timestamps");

            
            if (isset($settings["calculate_chain_stats"]) && $settings["calculate_chain_stats"]){
                // load from chainstats
                $apt->createTimer("db_getChainStatsMany",1);
                $chain_stats = db_getChainStatsMany($min_timestamp,$max_timestamp);
                $apt->stopTimer("db_getChainStatsMany");
                //$z = $apt->getDurationSeconds("db_getChainStatsMany");
                if (count($chain_stats)>0){
                    $apt->createTimer("process_chain_stats",1);
                    // populate timestamps
                    $res->blockStats_data       = $ref_timestamps;
                    $res->blockTypes_data       = $ref_timestamps;
                    $res->blockVersions_data   = $ref_timestamps;
                    //$res->moveTypes_data        = $ref_timestamps;      
                    $res->txTypes_data          = $ref_timestamps;
                    $res->voutTypes_data        = $ref_timestamps;
                    if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
                        $res->assetTypes_data   = $ref_timestamps;
                    }

                    // iterate through chain_stats
                    foreach ($chain_stats as $cs){

                        // process asset types
                        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"] && isset($cs->assetTypes)){
                            foreach((array)$cs->assetTypes as $type=>$n){
                                $res->assetTypes_data[$cs->_id][$type] = $n;
                                if( !in_array($type,["total","non-admin"]) && !in_array($type,$res->assetTypes)){array_push($res->assetTypes,$type);}
                            }
                            //unset($cs->assetTypes);
                        }

                        // process block types
                        if (isset($cs->blockTypes)) {
                            foreach((array)$cs->blockTypes as $type=>$n){
                                $res->blockTypes_data[$cs->_id][$type] = $n;
                            }
                            //unset($cs->blockTypes);
                        }

                        // process block versions
                        if (isset($cs->blockVersions)) {
                            foreach((array)$cs->blockVersions as $ver=>$n){
                                $res->blockVersions_data[$cs->_id][$ver] = $n;
                                if(!in_array($ver,$res->blockVersions)){array_push($res->blockVersions,$ver);}
                            }
                            //unset($cs->blkVersions);
                        }

                        // process tx types
                        if (isset($cs->txTypes)){
                            foreach ((array)$cs->txTypes as $type=>$n){
                                $res->txTypes_data[$cs->_id][$type] = $n;
                                if (!in_array($type,$res->txTypes)){
                                    array_push($res->txTypes, $type);
                                }
                                if (in_array($type,["new_asset","transfer_asset","reissue_asset"])){
                                    if (array_key_exists("asset",$res->txTypes_data[$cs->_id])){
                                        $res->txTypes_data[$cs->_id]["asset"] += $n;
                                    } else {
                                        $res->txTypes_data[$cs->_id]["asset"] = $n;
                                    }
                                    if (!in_array("asset",$res->txTypes)){
                                        array_push($res->txTypes, "asset");
                                    }
                                }
                            }
                            //unset($cs->txTypes);
                        }

                        // process vout types
                        if (isset($cs->voutTypes)){
                            foreach ((array)$cs->voutTypes as $type=>$n){
                                $res->voutTypes_data[$cs->_id][$type] = $n;
                                if(!in_array($type,$res->voutTypes)){array_push($res->voutTypes,$type);}
                            }
                            //unset($cs->voutTypes);
                        }

                        // movement types
                        if (isset($cs->movementsTotal)){
                            $move = @cdec($cs->movementsTotal);
                            
                            if ($move > 0){
                                if (!array_key_exists(99,$res->moveTypes)){ $res->moveTypes[99] = ["name"=>"Transfer", "field"=>"rtx"];}
                                if (isset($cs->powRewardsTotal)) {
                                    $pow = @cdec($cs->powRewardsTotal);
                                    if ($pow > 0) {
                                        if (!array_key_exists(0,$res->moveTypes)){ $res->moveTypes[0] = ["name"=>"PoW Rewards", "field"=>"pow"];}
                                        $res->blockStats_data[$cs->_id]["pow"] = $pow->toFixed(0);
                                    }
                                }
                                if (isset($cs->stakeRewardsTotal)) {
                                    $pos = @cdec($cs->stakeRewardsTotal);
                                    if ($pos > 0){
                                        if (!array_key_exists(1,$res->moveTypes)){ $res->moveTypes[1] = ["name"=>"PoS Rewards", "field"=>"pos"];}
                                        $res->blockStats_data[$cs->_id]["pos"] = $pos->toFixed(0);
                                    }
                                }
                                if (isset($cs->mnRewardsTotal)) {
                                    $mn = @cdec($cs->mnRewardsTotal);
                                    if ($mn > 0) {
                                        if (!array_key_exists(2,$res->moveTypes)){ $res->moveTypes[2] = ["name"=>"MN Rewards", "field"=>"mn"];}
                                        $res->blockStats_data[$cs->_id]["mn"] = $mn->toFixed(0);
                                    }
                                }
                                if (isset($cs->devRewardsTotal)) {
                                    $dev = @cdec($cs->devRewardsTotal);
                                    if ($dev > 0){
                                        if (!array_key_exists(3,$res->moveTypes)){ $res->moveTypes[3] = ["name"=>"Dev Rewards", "field"=>"dev"];}
                                        $res->blockStats_data[$cs->_id]["dev"] = $dev->toFixed(0);
                                    }
                                }
                                $res->blockStats_data[$cs->_id]["rtx"] = @cdec($cs->movementsRegularTotal)->toFixed(0);
                                if (isset($cs->feeTotal) && isset($cs->feeSubjectBytes) && $cs->feeSubjectBytes>0){
                                    $res->blockStats_data[$cs->_id]["fpkb"] = ((@cdec($cs->feeTotal))/@cdec($cs->feeSubjectBytes))->toFixed($precision);
                                }
                            }
                        }

                        // txps
                        if (isset($cs->txNumber)){
                            $mp = (isset($cs->periods)) ? $cs->periods *3600 : $granularity_sec;
                            $res->blockStats_data[$cs->_id]["txps"] = (@cdec($cs->txNumber)/$mp)->toFixed(3);
                            $rtxNumber = $cs->txNumber - ( (isset($cs->txTypes->reward)) ? $cs->txTypes->reward : 0 ) - ( (isset($cs->txTypes->nonstandard)) ? $cs->txTypes->nonstandard : 0 );
                            $res->blockStats_data[$cs->_id]["rtxps"] = (@cdec($rtxNumber)/$mp)->toFixed(3);
                        }
                        if (isset($cs->addressCount) && $cs->addressCount>=0) {$res->blockStats_data[$cs->_id]["acnt"] = $cs->addressCount;}
                        if (isset($cs->groupCount) && $cs->groupCount>=0) {$res->blockStats_data[$cs->_id]["gcnt"] = $cs->groupCount;}
                        if (isset($cs->blocksNumber) && isset($cs->blocksSizeTotal) && $cs->blocksNumber>0) {$res->blockStats_data[$cs->_id]["avgbs"] = (@cdec($cs->blocksSizeTotal)/@cdec($cs->blocksNumber))->toFixed(0);}

                        if (isset($cs->coincirculation)) {
                            $cs->coincirculation = @cdec($cs->coincirculation);
                            $res->blockStats_data[$cs->_id]["ccirc"] = $cs->coincirculation->toFixed(0);
                        }
                        // cdd & acdd
                        if (isset($cs->cdd)) {
                            $cdd = @cdec($cs->cdd); if ( $cdd->round(0) > 0 ){$res->blockStats_data[$cs->_id]["cdd"] = $cdd->toFixed(0);}
                            if (isset($cs->coincirculation) && $cs->coincirculation != 0){
                                $acdd = $cdd/$cs->coincirculation;
                                if ($acdd->round(3)>0){$res->blockStats_data[$cs->_id]["acdd"] = $acdd->toFixed(3);}
                            }
                        }

                        if (isset($cs->burned) ) {$res->blockStats_data[$cs->_id]["cburn"] = (@cdec($cs->burned))->toFixed(0);} 
                        if (isset($cs->coinsupply) ) {$res->blockStats_data[$cs->_id]["csupl"] = (@cdec($cs->coinsupply))->toFixed(0);} 

                        // stake 
                        if (isset($cs->stakeBaseTotal)) {$res->blockStats_data[$cs->_id]["sbsum"] = (@cdec($cs->stakeBaseTotal))->toString();} 
                        if (isset($cs->stakeRewardsTotal)) {$res->blockStats_data[$cs->_id]["srsum"] = (@cdec($cs->stakeRewardsTotal))->toString();} 
                        if (isset($cs->avgStakeBase)) {$res->blockTypes_data[$cs->_id]["avgsb"] = (@cdec($cs->avgStakeBase))->toString();} 
                        if (isset($cs->avgStakeReward)) {$res->blockTypes_data[$cs->_id]["avgsr"] = (@cdec($cs->avgStakeReward))->toString();} 

                        if (isset($cs->avgHashrate) && !is_null($cs->avgHashrate)) {$res->blockStats_data[$cs->_id]["powhr"] = @cdec($cs->avgHashrate)->toFixed(0);}
                        if (isset($cs->avgDifficulty) && !is_null($cs->avgDifficulty)) {$res->blockStats_data[$cs->_id]["powdif"] = @cdec($cs->avgDifficulty)->toFixed(3);}
                        $res->blockStats_data[$cs->_id]["mnttl"] = (isset($cs->avgMasternodeCount) && !is_null($cs->avgMasternodeCount)) ? @cdec($cs->avgMasternodeCount)->toInt() : 0;
                        $res->blockStats_data[$cs->_id]["mnena"] = (isset($cs->avgMasternodeCountEnabled) && !is_null($cs->avgMasternodeCountEnabled)) ? @cdec($cs->avgMasternodeCountEnabled)->toFixed(0) : 0;
                        if ($res->blockStats_data[$cs->_id]["mnttl"] != 0 && isset($res->blockStats_data[$cs->_id]["mn"])) {$res->blockStats_data[$cs->_id]["avgmnr"] = (@cdec($res->blockStats_data[$cs->_id]["mn"])/$res->blockStats_data[$cs->_id]["mnttl"])->toString();}
    
                        if (isset($cs->btcUsd)) {$res->blockStats_data[$cs->_id]["btc_usd"] = @cdec($cs->btcUsd)->toFixed(2);}
                        if (isset($cs->vwapBtc)) {$res->blockStats_data[$cs->_id]["vwap_btc"] = @cdec($cs->vwapBtc)->toFixed($precision);}
                        if (isset($cs->vwapUsd)) {$res->blockStats_data[$cs->_id]["vwap_usd"] = @cdec($cs->vwapUsd)->toFixed(4);}

                    }
                    $apt->stopTimer("process_chain_stats");

                    // check if data is complete
                    if ($chain_stats[count($chain_stats)-1]->_id != $max_timestamp-$granularity_sec){
                        // calculate remaining on-demand
                        $apt->createTimer("calculate_remaining_on-demand",1);
                        $odres = ui_calculateChainStatsOnDemand($chain_stats[count($chain_stats)-1]->_id+$granularity_sec,$max_timestamp,$granularity_sec);

                        // merge blockStats_data data
                        foreach ($odres->blockStats_data as $ts=>$data){
                            //$data["date"]=$ts*1000;
                            $res->blockStats_data[$ts] = $data;
                        }

                        // merge asset types data
                        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"] && isset($odres->assetTypes_data)){
                            foreach ($odres->assetTypes_data as $ts=>$data){
                                //$data["date"]=$ts*1000;
                                $res->assetTypes_data[$ts] = $data;
                            }
                        }

                        // merge block types data
                        foreach ($odres->blockTypes_data as $ts=>$data){
                            //$data["date"]=$ts*1000;
                            $res->blockTypes_data[$ts] = $data;
                        }

                        // merge block versions data
                        foreach ($odres->blockVersions_data as $ts=>$data){
                            //$data["date"]=$ts*1000;
                            $res->blockVersions_data[$ts] = $data;
                        }

                        // merge tx types data
                        foreach ($odres->txTypes_data as $ts=>$data){
                            //$data["date"]=$ts*1000;
                            $res->txTypes_data[$ts] = $data;
                        }

                        // merge vout types data
                        foreach ($odres->voutTypes_data as $ts=>$data){
                            //$data["date"]=$ts*1000;
                            $res->voutTypes_data[$ts] = $data;
                        }

                        // merge block types
                          //    currently not in use

                        // merge block versions
                        $res->blockVersions = array_merge($res->blockVersions, $odres->blockVersions);

                        // merge move types
                        foreach($odres->moveTypes as $idx=>$move){
                            if (!array_key_exists($idx,$res->moveTypes)){
                                $res->moveTypes[$idx] = $move;
                            }
                        }

                        // merge tx types
                        $res->txTypes = array_merge($res->txTypes, $odres->txTypes);

                        // merge vout types
                        $res->voutTypes = array_merge($res->voutTypes, $odres->voutTypes);

                        // merge asset types
                        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
                            $res->assetTypes = array_merge($res->assetTypes, $odres->assetTypes);
                        }
                        $apt->stopTimer("calculate_remaining_on-demand");
                    }    
                    
                    
                    
                } else {
                    // chain stats, need to calc on-demand
                    $apt->createTimer("no_chainstats_calculate_all_on-demand",1);
                    calculateAllOnDemand($ref_timestamps,$min_timestamp,$max_timestamp,$granularity_sec);
                    $apt->stopTimer("no_chainstats_calculate_all_on-demand");
                }
            } else {
                // calculate all on-demand
                $apt->createTimer("calculate_all_on-demand",1);
                calculateAllOnDemand($ref_timestamps,$min_timestamp,$max_timestamp,$granularity_sec);
                $apt->stopTimer("calculate_all_on-demand");

            }

            // arrange data
            $apt->createTimer("arrange_data",1);

            if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
                ksort($res->assetTypes_data); 
                $res->assetTypes_data   = array_values($res->assetTypes_data);
                
                $res->assetTypes        = array_unique($res->assetTypes);
                // SIP-048
                if (in_array('regular_admin',$res->assetTypes)){unset($res->assetTypes[array_search('regular_admin',$res->assetTypes)]);} 
                if (in_array('subasset_admin',$res->assetTypes)){unset($res->assetTypes[array_search('subasset_admin',$res->assetTypes)]);}
                // eof SIP-048                
                rsort($res->assetTypes);


            }
            ksort($res->blockStats_data);
            $res->blockStats_data       = array_values($res->blockStats_data);

            ksort($res->blockTypes_data);
            $res->blockTypes_data       = array_values($res->blockTypes_data);

            ksort($res->blockVersions_data);
            $res->blockVersions_data    = array_values($res->blockVersions_data);
            $res->blockVersions         = array_unique($res->blockVersions);
            sort($res->blockVersions);

            //$res->moveTypes = array_unique($res->moveTypes);
            ksort($res->moveTypes);
            $res->moveTypes             = array_values($res->moveTypes);

            ksort($res->txTypes_data);    
            $res->txTypes_data          = array_values($res->txTypes_data);
            $res->txTypes               = array_unique($res->txTypes);
            rsort($res->txTypes);

            ksort($res->voutTypes_data);
            $res->voutTypes_data        = array_values($res->voutTypes_data);
            $res->voutTypes             = array_unique($res->voutTypes);
            rsort($res->voutTypes);

            $apt->stopTimer("arrange_data");
            $z = $apt->getDurationSeconds("arrange_data");

        }
        $apt->stopTimer("total");
        //$z = $apt->getDurationSeconds("total");
        $res->pt = (@cdec($apt->getDurationMicroSeconds("total"))/1000000)->toFixed(6);
    } else {
        $apt->stopTimer("total");
        $res = new stdClass();
    }

    header('Content-Type: application/json');
    echo json_encode($res);

    ui_LogPageEvent($cip,"_dp/statschart",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
    //echo "\n".$z."\n";
?>