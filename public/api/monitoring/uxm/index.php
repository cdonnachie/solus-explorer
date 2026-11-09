<?php 
    require __DIR__ . '/../../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../../lib/explorerUi.php';

    include_once __DIR__ . '/../../../../lib/common.php';
    $in = beacon_body();
    if (is_null($in) || !is_string($in["rId"] ?? null)) {
        http_response_code(400);
        return;
    }
    $rid = clearWebParameter($in["rId"]);

    // check if rid is registered - send 404 if not
    $is_valid_rid = db_validateRequestId($rid);

    if (!$is_valid_rid) {
        http_response_code(404);
        return;
    }

    // get client info from request
    $page_metric = ui_GetClientInfo();

    // extend metric with data from the call
    $page_metric["ts"] = (int)($in["ts"] ?? 0);
    $page_metric["viewport"] = (is_array($in["viewport"] ?? null) && strlen(json_encode($in["viewport"])) <= 1024) ? $in["viewport"] : null;
    $page_metric["vitals"] = (is_array($in["vitals"] ?? null) && strlen(json_encode($in["vitals"])) <= 4096) ? $in["vitals"] : null;
    $page_metric["url"] = substr((string)($in["pageUrl"] ?? ''), 0, 512);
    $page_metric["root"] = ui_getRootURL();
    $page_metric["path"] = trim(str_replace(ui_getRootURL(), "", $page_metric["url"]), "/");

    // add coin name to metric
    $page_metric["coin"] = ui_getCoinName();//strtolower($settings["coin_symbol"]);

    // if configured, add:
    //    - ads provider

    // store data in the database
    db_recordPageMetrics($page_metric);

    // forward to global API endpoint

    // respond with 200
    http_response_code(200);

    // test only
    // header('Content-Type: application/json');
    // echo json_encode($page_metric);
?>