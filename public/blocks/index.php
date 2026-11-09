<?php
  require_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';    
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php'; 
  include_once __DIR__ . '/../../lib/explorerDb.php';
  include_once __DIR__ . '/../../lib/explorerRpc.php';
  include_once __DIR__ . '/../../lib/timeTracker.php';
  $apt = new TimeTracker;
  $apt->createTimer("total",0);
  $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  $tt = new TimeTracker();

  date_default_timezone_set($settings["timezone"]);

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  global $description;
  $description = ui_getCoinName()." blocks: latest blocks, block spacing and blockchain height.";
  global $keywords;
  $keywords = $settings["title"].", chain blocks, block spacing, chain height, miners, transactions, movements, difficulty, confirmations"; 
  global $title;
  $title = ui_getCoinName()." Blocks | Last Blocks & Spacing Statistics";
  global $page_name;
  $page_name = $settings["title"]." - Blocks";
  global $page_id;
  $page_id = "Blocks";

  include_once __DIR__ . '/../includes/header.php';
?>
  <!--Main layout-->
  <main>
    <div id="content" class="container-fluid text-center">
      <!--Section: Button icon-->
      <section>
        <!--Grid row-->
        <div class="row">
          <!--Grid column I-->
          <div class="col-xl-6 col-lg-6 col-md-12 ">

              <!--Block Height Card-->
                <?php 
                  $tt->createTimer('db_getLastDbBlockNumber',0, true);
                  $db_bc = db_getLastDbBlockNumber();
                  $tt->stopTimer('db_getLastDbBlockNumber');
                  $tt->createTimer('getRpcBlockCount',0,true);
                  $rpc_bc = getRpcBlockCount();
                  $tt->stopTimer('getRpcBlockCount');
                  if ($db_bc < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }
                ?>
                <div class="card mb-4">

                  <!--Card Data-->
                  <div class="row pr-3 pl-3">
                    <div class="col-md-3 col-3 text-left">
                      <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-cubes card-icon-text" aria-hidden="true"></i></a>
                    </div>

                    <div class="col-md-9 col-9 text-right">
                      <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $db_bc; ?></h2>
                      <p class="font-small h4-responsive primary-text">EXPLORER BLOCK</p>
                    </div>
                  </div>
                  <!--/.Card Data-->

                  <!--Card content-->
                  <div class="row my-0 pr-3 pl-3">
                    <div class="mb-2 col-12 text-right">
                      <span class="font-small pr-3 h6-responsive grey-text">Daemon Block</span>
                      <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $rpc_bc; ?></span>
                    </div>
                  </div>
                  <!--/.Card content-->

                </div>
              <!--/.Block Height Card-->
          </div>
          <!--/.Grid column II-->

          <!--Grid column I-->
          <div class="col-xl-6 col-lg-6 col-md-12 ">    
              <!--24h Blocks Card-->
              <?php $et = time();
                    $st = $et - 86400;
                    $tt->createTimer('db_getBlocksByTimeRange',0,true);
                    $db_btr = db_getBlocksByTimeRange($st,$et,true,true); 
                    $tt->stopTimer('db_getBlocksByTimeRange');
                    $tt->createTimer('getTimeSinceLastExplorerBlock',0,true);
                    $db_tslb = getTimeSinceLastExplorerBlock();
                    $tt->stopTimer('getTimeSinceLastExplorerBlock');
              ?>
              <div class="card mb-4">

                <!--Card Data-->
                <div class="row pr-3 pl-3">
                  <div class="col-md-3 col-3 text-left">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-spinner card-icon-text" aria-hidden="true"></i></a>
                  </div>
                  <div class="col-md-9 col-9 text-right">
                    <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo count($db_btr); ?></h2>
                    <p class="font-small h4-responsive primary-text">BLOCKS WITHIN 24H</p>
                  </div>
                </div>
                <!--/.Card Data-->

                <!--Card content-->
                <div class="row my-0 pr-3 pl-3">
                  <div class="mb-2 col-12 text-right">
                    <span class="font-small pr-3 h6-responsive grey-text">Time Since Last Block</span>
                    <span class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $db_tslb->string; ?></span>
                  </div>
                </div>
                <!--/.Card content-->

              </div>
              <!--/.24h Blocks Card-->
            
          </div>
          <!--/.Grid column II-->
        </div>

        <!--Grid row-->
        <div class="row">
          <!--Grid column II-->
          <div class="col-md-12 mb-4">
              <!--Block Spacing Chart Card-->
              <?php 
                $tt->createTimer('getAverageBlockSpacing',0,true);
                $abs = getAverageBlockSpacing(1440, time())->string;
                $tt->stopTimer('getAverageBlockSpacing');
              ?>
              <div class="card" style="height:388px;">
                <!--Card Data-->
                <div class="row pr-3 pl-3">
                  <div class="col-md-3 col-3 text-left">
                    <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0"><i class="fas fa-chart-bar card-icon-text" aria-hidden="true"></i></a>
                  </div>

                  <div class="col-md-9 col-9 text-right">
                    <div class="ml-4 mt-3 mb-0 font-weight-bold"><span class="h5 font-weight-bold"><?php echo $abs; ?></span></div>
                    <p class="font-small primary-text"><?php echo "AVERAGE BLOCK SPACING (24H)"; ?></p>
                  </div>
                </div>
                <!--/.Card Data-->

                <!--Card content-->
                <div class="row my-0 pr-3 pl-3 ">
                  <div class="mb-3 col-12 chart-container text-center" style="height: 25em;">
                    <canvas id="scBlockSpacing" height="100%"></canvas>
                  </div>
                </div>
                <script nonce="<?php echo $csp_nonce; ?>">
                  $(document).ready(function() {
                    var ctxSc = document.getElementById('scBlockSpacing').getContext('2d');
                    var scData = {
                      datasets: [{
                        borderColor: '<?php echo hex2rgba(getNamedColor("primary"),0.6); ?>',
                        backgroundColor: '<?php echo hex2rgba(getNamedColor("primary"),0.5); ?>',
                        pointRadius: 1,
                        pointHoverRadius: 2,
                        label: 'Block Spacing',
                        data: [
                          <?php 
                            for ($i = 1 ; $i <= count($db_btr) - 2; $i++){
                              echo "{x:" . ($db_btr[$i]->block_ix) . ",y:" . ($db_btr[$i]->block_time_sec - $db_btr[$i-1]->block_time_sec ) . "},";
                            }
                          ?>
                        ]
                      },
                      {
                        borderColor: '<?php echo hex2rgba(getNamedColor("danger"),0.8); ?>',
                        backgroundColor: '<?php echo hex2rgba(getNamedColor("danger"),0.7); ?>',
                        pointRadius: 3,
                        pointHoverRadius: 4,
                        label: 'Last Block Spacing',
                        data: [
                          <?php 
                              if (count($db_btr)>0){
                                echo "{x:" . ($db_btr[count($db_btr) - 1]->block_ix) . ",y:" . ($db_btr[count($db_btr) - 1]->block_time_sec - $db_btr[count($db_btr) - 2]->block_time_sec ) . "},";
                              }
                          ?>
                        ]
                      }]
                    }
                    var scConfig = new Chart.Scatter(ctxSc, {
                      data: scData,
                      height: '100%',
                      options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: {
                          duration: 0,
                        },
                        title: {
                          display: true,
                          text: 'Block Spacing (last 24h)'
                        },
                        scales: {
                          xAxes: [{
                            type: 'linear',
                            position: 'bottom',
                            scaleLabel: {
                              labelString: 'Block Height',
                              display: true,
                            }
                          }],
                          yAxes: [{
                            type: 'linear',
                            ticks: {
                              beginAtZero: true
                            },
                            scaleLabel: {
                              labelString: 'Block Spacing (s)',
                              display: true
                            }
                          }]
                        }
                      }
                    });
                  });
                </script>
                <!--/.Card content-->
              </div>
              <!--/.Block Spacing Chart Card-->
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
            <!--Block List Card-->
            <div class="card">
              <div class="card-header card-header primary-color card-header-text py-2 text-left">
                <i class="fas fa-cubes mr-1" aria-hidden="true"></i> BLOCK LIST
              </div>

              <!--Card content-->
              <div class="card-body table-responsive pb-0 text-nowrap">
                <table id="dtBlockList" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
                  <thead>
                    <tr class="table-secondary text-center">
                      <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                      <th class="th-sm">Height</th>
                      <th class="th-sm">Block Hash</th>
                      <th class="th-sm">Confirmations</th>
                      <th class="th-sm">Size (kB)</th>
                      <th class="th-sm">TXs</th>
                      <th class="th-sm">Movement</th>
                    </tr>
                  </thead>
                </table>
              </div>
              <!--/.Card content-->
            </div>
            <!--/.Block List Card-->
          </div>      
        </div>
      </section>
      <!--/Section: Blocks DataTable-->

      <script defer nonce="<?php echo $csp_nonce; ?>" type='text/javascript'>
        $(document).ready(function() {
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
              "url":"<?php echo $settings["explorer_address"]; ?>/_dp/blocksdp.php",
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
            {"targets": 6, "className": "text-right"},
            {"targets": "_all", "className": " text-center"}
            ]
          });
          $('.dataTables_length').addClass('d-none');
          $('.dataTables_processing').addClass('pl-3 primary-text');
          $('.dataTables_info').addClass('pt-4');
        })

      </script>

      <section class="d-none">
        <div class="row mt-3">
          <div class="col-12">
            <div class="card card-info p-3">
              <p class="small my-0 py-0" id="gbl_time"></p>
              <?php 
                foreach ($tt->getCollection() as $name=>$timer){
                  print("<p class='small my-0 py-0'>".$name." = ".$timer->getDurationMilliseconds()." ms</p>");
                }
              ?>
          </div>
          </div>
        </div>
      </section>
    </div>
</main>
<!--/Main layout-->

<?php
  include_once __DIR__ . '/../includes/footer.php';

  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"blocks",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
