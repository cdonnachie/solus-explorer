<?php
/*
 * Recompute BLOCKS.coinbase_tag on blocks that are already indexed.
 *
 * The miner tag is extracted by ldr_coinbaseTag() while a block is indexed, and
 * only the extracted text is stored -- the coinbase script itself is not kept
 * anywhere (Vin records just the type and the literal address "Coinbase"). So
 * whenever that extraction changes, existing blocks keep whatever the loader
 * computed at the time, and the only way to refresh them is to ask the daemon
 * for the coinbase script again. That is what this does, in place, without
 * touching any other field and without a reindex.
 *
 * Note for anyone expecting the tag to be recoverable from the stored data: it
 * is not in the coinbase OP_RETURN either. On segwit chains that output is the
 * BIP141 witness commitment, which is byte-identical in structure across every
 * block and carries no miner identity. The tag lives in the coinbase *input*
 * script, which is why the daemon is the only source.
 *
 * Reorg safety: every update is matched on the block hash (_id), never on the
 * height. If a block is rolled back and replaced while this is running, the
 * stale hash simply matches no document and the write is a no-op rather than
 * stamping a tag onto the wrong block.
 *
 * Usage:
 *   php script/refreshCoinbaseTags.php [options]
 *
 *   --from=<height>    first height to visit        (default 0)
 *   --to=<height>      last height to visit         (default the highest indexed block)
 *   --batch=<n>        blocks read and written per batch (default 2000)
 *   --only-missing     visit only blocks whose coinbase_tag is null or absent
 *   --dry-run          report what would change, write nothing
 *   --verbose          print every change
 *   --help
 *
 * Exit status: 0 on completion, 1 on a fatal error.
 */

    require __DIR__ . '/../settings.php';
    global $settings;
    include_once __DIR__ . '/../lib/explorerDb.php';
    include_once __DIR__ . '/../lib/explorerRpc.php';
    include_once __DIR__ . '/../lib/bech32.php';
    include_once __DIR__ . '/../lib/timeTracker.php';
    include_once __DIR__ . '/../lib/loaderBlock.php';

    if (PHP_SAPI !== 'cli'){
        http_response_code(404);
        exit(1);
    }

    global $tt;
    $tt = new TimeTracker();

// ---------------------------------------------------------------- arguments

    function rct_usage(){
        print(
            "Recompute BLOCKS.coinbase_tag on already-indexed blocks.\n\n".
            "  php script/refreshCoinbaseTags.php [options]\n\n".
            "  --from=<height>   first height to visit (default 0)\n".
            "  --to=<height>     last height to visit (default the highest indexed block)\n".
            "  --batch=<n>       blocks per batch (default 2000)\n".
            "  --only-missing    visit only blocks whose coinbase_tag is null or absent\n".
            "  --dry-run         report what would change, write nothing\n".
            "  --verbose         print every change\n".
            "  --help\n\n"
        );
    }

    $opt = getopt("", ["from::", "to::", "batch::", "only-missing", "dry-run", "verbose", "help"]);
    if ($opt === false || array_key_exists("help", $opt)){
        rct_usage();
        exit($opt === false ? 1 : 0);
    }

    $from         = array_key_exists("from", $opt)  ? (int)$opt["from"]  : 0;
    $to           = array_key_exists("to", $opt)    ? (int)$opt["to"]    : null;
    $batch        = array_key_exists("batch", $opt) ? (int)$opt["batch"] : 2000;
    $only_missing = array_key_exists("only-missing", $opt);
    $dry_run      = array_key_exists("dry-run", $opt);
    $verbose      = array_key_exists("verbose", $opt);

    if ($from < 0)  { $from = 0; }
    if ($batch < 1) { $batch = 1; }
    if ($batch > 20000) { $batch = 20000; }

    if (!db_checkDbConnected()){
        print("Cannot reach MongoDB with the credentials in settings.php.\n");
        exit(1);
    }

    $tip = db_getLastDbBlockNumber();
    if (!is_int($tip) && !is_float($tip)){
        print("No indexed blocks found in the BLOCKS collection.\n");
        exit(1);
    }
    $tip = (int)$tip;
    if ($tip < 0){
        print("No indexed blocks found in the BLOCKS collection.\n");
        exit(1);
    }
    if (is_null($to) || $to > $tip){ $to = $tip; }
    if ($to < $from){
        print("Nothing to do: --to=".$to." is below --from=".$from.".\n");
        exit(0);
    }

// ---------------------------------------------------------------- coinbase read
    /*
     * getblock(hash, 2) returns fully decoded transactions, so one call yields
     * the coinbase script. Daemons that predate verbosity 2 need the two-call
     * route instead; which one applies is settled once, on the first block, and
     * then reused rather than being guessed per block.
     */
    $rct_verbosity2 = null;     // null = undecided, true/false once probed

    /*
     * Returns the coinbase script hex, null when the block has no coinbase vin
     * at all, or false when the daemon could not be read.
     *
     * The loader takes the FIRST coinbase vin it meets while walking the block's
     * transactions in order, not specifically the one in tx 0 -- see the capture
     * site in Tx::readTx(), whose is_null() guard is what makes it first-wins.
     * This walks them in the same order so the two agree even on a chain whose
     * coinbase is not transaction zero.
     */
    function rct_coinbaseHex($block_hash){
        global $rpc, $rct_verbosity2;

        if ($rct_verbosity2 !== false){
            $res = $rpc->getblock($block_hash, 2);
            if (is_null($res->error) && isset($res->result->tx) && is_array($res->result->tx)){
                $rct_verbosity2 = true;
                foreach ($res->result->tx as $tx){
                    if (!isset($tx->vin) || !is_array($tx->vin)){ continue; }
                    foreach ($tx->vin as $vin){
                        if (isset($vin->coinbase)){ return $vin->coinbase; }
                    }
                }
                return null;
            }
            if ($rct_verbosity2 === true){
                // Verbosity 2 has worked before, so this is a per-block fault
                // rather than an unsupported parameter. Report it instead of
                // silently dropping to a different code path.
                return false;
            }
            $rct_verbosity2 = false;
        }

        // Compatibility route for a daemon without verbosity 2: txids, then one
        // getrawtransaction each until a coinbase turns up. In practice that is
        // a single extra call, since the coinbase is transaction zero.
        $blk = rpc_getblock($block_hash);
        if (!isset($blk->tx) || !is_array($blk->tx)){ return false; }
        foreach ($blk->tx as $entry){
            $txid = is_object($entry) ? ($entry->txid ?? null) : $entry;
            if (!is_string($txid)){ continue; }
            $txd = rpc_getrawtransaction($txid, 1);
            if (!isset($txd->vin) || !is_array($txd->vin)){ continue; }
            foreach ($txd->vin as $vin){
                if (isset($vin->coinbase)){ return $vin->coinbase; }
            }
        }
        return null;
    }

// ---------------------------------------------------------------- main loop

    print("Refreshing coinbase tags over heights ".$from."-".$to.
          ($only_missing ? " (missing tags only)" : "").
          ($dry_run ? " [dry run]" : "")."\n");

    $visited = 0;   // blocks read from the database
    $changed = 0;   // blocks whose tag differs from what is stored
    $written = 0;   // documents the server reported as modified
    $failed  = 0;   // blocks whose coinbase could not be read
    $cursor_h = $from;
    $started  = microtime(true);
    $reported = $started;

    $filter_base = $only_missing ? ['coinbase_tag' => null] : [];

    while ($cursor_h <= $to){
        $filter = $filter_base;
        $filter['block_ix'] = ['$gte' => $cursor_h, '$lte' => $to];

        $query = new MongoDB\Driver\Query(
            $filter,
            [
                'sort'       => ['block_ix' => 1],
                'limit'      => $batch,
                'projection' => ['_id' => 1, 'block_ix' => 1, 'coinbase_tag' => 1],
            ]
        );
        $rows = mongo_execDbQuery($query, "BLOCKS")->toArray();
        if (count($rows) == 0){ break; }

        $bulk    = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $pending = 0;
        $last_h  = $cursor_h;

        foreach ($rows as $row){
            $visited++;
            $last_h = (int)$row->block_ix;

            $hex = rct_coinbaseHex($row->_id);
            if ($hex === false){
                $failed++;
                print("  ! height ".$last_h." (".$row->_id."): coinbase could not be read\n");
                continue;
            }

            $tag = is_null($hex) ? null : ldr_coinbaseTag($hex);
            $old = isset($row->coinbase_tag) ? $row->coinbase_tag : null;
            if ($tag === $old){ continue; }

            $changed++;
            if ($verbose){
                printf("  %-10d %-34s -> %s\n", $last_h,
                    var_export($old, true), var_export($tag, true));
            }
            if (!$dry_run){
                // Matched on the hash, so a block replaced by a reorg mid-run
                // updates nothing instead of the wrong document.
                $bulk->update(['_id' => $row->_id], ['$set' => ['coinbase_tag' => $tag]]);
                $pending++;
            }
        }

        if ($pending > 0){
            try {
                $res = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKS", $bulk);
                $written += $res->getModifiedCount();
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                print("  ! bulk write failed near height ".$last_h.": ".$e->getMessage()."\n");
                $failed += $pending;
            }
        }

        $cursor_h = $last_h + 1;

        $now = microtime(true);
        if ($now - $reported >= 10 || $cursor_h > $to){
            $rate = $visited / max(0.001, $now - $started);
            printf("  height %d  visited %d  changed %d  written %d  (%.0f blocks/s)\n",
                $last_h, $visited, $changed, $written, $rate);
            $reported = $now;
        }

        if (count($rows) < $batch){ break; }
    }

    $elapsed = microtime(true) - $started;
    printf("\nDone in %.1fs: %d blocks visited, %d tags changed, %d documents written, %d failures.\n",
        $elapsed, $visited, $changed, $written, $failed);
    if ($dry_run && $changed > 0){
        print("Dry run: nothing was written. Re-run without --dry-run to apply.\n");
    }
    if ($changed > 0 && !$dry_run){
        print("The mining page reads a cache that loaderMongo-blocks.php rebuilds at the end of\n".
              "every run, so the next cron pass picks these up with no further action.\n");
    }
    exit(0);
?>
