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
?>
<?php 
  $stime = milliseconds();
  $db_bc = db_getLastDbBlockNumber();
  $rpc_bc = getRpcBlockCount();
  if ($db_bc < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }
  $gbc_time = milliseconds() - $stime;

  $stime = milliseconds();
  $db_cs = db_getDbCoinSupply(); 
  $cs_split = splitFloat(dflt($db_cs->coincirculation),$precision,$precision);
  $gcs_time = milliseconds() - $stime;

  $stime = milliseconds();
  $lmi = getLatestMarketsData();
  $glm_time = milliseconds() - $stime;

  if (time() - $lmi->timestamp  > 3600 || $db_bc < ($rpc_bc - $settings["maturity"])) { $mmold = true; } else { $mmold = false; }
  $avp_split = splitFloat($lmi->avg_price,$def_precision, $def_precision);
  if (isset($lmi->base_prices["USD"]) && !is_null($lmi->base_prices["USD"])&&!is_null($lmi->avg_price)){
    $aup_split = splitFloat($lmi->avg_price*$lmi->base_prices["USD"],$def_precision, $def_precision);
  } else {
    $$aup_split = splitFloat(0,$def_precision, $def_precision);
  }
  $cng_split = splitFloat($lmi->avg_percent_change,2,2);
  $cvol_split = splitFloat($lmi->volume_coin,$precision,$precision);
  $bvol_split = splitFloat($lmi->volume_base,$def_precision,$def_precision);
  if ($lmi->avg_percent_change > 0) {
    $p_class = "text-success";
    $trend = '<i class="fas fa-long-arrow-alt-up ml-2" aria-hidden="true"></i>';
  } else if ($lmi->avg_percent_change < 0){
    $p_class = "text-danger";
    $trend = '<i class="fas fa-long-arrow-alt-down ml-2" aria-hidden="true"></i>';
  } else {
    $p_class = "grey-text";
    $trend = '<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>';
  }
  if (isset($lmi->base_prices["USD"])){
    $ucap_split = splitFloat(($db_cs->coincirculation*$lmi->avg_price*$lmi->base_prices["USD"]),$precision,$precision);
    $uprc_split = splitFloat($lmi->base_prices["USD"],$precision,$precision);
  }
  if (isset($lmi->base_prices["EUR"])){
    $ecap_split = splitFloat(($db_cs->coincirculation*$lmi->avg_price*$lmi->base_prices["EUR"]),$precision,$precision);
  }
  $bcap_split = splitFloat(($db_cs->coincirculation*$lmi->avg_price),$precision,$precision);
  if (isset($lmi->base_changes["USD"])){
    $bcng_split = splitFloat($lmi->base_changes["USD"],2,2);
  }
  if ($bcng_split->number > 0){
    $b_class = "text-success";
    $btrend = '<i class="fas fa-long-arrow-alt-up ml-2" aria-hidden="true"></i>';
  } else if ($bcng_split->number < 0) {
    $b_class = "text-danger";
    $btrend = '<i class="fas fa-long-arrow-alt-down ml-2" aria-hidden="true"></i>';
  } else {
    $b_class = "grey-text";
    $btrend = '<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>';
  }
  $stime = milliseconds();
  $mo_days = (isset($settings["markets_overview_days"])) ? ($settings["markets_overview_days"]) : 7;
  $mo_hours = $mo_days * 24;
  $gma = getMarketPriceAggregatesByHour(time(), $mo_hours);
  $gma_time = milliseconds()-$stime;
?>
<!--Section: Button icon-->
<section>
<!--Grid row-->
<div class="row">
    <!--Grid column I-->
    <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
      <!--Coin Price Card-->
      <div class="card col-12 pb-3">

        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-chart-line card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if ($mmold) { echo "grey-text";} ?>"><?php echo $avp_split->whole.".".$avp_split->fraction; ?></h2>
            <p class="font-small h4-responsive primary-text">AVERAGE PRICE (BTC)</p>

          </div>
        </div>
        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small pr-3 h6-responsive grey-text"></span>
            <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if ($mmold) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">$&nbsp;<?php echo $aup_split->whole.".".$aup_split->fraction.""; ?></span>
          </div>
        </div>
        <!--/.Card content-->
        
      
      </div>
      <!--/.Coin Price Card-->
    </div>
    <!--/.Grid column I-->

    <!--Grid column II-->
    <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
      <!--Coin Vol Card-->
      <div class="card col-12 pb-3">

        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-chart-bar card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $cvol_split->whole."."; ?><small><?php echo $cvol_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "24H Volume (".$settings["coin_symbol"].")"; ?></p>
          </div>
        </div>
        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small pr-3 h6-responsive grey-text">24H Volume (BTC)</span>
            <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if ($mmold) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $bvol_split->whole.".".$bvol_split->fraction.""; ?></span>
          </div>
        </div>
        <!--/.Card content-->
      </div>
      <!--/.Coin Vol Card-->
    </div>
    <!--/.Grid column II-->

    <!--Grid column III-->
    <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
      <!--Coin Cap Card-->
      <div class="card col-12 pb-3">

        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-weight-hanging card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $bcap_split->whole."."; ?><small><?php echo $bcap_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "CAPITALIZATION (BTC)"; ?></p>
          </div>
        </div>
        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small pr-3 h6-responsive grey-text"></span>
            <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if ($mmold) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">$&nbsp;<?php echo $ucap_split->whole.".".$ucap_split->fraction.""; ?></span>
          </div>
        </div>
        <!--/.Card content-->
      </div>
      <!--/.Coin Cap Card-->
    </div>
    <!--/.Grid column III-->

    <!--Grid column IV-->
    <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
      <!--Btc Price Card-->
      <div class="card col-12 pb-3">

        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fab fa-btc card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">$&nbsp;<?php echo $uprc_split->whole."."; ?><small><?php echo $uprc_split->fraction; ?></small></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "BTC PRICE (USD)"; ?></p>
          </div>
        </div>
        <!--Card content-->
        <div class="row my-0 pr-3 pl-3">
          <div class="mb-2 col-12 text-right">
            <span class="font-small pr-3 h6-responsive grey-text">24H Price Change</span>
            <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if ($mmold) { echo "grey-text";} else { echo $b_class; } ?>"><?php echo $btrend."&nbsp;".$bcng_split->whole.".".$bcng_split->fraction.""; ?>%</span>
          </div>
        </div>
        <!--/.Card content-->
      </div>
      <!--/.Coin Cap Card-->
    </div>
    <!--/.Grid column IV-->    

  </div>
  <!--/.Grid row 1-->

  <!--Grid row 2-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-xl-12 col-lg-12 col-md-12 flex-col mb-4">
      <div class="card col-12 pb-3">
        <div id="topContainer"> 
            <div class="row px-3">

            <div class="table-container col-12" id="topTableContainer">
                <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                  <thead>
                    <th class="th-sm font-weight-bold text-left">Exchange</th>
                    <th class="th-sm font-weight-bold text-right">Last Price</th>
                    <th class="th-sm font-weight-bold text-center">% Change</th>
                    <th class="th-sm font-weight-bold text-right">Highest Bid</th>
                    <th class="th-sm font-weight-bold text-right">Lowest Ask</th>
                    <th class="th-sm font-weight-bold text-right">Volume<?php echo " (".$settings["coin_symbol"].")" ?></th>
                    <th class="th-sm font-weight-bold text-right">Volume (BTC)</th>
                  </thead>
                  <tbody>
                    <?php 
                      foreach ($lmi->metrics as $m){
                        $p_split = splitFloat($m->latest,$def_precision,$def_precision);
                        $b_split = splitFloat($m->highest_bid,$def_precision,$def_precision);
                        $a_split = splitFloat($m->lowest_ask,$def_precision,$def_precision);
                        $v_split = splitFloat($m->quote_volume, $precision, $precision);
                        $t_split = splitFloat($m->base_volume, $def_precision, $def_precision);
                        $c_split = splitFloat($m->percent_change,2,2);
                        $m_link = getMarketLink($m->exchange,$m->base,$m->quote);
                        $m->percent_change = @cdec($m->percent_change);
                        if ($m->percent_change > 0) {
                          $p_class = "text-success";
                          $p_trend = '<i class="fas fa-long-arrow-alt-up ml-2" aria-hidden="true"></i>';
                        } else if ($m->percent_change < 0){
                          $p_class = "text-danger";
                          $p_trend = '<i class="fas fa-long-arrow-alt-down ml-2" aria-hidden="true"></i>';
                        } else {
                          $p_class = "grey-text";
                          $p_trend = '<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>';
                        }

                        echo "<tr><td class='text-left p-1'><a class='primary-text' ".((!empty($m_link)) ? "href='".$m_link."' target='_blank'" : "" )." >".$m->exchange."</a></td>
                        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : $p_class )."'>". $p_split->whole.".".$p_split->fraction."</td>
                        <td class='text-center p-1 " . ((!$bcs) ? "grey-text" : $p_class )."'>". $p_trend."&nbsp;".$c_split->whole.".".$c_split->fraction."%</td>
                        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : "" )."'>". $b_split->whole.".".$b_split->fraction."</td>
                        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : "" )."'>". $a_split->whole.".".$a_split->fraction."</td>
                        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : "" )."'>". $v_split->whole."<small>.".$v_split->fraction."</small></td>
                        <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : "" )."'>". $t_split->whole."<small>.".$t_split->fraction."</small></td>
                        </tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
          </div>
          <!--/.Card Data-->
          <!--Card content-->
          <!--/.Card content-->
        </div>
      </div>
    </div>
  </div>


</section>
<!--/Section: Button icon-->

<!--Section: Chart-->
<section>
  <div class="row">
    <div class="col-12 mb-4">
      <div class="card">
      <div class="row my-0 pr-3 pl-3 d-none d-sm-none d-md-block">
          <div class="col-12 py-3 text-center">
            <span >Price Evolution (last <?php echo $mo_days; ?> days)</span>
          </div>
          <div class="mb-3 col-12 chart-container text-center" style="height: 30em;">
            <canvas id="lcMnPerHour" height="100%"></canvas>
          </div>
        </div>
        <script>
          var ctxL = document.getElementById('lcMnPerHour').getContext('2d');
          var myLineChart = new Chart(ctxL, {
            type: 'line',
            height: '100%',
            data: {
              labels: [
                <?php  
                  foreach($gma->timestamps as $n=>$a){
                    echo "'" . $a . "',";
                  } 
                ?>
              ],
              datasets: [
                <?php 
                  $border_settings = "";
                  if ($mo_hours <= 168){
                    $border_settings = "
                    borderWidth: 2,
                    pointRadius: 1,
                    pointHoverRadius: 2,";
                  } else {
                    $border_settings = "
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 2,";
                  }
                  if (isset($settings["vwap_enabled"]) && $settings["vwap_enabled"] && isset($gma->vwap) && count($gma->markets)>1) { 
                    $hide = "true";
                    $vwap_values = "";
                    foreach ($gma->vwap as $k=>$v){
                        if (is_float($v)){ 
                                $vwap_values .= "'".( is_nan($v) ? "NaN" : round($v,$def_precision) ) ."',";
                        } else {
                                $vwap_values .= "'".( $v->isNaN() ? "NaN" : $v->toFixed($def_precision) ) ."',";
                        }
                      //$vwap_values .= "'".$v->toFixed($def_precision)."',";
                    }
                    echo "{
                      label: 'VWAP',
                      fill: false,
                      data: [".$vwap_values."],
                      borderColor: '".hex2rgba($settings["vwap_color"],'0.7')."',
                      backgroundColor: '".hex2rgba($settings["vwap_color"],'0.6')."',
                      ".$border_settings."
                      hidden: false,
                    },";
                  } else {
                    $hide = "false";
                  }
                  $i = 0;

                  foreach ($gma->markets as $market=>$values){
                    echo "{
                       label: '" .$market."',
                       fill: false,
                       data: [";
                    foreach ($values as $n=>$m){
                      //echo "'" . $m . "',";
                      echo "'".number_format((float)round((float)((string)$m),$def_precision),$def_precision,".","")."',";
                    } 
                    echo "   ],
                       borderColor: '".hex2rgba(getChartColorArray(count($gma->markets))[$i],0.7)."',
                       backgroundColor: '".hex2rgba(getChartColorArray(count($gma->markets))[$i],0.6)."',
                       ".$border_settings."
                       hidden: ".$hide.",
                     },";
                    $i+=1;
                  }
                ?>
              ]
            },
            options: {
              tooltips: {
                mode: 'x',
                callbacks: {
                  label: function(tooltipItem, data) {
                    var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || '';
                    return datasetLabel + ' : ' + tooltipItem.yLabel.toFixed(<?php echo $def_precision; ?>);
                  }
                }
              },
              responsive: true,
              maintainAspectRatio: false,
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
                    labelString: 'Price (BTC)',
                    display: true,
                  },
                  ticks: {
                    beginAtZero: true,
                    precision: <?php echo $def_precision; ?>,
                    callback: function(value, index, values) {
                      return value.toFixed(<?php echo $def_precision; ?>);
                    }
                  },
                }]
              },
            }
          });
          
        </script>
      </div>
    </div>
  </div>

</section>
<!--/Section: Chart-->

<?php 
  //var_dump(getMarketPriceAggregatesByHour(time(),36)); 
?>

<section class="d-none">
 <div class="row text-left mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0">$gbc_time=<?php echo $gbc_time; ?> ms</p>
    <p class="small my-0 py-0">$gcs_time=<?php echo $gcs_time; ?> ms</p>
    <p class="small my-0 py-0">$gma_time=<?php echo $gma_time; ?> ms</p>
   </div>
  </div>
 </div>
</section>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"markets",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>