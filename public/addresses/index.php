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
  $precision = (int)$settings["overview_precision"];

  global $csp_nonce;
  $csp_nonce = random_hex(8);  

  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

  global $description;
  $description = "Leaderboard of ".ui_getCoinName()." addresses by balance, coin supply and circulation.";
  global $keywords;
  $keywords = $settings["title"].", top 100, addresses, rich list, ranking, supply concentration";
  global $title;
  $title = "Top ".ui_getCoinName()." Addresses | Balances & Supply Share";
  global $page_name;
  $page_name = $settings["title"]." - Top 100 Addresses";
  global $page_id;
  $page_id = "Addresses";

// get data from database
$data = ui_GetTopAddressesData();

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
            <div class="col-xl-6 col-lg-6 col-md-6 flex-col mb-4">

              <!--Coin Supply Card-->
              <div class="card col-12">

                <!--Card Data-->
                <div class="row pr-3 pl-3">
                  <div class="col-md-3 col-3 text-left">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-coins card-icon-text" aria-hidden="true"></i></a>
                  </div>

                  <div class="col-md-9 col-9 text-right">
                    <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $data->coin_supply->circulation->whole."."; ?><small><?php echo $data->coin_supply->circulation->fraction; ?></small></h2>
                    <p class="font-small h4-responsive primary-text"><?php echo "COIN CIRCULATION (".$settings["coin_symbol"].")"; ?></p>

                    <?php if (!isset($settings["show_burned"]) || $settings["show_burned"] == true) : ?>
                      <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h6 font-weight-bold"><?php echo $data->coin_supply->burned->whole; ?><small>.<?php echo $data->coin_supply->burned->fraction; ?></small></span></div>        
                      <p class="font-small primary-text"><?php echo "BURNED AMOUNT (".$settings["coin_symbol"].")"; ?></p>
                    <?php endif; ?>  

                    <?php if(@cdec($data->coin_supply->masternode_locked_coins->number) > 0 ) :  ?>
                    <div class="ml-4 mt-3 mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?> ">
                      <span class="font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">
                        <?php 
                          if(@cdec($data->coin_supply->masternode_locked_coins->number) > 0) { 
                            echo $data->coin_supply->masternode_locked_coins->whole."<small>.".$data->coin_supply->masternode_locked_coins->fraction."</small>&nbsp;/&nbsp;".
                                $data->coin_supply->masternode_locked_percent->whole."<small>.".$data->coin_supply->masternode_locked_percent->fraction."</small>%"; 
                          } else { 
                            echo "0";
                          }
                        ?>
                      </span>
                    </div>
                    <p class="font-small h5-responsive primary-text"><?php echo "LOCKED IN MASTERNODES (".$settings["coin_symbol"].")"; ?></p>
                    <?php endif; ?>

                    <?php if (isset($settings["show_zerocoin"]) && $settings["show_zerocoin"]) : ?>
                      <div class="ml-4 mt-3 mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?> <?php if(@cdec($data->coin_supply->zerocoin_supply->number) == 0) { echo "d-none"; }  ?>"><span class="font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">
                        <?php if(@cdec($data->coin_supply->zerocoin_supply->number) <> 0) { echo $data->coin_supply->zerocoin_supply->string; } else { echo "";} ?></span></div>
                      <p class="font-small h5-responsive primary-text <?php if(@cdec($data->coin_supply->zerocoin_supply->number) == 0) { echo "d-none"; } ?>"><?php echo "ZEROCOIN CIRCULATION (".$settings["coin_symbol"].")"; ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <!--/.Card Data-->
              </div>
              <!--/.Coin Supply Card-->
            </div>
            <!--/.Grid column I-->

            <!--Grid column II-->
            <div class="col-xl-6 col-lg-6 col-md-6 flex-col mb-4">

              <!--Coin Supply Card-->
              <div class="card col-12">

                <!--Card Data-->
                <div class="row pr-3 pl-3">
                  <div class="col-md-3 col-3 text-left">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0"><i class="fas fa-sign-out-alt card-icon-text" aria-hidden="true"></i></a>
                  </div>

                  <div class="col-md-9 col-9 text-right">
                    <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h6 font-weight-bold"><?php echo $data->coin_supply->pow_supply->whole; ?><small>.<?php echo $data->coin_supply->pow_supply->fraction; ?></small></span></div>
                    <p class="font-small primary-text"><?php echo "POW COIN SUPPLY (".$settings["coin_symbol"].")"; ?></p>
                    <?php if (isset($settings['show_pages']['staking']) && $settings['show_pages']['staking']) : ?>
                      <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h6 font-weight-bold"><?php echo $data->coin_supply->pos_supply->whole; ?><small>.<?php echo $data->coin_supply->pos_supply->fraction; ?></small></span></div>
                      <p class="font-small primary-text"><?php echo "POS COIN SUPPLY (".$settings["coin_symbol"].")"; ?></p>
                    <?php endif; ?>
                    <?php if (isset($data->coin_supply->airdrop_supply)) : ?>
                      <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h6 font-weight-bold"><?php echo $data->coin_supply->airdrop_supply->whole; ?><small>.<?php echo $data->coin_supply->airdrop_supply->fraction; ?></small></span></div>
                      <p class="font-small primary-text"><?php echo "AIRDROP SUPPLY (".$settings["coin_symbol"].")"; ?></p>
                    <?php endif; ?>
                    <?php if ((!isset($settings["show_illegal_supply"]) || $settings["show_illegal_supply"]) && @cdec($data->coin_supply->illegal_supply->number) > 0) : ?>
                      <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h6 font-weight-bold"><?php echo $data->coin_supply->illegal_supply->whole; ?><small>.<?php echo $data->coin_supply->illegal_supply->fraction; ?></small></span></div>
                      <p class="font-small primary-text"><?php echo "ILLEGAL SUPPLY (".$settings["coin_symbol"].")"; ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <!--/.Card Data-->
              </div>
              <!--/.Coin Supply Card-->
            </div>
            <!--/.Grid column II-->
        </div>
        <!--Grid row-->
      </section>
      <!--/Section: Button icon-->

      <!--Section: Coin distribution chart-->
      <section>
        <!--Grid row-->
        <div class="row">
          <!--Grid column I-->
          <div class="col-xl-6 col-lg-6 col-md-6 flex-col mb-4">
            <div class="card col-12" style="min-height:231px;">
              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0"><i class="fas fa-chart-bar card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h5 font-weight-bold"><?php echo $data->distribution->q_total->amount->whole."."; ?><small><?php echo $data->distribution->q_total->amount->fraction; ?></small></span></div>
                  <p class="font-small primary-text"><?php echo "COINS ON TOP100 ADDRESSES (".$settings["coin_symbol"].")"; ?></p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class=" col-12 p-3">
                  <div class="chart-container text-center" id="topChartContainer" style="min-height:1px;">
                    <canvas id="dcCoinDistribution" class="" height="130"></canvas>
                  </div>
                </div>
              </div>
              <script nonce="<?php echo $csp_nonce;?>" >
                $(document).ready(function(){
                  var ctxSc = document.getElementById('dcCoinDistribution').getContext('2d');
                  var dcData = {
                    labels: ["Top 1-25", "Top 26-50", "Top 51-75", "Top 76-100", "Other"],
                    datasets: [{
                      data: [<?php echo $data->distribution->q1->percent->string.",".$data->distribution->q2->percent->string.",".$data->distribution->q3->percent->string.",".$data->distribution->q4->percent->string.",".$data->distribution->other->percent->string; ?>],
                      backgroundColor: [<?php echo getChartColors(4,1); ?>],
                      hoverBorderWidth: 1,
                      hoverBorderColor: [<?php echo getChartColors(4,1); ?>],
                    }]
                  }
                  var scConfig = new Chart(ctxSc, {
                    type: 'doughnut',
                    data: dcData,
                    options: {
                      maintainAspectRatio: false,
                      responsive: true,
                      legend: {
                        display: true,
                        position: 'right',
                      },
                      tooltips: {
                        callbacks: {
                          label: function(tooltipItem, data) {
                            var i = tooltipItem.index;
                            return data.labels[i] + ': '+ data.datasets[0].data[i] + '%';
                          }
                        }
                      },
                      animation: {
                        //duration: 0,
                      },
                    }
                  });
                  //$( "#topChartContainer" ).height( $( "#topTableContainer" ).height());
                });
              </script>
              <!--/Card content-->
            </div>
            <!--/Card Data-->
          </div>
          <!--/Grid column I-->

          <!--Grid column II-->
          <div class="col-xl-6 col-lg-6 col-md-6 flex-col mb-4">
            <div class="card col-12"  style="min-height:231px;">
              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0"><i class="fas fa-table card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h5 font-weight-bold"><?php echo $data->distribution->q_total->percent->whole."."; ?><small><?php echo $data->distribution->q_total->percent->fraction; ?></small> %</span></div>
                  <p class="font-small primary-text"><?php echo "COIN SUPPLY CONCENTRATION"; ?></p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="card-body table-responsive text-nowrap">
                <div class="table-container" id="topTableContainer">
                  <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
                    <tbody>
                      <?php 
                        // Top 1-25
                        echo "<tr><td class='text-left p-1'>Top 1-25</td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q1->amount->whole.".<small>".$data->distribution->q1->amount->fraction."</small></span></td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q1->percent->whole .".<small>". $data->distribution->q1->percent->fraction ."</small>%</span></td></tr>";
                        // Top 26-50
                        echo "<tr><td class='text-left p-1'>Top 26-50</td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q2->amount->whole.".<small>".$data->distribution->q2->amount->fraction."</small></span></td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q2->percent->whole .".<small>". $data->distribution->q2->percent->fraction ."</small>%</span></td></tr>";
                        // Top 51-75
                        echo "<tr><td class='text-left p-1'>Top 51-75</td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q3->amount->whole.".<small>".$data->distribution->q3->amount->fraction."</small></span></td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q3->percent->whole .".<small>". $data->distribution->q3->percent->fraction ."</small>%</span></td></tr>";
                        // Top 76-100
                        echo "<tr><td class='text-left p-1'>Top 76-100</td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q4->amount->whole.".<small>".$data->distribution->q4->amount->fraction."</small></span></td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->q4->percent->whole .".<small>". $data->distribution->q4->percent->fraction ."</small>%</span></td></tr>";
                        // Other
                        echo "<tr><td class='text-left p-1'>Other</td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->other->amount->whole.".<small>".$data->distribution->other->amount->fraction."</small></span></td>";
                        echo "<td class='text-right p-1'><span>". $data->distribution->other->percent->whole.".<small>". $data->distribution->other->percent->fraction ."</small>%</span></td></tr>";
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/.Card content-->
            </div>
          </div>
        </div>
      </section>
      <!--/Section: Coin distribution chart-->

      <!--Section: Top 100 addresses table-->
      <section>
        <div class="row">
          <div class="col-md-12">
            <!--Top 100 List Card-->
            <div class="card mb-4">
              <div class="card-header card-header primary-color card-header-text py-2">
              <span class="h4 h4-responsive"><i class="fas fa-at mr-1" aria-hidden="true"></i> Top 100 Addresses List</span>
              </div>

              <!--Card content-->
              <div class="card-body table-responsive text-nowrap">
                <div class="table-container">
                  <table class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
                    <thead>
                      <tr class="table-secondary text-center">
                        <th class="th-sm">Rank</th>
                        <th class="th-sm">Address</th>
                        <th class="th-sm">Balance</th>
                        <th class="th-sm">Percent</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php 
                        foreach ($data->top_addresses as $rank => $address){
                         // $at = db_getAddressTags($address);
                          $tag_str = "";
                          if (!empty($address->alias)){
                            $tag_str.= "<span class='badge badge-info ml-1 mb-1'>".$address->alias."</span>&nbsp;";
                          }            
                          if (isset($address->tags)&& count($address->tags) > 0){
                            foreach ($address->tags as $ix => $tag){
                              $tag_str.= "<span class='badge badge-primary ml-1 mb-1'>".$tag."</span>&nbsp;";
                            }
                          }
                          // $a_split = splitFloat(($amount), $precision, $precision);
                          // $p_split = splitFloat((100*@cdec($amount)/($dbs->coincirculation)),2,2);
                          echo "<tr><td class='text-center p-1'>".$rank."</td>";
                          echo "<td class='text-left p-1'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$address->addr."'>".$address->addr."</a>".$tag_str."</td>";
                          echo "<td class='text-right p-1'><span class=''>". $address->bal->whole.".<small>".$address->bal->fraction."</small></span></td>";
                          echo "<td class='text-right p-1'><span class=''>". $address->bal_percent->whole.".<small>".$address->bal_percent->fraction."%</small></span></td></tr>";
                        }
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Top 100 List Card-->
          </div>      
        </div>
      </section>
      <!--/Section: Top 100 addresses table-->

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
    ui_LogPageEvent($cip,"addresses",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
