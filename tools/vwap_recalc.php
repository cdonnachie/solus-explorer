<?php 
    require "../settings.php";
    global $settings;
    require "../lib/explorerDb.php";
    global $dbm;

    $start_time = 0;
    $end_time = time();

    $mm = db_getMarketMetricsByTimeRange($start_time,$end_time);

    foreach ($mm as $m){
        // check if vwap and volume_total are missing
        if (!isset($m->vwap) || !isset($m->volume_total)){
            
            $_id = $m->_id;
            echo "Processing timestamp [".$_id."] ";
            $vwap = @cdec(0);
            $volume_total = @cdec(0);
            $turnover = @cdec(0);
            // iterate through metrics
            foreach($m->metrics as $x){
                if (isset($x->quote_volume) && !is_null($x->quote_volume) && isset($x->latest) && !is_null($x->latest)){
                    //"latest" : NumberDecimal("0.00000313")
                    $l = @cdec($x->latest);
                    //"quote_volume" : NumberDecimal("5232.0018")
                    $v = @cdec($x->quote_volume);

                    $turnover += $l * $v;
                    $volume_total += $v;
                }
            }
            echo str_pad("vol=".$volume_total->toString(),23);
            if ($volume_total>0){
                // calculate vwap
                $vwap = @cdec($turnover/$volume_total);
                echo str_pad("vwap=".$vwap->toString(),12);
                // record to db
                $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                $bulk->update(['_id' => $_id ], ['$set' => ["vwap"=>@cmdec($vwap),"volume_total" => @cmdec($volume_total)]],['upsert'=>false]);
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".MARKETS", $bulk);
                    echo "   DONE\n";
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    echo "   FAILED\n";
                    $result = $e->getWriteResult();
                    die();
                }
            } else {
                echo "   SKIP\n";
            }
        }
    }
    
?>