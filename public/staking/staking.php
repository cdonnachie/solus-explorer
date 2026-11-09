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
  $precision = (int)$settings["default_precision"];
   
  $db_bc = db_getLastDbBlockNumber();
  $rpc_bc = getRpcBlockCount();
  if ($db_bc < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }
  $lbk = db_getLastBlocks(1);
  if (count($lbk)>0){
    $etime = $lbk[0]->block_time_sec;
  } else {
    $etime = time();
  }
  $bks = db_getBlocksByTimeRange($etime-86400, $etime, true, true,true);
  $zero = splitFloat(0, $precision, $precision);
  $lsr = $lsb = $asr = $asb = $zero;
  $b_ix = $b_sb = $n_ix = $n_sb = $b_di = array();
  if (count($bks)>0){
    $lsr = splitFloat(dflt($bks[count($bks)-1]->stake_reward), $precision, $precision);
    $lsb = splitFloat(dflt($bks[count($bks)-1]->stake_base), $precision, $precision);
    $lbr = dflt($bks[count($bks)-1]->block_reward);
    $bc = count($bks);
    $asr = 0;
    $b_ix = array();
    $b_br = array(); //block_reward
    $b_sr = array(); //stake_reward
    $b_sb = array(); //stake_base
    $n_ix = array(); //non-pos blocks
    $n_sb = array(); //non-pos bases
    $asa_dec = @cdec(0);

    foreach ($bks as $br){
      if(@cdec($br->stake_reward) > 0){
        array_push($b_ix, $br->block_ix);
        array_push($b_sr, dflt($br->stake_reward));
        array_push($b_sb, dflt($br->stake_base));
        array_push($b_br, dflt($br->block_reward));
        $asa_dec += @cdec($br->stake_reward);
      }else{ 
        array_push($n_ix, $br->block_ix);
        array_push($n_sb, dflt($br->stake_base));
      }
    }
    $asa = array_filter($b_sr);
    $asr = count($b_ix)>0 ? splitFloat($asa_dec/count($b_ix),$precision, $precision) : splitFloat(0,$precision, $precision);
    $aba = array_filter($b_sb);
    $asb = count($b_sb)>0 ?  splitFloat(array_sum($aba)/count($b_sb),$precision, $precision) :  splitFloat(0,$precision, $precision);

    
    // $step = ceil((max($b_sb) - 0) / 20);
    // $b_di = [];
    // for ($i = 0; $i < max($b_sb) - $step; $i += $step) {
    //     $res = array_filter($b_sb, function($v) use ($i, $step) {
    //         return ($i <= $v) && $v < $i +$step;
    //     });
    //     $b_di[$i . ' ... ' . ($i + $step)] = $res;
    // }

    // NEW CALCULATION
    $steps = 20;
    $step = ceil(2 * $asb->number / $steps );

    for ($i = 0; count($b_sb) > 0 && $i < $steps-1; $i++) {
        $rstart = $step*$i;
        $res = array_filter($b_sb, function($v) use ($rstart, $step) {
            return ($rstart <= $v) && $v < $rstart + $step;
        });
        $b_di[$rstart . ' ... ' . ($rstart + $step)] = $res;
    }
    if (count($b_sb) > 0) {
      $rstart = $step*($steps-1);
      $res = array_filter($b_sb, function($v) use ($rstart) {
        return ($rstart <= $v);
      });
      $b_di[$rstart . ' ... ∞'] = $res;
    }
    ////////////////


  }
        
?>

<!--Section: Button icon-->
<section>

<!--Grid row-->
<div class="row">
    <!--Grid column I-->
     <div class="col-xl-6 col-lg-6 col-md-12 ">

      <!--Last Card-->
        
        <div class="card mb-4">

          <!--Card Data-->
          <div class="row pr-3 pl-3">
            <div class="col-md-3 col-3 text-left">
              <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-tint card-icon-text" aria-hidden="true"></i></a>
            </div>

            <div class="col-md-9 col-9 text-right">
              <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $lsr->whole.".".$lsr->fraction; ?></h2>
              <p class="font-small h4-responsive primary-text">LAST STAKE REWARD (<?php echo $settings["coin_symbol"]?>)</p>
            </div>
          </div>
          <!--/.Card Data-->

          <!--Card content-->
          <div class="row my-0 pr-3 pl-3">
            <div class="mb-2 col-12 text-right">
              <span class="font-small pr-3 h6-responsive grey-text">Last Stake Base (<?php echo $settings["coin_symbol"]?>)</span>
              <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $lsb->whole.".".$lsb->fraction; ?></span>
            </div>
          </div>
          <!--/.Card content-->

        </div>
        <!--/.Block Height Card-->
      </div>
    <!--/.Grid column I-->

    <!--Grid column II-->
      <div class="col-xl-6 col-lg-6 col-md-12 ">    
      <!--Average Card-->

      <div class="card mb-4">

        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-spinner card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $asr->whole.".".$asr->fraction; ?></h2>
            <p class="font-small h4-responsive primary-text">24H AVERAGE STAKE REWARD (<?php echo $settings["coin_symbol"]?>)</p>
          </div>
        </div>
        <!--/.Card Data-->

        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small pr-3 h6-responsive grey-text">24H Average Stake Base (<?php echo $settings["coin_symbol"]?>)</span>
            <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $asb->whole.".".$asb->fraction; ?></span>
          </div>
        </div>
        <!--/.Card content-->

      </div>
      <!--/.Average Blocks Card-->
    
      </div>
    <!--/.Grid column II-->
  </div>
  <!--Grid row-->

  <div class="row">
    <!--Grid column I-->
      <div class="col-lg-6 col-md-12 mb-4">
        <!--Base Chart Card-->
        <div class="card">

          <div class="card-header card-header primary-color card-header-text  py-2">
            <span class="h6 h6-responsive">Stake Base Per Block (24H)</span>
          </div>

          <!--Card content-->
          <div class="row my-0 pr-3 pl-3">
            <div class="my-3 pt-1 col-12 text-right">
              <canvas id="scStakeBase" height="120px"></canvas>
            </div>
          </div>
          <script>
            var ctxSc = document.getElementById('scStakeBase').getContext('2d');
            var myChart = new Chart.Scatter(ctxSc, {
              data: {
                spanGaps: false,
                datasets: [{
                  label: "Staking Blocks",
                  fill: false,
                  borderColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.6); ?>',
                  pointRadius: 2,
                  pointHoverRadius: 3,
                  data: [ 
                    <?php 
                      for ($i=0; $i<count($b_ix); $i++){
                        echo "{x:" . ($b_ix[$i]) . ",y:" . ($b_sb[$i]). "},";
                      }
                    ?>
                  ],
                },{
                  label: "Non-Staking Blocks",
                  fill: false,
                  hidden: true,
                  borderColor: '<?php echo hex2rgba(getNamedColor("secondary"),0.6); ?>',
                  backgroundColor: '<?php echo hex2rgba(getNamedColor("secondary"),0.5); ?>',
                  pointRadius: 2,
                  pointHoverRadius: 3,
                  data: [ 
                    <?php 
                      for ($i=0; $i<count($n_ix); $i++){
                        echo "{x:" . ($n_ix[$i]) . ",y:" . ($n_sb[$i]). "},";
                      }
                    ?>
                  ],
                }],
              },
              options: {
                responsive: true,
                spanGaps: true,
                animation: {
                  duration: 0,
                },
                
                legend:{
                  display: <?php if(count($n_ix)>0){ echo 'true';}else{ echo 'false';} ?>,
                  position: 'top',
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
                      labelString: 'Stake Base (<?php echo $settings['coin_symbol']?>)',
                      display: true
                    },
                    ticks: {
                      beginAtZero: true,
                    },
                  }]
                }
              },
            });
          </script>
          <!--/.Card content-->
        </div>
        <!--/.Block Spacing Chart Card-->
      </div>
    <!--/.Grid column I-->
    <!--Grid column II-->
      <div class="col-lg-6 col-md-12 mb-4">
        <!--stdDev Chart Card-->
        <div class="card">

          <div class="card-header card-header primary-color card-header-text  py-2">
            <span class="h6 h6-responsive">Stake Rewards Distribution (24H)</span>
          </div>

          <!--Card content-->
          <div class="row my-0 pr-3 pl-3 ">
            <div class="my-3 pt-1 col-12 text-right">
              <canvas id="bcBaseDist" height="120px"></canvas>
            </div>
          </div>
          <script>
            var ctxBc = document.getElementById('bcBaseDist').getContext('2d');
            var myBc = new Chart(ctxBc, {
              type: 'bar',
              data: {
                spanGaps: false,
                labels: [
                  <?php
                    foreach ($b_di as $n=>$v){
                      echo "'".$n."',";
                    }
                  ?>
                ],
                datasets: [{
                  label: "",
                  fill: false,
                  borderColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.6); ?>',

                  data: [ 
                    <?php 
                      foreach ($b_di as $n=>$v){
                        echo count($v).",";
                      }
                    ?>
                  ],
                }],
              },
              options: {
                responsive: true,
                spanGaps: true,
                animation: {
                  duration: 0,
                },
                
                legend:{
                  display: false,
                },
                scales: {
                  xAxes: [{
                    scaleLabel: {
                      labelString: 'Stake Base Range (<?php echo $settings['coin_symbol']?>)',
                      display: true,
                    },
                    barPercentage: 0.9,
                    //barThickness: 10,
                    //maxBarThickness: 20,
                    minBarLength: 0,
                    gridLines: {
                        offsetGridLines: true
                    }
                  }],
                  yAxes: [{
                    scaleLabel: {
                      labelString: 'Number Of Rewards',
                      display: true
                    },
                    ticks: {
                      beginAtZero: true,
                      precision: 0,
                      callback: function(value, index, values) {
                      return value.toFixed(0);
                      }
                    },
                  }]
                }
              },
            });
          </script>
          <!--/.Card content-->
        </div>
        <!--/.Base Dist Chart Card-->
      </div>
    <!--/.Grid column II-->

</div>
<!--Grid row-->

</section>
<!--/Section: Button icon-->

<!--Section: Blocks DataTable-->
<section>
<div class="row">
  <div class="col-md-12">
    <!-- List Card-->
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
              <th class="th-sm">Stake Reward</th>
              <th class="th-sm">Stake Base</th>
            </tr>
          </thead>
        </table>
      </div>
      <!--/.Card content-->

    </div>
    <!--/. List Card-->
  </div>      
</div>
</section>
<!--/Section: DataTable-->
<script type='text/javascript'>
  
  // Configure Datatable
    $('#dtBlockList').DataTable({
      serverSide: true,
      ordering: false,
      searching: false,
      "pageLength": 20,
      "scrollX": true,
      "pagingType": "full",
      "processing": true,
      "ajax": {
        "url":"<?php echo $settings["explorer_address"]; ?>/_dp/stakingdp.php",
        "type": "POST",
        "dataSrc": function ( json ) {
          document.getElementById("gbl_time").innerHTML = '$gbl_time='+ json.processingtime_ms +' ms';
          return json.data;
        }
      },
      "language": {
			    "processing": "Loading blocks..."
			},
      "deferRender": true,
      "columnDefs": [ 
       {"targets": [4,5,6,7], "className": "text-right"},
       {"targets": "_all", "className": " text-center"}
      ]
    });
    $('.dataTables_length').addClass('d-none');
    $('.dataTables_processing').addClass('pl-3 primary-text');
    $('.dataTables_info').addClass('pt-4');
</script>

<section class="d-none">
 <div class="row mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0" id="gbl_time"></p>
   </div>
  </div>
 </div>
</section>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"staking",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>