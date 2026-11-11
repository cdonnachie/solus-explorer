<?php
/*
 * Solus Explorer
 * Copyright (c) 2019-2026 Cryptoscope.io, unclear#0122
 * Released under the MIT License, see LICENSE in the repository root.
 */
  include_once __DIR__ . '/../lib/common.php';
  require __DIR__ . '/../settings.php';    
  global $settings;
  include_once __DIR__ . '/../lib/explorerDb.php';
  include_once __DIR__ . '/../lib/explorerUi.php';
  include_once __DIR__ . '/../lib/explorerRpc.php';
  include_once __DIR__ . '/../lib/timeTracker.php';
  $apt = new TimeTracker;
  $apt->createTimer("total",0);
  $tt = new TimeTracker;
  $tt->createTimer("pageTotal",0);
  $cip = ui_GetUserIpAddress();

  global $rpc;
  global $dbm;
  date_default_timezone_set($settings["timezone"]);
  $precision = (int)$settings["overview_precision"];
  $def_precision = (int)$settings["default_precision"];

  global $csp_nonce;
  $csp_nonce = random_hex(8);
  
  $mkey_str = null;
  $mkey = null;
  if (isset($_GET['mn'])){
    $mkey = strtr(sanitizeInput('mn',"GET"),"_"," ");
    $mkey_str = "?mn=".$mkey;
  } 

  global $description;
  $description = "Explore the ".ui_getCoinName()." blockchain: latest blocks and transactions, address lookups, address groups, ".
    (!empty($settings["show_pages"]["masternodes"])?"masternodes, ":"").
    (!empty($settings["show_pages"]["staking"])?"staking overview, ":"").
    (!empty($settings["show_pages"]["mining"])?"mining insights, ":"").
    (!empty($settings["show_pages"]["assets"])?"assets, ":"").
    "markets, and chain statistics.";
  global $keywords;
  $keywords = $settings["title"].", chain overview, block height, tx count, address count, group count, coin supply, hashrate, difficulty,top movements, chain highlights"; 
  global $title;
  $title = ui_getCoinName()." Block Explorer | Live Blocks, TXs & Stats";
  global $page_name;
  $page_name = $settings["title"]." - Overview";
  global $page_id;
  $page_id = "Overview";


  if (isset($settings["explorer_address"])&&!empty($settings["explorer_address"])){
    $mn=(isset($settings['show_pages']['masternodes'])&&($settings['show_pages']['masternodes']))?' masternodes,':'';
    $staking=(isset($settings['show_pages']['staking'])&&($settings['show_pages']['staking']))?' staking,':'';
    $mining=(isset($settings['show_pages']['mining'])&&($settings['show_pages']['mining']))?' mining,':'';
    $assets=(isset($settings['show_pages']['assets'])&&($settings['show_pages']['assets']))?' assets,':'';
    global $json_ld;
    $ld_data = [
      "@context" => "https://schema.org",
      "@type" => "WebApplication",
      "name" => ui_getCoinName()." Block Explorer",
      "url" => ui_getRootURL()."/",
      "applicationCategory" => "BlockchainExplorer",
      "operatingSystem" => "Any",
    ];
    if (!empty($settings["operator_name"])){
      $ld_data["provider"] = array_filter([
        "@type" => "Organization",
        "name" => $settings["operator_name"],
        "url" => $settings["operator_url"] ?? "",
      ]);
    }
    $ld_data["description"] = "Explore the ".ui_getCoinName()." blockchain with Solus Explorer: blocks, transactions, addresses, coin distribution,".$mn.$staking.$mining.$assets." and blockchain statistics.";
    $ld_data["offers"] = ["@type" => "Offer", "price" => "0", "priceCurrency" => "USD"];
    $json_ld = '<script nonce="'.$csp_nonce.'" type="application/ld+json">'.
      json_encode($ld_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG).'</script>';
  }

  // get data from database
  $tt->createTimer("ui_GetOverviewData()",1);
  $data = ui_GetOverviewData();
  $tt->stopTimer("ui_GetOverviewData()");

  // get rpc block height to see whether the node is synced
  $tt->createTimer("getRpcBlockCount();",1);
  $rpc_bc = getRpcBlockCount();
  $tt->stopTimer("getRpcBlockCount();");
  if ($data->last_block->number < ($rpc_bc - $settings["maturity"]) || $rpc_bc == -1) { $bcs = false; } else { $bcs = true; }

  $tt->createTimer("include_header",1);
  include_once __DIR__ . '/includes/header.php';
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
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">

            <!--Block Height Card-->
            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating primary-color btn-fl-color btn-md  accent-2 ml-0 " href="blocks/"><i class="fas fa-cubes card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="bcText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $data->last_block->number; ?></h2>
                  <p class="font-small h4-responsive primary-text">LAST BLOCK</p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text">Block Spacing</span>
                  <span id="asText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $data->block_spacing; ?></span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Block Height Card-->

          </div>
          <!--/.Grid column I-->

          <!--Grid column II-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">

            <!--Coin Supply Card-->
            <?php 
                  // $tt->createTimer("db_getDbCoinSupply",1);
                  // $db_cs = db_getDbCoinSupply(); 
                  // $tt->stopTimer("db_getDbCoinSupply");
                  // $cs_split = splitFloat(dflt($db_cs->coincirculation),$precision,$precision);
                  $coin_circulation = splitFloat($data->coin_supply->coincirculation,$precision,$precision);
                  $secondary_lead = "";
                  $secondary_data = "";
                  if (isset($settings["show_zerocoin"]) && $settings["show_zerocoin"]) {
                    if (isset($data->coin_supply->zerocoinsupply)) {
                      $secondary_lead = "Zerocoins";
                      $secondary_data =(splitFloat( $data->coin_supply->zerocoinsupply,$precision,$precision))->string;
                    }
                  } else if (isset($settings["show_shield"]) && $settings["show_shield"]){
                    if (isset($data->coin_supply->shieldsupply)) {
                      $secondary_lead = "Shielded";
                      $secondary_data = (splitFloat($data->coin_supply->shieldsupply,$precision,$precision))->string;
                    }
                  }
                  
            ?>
            <div class="card col-12">
              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" href="groups/"><i class="fas fa-coins card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="csText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $coin_circulation->whole."."; ?><small><?php echo $coin_circulation->fraction; ?></small></h2>
                  <p class="font-small h4-responsive primary-text"><?php echo "COIN CIRCULATION (".$settings["coin_symbol"].")"; ?></p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span id="zcHead" class="font-small pr-3 h6-responsive grey-text"><?php  echo  $secondary_lead; ?></span>
                  <span id="zcText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php  echo $secondary_data; ?></span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Coin Supply Card-->
          
          </div>
          <!--/.Grid column II-->

          <!--Grid column III-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
            <!--Hashrate Card-->
            <?php 
              if (isset($settings["show_pages"]["mining"]) && $settings["show_pages"]["mining"]){
                $hr_href = "mining/";
              } else {
                $hr_href = "staking/";
              }
            ?>
            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" href="<?php echo $hr_href;?>"><i class="fas fa-hashtag card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="hrText" class="ml-1 mt-3 mb-0 h2-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">
                    <?php echo $data->hashrate->value->whole."."; ?><small><?php echo $data->hashrate->value->fraction; ?></small>
                  </h2>
                  <p class="font-small h4-responsive primary-text"><?php echo "HASHRATE (<span id='hrDim'>".$data->hashrate->dimension."</span>H/s)"; ?></p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text "><?php echo "Difficulty"; ?></span>
                  <span id="difText" class="font-small font-up ml-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>">
                    <?php echo $data->difficulty->value->whole.".".$data->difficulty->value->fraction.$data->difficulty->dimension; ?>
                  </span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Hashrate Card-->
          </div>
          <!--/.Grid column III-->

          <!--Grid column IV-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
            <!--Masternodes Card-->
            <?php 
              if (isset($settings["show_pages"]["masternodes"]) && $settings["show_pages"]["masternodes"]){
                $tt->createTimer("ui_getMasternodeCount",1);
                if (isset($settings["masternode_caching_enabled"]) && $settings["masternode_caching_enabled"]){$ignore_cache = false;} else {$ignore_cache = true;}
                // $rpc_mnc = ui_getMasternodeCount($ignore_cache); 
                // $tt->stopTimer("ui_getMasternodeCount");
                // $tt->createTimer("getMnSyncStatus",1);
                // $rpc_mns = getMnSyncStatus();
                // $tt->stopTimer("getMnSyncStatus");
                // if(is_null($rpc_mns)){$rpc_mns=true;}
                $mt_text = $data->counts->mn_total;
                $me_text = $data->counts->mn_enabled;
                $mt_icon = "fab fa-hubspot"; 
                $mt_href = "href='masternodes/'";
                $me_desc = "Enabled";
                $mt_desc = "MASTERNODE COUNT";
              } else {
                // $tt->createTimer("ui_getMempoolInfo",1);
                // $rpc_mp = ui_getMempoolInfo();
                // $tt->stopTimer("ui_getMempoolInfo");
                $mt_text = $data->mempool->size;
                // $mp_bytes = normalizeSi($rpc_mp->bytes,3);
                //$me_text = fpp($mp_bytes->value,3)." ".$mp_bytes->dimension."Bytes";
                $me_text = $data->mempool->bytes_string;
                //$rpc_mns = $rpc_bcs;
                $mt_icon = "fas fa-hourglass-half";
                $mt_href = "";
                $me_desc = "Mempool size";
                $mt_desc = "TXS IN MEMPOOL";
              }
            ?>

            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a id="mtA" type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" <?php echo $mt_href; ?>><i class="<?php echo $mt_icon;?> card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h5 id="mtText" class="ml-1 mt-3 mb-0 h2-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text"; } ?>"><?php echo $mt_text; ?></h5>
                  <p id="mtDesc" class="font-small h4-responsive primary-text"><?php echo $mt_desc; ?></p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span id="meDesc" class="font-small pr-3 h6-responsive  grey-text"><?php echo $me_desc; ?></span>
                  <span id="meText" class="font-small font-up ml-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text"; } else { echo "dark-grey-text"; } ?>">
                    <?php echo $me_text; ?>
                  </span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Masternodes Card-->
          </div>
          <!--/.Grid column IV-->

      </div>
      <!--/.Grid row-->

      <!--Grid row-->
      <div class="row">
          <!--Grid column I-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">

            <!--Chain Height Card-->
            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color  accent-2 ml-0" href="<?php echo $settings["explorer_address"]; ?>/addresses/"><i class="fas fa-at card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="acText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $data->counts->addresses; ?></h2>
                  <p class="font-small h4-responsive primary-text">ADDRESS COUNT</p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text">Address Groups</span>
                  <span id="agText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $data->counts->addressgroups; ?></span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Chain Height Card-->

          </div>
          <!--/.Grid column I-->

          <!--Grid column II-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-exchange-alt card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="txText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $data->counts->txs; ?></h2>
                  <p class="font-small h4-responsive primary-text">TRANSACTIONS COUNT</p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text">Unspent TX Outputs</span>
                  <span id="utxoText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $data->counts->vouts; ?></span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Market Cap Card-->

          </div>
          <!--/.Grid column II-->    

          <!--Market DATA -->
            <?php 
              $tt->createTimer("getTimeSinceTimestamp",1);
              $mm_upd = getTimeSinceTimestamp(($data->market_data ?? null)?->timestamp);
              $tt->stopTimer("getTimeSinceTimestamp");  

              $cmc_split = splitFloat(
                isset($data->market_data->capitalization->in_usd) ? $data->market_data->capitalization->in_usd : 0,
                $precision,
                $precision
              );

              $avp_split = splitFloat(
                isset($data->market_data->avg_prices->in_btc) ? $data->market_data->avg_prices->in_btc : 0,
                $def_precision,
                $def_precision
              );

              $cng_split = splitFloat(
                isset($data->market_data->avg_percent_change) ? @cdec($data->market_data->avg_percent_change) : 0,
                2,
                2
              );

              if ($cng_split->number > 0) {
                $p_class = "text-success";
                $trend = '<i class="fas fa-long-arrow-alt-up ml-2" aria-hidden="true"></i>';
              } else if ($cng_split->number < 0){
                $p_class = "text-danger";
                $trend = '<i class="fas fa-long-arrow-alt-down ml-2" aria-hidden="true"></i>';
              } else {
                $p_class = "grey-text";
                $trend = '<i class="fas fa-arrows-alt-v" aria-hidden="true"></i>';
              }

            ?>
          <!--Grid column III-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" ><i class="fas fa-weight-hanging card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="ccText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>">$&nbsp;<?php echo $cmc_split->whole."."; ?><small><?php echo $cmc_split->fraction; ?></small></h2>
                  <p class="font-small h4-responsive primary-text">COIN CAPITALIZATION (USD)</p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text">Time Since Last Update </span>
                  <span id="tuText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo "dark-grey-text"; } ?>"><?php echo $mm_upd->string; ?></span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Market Cap Card-->

          </div>
          <!--/.Grid column III-->    

          <!--Grid column IV-->
          <div class="col-xl-3 col-lg-6 col-md-6 flex-col mb-4">
            <div class="card col-12">

              <!--Card Data-->
              <div class="row pr-3 pl-3">
                <div class="col-md-3 col-3 text-left">
                  <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0" href="<?php echo $settings["explorer_address"]?>/markets/"><i class="fas fa-chart-line card-icon-text" aria-hidden="true"></i></a>
                </div>

                <div class="col-md-9 col-9 text-right">
                  <h2 id="apText" class="ml-1 mt-3 h2-responsive mb-0 font-weight-bold <?php if (!$bcs) { echo "grey-text";} ?>"><?php echo $avp_split->whole.".".$avp_split->fraction; ?></h2>
                  <p class="font-small h4-responsive primary-text">AVERAGE COIN PRICE (BTC)</p>
                </div>
              </div>
              <!--/.Card Data-->

              <!--Card content-->
              <div class="row my-0 pr-3 pl-3">
                <div class="mb-2 col-12 text-right">
                  <span class="font-small pr-3 h6-responsive grey-text">24 Hours Change </span>
                  <span id="pcText" class="font-small font-up m-0 h6-responsive font-weight-bold <?php if (!$bcs) { echo "grey-text";} else { echo $p_class; } ?>"><?php echo $trend."&nbsp;".$cng_split->whole.".".$cng_split->fraction."%"; ?></span>
                </div>
              </div>
              <!--/.Card content-->

            </div>
            <!--/.Market Cap Card-->

          </div>
          <!--/.Grid column IV-->    
      </div>
      <!--/Grid row-->

      <!--Grid row-->
      <div class="row">
        <!--Grid column I-->
          <?php 

            $chartFontSize = 10;
            $minGridDistance = 30;
            $groupData = "false";
            $enableChartAnimation = false;
            $rid = db_createRequest();

          ?>
          <div class="col-xl-6 col-lg-12 col-md-12 flex-col mb-4">
            <div class="card col-12 pb-0 px-0">
              <div class="card-header primary-color card-header-text  py-2 text-left">
                <span class="h6 h6-responsive "><i class="fas fa-coins" aria-hidden="true"></i> COIN EMISSION </span>
              </div>
              <div class="card-body px-3 pb-2">
                <div class="">
                  <div id="lcCsPerDay" class="stats-chart pt-2" style="height: 20em;"></div>
                </div>
              </div>
            </div>
          </div>
          <script nonce="<?php echo $csp_nonce;?>">
            let l = {bc:<?php echo $data->last_block->number;?>,circ:<?php echo $coin_circulation->whole;?>};
            let cs_chart;
            $(document).ready(function(){
                am4core.ready(function() {
                  <?php if ($enableChartAnimation) : ?>
                  am4core.useTheme(am4themes_animated);
                  <?php endif; ?>
                  cs_chart = am4core.create("lcCsPerDay", am4charts.XYChart);
                  render_ccirc();
                  
                  function setChartDefaults(chart){
                    //chart.colors.step = 2;
                    chart.fontSize = <?php echo $chartFontSize; ?>;
                    chart.paddingBottom = 0;
                    chart.marginBottom = 0;
                    chart.responsive.enabled = true;
                    chart.zoomOutButton.scale = 0.5;

                    <?php if(!isset($settings["is_oem"]) || !$settings["is_oem"]) : ?>
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
                  }
                  function addDateAxis(chart){
                    var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
                    dateAxis.startLocation = 0;
                    dateAxis.endLocation = 1;
                    dateAxis.renderer.grid.template.location = 0;
                    dateAxis.renderer.minGridDistance = <?php echo $minGridDistance; ?>;
                    dateAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                    dateAxis.tooltipDateFormat = "d MMM, yyyy";
                    dateAxis.groupData = <?php echo $groupData;?>;
                    dateAxis.keepSelection = true;
                    setDateGridIntervals(dateAxis);
                    return dateAxis;
                  }
                  function addValueAxis(chart, title, logarithmic, opposite){
                    var  valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
                    valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                    valueAxis.renderer.minGridDistance = <?php echo $minGridDistance; ?>;
                    valueAxis.title.text = title;
                    if (!logarithmic) {valueAxis.min = 0;}else{valueAxis.logarithmic = logarithmic;}
                    valueAxis.opposite = opposite;
                    return valueAxis;
                  }
                  function setDateGridIntervals(axis){
                    axis.gridIntervals.setAll([
                      {timeUnit: "day", count: 1},
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
                  function addLineSeries(chart, name, field, measurement, filled, stacked, valueInLegend, color){
                    var lineSeries = chart.series.push(new am4charts.LineSeries());
                    lineSeries.dataFields.valueY = field;
                    lineSeries.dataFields.dateX = "date";
                    lineSeries.name = name;

                    lineSeries.tooltipText = "{name}: [bold]{valueY.value}[/] "+measurement;

                    lineSeries.tooltip.getFillFromObject = false;
                    lineSeries.tooltip.label.fill = am4core.color("#000");
                    lineSeries.tooltip.background.fill = am4core.color("#FFF");
                    lineSeries.tooltip.fontSize = <?php echo $chartFontSize; ?>;

                    lineSeries.tooltip.getStrokeFromObject = true;
                    lineSeries.tooltip.background.strokeWidth = 2;
                    lineSeries.stacked = stacked;

                    lineSeries.strokeWidth = 2;
                    lineSeries.stroke = am4core.color(color);
                    if (filled){
                        lineSeries.fill = am4core.color(color);
                        lineSeries.fillOpacity = 0.6;
                    }
                    if (valueInLegend){
                        // static
                        lineSeries.legendSettings.labelText = name+":";
                        lineSeries.legendSettings.valueText = "{valueY.close} "+measurement;

                        // hovering
                        lineSeries.legendSettings.itemLabelText = name+":";
                        lineSeries.legendSettings.itemValueText = "{valueY} "+measurement;
                    } else {
                        lineSeries.legendSettings.labelText = name;
                    }
                    return lineSeries;
                  }
                  function render_ccirc(){
                    setChartDefaults(cs_chart);
                    addDateAxis(cs_chart);
                    addValueAxis(cs_chart, "Coins (<?php echo $settings["coin_symbol"]; ?>)");
                    addLineSeries(cs_chart, "Circulation", "ccirc", "<?php echo $settings["coin_symbol"]; ?>", false, false, true,<?php echo "'".getNamedColor("info")."'";?>);
                    cs_chart.dataSource.url = '<?php echo $settings["explorer_address"]; ?>/_dp/ccircchartdp.php?rid=<?php echo $rid; ?>';
                  };
                });
            });
          </script>



        <!--Grid column II-->
          <div class="col-xl-6 col-lg-12 col-md-12 flex-col mb-4">
              <div class="card col-12 pb-3 px-0">
                <div class="card-header primary-color card-header-text  py-2 text-left">
                  <span class="h6 h6-responsive "><i class="fas fa-chart-line" aria-hidden="true"></i> MARKETS </span>
                </div>
                <div id="mktContainer" class="px-3"> 
                  <div class="row">
                    <div class="table-container col-12" id="topTableContainer">
                        <table class="table table-hover table-stripped table-sm pb-2" cellspacing="0" width="100%">
                          <thead>
                            <th class="th-sm font-weight-bold text-left">Exchange</th>
                            <th class="th-sm font-weight-bold text-right">Last Price</th>
                            <th class="th-sm font-weight-bold text-center">% Change</th>
                            <th class="th-sm font-weight-bold text-right">24H Volume<?php echo " (".$settings["coin_symbol"].")" ?></th>
                          </thead>
                          <tbody id="mkTbl">
                            <?php 
                              foreach ((($data->market_data ?? null)?->metrics ?? []) as $m){
                                $p_split = splitFloat($m->latest,$def_precision,$def_precision);
                                $v_split = splitFloat($m->quote_volume, $precision, $precision);
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
                                <td class='text-right p-1 " . ((!$bcs) ? "grey-text" : "" )."'>". $v_split->whole."<small>.".$v_split->fraction."</small></td>
                                </tr>";
                              }
                            ?>
                          </tbody>
                        </table>
                    </div>
                  </div>
                  <!--/.Card Data-->
                </div>
              </div>
          </div>
        <!--/Grid Column II -->
      </div>
      <!--/.Grid row-->

      <!--Grid row-->
        <div class="row"> 
          <!--Grid column I-->
            <div class="col-md-12 flex-col mb-4">
              <div class="card col-12 px-0">
                <div class="card-header primary-color card-header-text  py-2 text-left">
                  <span class="h6 h6-responsive "><i class="fas fa-sign-out-alt" aria-hidden="true"></i> TOP COIN MOVEMENTS (LAST&nbsp;<?php echo $data->top_days;?>&nbsp;DAYS) </span>
                </div>
                <div class="card-body pb-2">
                  <div class="row">
                    <div class="col-xl-6 col-lg-12 col-md-12 flex-col px-1">

                      <!--Card content-->

                        <div class="table-container col-12 pb-3" id="topSpentTableContainer" style="height:17.5em;">
                          <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
                            <thead>
                              <tr class="danger-color text-center">
                                <th colspan="2" class="h6 px-2 white-text">Top Sent</th>
                              </tr>
                              <tr class="table-secondary text-center">
                                <th class="th-sm px-2 text-left">Address</th>
                                <th class="th-sm px-2 text-right">Amount (<?php echo $settings["coin_symbol"]; ?>)</th>
                              </tr>
                            </thead>
                            <tbody id="saTbl">

                                <?php
                                $tt->createTimer("create_sent_table",1);
                                foreach ($data->top_spent as $am){
                                  $am_split = splitFloat($am->amount,$def_precision,$def_precision);
                                  //$ai = db_getAddressData($am->address)[0];
                                  //$at = db_getAddressTags($am->address);
                                  $tag_str = "";
                                  $tag_str .= ui_labelBadges($am->address_alias ?? '', $am->tags ?? [], 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                                  if (isset($am->suspect) && $am->suspect){
                                    $tag_str.= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movement'></i>&nbsp;";
                                  }
                                  echo "<tr><td class='text-left py-1 px-2 text-truncate'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str."&nbsp;
                                  <small><a class='' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a></small></td>
                                  <td class='text-right py-1 px-2 text-danger'>".$am_split->whole."<small>.".$am_split->fraction."</small></td></tr>";
                                }
                                $tt->stopTimer("create_sent_table");
                                ?>

                            </tbody>
                          </table>
                        </div>

                    </div>

                    <div class="col-xl-6 col-lg-12 col-md-12 flex-col px-1">

                      <!--Card content-->

                        <div class="table-container col-12 pb-3" id="topReceivedTableContainer" style="height:17.5em;">
                          <table class="table table-hover table-stripped table-sm" cellspacing="0" width="100%">
                            <thead>
                            <tr class="success-color text-center">
                                <th colspan="2" class="h6 px-2 white-text">Top Received</th>
                              </tr>
                              <tr class="table-secondary text-center">
                                <th class="th-sm px-2 text-left">Address</th>
                                <th class="th-sm px-2 text-right">Amount (<?php echo $settings["coin_symbol"]; ?>)</th>
                              </tr>
                            </thead>
                            <tbody id="raTbl">

                                <?php
                                $tt->createTimer("create_recv_table",1);
                                foreach ($data->top_received as $am){
                                  $am_split = splitFloat($am->amount,$def_precision,$def_precision);
                                  //$ai = db_getAddressData($am->address)[0];
                                  //$at = db_getAddressTags($am->address);
                                  $tag_str = "";
                                  $tag_str .= ui_labelBadges($am->address_alias ?? '', $am->tags ?? [], 'badge-info ml-1 mb-1', 'badge-primary ml-1 mb-1');
                                  if (isset($am->suspect) && $am->suspect){
                                    $tag_str.= "<i class='fas fa-exclamation-triangle text-danger ml-1 mb-1 p-1' title='Suspected Movement'></i>&nbsp;";
                                  }
                                  echo "<tr><td class='text-left py-1 px-2 text-truncate'><a class='primary-text' href='".$settings["explorer_address"]."/address/?address=".$am->address."'>".$am->address."</a>".$tag_str."&nbsp;
                                  <small><a class='' href='".$settings["explorer_address"]."/tx/?txid=".$am->tx_id."' title='Navigate to Transaction'><i class='fas fa-external-link-alt' aria-hidden='true'></i></a></small></td>
                                  <td class='text-right py-1 px-2 text-success'>".$am_split->whole."<small>.".$am_split->fraction."</small></td></tr>";
                                }
                                $tt->stopTimer("create_recv_table");
                                ?>

                            </tbody>
                          </table>
                        </div>

                    </div>
                  </div>
                </div>
              </div>
            </div>
          <!--/Grid column I-->
        </div>
      <!--/Grid column I-->

      </section>
      <!--/.Section: Button icon-->

      <?php 
        $tt->stopTimer("pageTotal");
        if (isset($mkey) && !empty($mkey) && isset($settings["magic_key"]) && $mkey == $settings["magic_key"]) : ?>
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


<script nonce="<?php echo $csp_nonce;?>" type='text/javascript'>
  $(document).ready(function(){
    setInterval(updateOverview, <?php echo $settings["gui_refresh_sec"];?>000,'<?php echo$settings["explorer_address"]; ?>');
  });
</script>
<?php
  $use_amcharts = true;
  include_once __DIR__ . '/includes/footer.php';
?>