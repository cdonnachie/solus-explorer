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
  $precision = (int)$settings["overview_precision"];
  $def_precision = (int)$settings["default_precision"];
  if (isset($settings["masternode_caching_enabled"]) && $settings["masternode_caching_enabled"]){$ignore_cache = false;} else {$ignore_cache = true;}
  $stime = milliseconds();
  $gmc = ui_getMasternodeCount($ignore_cache);
  $gmc_time = milliseconds()-$stime;
  ($gmc->enabled == "---") ? $ena_vis = "d-none" : $ena_vis = "";
  $stime = milliseconds();
  $gms = getMnSyncStatus();
  if (is_null($gms)){$gms=true;}
  $gms_time = milliseconds() - $stime;
  $col = getCollateralAmount();
  if (is_array($col)){
    $collateral = $col["regular"];
  } else {
    $collateral = $col;
  }

  $collateral_precision = max(strlen(substr(strrchr($collateral, "."), 1)),$precision);
  $mncol_split = splitFloat($collateral, $collateral_precision, $collateral_precision); 

  $stime = milliseconds();
  $db_mm = getLatestMarketsData();
  $gmm_time = milliseconds() - $stime;
  $btc_price = ($db_mm->avg_price);
  
  if (isset($db_mm->base_prices["USD"])){
    $usd_price = ($btc_price * $db_mm->base_prices["USD"]);
  } else {
    $usd_price = @cdec(0);
  }
  
  $mnbtc_split = splitFloat((@cdec($mncol_split->number)*$btc_price),$def_precision,$def_precision);
  $mnusd_split = splitFloat((@cdec($mncol_split->number)*$usd_price),$precision,$precision);
  $b_t = db_getLastBlocks(1)[0]->block_time_sec;
  $stime = milliseconds();
  $gbr = getBlockRewards(1440,$b_t);
  $gbr_time = milliseconds()-$stime;
  //if (isset($settings["masternode_reward_share"])){$mn_share = (float)$settings["masternode_reward_share"];} else {$mn_share = 1;}
  
  $drc_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? @cdec($gbr->totalMn)/$gmc->total : null,$precision,$precision);
  $drb_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? @cdec($gbr->totalMn)*$btc_price/$gmc->total : null,$def_precision,$def_precision);
  $dru_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? @cdec($gbr->totalMn)*$usd_price/$gmc->total : null,$precision,$precision);
  $wrc_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 7*@cdec($gbr->totalMn)/$gmc->total : null,$precision,$precision);
  $wrb_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 7*@cdec($gbr->totalMn)*$btc_price/$gmc->total : null,$def_precision,$def_precision);
  $wru_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 7*@cdec($gbr->totalMn)*$usd_price/$gmc->total : null,$precision,$precision);
  $mrc_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 30*@cdec($gbr->totalMn)/$gmc->total : null,$precision,$precision);
  $mrb_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 30*@cdec($gbr->totalMn)*$btc_price/$gmc->total : null,$def_precision,$def_precision);
  $mru_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 30*@cdec($gbr->totalMn)*$usd_price/$gmc->total : null,$precision,$precision);
  $yrc_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 365*@cdec($gbr->totalMn)/$gmc->total : null,$precision,$precision);
  $yrb_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 365*@cdec($gbr->totalMn)*$btc_price/$gmc->total : null,$def_precision,$def_precision);
  $yru_split = splitFloat(($gmc->total != 0 && $gmc->total != "---") ? 365*@cdec($gbr->totalMn)*$usd_price/$gmc->total : null,$precision,$precision);
  
  $stime = milliseconds();
  $gma = getChainMetricsAggregates(time(),720);
  $gma_time = milliseconds() - $stime;
  $gmq_time = $gma->qtime;
?>

<!--Section: Button icon-->
<section>
  <!--Grid row-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <!--Masternode Count Card-->
      <div class="card col-12 pb-3">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fab fa-hubspot card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$gms) { echo "grey-text"; } ?>"><?php echo $gmc->total; ?></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "MASTERNODE COUNT"; ?></p>
          </div>
        </div>
        <div id="countContainer"> 
          <div class="row px-3 pb-3">
            <div class="table-container col-12" id="countTableContainer">
              <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                <tbody>
                  <?php 
                    // Enabled
                    echo "<tr><td class='text-left p-1'>Enabled </td>";
                    echo "<td class='text-right p-1'><span>". $gmc->enabled ."</span></td></tr>";
                    // Stable
                    echo "<tr><td class='text-left p-1'>Stable </td>";
                    echo "<td class='text-right p-1'><span>". $gmc->stable . "</span></td></tr>";
                    // In Queue
                    echo "<tr><td class='text-left p-1'>In Queue </td>";
                    echo "<td class='text-right p-1'><span>". $gmc->inqueue . "</span></td></tr>";
                  ?>
                </tbody>
              </table>
              <br>
              <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
                <tbody>
                  <?php 
                    // IPv4
                    echo "<tr><td class='text-left p-1'>IPv4 </td>";
                    echo "<td class='text-right p-1'><span>". $gmc->ipv4  ."</span></td></tr>";
                    // IPv6
                    echo "<tr><td class='text-left p-1'>IPv6</td>";
                    echo "<td class='text-right p-1'><span>". $gmc->ipv6 ."</span></td></tr>";
                    // Onion
                    echo "<tr><td class='text-left p-1'>Onion</td>";
                    echo "<td class='text-right p-1'><span>". $gmc->onion ."</span></td></tr>";
                  ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <!--/.Masternode Count Card-->        
      </div>
    <!-- /.Grid Column I -->
    </div>

    <!--Grid column II-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <!--Masternode Earnings Card-->
      <div class="card col-12 pb-3">
        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-coins card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$gms) { echo "grey-text"; } ?>"><?php echo $mncol_split->whole; ?><small>.<?php echo $mncol_split->fraction;?></small></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "COLLATERAL AMOUNT"; ?></p>
          </div>
        </div>

        <div id="earningsContainer"> 
          <div class="row px-3 pb-3">
            <div class="table-container col-12" id="countTableContainer">
              <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                <thead> 
                  <th class="text-center"></th>
                  <th class="text-right"><?php echo $settings["coin_symbol"]; ?></th>
                  <th class="text-right">BTC</th>
                  <th class="text-right">USD</th>
                </thead>
                <tbody>
                  <?php 
                  if (is_array($col)) {
                    foreach ($col as $tier => $collateral) {
                      $tier_col = splitFloat($collateral, $collateral_precision ,$collateral_precision );
                      $tier_btc = splitFloat($collateral * $btc_price, $def_precision ,$def_precision );
                      $tier_usd = splitFloat($collateral * $usd_price, $precision ,$precision );
                      ?>
                   <tr class="" >
                    <td class='text-left p-1 pb-3' style='border:0'><span class="h5-responsive"><?php echo ucwords($tier); ?> Masternode Price</td>
                    <td class='text-right p-1 pb-3' style='border:0'><span class="h5-responsive"><?php echo $tier_col->whole; ?><small>.<?php echo $tier_col->fraction; ?></small></span></td>
                    <td class='text-right p-1 pb-3' style='border:0'><span class="h5-responsive"><?php echo $tier_btc->whole; ?><small>.<?php echo $tier_btc->fraction; ?></small></span></td>
                    <td class='text-right p-1 pb-3' style='border:0'><span class="h5-responsive"><?php echo $tier_usd->whole; ?><small>.<?php echo $tier_usd->fraction; ?></small></span></td>
                  </tr>
                      <?php
                    }
                  } else {
                  ?>
                  <tr class="">
                    <td class='text-left p-1 pb-3'>Collateral Price</td>
                    <td class='text-right p-1 pb-3'><span><?php echo $mncol_split->whole; ?><small>.<?php echo $mncol_split->fraction; ?></small></span></td>
                    <td class='text-right p-1 pb-3'><span><?php echo $mnbtc_split->whole; ?><small>.<?php echo $mnbtc_split->fraction; ?></small></span></td>
                    <td class='text-right p-1 pb-3'><span><?php echo $mnusd_split->whole; ?><small>.<?php echo $mnusd_split->fraction; ?></small></span></td>
                  </tr>
                  <?php
                  }
                  ?>
                  <tr class="">
                    <td class='text-left p-1'>Daily Earnings</td>
                    <td class='text-right p-1'><span><?php echo $drc_split->whole; ?><small>.<?php echo $drc_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $drb_split->whole; ?><small>.<?php echo $drb_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $dru_split->whole; ?><small>.<?php echo $dru_split->fraction; ?></small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Weekly Earnings</td>
                    <td class='text-right p-1'><span><?php echo $wrc_split->whole; ?><small>.<?php echo $wrc_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $wrb_split->whole; ?><small>.<?php echo $wrb_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $wru_split->whole; ?><small>.<?php echo $wru_split->fraction; ?></small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Monthly Earnings</td>
                    <td class='text-right p-1'><span><?php echo $mrc_split->whole; ?><small>.<?php echo $mrc_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $mrb_split->whole; ?><small>.<?php echo $mrb_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $mru_split->whole; ?><small>.<?php echo $mru_split->fraction; ?></small></span></td>
                  </tr>
                  <tr class="">
                    <td class='text-left p-1'>Yearly Earnings</td>
                    <td class='text-right p-1'><span><?php echo $yrc_split->whole; ?><small>.<?php echo $yrc_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $yrb_split->whole; ?><small>.<?php echo $yrb_split->fraction; ?></small></span></td>
                    <td class='text-right p-1'><span><?php echo $yru_split->whole; ?><small>.<?php echo $yru_split->fraction; ?></small></span></td>
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
  <!--Grid row-->
  </div>
</section>
<!--/Section: Button icon-->

<!--/Section: MN chart-->
<section>
  <div class="row">
    <div class="col-12 mb-4">
      <div class="card">
      <div class="row my-0 pr-3 pl-3 d-none d-sm-none d-md-block">
          <div class="col-12 py-3 text-center">
            <span class="h6 h6-responsive">Masternodes Online (last 30 days)</span>
          </div>
          <div class="mb-2 col-12 text-right">
            <canvas id="lcMnPerHour" height="70px"></canvas>
          </div>
        </div>
        <script>
          var ctxL = document.getElementById('lcMnPerHour').getContext('2d');
          var myLineChart = new Chart(ctxL, {
            type: 'line',
            data: {
              spanGaps: false,
              labels: [
                <?php  
                  foreach($gma->timestamps as $n=>$a){
                    echo "'" . $a . "',";
                  } 
                ?>
              ],
              datasets: [{
                  label: "Total MNs",
                  fill: false,
                  data: [
                    <?php  
                      foreach ($gma->masternodecount as $n=>$m){
                        echo "'" . $m . "',";
                      } 
                    ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 2,
                },{
                  label: "Enabled MNs",
                  fill: false,
                  hidden: true,
                  data: [
                    <?php  
                      foreach ($gma->masternodecountenabled as $n=>$e){
                        echo "'" . $e . "',";
                      } 
                    ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getNamedColor("success"),0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor("success"),0.6); ?>',
                  borderWidth: 2,
                  pointRadius: 0,
                  pointHoverRadius: 2,
                }
              ]
            },
            options: {
              responsive: true,
              spanGaps: true,
              animation: {
                duration: 0,
              },
              
              legend:{
                display: true,
                position: 'right',
              },
              scales: {
                yAxes: [{
                  scaleLabel: {
                    labelString: 'Masternodes (n)',
                    display: true
                  },
                  ticks: {
                    beginAtZero: true,
                    precision: 0,
                  },
                }]
              }
            }
          });
          
        </script>
      </div>
    </div>
  </div>
</section>
<!--/Section: MN chart-->


<!--Section: Masternodes DataTable-->
<section>
<div class="row">
  <div class="col-md-12">
    <!--Masternode List Card-->
    <div class="card">
      <div class="card-header card-header primary-color card-header-text py-2 text-left">
        <span class="h4 h4-responsive"><i class="fab fa-hubspot mr-1" aria-hidden="true"></i> Masternode List</span>
      </div>

      <!--Card content-->
      <div class="card-body table-responsive text-nowrap">
        <table id="dtMasternodeList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
          <thead>
          <?php if (isset($settings["dip2_support"]) && $settings["dip2_support"]) : ?>
            <tr class="table-secondary text-center">
              <th class="th-sm">Payee Address</th>
              <th class="th-sm">Status</th>
              <th class="th-sm">Last Paid Block</th>
              <th class="th-sm">Last Paid Time</th>
              <th class="th-sm">Collateral Address</th>
            </tr>
          <?php else : ?>
            <tr class="table-secondary text-center">
              <th class="th-sm">Address</th>
              <th class="th-sm">Status</th>
              <th class="th-sm">Protocol</th>
              <th class="th-sm">Last Seen</th>
              <th class="th-sm">Active Time</th>
            </tr>
          <?php endif; ?>
          </thead>
        </table>
      </div>
      <!--/.Card content-->

    </div>
    <!--/.Masternode List Card-->
  </div>      
</div>
</section>
<!--/Section: Masternodes DataTable-->
<script type='text/javascript'>
  // Configure Datatable
    $('#dtMasternodeList').DataTable({
      "pageLength": 25,
      "scrollX": true,
      "pagingType": "simple_numbers",
      "stateSave": true,
      "ajax": { 
        url:"<?php echo $settings["explorer_address"]; ?>/_dp/masternodelistdp.php",
        dataSrc: function ( json ) {
          document.getElementById("gml_time").innerHTML = '$gml_time='+ json.processingtime_ms +' ms';
          return json.data;
        }
      },
      "deferRender": true,
      "columnDefs": [ 
        {"targets": "_all", "className": "text-center"}
      ]
    });
    $('.dataTables_length').addClass('bs-select');
    $('.dataTables_info').addClass('pt-4');
</script>

<section class="d-none">
 <div class="row mt-3">
  <div class="col-12 text-left">
   <div class="card card-info p-3">
    <p class="small my-0 py-0">$gmc_time=<?php echo $gmc_time; ?> ms</p>
    <p class="small my-0 py-0">$gms_time=<?php echo $gms_time; ?> ms</p>
    <p class="small my-0 py-0">$gmm_time=<?php echo $gmm_time; ?> ms</p>
    <p class="small my-0 py-0">$gbr_time=<?php echo $gbr_time; ?> ms</p>
    <p class="small my-0 py-0">$gma_time=<?php echo $gma_time; ?> ms</p>
    <p class="small my-0 py-0">$gmq_time=<?php echo $gmq_time; ?> ms</p>
    <p class="small my-0 py-0" id="gml_time"></p>
   </div>
  </div>
 </div>
</section>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"masternodes",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>