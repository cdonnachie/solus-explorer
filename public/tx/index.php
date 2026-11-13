<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;  
  include_once __DIR__ . '/../../lib/explorerDb.php';
  include_once __DIR__ . '/../../lib/explorerUi.php';
  include_once __DIR__ . '/../../lib/explorerRpc.php';
  include_once __DIR__ . '/../../lib/timeTracker.php';
  include_once __DIR__ . '/../../lib/explorerTx.php';
  $apt = new TimeTracker;
  $tt = new TimeTracker;
  $apt->createTimer("total",0);
  $tt->createTimer("pageTotal",0);
  $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];
  $processed = false;

  global $csp_nonce;
  $csp_nonce = random_hex(8);
  
  $txid = sanitizeInput('txid',"GET");
  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

  global $description;
  $description = ui_getCoinName()." transaction ".$txid.": inputs, outputs and more.";
  global $keywords;
  $keywords = $settings["title"].", transaction, ".$txid.", movements, fees, addresses, confirmations, block"; 
  global $title;
  $title = ui_getCoinName()." transaction ".$txid." | Inputs and Outputs";

  if(!empty($txid)){ 
    $notx = false; 
    $form_invis = ""; 
    $notfound_visibility = "d-none";
    $emptyrequest_visibility = "d-none";
    $tt->createTimer("db_getTxByHash",1);
    $txs = ctype_xdigit($txid) ? db_getTxByHash($txid) : [];
    $tt->stopTimer("db_getTxByHash");
    if (count($txs)>0){
      $tr = $txs[0];
      $processed = true;
    } else {
      //tx not found in DB, could be in unprocessed bloeck or in mempool
      $tt->createTimer("ui_getUnprocessedTransaction",1);
      $tr = ctype_xdigit($txid) ? ui_getUnprocessedTransaction($txid,1) : null;
      $tt->stopTimer("ui_getUnprocessedTransaction");
      if (isset($tr->_id) && isset($tr->vins) && isset($tr->vouts)) {
        // we have unprocessed tx        
      } else {
        $form_invis = "d-none"; 
        $notfound_visibility = "";
        $emptyrequest_visibility = "d-none";
        $tr = new stdClass();
        ui_markNotIndexable(404);   // unknown tx: not content, keep it unindexed
      }
    }
  } else { 
    $notx = true; 
    $form_invis = "d-none"; 
    $notfound_visibility = "d-none";
    $emptyrequest_visibility = "";
    $tr = new stdClass();
    ui_markNotIndexable(404);   // no txid supplied
  }

  // Synthetic shadow-ledger payouts hold no position in their anchor block's
  // transaction list, so tx_ix is -1 and there is no raw serialisation to
  // fetch. Label the position instead of printing the sentinel, and drop the
  // links that would resolve to nothing.
  $is_synthetic = (isset($tr->synthetic) && $tr->synthetic);
  $tx_position  = $is_synthetic ? "virtual" : (isset($tr->tx_ix) ? $tr->tx_ix : "");

  if ($form_invis == ""){
    $tt->createTimer("getRpcBlockCount",1);
    $rpc_bc = getRpcBlockCount();
    $tt->stopTimer("getRpcBlockCount");
    $t_size = normalizeSi($tr->tx_size);
    $t_height = $tr->block_ix;
    $t_vin = splitFloat($tr->vins_amount, $precision, $precision);
    $t_vout = splitFloat($tr->vouts_amount, $precision, $precision);
    $t_move = splitFloat($tr->move_amount, $precision, $precision);
    $t_fees = splitFloat($tr->fee_amount, $precision, $precision);
    $conf = $rpc_bc - $t_height + 1;
    if ($conf <= 0) {
      $conf_class="danger";
    // } else if ($conf < 0){
    //   $conf_class="danger";
    //   $conf = "N/A";
    } else if ($conf >= (int)$settings["maturity"]) {
      $conf_class="success";
    } else {
      $conf_class="warning";
    }
  }

  // V2 process movements to vins/vouts
  $vins   = array();
  $vouts  = array();
  //$movements = array();

  if (isset($settings["storage_improvement"]) && $settings["storage_improvement"] && $processed) {
    // load movements
    $tt->createTimer("db_getAddressMovesDataWithFilter",1);
    $tr->movements = db_getAddressMovesDataWithFilter(["tx_id" => $tr->_id],0,0);
    $tt->stopTimer("db_getAddressMovesDataWithFilter");
  }

  $is_shield_tx = false;
  $tt->createTimer("processMovements",1);
  if (!empty($tr->movements)){
    foreach ($tr->movements as $move){
      if ($move->vins_count > 0){
        $vin = new stdClass();
        $vin->address = $move->address;
        $vin->amount = $move->vins_amount;
        $vin->count = $move->vins_count;
        if (isset($move->type)) {
          if ($move->type == "shield"){$is_shield_tx = true;}
          if ($move->type == "airdrop" && isset($move->staker) && !empty($move->staker)){$vin->owner = $move->staker;}
          $vin->type = $move->type;
        }
        if (isset($move->suspect)) {$vin->suspect = $move->suspect;}
        if (isset($move->is_coldstake)) {$vin->is_coldstake = $move->is_coldstake; $vin->staker = $move->staker;}
        $vin->is_asset_move = (isset($move->is_asset_move)) ?  $move->is_asset_move : false;
        array_push($vins, $vin);
      }

      if ($move->vouts_count > 0){
        $vout = new stdClass();
        $vout->address = $move->address;
        $vout->amount = $move->vouts_amount;
        $vout->count = $move->vouts_count;
        $vout->is_reward = $move->is_reward;
        $vout->is_pow_reward = $move->is_pow_reward;
        $vout->is_pos_reward = $move->is_pos_reward;
        $vout->is_mn_reward = $move->is_mn_reward;
        $vout->is_dev_reward = $move->is_dev_reward;
        if (isset($move->suspect)) {$vout->suspect = $move->suspect;}
        if (isset($move->type)) {
          if ($move->type == "shield"){$is_shield_tx = true;}
          $vout->type = $move->type;
        }
        if (isset($move->is_coldstake)) {$vout->is_coldstake = $move->is_coldstake; $vout->staker = $move->staker;}
        $vout->is_asset_move = (isset($move->is_asset_move)) ?  $move->is_asset_move : false;
        array_push($vouts, $vout);
      }
    }
    if ($is_shield_tx){
      $notes_in  = (isset($tr->sins_count)) ? $tr->sins_count : 0;
      $notes_out = (isset($tr->souts_count)) ? $tr->souts_count : 0;
      //$smove = @cdec(0);
      $smove = (isset($tr->smove_amount)) ? @cdec($tr->smove_amount) : @cdec(0) ;
      //$smove_sign = ($smove < 0) ? "-" : "";
      $smove = splitFloat($smove, $precision, $precision);
      
      //$smove->whole = $smove_sign.$smove->whole; 
    }
  } else {
    // check tx is genesis
  }
  $tt->stopTimer("processMovements",'move_count = '.count($tr->movements ?? []));
  $a_vins   = array();
  $a_vouts  = array();
  $a_messages = array();
  //$asset_movements = array();
  if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]  && $processed) {
    // load movements
    $tt->createTimer("db_getAssetMovesDataWithFilter",1);
    $tr->asset_movements = db_getAssetMovesDataWithFilter(["tx_id" => $tr->_id],0,0);
    $tt->stopTimer("db_getAssetMovesDataWithFilter");
  }

  $asset_vis = "d-none";
  if (isset($tr->asset_movements) && count($tr->asset_movements)>0){
    $tt->createTimer("processAssetMovements",1);
    $keys = array();
    $asset_vis = "";
    $tt->createTimer("db_getAssetByName",1);
    foreach ($tr->asset_movements as $aam){
      if (array_key_exists($aam->name, $keys)){
        $key = $keys[$aam->name];
      } else {
        $tt->startTimer("db_getAssetByName");
        $tt->continueTimer("db_getAssetByName");
        $ast = db_getAssetByName($aam->name);
        $tt->stopTimer("db_getAssetByName");
        if (count($ast)>0){
          $key = $ast[0]->key;
          $keys[$aam->name] = $key;
        } else {
          $key = "";
        }
      }
      if ($aam->vins_count > 0){
        $avin = new stdClass();
        $avin->address = $aam->address;
        $avin->name = $aam->name;
        $avin->key = $key;
        $avin->amount = $aam->vins_amount;
        $avin->count = $aam->vins_count;
        if (isset($aam->suspect)) {$avin->suspect = $aam->suspect;}
        array_push($a_vins, $avin);
      }
      if ($aam->vouts_count > 0){
        $avout = new stdClass();
        $avout->address = $aam->address;
        $avout->name = $aam->name;
        $avout->key = $key;
        $avout->amount = @cdec($aam->vouts_amount);
        $avout->count = $aam->vouts_count;
        if (isset($aam->suspect)) {$avout->suspect = $aam->suspect;}
        if (isset($aam->message)) {$avout->message = $aam->message;$a_messages[$aam->address]=$aam->message; }
        if (isset($aam->qualifier_type)) {$avout->qualifier_type = $aam->qualifier_type; }
        array_push($a_vouts, $avout);
      }
    }
    $tt->stopTimer("processAssetMovements",'asset_move_count = '.count($tr->asset_movements));
  }

  include_once __DIR__ . '/../includes/header.php';
?>
  <!--Main layout-->
  <main>
    <div id="content" class="container-fluid">
<?php if ($form_invis == "") : ?>
<!--Section: Button icon-->
<section class="<?php echo $form_invis;?>">
  <!--Grid row-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-12 mb-4">
      <!--Address Card (small)-->
      <div class="card col-12 d-md-none" style="min-height:63px">
        <!--Card Data-->
        <div class="text-center">
          <div class="col p-2">
            <div class="row align-items-center justify-content-center">
              <div class="col-auto px-1">
                <h1 class="h4 h4-responsive m-0"><?php echo "Transaction #".$tr->block_ix.":".$tx_position; ?></h1>
              </div>
              <?php if ($is_synthetic) : ?>
              <div class="col-auto px-1">
                    <span class='badge badge-info ml-2 px-1'
                      title='Synthetic shadow-ledger payout: no raw serialisation exists'>virtual</span>
              </div>
              <?php else : ?>
              <div class="col-auto px-1">
                    <a class='btn badge badge-primary ml-2 px-1'
                      href='<?php echo $settings["explorer_address"];?>/tx/raw/?txid=<?php echo $tr->_id;?>'
                      title='Show raw transaction'>{...}</a>
              </div>
              <?php endif; ?>
            </div>
            <div class="row align-items-center justify-content-center">
              <div class="col-auto px-1">
                <small class='grey-text'><?php echo $tr->_id;?></small>
              </div>
            </div>
          </div>
        </div>
        <!--/.Card Data-->
      </div>
      <!--/.Address Card (small) Card-->

      <!--Address Header Card (big)-->
      <div class="card col-12 d-none d-md-flex" style="min-height:86px">
        <!--Card Data-->
        <div class="col-12">
          <div class="row align-items-center text-left">
            <div class="col">
              <div class="row">
                <div class="pt-1 pl-2 ">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0 mr-2">
                      <i class="fas fa-exchange-alt card-icon-text" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="p-3 ">
                  <span class="h2 h2-responsive mb-0 font-weight-bold primary-text">
                    <div class="row align-items-center">
                        <div class="col-auto pr-0"><h1 class="h5 h5-responsive m-0"><?php echo "Transaction #".$tr->block_ix.":".$tx_position; ?></h1></div>
                        <?php if ($is_synthetic) : ?>
                        <div class="col-auto px-2"><span class='badge badge-info ml-2 px-1'
                          title='Synthetic shadow-ledger payout: no raw serialisation exists'>virtual</span>
                        </div>
                        <?php else : ?>
                        <div class="col-auto px-2"><a class='btn badge badge-primary ml-2 px-1'
                          href='<?php echo $settings["explorer_address"];?>/tx/raw/?txid=<?php echo $tr->_id;?>'
                          title='Show raw transaction'>{...}</a>
                        </div>
                        <?php endif; ?>
                    </div>  
                    <div class="row">
                        <div class="col-auto"><small class='grey-text'><?php echo $tr->_id;?></small></div>
                    </div>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
        <!--/.Card Data-->
      </div>
      <!--/.Address Header Card (big)-->
    </div>
    <!--/.Grid column I-->
  </div>
  <!--Grid row-->

  <!--Grid row-->
  <div class="row">
      <!--Grid column I-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
        <!--Params I Card-->
        <div class="card table-responsive text-nowrap p-3 mb-4">
            <!--Card Data-->
            <div class="table-container">
              <table class="table table-stripped table-hover table-borderless table-sm" cellspacing="0" width="100%">
                <tr>
                  <td class="primary-text p-1">Timestamp</td>
                  <?php if ($tr->tx_time_sec > 0) : ?>
                    <td class="font-weight-bold text-right p-1 "><?php echo date('Y-m-d H:i:s (e)', $tr->tx_time_sec);?></td>
                  <?php else : ?>
                    <td class="font-weight-bold text-right p-1 ">NOT AVAILABLE</td>
                  <?php endif; ?>
                </tr>
                <tr>
                  <td class="primary-text p-1">Block Height</td>
                  <?php if ($tr->block_ix > 0) : ?>
                  <td class="font-weight-bold text-right p-1"><a href='<?php echo $settings["explorer_address"]; ?>/block/?blockheight=<?php echo $tr->block_ix; ?>'><?php echo $tr->block_ix; ?></a></td>
                  <?php else : ?>
                  <td class="font-weight-bold text-right text-danger p-1 text-uppercase">NOT IN BLOCK</td>
                  <?php endif; ?>
                </tr>              
                <tr>
                  <td class="primary-text p-1">Confirmations</td>
                  <?php if ($tr->block_ix > 0) : ?>
                  <td class="font-weight-bold text-right p-1"><span class=" text-<?php echo $conf_class; ?>"><?php echo $conf; ?></span></td>
                  <?php else : ?>
                  <td class="font-weight-bold text-right text-danger p-1 text-uppercase ">UNCONFIRMED</td>
                  <?php endif; ?>
                </tr>
                <tr>
                  <td class="primary-text p-1">Size</td>
                  <?php if ($is_synthetic) : ?>
                  <td class="font-weight-bold text-right p-1"><span class="grey-text" title="Synthetic shadow-ledger payout: never serialised into a block">&ndash;</span></td>
                  <?php else : ?>
                  <td class="font-weight-bold text-right p-1"><?php echo fpp($t_size->value,3) . " " . $t_size->dimension . "Bytes"; ?></td>
                  <?php endif; ?>
                </tr>
                <tr>
                  <td class="primary-text p-1">VINs Count</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $tr->vins_count; ?></td>
                </tr>
                <tr>
                  <td class="primary-text p-1">VOUTs Count</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $tr->vouts_count; ?></td>
                </tr>
              </table>
            </div>
            <!--/.Card Data-->

        </div>
        <!--/.Params I Card-->
      </div>
      <!--/.Grid column I-->

      <!--Grid column II-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
        <!--Params II Card-->
        <div class="card table-responsive text-nowrap p-3 mb-4">
            <!--Card Data-->
            <div class="table-container">
              <table class="table table-stripped table-hover table-borderless table-sm" cellspacing="0" width="100%">
                <tr>
                  <td class="primary-text p-1">Inputs Amount</td>
                  <td class="font-weight-bold text-right p-1 "><?php echo $t_vin->whole."."; ?><small class="font-weight-bold"><?php echo $t_vin->fraction; ?></small></td>
                </tr>
                <tr>
                  <td class="primary-text p-1">Outputs Amount</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $t_vout->whole."."; ?><small class="font-weight-bold"><?php echo $t_vout->fraction; ?></small></td>
                </tr>
                <tr>
                  <td class="primary-text p-1">Movements Amount</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $t_move->whole."."; ?><small class="font-weight-bold"><?php echo $t_move->fraction; ?></small></td>
                </tr>
                <tr>
                  <td class="primary-text p-1">Fees Amount</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $t_fees->whole."."; ?><small class="font-weight-bold"><?php echo $t_fees->fraction; ?></small></td>
                </tr>

                <?php if ($is_shield_tx): ?>
                  <tr>
                  <td class="primary-text p-1">Shield Notes Consumed</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $notes_in; ?></td>
                </tr>
                <tr>
                  <td class="primary-text p-1">Shield Notes Created</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $notes_out; ?></td>
                </tr>
                <tr>
                  <td class="primary-text p-1">Shield Circulation Change</td>
                  <td class="font-weight-bold text-right p-1"><?php echo $smove_sign.$smove->whole."."; ?><small class="font-weight-bold"><?php echo $smove->fraction; ?></small></td>
                </tr>
                <?php endif; ?>
              </table>
            </div>
            <!--/.Card Data-->

        </div>
        <!--/.Params II Card-->

      </div>
      <!--/.Grid column II-->
  </div>
  <!--Grid row-->    
</section>
<!--/Section: Button icon-->


<!--Section: VIN/VOUT DataTables-->
<section class="<?php echo $form_invis;?>">
<?php 
$tt->createTimer("processVinsAndVouts",1);
$tt->createTimer("tx.db_getAddressTags",2);
if (count($vins)>0 && count($vouts)>0) : ?>
  <div class="row">
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
      <!--Inputs List Card-->
      <div class="card w-100 mb-4">
        <div class="card-header card-header danger-color white-text py-2">
        <span class="h4 h4-responsive"><i class="fas fa-sign-out-alt" aria-hidden="true"></i> Inputs </span>
        </div>

        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <div class="table-container">
            <table class="table table-hover table-bordered table-sm" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                foreach ($vins as $vin){
                  $ia_split = splitFloat($vin->amount, $precision, $precision);

                  $reward_tag = "";
                  $pre_str = "";
                  // asset tag
                  if (isset($vin->is_asset_move) && $vin->is_asset_move ) {
                    $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='Asset Movement'><i class='fas fa-box-open'></i></span>&nbsp;";
                  }
                  // group tags & address alias
                  $tt->continueTimer("tx.db_getAddressTags");
                  $at = db_getAddressTags($vin->address);
                  $tt->stopTimer("tx.db_getAddressTags");
                  $tag_str = "";
                  if (isset($vin->suspect) && $vin->suspect) {
                    $tag_str .= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movements'></i>&nbsp;";
                  }
                  $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                  if (isset($vin->type)) {
                    if ($vin->type == "shield"){
                      $sym = (isset($settings["shield_symbol"])) ? $settings["shield_symbol"] : "fas fa-shield-alt";
                      $pre_str .= "<span class='text-info ml-1 mb-1 p-1' title='Shielded Input'><i class='".$sym."'></i></span>&nbsp;";
                    } 
                    if ($vin->type == "airdrop"){
                      $sym = (isset($settings["airdrop_symbol"])) ? $settings["airdrop_symbol"] : "fas fa-hand-holding-water";
                      $reward_tag .= "<span class='text-info ml-1 mb-1 p-1' title='Airdrop Input'><i class='".$sym."'></i></span>&nbsp;";
                    }
                  }
                  $cs_staker = "";
                  $cs_owner = "";
                  if (isset($vin->staker) && !empty($vin->staker)){
                    $cs_staker = "<br><span class='grey-text cs-address' title='Staker Address'>".$vin->staker."</span>";
                    $cs_owner = "title='Owner Address'";
                  }
                  if (isset($vin->owner) && !empty($vin->owner)){
                    $cs_staker = "<br><span class='grey-text cs-address' title='Airdrop Owner'>".$vin->owner."</span>";
                  }
                  echo "<tr><td class='text-left p-1'><a class='primary-text' href='". $settings["explorer_address"]."/address/?address=".$vin->address."' ".$cs_owner.">".$pre_str.$vin->address."</a>".$reward_tag.$cs_staker.$tag_str."</td>";
                  echo "<td class='text-right p-1'><span class='text-danger'>". $ia_split->whole.".<small>".$ia_split->fraction."</small></span></td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
        <!--/.Card content-->

      </div>
      <!--/.Inputs List Card-->
    </div>  
    
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
      <!--Outputs List Card-->
      <div class="card w-100 mb-4">
        <div class="card-header card-header success-color white-text py-2">
        <span class="h4 h4-responsive"><i class="fas fa-sign-in-alt" aria-hidden="true"></i> Outputs </span>
        </div>

        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <div class="table-container">
            <table class="table table-hover table-bordered table-sm " cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Amount</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                
                foreach ($vouts as $vout){
                  $oa_split = splitFloat($vout->amount, $precision, $precision);
                  // reward tags
                  $reward_tag = "";
                  $pre_str = "";
                  if ($vout->is_reward && $vout->address != 'nonstandard' && $vout->address != 'nulldata'){
                    if ($vout->is_pow_reward){
                      $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='PoW Reward'><i class='fas icons-pike'></i></span>&nbsp;";
                    } 
                    if ($vout->is_pos_reward) {
                      $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='PoS Reward'><i class='fas fa-tint'></i></span>&nbsp;";
                      if (isset($vout->is_coldstake) && $vout->is_coldstake) {
                        $reward_tag = $reward_tag. "<span class='text-info mb-1 p-1' title='Coldstake Reward'><i class='".$settings["coldstake_symbol"]."'></i></span>&nbsp;";
                      } 
                    } 
                    if ($vout->is_mn_reward) {
                      $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='MN Reward'><i class='fab fa-hubspot'></i></span>&nbsp;";
                    } 
                    if ($vout->is_dev_reward) {
                      if (isset($settings["dev_symbol"])){
                        // custom dev_symbol
                        $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='Developer Fee'><i class='".$settings["dev_symbol"]."'></i></span>&nbsp;";
                      } else {
                        $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='Developer Fee'><i class='fas fa-cog'></i></span>&nbsp;";
                      }
                      
                    }
                    if (isset($vout->type)){
                      // custom vout type
                      if ($vout->type == "spow" && isset($settings["spow_symbol"])){
                        $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='SPoW Reward'><i class='".$settings["spow_symbol"]."'></i></span>&nbsp;";
                      } else if ($vout->type == "mn_ops" && isset($settings["mnops_symbol"])){
                        $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='MN Operations Reward'><i class='".$settings["mnops_symbol"]."'></i></span>&nbsp;";
                      } else if ($vout->type == "mlc" && isset($settings["mlc_symbol"])){
                        $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='MLC Reward'><i class='".$settings["mlc_symbol"]."'></i></span>&nbsp;";
                      } 
                    }
                  }
                  if (isset($vout->type)){
                    if ($vout->type == "shield"){
                      $sym = (isset($settings["shield_symbol"])) ? $settings["shield_symbol"] : "fas fa-shield-alt";
                      $pre_str .= "<span class='text-info ml-1 mb-1 p-1' title='Shielded Output'><i class='".$sym."'></i></span>&nbsp;";
                    } else {
                      if ($vout->type == "airdrop"){
                        $sym = (isset($settings["airdrop_symbol"])) ? $settings["shield_symbol"] : "fas fa-hand-holding-water";
                        $pre_str .= "<span class='text-info ml-1 mb-1 p-1' title='Airdrop Output'><i class='".$sym."'></i></span>&nbsp;";
                      }
                    }
                  }
                  // asset tag
                  if (isset($vout->is_asset_move) && $vout->is_asset_move ) {
                    $reward_tag = $reward_tag. "<span class='text-info ml-1 mb-1 p-1' title='Asset Movement'><i class='fas fa-box-open'></i></span>&nbsp;";
                  }
                  // suspect tag
                  $tag_str = "";
                  if (isset($vout->suspect) && $vout->suspect) {
                    $tag_str .= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movements'></i>&nbsp;";
                  }
                  // group tag & address alias
                  $tt->continueTimer("tx.db_getAddressTags");
                  $at = db_getAddressTags($vout->address);
                  $tt->stopTimer("tx.db_getAddressTags");

                  $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                  $cs_staker = ""; $cs_owner = "";
                  if (isset($vout->staker) && !empty($vout->staker)){
                    $cs_staker = "<br><span class='grey-text cs-address' title='Staker Address'>".$vout->staker."</span>";
                    $cs_owner = "title='Owner Address'";
                  }

                  echo "<tr><td class='text-left p-1'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$vout->address."' ".$cs_owner.">".$pre_str.$vout->address."</a>".$reward_tag.$cs_staker.$tag_str."</td>";
                  echo "<td class='text-right p-1'><span class='text-success'>". $oa_split->whole.".<small>".$oa_split->fraction."</small></span></td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
        <!--/.Card content-->

      </div>
      <!--/.Outputs List Card-->
    </div>  
  </div>
<?php else : ?>
  <div class="row">
    <div class="col-12">
      <!--Inputs List Card-->
      <div class="card w-100 mb-4">
        <!--Card content-->
        <div class="card-body text-nowrap text-center primary-text">
          This Tx doesn't have VINs and VOUTs.
        </div>
      </div>
    </div>
  </div>
<?php endif; 
$tt->stopTimer("processVinsAndVouts");
?>
</section>

<!--/Section: Transaction DataTable-->

<!--Section: ASSET Tables-->
<?php 
  $tt->createTimer("processAssetVinsAndVouts",1);
  $tt->createTimer("assets.db_getAddressTags",2);
?>
<section class="<?php echo $asset_vis;?>">
          <div class="row">
            <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
              <!--Inputs List Card-->
              <div class="card w-100 mb-4">
                <div class="card-header card-header light-color text-danger py-2">
                  <span class="h4 h4-responsive"><i class="fas fa-box-open pr-2" aria-hidden="true"></i> Asset Inputs </span>
                </div>

                <!--Card content-->
                <div class="card-body table-responsive text-nowrap">
                  <div class="table-container">
                    <table class="table table-hover table-bordered table-sm" cellspacing="0" width="100%">
                      <thead>
                        <tr class="table-secondary text-center">
                          <th class="th-sm">Address</th>
                          <th class="th-sm">Asset Name</th>
                          <th class="th-sm">Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        foreach ($a_vins as $avin){
                          $ia_split = splitFloat($avin->amount, $precision, $precision);
                          $tt->continueTimer("assets.db_getAddressTags");
                          $at = db_getAddressTags($avin->address);
                          $tt->stopTimer("assets.db_getAddressTags");
                          $tag_str = "";
                          // suspect tag
                          if (isset($avin->suspect) && $avin->suspect) {
                            $tag_str .= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movements'></i>&nbsp;";
                          }
                          $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');

                          echo "<tr><td class='text-left p-1'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$avin->address."'>".$avin->address."</a>".$tag_str."</td>";
                          echo "<td class='text-center p-1'><span class='primary-text'><a href='".$settings["explorer_address"]."/asset/?asset_id=". $avin->key."'>". $avin->name."</a></td>";
                          echo "<td class='text-right p-1'><span class='text-danger'>". $ia_split->whole.".<small>".$ia_split->fraction."</small></span></td></tr>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <!--/.Card content-->

              </div>
              <!--/.Inputs List Card-->
            </div>  
            
            <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
              <!--Outputs List Card-->
              <div class="card w-100 mb-4">
                <div class="card-header card-header light-color text-success py-2">
                  <span class="h4 h4-responsive"><i class="fas fa-box-open pr-2" aria-hidden="true"></i> Asset Outputs </span>
                </div>

                <!--Card content-->
                <div class="card-body table-responsive text-nowrap">
                  <div class="table-container">
                    <table class="table table-hover table-bordered table-sm " cellspacing="0" width="100%">
                      <thead>
                        <tr class="table-secondary text-center">
                          <th class="th-sm">Address</th>
                          <th class="th-sm">Asset Name</th>
                          <th class="th-sm">Amount</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        foreach ($a_vouts as $avout){
                          $oa_split = splitFloat($avout->amount, $precision, $precision);
                          $tt->continueTimer("assets.db_getAddressTags");
                          $at = db_getAddressTags($avout->address);
                          $tt->stopTimer("assets.db_getAddressTags");
                          $tag_str = "";
                          $sym_str = "";
                          // suspect tag
                          if (isset($avout->suspect) && $avout->suspect) {
                            $tag_str .= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movements'></i>&nbsp;";
                          }
                          $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                          if (isset($avout->message) && isset($settings["show_asset_messages"]) && $settings["show_asset_messages"]) {
                            if (isset($settings['asset_message_symbol']) && !empty($settings['asset_message_symbol'])) {
                               $sym_str .= "<span class='text-info ml-1 mb-1 p-1' title='Asset Message'><i class='".$settings['asset_message_symbol']."'></i></span>&nbsp;";
                            } else {
                              $sym_str .= "<span class='text-info ml-1 mb-1 p-1' title='Asset Message'><i class='far fa-comment-alt'></i></span>&nbsp;";
                            }
                          }
                          $amount_str = "";
                          if ($avout->amount == 0 && isset($avout->qualifier_type)){
                            $amount_str = "<span class='badge badge-primary ml-1 mb-1'>".$avout->qualifier_type."</span>&nbsp;";
                          } else {
                            $amount_str = "<span class='text-success'>". $oa_split->whole.".<small>".$oa_split->fraction."</small></span>";
                          }
                          echo "<tr><td class='text-left p-1'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$avout->address."'>".$avout->address."</a>".$sym_str.$tag_str."</td>";
                          echo "<td class='text-center p-1'><span class='primary-text'><a href='".$settings["explorer_address"]."/asset/?asset_id=".$avout->key."'>". h($avout->name)."</a></td>";
                          echo "<td class='text-right p-1'>".$amount_str."</td></tr>";
                        }
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <!--/.Card content-->

              </div>
              <!--/.Outputs List Card-->
            </div>  
          </div>

</section>
<!--/Section: ASSET Table-->
<?php endif; ?>

<?php if (isset($settings["dip2_support"]) && $settings["dip2_support"] && isset($settings["show_dip2"]) && $settings["show_dip2"] && isset($tr->dip2_type) && isset($tr->dip2_msg)) : ?>
  <section>
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card w-100 mb-4">
        <div class="card-header card-header primary-color text-white py-2">
            <span class="h4 h4-responsive">
              <?php 
                if (isset($settings['dip2_symbol']) && !empty($settings['dip2_symbol'])) {
                  echo "<i class='".$settings['dip2_symbol']." pr-2' aria-hidden='true'></i>";
                } else {
                  echo "<i class='fas fa-envelope-open-text pr-2' aria-hidden='true'></i>";
                }
              ?>
              DIP2 Message Content: <?php echo $tr->dip2_type;?> </span>
          </div>
          <div class="card-body">
            <div class="col-12">
              <pre id="dip2">
              </pre>
              <script nonce="<?php echo $csp_nonce;?>"  type="text/javascript">
                dip2=<?php echo json_encode($tr->dip2_msg); ?>;
                document.getElementById('dip2').innerHTML = jsonText(dip2);
              </script>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
<?php endif;?>  


<?php if(isset($tr->op_return) && isset($settings['show_op_return']) && $settings['show_op_return']) : ?>
  <section>
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card w-100 mb-4">
          <div class="card-header card-header primary-color text-white py-2">
            <span class="h4 h4-responsive">
              <?php 
                if (isset($settings['op_return_symbol']) && !empty($settings['op_return_symbol'])) {
                  echo "<i class='".$settings['op_return_symbol']." pr-2' aria-hidden='true'></i>";
                } else {
                  echo "<i class='far fa-comment-dots pr-2' aria-hidden='true'></i>";
                }
              ?>
              OP_RETURN Message </span>
          </div>
          <div class="card-body">
            <div class="col-12 text-primary">
              <?php echo h($tr->op_return); ?>
            </div>
          </div>
      </div>
    </div>
  </section>
<?php endif; ?>

<?php if(count($a_messages)>0 && isset($settings['show_asset_messages']) && $settings['show_asset_messages']) : ?>
  <section>
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card w-100 mb-4">
          <div class="card-header card-header primary-color text-white py-2">
            <span class="h4 h4-responsive">
              <?php 
                if (isset($settings['asset_message_symbol']) && !empty($settings['asset_message_symbol'])) {
                  echo "<i class='".$settings['asset_message_symbol']." pr-2' aria-hidden='true'></i>";
                } else {
                  echo "<i class='far fa-comment-alt pr-2' aria-hidden='true'></i>";
                        }
              ?>
              Asset Message </span>
          </div>
          <div class="card-body">
            <div class="col-12 text-primary">
              <?php if (count($a_messages)==1){
                foreach ($a_messages as $amsg){
                  echo h($amsg);
                }
              } else {
                foreach ($a_messages as $addr=>$amsg){
                  echo h($addr).": <b>".h($amsg)."</b><br>";
                }    
              }
               ?>
            </div>
          </div>
      </div>
    </div>
  </section>
<?php endif; ?>


<!-- NOT FOUND SECTION -->
<section class="<?php echo $notfound_visibility;?>">
  <div class="row">
    <!-- Grid column -->
    <div class="col-12 mb-4">
      <!-- Card -->
      <div class="card card-cascade cascading-admin-card">

        <!-- Card Data -->
        <div class="row admin-up">
          <div>
            <i class="far fa-question-circle danger-color accent-2 mr-3 z-depth-2"></i>
          </div>
          <div class="data-left pl-3">
            <h5 class="text-uppercase text-danger" >transaction not found</h5>
          </div>
        </div>

        <!-- Card content -->
        <div class="card-body card-body-cascade mt-2">
          <h6 > Requested transaction hash: <span class="font-weight-bold text-danger ml-2"><?php echo $txid; ?></span></h6>
          <p class="card-text">Transaction was not found in the database.<br>
          It may happen due to following reasons:
            <ul> 
              <li class="card-text">incorrect transaction hash entered</li>
              <li class="card-text">transaction was not yet included in block</li>
              <li class="card-text">explorer not fully synchronized with blockchain</li>
            </ul>
          </p>
        </div>
      </div>
      <!-- Card -->
    </div>
    <!-- Grid column -->
  </div>
</section>

<!-- EMPTY REQUEST SECTION -->
<section class="<?php echo $emptyrequest_visibility;?>">
  <div class="row">
    <!-- Grid column -->
    <div class="col-12 mb-4">
      <!-- Card -->
      <div class="card card-cascade cascading-admin-card">

        <!-- Card Data -->
        <div class="row admin-up">
          <div>
            <i class="far fa-question-circle danger-color accent-2 mr-3 z-depth-2"></i>
          </div>
          <div class="data-left pl-3">
            <h5 class="text-uppercase text-danger" >empty request</h5>
          </div>
        </div>

        <!-- Card content -->
        <div class="card-body card-body-cascade mt-2">
          <h6 >Your search request doesn't contain transaction hash.</h6>
          <p class="card-text">Navigation url is incomplete, please fix it and try again.
          </p>
        </div>
      </div>
      <!-- Card -->
    </div>
    <!-- Grid column -->
  </div>
</section>

<?php 
  $tt->stopTimer("pageTotal");
  if (isset($mn) && !empty($mn) && isset($settings["magic_key"]) && $mn == $settings["magic_key"]) : ?>
  <div class="card col-12 mt-3 p-0">
    <div class="card-header primary-color card-header-text  py-2 text-left">
      <span class="h6 h6-responsive "><i class="fas fa-stopwatch" aria-hidden="true"></i> PAGE PROCESSING TIME </span>
    </div>
    <div class="table-container p-3">
      <table class="table table-stripped table-hover table-sm" cellspacing="0" width="100%" style="font-size:90%;">
      <thead>
        <tr class="table-secondary text-center text-weight-bold">
          <th class="th-sm px-2 text-left">Task</th>
          <th class="th-sm px-2 text-right">Execution Time (seconds)</th>
          <th class="th-sm px-2 ">Additional Info</th>
        </tr>
      </thead>
      <?php 
        foreach ($tt->getCollection() as $tt){
          echo "  <tr><td class='py-1  pl-".(2*$tt->level)."'>$tt->name</td><td class='py-1 px-3 text-right'>".fpp($tt->getDurationSeconds(),6)."</td><td class='py-1  pl-4'>$tt->info</td></tr>";
        }
      ?>
      </table>
    </div>
  </div>
<?php endif; ?>

    </div>
  </main>
  <!--/Main layout-->



<?php
  include_once __DIR__ . '/../includes/footer.php';
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"transaction",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
