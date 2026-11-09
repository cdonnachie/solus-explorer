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

    //$address = strtr(strtr($_GET['addr'],"_"," "),["'"=>"","."=>"","{"=>"","}"=>""]);
    $address =  strtr(sanitizeInput("addr","GET"),"_"," ");
    $gp = sanitizeInput("gp","GET");
    //$gp = strtr($_GET['gp'],"_"," ");
    //$rid = (isset($_GET['rid'])) ? strtr($_GET['rid'],["_"=>""," "=>"","."=>"",","=>"","!"=>"","\\"=>"","'"=>"","\""=>"","{"=>"","}"=>"",":"=>"",";"=>""]) : "";
    $rid  = sanitizeInput("rid","GET");
    $mn = (isset($_GET['mn'])) ? strtr(sanitizeInput("mn","GET"),"_"," "):null;

    $is_valid_rid = db_validateRequestId($rid);

    if ($is_valid_rid || ui_checkMagicNumber($mn)){
        $chartGranularity = 86400;
        $to = time();
        $from = 0;
        try {
            $chartGranularity = (int)$gp;
        } catch (Exception $e){}

        if (!is_null($address)){
            $apt->createTimer("ui_getAddressMovesChartData",1);
            //$chartData = ui_getAddressMovesChartData($address, $from, $to, $chartGranularity, 1000);
            $chartData = ui_getAddressMovesChartDataSip070($address, $from, $to, $chartGranularity, 1000);
            //$chartData = ui_getAddressMovesChartDataSip032($address, $from, $to, $chartGranularity, 1000); //SIP-032
            $apt->stopTimer("ui_getAddressMovesChartData",1);
        } else {
            $chartData = array();
        }
        header('Content-Type: application/json');
        echo json_encode(array_values($chartData));
    } else {
        header('Content-Type: application/json');
        echo json_encode(array_values(array()));
    }
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/addressmoveschart",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>