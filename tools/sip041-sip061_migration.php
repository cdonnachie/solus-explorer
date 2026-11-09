<?php 
    require __DIR__ . '/../settings.php';    
    global $settings;
    include_once __DIR__ . '/../lib/explorerDb.php';
    include_once __DIR__ . '/../lib/explorerUi.php';
    include_once __DIR__ . '/../lib/explorerRpc.php';
    include_once __DIR__ . '/../lib/timeTracker.php';
    $apt = new TimeTracker;
    global $apt;

    global $rpc;
    global $dbm;

    ////////////////////////////////
    //                            //
    //           SIP-041          //
    //                            //
    ////////////////////////////////


    function getBytesIncrement($type){
        if (!in_array($type->_id->t, ['nonstandard','reward','genesis'])){
            return $type->n;
        } else {
            return 0;
        }
    }

    date_default_timezone_set($settings["timezone"]);
    $precision = (int)$settings["default_precision"];
    $granularity_sec = 86400;

    $now = time();



    global $dbm;
    global $settings;
    // SIP-041 re-calculate feeTotal

    $dbc = new MongoDB\Driver\Command([
        'aggregate' => 'BLOCKS',
        'pipeline' => [
            ['$group'   => [
                '_id' => [
                    '$subtract'=> ['$block_time_sec', ['$mod' => ['$block_time_sec', 3600]]],
                ],

                "feeTotal" => ['$sum'=> '$fee_amount'],
            ]],
            ['$merge' => [
                'into'              => 'BLOCKSTATS',
                'on'                => '_id',
                'whenMatched'       => 'merge',
                'whenNotMatched'    => 'discard',
            ]]
        ],
        'cursor' => ['batchSize' => 1]
    ]);
    echo "feeTotal              ";
    try {
        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        echo "Done\n";
    } catch (Exception $e){
        echo "Failed\n";
    }


    // SIP-041 re-calculate feeSubjectBytes    
    $tx_sizes = db_getTxSizesAggregatesByGranularity(0, ($now - $now % 3600) + 3600);
    if (count($tx_sizes)>0) {
        $blockStats = array();
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        foreach ($tx_sizes as $type){
            if (array_key_exists($type->_id->ts,$blockStats)){
                $blockStats[$type->_id->ts]["feeSubjectBytes"] += getBytesIncrement($type);
            } else {
                $blockStats[$type->_id->ts]["feeSubjectBytes"] = getBytesIncrement($type);
            }
        }
        foreach ($blockStats as $ts=>$stats){
            $bulk->update(["_id" => $ts],['$set' => $stats],['upsert'=>false]);
        }
        if (count($bulk)>0){
            echo "feeSubjectBytes       ";
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATS", $bulk);
                echo "Done\n";
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                echo "Failed\n";
            }
        }
    }

    // SIP-061 add index to BLOCKS
    //db.BLOCKS.createIndex({type:1,block_ix:-1},{name:"type_block_ix_desc",unique:false})
    echo "add block type index  ";
        //create index
        $dbc = new MongoDB\Driver\Command([
             "createIndexes" => "BLOCKS",
             "indexes" => [[
                 "name" => "type_block_ix_desc",
                 "key" => (object)["type" => 1, "block_ix" => -1],
                 "unique" => false,
                 "ns" => $settings["db_name"] . ".BLOCKS",
        //     ], [
        //         "name" => "last_block_nonunique",
        //         "key" => ["last_block" => -1],
        //         "unique" => false,
        //         "ns" => $db_name . "." . $col,
        //     ], [
        //         "name" => "group_id_nonunique",
        //         "key" => ["group_id" => 1],
        //         "unique" => false,
        //         "ns" => $db_name . "." . $col,
             ]],
        ]);
        try {
            $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
            echo "Done\n";
        } catch (Exception $e){
            echo "Failed\n";
        }
    
?>