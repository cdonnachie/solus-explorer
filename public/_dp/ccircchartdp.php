<?php 
    include_once __DIR__ . '/../../lib/common.php';
    require __DIR__ . '/../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../lib/explorerDb.php';
    include_once __DIR__ . '/../../lib/explorerUi.php';
    include_once __DIR__ . '/../../lib/timeTracker.php';
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    global $dbm;
    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["default_precision"];

    //$mn = (isset($_GET['mn'])) ? strtr($_GET['mn'],"_"," "):null;
    //$rid = (isset($_GET['rid'])) ? strtr($_GET['rid'],["_"=>""," "=>"","."=>"",","=>"","!"=>"","\\"=>"","'"=>"","\""=>"","{"=>"","}"=>"",":"=>"",";"=>""]) : "";

    $rid  = sanitizeInput("rid","GET");
    $mn = (isset($_GET['mn'])) ? strtr(sanitizeInput("mn","GET"),"_"," "):null;

    // function calculateAllOnDemand($ref_timestamps,$min_timestamp,$max_timestamp,$granularity_sec){
    //     global $res;
    //     global $settings;
        
    //     // populate timestamps
    //     $res->blockStats_data       = $ref_timestamps;
    //     $odres = ui_calculateCoinCirculationOnDemand($min_timestamp,$max_timestamp,$granularity_sec);

    //     // merge blockStats_data data
    //     foreach ($odres->blockStats_data as $ts=>$data){
    //         $res->blockStats_data[$ts] = $data;
    //     }
    // }

    $res = new stdClass();
    //global $res;
    $res->blockStats_data = array();

    $is_valid_rid = db_validateRequestId($rid);
    //$is_valid_rid = true;

    if ($is_valid_rid || ui_checkMagicNumber($mn)){
        $apt->createTimer("ui_calculateCoinCirculationChartData",1);
        $r = ui_calculateCoinCirculationChartData($apt);
        $apt->stopTimer("ui_calculateCoinCirculationChartData");

        $res->blockStats_data = $r->blockStats_data;
        $apt->stopTimer("total");
        //$z = $apt->getDurationSeconds("total");
        //$res->pt = (@cdec($apt->getDurationSeconds("total")))->toFixed(6);
    } else {
        $apt->stopTimer("total");
        //$res = new stdClass();
    }

    header('Content-Type: application/json');
    echo json_encode($res->blockStats_data);

    ui_LogPageEvent($cip,"_dp/circchart",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
    //echo "\n".$z."\n";
?>