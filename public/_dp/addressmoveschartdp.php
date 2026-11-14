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

    $chartData = array();
    $unavailable = false;

    if ($is_valid_rid || ui_checkMagicNumber($mn)){
        $to = time();
        $from = 0;

        /* A cast cannot throw, so the try/catch this replaced caught nothing -- and
           (int) of a missing parameter is 0, which would have asked the server for a
           modulus by zero and then stepped the fill loop by zero seconds. */
        $chartGranularity = ui_ClampChartGranularity($gp);

        if (!is_null($address)){
            $slow_key = "AMOVESCHART:".$address.":".$chartGranularity;
            if (db_isMarkedSlow($slow_key)){
                /* Already known, recently, to be beyond the budget. Answer now
                   rather than spending the budget again for the same answer. */
                $unavailable = true;
            } else {
                $apt->createTimer("ui_getAddressMovesChartData",1);
                try {
                    $chartData = ui_getAddressMovesChartDataSip070(
                        $address, $from, $to, $chartGranularity, 1000,
                        db_getAddressChartTimeLimitMs()
                    );
                } catch (Throwable $e) {
                    /* Budget gone, or the server went away mid-aggregation. There is
                       no chart either way; say so, and remember it.
                       Throwable, not Exception: PHP 8 raises Error for arithmetic
                       faults, and this page is already drawn by the time the chart is
                       fetched -- a decoration must not be able to turn it into a 500. */
                    $unavailable = true;
                    db_markSlow($slow_key);
                }
                $apt->stopTimer("ui_getAddressMovesChartData",1);
            }
        }
    }

    header('Content-Type: application/json');
    if ($unavailable){
        /* A status the caller can act on. Answering 200 with an empty series would
           tell the reader this address has never moved, which is the one thing we
           know to be untrue. */
        http_response_code(503);
        echo json_encode(array("status" => "error", "error" => "Chart data unavailable for this address"));
    } else {
        echo json_encode(array_values($chartData));
    }
    $apt->stopTimer("total");
    ui_LogPageEvent($cip,"_dp/addressmoveschart",(int)$apt->getDurationMicroSeconds("total"),$_REQUEST);
?>