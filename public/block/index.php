<?php
  require_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';    
  global $settings;
  include_once __DIR__ . '/../../lib/explorerDb.php';
  include_once __DIR__ . '/../../lib/explorerUi.php';
  include_once __DIR__ . '/../../lib/explorerRpc.php';
  include_once __DIR__ . '/../../lib/timeTracker.php';

  global $csp_nonce;
  $csp_nonce = random_hex(8);    

  $apt = new TimeTracker;
  $apt->createTimer("total",0);
  $tt = new TimeTracker;
  $tt->createTimer("pageTotal",0);
  $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];

  $blockheight = sanitizeInput('blockheight','GET');
  $blockhash = sanitizeInput('blockhash','GET');
  // Heights are digits only; anything else is treated as not found.
  // Keep the fact that something WAS asked for: once the height is blanked the
  // page cannot otherwise tell "/block/" (a search form, fine to serve) from
  // "/block/?blockheight=notanumber" (a bad request, which must answer 404).
  $bad_request = ($blockheight !== '' && !ctype_digit((string)$blockheight));
  if ($blockheight !== '' && !ctype_digit((string)$blockheight)) { $blockheight = ''; }
  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

  if(!empty($blockheight) || $blockheight == "0"){ $block_str = ": $blockheight"; $search_by = "blockheight"; $search_item = $blockheight; } 
  else if(!empty($blockhash)){ $block_str = ": $blockhash"; $search_by = "blockhash"; $search_item = $blockhash;}
  else { $block_str = ""; $search_by = "block"; $search_item = "";}

  global $description;
  $description = ui_getCoinName()." block ".$search_item.": transactioms, movements, reward and winners.";
  global $keywords;
  $keywords = $settings["title"].", chain block, ".$search_item.", block subsidy, block reward, miners, transactions, movements, difficulty, confirmations"; 
  global $title;
  $title = ui_getCoinName()." Block ".$search_item." | Transactions & Movements";

  if(!empty($blockhash)){ 
    $noblk = false; 
    $tt->createTimer("db_getBlockByHash",1);
    $blk=db_getBlockByHash($blockhash); 
    $tt->stopTimer("db_getBlockByHash");
    $query = $blockhash;
    if (count($blk)>0){
      $br = $blk[0];
      $blockheight = $br->block_ix;
      $form_visible =true;
      $notfound_visible = false;
      $emptyrequest_visible = false;
    } else {
      //block not found
      $form_visible =false;
      $notfound_visible = true;
      $emptyrequest_visible = false;
      $rtype = "hash";
      $br = new stdClass();
    }
  } else if (!empty($blockheight) || $blockheight == "0") {
    $noblk = false; 
    $tt->createTimer("db_getBlockByHeight",1);
    $blk=db_getBlockByHeight((int)$blockheight);
    $tt->stopTimer("db_getBlockByHeight"); 
    $query = $blockheight;
    if (count($blk)>0){
      $br = $blk[0];
      $form_visible = true;
      $notfound_visible = false;
      $emptyrequest_visible = false;
    } else {
      //block not found
      $form_visible = false;
      $notfound_visible = true;
      $emptyrequest_visible = false;
      $rtype = "height";
      $br = new stdClass();
    }
  } else { 
    $noblk = true; 
    $form_visible = false;
    $notfound_visible = false;
    $emptyrequest_visible = true;
    $br = new stdClass();
  }

  if ($form_visible){
    $title = ui_getCoinName()." Block ".$br->block_ix." | Transactions & Movements";

    $tt->createTimer("getRpcBlockCount",1);
    $rpc_bc = getRpcBlockCount();
    $tt->stopTimer("getRpcBlockCount");
    $tt->createTimer("db_getLastDbBlockNumber",1);
    $db_bc = db_getLastDbBlockNumber();
    $tt->stopTimer("db_getLastDbBlockNumber");
    $b_size = normalizeSi($br->block_size);
    $b_diff = normalizeSi(@cdec($br->difficulty));
    $d_split = splitFloat($b_diff->value,3,3);
    $b_vout = splitFloat(($br->vouts_amount), $precision, $precision);
    $b_move = splitFloat(($br->move_amount), $precision, $precision);
    $b_fees = splitFloat(($br->fee_amount), $precision, $precision);
    $conf = $rpc_bc - $br->block_ix +1;
    if ($conf <= 0) {
      $conf_class="danger";
    // } else if ($conf < 0) {
    //     $conf_class="danger";
    //     $conf = "N/A";
    } else if ($conf >= (int)$settings["maturity"]) {
      $conf_class="success";
    } else {
      $conf_class="warning";
    }

    $prev_vis = "btn-lst";
    $next_vis = "btn-lst";
    if ($blockheight+1 > $db_bc){$next_vis = "btn-lst-disabled disabled";}
    if ($blockheight-1 < 0) {$prev_vis = "btn-lst-disabled disabled";}
  }


  // An unknown block is not content: answer 404 and keep it out of the index.
  // The page URLs are query-string based, so leaving these at 200 "index, follow"
  // offers crawlers an unbounded space of near-duplicate pages.
  if ($notfound_visible || $bad_request){
      ui_markNotIndexable(404);
  } else if ($emptyrequest_visible){
      ui_markNotIndexable();      // bare /block/ is a usable form, just not content
  }

  include_once __DIR__ . '/../includes/header.php';
?>
  <!--Main layout-->
  <main>
    
    <div id="content" class="container-fluid">
<?php if($form_visible) : ?>
  <!--Section: Button icon-->
  <section>
    <!--Grid row-->
    <div class="row">
      <!--Grid column I-->
      <div class="col-12 mb-4">
        <!--Address Card (small)-->
        <div class="card col-12 d-md-none" style="min-height:90px">
          <!--Card Data-->
          <div class="text-center">
            <div class="col p-2">
              <div class="row align-items-center justify-content-center">
                <div class="col-auto px-1">
                  <h1 class="h4 h4-responsive m-0"><?php echo "Block #".$br->block_ix; ?></h1>
                </div>
                <div class="col-auto px-1">
                  <a class='btn badge badge-primary ml-2 px-1'
                    href='<?php echo $settings["explorer_address"];?>/block/raw/?hash=<?php echo $br->_id;?>' 
                    title='Show raw block'>{...}</a>
                </div>
              </div>
              <div class="row align-items-center justify-content-center">
                <div class="col-auto px-1">
                  <small class='grey-text'><?php echo $br->_id;?></small>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-6 text-left px-2 py-0 my-0">
                <a title="Previous Block" class="<?php echo $prev_vis; ?> p-0 m-0" href="<?php echo $settings['explorer_address'].'/block/?blockheight='.($blockheight-1);?>">
                  <div class="p-1">
                    <i class="fas fa-angle-double-left p-0 m-0 mr-1" aria-hidden="true"></i>Previous
                  </div>                  
                </a>
              </div>
              <div class="col-6 text-right px-2 py-0 my-0">
                <a title="Next Block" class="<?php echo $next_vis; ?> p-0 m-0" href="<?php echo $settings['explorer_address'].'/block/?blockheight='.($blockheight+1);?>">
                  <div class="p-1">
                    Next <i class="fas fa-angle-double-right p-0 m-0 ml-1" aria-hidden="true"></i>
                  </div>                  
                </a>
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
              <a type="button" title="Previous Block" class="<?php echo $prev_vis; ?>" href="<?php echo $settings['explorer_address'].'/block/?blockheight='.($blockheight-1);?>">
                  <div class="p-2 mr-3" style="width:2rem !important;">
                    <i class="fas fa-angle-double-left" aria-hidden="true"></i>
                  </div>                  
              </a>
              <div class="col p-0">
                <div class="row">
                  <div class="pt-1 pl-2 ">
                      <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-2 mr-2"><i class="fas fa-cube card-icon-text" aria-hidden="true"></i></a>
                  </div>
                  <div class="p-3 ">
                    <span class="h2 h2-responsive mb-0 font-weight-bold primary-text">
                      <div class="row align-items-center">
                          <div class="col-auto pr-0"><h1 class="h5 h5-responsive m-0"><?php echo "Block #".$br->block_ix; ?></h1></div>
                          <div class="col-auto px-2"><a class='btn badge badge-primary ml-2 px-1'
                            href='<?php echo $settings["explorer_address"];?>/block/raw/?hash=<?php echo $br->_id;?>'
                            title='Show raw block'>{...}</a>
                          </div>
                      </div>  
                      <div class="row">
                          <div class="col-auto"><small class='grey-text'><?php echo $br->_id;?></small></div>
                      </div>
                    </span>
                  </div>
                </div>
              </div>
              <a type="button" title="Next Block" class="<?php echo $next_vis; ?>" href="<?php echo $settings['explorer_address'].'/block/?blockheight='.($blockheight+1);?>">
                  <div class="p-2 ml-3" style="width:2rem !important;">
                    <i class="fas fa-angle-double-right" aria-hidden="true"></i>
                  </div>                  
              </a>
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
                    <td class="font-weight-bold text-right p-1 "><?php echo date('Y-m-d H:i:s (e)', $br->block_time_sec);?></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Confirmations</td>
                    <td class="font-weight-bold text-right p-1"><span class=" text-<?php echo $conf_class; ?>"><?php echo $conf; ?></span></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Size</td>
                    <td class="font-weight-bold text-right p-1"><?php echo fpp($b_size->value,3) . " " . $b_size->dimension . "Bytes"; ?></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Difficulty</td>
                    <td class="font-weight-bold text-right p-1"><?php echo $d_split->whole.".". $d_split->fraction." ".$b_diff->dimension; ?></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Consensus</td>
                    <td class="font-weight-bold text-right p-1"><?php echo $br->type; ?></td>
                  </tr>
                  <?php 
                    if ($br->type == "PoS"){
                      echo '              <tr>
                    <td class="primary-text p-1">PoS Winner</td>
                    <td class="font-weight-bold text-right p-1">
                    <a href='.$settings['explorer_address'].'/address/?address='.$br->pos_winner.'>'.$br->pos_winner.'</a>
                    </td>
                  </tr>';
                    } else if ($br->type == "PoW"){
                      echo '              <tr>
                    <td class="primary-text p-1">PoW Winner</td>
                    <td class="font-weight-bold text-right p-1">
                    <a href='.$settings['explorer_address'].'/address/?address='.$br->pow_winner.'>'.$br->pow_winner.'</a>
                    </td>
                  </tr>';
                    }
                    if (isset($br->mn_winner)){
                      echo '              <tr>
                      <td class="primary-text p-1">MN Winner</td>
                      <td class="font-weight-bold text-right p-1">
                      <a href='.$settings['explorer_address'].'/address/?address='.$br->mn_winner.'>'.$br->mn_winner.'</a>
                      </td>
                    </tr>';
                    }
                    if (isset($br->spow_winner)){
                      echo '              <tr>
                      <td class="primary-text p-1">SPoW Winner</td>
                      <td class="font-weight-bold text-right p-1">
                      <a href='.$settings['explorer_address'].'/address/?address='.$br->spow_winner.'>'.$br->spow_winner.'</a>
                      </td>
                    </tr>';
                    }
                    if (isset($br->dev_winner)){
                      echo '              <tr>
                      <td class="primary-text p-1">Dev Winner</td>
                      <td class="font-weight-bold text-right p-1">
                      <a href='.$settings['explorer_address'].'/address/?address='.$br->dev_winner.'>'.$br->dev_winner.'</a>
                      </td>
                    </tr>';
                    }
                  ?>
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
                    <td class="primary-text p-1">Transactions</td>
                    <td class="font-weight-bold text-right p-1 "><?php echo $br->tx_count;?></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Block Reward</td>
                    <?php $b_split = splitFloat(($br->block_reward), $precision, $precision);?>
                    <td class="font-weight-bold text-right p-1 "><?php echo $b_split->whole;?>.<small class="font-weight-bold"><?php echo $b_split->fraction;?></small></td>
                  </tr>
                  <?php 
                    if ($br->type == "PoS"){
                      $p_split = splitFloat(($br->stake_reward), $precision, $precision);
                      echo '              <tr>
                    <td class="primary-text p-1">PoS Reward</td>
                    <td class="font-weight-bold text-right p-1">
                    '.$p_split->whole.'.<small class="font-weight-bold">'.$p_split->fraction.'</small>
                    </td>
                  </tr>';
                    } else if ($br->type == "PoW"){
                      $p_split = splitFloat(($br->pow_reward), $precision, $precision);
                      echo '              <tr>
                    <td class="primary-text p-1">PoW Reward</td>
                    <td class="font-weight-bold text-right p-1">
                    '.$p_split->whole.'.<small class="font-weight-bold">'.$p_split->fraction.'</small>
                    </td>
                  </tr>';
                    }
                    if (isset($br->mn_winner)){
                      $m_split = splitFloat(($br->mn_reward), $precision, $precision);
                      echo '              <tr>
                      <td class="primary-text p-1">MN Reward</td>
                      <td class="font-weight-bold text-right p-1">
                      '.$m_split->whole.'.<small class="font-weight-bold">'.$m_split->fraction.'</small>
                      </td>
                    </tr>';
                    }
                    if (isset($br->spow_winner)){
                      $o_split = splitFloat(($br->spow_reward), $precision, $precision);
                      echo '              <tr>
                      <td class="primary-text p-1">SPoW Reward</td>
                      <td class="font-weight-bold text-right p-1">
                      '.$o_split->whole.'.<small class="font-weight-bold">'.$o_split->fraction.'</small>
                      </td>
                    </tr>';
                    }
                    if (isset($br->dev_winner)){
                      $d_split = splitFloat(($br->dev_reward), $precision, $precision);
                      echo '              <tr>
                      <td class="primary-text p-1">Dev Reward</td>
                      <td class="font-weight-bold text-right p-1">
                      '.$d_split->whole.'.<small class="font-weight-bold">'.$d_split->fraction.'</small>
                      </td>
                    </tr>';
                    }
                  ?>
                  <tr>
                    <td class="primary-text p-1">Outputs Amount</td>
                    <td class="font-weight-bold text-right p-1"><?php echo $b_vout->whole."."; ?><small class="font-weight-bold"><?php echo $b_vout->fraction; ?></small></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Movements Amount</td>
                    <td class="font-weight-bold text-right p-1"><?php echo $b_move->whole."."; ?><small class="font-weight-bold"><?php echo $b_move->fraction; ?></small></td>
                  </tr>
                  <tr>
                    <td class="primary-text p-1">Fees Amount</td>
                    <td class="font-weight-bold text-right p-1"><?php echo $b_fees->whole."."; ?><small class="font-weight-bold"><?php echo $b_fees->fraction; ?></small></td>
                  </tr>

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


  <!--Section: Masternodes DataTable-->
  <section>
    <div class="row">
      <div class="col-md-12 ">
        <!--Transaction List Card-->
        <div class="card">
          <div class="card-header card-header primary-color card-header-text py-2">
          <span class="h4 h4-responsive"><i class="fas fa-exchange-alt mr-1" aria-hidden="true"></i> Transaction List</span>
          </div>

          <!--Card content-->
          <div class="card-body table-responsive text-nowrap">
            <table id="dtTransactionList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">#</th>
                  <th class="th-sm">Transaction Hash</th>
                  <th class="th-sm">Size</th>
                  <th class="th-sm">VINS/VOUTS</th>
                  <th class="th-sm">Output</th>
                  <th class="th-sm">Movement</th>
                </tr>
              </thead>
            </table>
          </div>
          <!--/.Card content-->

        </div>
        <!--/.Transaction List Card-->
      </div>      
    </div>
  </section>
    <!--/Section: Transaction DataTable-->
    <script nonce="<?php echo $csp_nonce;?>" type='text/javascript'>
      $(document).ready(function() {
        // Configure Datatable
        $('#dtTransactionList').DataTable({
          "pageLength": 25,
          "scrollX": true,
          "pagingType": "simple_numbers",
          "stateSave": true,
          "ajax": { 
            url:"<?php echo $settings["explorer_address"]; ?>/_dp/txlistdp.php?blockhash=<?php echo $br->_id; ?>",
            "type" : "POST",
            dataSrc: function ( json ) {
              const gtx = document.getElementById("gtx_time");
              if (gtx){
                gtx.innerHTML = '$gtx_time='+ json.processingtime_ms +' ms';
              }
              return json.data;
            }
          },
          "deferRender": true,
          "columnDefs": [ 
            {"targets": [ 4, 5 ], "className": "text-right"},
            {"targets": "_all", "className": "text-center"}
          ]
        });
        $('.dataTables_length').addClass('bs-select');
        $('.dataTables_info').addClass('pt-4');
      })
    </script>
<?php endif; ?>

<!-- NOT FOUND SECTION -->
<?php if($notfound_visible) : ?>
  <section>
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
              <h5 class="text-uppercase text-danger" >block not found</h5>
            </div>
          </div>

          <!-- Card content -->
          <div class="card-body card-body-cascade mt-2">
            <h6 > Requested block (<?php echo $rtype; ?>): <span class="font-weight-bold text-danger ml-2"><?php echo $query; ?></span></h6>
            <p class="card-text">Block was not found in the database.<br>
            It may happen due to the following reasons:
              <ul> 
                <li class="card-text">incorrect block <?php echo $rtype; ?> entered</li>
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
<?php endif; ?>

<!-- EMPTY REQUEST SECTION -->
<?php if($emptyrequest_visible) : ?>
  <section>
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
            <h6 >Your search request doesn't contain a block identifier (block height of hash).</h6>
            <p class="card-text">Navigation url is incomplete, please fix it and try again.
            </p>
          </div>
        </div>
        <!-- Card -->
      </div>
      <!-- Grid column -->
    </div>
  </section>
<?php endif; ?>

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
  ui_LogPageEvent($cip,"block",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
