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

  $stime=milliseconds();

  $q = isset($_POST['q']) ? trim(sanitizeInput('q',"POST")) : '';
  $q = substr((string)$q, 0, 128);

  $result = array();
  $rnumber=0;
  $obj = "none";
  $value = "";
  if(!empty($q) || $q == "0") {
    // serach by block height
      if(is_numeric($q)){
        try {
          $blk_hei = db_getBlockByHeight((int)$q);
        } catch (Exception $ex){}
      } else {
          $blk_hei = array();
      }
      
      if(count($blk_hei)){
        $rnumber += count($blk_hei);
        //$res = array();
        foreach($blk_hei as $blk){
          $value = $blk->block_ix;
        }
        $obj="block_ix";
      }
    // search by block hash
    $blk_hash = db_getBlockByHash($q);
    if(count($blk_hash)){
      $rnumber += count($blk_hash);
      //$res = array();
      foreach($blk_hash as $blk){
        $value = $blk->_id;
      }
      $obj = "blockhash";
    }
    // search by txid
    $txs=db_getTxByHash($q);
    if(count($txs)){
      $rnumber += count($txs);
      //$res = array();
      foreach($txs as $tx){
        $value = $tx->_id;
      }
      $obj = "tx";
    } else {
      // check if it's unconfirmed tx
      $rtx = rpc_getrawtransaction($q,1,false);
      if (!empty($rtx)){
        $obj = "tx";
        $value = $q;
      }
    }
    // search by address
    $addr = db_getAddressData($q);
    if(count($addr)){
      $rnumber += count($addr);
      //$res = array();
      foreach($addr as $address){
        $value = $address->_id;
      }
      $obj ="address";
    }
    // search by address group
    $agr = db_getAddressGroupById($q);
    if(count($agr)){
      $rnumber += count($agr);
      //$res = array();
      foreach($agr as $group){
        $value = $group->_id;
      }
      $obj ="addressgroup";
    }
    // search by asset name
    $ast = db_getAssetByName($q, false);
    if (count($ast)){
      $rnumber++;
      $value = $ast[0]->key;
      $obj = "asset_id";
    }

    }
  if ($rnumber > 1) { $obj = "many"; $value = $q; }
  $ptime=milliseconds() - $stime;
   echo json_encode([
     "processingtime_ms"  => $ptime,
     "resultsNumber"      => $rnumber,
     "objectType"         => $obj,
     "objectValue"        => $value
     ]);
  ?>
  <?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/search",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>