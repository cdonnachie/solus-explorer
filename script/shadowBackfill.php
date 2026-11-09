<?php
    /**
     * Shadow ledger (Gold Rush) backfill.
     *
     * Only needed when a chain was already indexed WITHOUT "shadow_accounting" and the flag is
     * being turned on afterwards. A fresh sync needs nothing: enable the flag before the initial
     * load and the block loader ingests the payouts as it goes.
     *
     * Rather than writing synthetic records into already-processed blocks - which would have to
     * merge into their existing BLOCKROLLBACKS entries and risks leaving balances inconsistent -
     * this rolls the affected range back so the normal loader can re-process it with shadow
     * accounting enabled. That reuses the tested pipeline end to end.
     *
     *   php script/shadowBackfill.php            # report only
     *   php script/shadowBackfill.php --yes      # perform the rollback
     */
    require __DIR__ . '/../settings.php';
    global $settings;
    include_once __DIR__ . '/../lib/explorerDb.php';
    include_once __DIR__ . '/../lib/explorerRpc.php';
    include_once __DIR__ . '/../lib/loaderBlock.php';
    include_once __DIR__ . '/../lib/bech32.php';

    use Decimal\Decimal;

    isset($settings['arbitrary_precision']) ? $pp = $settings['arbitrary_precision'] : $pp = 64;
    global $pp;

    /**
     * Rolls back a single block. Mirrors rollbackBlock() in loaderMongo-blocks.php, without the
     * time tracking and processing log.
     */
    function sbf_rollbackBlock($block_ix){
        global $settings;
        $rr = db_getRollbackData($block_ix);
        if (count($rr) < 1){
            print("  Block ".$block_ix.": no rollback data, aborting.".PHP_EOL);
            return false;
        }
        $rr = $rr[0];

        if (!db_deleteBlock($block_ix))          { print("  delete block failed\n");        return false; }
        if (!db_rollbackBlockStats($rr))         { print("  block stats rollback failed\n");return false; }
        if (!db_rollbackAddressGroups($rr))      { print("  groups rollback failed\n");     return false; }
        if (!db_rollbackAddresses($rr))          { print("  addresses rollback failed\n");  return false; }
        if (!db_rollbackAssets($rr))             { print("  assets rollback failed\n");     return false; }
        if (!db_rollbackAssetMoves($block_ix))   { print("  asset moves rollback failed\n");return false; }
        if (!db_rollbackAddressMoves($block_ix)) { print("  moves rollback failed\n");      return false; }
        if (!db_rollbackTxs($block_ix))          { print("  txs rollback failed\n");        return false; }
        if (!db_rollbackVouts($block_ix))        { print("  vouts rollback failed\n");      return false; }
        if (!db_rollbackVoutSpent($rr))          { print("  vout spent rollback failed\n"); return false; }
        if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
            if (!db_rollbackAirdrops($block_ix))        { print("  airdrops rollback failed\n");       return false; }
            if (!db_rollbackAirdropsClaimed($rr))       { print("  claimed airdrops rollback failed\n");return false; }
        }
        if (!db_deleteRollbackRecord($block_ix)) { print("  rollback record delete failed\n");return false; }
        return true;
    }

    //// MAIN ////

    print(PHP_EOL."Shadow ledger backfill".PHP_EOL);
    print("----------------------".PHP_EOL);

    if (!shadow_enabled()){
        print("'shadow_accounting' is disabled in settings - nothing to do.".PHP_EOL.PHP_EOL);
        exit(0);
    }

    // refuse to run against a live loader
    if (file_exists(__DIR__.'/tmp/blockloader.pid')){
        $piddata = json_decode(file_get_contents(__DIR__.'/tmp/blockloader.pid'));
        if (isset($piddata->pid) && file_exists("/proc/".$piddata->pid)){
            print("The block loader is currently running (pid ".$piddata->pid.")."
                .PHP_EOL."Stop it (script/tmp/stop.blocks) and try again.".PHP_EOL.PHP_EOL);
            exit(1);
        }
    }

    $start   = (int)shadow_cfg("start_height", 0);
    $end     = shadow_cfg("end_height", null);
    $last_db = db_getLastDbBlockNumber();

    print("  reward window start : ".$start.PHP_EOL);
    print("  reward window end   : ".(is_null($end) ? "-" : $end).PHP_EOL);
    print("  last loaded block   : ".$last_db.PHP_EOL);

    if ($last_db < $start){
        print(PHP_EOL."Nothing to backfill - the chain is not yet loaded past the reward window start."
            .PHP_EOL."The block loader will ingest payouts as it reaches them.".PHP_EOL.PHP_EOL);
        exit(0);
    }

    $depth = $last_db - $start + 1;
    print("  blocks to re-process: ".$depth.PHP_EOL);

    $max_depth = isset($settings["rollback_depth"]) ? (int)$settings["rollback_depth"] : 0;
    if ($depth > $max_depth){
        print(PHP_EOL."Refusing to run: ".$depth." blocks exceeds the configured rollback_depth of "
            .$max_depth."." .PHP_EOL."A full resync is required instead.".PHP_EOL.PHP_EOL);
        exit(1);
    }

    $confirmed = false;
    foreach ($argv as $a){ if ($a === "--yes"){ $confirmed = true; } }
    if (!$confirmed){
        print(PHP_EOL."This will roll back blocks ".$start." .. ".$last_db." so the loader can"
            .PHP_EOL."re-process them with shadow accounting enabled."
            .PHP_EOL."Re-run with --yes to proceed.".PHP_EOL.PHP_EOL);
        exit(0);
    }

    print(PHP_EOL."Rolling back ".$depth." blocks...".PHP_EOL);
    $done = 0;
    for ($h = $last_db; $h >= $start; $h--){
        if (!sbf_rollbackBlock($h)){
            print(PHP_EOL."Aborted at block ".$h." after ".$done." rollbacks.".PHP_EOL.PHP_EOL);
            exit(1);
        }
        $done++;
        if ($done % 1000 === 0){
            print("  ".$done." / ".$depth." (at block ".$h.")".PHP_EOL);
        }
    }

    print(PHP_EOL."Rolled back ".$done." blocks. Last loaded block is now ".db_getLastDbBlockNumber()."."
        .PHP_EOL."Run the block loader to re-process the range with shadow accounting on.".PHP_EOL.PHP_EOL);
?>
