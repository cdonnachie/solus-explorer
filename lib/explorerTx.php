<?php 
include_once "loaderBlock.php";

use Decimal\Decimal;
use WebSocket\Exception;
    function ui_getUnprocessedTransaction($txid, $decode = 1){
        $txdata = rpc_getrawtransaction($txid, $decode);
        if (!is_object($txdata) || !isset($txdata->txid)){
            return null;
        }

        $block_ix = -1;
        $time = -1;
        $coinbase_vins = array();
        if (isset($txdata->height)){
            $block_ix = $txdata->height;
        }
        if (isset($txdata->time)){
            $time = $txdata->time;
        } else if (isset($txdata->locktime)){
            // locktime is actually a chain height
            $lock_hash = rpc_getblockhash($txdata->locktime);
            $lock = rpc_getblock($lock_hash);
            if (isset($lock->time)) {
                $time = $lock->time;
            }
        }   
        $block = new Block($block_ix, true);
        $tx = new Tx($txdata->txid, $block_ix, -1);
        $tx->readTx($block, true);

        // load vins data
        if (count($tx->vins)>0){
            foreach ($tx->vins as $vin){            
                if (isset($vin->ref_vout)){
                    $ref = explode("-",$vin->ref_vout);
                    if (count($ref)==2){
                        $vout = getRpcTxVout($ref[0],$ref[1]);
                        if (!is_null($vout)){

                           $tx->vins[$vin->_id]->address = $vout->address;
                           $tx->vins[$vin->_id]->type = $vout->type;
                           $tx->vins[$vin->_id]->amount = @cdec($vout->amount);
                           $tx->vins[$vin->_id]->time_sec = $vout->time_sec;

                           if ($vout->type == "coldstake"){
                               if (isset($vout->staker) && !empty($vout->staker)){$tx->vins[$vin->_id]->staker = $vout->staker;}
                           }
                           if (isset($vout->asset)){ 
                               $tx->vins[$vin->_id]->asset = $vout->asset; 
                               $tx->vins[$vin->_id]->asset->amount = @cdec($vout->asset->amount);
                           }
            
                           $tx->vins_amount = $tx->vins_amount+ @cdec($vin->amount);
                           // if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                           //     $asset_vin = $vout;
                           //     $tx->asset_vins[$key] = $asset_vin;
                           //     $this->txs[$vin->tx_id]->asset_vins[$key] = $asset_vin;
                           // }
                        }
                    }
                }
                // $tx->vins[$vin->_id] = $vin;
            }
        }


        // process movements
        foreach ($tx->vouts as $vout){
            $is_asset_move = false;
            ////// transfer_asset
            if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                $options = array();
                if (isset($vout->asset->message)) { $options["message"] = $vout->asset->message; }
                $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false,$options);
                $is_asset_move = true;
                if ($vout->type == "transfer_asset") {
                    if ((!isset($tx->type)) || ($tx->type != "new_asset" && $tx->type != "reissue_asset")){
                        $tx->type = "transfer_asset";
                    }
                } else {
                    $tx->type = $vout->type;
                }
            }
            // nullassetdata
            if ($vout->type == "nullassetdata"){
                if ($vout->address != "nullassetdata"){
                    $options = array();
                    if (isset($vout->asset->message)) { $options["message"] = $vout->asset->message; }
                    if (isset($vout->asset->qualifier_type)) { $options["qualifier_type"] = $vout->asset->qualifier_type; }
                    $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false, $options);
                    $is_asset_move = true;
                }
            }
            
 
            ////// new_asset -> create phantom Assetbase vin
            if ($vout->type == "new_asset" || $vout->type == "reissue_asset" ){
 
                if (isset($vout->asset->reissuable)) { $asset->reissuable =  $vout->asset->reissuable; }
                if (isset($vout->asset->units)) { $asset->units =  $vout->asset->units; }
                
                // create New Asset movement //
                $tx->moveAssetAmountTx(-$vout->asset->amount, $vout->asset->name, "New Asset", $vout->type, $tx->_id."-New_Asset-".$vout->asset->name, true);
                $is_asset_move = true;
            } 
 
            /////// Cold Stake contract
            if ($vout->type == "coldstake"){
                $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                        $vout->address,                     // 2  : address
                                        $tx->_id."-".$vout->address,        // 3  : key
                                        false,                              // 4  : is_reward
                                        false,                              // 5  : is_pow_reward
                                        false,                              // 6  : is_pos_reward
                                        false,                              // 7  : is_mn_reward
                                        false,                              // 8  : is_dev_reward
                                        false,                              // 9  : is_vin
                                        $is_asset_move,                     // 10 : is_asset_move
                                        null,                               // 11 : custom type
                                        $is_coldstake   = true,             // 12 : is_coldstake
                                        $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                    );
                $tx->type = "coldstake";
            } else {
                if ($vout->type == "shield") {
                    $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                            $vout->address,                     // 2  : address
                                            $tx->_id."-".$vout->address,        // 3  : key
                                            false,                              // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            false,                              // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            false,                              // 9  : is_vin
                                            false,                              // 10 : is_asset_move
                                            $vout->type                         // 11 : custom type
                                        );
                    // remove Shield vouts from list to be recorded
                    unset($tx->vouts[$vout->_id]);
                } else if ($vout->type == "creditpool_lock") {
                    $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                            $vout->address,                     // 2  : address
                                            $tx->_id."-".$vout->address,        // 3  : key
                                            false,                              // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            false,                              // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            false,                              // 9  : is_vin
                                            false,                              // 10 : is_asset_move
                                            $vout->type                         // 11 : custom type
                                        );
                    // remove Credit Pool vouts from list to be recorded
                    unset($tx->vouts[$vout->_id]);
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address,false,false,false,false,false,false,$is_asset_move); 
                }
            }
            
        }
        foreach ($tx->vins as $vin){
            if ($vin->type != "coinbase") {
                $is_asset_move = false;
    
                ////// transfer_asset
                if ($vin->type == "transfer_asset" || $vin->type == "new_asset" || $vin->type=="reissue_asset"){
                    $tx->moveAssetAmountTx(-$vin->asset->amount, $vin->asset->name, $vin->address, $vin->type, $tx->_id."-".$vin->address."-".$vin->asset->name, true);
                    $is_asset_move = true;
                }
                ///////
                if ($vin->type == "coldstake"){
                    $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                            $vin->address,                      // 2  : address
                                            $tx->_id."-".$vin->address,         // 3  : key
                                            false,                              // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            false,                              // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            true,                               // 9  : is_vin
                                            $is_asset_move,                     // 10 : is_asset_move
                                            null,                               // 11 : custom type
                                            $is_coldstake   = true,             // 12 : is_coldstake
                                            $staker         = (isset($vin->staker)) ? $vin->staker : null      // 13 : staker (cold stake)
                                        );
                } else {
                    if ($vin->type == "shield"){
                        $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                                $vin->address,                      // 2  : address
                                                $tx->_id."-".$vin->address,         // 3  : key
                                                false,                              // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                false,                              // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                true,                               // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                $vin->type                          // 11 : custom type
                                            );
                    } else if ($vin->type == "creditpool_unlock"){
                        $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                                $vin->address,                      // 2  : address
                                                $tx->_id."-".$vin->address,         // 3  : key
                                                false,                              // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                false,                              // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                true,                               // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                $vin->type                          // 11 : custom type
                                            );
                    } else {
                        $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address,false,false,false,false,false,true,$is_asset_move); 
                    }
                }

            } else {
                array_push($coinbase_vins, $vin);
            }
        }
        if (count($coinbase_vins) == 1){
            $vin = $coinbase_vins[0];
            $subsidy =  @cdec($tx->vouts_amount) - @cdec($tx->vins_amount);
            $vin->amount = $subsidy;
            $tx->vins_amount += $subsidy;
            $tx->moveAmountTx(      
                -$vin->amount,                      // 1  : amount
                $vin->address,                      // 2  : address
                $tx->_id."-".$vin->address,         // 3  : key
                false,                               // 4  : is_reward
                false,                              // 5  : is_pow_reward
                false,                              // 6  : is_pos_reward
                false,                              // 7  : is_mn_reward
                false,                              // 8  : is_dev_reward
                true,                               // 9  : is_vin
                false,                              // 10 : is_asset_move
                null,                               // 11 : custom type
                false,                              // 12 : is_coldstake
                null                                // 13 : staker (cold stake)
            );
            $tx->vins[$vin->_id] = $vin;
        } else if (count($coinbase_vins)>1){
            // wtf
            exit;
        }
        if (isset($tx->type) && $tx->type === 9 && isset($tx->assetUnlockTx)){
            $tx->fee_amount = @cdec($tx->assetUnlockTx->fee);
            $tx->vins_amount = $tx->vouts_amount - $tx->fee_amount;
            $tx->moveAmountTx(      -($tx->vouts_amount + $tx->fee_amount),                      // 1  : amount
                                    "Credit Pool",                      // 2  : address
                                    $tx->_id."-".$vin->address,         // 3  : key
                                    false,                              // 4  : is_reward
                                    false,                              // 5  : is_pow_reward
                                    false,                              // 6  : is_pos_reward
                                    false,                              // 7  : is_mn_reward
                                    false,                              // 8  : is_dev_reward
                                    true,                               // 9  : is_vin
                                    false,                              // 10 : is_asset_move
                                    "assetUnlockTx"                     // 11 : custom type
                                );
        }
        // calculate tx fee
        $tx->fee_amount = $tx->vins_amount - @cdec($tx->vouts_amount);  // Decimal removed fpp

        // detect illegal emission
        if ( $tx->fee_amount < 0) {
            // set fee to zero
                $tx->fee_amount = 0;

            // calculate illegal emission
                $ie = $tx->vouts_amount - $tx->vins_amount;

            // create illegal emission vin
                $tx->moveAmountTx(-$ie,"Illegal Supply", $tx->_id."-"."Illegal_Supply",false,false,false,false,false,true,false);
            // mark tx suspect
                $tx->suspect = true;

            // create problem_log record
            /// ATT: problem lvl 0
        }

        // calculate tx move
        foreach($tx->movements as $move){

            if (isset($tx->suspect) && $tx->suspect){
                if (is_null($move->address)){
                    $move->address = "{Missing VIN}";
                    $move->suspect = true;
                } else if($move->address == "Illegal Supply") {
                    $move->suspect = true;
                }

            }
            if ($move->amount > 0){ 
                $tx->move_amount = @cdec($tx->move_amount) + @cdec($move->amount); 
                // mark illegal supply suspect
                if (isset($tx->suspect) && $tx->suspect){
                    $move->suspect = true;
                }
            } 
        }

        return $tx;

    }
    function getRpcTxVout($tx_id, $vout_ix){
       $tx = rpc_getrawtransaction($tx_id, 1, false);

       if (isset($tx->vout) && count($tx->vout)>=$vout_ix+1){
           $vout = new Vout($tx->txid, $vout_ix, isset($tx->height) ? $tx->height : -1, isset($tx->time) ? $tx->time :-1);
           $vout->readVout($tx->vout[$vout_ix]);

           return $vout;
       } else {
           return null;
       }

    }
?>