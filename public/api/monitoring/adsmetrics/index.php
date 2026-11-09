<?php 
    require __DIR__ . '/../../../../settings.php';
    global $settings;
    include_once __DIR__ . '/../../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../../lib/explorerUi.php';

    include_once __DIR__ . '/../../../../lib/common.php';
    $in = beacon_body();
    if (is_null($in) || !is_string($in["r"] ?? null)) {
        http_response_code(400);
        return;
    }
    $r = clearWebParameter($in["r"]);

    // check if rid is registered - send 404 if not
    $is_valid_rid = db_validateAdsRequestId($r);

    if (!$is_valid_rid) {
        http_response_code(404);
        return;
    }

    // get client info from request
    $page_metric = ui_GetClientInfo();

    $ads_metric = array();
    // take required client data
    $ads_metric["ip"] = $page_metric["clientIp"];
    $ads_metric["browser"] = $page_metric["browser"];
    $ads_metric["browser_version"] = $page_metric["browser_version"];
    $ads_metric["mobile"] = $page_metric["mobile"];
    $ads_metric["platform"] = $page_metric["platform"];
    $ads_metric["platform_version"] = $page_metric["platform_version"];
    $ads_metric["model"] = $page_metric["model"];
    // add coin name to metric
    $ads_metric["coin"] = ui_getCoinName();//strtolower($settings["coin_symbol"]);
    // extend metric with data from the call
    $ads_metric["ts"] = (int)($in["ts"] ?? 0);
    $ads_metric["provider"] = substr((string)($in["adv"] ?? ''), 0, 32);
    $ads_metric["slot"] = substr((string)($in["sl"] ?? ''), 0, 64);
    $ads_metric["time_ms"] = (int)($in["lt"] ?? 0);
    $ads_metric["url"] = substr((string)($in["pg"] ?? ''), 0, 512);
    $ads_metric["root"] = ui_getRootURL();
    $ads_metric["path"] = trim(str_replace(ui_getRootURL(), "", $ads_metric["url"]), "/");

    // store data in the database
    db_recordAdsMetrics($ads_metric);

    // forward to global API endpoint

    // respond with 200
    http_response_code(200);
?>