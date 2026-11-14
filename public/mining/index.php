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
  $tt = new TimeTracker;
  $tt->createTimer("pageTotal",0);
  $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["default_precision"];
   

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

  global $description;
  $description = ui_getCoinName()." mining: hashrate, difficulty, block reward and winners.";
  global $keywords;
  $keywords = $settings["title"].", mining, winners, mining rewards, block rewards, block subsidy, hashrate, network hashrate, difficulty, mining difficulty"; 
  global $title;
  $title = ui_getCoinName()." Mining | Hashrate, Difficulty & Rewards";
    global $page_name;
  $page_name = $settings["title"]." - Mining";
  global $page_id;
  $page_id = "Mining";

  // get data from database
  $data = ui_GetMiningData();

  // get rpc block height to see whether the node is synced
  $tt->createTimer('getRpcBlockCount',1);
  $rpc_bc = getRpcBlockCount();
  $tt->stopTimer('getRpcBlockCount');
  if ($data->last_block->number < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }

  $tt->createTimer("include_header",1);
  include_once __DIR__ . '/../includes/header.php';
  $tt->stopTimer("include_header");
?>
  <!--Main layout-->
  <main>

    <div id="content" class="container-fluid">

      <!--Section: Button icon-->
      <section>
        <!--Grid row-->
          <div class="row">
            <!--Grid column I-->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 flex-col">
              <!--Last Card-->
                <div class="card col-12 mb-4">

                  <!--Card Data-->
                  <div class="row pr-3 pl-3">
                    <div class="col-md-3 col-3 text-left">
                      <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas icons-pike card-icon-text" aria-hidden="true"></i></a>
                    </div>

                    <div class="col-md-9 col-9 text-right">
                      <h2 id="lmrText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">
                        <?php echo $data->last_block->pow_reward->whole.".<small>".$data->last_block->pow_reward->fraction; ?></small>
                      </h2>
                      <p class="font-small h4-responsive primary-text">LAST BLOCK MINING REWARD (<?php echo $settings["coin_symbol"]?>)</p>
                    </div>
                  </div>
                  <!--/.Card Data-->

                  <!--Card content-->
                  <div class="row my-0 pr-3 pl-3">
                    <div class="mb-2 col-12 text-right">
                      <span class="font-small pr-3 h6-responsive grey-text">Last Block Reward (<?php echo $settings["coin_symbol"]?>)</span>
                      <span id="lbrText"  class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">
                        <?php echo $data->last_block->block_reward->whole.".".$data->last_block->block_reward->fraction; ?>
                      </span>
                    </div>
                  </div>
                  <!--/.Card content-->

                </div>
              <!--/.Block Height Card-->
            </div>
            <!--/.Grid column I-->

            <!--Grid column II-->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 flex-col">    
              <!--Average Card-->
              <div class="card col-12 mb-4">

                <!--Card Data-->
                <div class="row pr-3 pl-3">
                  <div class="col-md-3 col-3 text-left">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-spinner card-icon-text" aria-hidden="true"></i></a>
                  </div>

                  <div class="col-md-9 col-9 text-right">
                    <h2 id="amrText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">
                      <?php echo $data->average->pow_reward->whole.".<small>".$data->average->pow_reward->fraction; ?></small>
                    </h2>
                    <p class="font-small h4-responsive primary-text">24H AVERAGE MINING REWARD (<?php echo $settings["coin_symbol"]?>)</p>
                  </div>
                </div>
                <!--/.Card Data-->

                <!--Card content-->
                <div class="row my-0 pr-3 pl-3">
                  <div class="mb-2 col-12 text-right">
                    <span class="font-small pr-3 h6-responsive grey-text">24H Average Block Reward (<?php echo $settings["coin_symbol"]?>)</span>
                    <span id="abrText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">
                      <?php echo $data->average->block_reward->whole.".".$data->average->block_reward->fraction; ?>
                    </span>
                  </div>
                </div>
                <!--/.Card content-->

              </div>
              <!--/.Average Blocks Card-->
            
            </div>
            <!--/.Grid column II-->

            <!--Grid column I-->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 flex-col">
              <!--Last Card-->
                <div class="card col-12 mb-4">

                  <!--Card Data-->
                  <div class="row pr-3 pl-3">
                    <div class="col-md-3 col-3 text-left">
                      <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-hashtag card-icon-text" aria-hidden="true"></i></a>
                    </div>

                    <div class="col-md-9 col-9 text-right">
                    <h2 id="hrText" class="ml-1 mt-3 mb-0 h2-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">
                      <?php echo $data->hashrate->value->whole."."; ?><small><?php echo $data->hashrate->value->fraction; ?></small>
                    </h2>
                    <p class="font-small h4-responsive primary-text"><?php echo "HASHRATE (<span id='hrDim'>".$data->hashrate->dimension."</span>H/s)"; ?></p>
                    </div>
                  </div>
                  <!--/.Card Data-->

                </div>
              <!--/.Block Height Card-->
            </div>
            <!--/.Grid column I-->

            <!--Grid column II-->
            <div class="col-xl-3 col-lg-3 col-md-6 col-sm-12 flex-col">    
              <!--Average Card-->
              <div class="card col-12 mb-4">

                <!--Card Data-->
                <div class="row pr-3 pl-3">
                  <div class="col-md-3 col-3 text-left">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-cogs card-icon-text" aria-hidden="true"></i></a>
                  </div>

                  <div class="col-md-9 col-9 text-right">
                    <h2 id="difText" class="ml-1 mt-3 mb-0 h2-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">
                      <?php echo $data->difficulty->value->whole."."; ?><small><?php echo $data->difficulty->value->fraction; ?></small>
                    </h2>
                    <p class="font-small h4-responsive primary-text"><?php 
                      $dimension = empty($data->difficulty->dimension) ? "" : "(".$data->difficulty->dimension.")";
                      echo "DIFFICULTY <span id='difDim'>".$dimension."</span>"; 
                      ?></p>
                  </div>
                </div>
                <!--/.Card Data-->

              </div>
              <!--/.Average Blocks Card-->
            
            </div>
            <!--/.Grid column II-->
          </div>
        <!--/.Grid row-->

        <!--Grid row-->
          <div class="row">
            <div class="col-md-12">
              <div class="card col-12 px-0 mb-4">
                <div class="card-header card-header primary-color card-header-text py-2">
                  <span class="h6 h6-responsive">Hashrate & Difficulty (Last 7 Days)<span>
                </div>
                <!--Card content-->
                <div class="row my-0 pr-3 pl-3" style="min-height:336px;">
                  <div class="my-3 col-12 text-right">
                    <div class="chart-container w-100 text-center p-0 m-0" id="hashdiffContainer" style="height:302px;">
                      <canvas id="lcHashrateDiff"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <!--/.Grid row-->

        <!--Grid row-->
        <div class="row">
          <!--Grid column I-->
            <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
                <!--Base Chart Card-->
                <div class="card col-12 px-0 mb-4">

                  <div class="card-header card-header primary-color card-header-text  py-2">
                    <span class="h6 h6-responsive">Mining Reward Per Block (24H)</span>
                  </div>

                  <!--Card content-->
                  <div class="row my-0 px-3" style="min-height:336px;">
                    <div class="my-3 col-12">
                      <div class="chart-container w-100 text-center p-0 m-0" id="rewardsContainer" style="min-height:302px;">
                        <canvas id="scMiningRewards" height="300px"></canvas>
                      </div>
                    </div>
                  </div>
                  <!--/.Card content-->
                </div>
                <!--/.Block Spacing Chart Card-->
            </div>
          <!--/.Grid column I-->

          <!--Grid column II-->
            <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
              <!--stdDev Chart Card-->
                <div class="card col-12 px-0 mb-4">

                  <div class="card-header primary-color card-header-text  py-2">
                    <span class="h6 h6-responsive">Top Miners (24H)</span>
                  </div>
                    <div class="row my-0 px-3" style="min-height:336px;">
                      <div class="my-3 col-12 flex-col">
                          <!--<div class="col-12 h6 h6-responsive text-center text-primary p-3">BLOCKS FOUND</div> -->
                          <div class="chart-container w-100 text-center" id="blocksContainer" style="min-height:302px;">
                            <canvas id="dcBlockDistr" height="300px"></canvas>
                          </div>
                    </div>
                  </div>
                </div>
              <!--/.Base Dist Chart Card-->
            </div>
          <!--/.Grid column II-->
        </div>
        <!--/.Grid row-->

      </section>
      <!--/Section: Button icon-->

      <?php 
        $minersTableConstantHeight = 
          // search header
          48.38 +
          // table header
          37.25 +
          // table footer
          71.75 + 
          // padding top
          17.5 +
          // padding bottom
          17.5;

        $minersTableRows = isset($data->miners) ? count( array($data->miners) ) : 0;
        $minersTableRowsHeight = $minersTableRows * 27.11;
        $minersTableHeightStyle = $minersTableRows > 0 ? "style=\"min-height:".($minersTableConstantHeight+$minersTableRowsHeight)."px;\"" : "";
      ?>
      <?php
        /* Multi-algorithm chains only. ui_CreateMiningCache() leaves pow_algos empty for
           every chain that does not configure settings["pow_algos"], and the isset()
           also covers a cache document written before this field existed -- that one
           rebuilds within two minutes, and until it does the card is simply absent. */
        $pow_algos = isset($data->pow_algos) ? (array)$data->pow_algos : array();
        if (count($pow_algos) > 0) :
      ?>
      <!--Section: Algorithm split-->
      <section>
        <div class="row">
          <div class="col-md-12 mb-4">
            <div class="card">
              <div class="card-header card-header primary-color card-header-text  py-2 text-left">
                <span class="h6 h6-responsive "><i class="fas fa-cogs mr-1" aria-hidden="true"></i> Algorithm Split (24H)</span>
              </div>
              <div class="card-body table-responsive text-nowrap col-12">
                <table class="table table-hover table-striped table-bordered table-sm mb-0" cellspacing="0" width="100%">
                  <thead>
                    <tr class="table-secondary text-center">
                      <th class="th-sm">Algorithm</th>
                      <th class="th-sm">Blocks</th>
                      <th class="th-sm">Share</th>
                    </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($pow_algos as $pa) : if (!is_object($pa)) { continue; } ?>
                    <tr class="text-center">
                      <td class="font-weight-bold"><?php echo htmlspecialchars($pa->name, ENT_QUOTES, "UTF-8"); ?></td>
                      <td><?php echo number_format((int)$pa->blocks); ?></td>
                      <td><?php echo number_format((float)$pa->percent, 1); ?>%</td>
                    </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!--/Section: Algorithm split-->
      <?php endif; ?>

      <section>
        <div class="row">
          <div class="col-md-12 mb-4">
            <div class="card">
              <div class="card-header card-header primary-color card-header-text  py-2 text-left">
                <span class="h6 h6-responsive "><i class="fas icons-pike mr-1" aria-hidden="true"></i> Top Miners List</span>
              </div>
              <div class="card-body table-responsive text-nowrap col-12" <?php echo $minersTableHeightStyle; ?> >
                <table id="dtWinnerList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
                  <thead>
                  </thead>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!--Section: Blocks DataTable-->
      <section>
        <div class="row">
          <div class="col-12">
            <!--Masternode List Card-->
            <div class="card">
              <div class="card-header card-header primary-color card-header-text  py-2 text-left">
                <span class="h6 h6-responsive "><i class="fas fa-cubes mr-1" aria-hidden="true"></i> Block List</span>
              </div>

              <!--Card content-->
              <div class="card-body table-responsive text-nowrap">
                <table id="dtBlockList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
                  <thead>
                    <tr class="table-secondary text-center">
                      <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                      <th class="th-sm">Height</th>
                      <th class="th-sm">Block Hash</th>
                      <th class="th-sm">Winner</th>
                      <th class="th-sm">Block Reward</th>
                      <th class="th-sm">Fee Amount</th>
                      <th class="th-sm">Winner Reward</th>
                    </tr>
                  </thead>
                </table>
              </div>
              <!--/.Card content-->
            </div>
            <!--/.Masternode List Card-->
            
          </div>      
        </div>
      </section>
      <!--/Section: Blocks DataTable-->

    </div>
</main>
<!--/Main layout-->

<script  nonce="<?php echo $csp_nonce;?>" type='text/javascript'>
  $(document).ready(function() {
    Chart.defaults.global.defaultFontSize = 11;
    let blocksTable;
    let minersTable;

    // hashrate & difficulty chart
    const ctxHd = document.getElementById('lcHashrateDiff').getContext('2d');
    const hdChart = new Chart(ctxHd, {
      type: 'line',
      data: {
        spanGaps: true,
        labels: [
          <?php  
            foreach($data->aggregates->timestamps as $n=>$a){
              echo "'" . $a . "',";
            } 
          ?>
        ],
        datasets: [{
          label: 'Hashrate',
          yAxisID: 'yA',
          fill: false,
          data: [
            <?php  
              foreach ($data->aggregates->hashrate as $n=>$m){
                echo "'" . $m . "',";
              } 
            ?>
          ],
          borderColor: '<?php echo hex2rgba(getNamedColor("primary"),0.7); ?>',
          backgroundColor: '<?php echo hex2rgba(getNamedColor("primary"),0.6); ?>',
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 3,
        }, {
          label: 'Difficulty',
          yAxisID: 'yB',
          fill: false,
          data: [
            <?php  
              foreach ($data->aggregates->difficulty as $n=>$m){
                echo "'" . $m . "',";
              } 
            ?>
          ],
          borderColor: '<?php echo hex2rgba(getNamedColor("info"),0.7); ?>',
          backgroundColor: '<?php echo hex2rgba(getNamedColor("info"),0.6); ?>',
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 2,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        spanGaps: true,
        animation: {
          duration: 0,
        },
        scales: {
          yAxes: [{
            id: 'yA',
            type: 'linear',
            position: 'left',
            gridLines: {
              // color: '<?php echo hex2rgba(getNamedColor("primary"),0.25); ?>',
              display: false
            },
            scaleLabel: {
              labelString: 'Hashrate (H/s)',
              display: true
            },
            ticks: {
              beginAtZero: true,
              callback: function(label, index, labels){
                return normalizeToScale(label, '<?php echo $data->hashrate->dimension; ?>',1)+'<?php echo $data->hashrate->dimension; ?>';
              }
            }
          },{
            id: 'yB',
            type: 'linear',
            position: 'right',
            gridLines: {
              // color: '<?php echo hex2rgba(getNamedColor("info"),0.25); ?>',
              display: false
            },
            scaleLabel: {
              labelString: 'Difficulty',
              display: true
            },
            ticks: {
              beginAtZero: true,
              callback: function(label, index, labels){
                return normalizeToScale(label, '<?php echo $data->difficulty->dimension; ?>',1)+'<?php echo $data->difficulty->dimension; ?>';
              }
            }
          }],
        },
        tooltips: {
          mode: 'x',
          callbacks: {
            label: function(tooltipItem, data) {
              if (tooltipItem.datasetIndex == 0){
                var scale = '<?php echo $data->hashrate->dimension;?>';
              } else if (tooltipItem.datasetIndex == 1){
                var scale = '<?php echo $data->difficulty->dimension;?>';
              }
              var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
              return datasetLabel + '  ' + normalizeToScale(tooltipItem.yLabel, scale, 3) + scale;
            }
          }
        },
      },
    });

    // mining reward chart
    const ctxSc = document.getElementById('scMiningRewards').getContext('2d');
    const mrChart = new Chart.Scatter(ctxSc, {
      data: {
        spanGaps: false,
        datasets: [{
          label: "PoW Blocks",
          fill: false,
          borderColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.7); ?>',
          backgroundColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.6); ?>',
          pointRadius: 2,
          pointHoverRadius: 3,
          data: [ 
            <?php 
              foreach ($data->reward_per_block->pow as $n=>$r){
                echo "{x:" . ($n) . ",y:" . ($r). "},";
              }
            ?>
          ],
        },{
          label: "Non-PoW Blocks",
          fill: false,
          hidden: true,
          borderColor: '<?php echo hex2rgba(getNamedColor("secondary"),0.6); ?>',
          backgroundColor: '<?php echo hex2rgba(getNamedColor("secondary"),0.5); ?>',
          pointRadius: 2,
          pointHoverRadius: 3,
          data: [ 
            <?php 
              foreach ($data->reward_per_block->non_pow as $n=>$r){
                echo "{x:" . ($n) . ",y:" . ($r). "},";
              }
            ?>
          ],
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        spanGaps: true,
        animation: {
          duration: 0,
        },
        legend:{
          display: <?php if($data->reward_per_block->non_pow_blocks>0){ echo 'true';}else{ echo 'false';} ?>,
          position: 'top',
        },
        tooltips: {
          callbacks: {
            label: function(tooltipItem, data) {
              var i = tooltipItem.index;
              return 'Block ' + tooltipItem.xLabel + ': ' + tooltipItem.yLabel.toFixed(<?php echo $precision; ?>) + ' <?php echo $settings["coin_symbol"]; ?>';
            }
          }
        },
        scales: {
          xAxes: [{
            type: 'linear',
            position: 'bottom',
            scaleLabel: {
              labelString: 'Block Height',
              display: true,
            },
            ticks:{
              precision:0,
              callback: function(value, index, values) {
              return value.toFixed(0);
              }
            },
          }],
          yAxes: [{
            scaleLabel: {
              labelString: 'Mining Reward (<?php echo $settings['coin_symbol']?>)',
              display: true
            },
            ticks: {
              beginAtZero: true,
            },
          }]
        }
      },
    });

    // top miners chart (doughnut)
    const ctxDn = document.getElementById('dcBlockDistr').getContext('2d');
    const dnChart = new Chart(ctxDn, {
      type: 'doughnut',
      data: {
        labels: <?php echo json_encode($data->pie_chart->labels); ?>,
        datasets: [{
          data: <?php echo json_encode($data->pie_chart->values); ?>,
          backgroundColor: [<?php echo getChartColors(count($data->pie_chart->labels)-1,1); ?>],
          hoverBorderWidth: 1,
          hoverBorderColor: [<?php echo getChartColors(count($data->pie_chart->labels)-1,1); ?>],
        }]
      },
      options: {
        responsive: true,        
        maintainAspectRatio: false,
        animation: {
          duration: 0,
        },
        legend: {
          display: true,
          position: 'bottom',
        },
        tooltips: {
          callbacks: {
            label: function(tooltipItem, data) {
              var i = tooltipItem.index;
              return data.labels[i] + ': '+ data.datasets[0].data[i] + ' blocks (' + ( 100*data.datasets[0].data[i]/<?php echo $data->total->blocks; ?> ).toFixed(1) + '%)';
            }
          }
        }
      }
    });

    // Miners Datatable
    minersTable = $('#dtWinnerList').DataTable({
      "paging":true,
      "pageLength": 20,
      "scrollX": true,
      "pagingType": "full",
      "processing": false,
      "stateSave": true,
      "language": {
          "processing": "Loading list..."
      },
      "deferRender": true,
      "columns":[
        {"title": "Miner", "className": "text-left"},
        {"title": "Found Blocks"},
        {"title": "Earned Coins(<?php echo $settings['coin_symbol'];?>)"}
      ],
      "columnDefs": [ 
        {"targets": [1,2], "className": "th-sm text-right"},
        {"targets": "_all", "className": "blocklist-hash text-left"}
      ],
      "order": [[ 2, "desc" ]],
      "data": [
        <?php 
          foreach ($data->miners as $addr=>$miner_info) {
            if ($addr != "Other"){
              $cell = "<a class='txlist-txid text-primary' href='".h($settings["explorer_address"]."/address/?address=".$addr)."'>".h($addr)."</a>".ui_labelBadge($miner_info->alias ?? '', 'badge-info ml-1');
              echo json_encode([$cell, (int)$miner_info->mined_blocks, $miner_info->mined_coins->whole.".<small>".$miner_info->mined_coins->fraction."</small>"], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).",";
            }
          }
        ?>
      ]
    });
    $('.dataTable thead tr').addClass('table-secondary');
    $('.dataTable thead tr th').addClass('th-sm');
    $('#dtWinnerList_length').addClass('d-none');
    $('#dtWinnerList_processing').addClass('pl-3 primary-text');

    // Blokcs Datatable
    blocksTable = $('#dtBlockList').DataTable({
      "serverSide": true,
      "ordering": false,
      "searching": false,
      "pageLength": 20,
      "scrollX": true,
      "pagingType": "full",
      "processing": false,
      "ajax": {
        "url":"<?php echo $settings["explorer_address"]; ?>/_dp/miningdp.php",
        "type": "POST",
        "dataSrc": function ( json ) {
          return json.data;
        }
      },
      "language": {
          "processing": "Loading blocks..."
      },
      "deferRender": true,
      "columnDefs": [ 
      {"targets": [4,5,6], "className": "text-right"},
      {"targets": "_all", "className": " text-center"}
      ]
    });
    $('#dtBlockList_length').addClass('d-none');
    $('#dtBlockList_processing').addClass('pl-3 primary-text');
    $('#dtBlockList_info').addClass('pt-4');

  });
</script>


<?php
  include_once __DIR__ . '/../includes/footer.php';
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"mining",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
