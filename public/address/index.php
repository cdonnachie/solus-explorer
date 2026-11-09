<?php
  include_once __DIR__ . '/../../lib/common.php';
  require __DIR__ . '/../../settings.php';
  global $settings;
  include_once __DIR__ . '/../../lib/explorerUi.php'; 
  include_once __DIR__ . '/../../lib/explorerDb.php';
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

  $address = sanitizeInput('address','GET');
  if (isset($_GET['mn'])){
    $mn = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mn_str = "?mn=".$mn;
  } 

  global $description;
  $description = ui_getCoinName()." address ".$address.": current balance and transactions. View inputs, outputs and related statistics.";
  global $keywords;
  $keywords = $settings["title"].", address, ".$address.", balance, transactions, statistics, charts, csv export"; 
  global $title;
  $title = ui_getCoinName()." Address ".$address." | Balance & Transactions";

  $tt->createTimer("include_header",0);
  include_once __DIR__ . '/../includes/header.php';
  $tt->stopTimer("include_header");
  //if(!empty($address)){ $address_str = ": $address"; } else { $address_str = ""; }

  $showChart = true;
  $chartFontSize = 9;
  $chartTimeWindow = 36500; // days
  $chartGranularity = 86400; // seconds
  $groupData = "true";

  $chartData = array();

  $notfound = false;
  if(!empty($address)){ 
    $noaddr = false; 
    $tt->createTimer("db_getAddressData",1);
    $ars=db_getAddressData($address); 
    $tt->stopTimer("db_getAddressData");
    //$ams = getAddressMovesData($address, 0); 

    if (count($ars)>0){
      $ar = $ars[0];
      $groupid = $ar->group_id;
      $gtags="";
      $tt->createTimer("get_address_tags",1);
      if (!empty($groupid)){
        $at = db_getAddressTags($address);
        if ($at->has_tags || $at->has_alias){$gtags = 'Tags: ';}
        if ($at->has_alias){
          $gtags .= "<span class='badge badge-info ml-1 mb-1'>".$at->address_alias."</span>&nbsp;";
        }
        if ($at->has_tags){
          foreach ($at->group_tags as $tag){
            $gtags .= "<span class='badge badge-primary ml-1 mb-1'>".$tag."</span>&nbsp;";
          }
        }
      }
      $tt->stopTimer("get_address_tags");
      $bal_split = splitFloat((@cdec($ar->balance) < 0) ? 0 : @cdec($ar->balance) ,$precision,$precision);
      $rec_split = splitFloat(@cdec($ar->received),$precision,$precision);
      $snt_split = splitFloat(@cdec($ar->sent),$precision,$precision);
      $dep_split = splitFloat(@cdec($ar->deposited),$precision,$precision);
      $wdn_split = splitFloat((@cdec($ar->withdrawn)->abs()),$precision,$precision);
      $pe = $ar->passive_in;  // passive earnings
      $pe_split = splitFloat((@cdec($pe) < 0) ? 0 : $pe ,$precision,$precision);
      if (@cdec($ar->deposited)->abs() > 0){
        $proi = (@cdec($ar->passive_in)*100/@cdec($ar->deposited))->round(2);
        $pr_split = splitFloat($proi,2,1);  
      } else {
        $proi = 0;
        $pr_split = splitFloat(0,2,1);
      }

      $mdao_balance = null;
      if (isset($settings["special_features"]) && isset($settings["special_features"]["MDAO_balance"]) && $settings["special_features"]["MDAO_balance"]){
        $tt->createTimer("db_getAddressMdaoBalanceData",1);
        $mdao = db_getAddressMdaoBalanceData($address);
        $tt->stopTimer("db_getAddressMdaoBalanceData");
        if (count($mdao)>0){
          $mdao_balance = @cdec($mdao[0]->balance);
          if ($mdao_balance >= 0){
            $mdao_split = splitFloat($mdao_balance,18,18);
          }
        }
      }

      if ($settings["load_markets"]){
        $tt->createTimer("getLatestMarketsData",1);
        $db_mm = getLatestMarketsData();
        $tt->stopTimer("getLatestMarketsData");
        $btc = cdec(0); $usd = cdec(0); $eur = cdec(0);
        if(!empty($db_mm)){
          $btc = @cdec($bal_split->number) * @cdec($db_mm->avg_price);
          if (isset($db_mm->base_prices["USD"]) && !is_null($db_mm->base_prices["USD"])){
            $usd = $btc * @cdec($db_mm->base_prices["USD"]);
          }
          if (isset($db_mm->base_prices["EUR"]) && !is_null($db_mm->base_prices["EUR"])){
            $eur = $btc * @cdec($db_mm->base_prices["EUR"]);
          }
        }
        $btc_split = splitFloat($btc,$precision,$precision);
        $usd_split = splitFloat($usd,2,2);
        $eur_split = splitFloat($eur,2,2);
        $btc_visibile = true;
      } else {
        $btc_visibile = false;
      }
      $form_invis = ""; 
      $notfound_visibility = "d-none";
      $emptyrequest_visibility = "d-none";
      $ar->assets = (array) $ar->assets;
      $tt->createTimer("get_assets",1);
      if (count($ar->assets)>0) {
        $keys = array();
        $adm_assets = array();
        $assets = array();
        foreach ($ar->assets as $key=>$asset){
          $asset_name = (string) $asset->name;
          if (!array_key_exists($asset_name, $keys)){
            $ast = db_getAssetByName($asset_name, true);
            if (count($ast)>0){
              $keys[$asset_name] = $ast[0]->key;
            } else {
              $keys[$asset_name] = null;
            }
          }
          if (@cdec($asset->value)>0){
            if (substr($asset_name,strlen($asset_name)-1,1) == "!"){
              $adm_assets[$asset_name] += fpp($asset->value);
            } else {
              $assets[$asset_name] = fpp($asset->value);
            }
          }
        }
        $tt->createTimer("sort_assets",2);
        ksort($adm_assets,SORT_STRING);
        ksort($assets, SORT_STRING);
        $tt->stopTimer("sort_assets");
        if ((count($adm_assets)+count($assets))>0) {$assets_visibility = true;}

      } else {
        $adm_assets = array();
        $assets = array();
        $assets_visibility = false;
      }
      $tt->stopTimer("get_assets");
      if ($showChart){
        $tt->createTimer("db_createRequest(chart)",1);
        $rid = db_createRequest();
        $tt->stopTimer("db_createRequest(chart)");
      }
    } else {
      //addressnot found
      $notfound = true;
      $showChart = false;
      $form_invis = "d-none"; 
      $notfound_visibility = "";
      $emptyrequest_visibility = "d-none";
      $assets_visibility = false;
    }


  } else { 
    $noaddr = true; 
    $showChart = false;
    $form_invis = "d-none"; 
    $notfound_visibility = "d-none";
    $emptyrequest_visibility = "";
    $assets_visibility = false;
    $ars = array();
    $ams = array();
    $ar = new stdClass();
  }

?>
  <!--Main layout-->
  <main>
    <div id="content" class="container-fluid">

<?php if (!$notfound) : ?>
  <script nonce="<?php echo $csp_nonce;?>"  src="../js/qrcode.min.js"></script>
  <!--Section: Button icon-->
  <section class="<?php echo $form_invis;?>">
    <!--Header grid row-->
    <div class="row">
      <!--Grid column I-->
      <div class="col-12 mb-4">
        <!--Address Card (small)-->
        <div class="card col-12 d-md-none " style="min-height:60px">
          <!--Card Data-->
            <div class="primary-text text-center pt-2 h2-responsive">
              <b><?php echo $address; ?></b>
            </div>
            <div class="text-center pb-2">
              <span class="grey-text">Group: <a href="<?php echo $settings["explorer_address"]; ?>/addressgroup/?address=<?php echo $address; ?>"><?php echo $groupid; ?></a></span>
            </div>
            <?php if(!empty($gtags)){echo '<div class="grey-text pb-2">'.$gtags.'</div>';}?>
          <!--/.Card Data-->
        </div>
        <!--/.Address Card (small) Card-->

        <!--Address Header Card (big)-->
        <div class="card col-12 d-none d-md-flex" style="min-height:72px">
          <!--Card Data-->
          <div class="row text-left">
              <div class="pt-1 pl-2 ">
              <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-2 mr-2"><i class="fas fas fa-at card-icon-text" aria-hidden="true"></i></a></div>
              <div class="p-3"><span class="h2 h2-responsive mb-0 font-weight-bold primary-text" style="height:1.5em;"><?php echo $address; ?></span><br>
              <span class='grey-text' style="height:14.5px"> Member of group: <a href="<?php echo $settings["explorer_address"]; ?>/addressgroup/?address=<?php echo $address; ?>" ><?php echo $groupid; ?></a></span>
              <?php if(!empty($gtags)){echo '<br><span class="grey-text pb-2">'.$gtags.'</span>';}?>
              </div>
            </div>
          <!--/.Card Data-->

        </div>
        <!--/.Address Header Card (big)-->
      </div>
      <!--/.Grid column I-->
    </div>
    <!--Header grid row-->

    <!--Info grid row-->
    <div class="row">
        <!--Balance grid column I-->
        <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4 ">

          <!--Balance Card-->
          <div class="card col-12 text-center" style="min-height: 145px;">
            <!--Card Data-->
            <div id="bal_row" class="row pr-3 pl-3">
              <div class="col-md-3 col-3 text-left">
                <div id="address_qr" class="mt-3" style="height:82px;width:82px;" title='<?php echo $settings["coin_symbol"]; ?> Address: <?php echo $address; ?>'></div>
                <script nonce="<?php echo $csp_nonce;?>">
                  $(document).ready(function() {
                    const qrcode = new QRCode({content: "<?php echo $address; ?>",ecl: "Q",color: "<?php echo getNamedColor("primary");?>",container: "svg-viewbox",join: true});$("#address_qr").html(qrcode.svg());
                  })
                </script>
              </div>

              <div class="col-md-9 col-9 text-right">
                <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $bal_split->whole."."; ?><small><?php echo $bal_split->fraction; ?></small></h1>
                <p class="font-small h4-responsive mb-0 primary-text"><?php echo "BALANCE (".$settings["coin_symbol"].")"; ?></p>
                <?php if (!is_null($mdao_balance)) : ?>
                <h4 class="ml-1 mt-3 h4-responsive mb-0 font-weight-bold"><?php echo $mdao_split->whole."."; ?><small><?php echo $mdao_split->fraction; ?></small></h4>
                <p class="font-small h4-responsive mb-0 primary-text"><?php echo "BALANCE (MDAO)"; ?></p>
                <?php endif; ?>
                <div class="mb-2 col-12 pt-2 pr-0 text-right ">
                  <span class="font-small pr-3 h6-responsive grey-text"><?php if($btc_visibile){ echo "BTC";}; ?></span>
                  <span id="value" class="font-small font-up m-0 h6-responsive font-weight-bold dark-grey-text" <?php if($btc_visibile){ echo 'data-toggle="popover-hover"';}?> > <?php if($btc_visibile){ echo $btc_split->whole .".". $btc_split->fraction;}; ?></span>
                  <?php if ($btc_visibile) : ?>
                  <script nonce="<?php echo $csp_nonce;?>">
                    $(document).ready(function() {
                      $('#value').popover({
                        html: true,
                        trigger: 'hover',
                        placement: 'bottom',
                        content: function () { 
                          return '<div><div class="row"><div class="col font-small h6-responsive">BTC</div><div class="col font-small h6-responsive text-right"><b><?php echo $btc_split->whole;?>.<small><?php echo $btc_split->fraction;?></small></b></div></div><div class="row"><div class="col font-small h6-responsive">USD</div><div class="col font-small h6-responsive text-right"><b><?php echo $usd_split->whole;?>.<small><?php echo $usd_split->fraction;?></small></b></div></div><div class="row"><div class="col font-small h6-responsive">EUR</div><div class="col font-small h6-responsive text-right"><b><?php echo $eur_split->whole;?>.<small><?php echo $eur_split->fraction;?></small></b></div></div></div>'; 
                        }
                      });
                    })

                  </script>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <!--/.Card Data-->

            <!--Card content-->
            <span class="text-center pl-3 pr-3 "><hr class="primary-color my-1" width="100%"/></span>
            <div class="row my-0 pr-3 pl-3">
              <div class="col-6 ctext-center">
                  <p class="font-small h4-responsive primary-text my-0"><?php echo "RECEIVED (".$settings["coin_symbol"].")"; ?></p>
                  <h5 class="h5-responsive font-weight-bold mb-2 text-success"><?php echo $rec_split->whole."."; ?><small><?php echo $rec_split->fraction; ?></small></h5>
              </div>
              <div class="col-6 text-center">
              <p class="font-small h4-responsive primary-text my-0"><?php echo "SENT (".$settings["coin_symbol"].")"; ?></p>
                <h5 class="h5-responsive font-weight-bold mb-2 text-danger"><?php echo $snt_split->whole."."; ?><small><?php echo $snt_split->fraction; ?></small></h5>
              </div>
            </div>
            <!--/.Card content-->

          </div>
          <!--/.Balance Card-->

        </div>
        <!--/.Balance grid column I-->


        <!--Income grid column II-->
        <div class="col-xl-6 col-lg-6 col-md-12 flex-col mb-4 ">
          <!-- Income Card-->
          <div class="card col-12 text-center"  style="min-height: 145px;">
            <!--Card Data-->
            <div id="pi_row" class="row pr-3 pl-3">
              <div class="col-md-3 col-3 text-left">
                
                <?php 
                  if ( is_null($ar->deposited) || @cdec($ar->deposited)->isZero() ){
                    $proi_msg = "<i class='fas fa-infinity'></i>";
                  } else {
                    $proi_msg = $pr_split->whole.".<small>".$pr_split->fraction."</small>%";
                  }
                ?>
                <span class="min-chart-roi mt-3 ml-2" id="chart-roi" data-percent="<?php echo $pr_split->whole; ?>"><span class='percent primary-text'><?php echo $proi_msg; ?></span></span>
                <script nonce="<?php echo $csp_nonce;?>">
                  $(document).ready(function () {
                    $('.min-chart-roi#chart-roi').easyPieChart({
                      barColor: "#42a759",
                      size: 76,
                    });
                  });
                </script>
              </div>

              <div class="col-md-9 col-9 text-right">
                <h1 class="ml-1 mt-3 h1-responsive mb-0 font-weight-bold"><?php echo $pe_split->whole."."; ?><small><?php echo $pe_split->fraction; ?></small></h1>
                <p class="font-small h4-responsive mb-0 primary-text"><?php echo "PASSIVE INCOME (".$settings["coin_symbol"].")"; ?></p>
                <div class="mb-2 col-12 pt-2 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text"></span>
                  <span class="font-small font-up m-0 h6-responsive font-weight-bold dark-grey-text"></span>
                </div>
              </div>
            </div>
            <!--/.Card Data-->

            <!--Card content-->
            <span class="text-center pl-3 pr-3 "><hr class="primary-color my-1" width="100%"/></span>
            <div class="row my-0 pr-3 pl-3">
              <div class="col-6 text-center">
                  <p class="font-small h4-responsive primary-text my-0"><?php echo "DEPOSITED (".$settings["coin_symbol"].")"; ?></p>
                  <h5 class="h5-responsive font-weight-bold mb-2 primary-text"><?php echo $dep_split->whole; ?><small>.<?php echo $dep_split->fraction; ?></small></h5>
                </div>
                <div class="col-6 text-center">
                <p class="font-small h4-responsive primary-text my-0"><?php echo "SPENT (".$settings["coin_symbol"].")"; ?></p>
                  <h5 class="h5-responsive font-weight-bold mb-2 primary-text"><?php echo $wdn_split->whole; ?><small>.<?php echo $wdn_split->fraction; ?></small></h5>
                </div>
            </div>
            <!--/.Card content-->

          </div>
          <!--/.Income Card-->

        </div>
        <!--/.Income grid column II-->
        <script nonce="<?php echo $csp_nonce;?>">
        $("#pi_row").height($("#bal_row").height());
        </script>
    </div>
    <!--Info grid row-->
  </section>
  <!--/Section: Button icon-->

  <?php if ($showChart) :?>
  <!-- Section: movements chart -->
  <section>
  <div class="row">
    <!--Grid column I-->
    <div class="col-12 mb-4 ">
      <div id="movchart" class="card w-100" style="height: 360px;">
        <div class="card card-body fading-front">
          <div class="row primary-text text-center">
            <div class="col-12"><span class="h6 h6-responsive">Address Movements</span></div>
          </div>

          <div id="ac_mov" class="stats-chart pt-2"></div>
          <script defer nonce="<?php echo $csp_nonce; ?>">
            $(document).ready(function() {
              am4core.ready(function() {
                //am4core.useTheme(am4themes_animated);
                var chart = am4core.create("ac_mov", am4charts.XYChart);
                chart.dataSource.url = "<?php echo $settings["explorer_address"];?>/_dp/addressmoveschartdp.php?addr=<?php echo $address; ?>&gp=<?php echo $chartGranularity;?>&rid=<?php echo $rid; ?>";
                chart.fontSize = <?php echo $chartFontSize; ?>;
                chart.paddingBottom = 0;
                chart.marginBottom = 0;
                chart.responsive.enabled = true;
                chart.preloader.disabled = true;

                // zoomOut button
                chart.zoomOutButton.scale = 0.5;

                // export menu
                chart.exporting.menu = new am4core.ExportMenu();
                chart.exporting.menu.align = "right";
                chart.exporting.menu.verticalAlign = "bottom";
                chart.exporting.filePrefix = "<?php echo "Chart_".$address; ?>";

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

                // AxisX
                var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                dateAxis.startLocation = 0;
                dateAxis.endLocation = 0;
                //dateAxis.title.text = "Date";
                dateAxis.renderer.grid.template.location = 0;
                dateAxis.renderer.minGridDistance = 30;
                dateAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                dateAxis.tooltipDateFormat = "d MMM, yyyy";
                //dateAxis.dateFormatter = new am4core.DateFormatter();
                //dateAxis.dateFormatter.dateFormat = "d MMM, yyyy";
                dateAxis.groupData = <?php echo $groupData;?>;
                dateAxis.groupCount = 1500;
                // Axis Y
                var  valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                valueAxis.title.text = "Amount (<?php echo $settings["coin_symbol"]; ?>)";

                // Received
                var s = chart.series.push(new am4charts.StepLineSeries());
                s.dataFields.valueY = "recv";

                s.dataFields.dateX = "date";
                s.name = "Received";

                s.sequencedInterpolation = true;
                //s.columns.template.width = am4core.percent(100);
                s.fill = am4core.color("<?php echo $settings["named_colors"]["success"];?>");
                s.strokeWidth = 0.5;
                s.stroke = am4core.color("<?php echo $settings["named_colors"]["success"];?>");
                s.tooltipText = "{name}: [bold <?php echo $settings["named_colors"]["success"];?>]{valueY.value.formatNumber('#,###')}[/]";

                s.tooltip.getFillFromObject = false;
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.background.fill = am4core.color("#FFF");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                s.tooltip.getStrokeFromObject = true;
                s.tooltip.background.strokeWidth = 2;

                s.fillOpacity = 0.75;
                s.stacked = true;
                s.connect = false;

                // static
                s.legendSettings.labelText = "Received";

                // hovering
                s.legendSettings.itemLabelText = "Received";

                // Sent
                var s = chart.series.push(new am4charts.StepLineSeries());
                s.dataFields.valueY = "sent";

                s.dataFields.dateX = "date";
                s.name = "Sent";

                s.sequencedInterpolation = true;
                //s.columns.template.width = am4core.percent(100);
                s.fill = am4core.color("<?php echo $settings["named_colors"]["danger"];?>");
                s.strokeWidth = 0.5;
                s.stroke = am4core.color("<?php echo $settings["named_colors"]["danger"];?>");
                s.tooltipText = "{name}: [bold <?php echo $settings["named_colors"]["danger"];?>]{valueY.value.formatNumber('#,###')}[/]";

                s.tooltip.getFillFromObject = false;
                s.tooltip.label.fill = am4core.color("#000");
                s.tooltip.background.fill = am4core.color("#FFF");
                s.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                s.tooltip.getStrokeFromObject = true;
                s.tooltip.background.strokeWidth = 2;

                s.fillOpacity = 0.75;
                s.stacked = true;
                s.connect = false;

                // static
                s.legendSettings.labelText = "Sent";

                // hovering
                s.legendSettings.itemLabelText = "Sent";

                // Balance series
                var s_abs = chart.series.push(new am4charts.StepLineSeries());
                s_abs.dataFields.valueY = "bal";
                s_abs.dataFields.dateX = "date";
                s_abs.name = "Balance";

                s_abs.tooltipText = "{name}: [bold]{valueY.value.formatNumber('#,###')}[/]";

                s_abs.tooltip.getFillFromObject = false;
                s_abs.tooltip.label.fill = am4core.color("#000");
                s_abs.tooltip.background.fill = am4core.color("#FFF");
                s_abs.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                s_abs.tooltip.getStrokeFromObject = true;
                s_abs.tooltip.background.strokeWidth = 2;

                // static
                s_abs.legendSettings.labelText = "Balance";

                // hovering
                s_abs.legendSettings.itemLabelText = "Balance";

                // Add cursor
                chart.cursor = new am4charts.XYCursor();

                // add legend
                chart.legend = new am4charts.Legend();
                chart.legend.markers.template.width = <?php echo $chartFontSize; ?>*1.5;
                chart.legend.markers.template.height = <?php echo $chartFontSize; ?>*1.5;

                // scrollbar
                chart.scrollbarX = new am4core.Scrollbar();
                //chart.scrollbarX.scale = 0.25;
                //chart.scrollbarX.width = am4core.percent(100);
                chart.scrollbarX.marginTop = -10;
                chart.scrollbarX.minHeight = 4;
                chart.scrollbarX.startGrip.scale=0.4;
                chart.scrollbarX.endGrip.scale=0.4;


                var indicator;
                var indicatorLabel;

                function showIndicator() {
                  
                  if (!indicator) {
                    indicator = chart.tooltipContainer.createChild(am4core.Container);
                    indicator.background.fill = am4core.color("#fff");
                    indicator.background.fillOpacity = 0.8;
                    indicator.width = am4core.percent(100);
                    indicator.height = am4core.percent(100);

                    indicatorLabel = indicator.createChild(am4core.Label);
                    indicatorLabel.text = "Requesting date from the server...";
                    indicatorLabel.align = "center";
                    indicatorLabel.valign = "middle";
                    indicatorLabel.fontSize = <?php echo $chartFontSize; ?>*1.15;
                  }
                  
                  indicator.hide(0);
                  indicator.show();
                  
                }

                function hideIndicator() {
                  indicator.hide();
                }
                showIndicator();

                chart.dataSource.events.on("loadstarted",function(e){
                  indicatorLabel.text = "Loading the data, this may take a while...";
                });
                chart.dataSource.events.on("parsestarted",function(e){
                  indicatorLabel.text = "Parsing the data...";
                });
                chart.dataSource.events.on("done",function(e){
                  indicatorLabel.text = "All done";
                  hideIndicator();
                });



              });
            });
          </script>
        </div>
      </div>
    </div>
  </div>
  </section>
  <!--/Section: movements chart-->
  <?php endif; ?>

  <?php if ($assets_visibility) : ?>
    <section>
      <div class="row text-center pt-0 pb-2">
        <div class="col-12">
          <button id="bAssets" type="button" class="btn btn-info py-2"  data-action="show"><span class="fas fa-box-open pr-2"></span><span id="bText">Show Assets ( <?php echo (count($assets)+count($adm_assets));?> )</span></button>
          <script nonce="<?php echo $csp_nonce;?>">
            bw = $( "#bAssets" ).width();
            $( "#bAssets" ).click(function() {
              if ( $( this ).data( 'action' ) == 'show' ){
                $( this ).data( 'action','hide');
                $( '#bText' ).text('Hide Assets');
                $('#assetTable').removeClass('d-none');
              } else {
                $( this ).data( 'action','show');
                $( '#bText').text('Show Assets ( <?php echo (count($assets)+count($adm_assets));?> )');
                $('#assetTable').addClass('d-none');
              }
              $( "#bAssets" ).width(bw);
            });
          </script>
        </div>
      </div>
      <div id="assetTable" class="row d-none">
        <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
          <div class="card w-100 mb-4">
            <div class="card-header card-header primary-color card-header-text py-2">
              <span class="h4 h4-responsive"><i class="fas fa-box-open pr-2" aria-hidden="true"></i> Administrative Tokens </span>
            </div>
            <div class="card-body table-responsive text-nowrap">
              <div class="table-container">
                <?php if (count($adm_assets)>0){ $aanf="d-none"; $aatb="";}else {$aanf=""; $aatb="d-none";}?>
                <span class="<?php echo $aanf; ?>">Not found</span>
                <table id="dtAdmTokens" class="table table-hover table-sm <?php echo $aatb; ?>" cellspacing="0" width="100%">
                  <thead>
                    <tr class="table-secondary text-center">
                      <th class="th-sm">Name</th>
                      <th class="th-sm">Number</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      foreach ($adm_assets as $name=>$value) {
                          if (array_key_exists($name,$keys)) { $akey = $keys[$name];} else{$akey = "";}
                          echo "<tr><td class='px-2'><a class='txlist-txid text-primary' href='".$settings["explorer_address"]."/asset/?asset_id=".$akey."'>".$name."</a></td><td class='px-2 text-right'>".$value."</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <div class="col-xl-6 col-lg-6 col-md-12 flex-col">
          <div class="card w-100 mb-4">
            <div class="card-header card-header primary-color card-header-text py-2">
              <span class="h4 h4-responsive"><i class="fas fa-box-open pr-2" aria-hidden="true"></i> Asset Tokens </span>
            </div>
            <div class="card-body table-responsive text-nowrap ">
              <div class="table-container col-12">
              <?php if (count($assets)>0){ $anf="d-none"; $atb="";}else {$anf=""; $atb="d-none";}?>
                <span class="<?php echo $anf; ?>">Not found</span>
                <table id="dtAstTokens" class="table table-hover table-sm <?php echo $atb;?>" cellspacing="0" width="100%">
                  <thead>
                    <tr class="table-secondary text-center">
                      <th class="th-sm">Name</th>
                      <th class="th-sm">Amount</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php 
                      foreach ($assets as $name=>$value) {
                          if (array_key_exists($name,$keys)) { $key = $keys[$name];} else{$key = "";}
                          $am_split=splitFloat($value, $precision, $precision);
                          echo "<tr><td class='px-2'><a class='txlist-txid text-primary' href='".$settings["explorer_address"]."/asset/?asset_id=".$key."'>".$name."</a></td><td class='px-2 text-right'>".$am_split->whole.".<small>".$am_split->fraction."</small></td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div> 
      </div>
    </section>
  <?php endif; ?>
  <?php if (isset($settings['show_pages']['masternodes']) && $settings['show_pages']['masternodes']) : ?>
    <!--Section: Masternode status-->
    <?php 
      $mns_invis = "d-none";  // SIP-036 fix
      $tt->createTimer("ui_getMasternodeStatus",1);
      $mns = ui_getMasternodeStatus($address);
      $tt->stopTimer("ui_getMasternodeStatus");
      if(count($mns) > 0) { $mns_invis = ""; } // SIP-036 fix

    ?>
    <section class="<?php echo $mns_invis;?> <?php echo $form_invis;?>">
      <!--Grid row-->
      <div class="row">
        <!--Grid column I-->
        <div class="col-12 mb-4">
          <!--Masternode Status Card-->
          <div class="card">
            <div class="card-header primary-color card-header-text  py-2">
              <span class="h4 h4-responsive"><i class="fab fa-hubspot mr-1" aria-hidden="true"></i> Masternode Status</span>
            </div>

            <!--Card content-->

            <div class="card-body table-responsive text-nowrap" >
              <div class="table-container">
              <table class="table table-hover table-striped table-bordered table-sm " cellspacing="0" width="100%">
                <thead>
                <?php if (isset($settings["dip2_support"]) && $settings["dip2_support"]) : ?>
                  <tr class="table-secondary text-center">
                    <th class="th-sm">Masternode Identifier</th>
                    <th class="th-sm">Status</th>
                    <th class="th-sm">Last Paid Block</th>
                    <th class="th-sm">Last Paid Time</th>
                    <th class="th-sm">Payee Address</th>
                    <th class="th-sm">Collateral Address</th>
                  </tr>
                <?php else : ?>
                  <tr class="table-secondary text-center">
                    <th class="th-sm">Masternode Identifier</th>
                    <th class="th-sm">Status</th>
                    <th class="th-sm">Protocol</th>
                    <th class="th-sm">Last Seen</th>
                    <th class="th-sm">Active Time</th>
                  </tr>
                <?php endif; ?>
                </thead>
                <tbody>
                  <?php 
                  foreach($mns as $key => $mn){
                      if (in_array($mn->status,["ENABLED"])) {
                      $badge_class="success";
                      } else if (in_array($mn->status,["ACTIVE", "PRE-ENABLED", "WATCHDOG_EXPIRED"])) {
                      $badge_class="warning";
                      } else {
                      $badge_class="danger";
                      }

                      // mn tier badge
                      $tier_str = "";
                      if (isset($mn->tier)){
                        if (is_numeric($mn->tier)){
                          $tier_str = "<span class='badge ml-2' style='background-color:".getChartColorArray(3)[(int)$mn->tier].";'><i class='fab fa-hubspot'></i>&nbsp;Tier ".$mn->tier."</span>";
                        } else{
                          $tier_str = "<span class='badge badge-info ml-2'><i class='fab fa-hubspot'></i>&nbsp;".$mn->tier."</span>";
                        }
                      }
                      if (isset($settings["dip2_support"]) && $settings["dip2_support"]){
                        echo "<tr><td class='text-center p-2'>".$key.$tier_str."</td>";
                        echo "<td class='text-center p-2'><span class='badge badge-$badge_class mnlist-status h6 mb-0'>".$mn->status."</span></td>";
                        echo "<td class='text-center p-2'><a class='blocklist-height ' href='../block/?blockheight=".$mn->lastpaidblock."'>".$mn->lastpaidblock."</a></td>";
                        echo "<td class='text-center p-2'>".date('Y-m-d H:i:s', $mn->lastpaidtime)."</td>";
                        echo "<td class='text-center p-2'><a class='primary-text mnlist-address' href='".$settings["explorer_address"]."/address/?address=".$mn->addr."'>".$mn->addr."</a></td>";
                        echo "<td class='text-center p-2'><a class='primary-text mnlist-address' href='".$settings["explorer_address"]."/address/?address=".$mn->colladdr."'>".$mn->colladdr."</a></td></tr>";
                      } else {
                        //echo "<tr><td class='text-center p-2'>".substr($key,0,8)."******".substr($key, strlen($key)-10, 10)."</td>";
                        echo "<tr><td class='text-center p-2'>".$key.$tier_str."</td>";
                        echo "<td class='text-center p-2'><span class='badge badge-".$badge_class." mnlist-status h6 mb-0'>".$mn->status."</span></td>";
                        echo "<td class='text-center p-2'>$mn->version</td>";
                        echo "<td class='text-center p-2'>".date('Y-m-d H:i:s', $mn->lastseen)."</td>";
                        echo "<td class='text-center p-2'>".timediffToParts($mn->activetime)->string."</td><tr>";
                      }
                  }
                  ?>
                </tbody>
              </table>
              </div>
            </div>
            <!--/.Card content-->
          </div>
          <!--/.Masternode Status Card-->
        </div>
        <!--/.Grid column I-->
      </div>
      <!--Grid row-->
    </section>
    <!--/Section: Masternode status-->
  <?php endif; ?>

  <!--Section: Transactions DataTable-->
  <section class="<?php echo $form_invis;?>">
    <div class="row">
      <div class="col-md-12">
        <!--Masternode List Card-->
        <div class="card">
          <div class="card-header primary-color card-header-text  py-2">
            <span class="h4 h4-responsive"><i class="fas fa-exchange-alt mr-1" aria-hidden="true"></i> Transaction List</span>
            
          </div>

          <!--Card content-->
          <div class="card-body pb-0 text-nowrap w-100" style="_min-height:710px;">
            <?php if (isset($settings["export_csv_enabled"]) && $settings["export_csv_enabled"]) : ?>
              <div class="row">
                <div class="col-12">
                  <span class="float-right pb-2"><button class="btn btn-sm btn-info " id="exportToCsv">Export to CSV</button></span>
                  <script defer nonce="<?php echo $csp_nonce;?>" type='text/javascript'>
                    $('#exportToCsv').click( function(){
                      exportToCsv('<?php echo $settings["explorer_address"]; ?>/_dp/addressmovescsv.php?addr=<?php echo rawurlencode((string)$address); ?>','<?php echo rawurlencode((string)$address); ?>_movements_<?php echo date("Y-m-d_H-i-s"); ?>.csv');
                    });
                  </script>
                </div>
              </div>
            <?php endif; ?>
            <div class="row">
              <div class="col-12 table-responsive w-100">
                <table id="dtTransactionsList" class="table table-striped table-bordered table-hover table-sm w-100 m-0" cellspacing="0" width="100%">
                  <thead>
                    <tr class="table-secondary text-center">
                      <th class="th-sm">Timestamp <?php if (isset($settings["timezone"])) { echo "[".$settings["timezone"]."]";} ?></th>
                      <th class="th-sm">Transaction Hash</th>
                      <?php if ($address == "Airdrop") : ?>
                        <th class="th-sm">Airdrop Owner</th>
                      <?php endif; ?>
                      <th class="th-sm">Confirmations</th>
                      <th class="th-sm">Amount</th>
                    </tr>
                  </thead>
                </table>
              </div>
            </div>
          </div>
          <!--/.Card content-->

        </div>
        <!--/.Masternode List Card-->
      </div>      
    </div>
  </section>
  <!--/Section: Transactions DataTable-->
  <script defer nonce="<?php echo $csp_nonce;?>" type='text/javascript'>
    // Configure Datatable
    $(document).ready(function() {
      var table = $('#dtTransactionsList').DataTable({
        "serverSide": true,
        "ordering": false,
        "searching": false,
        "pageLength": 20,
        "lengthChange": false,
        "scrollX": true,
        "pagingType": "simple_numbers",
        "processing": true,
        "ajax": {
          "url":"<?php echo $settings["explorer_address"]; ?>/_dp/addressmovesdp.php?addr=<?php echo rawurlencode((string)$address); ?>",
          "type":"POST",
          "dataSrc": function ( json ) {
            const gtx = document.getElementById("gtx_time");
            if (gtx){
              gtx.innerHTML = '$gtx_time='+ json.processingtime_ms +' ms';
            }
            return json.data;
          }
        },
        "language": {
            "processing": "Loading transactions..."
        },
        "deferRender": true,
        "columnDefs": [ 
        {"targets": -1, "className": "text-right"},
        {"targets": "_all", "className": " text-center"}
        ],

      });
      $('div#dtTransactionsList_processing.dataTables_processing').addClass('pl-3 primary-text');
      $('div#dtTransactionsList_info.dataTables_info').addClass('pt-4 d-none d-md-block');
    })
  </script>

<?php else : ?>
  <!-- NOT FOUND SECTION -->
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
              <h5 class="text-uppercase text-danger" >address not found</h5>
            </div>
          </div>

          <!-- Card content -->
          <div class="card-body card-body-cascade mt-2">
            <h6 > Requested address: <span class="font-weight-bold text-danger ml-2"><?php echo $address; ?></span></h6>
            <p class="card-text">Address was not found in the database.<br>
            It may happen due to the following reasons:
              <ul> 
                <li class="card-text">incorrect address entered</li>
                <li class="card-text">address was not yet used</li>
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
<section class="<?php echo $emptyrequest_visibility;?>">
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
          <h6 >Your search request doesn't contain an address.</h6>
          <p class="card-text">Navigation url is incomplete, please fix it and try again.
          </p>
        </div>
      </div>
      <!-- Card -->
    </div>
    <!-- Grid column -->
  </div>
</section>

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
  $use_amcharts = true;
  include_once __DIR__ . '/../includes/footer.php';
  $apt->stopTimer("total");
  ui_LogPageEvent($cip,"address",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>
