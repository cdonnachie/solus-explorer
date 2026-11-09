<?php 
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerDb.php';
  include_once __DIR__ . '/../../lib/explorerUi.php';
  include_once __DIR__ . '/../../lib/explorerRpc.php';
  include_once __DIR__ . '/../../lib/explorerCustom.php';
  include_once __DIR__ . '/../../lib/timeTracker.php';

  $tt = new TimeTracker();
  $tt->createTimer("pageTotal",0);
  $cip = ui_GetUserIpAddress();

  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 
  if ( (isset($settings["show_pages"]["assets"]) && $settings["show_pages"]["assets"]) || (isset($mn) && !empty($mn) && isset($settings["magic_key"]) && $mn == $settings["magic_key"])) {
    global $rpc;
    global $dbm;

    $chartFontSize = 11;
    $minGridDistance = 30;
    $groupData = "false";

    $observation_days = 30;
    $consolidation_distribution_days = 7;
    $consolidation_distribution_limit = 100;

    date_default_timezone_set($settings["timezone"]);
    $tt->createTimer('db_getLastChainStats',1,true);
    $lbs = db_getLastChainStats();
    $tt->stopTimer('db_getLastChainStats','asset types chart');
    $non_admin_count = 0;
    $admin_count = 0;
    $assetTypes = [];
    if (count($lbs)>0){
      $lbs = $lbs[0];
      if (isset($lbs->assetTypes) && is_object($lbs->assetTypes)){
        $assetTypes = (array)$lbs->assetTypes;
        if (array_key_exists("non-admin",$assetTypes)) {$non_admin_count = $assetTypes["non-admin"];}
        if (array_key_exists("non-admin",$assetTypes) && array_key_exists("total",$assetTypes)) {$admin_count = $assetTypes["total"] - $assetTypes["non-admin"];}
        unset($assetTypes["non-admin"]);
        unset($assetTypes["total"]);
        ksort($assetTypes);
      }
    }

    $to_time = time();
    $from_time = $to_time - $consolidation_distribution_days * 86400;

    $tt->createTimer('db_getAssetCount(24H)',1);
    $last24h_count = db_getAssetMovesCount(['move_time_sec' => ['$gte' => (time() - 86400)]]);
    $tt->stopTimer('db_getAssetCount(24H)');
    if ($last24h_count < 0 ){$last24h_count = 0;}

    $tt->createTimer('db_getAssetMovesDataWithFilter',1);
    $last_move = db_getAssetMovesDataWithFilter([],1,0);
    $tt->stopTimer('db_getAssetMovesDataWithFilter','filter=[], limit=1, skip=0)');
    $last_move_str = "N/A";

    if (count($last_move)>0){
      $last_move_str = timediffToParts(time()-$last_move[0]->move_time_sec)->string;
    }

    $tt->createTimer('db_getLastBlocks(1)',1);
    $to_time = db_getLastBlocks(1)[0]->block_time_sec;
    $tt->stopTimer('db_getLastBlocks(1)');
    $granularity_sec = 3600;
    $period = $observation_days * 86400;

    $max_timestamp = ($to_time - $to_time % $granularity_sec);
    $min_timestamp = ($max_timestamp - $period);

    $ref_timestamps = array();

    // fill reference timestamps
    $tt->createTimer("fill_ref_timestamps",1);
    for ($ts = $min_timestamp; $ts < $max_timestamp; $ts += $granularity_sec){
        $ts_ms = $ts*1000;
        $ref_timestamps[$ts] = ["date" => $ts_ms];
    }
    ksort($ref_timestamps);
    $tt->stopTimer("fill_ref_timestamps");


    // last X days
    $tt->createTimer('db_getBlockStats',1);
    $blk_stats = db_getBlockStatsRange($min_timestamp, $max_timestamp);
    $tt->stopTimer('db_getBlockStats','tx chart');

    $tx_types = [];
    $tx_types_data = $ref_timestamps;
    foreach ($blk_stats as $stats){
      if (isset($stats->txTypes)){
        foreach ($stats->txTypes as $type=>$number){
          if (in_array($type,['new_asset','transfer_asset','reissue_asset'])){
            $tx_types_data[$stats->_id][$type] = $number;
            if (!in_array($type, $tx_types)){array_push($tx_types,$type);}
          }
        }
      }
    }
    $tt->createTimer('sort_arrays',1);
    rsort($tx_types);
    ksort($tx_types_data);
    $tt->stopTimer('sort_arrays');
?>

  <!--Section: Button icon-->
  <section>

  <!--Grid row-->
  <div class="row">
      <!--Grid column I-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
          <!--Asset Count Card-->
          <div class="card w-100 mb-4">

            <!--Card Data-->
            <div class="row pr-3 pl-3">
              <div class="col-md-3 col-3 text-left">
                <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-box-open card-icon-text" aria-hidden="true"></i></a>
              </div>

              <div class="col-md-9 col-9 text-right">
                <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold"><?php echo $non_admin_count; ?></h2>
                <p class="font-small h4-responsive primary-text">NON-ADMIN ASSET COUNT</p>
              </div>
            </div>
            <!--/.Card Data-->

            <!--Card content-->
            <div class="row my-0 pr-3 pl-3">
              <div class="mb-2 col-12 text-right">
                <span class="font-small pr-3 h6-responsive grey-text">Admin asset count:</span>
                <span class="font-small font-up m-0 h6-responsive font-weight-bold dark-grey-text"><?php echo $admin_count; ?></span>
              </div>
            </div>
            <!--/.Card content-->

          </div>
          <!--/.Asset Count Card-->
      </div>
      <!--/.Grid column I-->

      <!--Grid column II-->
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
        <!--24h Blocks Card-->
        <div class="card w-100 mb-4">

          <!--Card Data-->
          <div class="row pr-3 pl-3">
            <div class="col-md-3 col-3 text-left">
              <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-spinner card-icon-text" aria-hidden="true"></i></a>
            </div>

            <div class="col-md-9 col-9 text-right">
              <h2 class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold"><?php echo $last24h_count; ?></h2>
              <p class="font-small h4-responsive primary-text">ASSET MOVEMENTS (24H)</p>
            </div>
          </div>
          <!--/.Card Data-->

          <!--Card content-->
          <div class="row my-0 pr-3 pl-3">
            <div class="mb-2 col-12 text-right">
              <span class="font-small pr-3 h6-responsive grey-text">Time since last asset TX:</span>
              <span class="font-small font-up m-0 h6-responsive font-weight-bold dark-grey-text"><?php echo $last_move_str; ?></span>
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
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
          <div class="card w-100 pb-1 mb-4">
            <div class=" card-header primary-color card-header-text py-2 text-left">
              <i class="fas fa-chart-pie mr-1" aria-hidden="true"></i> ASSET TYPES DISTRIBUTION
            </div>
            <div id="ac_assetTypes" class="stats-chart pt-1 px-2"></div>
              <script>
                function render_assetTypes(){
                  var chart = am4core.create("ac_assetTypes", am4charts.PieChart);
                  chart.colors.step = 1;
                  chart.fontSize = <?php echo $chartFontSize; ?>;
                  chart.paddingBottom = 0;
                  chart.marginBottom = 0;
                  chart.responsive.enabled = true;
                  chart.data = [<?php foreach ($assetTypes as $type=>$num){if (!in_array($type,['regular_admin','subasset_admin'])){echo "{'type':'".$type."','number':".$num."},";}}?>];
                  // Set inner radius
                  chart.radius = am4core.percent(70);
                  chart.innerRadius = am4core.percent(35);

                  <?php if((!isset($settings["is_oem"]) || !$settings["is_oem"])) : ?>
                  // watermark
                  var watermark = new am4core.Image();
                  watermark.href = "<?php echo $settings["explorer_address"]; ?>/img/solus.svg";
                  chart.tooltipContainer.children.push(watermark);
                  watermark.align = "right";
                  watermark.valign = "bottom";
                  watermark.height = <?php echo $chartFontSize; ?>*2.5;
                  watermark.opacity = 0.2;
                  watermark.marginRight = 5;
                  watermark.marginBottom = 3;
                  watermark.zIndex = 50;
                  <?php endif; ?>

                  // Add and configure Series
                  var pieSeries = chart.series.push(new am4charts.PieSeries());
                  pieSeries.dataFields.value = "number";
                  pieSeries.dataFields.category = "type";
                  pieSeries.slices.template.stroke = am4core.color("#fff");
                  pieSeries.slices.template.cornerRadius = 5;
                  pieSeries.slices.template.innerCornerRadius = 3.5;
                  pieSeries.slices.template.draggable = false;
                  pieSeries.slices.template.strokeWidth = 2;
                  pieSeries.slices.template.strokeOpacity = 1;
                  //pieSeries.dataFields.depthValue = "number";

                  // Disable ticks and labels
                  pieSeries.labels.template.disabled = true;
                  pieSeries.ticks.template.disabled = true;

                  // Add a legend
                  chart.legend = new am4charts.Legend();
                  chart.legend.position = "right";
                  chart.legend.scrollable = true;
                  chart.legend.valueLabels.template.text = "[bold]{value}[/]";
                  chart.legend.valueLabels.template.align = "right";
                  chart.legend.valueLabels.template.textAlign = "end"; 
                  chart.legend.markers.template.width = <?php echo $chartFontSize; ?>*1.5;
                  chart.legend.markers.template.height = <?php echo $chartFontSize; ?>*1.5;   
                  // This creates initial animation
                  pieSeries.hiddenState.properties.opacity = 1;
                  pieSeries.hiddenState.properties.endAngle = -90;
                  pieSeries.hiddenState.properties.startAngle = -90;
                }
              </script>
          </div>
      </div>
      <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
          <div class="card w-100 pb-1 mb-4">
          <div class=" card-header primary-color card-header-text py-2 text-left">
              <i class="fas fa-chart-bar mr-1" aria-hidden="true"></i> ASSET TRANSACTIONS (LAST <?php echo $observation_days; ?> DAYS)
            </div>
            <div id="ac_assetTxTypes" class="stats-chart pt-1 px-2"></div>
            <script>
              function render_assetTxTypes(){
                // Create chart instance
                var chart = am4core.create("ac_assetTxTypes", am4charts.XYChart);
                setChartDefaults(chart);
                addDateAxis(chart);
                valueAxis = addValueAxis(chart, "TX Number / Hour");
                (<?php echo json_encode($tx_types);?>).forEach(type => addStepLineSeries(chart,type,type,"",true,true,false));
                chart.data = <?php echo json_encode(array_values($tx_types_data));?>; 
              };
            </script>
          </div>
      </div>
  </div>    
  <script>
    $(document).ready(function(){
      function am4themes_customized(target) {
        if (target instanceof am4core.ColorSet) {
          target.list = [
            <?php 
            echo "am4core.color('".$settings["chart_colors"][0]."'),";
            ?>
          ];
        }
      };
      am4core.ready(function() {
        am4core.useTheme(am4themes_animated);
        am4core.useTheme(am4themes_customized);
        render_assetTypes();
        render_assetTxTypes();
      });
    });
    function setChartDefaults(chart){
        chart.colors.step = 1;
        chart.fontSize = <?php echo $chartFontSize; ?>;
        chart.paddingBottom = 0;
        chart.marginBottom = 0;
        chart.responsive.enabled = true;
        chart.zoomOutButton.scale = 0.5;

        <?php if(!isset($settings["is_oem"]) || !$settings["is_oem"]) : ?>
        // watermark
        var watermark = new am4core.Image();
        watermark.href = "<?php echo $settings["explorer_address"]; ?>/img/solus.svg";
        chart.plotContainer.children.push(watermark);
        watermark.align = "right";
        watermark.valign = "top";
        watermark.height = <?php echo $chartFontSize; ?>*2.5;
        watermark.opacity = 0.2;
        watermark.marginRight = 30;
        watermark.marginTop = 3;
        watermark.zIndex = 50;
        <?php endif; ?>

        chart.cursor = new am4charts.XYCursor();

        chart.legend = new am4charts.Legend();
        chart.legend.paddingLeft = 30;
        chart.legend.markers.template.width = <?php echo $chartFontSize; ?>*1.25;
        chart.legend.markers.template.height = <?php echo $chartFontSize; ?>*1.25;   
    }
    function setDateGridIntervals(axis){
        axis.gridIntervals.setAll([
          {timeUnit: "hour", count: 1},
          {timeUnit: "hour", count: 5},
          {timeUnit: "hour", count: 12},
          {timeUnit: "day", count: 1},
          {timeUnit: "day", count: 5},
          {timeUnit: "day", count: 10},
          {timeUnit: "month", count: 1},
          {timeUnit: "month", count: 3},
          {timeUnit: "month", count: 6},
          {timeUnit: "year", count: 1},
          {timeUnit: "year", count: 2},
          {timeUnit: "year", count: 5},
          {timeUnit: "year", count: 10},
        ]);
    }
    function addDateAxis(chart){
        var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
        dateAxis.startLocation = 0;
        dateAxis.endLocation = 1;
        dateAxis.renderer.grid.template.location = 0;
        dateAxis.renderer.minGridDistance = <?php echo $minGridDistance; ?>;
        dateAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
        dateAxis.tooltipDateFormat = "d MMM, yyyy HH:mm";
        dateAxis.groupData = <?php echo $groupData;?>;
        setDateGridIntervals(dateAxis);
        return dateAxis;
    }
    function addValueAxis(chart, title, logarithmic, opposite){
        var  valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
        valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
        valueAxis.title.text = title;
        if (!logarithmic) {valueAxis.min = 0;}else{valueAxis.logarithmic = logarithmic;}
        valueAxis.opposite = opposite;
        return valueAxis;
    }
    function addStepLineSeries(chart, name, field, measurement, filled, stacked, valueInLegend){
      var stepSeries = chart.series.push(new am4charts.StepLineSeries());
      stepSeries.dataFields.valueY = field;
      stepSeries.dataFields.dateX = "date";
      stepSeries.name = name;
      stepSeries.sequencedInterpolation = true;
      stepSeries.tooltipText = "{name}: [bold]{valueY.value}[/] "+measurement;
      stepSeries.tooltip.getFillFromObject = false;
      stepSeries.tooltip.label.fill = am4core.color("#000");
      stepSeries.tooltip.background.fill = am4core.color("#FFF");
      stepSeries.tooltip.fontSize = <?php echo $chartFontSize; ?>;
      stepSeries.tooltip.getStrokeFromObject = true;
      stepSeries.tooltip.background.strokeWidth = 2;
      stepSeries.noRisers = true;
      if (filled){
          stepSeries.fillOpacity = 0.85;
      }
      stepSeries.stacked = stacked;
      stepSeries.connect = false;
      stepSeries.strokeWidth = 0.5;

      if (valueInLegend){
          stepSeries.legendSettings.labelText = name+":";
          stepSeries.legendSettings.valueText = "{valueY.close} "+measurement;
          stepSeries.legendSettings.itemLabelText = name+":";
          stepSeries.legendSettings.itemValueText = "{valueY} "+measurement;
      } else {
          stepSeries.legendSettings.labelText = name;
      }
      stepSeries.hiddenState.properties.opacity = 1;
      return stepSeries;
    }
  </script>
  <!-- /Grid row -->

  <!--Grid row-->
  <div class="row">
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
      <div class="card w-100 pb-1 mb-4">
        <div class=" card-header primary-color card-header-text py-2 text-left">
          <i class="fas fa-sign-out-alt mr-1" aria-hidden="true"></i> TOP <?php echo $consolidation_distribution_limit;?> DISTRIBUTING ASSETS (LAST <?php echo $consolidation_distribution_days; ?> DAYS)
        </div>
        <div class="card-body table-responsive text-nowrap">
          <table id="dtDiAssets" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Rank</th>
                <th class="th-sm">Asset Name</th>
                <th class="th-sm">Asset Type</th>
                <th class="th-sm">VINs Count</th>
                <th class="th-sm">VOUTs Count</th>
              </tr>
            </thead>
          </table>
          <script type='text/javascript'>
            // Configure Datatable
              $('#dtDiAssets').DataTable({
                serverSide: false,
                ordering: true,
                searching: false,
                "pageLength": 10,
                "scrollX": true,
                "pagingType": "full",
                "processing": false,
                "ajax": { 
                  "url":"<?php echo $settings["explorer_address"];?>/_dp/assetdistdp.php?f=<?php echo $from_time; ?>&t=<?php echo $to_time;?>&l=<?php echo $consolidation_distribution_limit;?>&rid=<?php echo db_createRequest(); ?>",
                  "dataSrc": function ( json ) {
                    return json.data;
                  }
                },
                "language": {
                    "processing": "Loading list..."
                },
                "deferRender": false,
                "columnDefs": [ 
                {"targets": 1, "className": "text-left"},
                {"targets": "_all", "className": " text-center"}
                ]
              });
              $('.dataTables_length').addClass('d-none');
              $('.dataTables_processing').addClass('pl-3 primary-text');
              $('.dataTables_info').addClass('pt-4');
          </script>
        </div>
      </div>
    </div>
    <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
      <div class="card w-100 pb-1 mb-4">
        <div class=" card-header primary-color card-header-text py-2 text-left">
          <i class="fas fa-sign-in-alt mr-1" aria-hidden="true"></i> TOP <?php echo $consolidation_distribution_limit;?> CONSOLIDATING ASSETS (LAST <?php echo $consolidation_distribution_days; ?> DAYS)
        </div>   
        <div class="card-body table-responsive text-nowrap">
        <table id="dtCoAssets" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Rank</th>
                <th class="th-sm">Asset Name</th>
                <th class="th-sm">Asset Type</th>
                <th class="th-sm">VINs Count</th>
                <th class="th-sm">VOUTs Count</th>
              </tr>
            </thead>
          </table>
          <script type='text/javascript'>
            // Configure Datatable
              $('#dtCoAssets').DataTable({
                serverSide: false,
                ordering: true,
                searching: false,
                "pageLength": 10,
                "scrollX": true,
                "pagingType": "full",
                "processing": false,
                "ajax": { 
                  "url":"<?php echo $settings["explorer_address"];?>/_dp/assetconsdp.php?f=<?php echo $from_time; ?>&t=<?php echo $to_time;?>&l=<?php echo $consolidation_distribution_limit;?>&rid=<?php echo db_createRequest(); ?>",
                  "dataSrc": function ( json ) {
                    return json.data;
                  }
                },
                "language": {
                    "processing": "Loading list..."
                },
                "deferRender": false,
                "columnDefs": [ 
                  {"targets": 1, "className": "text-left"},
                  {"targets": "_all", "className": " text-center"}
                ]
              });
              $('.dataTables_length').addClass('d-none');
              $('.dataTables_processing').addClass('pl-3 primary-text');
              $('.dataTables_info').addClass('pt-4');
          </script>
        </div>   
      </div>
    </div>
  </div>
  <!-- /Grid row -->
  </section>
  <!--/Section: Button icon-->

  <!--Section: DataTable-->
  <section>
    <div class="row">
      <div class="col-12">
        <!--Asset Genesis List Card-->
        <div class="card mb-4">
          <div class="card-header card-header primary-color card-header-text py-2 text-left">
            <i class="fas fa-box-open mr-1" aria-hidden="true"></i> RECENTLY CREATED ASSETS
          </div>

          <!--Card content-->
          <div class="card-body table-responsive text-nowrap">
            <table id="dtAssetGen" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
              <thead>
                <tr class="table-secondary text-center">
                  <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                  <th class="th-sm">Asset Name</th>
                  <th class="th-sm">Type</th>
                  <th class="th-sm">Supply</th>
                  <th class="th-sm">Reissuable</th>
                  <th class="th-sm">IPFS</th>
                </tr>
              </thead>
            </table>
          </div>
          <!--/.Card content-->
          <script type='text/javascript'>
            // Configure Datatable
              $('#dtAssetGen').DataTable({
                serverSide: true,
                ordering: false,
                searching: false,
                "pageLength": 15,
                "scrollX": true,
                "pagingType": "full",
                "processing": true,
                "ajax": {
                  "url":"../_dp/assetsgendp.php",
                  "type": "POST",
                  "dataSrc": function ( json ) {
                    <?php //                     document.getElementById("gbl_time").innerHTML = '$gbl_time='+ json.processingtime_ms +' ms'; ?>
                    return json.data;
                  }
                },
                "language": {
                    "processing": "Loading assets..."
                },
                "deferRender": true,
                "columnDefs": [ 
                {"targets": 3, "className": "text-right"},
                {"targets": "_all", "className": " text-center"}
                ]
              });
              $('.dataTables_length').addClass('d-none');
              $('.dataTables_processing').addClass('pl-3 primary-text');
              $('.dataTables_info').addClass('pt-4');
          </script>
        </div>
        <!--/.Masternode List Card-->
      </div>      
    </div>

  </section>
  <!--/Section: Asset Genesis DataTable-->


  <!--Section: DataTable-->
  <section>
  <div class="row">
    <div class="col-12">
      <!--Asset Movements List Card-->
      <div class="card mb-4">
        <div class="card-header card-header primary-color card-header-text py-2 text-left">
          <i class="fas fa-exchange-alt mr-1" aria-hidden="true"></i> RECENT ASSET MOVEMENTS
        </div>

        <!--Card content-->
        <div class="card-body table-responsive text-nowrap">
          <table id="dtAssetMoves" class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
            <thead>
              <tr class="table-secondary text-center">
                <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                <th class="th-sm">Asset Name</th>
                <th class="th-sm">Address</th>
                <th class="th-sm">Transaction</th>
                <th class="th-sm">Confirmations</th>
                <th class="th-sm">Amount</th>
              </tr>
            </thead>
          </table>
        </div>
        <!--/.Card content-->

      </div>
      <!--/.Masternode List Card-->
    </div>      
  </div>
  <script type='text/javascript'>
    // Configure Datatable
      $('#dtAssetMoves').DataTable({
        serverSide: true,
        ordering: false,
        searching: false,
        "pageLength": 30,
        "scrollX": true,
        "pagingType": "full",
        "processing": true,
        "ajax": {
          "url":"../_dp/assetsmovesdp.php",
          "type": "POST",
          "dataSrc": function ( json ) {
            <?php //document.getElementById("gbl_time").innerHTML = '$gbl_time='+ json.processingtime_ms +' ms'; ?>
            return json.data;
          }
        },
        "language": {
            "processing": "Loading assets..."
        },
        "deferRender": true,
        "columnDefs": [ 
        {"targets": 5, "className": "text-right"},
        {"targets": "_all", "className": " text-center"}
        ]
      });
      $('.dataTables_length').addClass('d-none');
      $('.dataTables_processing').addClass('pl-3 primary-text');
      $('.dataTables_info').addClass('pt-4');
  </script>
  </section>
  <!--/Section: Asset Genesis DataTable-->
  
  <?php   
  $tt->stopTimer("pageTotal");
  if (isset($mn) && !empty($mn) && isset($settings["magic_key"]) && $mn == $settings["magic_key"]) : ?>
    <div class="card col-12 p-0">
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
          echo "      <tr><td class='py-1  pl-".(2*$tt->level)."'>$tt->name</td><td class='py-1 px-3 text-right'>".fpp($tt->getDurationSeconds(),6)."</td><td class='py-1  pl-4'>$tt->info</td></tr>";
        }
      ?>
      </table>
    </div>
    </div>
  <?php endif; ?>


<?php 
  ui_LogPageEvent($cip,"assets",(int)$tt->getDurationMicroSeconds("pageTotal"),$_REQUEST);


  } else {
    header("Location: /err/404/");
    exit(0);
  }
?>