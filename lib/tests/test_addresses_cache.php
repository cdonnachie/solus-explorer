<?php 
    require_once __DIR__ . "/../../settings.php";
    global $settings;
    require_once __DIR__ . "/../explorerUi.php";
    require_once __DIR__ . "/../explorerDb.php";
    require_once __DIR__ . "/../explorerRpc.php";
    require_once __DIR__ . "/../timeTracker.php";

    global $dbm;
    global $rpc;

    ui_CreateTopAddressesCache();
?>