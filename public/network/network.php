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
?>
<?php 
  $stime = milliseconds();
  $blk = rpc_getblockcount();
  if (is_null($blk) && $blk == -1){
    $blk = -2;
  }
  $peers = getPeersInfo();
  $con = count($peers->outbound) + count($peers->inbound);
  $gpi_time = milliseconds() - $stime;
?>
<!--Section: Button icon-->
<section>
<!--Grid row-->
<div class="row">
    <!--Grid column I-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <!--Connection Count Card-->
      <div class="card col-12 pb-3">

        <!--Card Data-->
        <div class="row pr-3 pl-3">
          <div class="col-md-3 col-3 text-left">
            <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-network-wired card-icon-text" aria-hidden="true"></i></a>
          </div>

          <div class="col-md-9 col-9 text-right">
            <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold"><?php echo $con; ?></h2>
            <p class="font-small h4-responsive primary-text"><?php echo "CONNECTIONS COUNT"; ?></p>
          </div>
        </div>
        <div id="topContainer"> 
        <div class="row px-3 pb-3">
          
          <div class="table-container col-12" id="topTableContainer">
            <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
              <tbody>
                <?php 
                  // Outbound
                  echo "<tr><td class='text-left p-1'>Outbound </td>";
                  echo "<td class='text-right p-1'><span>". count($peers->outbound) ."</span></td></tr>";
                  // Inbound
                  echo "<tr><td class='text-left p-1'>Inbound </td>";
                  echo "<td class='text-right p-1'><span>". count($peers->inbound) . "</span></td></tr>";
                ?>
              </tbody>
            </table>
            <br>
            <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
              <tbody>
                <?php 
                  // IPv4
                  echo "<tr><td class='text-left p-1'>IPv4 Peers</td>";
                  echo "<td class='text-right p-1'><span>". $peers->ipv4count ."</span></td></tr>";
                  // IPv6
                  echo "<tr><td class='text-left p-1'>IPv6 Peers</td>";
                  echo "<td class='text-right p-1'><span>". $peers->ipv6count ."</span></td></tr>";
                ?>
              </tbody>
            </table>
          </div>
        </div>
        <!--/.Card Data-->
        <!--Card content-->
        <!-- Button trigger modal -->
        <div class="col-12" >
          <button type="button" class="btn btn-info col-12 py-2" data-toggle="modal" data-target="#addnodeModal">
            Show Addnode List
          </button>
        </div>
        <!--/.Card content-->
      </div>
      </div>
      <!--/.Connection Count Card-->
    </div>
    <!--/.Grid column I-->

    <!--Grid column II-->
      <?php 
        $keys='';
        $values='';
        arsort($peers->clients);
        foreach ($peers->clients as $key => $value) {
          $keys = $keys.json_encode((string)$key, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP).",";
          $values = $values.$value.',';
        }
      ?>
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
        <div class="card col-12 p-0">
          <div class="card-header primary-color card-header-text  py-2 text-left">
            <span class="h6 h6-responsive "><i class="fas fa-code" aria-hidden="true"></i> CLIENT VERSIONS </span>
          </div>
        <!--Card content-->
          <div class="row my-0 px-3 pb-3">
            <div class="col-12 mt-3 pt-3">
              <div class="chart-container text-center" id="clientChartContainer">
                <canvas id="dcClientsDistribution"></canvas>
              </div>
            </div>
          </div>
          <script>
            var ctxSc = document.getElementById('dcClientsDistribution').getContext('2d');
            var dcData = {
              labels: [<?php echo $keys; ?>],
              datasets: [{
                data: [<?php echo $values; ?>],
                backgroundColor:  [<?php echo getChartColors(count($peers->clients),1); ?>],
                hoverBorderWidth: 1,
                hoverBorderColor: [<?php echo getChartColors(count($peers->clients),1); ?>],
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
                animation: {
                  //duration: 0,
                },
              }
            });
            $(document).ready(function(){
              $( "#clientChartContainer" ).height( $( "#topContainer" ).height() );
              });
          </script>
          <!--/Card content-->
        <!--Card Data-->
        </div>
      </div>
    <!--/.Grid column II-->
  </div>
  <!--Grid row-->
</section>
<!--/Section: Button icon-->

<!--Section: Peer list icon-->
<section>
  <!--Grid row-->
  <div class="row">

    <!--Grid column I-->
    <div class="col-md-12 flex-col mb-4 ">
      <div class="card col-12 p-0">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-upload" aria-hidden="true"></i> OUTBOUND CONNECTIONS </span>
        </div>
        <?php 
          if (count($peers->outbound)>0){
            $o_vis = '';
          } else {
            $o_vis='d-none';
          }
        ?>
        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <div class="table-container <?php echo $o_vis; ?>">
            <table class="table table-hover table-bordered table-sm" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Protocol</th>
                  <th class="th-sm">Client</th>
                  <th class="th-sm">Ping (ms)</th>
                  <th class="th-sm">Blocks&nbsp;/&nbsp;Headers</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                foreach ($peers->outbound as $peer){
                  $ping_ms = splitFloat($peer->pingtime*1000, 0, 0);
                  if ($ping_ms->whole > 300) { $ping_color = ' text-danger'; } else {$ping_color = '';}
                  echo "<tr><td class='text-left p-1'>".$peer->ip.':'.$peer->port."</td>";
                  echo "<td class='text-center p-1'>". $peer->protocol."</td>";
                  echo "<td class='text-center p-1'>". h($peer->client)."</td>";
                  echo "<td class='text-center ".$ping_color." p-1'>".$ping_ms->whole."</td>";
                  echo "<td class='text-center p-1 ".(($blk == $peer->blocks) ? "text-success": "")."'>".$peer->blocks."&nbsp;/&nbsp;".$peer->headers."</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
        <!--/.Card content-->
      </div>
    </div>
    <!--/Grid column I-->
        <!--Grid column II-->
        <div class="col-md-12 flex-col mb-4">
        <div class="card col-12 p-0">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-download" aria-hidden="true"></i> INBOUND CONNECTIONS </span>
        </div>
        <?php 
          if (count($peers->inbound)>0){
            $i_vis = '';
          } else {
            $i_vis='d-none';
          }
        ?>
        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <div class="table-container <?php echo $i_vis; ?>">
            <table class="table table-hover table-bordered table-sm" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Address</th>
                  <th class="th-sm">Protocol</th>
                  <th class="th-sm">Client</th>
                  <th class="th-sm">Ping (ms)</th>
                  <th class="th-sm">Blocks&nbsp;/&nbsp;Headers</th>
                </tr>
              </thead>
              <tbody>
                <?php 
                foreach ($peers->inbound as $peer){
                  $ping_ms = splitFloat($peer->pingtime*1000, 0, 0);
                  if ($ping_ms->whole > 300) { $ping_color = ' text-danger'; } else {$ping_color = '';}
                  echo "<tr><td class='text-left p-1'>".$peer->ip.':'.$peer->port."</td>";
                  echo "<td class='text-center p-1'>". $peer->protocol."</td>";
                  echo "<td class='text-center p-1'>". h($peer->client)."</td>";
                  echo "<td class='text-center ".$ping_color." p-1'>".$ping_ms->whole."</td>";
                  echo "<td class='text-center p-1 ".(($blk == $peer->blocks) ? "text-success": "")."'>".$peer->blocks."&nbsp;/&nbsp;".$peer->headers."</td></tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
        <!--/.Card content-->
      </div>
    </div>
    <!--/Grid column II-->
  </div>
  <!--/Grid row-->
</section>
<!--/Section: Peer list icon-->

<section class="d-none">
 <div class="row text-left mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0">$gpi_time=<?php echo $gpi_time; ?> ms</p>

   </div>
  </div>
 </div>
</section>
<!-- Central Modal Small -->
<div class="modal fade" id="addnodeModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel"
  aria-hidden="true">

  <!-- Change class .modal-sm to change the size of the modal -->
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title w-100 primary-text" id="modalLabel">Addnode List</h4>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-justify p-3">
        <p>In case you experience difficulties to synchronize your wallet  with blockchain, 
          please use below 'addnode' list to establish connection to network peers.</p>
        <div class="light-color px-3 py-2">
          <code>
            <?php 
              foreach ($peers->outbound as $peer) {
                echo "addnode=".$peer->ip.":".$peer->port."<br>";
              }
            ?>
          </code>
        </div>
        <p class="pt-2">Add above list to wallet configuration file and re-start client.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-info btn-sm" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<!-- Central Modal Small -->
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"network",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>