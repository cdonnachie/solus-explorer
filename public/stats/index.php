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
  
  $chartFontSize = 9;
  $minGridDistance = 30;
  $groupData = "true";
  $enableChartAnimation = false;
  $rid = db_createRequest();
  $mn = (isset($_GET['mn'])) ? strtr(sanitizeInput('mn',"GET"),"_"," "):null;

  global $csp_nonce;
  $csp_nonce = random_hex(8);

  global $description;
  $description = "One-page ".ui_getCoinName()." statistics: TPS, CDD, block size, chain growth, address count, and more—updated live.";
  global $keywords;
  $keywords = $settings["title"].", statistics, tx rate, block size, vout types, tx types, coindays destroyed, address count, group count, coin supply, block versions"; 
  global $title;
  $title = ui_getCoinName()." Chain Statistics | TPS, Size & Address Growth";
  global $page_id;
  $page_id = "Stats";

?>
<?php if ( (isset($settings["show_pages"]["stats"]) && $settings["show_pages"]["stats"]) || (isset($mn) && !empty($mn) && isset($settings["magic_key"]) && $mn == $settings["magic_key"])) : ?>
 
  <?php
  include_once __DIR__ . '/../includes/header.php';
  ?>
  <!--Main layout-->
  <main>
    <div id="content" class="container-fluid">
<!--Section: Button icon-->
    <section>
    <h5 class="primary-text mb-4">General Blockchain Statistics</h5>
    <!--Grid row-->
    <div class="row">
        <!-- TX/s -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="txps" class="card w-100" >
                <div id="txps-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">TX per Second</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="txps" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_txps"  class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_txps(){
                            var chart = am4core.create("ac_txps", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "TX per Second")
                            addLineSeries(chart, "TX/s (all)","txps", "", false, false, true);
                            addLineSeries(chart, "TX/s (non-reward)", "rtxps", "",false, false, true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="txps-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">TX per Second</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="txps" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>Transactions per second shows the utilisation of the blockchain for the coin transmission.</p>
                        <p>TX per Second calculated as a number of transactions divided by the number of second within the measurement interval.</p>
                        <ul>
                            <li><b>TX/s (all)</b> transactions per second including all transactions.</li>
                            <li><b>TX/s (non-reward)</b> transactions per second excluding reward and nonstandard transactions.</li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
        
        <!-- TX Types -->
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="txtypes" class="card w-100">
                <div id="txtypes-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">TX Types</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="txtypes" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_txtypes" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_txtypes(){
                            // Create chart instance
                            var chart = am4core.create("ac_txtypes", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            addValueAxis(chart, "TX Number")
                            chart.data = txdata;
                            txtypesdata.forEach(function(type){
                                if(!(['new_asset','transfer_asset','reissue_asset']).includes(type)){
                                    addStepLineSeries(chart, type, type, "", true, true, false);
                                }
                            });                    
                        };
                    </script>
                </div>

                <div id="txtypes-back" class="card card-body light-gray-color fading-back" >
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">TX Types</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="txtypes" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2" >
                        <hr>
                        <p>Transaction types shows the distribution between different transaction types.</p>
                        <p>Following types can be found:<br>
                        <ul>
                            <li><b>reward</b> a transaction with the new coins rewarded to the block winner</li>
                            <li><b>nonstandard</b> a transaction used in the Proof-of-Stake blockchains as a part of the block reward solution</li>
                            <?php if(isset($settings["dip2_support"])&&$settings["dip2_support"]): ?>
                            <li><b>dip2</b> a transaction related to the Deterministic Masternode Lists feature (DIP2/DIP3)</li>
                            <?php endif; ?>
                            <?php if(isset($settings["rvn_assets"])&&$settings["rvn_assets"]): ?>
                            <li><b>asset</b> a transaction related to the on-chain asset use</li>
                            <?php endif; ?>
                            <li><b>coldstake</b> a transaction related to the Cold Staking feature</li>
                            <li><b>regular</b> a general coin transfer transaction</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->

    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="agcnt" class="card w-100">
                <div id="agcnt-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Address and Group Count</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="agcnt" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_agcnt" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_agcnt(){
                            var chart = am4core.create("ac_agcnt", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            addValueAxis(chart, "Number");
                            addLineSeries(chart, "Addresses", "acnt", "",false, false, true);
                            addLineSeries(chart, "Groups", "gcnt", "",false, false, true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="agcnt-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Address and Group Count</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="agcnt" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">

                    <hr>
                        <p>Address and Group count values shows the address and addressgroup base growth over the time.</p>
                        <p><b>Address</b> - a number of unique addresses in the blockchain being used by the measurement timestamp.<br>
                           <b>Groups</b> - a number of unique address groups in the blockchain being detected by the measurement timestamp.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="vouttypes" class="card w-100">
                <div id="vouttypes-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">VOUT Types</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="vouttypes" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_vouttypes" class="stats-chart pt-2" ></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_vouttypes(){
                            var chart = am4core.create("ac_vouttypes", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            addValueAxis(chart, "VOUT Number");
                            //vouttypesdata.forEach(type => addColumnSeries(chart,type,type,"",true,true,false));
                            vouttypesdata.forEach(type => addStepLineSeries(chart,type,type,"",true,true,false));
                            setVanishingLegend(chart, 400);
                            chart.data = voutdata;
                        };
                    </script>
                </div>

                <div id="vouttypes-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">VOUT Types</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="vouttypes" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>VOUT types shows the distribution between different VOUT types.</p>
                        <p>Following types can be found:<br>
                        <ul>
                            <li><b>pubkey</b> Pay to public key type (P2PK) </li>
                            <li><b>pubkeyhash</b> Pay to public key hash type (P2PKH)</li>
                            <li><b>scripthash</b> Pay to script hash type (P2SH) </li>
                            <?php if(isset($settings["segwit"])&&isset($settings["segwit"]["supported"])&&$settings["segwit"]["supported"]): ?>
                            <li><b>witness_v0_keyhash</b> Pay to witness private key hash type (P2WPKH) </li>
                            <li><b>witness_v0_scripthash</b> Pay to witness script hash type (P2WSH) </li>
                            <?php endif; ?>
                            <li><b>multisig</b> Pay to multi-signature type (P2MS) </li>
                            <li><b>nulldata</b> </li>
                            <li><b>nonstandard</b> </li>
                            <?php if(isset($settings["rvn_assets"])&&$settings["rvn_assets"]): ?>
                            <li><b>nullassetdata</b> </li>
                            <li><b>new_asset</b> Asset creation type</li>
                            <li><b>transfer_asset</b> Asset transfer type</li>
                            <li><b>reissue_asset</b> Asset re-issue type</li>
                            <?php endif; ?>
                            <li><b>coldstake</b> Type related to coldstake operation</li>
                            <li><b>shield</b> Type related to Shield feature</li>
                            <li><b>exchangeaddress</b> Type related to exchange addresses in Shield feature</li>
                            <li><b>zerocoinmint</b> Type related to the Zerocoin minting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->

    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="avgbs" class="card w-100">
                <div id="avgbs-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Block Size</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="avgbs" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_avgbs" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_avgbs(){
                            var chart = am4core.create("ac_avgbs", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Bytes");
                            addLineSeries(chart, "Average Block Size", "avgbs", "Bytes", false, false, true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="avgbs-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Block Size</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="avgbs" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>Average block size shows effective load of the blockchain over the measurement period.</p>
                        <p>Block size measured in Bytes.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="blkver" class="card w-100">
                <div id="blkver-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Block Versions</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="blkver" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_blkver" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_blkversions(){
                            var chart = am4core.create("ac_blkver", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Percent");
                            valueAxis.calculateTotals = true;
                            valueAxis.min = 0;
                            valueAxis.max = 100;
                            valueAxis.strictMinMax = true;
                            valueAxis.renderer.labels.template.adapter.add("text", function(text) {
                                return text + "%";
                            });
                            blkversions.forEach(function(type){
                                stepSeries = addStepLineSeries(chart,type,type,"",true,true,false);
                                stepSeries.dataFields.valueYShow = "totalPercent";
                            });
                            setVanishingLegend(chart, 400);
                            chart.data = blkdist;
                        };
                    </script>
                </div>

                <div id="blkver-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Block Versions</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="blkver" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows distribution of blocks between different versions.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->

    </section>
    <!--/Section: Button icon-->

    <!--Section: Button icon-->
    <section>
    <h5 class="primary-text mb-4 mt-5">Coinnomics Statistics</h5>
    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="cprice" class="card w-100">
                <div id="cprice-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Price (BTC)</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="cprice" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_cprice" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_cprice(){
                            var chart = am4core.create("ac_cprice", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Price (BTC)");
                            valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                            valueAxis.numberFormatter.numberFormat = "#.00000000";
                            valueAxis.adjustLabelPrecision = false;
                            valueAxis.extraTooltipPrecision = 0.00000001;

                            lineSeries = addLineSeries(chart, "Price (BTC)", "vwap_btc", "", false, false, true);
                            lineSeries.tooltipText = "{name}: [bold]{valueY.value.formatNumber('#.00000000')}[/]";
                            lineSeries.connect = true;
                            lineSeries.legendSettings.valueText = "{valueY.close.formatNumber('#.00000000')}";
                            lineSeries.legendSettings.itemValueText = "{valueY.formatNumber('#.00000000')}";

                            chart.data = stats;
                        }
                    </script>
                </div>

                <div  id="cprice-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Price (BTC)</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="cprice" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the price evolution of the coin over time.</p>
                        <p>Reported value is a volume weighted average price in BTC. Data collected from the selected markets using this explorer API collector. <br>
                        This data may deviate from the ones provided by the market aggregators, such as CMC.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="cpriceu" class="card w-100">
                <div id="cpriceu-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Price (USD)</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="cpriceu" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_cpriceu" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_cpriceu(){
                            var chart = am4core.create("ac_cpriceu", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Price (USD)");
                            valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                            valueAxis.numberFormatter.numberFormat = "#.00";
                            valueAxis.adjustLabelPrecision = true;
                            valueAxis.extraTooltipPrecision = 0.0001;

                            lineSeries = addLineSeries(chart, "Price (USD)", "vwap_usd", "", false, false, true);
                            lineSeries.tooltipText = "{name}: [bold]{valueY.value.formatNumber('#.0000')}[/]";
                            lineSeries.connect = true;
                            lineSeries.legendSettings.valueText = "{valueY.close.formatNumber('#.0000')}";
                            lineSeries.legendSettings.itemValueText = "{valueY.formatNumber('#.0000')}";

                            chart.data = stats;
                        }
                    </script>
                </div>

                <div  id="cpriceu-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Price (USD)</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="cpriceu" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the price evolution of the coin over time.</p>
                        <p>Reported value is a volume weighted average price in USD. Data collected from the selected markets using this explorer API collector. <br>
                        This data may deviate from the ones provided by the market aggregators, such as CMC.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->

    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="fpkb" class="card w-100">
                <div id="fpkb-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Transaction Fee Rate</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="fpkb" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_fpkb" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_fpkb(){
                            var chart = am4core.create("ac_fpkb", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Fee per Byte (<?php echo $settings["coin_symbol"]; ?>)");
                            valueAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
                            valueAxis.numberFormatter.numberFormat = "#.00000000";
                            valueAxis.adjustLabelPrecision = false;
                            valueAxis.extraTooltipPrecision = 0.00000001;
                            addLineSeries(chart, "Fee per Byte", "fpkb", "<?php echo $settings["coin_symbol"]; ?>", false, false, true);
                            chart.data = stats;
                        };
                    </script>
                </div>

                <div id="fpkb-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Transaction Fee Rate</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="fpkb" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows transaction fee rate in <?php echo $settings["coin_symbol"];?> per byte of transaction size.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="ccirc" class="card w-100">
                <div id="ccirc-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Circulation</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="ccirc" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_ccirc" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_ccirc(){
                            var chart = am4core.create("ac_ccirc", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            addValueAxis(chart, "Coins (<?php echo $settings["coin_symbol"]; ?>)");
                            addLineSeries(chart, "Circulation", "ccirc", "<?php echo $settings["coin_symbol"]; ?>", true, true, true);
                            addLineSeries(chart, "Burned", "cburn", "<?php echo $settings["coin_symbol"]; ?>", true, true, true);
                            addLineSeries(chart, "Supply", "csupl", "<?php echo $settings["coin_symbol"]; ?>", false, false, true);
                            chart.data = stats;
                        };
                    </script>
                </div>

                <div id="ccirc-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Circulation</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="ccirc" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the coin supply, burned coins and coin circulation.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->

    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="cdd" class="card w-100">
                <div id="cdd-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Days Destroyed</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="cdd" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_cdd" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_cdd(){
                            var chart = am4core.create("ac_cdd", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Coin Days Destroyed", true);
                            addLineSeries(chart,"Coin Days Destroyed", "cdd", "", false, false, true);
                            addLineSeries(chart,"Adjusted Coin Days Destroyed", "acdd", "", false, false, true);
                            chart.data = stats;
                        }
                    </script>

                </div>

                <div id="cdd-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Days Destroyed</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="cdd" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows coin economical activity with respect to the coin holding duration.</p>
                        <p><b>Coin Days Destroyed (CDD)</b> for any given transaction is calculated by taking the number of coins in a transaction and multiplying it by the number of days it has been since those coins were last spent.<br>
                            CDD <u>doesn't</u> include coins sent to the same address, e.g. in case of sending change to any of addresses appearing in the transaction VINs.
                        <p><b>Adjusted Coin Days Destroyed (ACDD)</b> is a CDD value normalized to the Circulating Supply. <br>Adjusted CDD more accurately represents the quantity of coins sold by long-term holders over time. It removes the influence of the new coins emission over the time.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="cmove" class="card w-100">
                <div id="cmove-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Movement</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="cmove" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_cmove" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_cmove(){
                            var chart = am4core.create("ac_cmove", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Coins");
                            movetypes.forEach(type => addStepLineSeries(chart,type.name,type.field,"<?php echo $settings["coin_symbol"]; ?>", true, true, false));
                            chart.data = stats;
                        };
                    </script>
                </div>

                <div id="cmove-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Coin Movement</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="cmove" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows an economical activity of the coin in a classical representation of the coins being moved in the transactions over time.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->
    </section>

    <?php if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]) : ?>
    <!--Section: Button icon-->
    <section>
    <h5 class="primary-text mb-4 mt-5">Asset-specific Statistics</h5>
    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="astcnt" class="card w-100">
                <div id="astcnt-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Asset Count</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="astcnt" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_astcnt" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_astcnt(){
                            var chart = am4core.create("ac_astcnt", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Number");
                            assettypes.forEach(type => addStepLineSeries(chart,type,type,"",true,true,false));
                            addStepLineSeries(chart, "Assets (non-admin)","non-admin","",false,false,true);
                            setVanishingLegend(chart, 400);
                            chart.data = assetdata;
                        }
                    </script>
                </div>

                <div id="astcnt-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Asset Count</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="astcnt" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the number of the assets per its type created over time.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="atxtypes" class="card w-100">
                <div id="atxtypes-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Asset TX Types</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="atxtypes" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_atxtypes" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_atxtypes(){
                            // Create chart instance
                            var chart = am4core.create("ac_atxtypes", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "TX Number");
                            (['new_asset','transfer_asset','reissue_asset']).forEach(type => addStepLineSeries(chart,type,type,"",true,true,false));
                            chart.data = txdata;
                        };
                    </script>
                </div>

                <div id="atxtypes-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">TX Types</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="atxtypes" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="scrollable pt-2" style="height:25em;">
                    <hr>
                        <p>This chart shows the distribution between different asset transaction types.</p>
                        <p>Following types can be found:<br>
                        <ul>
                            <li><b>new_asset</b> creation of a new asset</li>
                            <li><b>transfer_asset</b> transfer of the existing asset to another address</li>
                            <li><b>reissue_asset</b> additional supply of the existing asset</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->
    </section>
    <?php endif; ?>

    <?php if (isset($settings["show_pages"]["mining"]) && $settings["show_pages"]["mining"]) : ?>
    <!--Section: Button icon-->
    <section>
    <h5 class="primary-text mb-4 mt-5">Proof-of-Work Statistics</h5>
    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="powhr" class="card w-100">
                <div id="powhr-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Proof-of-Work Hashrate</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="powhr" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_powhr" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_powhr(){
                            var chart = am4core.create("ac_powhr", am4charts.XYChart);
                            chart.numberFormatter.numberFormat = "#.###a";
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Hashrate (H/s)");
                            addLineSeries(chart,"Hashrate","powhr","H/s",false,false,true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="powhr-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Proof-of-Work Hashrate</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="powhr" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the evolution of the aggregated computation power being used in the blockchain for the purpose of mining a new block.<br>
                        This computation power is traditionally measured in the number of hashing operations per second (H/s).</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="powdif" class="card w-100">
                <div id="powdif-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Proof-of-Work Difficulty</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="powdif" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_powdif" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_powdif(){
                            var chart = am4core.create("ac_powdif", am4charts.XYChart);
                            chart.numberFormatter.numberFormat = "#.###a";
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Difficulty");
                            addLineSeries(chart,"Difficulty","powdif","",false,false,true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="powdif-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Proof-of-Work Difficulty</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="powdif" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the evolution of the computation difficulty for the task of mining a new block.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->
    </section>
    <?php endif; ?>

    <?php if (isset($settings["show_pages"]["staking"]) && $settings["show_pages"]["staking"]) : ?>
    <!--Section: Button icon-->
    <section>
    <h5 class="primary-text mb-4 mt-5">Proof-of-Stake Statistics</h5>
    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="avgsb" class="card w-100">
                <div id="avgsb-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Stake Base</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="avgsb" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_avgsb" class="stats-chart pt-2"></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_avgsb(){
                            var chart = am4core.create("ac_avgsb", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Stake Base (<?php echo $settings["coin_symbol"]; ?>)");
                            addLineSeries(chart,"Average Stake Base","avgsb","<?php echo $settings["coin_symbol"]; ?>",false,false,true);
                            chart.data = blkdata;
                        }
                    </script>
                </div>

                <div id="avgsb-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Stake Base</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="avgsb" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the evolution of stake base amount over time.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="avgsr" class="card w-100">
                <div id="avgsr-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Proof-of-Stake Reward</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="avgsr" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_avgsr" class="stats-chart pt-2" style="height:25em;">Line chart</div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_avgsr(){
                            var chart = am4core.create("ac_avgsr", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "PoS Reward (<?php echo $settings["coin_symbol"]; ?>)");
                            addLineSeries(chart,"Average PoS Reward","avgsr","<?php echo $settings["coin_symbol"]; ?>",false,false,true);
                            chart.data = blkdata;
                        }
                    </script>
                </div>

                <div id="avgsr-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Proof-of-Stake Reward</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="avgsr" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2" style="height:25em;">
                    <hr>
                        <p>This chart shows th evolution of the Proof-of-Stake reward per block over time.</p>
                        <p><i>Note: the PoS block reward amount may contain block fees which will lead to the deviation of the reward from the planned block subsidy.</i></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->
    </section>
    <?php endif; ?>

    <?php if (isset($settings["show_pages"]["masternodes"]) && $settings["show_pages"]["masternodes"]) : ?>
    <!--Section: Button icon-->
    <section>
    <h5 class="primary-text mb-4 mt-5">Masternode Statistics</h5>
    <!--Grid row-->
    <div class="row">
        <div class="col-lg-6 col-md-12 mb-4">
            <div id="mncnt" class="card w-100">
                <div id="mncnt-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Masternode Count</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="mncnt" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_mncnt" class="stats-chart pt-2" ></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_mncnt(){
                            var chart = am4core.create("ac_mncnt", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Masternode Count");
                            addLineSeries(chart,"Masternode Count","mnttl","",false,false,true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="mncnt-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Masternode Count</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="mncnt" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the evolution of average number of masternodes in the network over time.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12 mb-4">
            <div id="avgmnr" class="card w-100">
                <div id="avgmnr-front" class="card card-body fading-front">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Daily Masternode Reward</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive fader" flips="avgmnr" title="Show description"><i class="far fa-question-circle"></i></div>
                    </div>

                    <div id="ac_avgmnr" class="stats-chart pt-2" ></div>
                    <script nonce="<?php echo $csp_nonce;?>">
                        function render_avgmnr(){
                            var chart = am4core.create("ac_avgmnr", am4charts.XYChart);
                            setChartDefaults(chart);
                            addDateAxis(chart);
                            valueAxis = addValueAxis(chart, "Reward (<?php echo $settings["coin_symbol"]; ?>)");
                            addLineSeries(chart,"Average Daily Reward","avgmnr","<?php echo $settings["coin_symbol"]; ?>",false,false,true);
                            chart.data = stats;
                        }
                    </script>
                </div>

                <div id="avgmnr-back" class="card card-body light-gray-color fading-back">
                    <div class="row primary-text">
                        <div class="col-auto mr-auto"><span class="h6 h6-responsive">Average Daily Masternode Reward</span></div>
                        <div class="col-auto"><span class="h6 h6-responsive unfader" flips="avgmnr" title="Close description"><i class="far fa-times-circle text-danger"></i></div>
                    </div>

                    <div class="stats-chart scrollable pt-2">
                    <hr>
                        <p>This chart shows the evolution of average daily masternode reward over time.</p>
                        <p></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--Grid row-->
    </section>
    <?php endif; ?>

    <script defer nonce="<?php echo $csp_nonce;?>">
        var stats = [];
        var txdata = [];
        var blkdata = [];
        var voutdata = [];
        var blkdist = [];
        var txtypesdata = [];
        var vouttypesdata = [];
        var blkversions = [];
        var movetypes = [];
        <?php if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]) : ?>
        var assetdata = [];
        var assettypes = [];
        <?php endif; ?>
        $(document).ready(function(){
            $(".fader").on("click", function(){
                var e = $(this).attr('flips');
                $('#'+e+'-front').css('opacity', '0');
                $('#'+e+'-back').css('opacity', '1');
                setTimeout(function(){
                     $('#'+e+'-front').css("z-index",1);
                 },300);
            });
            $(".unfader").on("click", function(){
                var e = $(this).attr('flips');
                $('#'+e+'-front').css("z-index", 51);
                $('#'+e+'-back').css('opacity', '0');
                $('#'+e+'-front').css('opacity', '1');
            });
            
            am4core.ready(function() {
                <?php if ($enableChartAnimation) : ?>
                am4core.useTheme(am4themes_animated);
                <?php endif; ?>

                $.ajax({
                  url : '<?php echo $settings["explorer_address"]; ?>/_dp/statschartdp.php?rid=<?php echo $rid; ?>',
                  type : 'GET',
                  dataType : 'json',
                  success : (function ( json ) {
                    if (json.blockStats_data){stats = json.blockStats_data;}
                    if (json.blockTypes_data){blkdata = json.blockTypes_data;}
                    if (json.blockVersions_data){blkdist = json.blockVersions_data;}
                    if (json.blockVersions){blkversions = json.blockVersions;}
                    if (json.moveTypes){movetypes = json.moveTypes;}
                    if (json.txTypes_data){txdata = json.txTypes_data;}
                    if (json.txTypes){txtypesdata = json.txTypes;}
                    if (json.voutTypes_data){voutdata = json.voutTypes_data;}
                    if (json.voutTypes){vouttypesdata = json.voutTypes;}
                    render_txps();render_txtypes();
                    render_agcnt();render_avgbs();
                    render_vouttypes();render_blkversions();
                    render_cprice();render_cpriceu();render_fpkb();render_ccirc();
                    render_cdd();render_cmove();
                    <?php if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]) : ?>
                    if (json.assetTypes_data){assetdata = json.assetTypes_data;}
                    if (json.assetTypes){assettypes = json.assetTypes;}
                    render_astcnt();render_atxtypes();
                    <?php endif; ?>
                    <?php if (isset($settings["show_pages"]["mining"]) && $settings["show_pages"]["mining"]) : ?>
                    render_powhr();render_powdif();
                    <?php endif; ?>
                    <?php if (isset($settings["show_pages"]["staking"]) && $settings["show_pages"]["staking"]) : ?>
                    render_avgsb();render_avgsr();
                    <?php endif; ?>
                    <?php if (isset($settings["show_pages"]["masternodes"]) && $settings["show_pages"]["masternodes"]) : ?>
                    render_mncnt();render_avgmnr();
                    <?php endif; ?>

                  }),
                });



            });
        });        
        function setVanishingLegend(chart, minWidth){
          chart.responsive.enabled = true;

          chart.responsive.rules.push({
            relevant: function(target) {
              if (target.pixelWidth <= minWidth) {
                return true;
              }
              return false;
            },
            state: function(target, stateId) {
              if (target instanceof am4charts.Chart) {
                var state = target.states.create(stateId);
                target.legend.dispose();
                return state;
              }
              return null;
            }
          });
        };
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
        };
        function setChartDefaults(chart){
            chart.colors.step = 2;
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
        function addDateAxis(chart){
            var dateAxis = chart.xAxes.push(new am4charts.DateAxis());
            dateAxis.startLocation = 0;
            dateAxis.endLocation = 1;
            dateAxis.renderer.grid.template.location = 0;
            dateAxis.renderer.minGridDistance = <?php echo $minGridDistance; ?>;
            dateAxis.tooltip.fontSize = <?php echo $chartFontSize; ?>;
            dateAxis.tooltipDateFormat = "d MMM, yyyy";
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
        function addLineSeries(chart, name, field, measurement, filled, stacked, valueInLegend){
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

            lineSeries.strokeWidth = 1.25;
            if (filled){
                lineSeries.fillOpacity = 0.85;
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
        function addColumnSeries(chart, name, field, measurement, filled, stacked, valueInLegend){

            var columnSeries = chart.series.push(new am4charts.ColumnSeries());
            columnSeries.dataFields.valueY = field;
            columnSeries.dataFields.dateX = "date";
            columnSeries.name = name;
            columnSeries.sequencedInterpolation = true;
            columnSeries.columns.template.width = am4core.percent(100);
            columnSeries.tooltipText = "{name}: [bold]{valueY.value}[/] "+measurement;
            columnSeries.tooltip.getFillFromObject = false;
            columnSeries.tooltip.label.fill = am4core.color("#000");
            columnSeries.tooltip.background.fill = am4core.color("#FFF");
            columnSeries.tooltip.fontSize = <?php echo $chartFontSize; ?>;
            columnSeries.tooltip.getStrokeFromObject = true;
            columnSeries.tooltip.background.strokeWidth = 2;

            if (filled){
                columnSeries.fillOpacity = 0.85;
            }
            columnSeries.stacked = stacked;
            columnSeries.connect = false;
            columnSeries.strokeWidth = 0;

            if (valueInLegend){
                columnSeries.legendSettings.labelText = name+":";
                columnSeries.legendSettings.valueText = "{valueY.close} "+measurement;
                columnSeries.legendSettings.itemLabelText = name+":";
                columnSeries.legendSettings.itemValueText = "{valueY} "+measurement;
            } else {
                columnSeries.legendSettings.labelText = name;
            }
            return columnSeries;
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
            return stepSeries;
        }
    </script>

    </div>
</main>
<!--/Main layout-->


  <?php
    $use_amcharts = true;
    include_once __DIR__ . '/../includes/footer.php';
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"stats",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);

  ?>
<?php else :?>
  <?php header("Location: /err/404/");
    exit(0);
  ?>
<?php endif; ?>

