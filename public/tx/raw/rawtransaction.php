<?php 
    require __DIR__ . '/../../../settings.php';    
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerApi.php';
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    global $rpc;
    global $dbm;

    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["overview_precision"];
    $def_precision = (int)$settings["default_precision"];

    $txid = strtr(sanitizeInput('txid',"GET"),"_"," ");
    $tx_text = apiGetRawTransaction($txid,true);
?>

    <div class="row">
        <!--Grid column I-->
        <div class="col-12 mb-4">
            <!--Address Card (small)-->
            <div class="card col-12 d-md-none">
                <!--Card Data-->
                <div class="text-center">
                <div class="col p-2">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-auto px-1">
                            <h1 class="h4 h4-responsive m-0"><?php echo "Raw transaction"; ?><br>
                                <small class='grey-text'><?php echo $txid; ?></small>
                            </h1>
                        </div>
                    </div>
                </div>
                </div>
                <!--/.Card Data-->
            </div>
            <!--/.Address Card (small) Card-->

            <!--Address Header Card (big)-->
            <div class="card col-12 d-none d-md-flex">
                <!--Card Data-->
                <div class="col-12">
                    <div class="row align-items-center text-left">
                        <div class="col">
                        <div class="row">
                            <div class="pt-1 pl-2 ">
                                <a type="button" class="btn-floating btn-md primary-color btn-fl-color accent-2 ml-0 mr-2">
                                <i class="fas fa-exchange-alt card-icon-text" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="p-3 ">
                            <span class="h2 h2-responsive mb-0 font-weight-bold primary-text">
                                <div class="row align-items-center">
                                    <div class="col-auto pr-0">
                                        <h1 class="h5 h5-responsive m-0"><?php echo "Raw transaction"; ?><br>
                                            <small class='grey-text'><?php echo $txid; ?></small>
                                        </h1>
                                    </div>
                                </div>  
                            </span>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
                <!--/.Card Data-->
            </div>
            <!--/.Address Header Card (big)-->
        </div>
        <!--/.Grid column I-->
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <pre id="rawTx" style="text-wrap-mode:wrap;word-wrap:break-word;"></pre>
                </div>
            </div>
        </div>
    </div>


    <script>
        document.getElementById('rawTx').innerHTML = jsonText(<?php echo $tx_text; ?>);
    </script>   

<?php 
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"transaction/raw",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>