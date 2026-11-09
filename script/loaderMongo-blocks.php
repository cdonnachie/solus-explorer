<?php
    require __DIR__ . '/../settings.php';
    global $settings;    
    include_once __DIR__ . '/../lib/explorerDb.php';
    include_once __DIR__ . '/../lib/explorerRpc.php';
    include_once __DIR__ . '/../lib/loaderBlock.php';
    //include_once __DIR__ . '/../lib/loaderBlock-pre-sip-030.php';
    include_once __DIR__ . '/../lib/explorerUi.php';
    include_once __DIR__ . '/../lib/bech32.php';
    include_once __DIR__ . '/../lib/timeTracker.php';
    
    use Decimal\Decimal;

    function createPidFile($since_time){
        $pid = getmypid();
        $str = array("pid" => $pid, "since" => $since_time, "name" => basename(__FILE__), "active" => time());
        // Write-then-rename keeps the pidfile atomic. A concurrent checkPidFile()
        // must never observe a truncated file: it would treat the running loader
        // as stale and start a second instance writing the same blocks.
        $tmp = __DIR__ . '/tmp/blockloader.pid.' . $pid;
        file_put_contents($tmp, json_encode($str));
        rename($tmp, __DIR__ . '/tmp/blockloader.pid');
        $GLOBALS['ldr_owns_pidfile'] = true;
    }
    function deletePidFile(){
        $GLOBALS['ldr_owns_pidfile'] = false;
        unlink(__DIR__. '/tmp/blockloader.pid');
    }
    // Mutual exclusion is a kernel flock() held for the whole process
    // lifetime: it cannot be observed half-written the way a file can, and it
    // is released the instant the holder dies, however it dies. The pidfile
    // stays as a heartbeat only, used to decide whether a live lock holder is
    // wedged. Note: never delete the .lock file itself -- flock() binds to the
    // inode, and recreating the file would hand out a second, independent lock.
    function acquireLoaderLock(){
        global $settings;
        // check if stop files exist
        if ( file_exists(__DIR__ . '/tmp/stop.blocks' ) || file_exists(__DIR__ . '/tmp/stop.all' )){
            ldr_quietExit("Stop-file exists in 'tmp' directory. \nLoader disabled until stop-file removed.\nExiting now...\n\n");
        }

        $fh = fopen(__DIR__ . '/tmp/blockloader.lock', 'c');
        if ($fh === false){
            ldr_abort("Cannot open the lock file in the 'tmp' directory.", array("file" => __DIR__ . '/tmp/blockloader.lock'));
        }
        if (flock($fh, LOCK_EX | LOCK_NB)){
            $GLOBALS['loader_lock'] = $fh;   // keep the handle open until exit
            return true;
        }

        // The lock is held, so a loader process is alive right now. Its
        // heartbeat decides whether it is healthy or wedged.
        $to = array_key_exists("loader_timeout_sec",$settings) ? $settings["loader_timeout_sec"] : 120;
        $piddata = @json_decode(@file_get_contents(__DIR__ . '/tmp/blockloader.pid'));
        if (!is_object($piddata) || !isset($piddata->pid) || !isset($piddata->active)){
            ldr_quietExit("Loader lock is held; assuming a loader is active.\nExiting now...\n\n");
        }
        if ($piddata->active + $to >= time()){
            ldr_quietExit("BlockLoader already running with pid=$piddata->pid. \nActivity timeout set to $to seconds was not exceeded.\nExiting now...\n\n");
        }

        // Heartbeat expired: the holder is wedged. Verify the pid really is
        // this script before killing it, then take the lock the kernel
        // releases on its death.
        $cmd = trim((string)@file_get_contents("/proc/".(int)$piddata->pid."/cmdline"), "\0");
        if (\strpos($cmd, basename(__FILE__)) === false) {
            ldr_abort("Loader lock is held, the heartbeat expired and its pid is not this loader; not killing it.",
                array("pid" => (int)$piddata->pid));
        }
        print("Loader pid ".(int)$piddata->pid." exceeded the activity timeout of ".$to." seconds, terminating it.\n");
        exec("kill -9 ".(int)$piddata->pid);
        for ($i = 0; $i < 50; $i++){
            if (flock($fh, LOCK_EX | LOCK_NB)){
                $GLOBALS['loader_lock'] = $fh;
                return true;
            }
            usleep(100000);
        }
        ldr_abort("Loader lock was not released after terminating a wedged loader.", array("pid" => (int)$piddata->pid));
    }
    function ldr_check_connections(){
        global $settings;
        global $dbm;

        // check db connection
        print("Connecting to database: ");
        if (db_checkDbConnected()){
            print("Successful \n");
        } else {
            print("FAILED! \n");
            ldr_abort("Database server connection failed.");
        }
        db_checkCollections($dbm, $settings['db_name']);

        print("Connecting to daemon: ");
        try {
            $bc_info=rpc_getblockcount();

        } catch (Exception $e) {
            print("FAILED! \n");
            ldr_abort("Daemon connection failed.", array("error" => trim($e->getMessage())));
        }
        if (is_null($bc_info)){
            print("FAILED! \n");
            ldr_abort("Daemon connection failed: cannot read the block count.");
        } else {
            print("Successful\n");
            print(" Current block height: ".$bc_info."\n");
        }
    }

    /**
     * A roll-back step failed after earlier steps may have applied. Mark the
     * height rollback_failed in BLOCKSTATE: the next run's
     * recoverUnfinishedBlocks() retries the roll-back (every step is
     * idempotent, and the rollback record is deleted last).
     */
    function rollbackStepFailed($block_ix, $block_hash, $step){
        try { db_setBlockState($block_ix, 'rollback_failed', $block_hash); } catch (Throwable $e) {}
        ldr_abortBlock($block_ix, $block_hash, "Roll-back step '".$step."' failed.", array("step" => $step),
            "The height may be partially rolled back; BLOCKSTATE marks it rollback_failed for the next run's recovery.");
    }

    function rollbackBlock($block_ix, $block_hash = null){
        $GLOBALS['ldr_current_height'] = $block_ix;
        global $settings;
        global $dbm; //ATT
        $tt = new TimeTracker();
        $tt->createTimer('block_total',0,true);

        // 0. load rollback record
        $tt->createTimer('get_rollback_data',1,true);
        $rr = db_getRollbackData($block_ix);
        if (count($rr)>0){
            $rr = $rr[0];
            $tt->stopTimer('get_rollback_data','successful');
        } else {
            // no rollback data available
            $tt->stopTimer('get_rollback_data','failed');
            $tt->stopTimer('block_total','rollback failed');
            $pl = new ProcessingLog($block_ix, $block_hash, 'roll-back', $tt->getDurationSeconds('block_total'),$tt);
            return false;
        }

        // 1. delete block record
            $tt->createTimer('delete_block',1,true);
            $rs = db_deleteBlock($block_ix);
            $tt->stopTimer('delete_block', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Block delete"); }

        // 2. rollback block stats
            $tt->createTimer('rb_block_stats',1,true);
            $rs = db_rollbackBlockStats($rr);
            $tt->stopTimer('rb_block_stats', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback BlockStats"); }

        // 3. rollback groups
            $tt->createTimer('rb_addressgroups',1,true);
            $rs = db_rollbackAddressGroups($rr);
            $tt->stopTimer('rb_addressgroups', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback AddressGroups"); }

        // 4. rollback addresses
            $tt->createTimer('rb_addresses',1,true);
            $rs = db_rollbackAddresses($rr);
            $tt->stopTimer('rb_addresses', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback Addresses"); }

        // 5. rollback assets
            $tt->createTimer('rb_assets',1,true);
            $rs = db_rollbackAssets($rr);
            $tt->stopTimer('rb_assets', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback Assets"); }

        // 6. rollback asset moves
            $tt->createTimer('rb_asset_moves',1,true);
            $rs = db_rollbackAssetMoves($block_ix);
            $tt->stopTimer('rb_asset_moves', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback AssetMoves"); }

        // 7. rollback address moves
            $tt->createTimer('rb_address_moves',1,true);
            $rs = db_rollbackAddressMoves($block_ix);
            $tt->stopTimer('rb_address_moves', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback AddressMoves"); }

        // 8. rollback txs
            $tt->createTimer('rb_txs',1,true);
            $rs = db_rollbackTxs($block_ix);
            $tt->stopTimer('rb_txs', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback Txs"); }

        // 9. rollback vouts
            $tt->createTimer('rb_vouts',1,true);
            $rs = db_rollbackVouts($block_ix);
            $tt->stopTimer('rb_vouts', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback Vouts"); }

        // 10. rollback vout spent
            $tt->createTimer('rb_vout_spent',1,true);
            $rs = db_rollbackVoutSpent($rr);
            $tt->stopTimer('rb_vout_spent', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback VoutSpent"); }

        // 11 rollback airdrops
        if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
            $tt->createTimer('rb_airdrops',1,true);
            $rs = db_rollbackAirdrops($block_ix);
            $tt->stopTimer('rb_airdrops', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback Airdrops"); }
            // 12 rollback claimed airdrops
            $tt->createTimer('rb_airdrop_claimed',1,true);
            $rs = db_rollbackAirdropsClaimed($rr);
            $tt->stopTimer('rb_airdrop_claimed', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Rollback AirdropsClaimed"); }

        }
        // 13 remove rollback record
            $tt->createTimer('delete_rb_record',1,true);
            $rs = db_deleteRollbackRecord($block_ix);
            $tt->stopTimer('delete_rb_record', "success=".$rs);
            if (!$rs) { rollbackStepFailed($block_ix, $block_hash, "Delete Rollback Record"); }


        // 14. clear any lifecycle marker for this height
            db_clearBlockState($block_ix);

        // 15. Log rollback
        /// ATT: add rollbak logging
        $tt->stopTimer('block_total','rollback successful');
        $pLog = new ProcessingLog($block_ix,$block_hash,'roll-back');
        $pLog->addTimeData($tt->getDurationSeconds('block_total'),$tt);
        $pLog->record();
        return true;
    }

    /**
     * Unwind every height the loader left in flight.
     *
     * BLOCKSTATE rows exist only between the first mutation of a block and the
     * writing of its block record, so anything found here died mid-write.
     * Unlike checkIncompleteBlock() this is not limited to the height just
     * above the tip, and it does not depend on rollback data being present to
     * notice that something is wrong.
     */
    function recoverUnfinishedBlocks(){
        $states = db_getUnfinishedBlockStates();
        if (count($states)==0){ return; }

        print(" Found ".count($states)." unfinished block(s) from a previous run:".PHP_EOL);
        foreach ($states as $st){
            $h = (int)$st->_id;

            // Claim before touching anything. The atomic delete takes the
            // height away from a writer that may somehow still be alive: its
            // next owner-checked transition aborts instead of committing, so
            // this roll-back and that commit can never both win. The claimed
            // row is also fresher than the listing read above.
            $claim = db_claimBlockState($h);
            if (is_null($claim)){
                print("   #".$h." - finished by its writer, skipping".PHP_EOL);
                continue;
            }
            $state = isset($claim->state) ? $claim->state : 'unknown';
            print("   #".$h." (".$state.") ");

            if ($state == 'pending'){
                // Nothing was mutated: the rollback record is the first write,
                // and it had not happened yet. The claim already discarded the
                // marker, so the height will simply re-load.
                print("- nothing written, will re-load".PHP_EOL);
                continue;
            }

            // rollback_ready / data_written / unknown: undo data should exist.
            if (!rollbackBlock($h, isset($claim->block_hash) ? $claim->block_hash : null)){
                print("- FAILED".PHP_EOL);
                // Put the marker back so the next run still sees the fault.
                db_setBlockState($h, $state, isset($claim->block_hash) ? $claim->block_hash : null);
                ldr_abortBlock($h, isset($claim->block_hash) ? $claim->block_hash : null,
                    "Cannot roll back an unfinished block: no rollback data.", array("state" => $state),
                    "Re-sync from a height below #".$h." is required.");
            }
            print("- rolled back".PHP_EOL);
        }
        print(PHP_EOL);
    }

    function checkIncompleteBlock($block_ix){
        $rollback_required = false;
        $i = $block_ix + 1;

        $db_blk = db_getBlockByHeight($i);
        if (count($db_blk)==0){
            $db_rb = db_getRollbackData($i);
            if (count($db_rb)>0){ $rollback_required = true; }
        }
        if ($rollback_required){
            // rollback incomplete block
            if (!rollbackBlock($i)){
                ldr_abortBlock($i, null, "Cannot roll back an incomplete block: no rollback data.", array(),
                    "Re-sync from a height below #".$i." is required.");
            }
            return false;
        } else {
            return true;
        }

    }

    function checkOrphanRolback($block_ix){
        $rollback_required = false;

        // orphan check
        $db_blk = db_getBlockByHeight($block_ix);
        if (count($db_blk)>0){
            $db_blk  = $db_blk[0];
            $bc_hash = rpc_getblockhash($block_ix);
            if (is_null($bc_hash) || !ctype_xdigit($bc_hash)){
                ldr_abortBlock($block_ix, $db_blk->_id, "Daemon returned no hash for a loaded block during the orphan check.");
            }
            if ($db_blk->_id != $bc_hash){ $rollback_required = true; }
        }
        if ($rollback_required){
            // rollback orphan block
            if (!rollbackBlock($block_ix, $db_blk->_id)){
                ldr_abortBlock($block_ix, $db_blk->_id,
                    "Cannot roll back an orphaned block: no rollback data. The chain has reorganised below the available rollback depth.",
                    array("daemon_hash" => (string)$bc_hash), "Re-sync from a height below #".$block_ix." is required.");
            }
            return false;
        } else {
            return true;
        }

    }

    function printTimeTracking($tt){
        print("\n");
        print("        ". 
            str_pad("SUBTASK",35," ").
            str_pad("SECONDS",7," ", STR_PAD_LEFT).
            "  ". "INFO".PHP_EOL
        );
        foreach ($tt->getCollection() as $timer){
            $d_split = splitFloat($timer->getDurationSeconds(),3,3);
            print(
                "        ".
                str_pad((str_repeat("  ",$timer->level).$timer->name),35,' ', STR_PAD_RIGHT).
                str_pad(($d_split->whole.'.'.str_pad($d_split->fraction,3,'0',STR_PAD_RIGHT)),7,' ', STR_PAD_LEFT).
                "  ".$timer->getInfo().PHP_EOL
            );
        }
        print(PHP_EOL);
    }

    function check_subscription(){
        global $settings;
        if (isset($settings["ads_enabled"]) && $settings["ads_enabled"]){
            // if ads are enabled, we don't need to check subscription
            return;
        }
        if (isset($settings["subscription_enabled"]) && $settings["subscription_enabled"]){
            $exp_ts = strtotime($settings["subscription_endtime"]);
            if ($exp_ts <= time()){
                ldr_quietExit("  Service subscription expired, block processing disabled!".PHP_EOL);
            }
        }
    }

    //----------------------//
    //    Start is here     //
    //----------------------//

    global $settings;

    date_default_timezone_set($settings["timezone"]);
    ldr_installStopHandlers();
    check_subscription();

    $tt = new TimeTracker();
    global $tt;
    
    $since_time = time();
    global $rpc;
    global $dbm;

    isset($settings['arbitrary_precision']) ? $pp = $settings['arbitrary_precision'] : $pp = 64;
    global $pp;
    
    $pLog = new ProcessingLog(-1,'','');
    global $pLog;

    acquireLoaderLock();
    createPidFile($since_time);
    $continue_load=false;
    $check_block=true;
    $argb = 0;
    if (count($argv)>1) {

        try{
            if (is_numeric($argv[1])){
                $argb = (int)$argv[1];
                //echo "int: '".$argb."'".PHP_EOL;
            } else {
                //echo "non-int: '".$argv[1]."'".PHP_EOL;
                if (substr($argv[1],0,1) == "="){
                    $tn = (int)ltrim($argv[1],"=");
                    $tx = db_getLastDbBlockNumber();
                    $argb = $tn - $tx;
                    echo "Processing ".$argb." blocks".PHP_EOL;    
                }
            }
        } catch (Exception $ex) {
        }
    }
    if ($argb < 0) {
        $continue_load=true;
    }
    if (!$continue_load && (!isset($settings["loader_random_delay"]) || $settings["loader_random_delay"])){
        $rd = rand(0, 10);
        echo "Waiting for the random delay of ".$rd." seconds...".PHP_EOL;
        sleep($rd);
    }

    ldr_check_connections();

    // unwind anything left in flight by a previous run, at any height
    if (!$continue_load){
        recoverUnfinishedBlocks();
    }

    $n = db_getLastDbBlockNumber();

    // incomplete block check/rollback. Retained as a backstop for heights
    // written before BLOCKSTATE existed, which have no lifecycle row.
    if (!$continue_load){
        checkIncompleteBlock($n);
    }

    // orphan blocks check/rollback
    while(!$continue_load){
        if ($n >= 0){
             $continue_load = checkOrphanRolback($n);
             $n = $n-1;
        } else {
             $continue_load = true;
        }
    }

    $n = db_getLastDbBlockNumber();
    print(" Last loaded block:    ".$n."\n");
    $chainheight = rpc_getblockcount();
    if (is_null($chainheight) || !is_integer($chainheight)){
        ldr_abort("Daemon returned no block count.");
    }

    if (count($argv)>1) {
        try {
            //$argb = (int)$argv[1];
            if ($argb >= 0){
                $loadtoblock = $n + $argb;
                if ($loadtoblock > $chainheight) { $loadtoblock = $chainheight; }
            } else {
                $rollbackblocks = -$argb;
            }
        } catch (Exception $ex) {
            $loadtoblock = $chainheight;
        }
    } else {
        $loadtoblock = $chainheight;
    }

    $last_pidfile_update = $since_time;
    if (isset($rollbackblocks)){
        // rollback n blocks
        $maxRb = db_getRollbackDataCount();
        if ($maxRb < $rollbackblocks){
            print(" Maximum possible rollback depth is ".$maxRb." blocks".PHP_EOL);
            $rollbackblocks = $maxRb;
        }
        for ($i=0; $i < $rollbackblocks; $i++){
            $starttime = milliseconds();
            $n = db_getLastDbBlockNumber();
            $db_block=db_getBlockByHeight($n)[0];
            print("x ".str_pad($n,7," ",STR_PAD_LEFT).":". $db_block->_id.":");
            // A failed rollback leaves the block record in place, so the height
            // never advances: without this the loop would spin on the same
            // block for the remaining iterations and report success.
            if (!rollbackBlock($db_block->block_ix, $db_block->_id)){
                ldr_abortBlock($db_block->block_ix, $db_block->_id, "Manual roll-back stopped: no rollback data available.",
                    array("rolled_back" => $i, "requested" => $rollbackblocks));
            }
            $endtime = milliseconds();
            $processingtime = splitFloat(($endtime - $starttime)/1000,3,3);
            print(str_pad($processingtime->whole,3," ",STR_PAD_LEFT). "." . str_pad($processingtime->fraction,3,"0",STR_PAD_RIGHT) ."\n");
            $current_time = time();
            if ($current_time >= $last_pidfile_update + $settings["pidfile_update_interval"]){
                createPidFile($since_time);
                $last_pidfile_update = $current_time;
            }
        }
        deletePidFile();
        ldr_quietExit(' Total roll-back session duration: ' . timediffToParts(time() - $since_time)->string . PHP_EOL);
    }
    $i = $n+1;
    Block::$auto_repair_missing_vouts = true;
    $auto_repaired_vouts = array();

    while ( $i <= $loadtoblock ) {

        $tt = new TimeTracker();

        $tt->createTimer('block_total',0,true);
        
        $ldr_current_height = $i;
        $tt->createTimer('init_block',1,true);
        $block = new Block($i);
        $tt->stopTimer('init_block',null);
        $pLog = new ProcessingLog($block->block_ix,$block->_id,"load");
        $tt->setInfo('block_total','Block height = '.$block->block_ix);
        print(str_pad($i,7," ",STR_PAD_LEFT).":". $block->_id.":");
        try {
            $block->processBlock();
        } catch (LdrMissingVoutsException $e) {
            $tip = $i - 1;
            $target = ldr_missingVoutRepairTarget($e->vin_ids, $tip, $auto_repaired_vouts);
            if (isset($target['error'])){
                ldr_abortBlock($e->block_ix, $e->block_hash, $e->description,
                    $e->context + array("auto_rollback" => "not possible: ".$target['error']));
            }
            $rb_to = $target['height'] - 1;
            print(PHP_EOL." Missing VOUT(s) confirmed by the daemon, rolling back ".$tip."-".$target['height']." and re-loading:".PHP_EOL);
            $missing = array();
            foreach ($target['vouts'] as $ref => $h){
                print("   ".$ref." (block ".$h.")".PHP_EOL);
                $auto_repaired_vouts[$ref] = true;
                $missing[] = array("vout" => (string)$ref, "height" => $h);
            }
            $log_record = new stdClass();
            $log_record->log_id           = $i."@".time()."-autorb";
            $log_record->detectiontime_tz = date('c');
            $log_record->block_ix         = $i;
            $log_record->detected_by      = "blockLoader";
            $log_record->description      = "Auto-rollback: ".count($target['vouts'])." VOUT(s) spent in block ".$i.
                " were missing from the database but confirmed by the daemon. Rolled back to block ".$rb_to." and re-loaded.";
            $log_record->object_class     = "BLOCK";
            $log_record->object_id        = (string)$e->block_hash;
            $log_record->level            = 1;
            $log_record->context          = array(
                "missing_vouts"  => $missing,
                "current_height" => $i,
                "rolled_back_to" => $rb_to,
            );
            // The PROBLEMLOG entry is the cross-run loop guard: no entry, no rollback.
            $logged = false;
            try { $logged = db_recordLogData($log_record); } catch (Throwable $le) {}
            if (!$logged){
                ldr_abortBlock($e->block_ix, $e->block_hash, $e->description,
                    $e->context + array("auto_rollback" => "not possible: PROBLEMLOG write failed"));
            }

            for ($r = $tip; $r >= $target['height']; $r--){
                $db_block = db_getBlockByHeight($r)[0];
                print("x ".str_pad($r,7," ",STR_PAD_LEFT).":".$db_block->_id.PHP_EOL);
                if (!rollbackBlock($r, $db_block->_id)){
                    ldr_abortBlock($r, $db_block->_id, "Auto-rollback stopped: no rollback data available.",
                        array("target_height" => $target['height']));
                }
                if (time() >= $last_pidfile_update + $settings["pidfile_update_interval"]){
                    createPidFile($since_time);
                    $last_pidfile_update = time();
                }
            }
            $i = $target['height'];
            continue;
        }
        $tt->stopTimer('block_total', 'Block height = '.$block->block_ix);
        $processingtime = splitFloat($tt->getDurationSeconds('block_total'),3,3);
        print(str_pad($block->tx_count,3," ",STR_PAD_LEFT).":".str_pad($processingtime->whole,3," ",STR_PAD_LEFT). "." . str_pad($processingtime->fraction,3,"0",STR_PAD_RIGHT) ."\n");
        $current_time = time();
        if ($current_time >= $last_pidfile_update + $settings["pidfile_update_interval"]){
            createPidFile($since_time);
            $last_pidfile_update = $current_time;
        }
        $pLog->addTimeData($tt->getDurationSeconds('block_total'),$tt);
        $pLog->record();
        $i++;
        
        // try {
        //     $sip030 = $tt->getDurationSeconds('grp_update-sip030');
        //     if (!is_null($sip030)){
        //         echo "SIP-030   " . strtr((@cdec($tt->getDurationSeconds('block_total')))->toFixed(3)." \t".(@cdec($sip030))->toFixed(6),".",",")."\n";
        //     }
        // } catch (Exception $e){}    
        // try {
        //     $sip023 = $tt->getDurationSeconds('grp_balance_upd-sip023');
        //     if (!is_null($sip023)){
        //         echo "SIP-023   " . strtr((@cdec($tt->getDurationSeconds('block_total')))->toFixed(3)." \t".(@cdec($sip023))->toFixed(6),".",",")."\n";
        //     }
        // } catch (Exception $e){}   

    }

    // done with processing blocks, let's calculate cache stats
    print("calculating cache data".PHP_EOL);
    $ct = new TimeTracker();
    $ct->createTimer('cache_total',0,true);

    // overview
    $ct->createTimer('ui_CreateOverviewCache()',1,true);
    ui_CreateOverviewCache();
    $ct->stopTimer('ui_CreateOverviewCache()',null);

    // top addresses
    $ct->createTimer('ui_CreateTopAddressesCache()',1,true);
    ui_CreateTopAddressesCache();
    $ct->stopTimer('ui_CreateTopAddressesCache()',null);

    // top groups
    $ct->createTimer('ui_CreateTopAddressGroupsCache()',1,true);
    ui_CreateTopAddressGroupsCache();
    $ct->stopTimer('ui_CreateTopAddressGroupsCache()',null);

    // stats

    // api targets

    // burned coins

    // mining
    $ct->createTimer('ui_CreateMiningCache()',1,true);
    ui_CreateMiningCache();
    $ct->stopTimer('ui_CreateMiningCache()',null);

    // gold rush (shadow ledger)
    if (shadow_enabled()){
        $ct->createTimer('ui_CreateGoldRushCache()',1,true);
        ui_CreateGoldRushCache();
        $ct->stopTimer('ui_CreateGoldRushCache()',null);
    }

    // staking

    // assets

    $ct->stopTimer('cache_total');
    foreach($ct->getCollection() as $timer){
        print(str_pad(str_pad($timer->name, 2*(2*$timer->level),' ',STR_PAD_LEFT),40,' ',STR_PAD_RIGHT).": ".fpp($timer->getDurationSeconds(),6).PHP_EOL);
    }

    deletePidFile();
    ldr_quietExit(' Total loader session duration: ' . timediffToParts(time() - $since_time)->string . PHP_EOL);

?>