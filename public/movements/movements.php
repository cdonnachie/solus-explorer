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
  $tt = new TimeTracker();
  global $tt;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["overview_precision"];
  $def_precision = (int)$settings["default_precision"];
  
  $stime = milliseconds();
  $ldb = db_getLastDbBlockNumber();
  $ldb_time = milliseconds() - $stime;

  $qtypes = array("period" => "Query By Period", "block" => "Query By Block Range");
  if (isset($_POST["qType"])){
    $qTypeSelected = sanitizeInput("qType","POST");
  } else {
    $qTypeSelected = "period";
  }

  $periods = array(1=>"For Last Day",7=>"For Last 7 Days",30 =>"For Last 30 Days",-1=>"For All Time");
  if (isset($_POST["pDuration"])){
    $pSelected = (int)sanitizeInput("pDuration","POST");
  } else {
    $pSelected = 7;
  }

  if (isset($_POST["bStart"])){
    $bStart = (int)sanitizeInput("bStart","POST");
  } else {
    $bStart = $ldb - 1440;
  }
  if (isset($_POST["bEnd"])){
    $bEnd = (int)sanitizeInput("bEnd","POST");
  } else {
    $bEnd = $ldb;
  }
  //var_dump($_POST["rHide"]);
  if (isset($_POST["rHide"])){
    $rHide = sanitizeInput("rHide","POST");
  } else {
    $rHide = "false";
  }
  ($rHide == "true") ? $hide_rewards = true : $hide_rewards = false;
  //var_dump($hide_rewards);
?>
<?php 
  if ($qTypeSelected == "period" ){
    ($pSelected >=0 ) ? $start_time = time()-$pSelected*86400  : $start_time = 0;
    $stime = milliseconds();
    $top_sam = db_getTopAddressMovesByTimeRange($start_time, time(),10,true,true,true,$hide_rewards, true);
    $gts_time = milliseconds() - $stime;
    $stime = milliseconds();
    $top_ram = db_getTopAddressMovesByTimeRange($start_time, time(),10,false,true,true,$hide_rewards, true);
    $gtr_time = milliseconds() - $stime;
  } else if ( $qTypeSelected == "block" ){
    $stime = milliseconds();
    $top_sam = db_getTopAddressMovesByBlockRange($bStart,$bEnd,10,true,true,true,$hide_rewards, true);
    $gts_time = milliseconds() - $stime;
    $stime = milliseconds();
    $top_ram = db_getTopAddressMovesByBlockRange($bStart,$bEnd,10,false,true,true,$hide_rewards, true);
    $gtr_time = milliseconds() - $stime;
  }

?>

<div id="result">
<!--Section: Button icon-->
<section>
  <!--Grid row 2-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <div class="card col-12 px-0">
        <div class="card-header primary-color card-header-text py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-filter" aria-hidden="true"></i> QUERY SETUP </span>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-12 ">
              <select id="qType" class="custom-select">
                <?php 
                  foreach($qtypes as $val=>$text){
                    if ($val == $qTypeSelected) {$sel = "selected";} else {$sel = '';}
                    echo "<option value='".$val."' ".$sel.">".$text."</option>";
                  }
                ?>
              </select>
            </div>
          </div>
          <div  id="pDurationContainer" class="row">
            <div class="col-12">
              <select id="pDuration" class="custom-select">
              <?php 
                  foreach($periods as $val=>$text){
                    if ($val == $pSelected) {$sel = "selected";} else {$sel = '';}
                    echo "<option value='".$val."' ".$sel.">".$text."</option>";
                  }
                ?>
              </select>
            </div>
          </div>
          <div id="bRangeContainer" class="row" >
            <div class="col-md-6 text-left px-3 m-0">
              <div class="md-form m-0">
                <input type="text" class="form-control" id="bRangeStart" placeholder="From Block" value="<?php echo $bStart; ?>">
              </div>
            </div>
            <div class="col-md-6 text-left px-3 m-0">
              <div class="md-form m-0">
                <input type="text" class="form-control" id="bRangeEnd" placeholder="To Block" value="<?php echo $bEnd; ?>">
              </div>
            </div>
                
          </div>
          <div class="row">
            <div class="col-sm-6  pb-0">
              <div class="form-check text-left pt-3">
                <input type="checkbox" class="form-check-input" id="cbRewards" <?php if ($hide_rewards) { echo "checked"; }?>>
                <label class="form-check-label primary-text" for="cbRewards">Hide Block Rewards</label>
              </div>
            </div>
            <div class="col-sm-6 text-right pb-0">
              <button id="btnUpdate" type="button" class="btn btn-info col-12 py-2"  style="width: 10rem;">
                UPDATE 
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script type="text/javascript">

    // query type / period selects
      $(document).ready(function() {
        var qtype = $('#qType').val();
          switch(qtype) {
            case 'period':
              $('#pDurationContainer').show();
              $('#bRangeContainer').hide();
              break;
            case 'block':
              $('#pDurationContainer').hide();
                $('#bRangeContainer').show();
                break;
          }
        $( "#btnUpdate" ).click(function( event ) {
          $('#result').hide();
          $('#content').load( "spinner.php");
          $('#content').load( "movements.php", { qType: $('#qType').val(), pDuration:  $('#pDuration').val(), bStart: $('#bRangeStart').val(), bEnd: $('#bRangeEnd').val(), rHide: $('#cbRewards').is(':checked') }, function() {});
        });
        $('#qType').change(function(){
          var qtype = $('#qType').val();
          switch(qtype) {
            case 'period':
              $('#pDurationContainer').show();
              $('#bRangeContainer').hide();
              break;
            case 'block':
              $('#pDurationContainer').hide();
                $('#bRangeContainer').show();
                break;
          }
        });
      });
    </script>
    <!--Grid column II-->
    <?php 
      $stime = milliseconds();
      $gcs = ui_getCoinSupplyChartData();
      $gcs_time = milliseconds() - $stime;
    ?>
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <div class="card col-12 pb-3 px-0">
        <div class="card-header primary-color card-header-text py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-coins" aria-hidden="true"></i> COIN CIRCULATION </span>
        </div>
        <div class="mb-2 col-12 text-right">
          <canvas id="lcCsPerDay" height="120px"></canvas>
        </div>
        <script>
          var ctxL = document.getElementById('lcCsPerDay').getContext('2d');
          var myLineChart = new Chart(ctxL, {
            type: 'line',
            data: {
              spanGaps: false,
              labels: [
                <?php  
                  foreach($gcs as $ts=>$m){
                    echo "'" . $ts . "',";
                  } 
                ?>
              ],
              datasets: [{
                  label: "Coin Circulation",
                  fill: false,
                  data: [
                    <?php  
                      foreach ($gcs as $ts=>$m){
                        echo "'" . $m . "',";
                      } 
                    ?>
                  ],
                  borderColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.7); ?>',
                  backgroundColor: '<?php echo hex2rgba(getChartColorArray(1)[0],0.6); ?>',
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
                position: 'top',
              },
              scales: {
                yAxes: [{
                  scaleLabel: {
                    labelString: 'Coins (<?php echo $settings['coin_symbol']; ?>)',
                    display: true
                  },
                  ticks: {
                    beginAtZero: true,
                    callback: function(label, index, labels){
                        return normalizeMoney(label,1);
                      },
                      maxTicksLimit: 1,
                  },
                }]
              }
            }
          });
          
        </script>
      </div>
    </div>

  </div>
  <!--/.Grid row 2-->

</section>
<!--/Section: Button icon-->

<!--Section: Top Tables-->
<section>
  <!--Grid row 2-->
  <div class="row">
    <!--Grid column I-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <div class="card col-12 pb-3 px-0">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-sign-out-alt" aria-hidden="true"></i> TOP SPENT </span>
        </div>
        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <div class="table-container" id="topSpentTableContainer">
            <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm px-2 text-left">Address</th>
                  <th class="th-sm px-2">Block Height</th>
                  <th class="th-sm px-2 text-right">Amount (<?php echo $settings["coin_symbol"]; ?>)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php
                  foreach ($top_sam as $am){
                    $ai = db_getAddressData($am->address)[0];
                    $at = db_getAddressTags($ai->_id);
                    $tag_str = "";
                    $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                    $am_split = splitFloat(dflt($am->amount),$def_precision,$def_precision);
                    echo "<tr><td class='text-left py-1 px-2'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str."</td>
                    <td class='text-center py-1 px-2'><a class='' href='".$settings["explorer_address"]."/block/?blockheight=".$am->block_ix."' title='Navigate to Block'>".$am->block_ix."</a>&nbsp;&nbsp;<small><a class='' href='../tx/?txid=".$am->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a></small></td>
                    <td class='text-right py-1 px-2 text-danger'>".$am_split->whole."<small>.".$am_split->fraction."</small></td><tr>";
                  }

                  ?>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <!--Grid column II-->
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4">
      <div class="card col-12 pb-3 px-0">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-sign-in-alt" aria-hidden="true"></i> TOP RECEIVED </span>
        </div>
        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <div class="table-container" id="topReceivedTableContainer">
            <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm px-2 text-left">Address</th>
                  <th class="th-sm px-2">Block Height</th>
                  <th class="th-sm px-2 text-right">Amount (<?php echo $settings["coin_symbol"]; ?>)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <?php
                  foreach ($top_ram as $am){
                    $ai = db_getAddressData($am->address)[0];
                    $at = db_getAddressTags($ai->_id);
                    $tag_str = "";
                    $tag_str .= ui_labelBadges($at->address_alias, $at->group_tags, 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                    $am_split = splitFloat(dflt($am->amount),$def_precision,$def_precision);
                    echo "<tr><td class='text-left py-1 px-2'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str."</td>
                    <td class='text-center py-1 px-2'><a class='' href='".$settings["explorer_address"]."/block/?blockheight=".$am->block_ix."' title='Navigate to Block'>".$am->block_ix."</a>&nbsp;&nbsp;<small><a class='' href='../tx/?txid=".$am->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a></small></td>
                    <td class='text-right py-1 px-2 text-success'>".$am_split->whole."<small>.".$am_split->fraction."</small></td><tr>";
                  }

                  ?>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--/.Grid row 2-->
</section>
<!--/Section: Top Tables-->

<!--Section: Movements List-->
<section>
  <div class="row">
    <div class="col-12 mb-4">
      <div class="card">
        <div class="card-header primary-color card-header-text  py-2 text-left">
          <span class="h6 h6-responsive "><i class="fas fa-exchange-alt" aria-hidden="true"></i> Movements List </span>
        </div>
        <div class="card-body table-responsive text-nowrap">
          
          <table id="dtMovementsList" class="table table-striped table-bordered table-hover table-sm m-0" cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                <th class="th-sm">Address</th>
                <th class="th-sm">Transaction Hash</th>
                <th class="th-sm">Confirmations</th>
                <th class="th-sm">Amount</th>
              </tr>
            </thead>
          </table>
          
        </div>
      </div>
    </div>
  </div>
  <script type='text/javascript'> 
    // Configure Datatable
      $('#dtMovementsList').DataTable({
        "serverSide": true,
        "ordering": false,
        "searching": false,
        "pageLength": 20,
        "lengthChange": false,
        "scrollX": true,
        "pagingType": "simple_numbers",
        "processing": true,
        "ajax": {
          "url":"<?php echo $settings["explorer_address"]; ?>/_dp/movementsdp.php",
          "type":"POST",
          "data": {
            "qType":"<?php echo $qTypeSelected; ?>",
            "bStart":"<?php echo $bStart; ?>",
            "bEnd":  "<?php echo $bEnd; ?>",
            "pDuration":"<?php echo $pSelected; ?>",
            "rHide":"<?php echo $rHide; ?>",
          },
          "dataSrc": function ( json ) {
            document.getElementById("gml_time").innerHTML = '$gml_time='+ json.processingtime_ms +' ms';
            return json.data;
          }
        },
        "language": {
            "processing": "Loading movements..."
        },
        "deferRender": true,
        "columnDefs": [ 
        {"targets": 4, "className": "text-right"},
        {"targets": 1, "className": "text-left"},
        {"targets": "_all", "className": " text-center"}
        ]
      });
      $('div#dtMovementsList_processing.dataTables_processing').addClass('pl-3 primary-text');
      $('div#dtMovementsList_info.dataTables_info').addClass('pt-4');
  </script>
</section>
<!--/Section: Movements List-->
<script type="text/javascript">
    // popovers Initialization
    $(function () {
      $('[data-toggle="popover-hover"]').popover({
        trigger: 'hover',
      })
    })
</script>
<?php 
  //var_dump($pSelected); 
?>

<section class="d-none">
 <div class="row text-left mt-3">
  <div class="col-12">
   <div class="card card-info p-3">
    <p class="small my-0 py-0">$ldb_time=<?php echo $ldb_time; ?> ms</p>
    <p class="small my-0 py-0">$gcs_time=<?php echo $gcs_time; ?> ms</p>
    <p class="small my-0 py-0">$gts_time=<?php echo $gts_time; ?> ms</p>
    <p class="small my-0 py-0">$gtr_time=<?php echo $gtr_time; ?> ms</p>
    <p class="small my-0 py-0" id="gml_time"></p>
   </div>
  </div>
 </div>
</section>
</div>
<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"movements",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>