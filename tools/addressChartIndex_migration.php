<?php
/*
 * Adds the covering index the address movement chart needs, on an existing
 * deployment. Indexes are only created with their collection, so a database
 * built before this release has the old three-field index and must be migrated.
 *
 * What it changes, and why:
 *
 * The chart aggregates ADDRESSMOVES by time bucket, summing amount,
 * vouts_amount and vins_amount. address_asc_time_desc_amount_asc served the
 * $match and carried amount, but not the other two -- so the server fetched one
 * document per movement to add them up. Measured on Avian: ~0.25 ms per
 * movement warm, which is 11 s on a 45,000-movement address, and far worse cold.
 * address_asc_time_desc_amounts carries all four, so the aggregation is answered
 * from the index with no document reads at all.
 *
 * The new index is a superset of the old one's key, and the old one's prefixes
 * ({address}, {address, move_time_sec}, {address, move_time_sec, amount}) are
 * all prefixes of the new key, so every query the old index served is still
 * served. No query in the repository hints it by name. It is therefore safe to
 * drop -- but only once the new index exists and is confirmed to be used, which
 * is why dropping is a separate opt-in step rather than part of the default run.
 *
 * Usage, from the repository root:
 *
 *   php tools/addressChartIndex_migration.php              # show the plan, change nothing
 *   php tools/addressChartIndex_migration.php --create     # build the new index
 *   php tools/addressChartIndex_migration.php --drop-old   # drop the superseded index
 *
 * Build it with the loaders paused (touch script/tmp/stop.all). The collection is
 * the largest in the database and the build competes with the loader for I/O.
 */

    require __DIR__ . '/../settings.php';
    global $settings;
    include_once __DIR__ . '/../lib/explorerDb.php';

    global $dbm;

    $NEW_NAME = "address_asc_time_desc_amounts";
    $OLD_NAME = "address_asc_time_desc_amount_asc";
    $NEW_KEY  = ["address" => 1, "move_time_sec" => -1, "amount" => 1,
                 "vouts_amount" => 1, "vins_amount" => 1];

    $do_create = in_array("--create", $argv, true);
    $do_drop   = in_array("--drop-old", $argv, true);

    function out($s){ echo $s . PHP_EOL; }

    /* Finds the winning plan's executionStats in an explain document of any
     * shape. The layout differs between MongoDB versions and between the
     * classic and slot-based engines -- $group may be pushed into the query
     * layer or left as an aggregation stage -- so recursing to it is more
     * durable than reaching for a fixed path.
     *
     * Deliberately returns the stats object itself and does not recurse into
     * it. An explain also reports the plans the optimiser *rejected*, each with
     * its own counters, under executionStats.allPlansExecution: while the old
     * index still exists it is one of them, complete with the FETCH stage this
     * migration exists to remove. Summing or maximising over every
     * totalDocsExamined in the document therefore measures the discarded plan
     * as much as the chosen one, and reports a covered query as uncovered. */
    function explain_findStats($node){
        if (is_object($node)) { $node = get_object_vars($node); }
        if (!is_array($node)) { return null; }
        if (array_key_exists("totalDocsExamined", $node)
            && array_key_exists("totalKeysExamined", $node)) { return $node; }
        foreach ($node as $v){
            $hit = explain_findStats($v);
            if (!is_null($hit)) { return $hit; }
        }
        return null;
    }

    /* Finds the name of the index the winning plan chose.
     *
     * Skips rejectedPlans and allPlansExecution for the same reason
     * explain_findStats() does not recurse: the plans the optimiser discarded
     * name their own indexes, and while the superseded index still exists it is
     * one of them. */
    function explain_findIndexName($node){
        if (is_object($node)) { $node = get_object_vars($node); }
        if (!is_array($node)) { return null; }
        if (isset($node["indexName"]) && is_string($node["indexName"])) {
            return $node["indexName"];
        }
        foreach ($node as $k => $v){
            if ($k === "rejectedPlans" || $k === "allPlansExecution") { continue; }
            $hit = explain_findIndexName($v);
            if (!is_null($hit)) { return $hit; }
        }
        return null;
    }

    /* A comparable signature for an index key, so this migration can recognise
     * the index it wants by what it does rather than by what it is called.
     *
     * An operator who built it by hand will have chosen their own name, and
     * MongoDB refuses createIndexes for a key that already exists under a
     * different name -- so matching on the name alone would report the work as
     * outstanding and then fail trying to repeat it. Directions are cast
     * because the server may return them as int or double. */
    function keySig($key){
        if (is_object($key)) { $key = get_object_vars($key); }
        if (!is_array($key)) { return ""; }
        $parts = array();
        foreach ($key as $f => $d){ $parts[] = $f . ":" . (int)$d; }
        return implode(",", $parts);
    }

    /* name => ["json" => printable key, "sig" => comparable key] */
    function listIndexes($col){
        global $dbm, $settings;
        $res = $dbm->executeCommand($settings['db_name'],
            new MongoDB\Driver\Command(['listIndexes' => $col]))->toArray();
        $out = array();
        foreach ($res as $i){
            $out[$i->name] = array("json" => json_encode($i->key), "sig" => keySig($i->key));
        }
        return $out;
    }

    /* The name of the index holding this key, whatever it is called, or null. */
    function indexNameForKey(array $indexes, $sig){
        foreach ($indexes as $name => $meta){
            if ($meta["sig"] === $sig) { return $name; }
        }
        return null;
    }

    /* Any address proves it: whether a plan is covered is a property of the
     * plan, not of how many movements the address happens to have. So take the
     * address off one recent movement rather than paying for a 47M-document
     * group to find the biggest one. */
    function sampleAddress(){
        global $dbm, $settings;
        $q = new MongoDB\Driver\Query([], ['sort' => ['move_time_sec' => -1], 'limit' => 1,
                                           'projection' => ['address' => 1]]);
        $r = $dbm->executeQuery($settings['db_name'] . ".ADDRESSMOVES", $q)->toArray();
        return count($r) > 0 && isset($r[0]->address) ? $r[0]->address : null;
    }

    function chartPipeline($address){
        return [
            ['$match' => ['address' => $address,
                          'move_time_sec' => ['$gte' => 0, '$lte' => time()]]],
            ['$group' => [
                '_id' => ['$subtract' => ['$move_time_sec',
                                          ['$mod' => ['$move_time_sec', 86400]]]],
                'movementsTotal' => ['$sum' => '$amount'],
                'receivedTotal'  => ['$sum' => '$vouts_amount'],
                'sentTotal'      => ['$sum' => '$vins_amount'],
            ]],
            ['$sort' => ['_id' => 1]],
        ];
    }

    /* Returns [covered, index_used, docs_examined, error]. */
    function checkCoverage($address){
        global $dbm, $settings;
        try {
            $cmd = new MongoDB\Driver\Command([
                'explain' => [
                    'aggregate' => 'ADDRESSMOVES',
                    'pipeline'  => chartPipeline($address),
                    'cursor'    => new stdClass(),
                ],
                'verbosity' => 'executionStats',
            ]);
            $r = $dbm->executeCommand($settings['db_name'], $cmd)->toArray()[0];
        } catch (Throwable $e) {
            return array(false, null, null, $e->getMessage());
        }
        $stats    = explain_findStats($r);
        $examined = is_null($stats) ? null : (int)$stats["totalDocsExamined"];
        $keys     = is_null($stats) ? null : (int)$stats["totalKeysExamined"];
        $name     = explain_findIndexName($r);
        /* Covered means the answer came from index keys alone. Zero documents
           read with keys actually scanned is the whole test: no document reads
           is the property the chart needs, whatever the stages are called. */
        $covered = ($examined === 0 && !is_null($keys) && $keys > 0);
        return array($covered, $name, $examined, null);
    }

    out("");
    out("Address movement chart index migration");
    out(str_repeat("=", 54));
    out("  database   " . $settings['db_name']);

    $NEW_SIG = keySig($NEW_KEY);
    $idx = listIndexes("ADDRESSMOVES");

    /* Match the covering index by key, not by name: an operator may have built
       it by hand and named it something else, and it is the key that does the
       work. */
    $new_actual = indexNameForKey($idx, $NEW_SIG);
    $has_new    = !is_null($new_actual);
    $has_old    = array_key_exists($OLD_NAME, $idx);

    out("");
    out("Indexes on ADDRESSMOVES:");
    foreach ($idx as $n => $meta){
        $mark = "";
        if ($n === $new_actual) { $mark = "  <- covers the chart"; }
        else if ($n === $OLD_NAME) { $mark = "  <- superseded"; }
        out("  " . str_pad($n, 40) . $meta["json"] . $mark);
    }
    if ($has_new && $new_actual !== $NEW_NAME){
        out("");
        out("Note: the covering index is present under the name " . $new_actual . ",");
        out("      not " . $NEW_NAME . " as a fresh install creates it. The key is what");
        out("      matters, so there is nothing to build -- the name differs only in");
        out("      cosmetics. Renaming would mean a rebuild; not worth it.");
    }

    $addr = sampleAddress();
    if (is_null($addr)){
        out("");
        out("ADDRESSMOVES is empty -- nothing to migrate.");
        exit(0);
    }

    out("");
    out("Plan for the chart aggregation (sample address " . $addr . "):");
    list($covered, $used, $examined, $err) = checkCoverage($addr);
    if (!is_null($err)){
        out("  could not explain: " . $err);
    } else {
        out("  index used        " . ($used ?: "none identified"));
        out("  documents fetched " . (is_null($examined) ? "?" : $examined));
        out("  covered           " . ($covered ? "YES -- no document reads" : "no"));
    }

    if (!$do_create && !$do_drop){
        out("");
        if (!$has_new){
            out("Nothing has been changed. Re-run with --create to build " . $NEW_NAME . ".");
            out("Pause the loaders first: touch script/tmp/stop.all");
        } else if ($has_old && $covered){
            out("Nothing has been changed. The new index is in place and covering;");
            out("re-run with --drop-old to remove the superseded " . $OLD_NAME . ".");
        } else {
            out("Nothing has been changed. Nothing left to do.");
        }
        out("");
        exit(0);
    }

    if ($do_create){
        out("");
        if ($has_new){
            out("The covering index already exists as " . $new_actual . " -- skipping.");
        } else {
            out("Building " . $NEW_NAME . " ... this reads the whole collection.");
            $cmd = new MongoDB\Driver\Command([
                "createIndexes" => "ADDRESSMOVES",
                "indexes" => [[
                    "name"   => $NEW_NAME,
                    "key"    => (object)$NEW_KEY,
                    "unique" => false,
                    "ns"     => $settings["db_name"] . ".ADDRESSMOVES",
                ]],
            ]);
            $t0 = microtime(true);
            try {
                $dbm->executeCommand($settings['db_name'], $cmd);
                out("  Done in " . number_format(microtime(true) - $t0, 1) . " s");
            } catch (Throwable $e){
                out("  FAILED: " . $e->getMessage());
                exit(1);
            }
            list($covered, $used, $examined, $err) = checkCoverage($addr);
            out("");
            out("Re-checked plan:");
            out("  index used        " . ($used ?: "none identified"));
            out("  documents fetched " . (is_null($examined) ? "?" : $examined));
            out("  covered           " . ($covered ? "YES" : "no"));
            if (!$covered){
                out("");
                out("The new index is not being used as a covering index. Leave the old");
                out("one in place and investigate before dropping anything.");
                exit(1);
            }
        }
    }

    if ($do_drop){
        out("");
        $idx = listIndexes("ADDRESSMOVES");
        if (is_null(indexNameForKey($idx, $NEW_SIG))){
            out("Refusing to drop " . $OLD_NAME . ": no index carries the covering key");
            out("  " . json_encode($NEW_KEY) . " yet. Run --create first.");
            exit(1);
        }
        if (!array_key_exists($OLD_NAME, $idx)){
            out("Index " . $OLD_NAME . " is already gone -- nothing to drop.");
        } else {
            list($covered, $used, $examined, $err) = checkCoverage($addr);
            if (!$covered){
                out("Refusing to drop " . $OLD_NAME . ": the chart plan is not covered by");
                out($NEW_NAME . " yet, so dropping it would make the chart slower, not faster.");
                exit(1);
            }
            out("Dropping " . $OLD_NAME . " ...");
            try {
                $dbm->executeCommand($settings['db_name'], new MongoDB\Driver\Command([
                    'dropIndexes' => 'ADDRESSMOVES', 'index' => $OLD_NAME,
                ]));
                out("  Done");
            } catch (Throwable $e){
                out("  FAILED: " . $e->getMessage());
                exit(1);
            }
        }
    }

    out("");
    out("Final indexes on ADDRESSMOVES:");
    foreach (listIndexes("ADDRESSMOVES") as $n => $meta){
        out("  " . str_pad($n, 40) . $meta["json"]);
    }
    out("");
?>
