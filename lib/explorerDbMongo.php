<?php
use Decimal\Decimal;
// --- MongoDB Collections ---
    function mongo_checkCollections($dbm, $db_name){
        global $settings;

        $cols = mongo_listDbCollections();
        print("Checking MongoDB collections \n");
            if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
                // AIRDROPS
            if (!array_key_exists("AIRDROPS", $cols)) {
                mongo_createAirdropsCollection($dbm, $db_name, "AIRDROPS");
            } else {
                print("  Collection AIRDROPS already exists \n");
            }
        }
            // VOUTS
        if (!array_key_exists("VOUTS", $cols)) {
            mongo_createVoutsCollection($dbm, $db_name, "VOUTS");
        } else {
            print("  Collection VOUTS already exists \n");
        }
            // TXS
        if (!array_key_exists("TXS", $cols)) {
            mongo_createTxsCollection($dbm, $db_name, "TXS");
        } else {
            print("  Collection TXS already exists \n");
        }
            // BLOCKS
        if (!array_key_exists("BLOCKS", $cols)) {
            mongo_createBlocksCollection($dbm, $db_name, "BLOCKS");
        } else {
            print("  Collection BLOCKS already exists \n");
        }
            // ADDRESSES
        if (!array_key_exists("ADDRESSES", $cols)) {
            mongo_createAddressesCollection($dbm, $db_name, "ADDRESSES");
        } else {
            print("  Collection ADDRESSES already exists \n");
        }
            // ADDRESSMOVES
        if (!array_key_exists("ADDRESSMOVES", $cols)) {
            mongo_createAddressMovesCollection($dbm, $db_name, "ADDRESSMOVES");
        } else {
            print("  Collection ADDRESSMOVES already exists \n");
        }
        if (isset($settings["rvn_assets"]) && $settings["rvn_assets"]){
                // ASSETS
            if (!array_key_exists("ASSETS", $cols)) {
                mongo_createAssetsCollection($dbm, $db_name, "ASSETS");
            } else {
                print("  Collection ASSETS already exists \n");
            }
                // ASSETMOVES
            if (!array_key_exists("ASSETMOVES", $cols)) {
                mongo_createAssetMovesCollection($dbm, $db_name, "ASSETMOVES");
            } else {
                print("  Collection ASSETMOVES already exists \n");
            }

        }
            // USERS
        if (!array_key_exists("USERS", $cols)) {
            mongo_createUsersCollection($dbm, $db_name, "USERS");
        } else {
            print("  Collection USERS already exists \n");
        } 
            // USERSESSIONS
        if (!array_key_exists("USERSESSIONS", $cols)) {
            mongo_createUserSessionsCollection($dbm, $db_name, "USERSESSIONS");
        } else {
            print("  Collection USERSESSIONS already exists \n");
        } 
            // PROBLEMLOG
        if (!array_key_exists("PROBLEMLOG", $cols)) {
            mongo_createProblemlogCollection($dbm, $db_name, "PROBLEMLOG");
        } else {
            print("  Collection PROBLEMLOG already exists \n");
        } 
            // BLOCKROLLBACKS
        if (!array_key_exists("BLOCKROLLBACKS", $cols)) {
            mongo_createBlockRollbackCollection($dbm, $db_name, "BLOCKROLLBACKS");
        } else {
            print("  Collection BLOCKROLLBACKS already exists \n");
        }
            // BLOCKSTATE
        if (!array_key_exists("BLOCKSTATE", $cols)) {
            mongo_createBlockStateCollection($dbm, $db_name, "BLOCKSTATE");
        } else {
            print("  Collection BLOCKSTATE already exists \n");
        }
            // ADDRESSGROUPS
        if (!array_key_exists("ADDRESSGROUPS", $cols)) {
            mongo_createAddressGroupCollection($dbm, $db_name, "ADDRESSGROUPS");
        } else {
            print("  Collection ADDRESSGROUPS already exists \n");
        } 
            // MARKETS
        if (!array_key_exists("MARKETS", $cols)) {
            mongo_createMarketsCollection($dbm, $db_name, "MARKETS");
        } else {
            print("  Collection MARKETS already exists \n");
        } 
            // CHAINMETRICS
        if (!array_key_exists("CHAINMETRICS", $cols)) {
            mongo_createChainMetricsCollection($dbm, $db_name, "CHAINMETRICS");
        } else {
            print("  Collection CHAINMETRICS already exists \n");
        } 
            // CHAINSTATS
        if (!array_key_exists("CHAINSTATS", $cols)) {
            mongo_createChainStatsCollection($dbm, $db_name, "CHAINSTATS");
        } else {
            print("  Collection CHAINSTATS already exists \n");
        } 
            // HOSTMETRICS
        if (!array_key_exists("HOSTMETRICS", $cols)) {
            mongo_createHostMetricsCollection($dbm, $db_name, "HOSTMETRICS");
        } else {
            print("  Collection HOSTMETRICS already exists \n");
        } 
            // BLOCKSTATS
        if (!array_key_exists("BLOCKSTATS", $cols)) {
            mongo_createBlockStatsCollection($dbm, $db_name, "BLOCKSTATS");
        } else {
            print("  Collection BLOCKSTATS already exists \n");
        } 

            // MASTERNODES
        if (!array_key_exists("MASTERNODES", $cols)) {
            mongo_createMasternodesCollection($dbm, $db_name, "MASTERNODES");
        } else {
            print("  Collection MASTERNODES already exists \n");
        } 
            // APIRATELIMITER
        if (!array_key_exists("APIRATELIMITER", $cols)) {
            mongo_createApiRateLimiterCollection($dbm, $db_name, "APIRATELIMITER");
        } else {
            print("  Collection APIRATELIMITER already exists \n");
        } 
            // LOADERLOG
        if (!array_key_exists("LOADERLOG", $cols)) {
            mongo_createLoaderLogCollection($dbm, $db_name, "LOADERLOG");
        } else {
            print("  Collection LOADERLOG already exists \n");
        } 

            // APILOG
        if (!array_key_exists("APILOG", $cols)) {
            mongo_createApiLogCollection($dbm, $db_name, "APILOG");
        } else {
            print("  Collection APILOG already exists \n");
        } 
            // APIAGGREGATES
        if (!array_key_exists("APIAGGREGATES", $cols)) {
            mongo_createApiAggregatesCollection($dbm, $db_name, "APIAGGREGATES");
        } else {
            print("  Collection APIAGGREGATES already exists \n");
        } 

            // PAGELOG
        if (!array_key_exists("PAGELOG", $cols)) {
            mongo_createPageLogCollection($dbm, $db_name, "PAGELOG");
        } else {
            print("  Collection PAGELOG already exists \n");
        } 
            // PAGEAGGREGATES
        if (!array_key_exists("PAGEAGGREGATES", $cols)) {
            mongo_createPageAggregatesCollection($dbm, $db_name, "PAGEAGGREGATES");
        } else {
            print("  Collection PAGEAGGREGATES already exists \n");
        } 
            // PAGEMETRICS
        if (!array_key_exists("PAGEMETRICS", $cols)) {
            mongo_createPageMetricsCollection($dbm, $db_name, "PAGEMETRICS");
        } else {
            print("  Collection PAGEMETRICS already exists \n");
        } 
            // ADSMETRICS
        if (!array_key_exists("ADSMETRICS", $cols)) {
            mongo_createAdsMetricsCollection($dbm, $db_name, "ADSMETRICS");
        } else {
            print("  Collection ADSMETRICS already exists \n");
        } 
            // REQUESTS
        if (!array_key_exists("REQUESTS", $cols)) {
            mongo_createRequestsCollection($dbm, $db_name, "REQUESTS");
        } else {
            print("  Collection REQUESTS already exists \n");
        } 
            // MDAO BALANCES
        if ( isset($settings["special_features"]["MDAO_API"]) && $settings["special_features"]["MDAO_API"] && isset($settings["special_features"]["MDAO_balance"]) && $settings["special_features"]["MDAO_balance"] ){
            if (!array_key_exists("MDAOBALANCES", $cols)) {
                mongo_createMdaoBalancesCollection($dbm, $db_name, "MDAOBALANCES");
            } else {
                print("  Collection MDAOBALANCES already exists \n");
            }
        }
            // SHADOW LEDGER (Gold Rush) index. Read from settings rather than
            // shadow_enabled(), which lives in loaderShadow.php and is not
            // loaded by every caller of this function.
        if ( isset($settings["shadow_accounting"]) && $settings["shadow_accounting"] ){
            mongo_ensureShadowVoutIndex($dbm, $db_name);
        }
        mongo_ensureLoaderStopIndex($dbm, $db_name);
        mongo_ensureUsersIndexes($dbm, $db_name);


    }
    /**
     * Index the shadow-ledger (Gold Rush) outputs in VOUTS.
     *
     * Synthetic payouts are a tiny fraction of the collection - tens of
     * thousands against tens of millions - yet every shadow query filters on
     * synthetic == true. Unindexed that is a collection scan which outgrows any
     * web timeout as the chain grows, so the index is partial: it holds only
     * the synthetic outputs, which keeps it small and adds no write cost for
     * ordinary vouts. block_ix is carried as the second key so the recent
     * payouts query gets its sort from the same index.
     *
     * createIndexes is idempotent, so this runs on every start and also
     * repairs databases created before the index existed.
     */
    /**
     * Partial index over the PROBLEMLOG entries written by ldr_abort(), so
     * getsummary finds the latest loader stop without scanning a log that
     * runs to six figures on older chains. Idempotent, runs on every start.
     */
    function mongo_ensureLoaderStopIndex($dbm, $db_name){
        $command = new MongoDB\Driver\Command([
            "createIndexes" => "PROBLEMLOG",
            "indexes" => [[
                "name"                    => "loader_stop_partial",
                "key"                     => ["loader_stop" => 1, "detected_at" => -1],
                "partialFilterExpression" => ["loader_stop" => true],
            ]],
        ]);
        try {
            $res = $dbm->executeCommand($db_name, $command)->toArray()[0];
            return isset($res->ok) && $res->ok == 1;
        } catch (Exception $e) {
            print("  Index loader_stop_partial on PROBLEMLOG failed: ".$e->getMessage()." \n");
            return false;
        }
    }
    /**
     * Databases created before 2.0.228 carry a unique index on USERS.name, a
     * field user documents never had, so every account after the first failed
     * with a duplicate key on null. User names are the _id, which is unique
     * already; drop the old index. Idempotent, runs on every start.
     */
    function mongo_ensureUsersIndexes($dbm, $db_name){
        try {
            $indexes = $dbm->executeCommand($db_name, new MongoDB\Driver\Command(["listIndexes" => "USERS"]))->toArray();
            foreach ($indexes as $ix){
                if (isset($ix->name) && $ix->name === "name_unique"){
                    $dbm->executeCommand($db_name, new MongoDB\Driver\Command(["dropIndexes" => "USERS", "index" => "name_unique"]));
                    print("  Index name_unique on USERS dropped \n");
                }
            }
            return true;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            // NamespaceNotFound: USERS does not exist yet, nothing to repair.
            if ($e->getCode() == 26) return true;
            print("  Checking USERS indexes failed: ".$e->getMessage()." \n");
            return false;
        }
    }
    function mongo_ensureShadowVoutIndex($dbm, $db_name){
        $command = new MongoDB\Driver\Command([
            "createIndexes" => "VOUTS",
            "indexes" => [[
                "name"                    => "synthetic_partial",
                "key"                     => ["synthetic" => 1, "block_ix" => -1],
                "partialFilterExpression" => ["synthetic" => true],
                "unique"                  => false,
            ]],
        ]);
        try {
            $res = $dbm->executeCommand($db_name, $command)->toArray()[0];
            if (isset($res->ok) && $res->ok == 1){
                print("  Index synthetic_partial on VOUTS is in place \n");
                return true;
            }
            print("  Index synthetic_partial on VOUTS was not confirmed \n");
        } catch (MongoDB\Driver\Exception\Exception $e) {
            // Not fatal: the loader writes fine without it, only the Gold Rush
            // page degrades, so report and carry on rather than stop a sync.
            print("  Index synthetic_partial on VOUTS failed: ".$e->getMessage()." \n");
        }
        return false;
    }
    function mongo_createVoutsCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "block_ix_nonunique",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }
    function mongo_createAirdropsCollection($dbm, $db_name, $col){
        //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "block_ix_nonunique",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }
        function mongo_createTxsCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "block_ix_nonunique",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createBlocksCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "block_ix_nonunique",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "block_time_sec_nonunique",
                "key" => ["block_time_sec" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "type_block_ix_desc",
                "key" => (object) ["type" => 1, "block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createAddressesCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "balance_nonunique",
                "key" => ["balance" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "last_block_nonunique",
                "key" => ["last_block" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "group_id_balance_nonunique",
                "key" => (object) ["group_id" => 1, "balance" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "assets_wildcard",
                "key" => ["assets.$**" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createAddressMovesCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index        
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                    "name" => "address_block_nu",
                    "key" => (object) ["address" => 1, "block_ix" => -1],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                ], [
                    "name" => "time_desc",
                    "key" => ["move_time_sec" => -1],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                ], [
                    "name" => "tx_id_nonunique",
                    "key" => ["tx_id" => 1],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                // ], [
                //     "name" => "amount_nonunique",
                //     "key" => ["amount" => 1],
                //     "unique" => false,
                //     "ns" => $db_name . "." . $col,
                // ], [
                //     "name" => "amount_nonunique_desc",
                //     "key" => ["amount" => -1],
                //     "unique" => false,
                //     "ns" => $db_name . "." . $col,
                ], [
                    "name" => "time_asc_amount_desc_partial",
                    "key" => ["move_time_sec" => 1, "amount" => -1],
                    "partialFilterExpression" => [ "amount" => [ '$gt' => 0] ],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                ], [
                    "name" => "time_asc_amount_asc_partial",
                    "key" => ["move_time_sec" => 1, "amount" => 1],
                    "partialFilterExpression" => [ "amount" => [ '$lt' => 0] ],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                // ], [
                //     "name" => "time_asc_amount_asc",
                //     "key" => ["move_time_sec"=>1, "amount" => 1],
                //     "unique" => false,
                //     "ns" => $db_name . "." . $col,
                ], [
                    "name" => "block_ix_desc",
                    "key" => ["block_ix" => -1],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                ], [
                    "name" => "is_reward_nonunique",
                    "key" => ["is_reward" => 1],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                ], [
                    "name" => "address_asc_time_desc_amount_asc",
                    "key" => ["address" => 1, "move_time_sec" => -1,"amount"=>1],
                    "unique" => false,
                    "ns" => $db_name . "." . $col,
                ]
            ],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createAssetsCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
             "createIndexes" => $col,
             "indexes" => [[
                 "name" => "type_nonunique",
                 "key" => ["type" => 1],
                 "unique" => false,
                 "ns" => $db_name . "." . $col,
            ], [
                "name" => "genesis_timestamp_desc",
                "key" => ["genesis_timestamp" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
        //     ], [
        //         "name" => "group_id_nonunique",
        //         "key" => ["group_id" => 1],
        //         "unique" => false,
        //         "ns" => $db_name . "." . $col,
             ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }

    
    function mongo_createAssetMovesCollection($dbm, $db_name, $col){
        //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
            //$key = new stdClass();
            //$key->address = 1;
            //$key->block_ix = -1;  
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "address_block_nu",
                "key" => (object)["address" => 1, "block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "move_time_sec_nonunique",
                "key" => ["move_time_sec" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "tx_id_nonunique",
                "key" => ["tx_id" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "amount_nonunique_asc",
                "key" => ["amount" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "amount_nonunique_desc",
                "key" => ["amount" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "block_ix_desc",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "name_block_amount_nu",
                "key" => (object)["name" => 1,"block_ix" => -1, "amount" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "message_partial",
                "key" => (object)["message" => 1],
                "partialFilterExpression" => ["message" => ['$exists'=> true ]],
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createVoutSuspectsCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "block_ix_nonunique",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "tx_id_nonunique",
                "key" => ["tx_id" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "suspect_type_nonunique",
                "key" => ["suspect_type" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createPeersCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "peer_ip_unique",
                "key" => ["peer_ip" => 1],
                "unique" => true,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "last_seen_nonunique",
                "key" => ["last_seen" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "country_nonunique",
                "key" => ["country" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "ip_version_nonunique",
                "key" => ["ip_version" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createProblemlogCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "log_id_unique",
                "key" => ["log_id" => 1],
                "unique" => true,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "block_ix_nonunique",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "level_nonunique",
                "key" => ["level" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ], [
                "name" => "object_id_nonunique",
                "key" => ["object_id" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createBunchesCollection($dbm, $db_name, $col){
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "bunch_id_unique",
                "key" => ["bunch_id" => 1],
                "unique" => true,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createBlockRollbackCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }

    }
    function mongo_createBlockStateCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }

    }
    function mongo_createAddressGroupCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "address_count_nonunique",
                "key" => ["address_count" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ],[
                "name" => "balance_address_count_nonunique",
                "key" => (object) ["balance" => -1, "address_count" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createMarketsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
    }
    function mongo_createHostMetricsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
    }
    function mongo_createChainMetricsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }

    }
    function mongo_createChainStatsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }

    }
    function mongo_createBlockStatsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }

    }
    function mongo_createMasternodesCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 86400; // 1209600 seconds = 2 weeks
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "list_address_asc",
                "key" => ["list.addr" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "list_status_asc",
                "key" => ["list.status" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }
    function mongo_createApiRateLimiterCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        if (isset($settings['api_burst_period_seconds']) && $settings['api_burst_period_seconds']>0){
            $bp = (int)$settings['api_burst_period_seconds'];
        } else {
            $bp = 1;
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "ip_address_nonunique",
                "key" => ["ip_address" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "request_time_ttl",
                "key" => ["request_time" => 1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

        // set mongo ttlMonitorSleepSecs
        // db.adminCommand({setParameter:1, ttlMonitorSleepSecs: 60});
        $cmd = new MongoDB\Driver\Command([
            "setParameter" => 1,
            "ttlMonitorSleepSecs" => $bp
        ]);
        try{
            $res = $dbm->executeCommand('admin', $cmd);  
            print("    Parameter ttlMonitorSleepSecs set to " . $bp . " seconds \n");
          } catch (Exception $e) {
            print("    Failed to adjust ttlMonitorSleepSecs parameter to " . $bp . " seconds \n");
          }

    }
    function mongo_createLoaderLogCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
            $bp = (int)$settings['log_record_timeout_sec'];
        } else {
            $bp = 1209600; // 1209600 seconds = 2 weeks
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "block_ix_desc",
                "key" => ["block_ix" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "processing_time",
                "key" => ["processing_time" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "timestamp_nonunique_desc",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }

    function mongo_createApiLogCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 7257600; // 1209600 seconds = 2 weeks
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "ip",
                "key" => ["ip" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "duration_desc",
                "key" => ["duration" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }
    function mongo_createApiAggregatesCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 7257600; // 1209600 seconds = 2 weeks
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }
    function mongo_createPageLogCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 7257600; // 1209600 seconds = 2 weeks
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "ip",
                "key" => ["ip" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "duration_desc",
                "key" => ["duration" => -1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
                ],
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }

    function mongo_createPageMetricsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 30*24*3600; // 30 days
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }

    function mongo_createAdsMetricsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 60*24*3600; // 30 days
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }


    function mongo_createPageAggregatesCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 7257600; // 1209600 seconds = 2 weeks
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }
    function mongo_createRequestsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
        //if (isset($settings['log_record_timeout_sec']) && $settings['log_record_timeout_sec']>0){
        //    $bp = (int)$settings['log_record_timeout_sec'];
        //} else {
            $bp = 60; // 60 seconds
        //}
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "timestamp_desc_ttl",
                "key" => ["timestamp" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }

    function mongo_createUsersCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "groups_wildcard",
                "key" => ["groups.$**" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createUserSessionsCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;
        $et = (isset($settings["session_inactivity_sec"]) && is_integer($settings["session_inactivity_sec"])) ? (int) $settings["session_inactivity_sec"] : 86400 ;
        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }
            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [[
                "name" => "name_nonunique",
                "key" => ["name" => 1],
                "unique" => false,
                "ns" => $db_name . "." . $col,
            ],[
                "name" => "last_active_ttl",
                "key" => ["last_active" => 1],
                "expireAfterSeconds" => $et,
                "ns" => $db_name . "." . $col,
            ]],
        ]);
        $res = $dbm->executeCommand($db_name, $command);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }

    }
    function mongo_createMdaoBalancesCollection($dbm, $db_name, $col){
        global $settings;
            //create collection
        $cmd = array();
        $cmd["create"] = (string)$col;

        $dbc = new MongoDB\Driver\Command($cmd);
        $res = $dbm->executeCommand($db_name, $dbc);
        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("  Collection " . $col . " created \n");
        } else {
            print("  Collection " . $col . " creation failed \n");
            die();
        }

        $bp = 3600; // 1209600 seconds = 2 weeks

            //create index
        $command = new MongoDB\Driver\Command([
            "createIndexes" => $col,
            "indexes" => [
                [
                "name" => "updated_desc_ttl",
                "key" => ["updated" => -1],
                "expireAfterSeconds" => $bp,
                "ns" => $db_name . "." . $col
                ]
            ],
        ]);
        try{
          $res = $dbm->executeCommand($db_name, $command);  
        } catch (Exception $e) {
            var_dump($e);
        }


        $ec = $res->toArray()[0];
        if ($ec->ok == 1) {
            print("    Indexes for collection " . $col . " created \n");
        } else {
            print("    Indexes creation for collection " . $col . " failed \n");
        }
    }

// --- MongoDB Queries ---
  // >>> General Queries <<<
    function mongo_checkDbConnected(){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command(
            ['connectionStatus' => 1]
        );
        try {
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $ra = $res->toArray()[0];
            return (($ra->ok == 1) ? true : false);
        } catch (Exception $e){
            return false;
        }
        
    }
    /*
     * Web requests (not the CLI loaders) get a server-side time limit on the
     * queries a visitor can make expensive: counts, history pages, searches.
     */
    function mongo_webTimeLimit(array $opts) : array {
        if (PHP_SAPI !== 'cli' && !isset($opts['maxTimeMS'])) { $opts['maxTimeMS'] = 20000; }
        return $opts;
    }

    function mongo_execDbQuery($query, $collection){
        global $dbm;
        global $settings;
        $cursor = $dbm->executeQuery($settings['db_name'] . "." . $collection, $query);
        return $cursor;
    }
    function mongo_listDbCollections(){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command([
            "listCollections" => 1,
            "authorizedCollections" => true,
            "nameOnly" => true
        ]);
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $c_ar = array();
        foreach ($res as $c) {
            $c_ar[$c->name] = $c->type;
        }
        ksort($c_ar);
        return $c_ar;
    }
    function mongo_countDbCollection($collection){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command(
            ['count' => $collection]
        );
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $n = $res->toArray()[0]->n;
        if ($n == 0) {
            $n = -1;
        }
        return $n;
    }
    function mongo_countDbCollectionQuery($collection, $query){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command(
            mongo_webTimeLimit(['count' => $collection, "query" => (object)$query])
        );
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $n = $res->toArray()[0]->n;
        if ($n == 0) {
            $n = -1;
        }
        return $n;
    }
    function mongo_GetDbCollectionStats($collection){
        return mongo_sizeDbCollection($collection);
    }
    function mongo_sizeDbCollection($collection){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command(
            ['collstats' => $collection]
        );
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $ra = $res->toArray()[0];
        return array(
            "count" => $ra->count,
            "storage" => $ra->storageSize,
            "index" => $ra->totalIndexSize,
            "total" => $ra->totalIndexSize + $ra->storageSize,
            "indexSizes" => $ra->indexSizes,
            "indexCount" => $ra->nindexes,
            "scaleFactor" => $ra->scaleFactor
        );
    }
    function mongo_sizeDb(){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command(
            ['dbStats' => 1]
        );
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $ra = $res->toArray()[0];
        return array(
            "data" => $ra->dataSize,
            "storage" => $ra->storageSize,
            "index" => $ra->indexSize,
            "total" => $ra->indexSize + $ra->storageSize
        );
    }
    function mongo_getDbVersion(){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command(
            ['serverStatus' => true]
        );
        $res = $dbm->executeCommand($settings['db_name'], $dbc);
        $ra = $res->toArray()[0]->version;
        return $ra;
    }
    function mongo_getDbCoinSupply(){
        global $settings;
        $res = new stdClass();
        $res->pow_supply = @cdec(0);
        $res->pos_supply = @cdec(0);
        $res->illegal_supply = @cdec(0);
        $res->burned = @cdec(0);
        try {
            $cba = mongo_getAddressData("Coinbase");
            (count($cba)>0) ? $res->pow_supply = @cdec($cba[0]->sent) : $res->pow_supply = @cdec(0);
        } catch (Exception $e) {}
        try {
            $pcba = mongo_getAddressData("PoS Coinbase");
            (count($pcba)>0) ? $res->pos_supply = @cdec($pcba[0]->sent) : $res->pos_supply = @cdec(0);
        } catch (Exception $e) {}
        try {
            $isa = mongo_getAddressData("Illegal Supply");
            (count($isa)>0) ? $res->illegal_supply = @cdec($isa[0]->sent) : $res->illegal_supply = @cdec(0);
        } catch (Exception $e) {}

        if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
            try {
                $asa = mongo_getAddressData("Airdrop");
                (count($asa)>0) ? $res->airdrop_supply = @cdec($asa[0]->sent) : $res->airdrop_supply = @cdec(0);
            } catch (Exception $e) {}
            }
        try {
            if (isset($settings['burn_addresses']) && count($settings['burn_addresses'])>0){
                foreach ($settings['burn_addresses'] as $ba){
                    $bb = db_getAddressData($ba);
                    if (count($bb) > 0){
                        $res->burned += @cdec($bb[0]->received);
                    }
                }
            } else {
                $res->burned = @cdec(db_getAddressData("Fee Collector")[0]->received);
            }
        } catch (Exception $e) {
        }
        if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
            $res->coincirculation = @cdec($res->airdrop_supply + $res->pow_supply + $res->pos_supply + $res->illegal_supply - $res->burned);
            $res->moneysupply =     @cdec($res->airdrop_supply + $res->pow_supply + $res->pos_supply + $res->illegal_supply );
        }else{
            $res->coincirculation = @cdec($res->pow_supply + $res->pos_supply + $res->illegal_supply - $res->burned);
            $res->moneysupply =     @cdec($res->pow_supply + $res->pos_supply + $res->illegal_supply);
        }
        try {
            $zc = mongo_getAddressData("Zerocoin");
            (count($zc)>0) ? $res->zerocoinsupply = @cdec($zc[0]->balance) : $res->zerocoinsupply = 0;
        } catch (Exception $e) {
            $res->zerocoinsupply = 0;
        }
        try {
            $sc = mongo_getAddressData("Shield");
            (count($sc)>0) ? $res->shieldsupply = @cdec($sc[0]->balance) : $res->shieldsupply = 0;
        } catch (Exception $e) {
            $res->shieldsupply = 0;
        }
        try {
            $cp = mongo_getAddressData("Credit Pool");
            (count($cp)>0) ? $res->creditpoolsupply = @cdec($cp[0]->balance) : $res->creditpoolsupply = 0;
        } catch (Exception $e) {
            $res->creditpoolsupply = 0;
        }
        return $res;
    }
  // >>> Block Queries <<<
    function mongo_getLastDbBlockNumber(){
        $n = mongo_countDbCollection("BLOCKS");
        if ($n > -1) {
            $query = new MongoDB\Driver\Query(
                [], 
                ['sort' => ['block_ix' => -1], 'limit' => 1]
            );
            $cursor = mongo_execDbQuery($query, "BLOCKS");
            $res = $cursor->toArray();
            $n = $res[0]->block_ix;
            return $n;
        } else {
            return $n;
        }
    }
    function mongo_getBlockByHash($block_hash){
        $query = new MongoDB\Driver\Query(
            ['_id' => $block_hash],
            []
        );
        $cursor = mongo_execDbQuery($query, "BLOCKS");
        return $cursor->toArray();
    }
    function mongo_getBlockByHeight($block_ix){
        $query = new MongoDB\Driver\Query(
            ['block_ix' => $block_ix] 
        );
        $cursor = mongo_execDbQuery($query, "BLOCKS");
        return $cursor->toArray();
    }
    function mongo_getLastBlocks($limit = 100, $skip = 0, $filter = null){  // SIP-061 added filter
        if (is_null($filter) || !is_array($filter)){$filter = array();}
        $query = new MongoDB\Driver\Query(
            $filter,  // SIP-061 added filter
            ['sort' => ['block_ix' => -1], 'skip' => $skip, 'limit' => $limit] 
        );
        $cursor = mongo_execDbQuery($query, "BLOCKS");
        return $cursor->toArray();
    }
    /* Oldest-first page of blocks. The mirror of mongo_getLastBlocks(), so a page
       near the far end of the list can be reached by skipping from the near end of
       the other sort order -- a skip of 4.8M walks and discards 4.8M documents,
       while the same page is a skip of a few from this direction. */
    function mongo_getFirstBlocks($limit = 100, $skip = 0, $filter = null){
        if (is_null($filter) || !is_array($filter)){$filter = array();}
        $query = new MongoDB\Driver\Query(
            $filter,
            ['sort' => ['block_ix' => 1], 'skip' => $skip, 'limit' => $limit]
        );
        $cursor = mongo_execDbQuery($query, "BLOCKS");
        return $cursor->toArray();
    }
    function mongo_getBlocksByTimeRange($start_time, $end_time, $sort_asc = false, $gte_start = false, $lte_end = true){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        $query = new MongoDB\Driver\Query(
            ['block_time_sec' => [$scond => $start_time, $econd => $end_time]],
            ['sort' => ['block_ix' => $sortorder]]
        );
        $cursor = mongo_execDbQuery($query, "BLOCKS");
        return $cursor->toArray();
    }
    function mongo_countBlocksByTimeRange($start_time, $end_time, $gte_start = false, $lte_end = true){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }

        $query = ['block_time_sec' => [$scond => $start_time, $econd => $end_time]];

        $n = mongo_countDbCollectionQuery("BLOCKS", $query);
        return $n;
    }
    function mongo_getBlockRewardSumByTimeRange($start_time, $end_time, $gte_start = false, $lte_end = true){
        global $dbm;
        global $settings;
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKS',
            'pipeline' => [
                ['$match' => ['block_time_sec' => [$scond => $start_time, $econd => $end_time]]],
                ['$group' => 
                    [ 
                    '_id' => 0,
                    'totalFee' => [ '$sum' => '$fee_amount' ],
                    'totalStake' => [ '$sum' => '$stake_reward' ],
                    'totalMn' => [ '$sum' => '$mn_reward' ],
                    'totalPow' => [ '$sum' => '$pow_reward' ],
                    'totalDev' => [ '$sum' => '$dev_reward' ],
                    'totalReward' => [ '$sum' => '$block_reward' ],
                    'blockCount'       => [ '$sum' => 1 ]
                    ]
                ]
            ],
            'cursor' => ['batchSize' => 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

  // >>> TX Queries <<<
    function mongo_getTxCount($since = null) {
        if (empty($since)) {
            $query = [];
        } else {
            $query = ['tx_time_sec' => ['$gte' => $since]];
        }
        return mongo_countDbCollectionQuery("TXS", $query);
    }
    function mongo_getTxsByBlockHeight($block_ix, $sort = []){
        if (count($sort) == 0) {
            $sort = ['_id' => 1];
        }
        $query = new MongoDB\Driver\Query(
            ['block_ix' => $block_ix],
            ['sort'     => $sort]
        );
        $cursor = mongo_execDbQuery($query, "TXS");
        return $cursor->toArray();
    }
    function mongo_getTxByHash($tx_id){
        $query = new MongoDB\Driver\Query(
            ['_id' => $tx_id],
            ['sort' => ['block_ix' => -1]]
        );
        $cursor = mongo_execDbQuery($query, "TXS");
        return $cursor->toArray();
    }
    function mongo_getTxCountByBlockIx($block_ix){
        $query = ['block_ix' => $block_ix];
        return mongo_countDbCollectionQuery("TXS", $query);
    }
  // >>> VOUT Queries <<<
    function mongo_getVoutData($vout_id){
        $query = new MongoDB\Driver\Query(
            ['_id' => $vout_id], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "VOUTS");
        return $cursor->toArray();
    }
    function mongo_getVoutByIdMany($ids){
        if (is_array($ids) && count($ids)>0){
            $query = new MongoDB\Driver\Query(
                ['_id' => ['$in' => array_values($ids)]], // query (empty: select all)
                []
            );
            $cursor = mongo_execDbQuery($query, "VOUTS");
            return $cursor->toArray();
        } else {
            return array();
        }
    }
    function mongo_getAirdropByIdMany($ids){
        if (is_array($ids) && count($ids)>0){
            $query = new MongoDB\Driver\Query(
                ['_id' => ['$in' => array_values($ids)]], // query (empty: select all)
                []
            );
            $cursor = mongo_execDbQuery($query, "AIRDROPS");
            return $cursor->toArray();
        } else {
            return array();
        }
    }
    /**
     * Synthetic shadow-ledger (Gold Rush) payout rows anchored to one block.
     */
    function mongo_getShadowVoutsByBlock($block_ix){
        $query = new MongoDB\Driver\Query(
            ['block_ix' => (int)$block_ix, 'synthetic' => true],
            ['sort' => ['_id' => 1]]
        );
        $cursor = mongo_execDbQuery($query, "VOUTS");
        return $cursor->toArray();
    }

    /**
     * Most recent synthetic payouts, newest first.
     */
    function mongo_getShadowRecentPayouts($limit = 25){
        $query = new MongoDB\Driver\Query(
            ['synthetic' => true],
            ['sort' => ['block_ix' => -1], 'limit' => (int)$limit]
        );
        $cursor = mongo_execDbQuery($query, "VOUTS");
        return $cursor->toArray();
    }

    /**
     * Credited totals split by channel, plus payout counts, straight from the indexed vouts.
     */
    function mongo_getShadowTotals(){
        global $dbm;
        global $settings;

        $res = new stdClass();
        $res->credited      = @cdec(0);
        $res->pow_credited  = @cdec(0);
        $res->pos_credited  = @cdec(0);
        $res->payout_count  = 0;
        $res->pow_count     = 0;
        $res->pos_count     = 0;
        $res->unspent       = @cdec(0);

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'VOUTS',
            'pipeline' => [
                ['$match'   => ['synthetic' => true]],
                ['$group'   => [
                    '_id'       => '$shadow_mode',
                    'amount'    => ['$sum' => '$amount'],
                    'count'     => ['$sum' => 1],
                ]],
            ],
            'cursor' => ['batchSize' => 16]
        ]);
        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        foreach ($cursor->toArray() as $r){
            $amount = @cdec($r->amount);
            $res->credited     = $res->credited + $amount;
            $res->payout_count = $res->payout_count + (int)$r->count;
            if ($r->_id === "pow"){
                $res->pow_credited = $amount;
                $res->pow_count    = (int)$r->count;
            } else if ($r->_id === "pos"){
                $res->pos_credited = $amount;
                $res->pos_count    = (int)$r->count;
            }
        }

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'VOUTS',
            'pipeline' => [
                ['$match'   => ['synthetic' => true, 'is_spent' => false]],
                ['$group'   => ['_id' => 1, 'amount' => ['$sum' => '$amount']]],
            ],
            'cursor' => ['batchSize' => 1]
        ]);
        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        $r = $cursor->toArray();
        if (count($r)>0){ $res->unspent = @cdec($r[0]->amount); }

        return $res;
    }

    /**
     * Quantum addresses ranked by total Gold Rush credit received.
     */
    function mongo_getShadowTopRecipients($limit = 10){
        global $dbm;
        global $settings;

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'VOUTS',
            'pipeline' => [
                ['$match'   => ['synthetic' => true]],
                ['$group'   => [
                    '_id'     => '$address',
                    'amount'  => ['$sum' => '$amount'],
                    'count'   => ['$sum' => 1],
                    'mode'    => ['$last' => '$shadow_mode'],
                ]],
                ['$sort'    => ['amount' => -1]],
                ['$limit'   => (int)$limit],
            ],
            'cursor' => ['batchSize' => (int)$limit]
        ]);
        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getAirdropTotals(){
        global $dbm;
        global $settings;

        $res = new stdClass();

        $addr = db_getAddressData('Airdrop');
        if (count($addr)>0){
            $res->claimed = @cdec($addr[0]->sent);
        } else {
            $res->claimed = @cdec(0);
        }
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'AIRDROPS',
            'pipeline' => [
                ['$group'   => [
                    '_id' => 1,
                    'unclaimed' => ['$sum'  => '$amount'],
                ]],
            ],
            'cursor' => ['batchSize' => 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        $r = $cursor->toArray();
        if (count($r)>0){
            $res->unclaimed = @cdec($r[0]->unclaimed);
        } else {
            $res->unclaimed = @cdec(0);
        }

        $res->total = $res->claimed + $res->unclaimed;

        return $res;
    }
    function mongo_getUnclaimedAirdrops(){
        //global $dbm;
        //global $settings;

        $query = new MongoDB\Driver\Query(
            [],
            ['projection' => ['_id' => 0,'address'=>1,'amount'=>1]]
        );
        $cursor = mongo_execDbQuery($query, "AIRDROPS");
        return $cursor->toArray();
    }
    function mongo_getClaimedAirdrops(){
        //global $dbm;
        //global $settings;

        $query = new MongoDB\Driver\Query(
            ["address" => "Airdrop"],
            ['projection' => ['_id' => 0,'staker'=>1,'vins_amount'=>1,'tx_id'=>1]]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSMOVES");
        return $cursor->toArray();
    }
    function mongo_getVoutCountByBlockIx($block_ix){
        $query = ['block_ix' => $block_ix];
        return mongo_countDbCollectionQuery("VOUTS", $query);
    }
  // >>> Address Queries <<<
    function mongo_getAddressCount() {
        $query = [];
        return mongo_countDbCollectionQuery("ADDRESSES", $query);
    }
    function mongo_getAddressData($address){
        $query = new MongoDB\Driver\Query(
            ['_id' => $address], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        return $cursor->toArray();
    }
    function mongo_getAddressDataMany($addresses){
        $query = new MongoDB\Driver\Query(
            ['_id' => ['$in' => $addresses]], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        return $cursor->toArray();
    }
    function mongo_getAddressMdaoBalanceData($address){
        $query = new MongoDB\Driver\Query(
            ['_id' => $address], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "MDAOBALANCES");
        return $cursor->toArray();
    }

    function mongo_getTopAddressesCache(){
        $query = new MongoDB\Driver\Query(
            ['_id' => 'TOP_ADDRESSES'], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "CACHE");
        return $cursor->toArray();
    }

    function mongo_getGoldRushCache(){
        $query = new MongoDB\Driver\Query(
            ['_id' => 'GOLD_RUSH'], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "CACHE");
        return $cursor->toArray();
    }

    function mongo_getAssetCount($filter = []) { // SIP-042 added filter
        $query = $filter;
        return mongo_countDbCollectionQuery("ASSETS", $query);
    }

    function mongo_getAssetData($name){
        $query = new MongoDB\Driver\Query(
            ['_id' => $name], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ASSETS");
        return $cursor->toArray();
    }
    function mongo_getAssets($filter = []){
        $query = new MongoDB\Driver\Query(
            $filter, // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ASSETS");
        return $cursor->toArray();
    }
    function mongo_getAssetByKey($key){
        $query = new MongoDB\Driver\Query(
            ['key' => $key], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ASSETS");
        return $cursor->toArray();
    }
    function mongo_getAssetByName($name, $strict = true){
        ($strict) ? $str = $name : $str = new MongoDB\BSON\Regex('^'.preg_quote(substr((string)$name, 0, 64), '/'), 'i');
        $query = new MongoDB\Driver\Query(
            ['_id' => $str], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ASSETS");
        return $cursor->toArray();
    }
    function mongo_getAssetHoldersByKey($key, $limit = 100, $skip = 0){
        $projection = ['assets.'.$key => 1, 'group_id' => 1, 'alias' => 1];
        $query = new MongoDB\Driver\Query(
            ['assets.'.$key => ['$exists' => 1]], // query (empty: select all)
            ['projection' => $projection,'sort' => ['assets.'.$key.'.value' => -1], 'skip' => $skip, 'limit' => $limit]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        return $cursor->toArray();
    }
    function mongo_getAssetMovementsByName($name, $limit = 100, $skip = 0, $strict = true, $filter = null){
        ($strict) ? $str = $name : $str = new MongoDB\BSON\Regex(preg_quote(substr((string)$name, 0, 64), '/'), 'i');
        $query_filter = ['name' => $str];
        if (!is_null($filter)){
            $query_filter = array_merge($query_filter, $filter);
        }
        $query = new MongoDB\Driver\Query(
            $query_filter, // query (empty: select all)
            mongo_webTimeLimit(['sort' => ['block_ix' => -1, 'amount' => -1], 'skip' => $skip, 'limit' => $limit])
        );
        $cursor = mongo_execDbQuery($query, "ASSETMOVES");
        return $cursor->toArray();
    }
    function mongo_getAssetMovesDataWithFilter($filter = array(), $limit = 100, $skip = 0){
        $query = new MongoDB\Driver\Query(
            $filter, 
            ['sort' => ['block_ix' => -1], 'skip' => $skip, 'limit' => $limit]
        );
        $cursor = mongo_execDbQuery($query, "ASSETMOVES");
        return $cursor->toArray();
    }
    function mongo_getAssetHoldersCountByKey($key){
        $query = ['assets.'.$key => ['$exists' => 1]];
        return mongo_countDbCollectionQuery("ADDRESSES", $query);
    }
    function mongo_getAssetTypeCount(){
        global $dbm;
        global $settings;
        $batchsize = 100;
        // //  db.ASSETS.aggregate([
        // //         {$project: {"type": 1}},
        // //         {$group: {
        // //           "_id": "$type",
        // //           "count": {"$sum": 1},
        // //         }},
        // //       ])
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ASSETS',
            'pipeline' => [
                ['$project'   => [ 'type' => 1]],
                ['$group'   => [
                    '_id' => '$type',
                    'count' => ['$sum'  => 1],
                ]],
                ['$sort'    => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        $res = $cursor->toArray();
        if (count($res) > 0){
            $normalized = [];
            foreach ($res as $r){
                $normalized[$r->_id] = $r->count;
            }
            $res = $normalized;
        }

        return $res;
    }

    function mongo_getAssetMovesCountByName($name){
        $query = ['name' => $name];
        return mongo_countDbCollectionQuery("ASSETMOVES", $query);
    }
    function mongo_getAssetDistributionRanking($from_time, $to_time, $limit = 100, $skip = 0){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ASSETMOVES',
            'pipeline' => [
                ['$match' => [ 'move_time_sec' => ['$gte' => $from_time, '$lte' => $to_time]]],
                ['$group' => 
                    [ 
                        '_id' => '$name',
                        'in_num'    => [ '$sum' => '$vins_count' ],
                        'out_num'   => [ '$sum' => '$vouts_count' ],
                    ]
                ],
                ['$addFields' => 
                    [
                        'k'     => [ '$divide'      => [ '$out_num', '$in_num' ] ],
                        // 'xd'    => [ '$multiply'    => [ [ '$subtract' => ['$out_num', '$in_num' ] ], [ '$divide' => ['$out_num', '$in_num'] ] ] ],
                        'xd'    => [ '$subtract' => ['$out_num', '$in_num' ] ],
                    ]
                ],
                ['$match'   =>
                    [ 
                        'k'  => [ '$gt'  => 1 ] 
                    ],
                ],
                ['$sort'    => 
                    [ 'xd' => -1]
                ],
                ['$skip'    => $skip ],
                ['$limit'    => $limit]
            ],
            'cursor' => ['batchSize' => 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getAssetConsolidationRanking($from_time, $to_time, $limit = 100, $skip = 0){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ASSETMOVES',
            'pipeline' => [
                ['$match' => [ 'move_time_sec' => ['$gte' => $from_time, '$lte' => $to_time]]],
                ['$group' => 
                    [ 
                        '_id' => '$name',
                        'in_num'    => [ '$sum' => '$vins_count' ],
                        'out_num'   => [ '$sum' => '$vouts_count' ],
                    ]
                ],
                ['$addFields' => 
                    [
                        'k'     => [ '$divide'      => [ '$in_num', '$out_num' ] ],
                        //'xc'    => [ '$multiply'    => [ [ '$subtract' => ['$in_num', '$out_num'] ], [ '$divide' => ['$in_num', '$out_num'] ] ] ],
                        'xc'    => [ '$subtract' => ['$in_num', '$out_num'] ],
                    ]
                ],
                ['$match'   =>
                    [ 
                        'k'  => [ '$gt'  => 1 ] 
                    ],
                ],
                ['$sort'    => 
                    [ 'xc' => -1]
                ],
                ['$skip'    => $skip ],
                ['$limit'    => $limit]
            ],
            'cursor' => ['batchSize' => 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getAddressesByGroupId($group_id, $limit = 0, $skip = 0, $filter = array(), $projection = array()){
        $filter['group_id'] = $group_id;
        if ($limit <= 0){$limit=100000;}
        if (count($projection)>0){
            $options = ["projection" => $projection,'sort' => ['balance' => -1], 'skip' => $skip, 'limit' => $limit];
        } else {
            $options = ['sort' => ['balance' => -1], 'skip' => $skip, 'limit' => $limit];
        }
        $query = new MongoDB\Driver\Query(
            $filter, 
            $options
        );
        try {
           $cursor = mongo_execDbQuery($query, "ADDRESSES");
            return $cursor->toArray(); 
        } catch (Exception $e){
            return array();
        }    
    }
    function mongo_getAddressesCountByGroupId($group_id){ // from ADDRESSES
        $query = ['group_id' => $group_id];
        return mongo_countDbCollectionQuery("ADDRESSES", $query);
    }
    function mongo_getAddressesCountByBlockIx($block_ix){
        $query = ['last_block' => $block_ix];
        return mongo_countDbCollectionQuery("ADDRESSES", $query);
    }
    function mongo_getAddressesByLastBlockData($block_ix){
        $query = new MongoDB\Driver\Query(
            ['last_block' => $block_ix], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        return $cursor->toArray();
    }    
    function mongo_getAddressesWithTopBalance($limit = 25, $skip = 0, $withburnaddresses = false, $strictlyabovezero = false){
        global $settings;
        $filter = ["Coinbase", "PoS Coinbase", "Zerocoin"];
        $limiter = '$gte';
        if (!$withburnaddresses){
            if (isset($settings['burn_addresses']) && count($settings['burn_addresses'])>0){
                $filter = array_merge($filter, $settings['burn_addresses']);
            } else {
                array_push($filter, "Fee Collector");
            }
        }
        if ($strictlyabovezero){$limiter = '$gt';}
        $query = new MongoDB\Driver\Query(
            ["balance" => [$limiter => 0], "_id" => [ '$nin' => $filter]], // query (empty: select all)
            ["sort" => ["balance" => -1], "limit" => $limit, "skip" => $skip]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        return $cursor->toArray();
    }

  // >>> AddressGroup Queries <<<    
    function mongo_getAddressGroupAggregates($group_id){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ADDRESSES',
            'pipeline' => [
                ['$match' => [ 'group_id' => $group_id]],
                ['$group' => 
                    [ '_id' => $group_id,
                    'totalBalance' => [ '$sum' => '$balance' ],
                    'countAddresses'       => [ '$sum' => 1 ]
                    ]
                ],
            ],
            'cursor' => ['batchSize' => 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getAddressGroupAggregatesMulti($group_ids){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ADDRESSES',
            'pipeline' => [
                ['$match' => [ 'group_id' => ['$in' => $group_ids]]],
                ['$group' => 
                    [ '_id' => '$group_id',
                    'totalBalance' => [ '$sum' => '$balance' ],
                    'countAddresses'       => [ '$sum' => 1 ]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => count($group_ids)]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getTopAddressGroups($limit = 1, $skip = 0,$strictlyabovezero = false){
        global $dbm;
        global $settings;
        $limiter = '$gte';
        if ($strictlyabovezero){$limiter = '$gt';}
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ADDRESSES',
            'pipeline' => [
                ['$match' => ['group_id' => ['$ne' => NULL ], 'balance' => [$limiter => 0]]],
                ['$group' => 
                    [ 
                    '_id' => '$group_id',
                    'totalBalance' => [ '$sum' => '$balance' ],
                    'countAddresses'       => [ '$sum' => 1 ]
                    ]
                ],
                ['$sort' => ['totalBalance' => -1, 'countAddresses' => -1]],['$skip' => $skip],['$limit' => $limit]
            ],
            'cursor' => ['batchSize' => $limit]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getTopAddressGroupsByBalance($limit = 1, $skip = 0, $withburnaddresses = false,$strictlyabovezero = false){ // from ADDRESSGROUPS
        global $settings;
        $limiter = '$gte';
        if ($strictlyabovezero){$limiter = '$gt';}
        if ($withburnaddresses){
            $filter = array();
        } else {
            if (isset($settings['burn_addresses']) && count($settings['burn_addresses'])>0){
                $burn_groups = array();
                foreach ($settings['burn_addresses'] as $ba){
                    $bad = mongo_getAddressData($ba);
                    if (count($bad)>0){
                        array_push($burn_groups, $bad[0]->group_id);
                    }
                } 
                $filter = ['balance' => [$limiter => 0], '_id' => ['$nin' => $burn_groups]];
            } else {
                $filter = array();
            }
        }
        $query = new MongoDB\Driver\Query(
            $filter, // query (empty: select all)
            ['sort' => ['balance' => -1, 'address_count' => -1],'skip' => $skip,'limit' => $limit]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSGROUPS");
        return $cursor->toArray();

    }
    function mongo_getAddressGroupById($group_id){ // from ADDRESSGROUPS
        $query = new MongoDB\Driver\Query(
            ['_id' => $group_id], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSGROUPS");
        return $cursor->toArray();
    }
    function mongo_getAddressGroupMany($group_ids){ // from ADDRESSGROUPS
        $query = new MongoDB\Driver\Query(
            ['_id' => [ '$in' => $group_ids]], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSGROUPS");
        return $cursor->toArray();
    }
    function mongo_getAddressGroupTags($group_id){ // from ADDRESSGROUPS
        $query = new MongoDB\Driver\Query(
            ['_id' => $group_id], // query (empty: select all)
            ['projection' => ['tags' => 1]]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSGROUPS");
        $res = $cursor->toArray();
        if (count($res)>0 && isset($res[0]->tags)) { 
            return $res[0]; 
        } else { 
            return array();
        }
    }
    function mongo_getAddressTags($address){ // from ADDRESSGROUPS and ADDRESSES
        $res = new stdClass();
        $res->has_tags = false;
        $res->has_alias = false;
        $res->address_alias = "";
        $res->group_tags = array();
        $res->group_id = "";

        // get address
        $query = new MongoDB\Driver\Query(
            ['_id' => $address], // query (empty: select all)
            ['projection' => ['group_id' => 1, 'alias' => 1]]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        $result = $cursor->toArray();
        if (count($result)>0 ){
            if (isset($result[0]->alias) && !empty($result[0]->alias)) { 
            $res->address_alias = $result[0]->alias;
            $res->has_alias = true;
            }
            $res->group_id = $result[0]->group_id;
        } else {
            return $res;
        }

        $query = new MongoDB\Driver\Query(
            ['_id' => $res->group_id], 
            ['projection' => ['tags' => 1]]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSGROUPS");
        $result = $cursor->toArray();
        if (count($result)>0 && isset($result[0]->tags) && count($result[0]->tags)>0) { 
            $res->group_tags = $result[0]->tags;
            $res->has_tags = true;
        }
        return $res;
    }
    // --- Address labels (admin Labels page) ---
    // The list queries repeat the partial filter of the labels indexes, so the planner can use them.

    function mongo_getLabelAddress(string $address){
        $q = new MongoDB\Driver\Query(['_id' => $address], ['projection' => ['balance' => 1, 'group_id' => 1, 'alias' => 1]]);
        return mongo_execDbQuery($q, "ADDRESSES")->toArray()[0] ?? null;
    }
    function mongo_getLabelGroup(string $group_id){
        $q = new MongoDB\Driver\Query(['_id' => $group_id], ['projection' => ['balance' => 1, 'address_count' => 1, 'tags' => 1]]);
        return mongo_execDbQuery($q, "ADDRESSGROUPS")->toArray()[0] ?? null;
    }
    function mongo_setAddressAlias(string $address, string $alias) : bool {
        global $dbm, $settings;
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(['_id' => $address], $alias === '' ? ['$unset' => ['alias' => '']] : ['$set' => ['alias' => $alias]]);
        return $dbm->executeBulkWrite($settings['db_name'].".ADDRESSES", $bulk)->getMatchedCount() === 1;
    }
    // An empty list removes the field, so the group drops out of the partial tags index.
    function mongo_setGroupTags(string $group_id, array $tags) : bool {
        global $dbm, $settings;
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(['_id' => $group_id], count($tags) === 0 ? ['$unset' => ['tags' => '']] : ['$set' => ['tags' => array_values($tags)]]);
        return $dbm->executeBulkWrite($settings['db_name'].".ADDRESSGROUPS", $bulk)->getMatchedCount() === 1;
    }
    // Which of the two labels indexes exist: ['alias' => bool, 'tags' => bool]
    function mongo_getLabelIndexes() : array {
        global $dbm, $settings;
        $has = function (string $col, string $field) use ($dbm, $settings) : bool {
            foreach ($dbm->executeCommand($settings['db_name'], new MongoDB\Driver\Command(['listIndexes' => $col])) as $ix) {
                $keys = array_keys((array)$ix->key);
                if ($keys === [$field] && isset($ix->partialFilterExpression)) { return true; }
            }
            return false;
        };
        return ['alias' => $has('ADDRESSES', 'alias'), 'tags' => $has('ADDRESSGROUPS', 'tags')];
    }
    function mongo_getLabelAliases(int $limit){
        $q = new MongoDB\Driver\Query(['alias' => ['$type' => 'string']], mongo_webTimeLimit([
            'projection' => ['alias' => 1, 'balance' => 1], 'sort' => ['alias' => 1], 'limit' => $limit]));
        return mongo_execDbQuery($q, "ADDRESSES")->toArray();
    }
    function mongo_getLabelGroups(int $limit){
        $q = new MongoDB\Driver\Query(['tags' => ['$exists' => true]], mongo_webTimeLimit([
            'projection' => ['tags' => 1, 'address_count' => 1, 'balance' => 1], 'limit' => $limit]));
        return mongo_execDbQuery($q, "ADDRESSGROUPS")->toArray();
    }

    function mongo_getAddressGroupByAddress($address){ // from ADDRESSGROUPS
        $query = new MongoDB\Driver\Query(
            ['_id' => $address], // query (empty: select all)
            ['projection' => ['group_id' => 1]]
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSES");
        $addr = $cursor->toArray();
        if (count($addr)>0){
            $group_id = $addr[0]->group_id;
            $query = new MongoDB\Driver\Query(
                ['_id' => $group_id], // query (empty: select all)
                []
            );
            $cursor = mongo_execDbQuery($query, "ADDRESSGROUPS");
            return $cursor->toArray();  
        } else {
            return null;
        }
        
    }
    function mongo_getAddressGroupCount() {
        $query = [];
        return mongo_countDbCollectionQuery("ADDRESSGROUPS", $query);
    }
    function mongo_getTopAddressGroupsCache(){
        $query = new MongoDB\Driver\Query(
            ['_id' => 'TOP_GROUPS'], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "CACHE");
        return $cursor->toArray();
    }
  // >>> AddressMove Queries <<<    
    function mongo_getAddressMovesDataByAddress($address, $limit = 100, $skip = 0){
        $query = new MongoDB\Driver\Query(
            ['address' => $address], // query (empty: select all)
            mongo_webTimeLimit(['sort' => ['block_ix' => -1], 'skip' => $skip, 'limit' => $limit,'hint'=>'address_block_nu']) // SIP-035 added hint
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSMOVES");
        return $cursor->toArray();
    }
    function mongo_getAddressMovesDataWithFilter($filter = array(), $limit = 100, $skip = 0, $sort = array()){
        if (count($sort) == 0) {$sort = ['block_ix' => -1];}
        $query = new MongoDB\Driver\Query(
            $filter, 
            mongo_webTimeLimit(['sort' => $sort, 'skip' => $skip, 'limit' => $limit])
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSMOVES");
        return $cursor->toArray();
    }
    function mongo_getAddressMovesCountByAddress($address){
        $query = ['address' => $address];
        return mongo_countDbCollectionQuery("ADDRESSMOVES", $query);
    }
    function mongo_getAddressMovesCountWithFilter($filter = array()){
        $query = $filter;
        return mongo_countDbCollectionQuery("ADDRESSMOVES", $query);
    }
    function mongo_getAddressMovesCountByBlockIx($block_ix){
        $query = ['block_ix' => $block_ix];
        return mongo_countDbCollectionQuery("ADDRESSMOVES", $query);
    }

    function mongo_getTopAddressMovesByTimeRange($start_time, $end_time, $limit = 10, $sort_asc = false, $gte_start = false, $lte_end = true, $hide_rewards = false, $from_zero = false){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        if ($hide_rewards){
            $filter = ['move_time_sec' => [$scond => $start_time, $econd => $end_time],'is_reward' => false];
        } else {
            $filter = ['move_time_sec' => [$scond => $start_time, $econd => $end_time]];
        }
        if ($from_zero){
            ($sortorder > 0) ? $filter["amount"] = ['$lt' => 0] : $filter["amount"] = ['$gt' => 0] ;
        }
        $query = new MongoDB\Driver\Query(
            $filter,
            [
                'sort' => ['amount' => $sortorder],
                'limit' => $limit, 
                'hint' => ["move_time_sec"=>1, "amount"=>$sortorder]
            ] //
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSMOVES");
        return $cursor->toArray();
    }
    function mongo_getTopAddressMovesByBlockRange($start_block, $end_block, $limit = 10, $sort_asc = false, $gte_start = false, $lte_end = true, $hide_rewards = false, $from_zero = false){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        if ($hide_rewards){
            $filter = ['block_ix' => [$scond => $start_block, $econd => $end_block],'is_reward' => false];
        } else {
            $filter = ['block_ix' => [$scond => $start_block, $econd => $end_block]];
        }
        if ($from_zero){
            ($sortorder > 0) ? $filter["amount"] = ['$lt' => 0] : $filter["amount"] = ['$gt' => 0] ;
        }
        $query = new MongoDB\Driver\Query(
            $filter,
            ['sort' => ['amount' => $sortorder],'limit' => $limit] //
        );
        $cursor = mongo_execDbQuery($query, "ADDRESSMOVES");
        return $cursor->toArray();
    }
    function mongo_getAddressMovesHourlyAggregates($end_time, $hours){
        global $dbm;
        global $settings;
        $start_time = $end_time - $hours*3600 - ($end_time % 3600);

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ADDRESSMOVES',
            'pipeline' => [
                ['$match' => [ 'amount' => [ '$gt' => 0 ], 'move_time_sec' => [ '$gte' => $start_time ]]],
                ['$group' => 
                    [ '_id' => 
                        [ '$dateFromParts' => 
                            [
                            'year'  => [ '$year'       => [ '$toDate' => [ '$multiply' => [ '$move_time_sec',1000 ] ] ] ] ,
                            'month' => [ '$month'      => [ '$toDate' => [ '$multiply' => [ '$move_time_sec',1000 ] ] ] ] ,
                            'day'   => [ '$dayOfMonth' => [ '$toDate' => [ '$multiply' => [ '$move_time_sec',1000 ] ] ] ] ,
                            'hour'  => [ '$hour'       => [ '$toDate' => [ '$multiply' => [ '$move_time_sec',1000 ] ] ] ] ,
                            //'minute'  => 0,
                            //'second'  => 0,
                            //'milliseconds' => 0,
                            //'timezone' => $settings["timezone"]
                            ] 
                        ],
                    'totalAmount' => [ '$sum' => '$amount' ],
                    'avgAmount'   => [ '$avg' => '$amount' ],
                    'count'       => [ '$sum' => 1 ]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $hours + 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getAddressMovesAggregatesByGranularity($address, $from_time, $to_time, $granularity_sec = 3600, $including_to_time = true){
        global $dbm;
        global $settings;
        $batchsize = floor((($to_time - $from_time) / $granularity_sec) + 2);
        if ($including_to_time){ // SIP-032
            $to_rule = '$lte';
        } else {
            $to_rule = '$lt';
        }
        // get starting balance ( "move_time_sec" => ["$lt" => $from_time] ) ?? or do reverse calculation using current balance and substracting Sum of amounts


        //$batchsize = 500;
        // db.ADDRESSMOVES.aggregate([
            //     {$match: {"address": '<address>', 'move_time_sec': {$gte: 1539216000, $lte: 1542499200}}},
            //     {$group: {
            //       "_id": {'$subtract': ['$move_time_sec', {'$mod': ['$move_time_sec', 86400]}]},
            //       "movementsTotal": {$sum: "$amount"},
            //       "receivedTotal": {$sum: "$vouts_amount"},
            //       "sentTotal": {$sum: "$vins_amount"},
            //     }},
            //     {"$sort": {"_id": 1}},
            //   ])
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ADDRESSMOVES',
            'pipeline' => [
                ['$match'   => [ 'address' => $address, 'move_time_sec' => [ '$gte' => $from_time, $to_rule => $to_time ]]],
                ['$group'   => [
                    '_id' => [
                        '$subtract'=> ['$move_time_sec', ['$mod' => ['$move_time_sec', $granularity_sec]]],
                    ],
                    'movementsTotal' => ['$sum'  => '$amount'],
                    'receivedTotal' => ['$sum'  => '$vouts_amount'],
                    'sentTotal' => ['$sum'  => '$vins_amount'],
                ]],
                ['$sort'    => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getAddressMovesInOutTotalsByTimeRange($address, $start_time, $end_time){
        global $dbm;
        global $settings;
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'ADDRESSMOVES',
            'pipeline' => [
                ['$match' => [
                    'address' => $address,
                    'move_time_sec' => [ '$gte' => $start_time, '$lte' => $end_time ],
                ]],
                ['$group' => [
                    '_id' => null,
                    'in' => [
                        '$sum' => [
                            '$cond' => [
                                [ '$gt' => [ '$amount', 0 ] ],
                                '$amount',
                                0
                            ]
                        ]
                    ],
                    'out' => [
                        '$sum' => [
                            '$cond' => [
                                [ '$lt' => [ '$amount', 0 ] ],
                                '$amount',
                                0
                            ]
                        ]
                    ],
                ]],
            ],
            'cursor' => ['batchSize' => 1]
        ]);
        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        $res = $cursor->toArray();
        if (count($res) == 0){
            return (object)[
                'in' => @cdec(0),
                'out' => @cdec(0),
            ];
        }
        return (object)[
            'in' => @cdec($res[0]->in ?? 0),
            'out' => @cdec($res[0]->out ?? 0),
        ];
    }
  // >>> Log Queries <<<    
    function mongo_getProblemLogCountByBlockIx($block_ix){
        $query = ['block_ix' => $block_ix];
        return mongo_countDbCollectionQuery("PROBLEMLOG", $query);
    }
    /** Newest PROBLEMLOG entry written by ldr_abort(), or null. */
    function mongo_getLastLoaderStop(){
        $query = new MongoDB\Driver\Query(['loader_stop' => true], ['sort' => ['detected_at' => -1], 'limit' => 1]);
        $res = mongo_execDbQuery($query, "PROBLEMLOG")->toArray();
        return count($res) > 0 ? $res[0] : null;
    }
    /** Unix time of the newest LOADERLOG entry for a loaded block, or null. */
    function mongo_getLastBlockLoadTime(){
        $query = new MongoDB\Driver\Query(['operation' => 'load'],
            ['sort' => ['_id' => -1], 'limit' => 1, 'projection' => ['timestamp' => 1]]);
        $res = mongo_execDbQuery($query, "LOADERLOG")->toArray();
        if (count($res) == 0 || !($res[0]->timestamp instanceof MongoDB\BSON\UTCDateTime)){ return null; }
        return $res[0]->timestamp->toDateTime()->getTimestamp();
    }
    function mongo_getLoaderLogCount(){
        return mongo_countDbCollection("LOADERLOG");
    }
    function mongo_getLoaderLogList($limit = 100, $skip = 0){
        $query = new MongoDB\Driver\Query(
            [], 
            [
                'projection' => ['timers' => 0],
                'sort' => ['_id' => -1], 
                'skip' => $skip, 
                'limit' => $limit
            ] 
        );
        $cursor = mongo_execDbQuery($query, "LOADERLOG");
        return $cursor->toArray();
    }
    function mongo_getLoaderLogTimers($oid){
        $query = new MongoDB\Driver\Query(
            ['_id' => new MongoDB\BSON\ObjectId($oid)], 
            [
                'projection' => ['timers' => 1],
            ] 
        );
        $cursor = mongo_execDbQuery($query, "LOADERLOG");
        return $cursor->toArray();
    }
  // >>> RollbackData Queries <<<  
    function mongo_getRollbackDataCount() {
        $query = [];
        return mongo_countDbCollectionQuery("BLOCKROLLBACKS", $query);
    }  
    function mongo_countAutoRollbacksForVout($vout_id){
        $n = mongo_countDbCollectionQuery("PROBLEMLOG", ['detected_by' => 'blockLoader', 'context.missing_vouts.vout' => (string)$vout_id]);
        return max(0, $n);
    }
    function mongo_countRollbackRange($from, $to){
        $n = mongo_countDbCollectionQuery("BLOCKROLLBACKS", ['_id' => ['$gte' => (int)$from, '$lte' => (int)$to]]);
        return max(0, $n);
    }
    function mongo_getRollbackData($block_ix){
        $query = new MongoDB\Driver\Query(
            ['_id' => $block_ix], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "BLOCKROLLBACKS");
        return $cursor->toArray();
    }
  // >>> Host Metric Queries <<<
    function mongo_getLastHostMetric(){
        $query = new MongoDB\Driver\Query(
            [], // query (empty: select all)
            ['sort' => ['_id' => -1], 'limit' => 1]
        );
        $cursor = mongo_execDbQuery($query, "HOSTMETRICS");
        return $cursor->toArray();
    }
    function mongo_getHostMetricsByTimeRange($start_time,$end_time,$gte_start = false, $lte_end = true, $sort_asc = true){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        $query = new MongoDB\Driver\Query(
            ['_id' => [$scond => $start_time, $econd => $end_time]], // query (empty: select all)
            ['sort' => ['_id' => $sortorder]]
        );
        $cursor = mongo_execDbQuery($query, "HOSTMETRICS");
        return $cursor->toArray();

    }
  // >>> Markets Queries <<<    
    function mongo_getLastMarketsMetric(){
        $query = new MongoDB\Driver\Query(
            [], // query (empty: select all)
            ['sort' => ['_id' => -1], 'limit' => 1]
        );
        $cursor = mongo_execDbQuery($query, "MARKETS");
        return $cursor->toArray();
    }
  // >>> Masternode Cache Queries <<<   
    function mongo_getMasternodeCache($start_time){
        $query = new MongoDB\Driver\Query(
            ['timestamp' => ['$gte' => new MongoDB\BSON\UTCDateTime($start_time * 1000)] ], // query (empty: select all)
            ['sort' => ['timestamp' => -1], 'limit' => 1]
        );
        $cursor = mongo_execDbQuery($query, "MASTERNODES");
        return $cursor->toArray();
    }
  // >>> Chain Metrics Queries <<<
    function mongo_getLastChainMetric(){
        $query = new MongoDB\Driver\Query(
            [], // query (empty: select all)
            ['sort' => ['_id' => -1], 'limit' => 1]
        );
        $cursor = mongo_execDbQuery($query, "CHAINMETRICS");
        return $cursor->toArray();
    }
    function mongo_getChainMetricsByTimeRange($start_time,$end_time,$gte_start = false, $lte_end = true, $sort_asc = true){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        $query = new MongoDB\Driver\Query(
            ['_id' => [$scond => $start_time, $econd => $end_time]], // query (empty: select all)
            ['sort' => ['_id' => $sortorder]]
        );
        $cursor = mongo_execDbQuery($query, "CHAINMETRICS");
        return $cursor->toArray();

    }
    function mongo_getChainMetricsHourlyAggregates($end_time, $hours){
        global $dbm;
        global $settings;
        $start_time = $end_time - $hours*3600 - ($end_time % 3600);

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'CHAINMETRICS',
            'pipeline' => [
                ['$match' => [ '_id' => [ '$gte' => $start_time ]]],
                ['$group' => 
                    [ '_id' => 
                        [ '$dateFromParts' => 
                            [
                            'year'  => [ '$year'       => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            'month' => [ '$month'      => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            'day'   => [ '$dayOfMonth' => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            'hour'  => [ '$hour'       => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            //'minute'  => 0,
                            //'second'  => 0,
                            //'milliseconds' => 0,
                            //'timezone' => $settings["timezone"]
                            ] 
                        ],
                        'measurementsCount'       => [ '$sum' => 1 ],
                        'avgHashrate' => [ '$avg' => '$metrics.hashrate' ],
                        'avgDifficulty' => [ '$avg' => '$metrics.difficulty' ],    
                        'avgMasternodeCount' => [ '$avg' => '$metrics.masternode_count.total' ],
                        'avgMasternodeCountEnabled'   => [ '$avg' => '$metrics.masternode_count.enabled' ]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $hours + 1]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getMarketMetricsByTimeRange($start_time,$end_time,$gte_start = false, $lte_end = true, $sort_asc = true){
        if ($gte_start) {
            $scond = '$gte';
        } else {
            $scond = '$gt';
        }
        if ($lte_end) {
            $econd = '$lte';
        } else {
            $econd = '$lt';
        }
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        $query = new MongoDB\Driver\Query(
            ['_id' => [$scond => $start_time, $econd => $end_time]], // query (empty: select all)
            ['sort' => ['_id' => $sortorder]]
        );
        $cursor = mongo_execDbQuery($query, "MARKETS");
        return $cursor->toArray();

    }
    function mongo_getMarketAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((10 * ($to_time - $from_time) / $granularity_sec) + 2);
        // db.MARKETS.aggregate([
            //     {$match: {"_id": {$gte: 1520640000, $lte: 1521072000}}},
            //     {$group: {
            //       "_id": {
            //         "ts": {'$subtract': ['$_id', {'$mod': ['$_id', 86400]}]}
            //       },
            //       "usd": {$avg: "$base_prices.USD"},
            //       "vwap": {$avg: "$vwap"}
            //     }},
            //   ])
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'MARKETS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$group'   => [
                    '_id' => [
                        '$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]],
                    ],
                    'usd' => ['$avg'  => '$base_prices.USD'],
                    'vwap' => ['$avg'  => '$vwap']
                ]],
                ['$addFields' => [
                    'vwap_usd' => ['$multiply' => ['$usd', '$vwap']],
                ]],
                ['$sort'    => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getChainMetricsAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((1 * ($to_time - $from_time) / $granularity_sec) + 2);

        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'CHAINMETRICS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$group'   => [
                    '_id' => [
                        '$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]],
                    ],
                    'periods'       => [ '$sum' => 1 ],
                    'avgHashrate' => [ '$avg' => '$metrics.hashrate' ],
                    'avgDifficulty' => [ '$avg' => '$metrics.difficulty' ],    
                    'avgMasternodeCount' => [ '$avg' => '$metrics.masternode_count.total' ],
                    'avgMasternodeCountEnabled'   => [ '$avg' => '$metrics.masternode_count.enabled' ]
                ]],
                ['$sort' => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getMiningCache(){
        $query = new MongoDB\Driver\Query(
            ['_id' => 'MINING'], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "CACHE");
        return $cursor->toArray();
    }

    function mongo_getOverviewCache(){
        $query = new MongoDB\Driver\Query(
            ['_id' => 'OVERVIEW'], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "CACHE");
        return $cursor->toArray();
    }
  // >>> Block Stats Queries <<<
    function mongo_getBlockStats($timestamp){
        $query = new MongoDB\Driver\Query(
            ['_id' => $timestamp ], // query (empty: select all)
            []
        );
        $cursor = mongo_execDbQuery($query, "BLOCKSTATS");
        return $cursor->toArray();
    }
    function mongo_getLastBlockStats(){
        $query = new MongoDB\Driver\Query(
            [],
            ['sort' => ['_id' => -1], 'limit' => 1]
        );

        $cursor = mongo_execDbQuery($query, "BLOCKSTATS");
        return $cursor->toArray();
    }
    function mongo_getBlockRewardAggregatesDaily($from_time, $to_time){
        global $dbm;
        global $settings;

        $batchsize = floor((($to_time - $from_time) / 86400) + 2);
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match' => [ '_id' => [ '$gte' => $from_time, '$lte' => $to_time ]]],
                ['$group' => 
                    [ '_id' => 
                        [ '$dateFromParts' => 
                            [
                            'year'  => [ '$year'       => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            'month' => [ '$month'      => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            'day'   => [ '$dayOfMonth' => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            //'hour'  => [ '$hour'       => [ '$toDate' => [ '$multiply' => [ '$_id',1000 ] ] ] ] ,
                            //'minute'  => 0,
                            //'second'  => 0,
                            //'milliseconds' => 0,
                            //'timezone' => $settings["timezone"]
                            ] 
                        ],
                    'blockRewardsTotal' => [ '$sum' => '$blockRewardsTotal' ],
                    'coinsupply'        => [ '$max' => '$coinsupply' ],
                    'coincirculation'        => [ '$max' => '$coincirculation' ],
                    'blocksNumber'      => [ '$sum' => '$blocksNumber' ]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getBlockStatsRange($start_time, $end_time, $sort_asc = true, $limit = -1){
        if ($sort_asc) {
            $sortorder = 1;
        } else {
            $sortorder = -1;
        }
        if ($limit > 0){
            $options = ['sort' => ['_id' => $sortorder], 'limit' => $limit];
        } else {
            $options = ['sort' => ['_id' => $sortorder]];
        }
        $query = new MongoDB\Driver\Query(
            ['_id' => ['$gte' => $start_time, '$lte' => $end_time]], // query (empty: select all)
            $options
        );

        $cursor = mongo_execDbQuery($query, "BLOCKSTATS");
        return $cursor->toArray();
    }

    function mongo_getTxTypesAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((10 * ($to_time - $from_time) / $granularity_sec) + 2);
        // db.BLOCKSTATS.aggregate([
            //     {$match: {"_id": {$gte: 1520640000, $lte: 1521072000}}},
            //     {$project: {"_id":1, "txType": { $objectToArray: "$txTypes" } } },
            //     {$unwind: "$txType"},
            //     { 
            //       $group: { 
            //         "_id": { 
            //           //$concat: [
            //           //    { $toString: { $subtract: [ "$_id", { $mod: [ "$_id", 86400 ] } ] } },
            //           //    "@",
            //           //    "$txType.k" 
            //           //],
            //           "ts": "$_id",
            //           "t": "$txType.k"
            //         },
            //         n: { $sum: "$txType.v" },
            //       } 
            //     },
            //     { $sort: {"_id.ts": -1}},
            //   ])
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$project' => ['txType' => ['$objectToArray'=>'$txTypes'] ]],
                ['$unwind'  => '$txType'],
                ['$group'   => [
                    '_id' => [
                        'ts'    => ['$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]]],
                        't'     => '$txType.k'
                    ],
                    'n' => ['$sum'  => '$txType.v']
                ]],
                ['$sort'    => ['_id.ts' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getTxSizesAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((10 * ($to_time - $from_time) / $granularity_sec) + 2);
        // db.BLOCKSTATS.aggregate([
            //     {$match: {"_id": {$gte: 1520640000, $lte: 1521072000}}},
            //     {$project: {"_id":1, "txType": { $objectToArray: "$txTypes" } } },
            //     {$unwind: "$txType"},
            //     { 
            //       $group: { 
            //         "_id": { 
            //           //$concat: [
            //           //    { $toString: { $subtract: [ "$_id", { $mod: [ "$_id", 86400 ] } ] } },
            //           //    "@",
            //           //    "$txType.k" 
            //           //],
            //           "ts": "$_id",
            //           "t": "$txType.k"
            //         },
            //         n: { $sum: "$txType.v" },
            //       } 
            //     },
            //     { $sort: {"_id.ts": -1}},
            //   ])
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$project' => ['txType' => ['$objectToArray'=>'$txSizes'] ]],
                ['$unwind'  => '$txType'],
                ['$group'   => [
                    '_id' => [
                        'ts'    => ['$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]]],
                        't'     => '$txType.k'
                    ],
                    'n' => ['$sum'  => '$txType.v']
                ]],
                ['$sort'    => ['_id.ts' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
    function mongo_getAssetTypesAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((10 * ($to_time - $from_time) / $granularity_sec) + 2);
        // db.BLOCKSTATS.aggregate([
            //     {$match: {"_id": {$gte: 1520640000, $lte: 1521072000}}},
            //     {$project: {"_id":1, "txType": { $objectToArray: "$txTypes" } } },
            //     {$unwind: "$txType"},
            //     { 
            //       $group: { 
            //         "_id": { 
            //           //$concat: [
            //           //    { $toString: { $subtract: [ "$_id", { $mod: [ "$_id", 86400 ] } ] } },
            //           //    "@",
            //           //    "$txType.k" 
            //           //],
            //           "ts": "$_id",
            //           "t": "$txType.k"
            //         },
            //         n: { $sum: "$txType.v" },
            //       } 
            //     },
            //     { $sort: {"_id.ts": -1}},
            //   ])
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$project' => ['assetType' => ['$objectToArray'=>'$assetTypes'] ]],
                ['$unwind'  => '$assetType'],
                ['$group'   => [
                    '_id' => [
                        'ts'    => ['$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]]],
                        't'     => '$assetType.k'
                    ],
                    'n' => ['$max'  => '$assetType.v']
                ]],
                ['$sort'    => ['_id.ts' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getVoutTypesAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((12 * ($to_time - $from_time) / $granularity_sec) + 2);
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$project' => ['voutType' => ['$objectToArray'=>'$voutTypes'] ]],
                ['$unwind'  => '$voutType'],
                ['$group'   => [
                    '_id' => [
                        'ts'    => ['$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]]],
                        't'     => '$voutType.k'
                    ],
                    'n' => ['$sum'  => '$voutType.v']
                ]],
                ['$sort'    => ['_id.ts' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getBlockVersionsAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((10 * ($to_time - $from_time) / $granularity_sec) + 2);
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$project' => ['blkVer' => ['$objectToArray'=>'$blockVersions'] ]],
                ['$unwind'  => '$blkVer'],
                ['$group'   => [
                    '_id' => [
                        'ts'    => ['$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]]],
                        'v'     => '$blkVer.k'
                    ],
                    'n' => ['$sum'  => '$blkVer.v']
                ]],
                ['$sort'    => ['_id.ts' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getBlockTypesAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        global $dbm;
        global $settings;
        $batchsize = floor((10 * ($to_time - $from_time) / $granularity_sec) + 2);
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match'   => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$project' => ['blkType' => ['$objectToArray'=>'$blockTypes'] ]],
                ['$unwind'  => '$blkType'],
                ['$group'   => [
                    '_id' => [
                        'ts'    => ['$subtract'=> ['$_id', ['$mod' => ['$_id', $granularity_sec]]]],
                        'v'     => '$blkType.k'
                    ],
                    'n' => ['$sum'  => '$blkType.v']
                ]],
                ['$sort'    => ['_id.ts' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }

    function mongo_getBlockStatsAggregatesByGranularity($from_time, $to_time, $granularity_sec = 3600){
        /// https://docs.mongodb.com/manual/reference/operator/aggregation/objectToArray/
        global $dbm;
        global $settings;

        $batchsize = floor((($to_time - $from_time) / $granularity_sec) + 2);
        $dbc = new MongoDB\Driver\Command([
            'aggregate' => 'BLOCKSTATS',
            'pipeline' => [
                ['$match' => [ '_id' => [ '$gte' => $from_time, '$lt' => $to_time ]]],
                ['$group' => 
                    [ 
                        '_id'               => [ '$subtract' => [ '$_id', ['$mod' => ['$_id', $granularity_sec] ] ] ],
                        'addressCount'      => [ '$max' => '$addressCount' ],
                        'assetCount'        => [ '$max' => '$assetCount' ],
                        'blockRewards'      => [ '$sum' => '$blockRewardsTotal' ],
                        'blocksNumber'      => [ '$sum' => '$blocksNumber' ],
                        'blocksSizeTotal'   => [ '$sum' => '$blocksSizeTotal' ],
                        'burned'            => [ '$last' => '$burned' ],
                        'cdd'               => [ '$sum' => '$cdd' ],
                        'coincirculation'   => [ '$last' => '$coincirculation' ],
                        'coinsupply'        => [ '$last' => '$coinsupply' ],
                        'groupCount'        => [ '$last' => '$groupCount' ],
                        'movementsTotal'    => [ '$sum' => '$movementsTotal' ],
                        'txCount'           => [ '$max' => '$txCount' ],
                        'txNumber'          => [ '$sum' => '$txNumber' ],
                        'devRewardsTotal'   => [ '$sum' => '$devRewardsTotal' ],
                        'mnRewardsTotal'    => [ '$sum' => '$mnRewardsTotal' ],
                        'powRewardsTotal'   => [ '$sum' => '$powRewardsTotal' ],
                        'stakeBaseTotal'    => [ '$sum' => '$stakeBaseTotal' ],
                        'stakeRewardsTotal' => [ '$sum' => '$stakeRewardsTotal' ],
                        // shadow ledger (Gold Rush) issuance
                        'shadowRewardsTotal'=> [ '$sum' => '$shadowRewardsTotal' ],
                        'shadowPowTotal'    => [ '$sum' => '$shadowPowTotal' ],
                        'shadowPosTotal'    => [ '$sum' => '$shadowPosTotal' ],
                        'periods'           => [ '$sum' => 1 ],
                        // SIP-041
                        'feeTotal'          => [ '$sum' => '$feeTotal' ],
                        'feeSubjectBytes'   => [ '$sum' => '$feeSubjectBytes' ],
                        // eof SIP-041
                        /* 
                            "zerocoinsupply" : 0
                        */

                    ]
                ],
                ['$addFields' => 
                    [
                        'movementsRegularTotal' => ['$subtract' => ['$movementsTotal', '$blockRewards']]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ],
            'cursor' => ['batchSize' => $batchsize]
        ]);

        $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
        return $cursor->toArray();
    }
  // >>> Asset Queries <<<
    function mongo_getAssetMovesCount($filter = []) {
        $query = $filter;
        return mongo_countDbCollectionQuery("ASSETMOVES", $query);
    }  
    function mongo_getLastCreatedAssets($limit = 100, $skip = 0, $filter = null){  // SIP-042
        if (is_null($filter) || !is_array($filter)){$filter = array();}
        $query = new MongoDB\Driver\Query(
            $filter,
            ['sort' => ['genesis_timestamp' => -1], 'skip' => $skip, 'limit' => $limit] 
        );
        $cursor = mongo_execDbQuery($query, "ASSETS");
        return $cursor->toArray();
    }



  // ChainStats
    function mongo_getLastChainStats(){ 
        $query = new MongoDB\Driver\Query(
            [], // query (empty: select all)
            ['sort' => [ '_id' => -1 ], 'limit' => 1]
        );

        $cursor = mongo_execDbQuery($query, "CHAINSTATS");
        return $cursor->toArray();
        //return $res;
    }
    function mongo_getChainStats($timestamp){ 
        $query = new MongoDB\Driver\Query(
            ['_id' => $timestamp], // query (empty: select all)
            []
        );

        $cursor = mongo_execDbQuery($query, "CHAINSTATS");
        return $cursor->toArray();
        //return $res;
    }
 
  function mongo_getChainStatsMany($from_time, $to_time){ 
    $query = new MongoDB\Driver\Query(
        ['_id' => ['$gte' => $from_time, '$lt' => $to_time]], // query (empty: select all)
        []
    );

    $cursor = mongo_execDbQuery($query, "CHAINSTATS");
    return $cursor->toArray();
    //return $res;
  }
  // Request Limiter
    function mongo_validateRequestId($request_id){ 
        $res = false;
        $query = ['_id' => $request_id];
        $n =  mongo_countDbCollectionQuery("REQUESTS", $query);
        if ($n > 0){
            $res = true;

            global $dbm;
            global $settings;		
                    
            // delete request_id
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->delete(['_id' => $request_id]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".REQUESTS", $bulk); 
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            }
        }
        return $res;
    }
  // Ads Request Limiter
    function mongo_validateAdsRequestId($request_id){ 
        $res = false;
        $query = new MongoDB\Driver\Query(
            ['_id' => $request_id],
            [] 
        );
        $cursor = mongo_execDbQuery($query, "REQUESTS");
        $res = $cursor->toArray();

        if (count($res) > 0 && (!isset($res[0]->cnt) || $res[0]->cnt <5)){
            $res = true;

            global $dbm;
            global $settings;		
                    
            // delete request_id
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(['_id' => $request_id],['$inc' => ['cnt' => 1]]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".REQUESTS", $bulk); 
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            }
        } else {
            $res = false;
        }
        return $res;
    }
  // >>> API Rate Limiter Queries <<<
    function mongo_getApiCallsCountByIpAddress($ip_address) {
        if (empty($ip_address)) {
            return -1;
        } else {
            $query = ['ip_address' =>  $ip_address];
        }
        return mongo_countDbCollectionQuery("APIRATELIMITER", $query);
    }
  // >>> User Queries <<<
    function mongo_getUserAccountByName(string $name) {
        if (empty($name)) {
            return array();
        } else {
            $query = new MongoDB\Driver\Query(
                ['_id' => $name ], // query (empty: select all)
                []
            );
        }
        $cursor = mongo_execDbQuery($query, "USERS");
        return $cursor->toArray();
    }
    function mongo_getUserSession(string $session) {
        if (empty($session)) {
            return array();
        } else {
            $query = new MongoDB\Driver\Query(
                ['_id' => $session ], // query (empty: select all)
                []
            );
        }
        $cursor = mongo_execDbQuery($query, "USERSESSIONS");
        return $cursor->toArray();
    }
  // >>> Usage Log Queries <<<
    function mongo_getPageAggregates($since){

        $query = new MongoDB\Driver\Query(
                ['_id' => ['$gte' => $since] ], // query (empty: select all)
                ['sort' => ['_id' => 1]]
            );
        $cursor = mongo_execDbQuery($query, "PAGEAGGREGATES");
        return $cursor->toArray();

    }
    function mongo_getApiAggregates($since){

        $query = new MongoDB\Driver\Query(
                ['_id' => ['$gte' => $since] ], // query (empty: select all)
                ['sort' => ['_id' => 1]]
            );
        $cursor = mongo_execDbQuery($query, "APIAGGREGATES");
        return $cursor->toArray();

    }
    
// --- MongoDB Inserts ---
    function mongo_createUserAccount(string $name, string $pwd_hash, array $groups = []){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $user = new stdClass();
        $user->_id = $name;
        $user->passwd = $pwd_hash;
        $user->groups = $groups;
        $user->enabled = true;
        
        $bulk->update(['_id' => $user->_id ], ['$set' => $user],['upsert'=>true]);
        mongo_ensureUsersIndexes($dbm, $settings["db_name"]);
        try {
            $dbm->executeBulkWrite($settings["db_name"] . ".USERS", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            print("  Writing USERS failed: ".$e->getMessage()." \n");
            return false;
        }
    }
    function mongo_setUserPassword(string $name, string $pwd_hash){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->update(['_id' => $name], ['$set' => ['passwd' => $pwd_hash]]);
        try {
            return $dbm->executeBulkWrite($settings["db_name"] . ".USERS", $bulk)->getMatchedCount() == 1;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return false;
        }
    }
    function mongo_recordVoutDataBulk($block_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        if ($settings["debug_enabled"]){
            echo str_pad("  Recording VOUTS",50);
            $stime = milliseconds();
        }
        if ($block_record->extras["check_block"]) {
            if (count($block_record->extras["vouts"]) != mongo_getVoutCountByBlockIx($block_record->block_ix)) {
                $push = true;
            } else {
                $push = false;
            }
        } else {
            $push = true;
        }
        if ($push) {
            foreach ($block_record->extras["vouts"] as $vout_record) {
                // strip vout data
                unset($vout_record->vout_tx);
                unset($vout_record->vout_ix);
                // prepare mongo record
                $bulk->insert($vout_record);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (" . round($spent,0)." ms)". PHP_EOL;
                    
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;  
                }
            }
        }

    }
    function mongo_recordAddressDataBulk($block_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $block_rollback = new stdClass();
        $block_rollback->_id = $block_record->block_ix;
        $block_rollback->block_ix = $block_record->block_ix;

        $block_rollback->addr_updates = array();
        $block_rollback->addr_deletes = array();
        $selective = false;
        $creategroup = false;
        if ($settings["debug_enabled"]){
            echo "  Saving ADDRESSES data".PHP_EOL;
            $fstime = milliseconds();
        }
        if ($block_record->extras["check_block"]) {
            if (count($block_record->extras["addresses"]) != mongo_getAddressesCountByBlockIx($block_record->block_ix)) {
                $selective = true;
                $push = true;
            } else {
                $push = false;
            }
        } else {
            $push = true;
        }
        $addresses = $block_record->extras["addresses"];
        if ($selective) {
            $db_addresses = mongo_getAddressesByLastBlockData($block_record->block_ix);
            foreach ($db_addresses as $ar) {
                if (array_key_exists($ar->_id, $addresses)) {
                    unset($addresses[$ar->_id]);
                }
            }
        }

        if ($push && count($addresses) > 0) {
            foreach ($addresses as $address_record) {
                //roi_move record
                if(array_key_exists($address_record->_id,$block_record->extras["addressroi"])){
                    $roi_move = $block_record->extras["addressroi"][$address_record->_id];
                    (float)$address_record->deposited = $roi_move->deposited;
                    (float)$address_record->withdrawn = $roi_move->withdrawn;
                } else {
                    //$roi_move = new stdClass();
                    (float)$address_record->deposited = 0;
                    (float)$address_record->withdrawn = 0;
                }
                // mongo_get address from db
                $db_address = mongo_getAddressData($address_record->_id);
                if (count($db_address) > 0) {
                    // address already exists, updating
                    $bulk->update(
                        ['_id' => $db_address[0]->_id],
                        ['$set' => [
                            'balance'       => rpp(rpp($db_address[0]->balance)   + rpp($address_record->balance)),
                            'received'      => rpp(rpp($db_address[0]->received)  + rpp($address_record->received)),
                            'sent'          => rpp(rpp($db_address[0]->sent)      + rpp($address_record->sent)),
                            'deposited'     => rpp(rpp($db_address[0]->deposited) + rpp($address_record->deposited)),
                            'withdrawn'     => rpp(rpp($db_address[0]->withdrawn) + rpp($address_record->withdrawn)),
                            'suspected'     => rpp(rpp($db_address[0]->suspected) + rpp($address_record->suspected)),
                            'last_block'    => $address_record->last_block
                        ]]
                    );
                    $rollback_record = new stdClass();
                    $rollback_record->_id = $db_address[0]->_id;
                    $rollback_record->balance   = rpp($db_address[0]->balance);
                    $rollback_record->received  = rpp($db_address[0]->received);
                    $rollback_record->sent      = rpp($db_address[0]->sent);
                    $rollback_record->deposited = rpp($db_address[0]->deposited);
                    $rollback_record->withdrawn = rpp($db_address[0]->withdrawn);
                    $rollback_record->suspected = rpp($db_address[0]->suspected);
                    $rollback_record->last_block = $db_address[0]->last_block;
                    array_push($block_rollback->addr_updates,$rollback_record);


                    // update group balance
                    if ($address_record->_id != "Coinbase" && $address_record->_id != "PoS Coinbase" && $address_record->_id != "Fee Collector"){
                        $updategroup = true;
                        $gbulk->update(
                            ['_id' => $db_address[0]->group_id],
                            ['$inc' => ['balance' => rpp($address_record->balance)]],
                            ['multi' => false, 'upsert' => false]
                        );
                    }
                } else {
                    // address is new, inserting
                    
                    if ($address_record->_id != "Coinbase" && $address_record->_id != "PoS Coinbase" && $address_record->_id != "Fee Collector"){
                        // creating address group if not coinbase or fee collector address
                        $creategroup = true;
                        //$address_record->group_id = generateUuid('csag');
                        $address_record->group_id = generateHash($address_record->_id, 'csag-'.strtolower($settings['coin_symbol']).'-');
                        $address_group = new stdClass();
                        $address_group->_id = $address_record->group_id;
                        // $address_group->address_list = array($address_record->_id);
                        $address_group->address_count = 1;
                        $address_group->balance = rpp($address_record->balance);
                        $gbulk->insert($address_group);
                    }
                    $bulk->insert($address_record);
                    $rollback_record = new stdClass();
                    $rollback_record->_id = $address_record->_id;
                    array_push($block_rollback->addr_deletes,$rollback_record);
                }
                
            }
            try {
                if ($settings["debug_enabled"]){
                    echo str_pad("   Recording BLOCKROLLBACKS",50);
                    $stime = milliseconds();
                }
                $rbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                $rbulk->insert($block_rollback);
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKROLLBACKS", $rbulk);
                $n = $result->getInsertedCount() + $result->getModifiedCount() + $result->getUpsertedCount();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (".$n. " ops in " . round($spent,0)." ms)". PHP_EOL;
                    
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;  
                }
            }
            if ($creategroup || $updategroup){
                try {
                    if ($settings["debug_enabled"]){
                        echo str_pad("   Recording ADDRESSGROUPS",50);
                        $stime = milliseconds();
                    }
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
                    $n = $result->getInsertedCount() + $result->getModifiedCount() + $result->getUpsertedCount();
                    if ($settings["debug_enabled"]){
                        $spent = milliseconds() - $stime;
                        echo "Done (".$n. " ops in "  . round($spent,0)." ms)". PHP_EOL;
                        
                    }
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    if ($settings["debug_enabled"]){
                        $spent = milliseconds() - $stime;
                        echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                        echo "   ".$e->getMessage().PHP_EOL;

                        
                    }
                }
            }
            try {
                if ($settings["debug_enabled"]){
                    echo str_pad("   Recording ADDRESSES",50);
                    $stime = milliseconds();
                }
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $bulk);
                $n = $result->getInsertedCount() + $result->getModifiedCount() + $result->getUpsertedCount();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (".$n. " ops in " . round($spent,0)." ms)". PHP_EOL; 
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;   
                }
            }
        }
        if ($settings["debug_enabled"]){
            $spent = milliseconds() - $fstime;
            echo str_pad("  Saving ADDRESSES data",50)."Done (" . round($spent,0)." ms)". PHP_EOL;
            
        }
    }
    function mongo_recordAddressMovesDataBulk($block_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        if ($settings["debug_enabled"]){
            echo str_pad("  Recording ADDRESSMOVES",50);
            $stime = milliseconds();
        }
        if ($block_record->extras["check_block"]) {
            if (count($block_record->extras["addressmoves"]) != mongo_getAddressMovesCountByBlockIx($block_record->block_ix)) {
                $push = true;
            } else {
                $push = false;
            }
            //if ($settings["debug_enabled"]){
            //    echo " [".(round(milliseconds()-$stime,0))."]";
            //}
        } else {
            $push = true;
        }
        if ($push) {
            foreach ($block_record->extras["addressmoves"] as $move_record) {
                    // prepare mongo record
                $bulk->insert($move_record);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSMOVES", $bulk);
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (".count($block_record->extras["addressmoves"]). " ops in " . round($spent,0)." ms)". PHP_EOL;
                
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;  
                }
            }
        }

    }
    function mongo_recordTxDataBulk($block_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        if ($settings["debug_enabled"]){
            echo str_pad("  Recording TXS",50);
            $stime = milliseconds();
        }
        if ($block_record->extras["check_block"]) {
            if (count($block_record->extras["txs"]) != mongo_getTxCountByBlockIx($block_record->block_ix)) {
                $push = true;
            } else {
                $push = false;
            }
        } else {
            $push = true;
        }
        if ($push) {
            foreach ($block_record->extras["txs"] as $tx_record) {
                    // strip tx data
                unset($tx_record->extras);
                $tx_record->vins = array_values($tx_record->vins);
                $tx_record->vouts = array_values($tx_record->vouts);
                    // prepare mongo record
                $bulk->insert($tx_record);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".TXS", $bulk);
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (" . round($spent,0)." ms)". PHP_EOL;
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;  
                }
            }
        }

    }
    function mongo_recordBlockData($block_record){
        global $dbm;
        global $settings;
        if ($settings["debug_enabled"]){
            echo str_pad("  Recording BLOCK",50);
            $stime = milliseconds();
        }
            // strip block data
        unset($block_record->extras);

            // prepare mongo record
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->insert($block_record);
        $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKS", $bulk);
        if ($settings["debug_enabled"]){
            $spent = milliseconds() - $stime;
            echo "               Done (" . round($spent,0)." ms)". PHP_EOL;
            
        }
    }
    function mongo_recordLogDataBulk($block_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        if ($settings["debug_enabled"]){
            echo "   Recording PROBLEMLOG";
            $stime = milliseconds();
        }
        if (count($block_record->extras["log"]) > 0) {
            if ($block_record->extras["check_block"]) {
                if (count($block_record->extras["log"]) != mongo_getProblemLogCountByBlockIx($block_record->block_ix)) {
                    $push = true;
                } else {
                    $push = false;
                }
            } else {
                $push = true;
            }
            if ($push) {
                foreach ($block_record->extras["log"] as $log_record) {
                        // prepare mongo record
                    $bulk->insert($log_record);
                }
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".PROBLEMLOG", $bulk);
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                }
            }
        }
        if ($settings["debug_enabled"]){
            $spent = milliseconds() - $stime;
            echo "          Done (" . round($spent,0)." ms)". PHP_EOL;
            
        }
    }
    function mongo_recordLogData($log_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);

        // prepare mongo record
        $bulk->insert($log_record);
                
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".PROBLEMLOG", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
    }
    function mongo_recordTxData($tx_record){
        global $dbm;
        global $settings;	
            // strip tx data	
        unset($tx_record->extras);	
                
            // prepare mongo record	
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->insert($tx_record);
        $result = $dbm->executeBulkWrite($settings["db_name"] . ".TXS", $bulk);
    }
    function mongo_recordMarketDataBulk($market_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);

        // prepare mongo record
        $bulk->insert($market_record);
                
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".MARKETS", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
    }
    function mongo_recordChainDataBulk($chain_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        // prepare mongo record
        $bulk->insert((array)$chain_record);
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".CHAINMETRICS", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
    }
    function mongo_recordMnCacheDataBulk($mncache_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        // prepare mongo record
        $bulk->insert($mncache_record);
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".MASTERNODES", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
    }
    function mongo_recordHostDataBulk($host_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        // prepare mongo record
        $bulk->insert($host_record);        
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".HOSTMETRICS", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
    }
    function mongo_recordBlockStats($block_record){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $rbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $block_stats = $block_record->extras['blockstats'];
        try {
            $db_bs = mongo_getBlockStats($block_stats->_id);
            if (count($db_bs)>0) {
                // update
                $bulk->update(
                    ['_id' => $db_bs[0]->_id],
                    ['$set' => [
                        'coinsupply'        => rpp($block_stats->coinsupply),
                        'zerocoinsupply'    => rpp($block_stats->zerocoinsupply),
                        'blockRewardsTotal' => rpp(rpp($db_bs[0]->blockRewardsTotal)    + rpp($block_stats->blockRewardsTotal)),
                        'stakeRewardsTotal' => rpp(rpp($db_bs[0]->stakeRewardsTotal)    + rpp($block_stats->stakeRewardsTotal)),
                        'stakeBaseTotal'    => rpp(rpp($db_bs[0]->stakeBaseTotal)       + rpp($block_stats->stakeBaseTotal)),
                        'blocksNumber'      => $db_bs[0]->blocksNumber                  + $block_stats->blocksNumber,
                        'lastBlock'         => $block_stats->lastBlock
                    ]]
                );
                // rollback info
                $rbulk->update(
                    ['_id' => $block_stats->lastBlock],
                    ['$set' => [
                        'blockstats_updates' => $db_bs[0]
                        ]
                    ]
                );

            } else {
                // insert
                $bulk->insert($block_stats);
                // rollback info
                $rbulk->update(
                    ['_id' => $block_stats->lastBlock ],
                    ['$set' => [
                        'blockstats_deletes' => $block_stats->_id
                        ]
                    ]
                );
            }

            $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKROLLBACKS", $rbulk);
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATS", $bulk);
        } catch (Exception $e) {
            echo PHP_EOL."Failed to record BLOCKSTATS of BLOCKROLLBAKS:".PHP_EOL.($e->getMessage()).PHP_EOL;
            readline();
        }
    }
    function mongo_recordApiCall($ip_address){
        $api_call = new stdClass();
        $api_call->ip_address = $ip_address;
        $api_call->request_time = new MongoDB\BSON\UTCDateTime();

        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->insert($api_call);
        $result = $dbm->executeBulkWrite($settings["db_name"] . ".APIRATELIMITER", $bulk);

    }
    function mongo_recordSession($user){
        global $dbm;
        global $settings;
        $sid = !empty($user->sid) ? $user->sid : session_id();
        $set = ['user' => $user->name, 'last_active' => new MongoDB\BSON\UTCDateTime()];
        if (!empty($user->ip)) { $set['ip'] = $user->ip; }
        if (!empty($user->ua)) { $set['ua'] = $user->ua; }
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->update(['_id' => $sid], ['$set' => $set, '$setOnInsert' => ['created' => new MongoDB\BSON\UTCDateTime()]], ['upsert' => true]);
        $dbm->executeBulkWrite($settings["db_name"] . ".USERSESSIONS", $bulk);
    }

    function mongo_deleteUserSessions(array $filter){
        global $dbm;
        global $settings;
        if (empty($filter)) return 0;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->delete($filter, ['limit' => 0]);
        return $dbm->executeBulkWrite($settings["db_name"] . ".USERSESSIONS", $bulk)->getDeletedCount();
    }

    /*
     * Failed admin logins, kept 15 minutes by a TTL index that is created on
     * first use, so existing installations need no migration.
     */
    function mongo_recordLoginFailure(string $ip, string $user){
        global $dbm;
        global $settings;
        static $indexed = false;
        if (!$indexed) {
            try {
                $dbm->executeCommand($settings["db_name"], new MongoDB\Driver\Command([
                    "createIndexes" => "LOGINFAILURES",
                    "indexes" => [
                        ["name" => "ts_ttl", "key" => ["ts" => 1], "expireAfterSeconds" => 900],
                        ["name" => "ip_ts", "key" => ["ip" => 1, "ts" => 1]],
                        ["name" => "user_ts", "key" => ["user" => 1, "ts" => 1]],
                    ],
                ]));
            } catch (MongoDB\Driver\Exception\Exception $e) {}
            $indexed = true;
        }
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->insert(['ip' => $ip, 'user' => $user, 'ts' => new MongoDB\BSON\UTCDateTime()]);
        $dbm->executeBulkWrite($settings["db_name"] . ".LOGINFAILURES", $bulk);
    }

    function mongo_countLoginFailures(string $field, string $value, int $window_sec) : int {
        global $dbm;
        global $settings;
        $since = new MongoDB\BSON\UTCDateTime((time() - $window_sec) * 1000);
        $res = $dbm->executeCommand($settings["db_name"], new MongoDB\Driver\Command([
            "count" => "LOGINFAILURES", "query" => [$field => $value, "ts" => ['$gte' => $since]],
        ]))->toArray();
        return (int)($res[0]->n ?? 0);
    }
    function mongo_recordLoaderLog($loaderLog){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->insert($loaderLog);
        $result = $dbm->executeBulkWrite($settings["db_name"] . ".LOADERLOG", $bulk);

    }
    /**
     * Record how far through a block the loader has got.
     *
     * A row exists only while a height is in flight. Reaching 'complete'
     * removes it, so the collection is empty on a healthy explorer and any
     * row found at startup is a block that died mid-write.
     *
     * States, in order:
     *   pending        nothing mutated yet -- safe to simply retry
     *   rollback_ready rollback record written -- undo data exists
     *   data_written   all data written and verified -- awaiting commit marker
     */
    function mongo_setBlockState($block_ix, $state, $block_hash = null){
        global $dbm;
        global $settings;
        $doc = [
            'state'      => (string)$state,
            'block_hash' => (string)$block_hash,
            'updated_at' => time(),
            'pid'        => getmypid(),
        ];
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->update(['_id' => (int)$block_ix], ['$set' => $doc, '$inc' => ['attempt' => 1]], ['upsert' => true]);
        try {
            $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATE", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            return false;
        }
    }
    function mongo_clearBlockState($block_ix){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->delete(['_id' => (int)$block_ix]);
        try {
            $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATE", $bulk);
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            return false;
        }
    }
    /**
     * Advance a height's lifecycle state, but only while this process still
     * owns its BLOCKSTATE row. Returns false when the row is gone or owned by
     * another pid -- a recovery has claimed the height, and this writer must
     * stop before publishing its commit marker.
     */
    function mongo_transitionBlockState($block_ix, $state){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $bulk->update(
            ['_id' => (int)$block_ix, 'pid' => getmypid()],
            ['$set' => ['state' => (string)$state, 'updated_at' => time()]],
            ['upsert' => false]
        );
        try {
            $res = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATE", $bulk);
            return ($res->getMatchedCount() == 1);
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            return false;
        }
    }
    /**
     * Atomically take an in-flight height away from its writer before rolling
     * it back. Deleting the row makes every later owner-checked transition by
     * that writer fail, so of a concurrent commit and roll-back exactly one
     * wins. Returns the claimed row (its state decides how to unwind), or
     * null when the writer finished the height first.
     */
    function mongo_claimBlockState($block_ix){
        global $dbm;
        global $settings;
        $command = new MongoDB\Driver\Command([
            'findAndModify' => 'BLOCKSTATE',
            'query'         => ['_id' => (int)$block_ix],
            'remove'        => true,
        ]);
        try {
            $res = $dbm->executeCommand($settings["db_name"], $command)->toArray()[0];
            return (isset($res->value) && !is_null($res->value)) ? $res->value : null;
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return null;
        }
    }
    /** Every in-flight height, highest first: unwind from the tip downwards. */
    function mongo_getUnfinishedBlockStates(){
        $query = new MongoDB\Driver\Query([], ['sort' => ['_id' => -1]]);
        $cursor = mongo_execDbQuery($query, "BLOCKSTATE");
        return $cursor->toArray();
    }

    /**
     * Decide how far BLOCKROLLBACKS may safely be pruned.
     *
     * Rolling back to height N requires an unbroken run of rollback records
     * from the tip down to N. If that run already has a hole, pruning the tail
     * only pushes the reachable floor further up and makes the damage
     * permanent. So contiguity is checked first, and pruning is skipped (and
     * the gap reported) whenever the chain is already broken.
     *
     * @return int|null height below which records may be deleted, or null to skip
     */
    function mongo_getRollbackPruneFloor($block_ix){
        global $settings;
        if (!isset($settings["rollback_depth"]) || $settings["rollback_depth"] <= 0){ return null; }

        $floor = $block_ix - $settings["rollback_depth"];
        if ($floor <= 0){ return null; } // nothing old enough to prune yet

        // Contiguity test in two O(1) lookups rather than counting the whole
        // ~rollback_depth window on every block: an unfiltered count is served
        // from collection metadata, and the lowest height is a single index
        // seek. If the records from the lowest height up to the tip are
        // contiguous, their number equals the height span.
        $total = mongo_countDbCollection("BLOCKROLLBACKS");
        if ($total < 0){ $total = 0; }

        $lowest_q = new MongoDB\Driver\Query([], ['sort' => ['_id' => 1], 'limit' => 1, 'projection' => ['_id' => 1]]);
        $lowest_r = mongo_execDbQuery($lowest_q, "BLOCKROLLBACKS")->toArray();
        if (count($lowest_r) == 0){ return null; } // nothing stored, nothing to prune
        $lowest = (int)$lowest_r[0]->_id;

        // Heights [lowest .. block_ix-1] should each hold exactly one record.
        $expected = $block_ix - $lowest;
        $actual = $total;

        if ($actual < $expected){
            $log_record = new stdClass();
            (string)$log_record->log_id           = $block_ix."@".time();
            (string)$log_record->detectiontime_tz = date('c');
            (int)   $log_record->block_ix         = $block_ix;
            (string)$log_record->detected_by      = "blockLoader";
            (string)$log_record->description      = "Rollback chain has gaps: heights ".$lowest."-".($block_ix-1).
                " should hold ".$expected." rollback records, found ".$actual.
                ". Pruning skipped to avoid raising the reachable rollback floor any further.";
            (string)$log_record->object_class     = "BLOCK";
            (string)$log_record->object_id        = (string)$block_ix;
            (int)   $log_record->level            = 1;
            try { mongo_recordLogData($log_record); } catch (Throwable $e) {}
            return null;
        }
        return $floor;
    }

    // V2 Methods
        function mongo_insertBlockRollbacks($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
            // add vout rollbacks
            if (isset($settings["storage_improvement"]) && $settings["storage_improvement"]) {
                // full vout structure recorded for rollback
                foreach ($block->rollbacks['vouts']['set_unspent'] as $key=>$vout){
                    if (isset($vout->asset)){$vout->asset->amount = cmdec($vout->asset->amount);}       
                    if (isset($vout->nested_cdd)){
                        $vout->nested_cdd = cmdec($vout->nested_cdd);
                    }    
                    $block->rollbacks['vouts']['set_unspent'][$key] = $vout;                                                       
                }
            } else {
                if (count($block->vout_spent)>0){
                    $block->rollbacks['vouts']['set_unspent'] = array_keys($block->vout_spent);
                }   
            }
            if (count($block->rollbacks['assets']['update'])>0){
                foreach ($block->rollbacks['assets']['update'] as $key=>$asset){
                    $asset->supply = cmdec($asset->supply);
                    $block->rollbacks['assets']['update'][$key] = $asset;
                }
                $block->rollbacks['assets']['update'] = array_values($block->rollbacks['assets']['update']);
            }
            // prepare mongo record
            $bulk->update(['_id' => $block->block_ix], ['$set' => $block->rollbacks],['upsert'=>true]);
            $prune_floor = mongo_getRollbackPruneFloor($block->block_ix);
            if (!is_null($prune_floor)){
                $bulk->delete(['_id' => ['$lt' => $prune_floor]]);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKROLLBACKS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                echo json_encode($block->rollbacks)."\n";
                echo $e->getMessage()."\n";
                return false;
            }
        }
        function mongo_insertVouts($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->vouts)<1){ return true; }
            // prepare mongo records
            $unstored = db_unstoredVoutAddresses();
            foreach ($block->vouts as $vout){
                if (!in_array($vout->address, $unstored, true)){
                    // we don't store nulldata, Shield and Credit Pool UTXO
                    $vout->stripData();
                    $bulk->update(['_id' => $vout->_id], ['$set' => $vout],['upsert'=>true]);
                }
            }
            if ($bulk->count() == 0){ return true; }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_insertAirdrops($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->airdrops)<1){ return true; }
            // prepare mongo records
            foreach ($block->airdrops as $airdrop){
                $airdrop->stripData();
                $bulk->update(['_id' => $airdrop->_id], ['$set' => $airdrop],['upsert'=>true]);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".AIRDROPS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_insertTxs($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->txs)<1 && is_null($block->reward_tx)){ return true; }
            // prepare mongo records
            if (!is_null($block->reward_tx)){
                $rtx = $block->reward_tx;
                $rtx->stripData();
                $bulk->update(['_id' => $rtx->_id],['$set' => $rtx],['upsert'=>true]);
            }

            foreach ($block->txs as $tx){
                // strip tx data
                $tx->stripData();
                //$bulk->insert($tx);
                $bulk->update(['_id' => $tx->_id],['$set' => $tx],['upsert'=>true]);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".TXS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_insertAddressMoves($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->movements)<1){ return true; }
            // prepare mongo records
            foreach (array_values($block->movements) as $am){
                $am->stripData();
                $bulk->insert($am);
                //$n++;
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSMOVES", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_insertAssetMoves($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->asset_movements)<1){ return true; }
            // prepare mongo records
            foreach (array_values($block->asset_movements) as $aam){
                $aam->stripData();
                $bulk->insert($aam);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ASSETMOVES", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_insertBlock($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            // prepare mongo records
            // strip block data
            $block->stripData();
            $bulk->insert($block);

            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_insertMdaoBalances($mdao_balances){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            
            if (count($mdao_balances) == 0) {return true; }

            foreach ($mdao_balances as $balance){
                $bulk->update(['_id' => $balance->_id ], ['$set' => $balance],['upsert'=>true]);
            }

            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".MDAOBALANCES", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }


        function mongo_createRequest(){
            $request = new stdClass();
            $request->_id = random_hex(32);
            $request->timestamp = new MongoDB\BSON\UTCDateTime();
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(['_id' => $request->_id], ['$set' => $request],['upsert'=>true]);
    
            try{
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".REQUESTS", $bulk);
                return $request->_id;
            } catch (Exception $e){
                throw new Exception("failed to register the request");
            }
    
        }
    // SIP-023
        function mongo_updateAddressGroupAggregates($group_id){
            global $dbm;
            global $settings;
            $dbc = new MongoDB\Driver\Command([
                'aggregate' => 'ADDRESSES',
                'pipeline' => [
                    ['$match' => [ 'group_id' => $group_id]],
                    ['$group' => 
                        [ '_id' => $group_id,
                        'address_count'     => [ '$sum' => 1 ],
                        'balance'           => [ '$sum' => '$balance' ],
                        ]
                    ],
                    [ '$merge' => 
                        [
                        "into"              => "ADDRESSGROUPS",
                        "on"                => "_id",
                        "whenMatched"       => "merge",
                        "whenNotMatched"    => "insert",
                        ]
                    ],
                ],
                'cursor' => ['batchSize' => 1]
            ]);

            $cursor = $dbm->executeCommand($settings['db_name'], $dbc);
            return $cursor->toArray();
        }

// --- MongoDb Updates ---
    function mongo_updateAddressGroups($block_record){
        global $dbm;
        global $settings;
        $abulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $ubulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        if ($settings["debug_enabled"]){
            echo "  Updating  GROUP_ID". PHP_EOL;
            $fstime = milliseconds();
        }
        $apply = false;

        $result = null;
        if (count($block_record->extras["addressgroups"])>0){
            foreach ($block_record->extras["addressgroups"] as $key => $addressgroup){
                if (count($addressgroup->addresses)>0){
                    // --- ADD ADDRESS TO GROUP --- //
                    $inc = count($addressgroup->addresses);
                    // 1. update addresses without groups
                    if (!empty($addressgroup->group_id)){
                        $gid = $addressgroup->group_id;
                    } else {
                        //var_dump($addressgroup->addresses);
                        //readline();
                        $gid = generateHash($addressgroup->addresses[0],'csag-'.strtolower($settings['coin_symbol']).'-');
                        $inc-=1;
                    }
                    

                    $abulk->update(
                        ['_id' => [ '$in' => $addressgroup->addresses]],
                        ['$set' => ['group_id' => $gid]],
                        ['multi' => true, 'upsert' => false]
                    );

                    // 2. update addressgroup
                    $gbulk->update(
                        ['_id' =>$gid],
                        ['$inc' => ['address_count' => $inc]],
                        ['multi' => false, 'upsert' => false]
                    );
                    $apply = true;
                    
                }
                if (count($addressgroup->groups)>1){
                    // --- JOIN GROUPS --- //
                    // 0. remove group_id from the list of updated, calc increment
                        unset($addressgroup->groups[$addressgroup->group_id]);
                        $inc = array_sum($addressgroup->groups);
                    // 1. join gtoups tags
                        $tags = array();
                        $ag = mongo_getAddressGroupById($addressgroup->group_id);
                        if (count($ag)>0 && isset($ag[0]->tags)){
                            $tags = $ag[0]->tags;
                        }
                        foreach ($addressgroup->groups as $gid => $gac){
                            $ag = mongo_getAddressGroupById($gid);
                            if (count($ag)>0 && isset($ag[0]->tags)){
                                $tags = array_merge($tags, $ag[0]->tags);
                            }
                        }
                        if (count($tags)>0){
                            $tags = array_unique($tags);
                        }

                    // 2. update addresses having groupid
                        $abulk->update(
                            ['group_id' => [ '$in' => array_keys($addressgroup->groups)]],
                            ['$set' => ['group_id' => $addressgroup->group_id]],
                            ['multi' => true, 'upsert' => false]
                        );
                    // 3. update addressgroup
                        if (count($tags)>0){
                            $gbulk->update(
                                ['_id' =>$addressgroup->group_id],
                                ['$inc' => ['address_count' => $inc], '$set' => ['tags' => $tags]],
                                ['multi' => false, 'upsert' => false]
                            );
                        } else {
                            $gbulk->update(
                                ['_id' =>$addressgroup->group_id],
                                ['$inc' => ['address_count' => $inc]],
                                ['multi' => false, 'upsert' => false]
                            );
                        }
                    // 4. delete merged minor groups
                        $gbulk->delete(['_id' => [ '$in' => array_keys($addressgroup->groups) ]]);
                    $apply = true;
                }
            }
        }
        if ($apply){
            try {
                if ($settings["debug_enabled"]){
                    echo str_pad("   Updating ADDRESSES",50);
                    $stime = milliseconds();
                }
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $abulk);
                $n = $result->getInsertedCount() + $result->getModifiedCount() + $result->getUpsertedCount();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (".$n. " ops in " . round($spent,0)." ms)". PHP_EOL; 
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;   
                }
            }
            try {
                if ($settings["debug_enabled"]){
                    echo str_pad("   Updating GROUP address_count",50);
                    $stime = milliseconds();
                }
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
                $n = $result->getInsertedCount() + $result->getModifiedCount() + $result->getUpsertedCount();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Done (".$n. " ops in " . round($spent,0)." ms)". PHP_EOL; 
                }
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                if ($settings["debug_enabled"]){
                    $spent = milliseconds() - $stime;
                    echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                    echo "   ".$e->getMessage().PHP_EOL;   
                }
            }
            $groupupdate = false;
            // update address_count and balance of groups
            foreach($block_record->extras["addressgroups"] as $addressgroup){
                $ga = mongo_getAddressGroupAggregates($addressgroup->group_id);
                if (count($ga)>0){
                    $g_count = $ga[0]->countAddresses;
                    $g_balance = rpp($ga[0]->totalBalance);
                    $ubulk->update(
                        ['_id' =>$addressgroup->group_id],
                        ['$set' => ['address_count' => $g_count], '$set' => ['balance' => rpp($g_balance)]],
                        ['multi' => false, 'upsert' => false]
                    );
                    $groupupdate = true;
                }
            }
            if ($groupupdate){
                try {
                    if ($settings["debug_enabled"]){
                        echo str_pad("   Updating GROUP balance",50);
                        $stime = milliseconds();
                    }
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $ubulk);
                    $n = $result->getInsertedCount() + $result->getModifiedCount() + $result->getUpsertedCount();
                    if ($settings["debug_enabled"]){
                        $spent = milliseconds() - $stime;
                        echo "Done (".$n. " ops in " . round($spent,0)." ms)". PHP_EOL; 
                    }
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    if ($settings["debug_enabled"]){
                        $spent = milliseconds() - $stime;
                        echo "Failed (" . round($spent,0)." ms)". PHP_EOL;
                        echo "   ".$e->getMessage().PHP_EOL;   
                    }
                }
            }
        }
        if ($settings["debug_enabled"]){
            $spent = milliseconds() - $fstime;
            echo str_pad("  Updating  GROUP_ID",50)."Done (" . round($spent,0)." ms)". PHP_EOL;
            
        }
        return $result;
    }
    function mongo_updateAddressGroups_bak($block_record){
        global $dbm;
        global $settings;
        $abulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        if ($settings["debug_enabled"]){
            echo "  Updating  GROUP_ID";
            $stime = milliseconds();
        }
        $apply = false;
        $result = null;
        if (count($block_record->extras["addressgroups"])>0){
            foreach ($block_record->extras["addressgroups"] as $key => $addressgroup){
                if (count($addressgroup->addresses)>0){
                    // --- ADD ADDRESS TO GROUP --- //
                    $inc = count($addressgroup->addresses);
                    // 1. update addresses without groups
                    if (!empty($addressgroup->group_id)){
                        $gid = $addressgroup->group_id;
                    } else {
                        $gid = generateHash($addressgroup->addresses[0],'csag-'.strtolower($settings['coin_symbol']).'-');
                        $inc-=1;
                    }
                    

                    $abulk->update(
                        ['_id' => [ '$in' => $addressgroup->addresses]],
                        ['$set' => ['group_id' => $gid]],
                        ['multi' => true, 'upsert' => false]
                    );

                    // 2. update addressgroup
                    $gbulk->update(
                        ['_id' =>$gid],
                        ['$inc' => ['address_count' => $inc]],
                        ['multi' => false, 'upsert' => false]
                    );
                    $apply = true;
                    
                }
                if (count($addressgroup->groups)>1){
                    // --- JOIN GROUPS --- //
                    // 0. remove group_id from the list of updated, calc increment
                        unset($addressgroup->groups[$addressgroup->group_id]);
                        $inc = array_sum($addressgroup->groups);
                    // 1. join gtoups tags
                        $tags = array();
                        $ag = mongo_getAddressGroupById($addressgroup->group_id);
                        if (count($ag)>0 && isset($ag[0]->tags)){
                            $tags = $ag[0]->tags;
                        }
                        foreach ($addressgroup->groups as $gid => $gac){
                            $ag = mongo_getAddressGroupById($gid);
                            if (count($ag)>0 && isset($ag[0]->tags)){
                                $tags = array_merge($tags, $ag[0]->tags);
                            }
                        }
                        if (count($tags)>0){
                            $tags = array_unique($tags);
                        }

                    // 2. update addresses having groupid
                        $abulk->update(
                            ['group_id' => [ '$in' => array_keys($addressgroup->groups)]],
                            ['$set' => ['group_id' => $addressgroup->group_id]],
                            ['multi' => true, 'upsert' => false]
                        );
                    // 3. update addressgroup
                        if (count($tags)>0){
                            $gbulk->update(
                                ['_id' =>$addressgroup->group_id],
                                ['$inc' => ['address_count' => $inc], '$set' => ['tags' => $tags]],
                                ['multi' => false, 'upsert' => false]
                            );
                        } else {
                            $gbulk->update(
                                ['_id' =>$addressgroup->group_id],
                                ['$inc' => ['address_count' => $inc]],
                                ['multi' => false, 'upsert' => false]
                            );
                        }
                    // 4. delete merged minor groups
                        $gbulk->delete(['_id' => [ '$in' => array_keys($addressgroup->groups) ]]);
                    $apply = true;
                }
            }
        }
        if ($apply){
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $abulk);
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
            }
        }
        if ($settings["debug_enabled"]){
            $spent = milliseconds() - $stime;
            echo "            Done (" . round($spent,0)." ms)". PHP_EOL;
            
        }
        return $result;
    }
    function mongo_setVoutSpent($vout_id, $vin_id){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(['_id' => $vout_id], ['$set' => ['vout_spent_in' => $vin_id]]);
        $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
    }
    function mongo_setVoutUnspent($vout_id){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite();
        $bulk->update(['_id' => $vout_id], ['$set' => ['vout_spent_in' => '']]);
        $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
    }

    // V2 Methods
        function mongo_updateVoutsSpent($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->vins)<1){ return true; }
            $apply = false;

            if (isset($settings["storage_improvement"]) && $settings["storage_improvement"]) {
                // deleting spent vouts
                if (count($block->vout_spent)>0){
                    $spent_vouts = array_keys($block->vout_spent);
                    $bulk->delete(['_id' => ['$in' => $spent_vouts]]);
                    $apply = true;
                }
            } else {
                // spent marking
                foreach($block->vout_spent as $vout=>$vin){
                    $bulk->update(['_id' => $vout], ['$set' => ['is_spent' => true, 'spent_in' => $vin]],['upsert'=>false]);
                    $apply = true;
                }
            }

            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    return false;
                }
            } else {
                return true;
            }
        }
        function mongo_updateAirdropsClaimed($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->claimed_airdrops)<1){ return true; }
            
            // deleting claimed airdrops
            $claimed_airdrops = array_keys($block->claimed_airdrops);
            $bulk->delete(['_id' => ['$in' => $claimed_airdrops]]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".AIRDROPS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_updateAddresses($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->addresses)<1){ return true; }
            // prepare mongo records
            foreach (array_values($block->addresses) as $addr){
                $addr->stripData();
                $bulk->update(['_id' => $addr->_id], ['$set' => $addr],['upsert'=>true]);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_updateAssets($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            if (count($block->assets)<1){ return true; }
            // prepare mongo records
            foreach (array_values($block->assets) as $asset){
                $asset->stripData();
                $bulk->update(['_id' => $asset->_id], ['$set' => $asset],['upsert'=>true]);
            }
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ASSETS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
        }
        function mongo_updateGroups($block){
            global $dbm;
            global $settings;
            global $tt;
            $abulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $apply = false;
            if (count($block->addressgroups)<1){ return true; }

            if (count($block->master_groups)>0){
                // record group merges (ADDRESSES), delete merged groups (GROUPS)
                $tt->createTimer('prep_addr_updates',3,true);

                foreach ($block->master_groups as $master=>$merged){
                    $abulk->update(['group_id' => ['$in' => array_keys($merged)]],['$set' => ['group_id' => $master]],['multi' => true]);
                }

                $tt->stopTimer('prep_addr_updates', 'addr_upd_reqs = '.count($abulk));
                $tt->createTimer('prep_grp_deletes',3,true);
                $gbulk->delete(['_id' => ['$in' => array_keys($block->merged_groups)]]);
                $tt->stopTimer('prep_grp_deletes', 'grp_del_reqs = '.count($gbulk));   

                //if ($apply){
                    $tt->createTimer('apply_addr_updates',3,true);
                    try {
                        $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $abulk);
                        //var_dump($result);
                        $tt->stopTimer('apply_addr_updates', 'successful ( modified '.$result->getModifiedCount().'/'.$result->getMatchedCount().')');
                    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                        $result = $e->getWriteResult();
                        $tt->stopTimer('apply_addr_updates', 'failed');
                        //print('Upd addresses..... (_!_)\n');
                    }
                    $tt->createTimer('apply_grp_deletes',3,true);
                    try {
                        $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
                        $tt->stopTimer('apply_grp_deletes', 'successful');
                    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                        $result = $e->getWriteResult();
                        $tt->stopTimer('apply_grp_deletes', 'failed');
                        //print('Upd groups.....(_!_)\n');
                    }
                //}
            }

            // SIP-030
            $sip030_enabled = true;

            // SIP-023
            $sip023_enabled = false;

            if ($sip030_enabled) {
                $tt->createTimer('grp_update-sip030',3,true);
                $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                foreach (array_values($block->addressgroups) as $mag){
                    $mag->stripData();
                    $gbulk->update(['_id' => $mag->_id],['$set' => $mag],['upsert' => true]);
                }
                if (count($gbulk)>0) {
                    try {
                        $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
                        $tt->stopTimer('grp_update-sip030', 'sent = '.count($block->addressgroups).' / matched = '.$result->getMatchedCount().' / updated = '.$result->getModifiedCount().' / inserted = '.$result->getUpsertedCount());
                        return true;
                    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                        echo "\n".$e->getWriteResult()."\n";
                        $tt->stopTimer('grp_update-sip030', 'failed: '.$e->getMessage());
                        return false;
                    }
                }

            } else if ($sip023_enabled) {
                $s=0;
                $tt->createTimer('grp_balance_upd-sip023',3,true);
                $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                foreach (array_values($block->addressgroups) as $mag){
                    try {
                        $r = mongo_updateAddressGroupAggregates($mag->_id);
                        $s++;
                    } catch (Exception $e){
                        echo $e->getMessage();
                    }
                    if (isset($mag->tags)){
                        $gbulk->update(['_id' => $mag->_id],['$set' => ['tags' => $mag->tags]]);
                    }
                }
                if (count($gbulk)>0) {
                    try {
                        $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
                    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                        $result = $e->getWriteResult();
                    }
                }
                $tt->stopTimer('grp_balance_upd-sip023', 'grp_balance_upd = '.$s.'/'.count($block->addressgroups)."; tag_upd_req = ".count($gbulk));
                if ($s == count($block->addressgroups)){
                    return true;
                } else {
                    return false;
                }

            } else { // pre SIP-023
                $tt->createTimer('prep_grp_balance_upd',3,true);
                // update master groups data (GROUPS)
                $gbulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                $apply = false;

                foreach (array_values($block->addressgroups) as $mag){
                    $mag->stripData();
                    // get group balance and address count
                    $ga = mongo_getAddressGroupAggregates($mag->_id);
                    // apply merged tags
                    if (count($ga)>0){
                        $ga = $ga[0];
                        $mag->balance = $ga->totalBalance;
                        $mag->address_count = $ga->countAddresses;
                        $gbulk->update(['_id' => $mag->_id],['$set' => $mag],['upsert'=>true]);
                        $apply = true;
                    } else {
                        $ss = "/// ATT:  WTF!!";
                    } 
                }

                $tt->stopTimer('prep_grp_balance_upd', 'grp_balance_upd_reqs = ' . count($gbulk));

                if ($apply){
                    $tt->createTimer('apply_grp_balance_upd',3,true);
                    try {
                        $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $gbulk);
                        $tt->stopTimer('apply_grp_balance_upd', 'successful');
                        return true;
                    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                        $result = $e->getWriteResult();
                        $tt->stopTimer('apply_grp_balance_upd', 'failed');
                        return false;
                    }
                }
            }

        }
        function mongo_updateBlockStats($block){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            
            try {
                
                $bulk->update(
                    ['_id' => $block->block_stats->_id],
                    ['$set' => $block->block_stats],
                    ['upsert' => true]
                );
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATS", $bulk);
                return true;
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record BLOCKSTATS:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }
        function mongo_updateChainStats($chain_stats){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            
            try {
                
                $bulk->update(
                    ['_id' => $chain_stats->_id],
                    ['$set' => $chain_stats],
                    ['upsert' => true]
                );
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CHAINSTATS", $bulk);
                return true;
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record CHAINSTATS:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }
        function mongo_deleteChainStats($timestamp){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            
            try {
                
                $bulk->delete(
                    ['_id' => $timestamp]
                );
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CHAINSTATS", $bulk);
                return true;
            } catch (Exception $e) {
                echo PHP_EOL."Failed to delete CHAINSTATS:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }
        function mongo_rollbackAddressGroups($rollback_record){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
            $apply = false;

            if (isset($rollback_record->groups->update)) {
                foreach ($rollback_record->groups->update as $ag){
                    $bulk->update(
                        ['_id'      => $ag->_id],
                        ['$set'     => $ag],
                        ['upsert'   => true]
                    );
                    $apply = true;
                }
            }

            if (isset($rollback_record->groups->delete) && count($rollback_record->groups->delete)>0){
                $bulk->delete(['_id'      => [ '$in' => $rollback_record->groups->delete ]]);
                $apply = true;
            }

            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSGROUPS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    return false;
                }
            } else {
                return true;
            }

        }
        function mongo_rollbackAddresses($rollback_record){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $apply = false;

            if (isset($rollback_record->addresses->update)) {
                foreach ($rollback_record->addresses->update as $addr){
                    $bulk->update(
                        ['_id'      => $addr->_id],
                        ['$set'     => $addr],
                        ['upsert'   => true]
                    );
                    $apply = true;
                }
            }

            if (isset($rollback_record->addresses->delete)) {
                foreach ($rollback_record->addresses->delete as $addr){
                    $bulk->delete(['_id'      => $addr]);
                    $apply = true;
                } 
            }
            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                    return false;
                }
            } else {
                return true;
            }
        }
        function mongo_rollbackAssets($rollback_record){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $apply = false;

            if (isset($rollback_record->assets->update)) {
                foreach ($rollback_record->assets->update as $asset){
                    $bulk->update(
                        ['_id'      => $asset->_id],
                        ['$set'     => $asset],
                        ['upsert'   => true]
                    );
                    $apply = true;
                }
            }

            if (isset($rollback_record->assets->delete)) {
                foreach ($rollback_record->assets->delete as $asset){
                    $bulk->delete(['_id'      => $asset]);
                    $apply = true;
                } 
            }
            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".ASSETS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                    return false;
                }
            } else {
                return true;
            }
        }
        function mongo_rollbackVoutSpent($rollback_record){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $apply = false;
            if (isset($rollback_record->vouts->set_unspent) && count($rollback_record->vouts->set_unspent)>0){
                if (isset($settings["storage_improvement"]) && $settings["storage_improvement"]) {
                    foreach ($rollback_record->vouts->set_unspent as $vout) {
                        $bulk->update(
                            ['_id'=>$vout->_id],
                            ['$set'=>$vout],
                            ['upsert'=>true]
                        );
                    }
                    $apply = true;
                } else {
                    foreach ($rollback_record->vouts->set_unspent as $vout) {
                        $bulk->update(
                            ['_id'=>$vout],
                            ['$set'=>['is_spent'=>false, 'spent_in'=>null]],
                            ['upsert'=>false]
                        );
                        $apply = true;
                    }
                }
            }
            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                        // LOG-2: rollback of blockstats failed
                        // $log_record = new stdClass();
                        // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                        // (string)$log_record->detectiontime_tz = date('c');
                        // (int)$log_record->block_ix = $block_ix;
                        // (string)$log_record->detected_by = "blockLoader";
                        // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                        // (string)$log_record->object_class = "BLOCK";
                        // (string)$log_record->object_id = $block->_id;
                        // (int)$log_record->level = 2;
                        // $res = mongo_recordLogData($log_record);
                        // print("\n\n$log_record->description\n");
                    return false;
                }
            } else {
                return true;
            }
        }
        function mongo_rollbackAirdropsClaimed($rollback_record){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $apply = false;
            if (isset($rollback_record->airdrops->set_unclaimed) && count($rollback_record->airdrops->set_unclaimed)>0){
                foreach ($rollback_record->airdrops->set_unclaimed as $airdrop) {
                    $bulk->update(
                        ['_id'=>$airdrop->_id],
                        ['$set'=>$airdrop],
                        ['upsert'=>true]
                    );
                }
                $apply = true;
            }
            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".AIRDROPS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                        // LOG-2: rollback of blockstats failed
                        // $log_record = new stdClass();
                        // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                        // (string)$log_record->detectiontime_tz = date('c');
                        // (int)$log_record->block_ix = $block_ix;
                        // (string)$log_record->detected_by = "blockLoader";
                        // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                        // (string)$log_record->object_class = "BLOCK";
                        // (string)$log_record->object_id = $block->_id;
                        // (int)$log_record->level = 2;
                        // $res = mongo_recordLogData($log_record);
                        // print("\n\n$log_record->description\n");
                    return false;
                }
            } else {
                return true;
            }
        }
        function mongo_recordApiLog($event){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);

            $id = $event->timestamp  - ($event->timestamp % 300);
            $event->timestamp = new MongoDB\BSON\UTCDateTime($event->timestamp*1000);

            try {
                
                $bulk->insert($event);
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".APILOG", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record APILOG:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
            try {
                $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                $bulk->update(
                    ["_id"=> $id],
                    ['$set' => [
                        "timestamp" => new MongoDB\BSON\UTCDateTime($id*1000)
                        ],
                    '$inc' =>[
                        $event->call.".total" => $event->duration,
                        $event->call.".num" => 1
                        ]
                    ],
                    ["upsert" => true]
                );
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".APIAGGREGATES", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record APIAGGREGATES:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }

        }
        function mongo_recordTopAddressesCache($data){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(
                [ "_id" => "TOP_ADDRESSES"],
                ['$set' => ["data" => $data ]],
                ["upsert" => true]
            );
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CACHE", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record CACHE:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }

        function mongo_recordGoldRushCache($data){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(
                [ "_id" => "GOLD_RUSH"],
                ['$set' => ["data" => $data ]],
                ["upsert" => true]
            );
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CACHE", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record CACHE:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }

        function mongo_recordTopAddressGroupsCache($data){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(
                [ "_id" => "TOP_GROUPS"],
                ['$set' => ["data" => $data ]],
                ["upsert" => true]
            );
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CACHE", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record CACHE:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }

        function mongo_recordMiningCache($data){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(
                [ "_id" => "MINING"],
                ['$set' => ["data" => $data ]],
                ["upsert" => true]
            );
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CACHE", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record CACHE:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }

        function mongo_recordOverviewCache($data){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->update(
                [ "_id" => "OVERVIEW"],
                ['$set' => ["data" => $data ]],
                ["upsert" => true]
            );
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".CACHE", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record CACHE:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }

        function mongo_recordPageLog($event){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);

            $id = $event->timestamp  - ($event->timestamp % 300);
            $event->timestamp = new MongoDB\BSON\UTCDateTime($event->timestamp*1000);

            try {
                
                $bulk->insert($event);
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".PAGELOG", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record PAGELOG:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
            try {
                $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                $bulk->update(
                    ["_id"=> $id],
                    ['$set' => [
                        "timestamp" => new MongoDB\BSON\UTCDateTime($id*1000)
                        ],
                    '$inc' =>[
                        $event->page.".total" => $event->duration,
                        $event->page.".num" => 1
                        ]
                    ],
                    ["upsert" => true]
                );
                
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".PAGEAGGREGATES", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record PAGEAGGREGATES:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }

        }

        function mongo_recordPageMetrics($event){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            // The TTL field is server time; the client's ts is kept only as data.
            $event["timestamp"] = new MongoDB\BSON\UTCDateTime();
            try {
                $bulk->insert($event);
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".PAGEMETRICS", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record PAGEMETRICS:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }
        function mongo_recordAdsMetrics($event){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $event["timestamp"] = new MongoDB\BSON\UTCDateTime();
            try {
                $bulk->insert($event);
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADSMETRICS", $bulk);
            } catch (Exception $e) {
                echo PHP_EOL."Failed to record ADSMETRICS:".PHP_EOL.($e->getMessage()).PHP_EOL;
                return false;
            }
        }
// --- MongoDB Deletes ---

    function mongo_deleteOldRollbackRecords($block_ix){
        global $dbm;
        global $settings;		
        if ($settings["debug_enabled"]){
            echo "  Deleting ROLLBACKDATA";
            $stime = milliseconds();
        }
        // prepare mongo record
        $prune_floor = mongo_getRollbackPruneFloor($block_ix);
        if (is_null($prune_floor)){
            if ($settings["debug_enabled"]){ echo "         Skipped (rollback chain incomplete)". PHP_EOL; }
            return true;
        }
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->delete(['_id' => ['$lt' => $prune_floor]]);
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKROLLBACKS", $bulk);
            $res = true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            $res = false;
        }
        if ($settings["debug_enabled"]){
            $spent = milliseconds() - $stime;
            echo "         Done (" . round($spent,0)." ms)". PHP_EOL;
            
        }
        return $res;
    }
    function mongo_deleteAddressMovesByBlockHeight($block_ix){
        global $dbm;
        global $settings;		
                
        // prepare mongo record	
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->delete(['block_ix' => $block_ix]);
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSMOVES", $bulk); 
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
        
    }
    function mongo_deleteVoutsByBlockHeight($block_ix){
        global $dbm;
        global $settings;		
                
        // prepare mongo record	
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
        $bulk->delete(['block_ix' => $block_ix]);
        try {
            $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk); 
            return true;
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            $result = $e->getWriteResult();
            return false;
        }
        
    }
    function mongo_rollbackBlockStatsV1($block){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $block_ix=$block->block_ix;

        $block_rollback = mongo_getRollbackData($block_ix);
        //var_dump($block_rollback);
        //readline();
        if (count($block_rollback) > 0) {
            if (isset($block_rollback[0]->blockstats_updates)){
                $rollback_record = $block_rollback[0]->blockstats_updates;
                    $bulk->update(
                        ['_id' => $rollback_record->_id],
                        ['$set' => [
                            'coinsupply'        => rpp($rollback_record->coinsupply),
                            'zerocoinsupply'    => rpp($rollback_record->zerocoinsupply),
                            'blockRewardsTotal' => rpp($rollback_record->blockRewardsTotal),
                            'stakeRewardsTotal' => rpp($rollback_record->stakeRewardsTotal),
                            'stakeBaseTotal'    => rpp($rollback_record->stakeBaseTotal),
                            'blocksNumber'      => $rollback_record->blocksNumber,
                            'lastBlock'         => $rollback_record->lastBlock
                        ]]
                    );
                
            }
            if (isset($block_rollback[0]->blockstats_deletes)){
                $bulk->delete(['_id' => $block_rollback[0]->blockstats_deletes]);
                
            }
            if (count($bulk) > 0){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    $log_record = new stdClass();
                    (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    (string)$log_record->detectiontime_tz = date('c');
                    (int)$log_record->block_ix = $block_ix;
                    (string)$log_record->detected_by = "blockLoader";
                    (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    (string)$log_record->object_class = "BLOCK";
                    (string)$log_record->object_id = $block->_id;
                    (int)$log_record->level = 2;
                    $res = mongo_recordLogData($log_record);
                    print("\n\n$log_record->description\n");
                    return false;
                    //return false;
                }
            }
        } else { 
            // LOG rollback failure LOG-2: no rollback data
            $log_record = new stdClass();
            (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
            (string)$log_record->detectiontime_tz = date('c');
            (int)$log_record->block_ix = $block_ix;
            (string)$log_record->detected_by = "blockLoader";
            (string)$log_record->description = "Rollback data missing for block stats: No rollback information. Explorer RE-SYNC required.";
            (string)$log_record->object_class = "BLOCK";
            (string)$log_record->object_id = $block->_id;
            (int)$log_record->level = 2;
            $res = mongo_recordLogData($log_record);
            print("\n\n$log_record->description\n");
            return false;
        }

    }
    function mongo_rollbackAddressBalances($block){
        global $dbm;
        global $settings;
        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
        $block_ix=$block->block_ix;

        $block_rollback = mongo_getRollbackData($block_ix);
        //var_dump($block_rollback);
        //readline();
        if (count($block_rollback) > 0) {
            if (count($block_rollback[0]->addr_updates)>0){
                foreach($block_rollback[0]->addr_updates as $rollback_record){
                    $bulk->update(
                        ['_id' => $rollback_record->_id],
                        ['$set' => [
                            'balance'   => rpp($rollback_record->balance),
                            'received'  => rpp($rollback_record->received),
                            'sent'      => rpp($rollback_record->sent),
                            'deposited' => rpp($rollback_record->deposited),
                            'withdrawn' => rpp($rollback_record->withdrawn),
                            'suspected' => rpp($rollback_record->suspected),
                            'last_block' => $rollback_record->last_block
                        ]]
                    );

                }
            }
            if (count($block_rollback[0]->addr_deletes)>0){
                foreach($block_rollback[0]->addr_deletes as $rollback_record){
                    $bulk->delete(['_id' => $rollback_record->_id]);
                }
            }
            if (count($bulk) > 0){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSES", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    // LOG-2: rollback of balances failed
                    $log_record = new stdClass();
                    (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    (string)$log_record->detectiontime_tz = date('c');
                    (int)$log_record->block_ix = $block_ix;
                    (string)$log_record->detected_by = "blockLoader";
                    (string)$log_record->description = "Rollback of address balances failed: orphan block detected: blocks #".$block_ix." [".$block->_id."]. Rollback FAILED. Explorer RE-SYNC required.";
                    (string)$log_record->object_class = "BLOCK";
                    (string)$log_record->object_id = $block->_id;
                    (int)$log_record->level = 2;
                    $res = mongo_recordLogData($log_record);
                    print("\n\n$log_record->description\n");
                    die();
                    //return false;
                }
            }
        } else { 
            // LOG rollback failure LOG-2: no rollback data
            $log_record = new stdClass();
            (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
            (string)$log_record->detectiontime_tz = date('c');
            (int)$log_record->block_ix = $block_ix;
            (string)$log_record->detected_by = "blockLoader";
            (string)$log_record->description = "Rollback data missing: orphan block detected: blocks #".$block_ix." [".$block->_id."] but rollback not possible. No rollback information. Explorer RE-SYNC required.";
            (string)$log_record->object_class = "BLOCK";
            (string)$log_record->object_id = $block->_id;
            (int)$log_record->level = 2;
            $res = mongo_recordLogData($log_record);
            print("\n\n$log_record->description\n");
            die();
        }

    }
    function mongo_rollbackTxsV1($block){
        global $dbm;
        global $settings;		
            
        // select block txs by height
        try {
            foreach($block->txs as $tx_id){
                $tx = mongo_getTxByHash($tx_id);
                if (count($tx)>0){
                    foreach($tx[0]->vins as $vin){
                        // setVoutUnspent
                        if (!empty($vin->vout_ref)){
                            mongo_setVoutUnspent($vin->vout_ref);
                        }
                    }
                    // delete tx
                        $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
                        $bulk->delete(['_id' => $tx_id]);
                        $result = $dbm->executeBulkWrite($settings["db_name"] . ".TXS", $bulk);
                }
            }
            return true;
        } catch (Exception $e) {
            $result = $e->getMessage();
            return false;
        }

    }

    // V2 Methods
        function mongo_deleteBlock($block_ix){
            global $dbm;
            global $settings;		
                    
            // prepare mongo record	
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->delete(['block_ix' => $block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKS", $bulk); 
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
            
        }   
        function mongo_rollbackBlockStats($rollback_record){
            global $dbm;
            global $settings;
            
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            //$block_ix = $rollback_record->_id;
            $apply = false;

            if (isset($rollback_record->block_stats->update) && !empty($rollback_record->block_stats->update)){
                $bulk->update(
                    ['_id'=>$rollback_record->block_stats->update->_id],
                    ['$set'=>$rollback_record->block_stats->update],
                    ['upsert'=>false]
                );
                $apply =true;
            } else if (isset($rollback_record->block_stats->delete)){
                $bulk->delete(['_id'=>$rollback_record->block_stats->delete]);
                $apply = true;
            }
    
            if ($apply){
                try {
                    $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKSTATS", $bulk);
                    return true;
                } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                    $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                    return false;
                }
            } else {
                return true;
            }
    
        }
        function mongo_rollbackAssetMoves($block_ix){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);

            $bulk->delete(['block_ix'=>$block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ASSETMOVES", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                return false;
            }
        }
        function mongo_rollbackAddressMoves($block_ix){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
    
            $bulk->delete(['block_ix'=>$block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".ADDRESSMOVES", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                return false;
            }
        }
        function mongo_rollbackTxs($block_ix){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
    
            $bulk->delete(['block_ix'=>$block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".TXS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                return false;
            }
        }
        function mongo_rollbackVouts($block_ix){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
    
            $bulk->delete(['block_ix'=>$block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".VOUTS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                return false;
            }
        }
        function mongo_rollbackAirdrops($block_ix){
            global $dbm;
            global $settings;
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
    
            $bulk->delete(['block_ix'=>$block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".AIRDROPS", $bulk);
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                    // LOG-2: rollback of blockstats failed
                    // $log_record = new stdClass();
                    // (string)$log_record->log_id = $block->block_ix."@".$block->block_time_sec;
                    // (string)$log_record->detectiontime_tz = date('c');
                    // (int)$log_record->block_ix = $block_ix;
                    // (string)$log_record->detected_by = "blockLoader";
                    // (string)$log_record->description = "Rollback of block stats FAILED. Explorer RE-SYNC required.";
                    // (string)$log_record->object_class = "BLOCK";
                    // (string)$log_record->object_id = $block->_id;
                    // (int)$log_record->level = 2;
                    // $res = mongo_recordLogData($log_record);
                    // print("\n\n$log_record->description\n");
                return false;
            }
        }
        function mongo_deleteRollbackRecord($block_ix){
            global $dbm;
            global $settings;		
                    
            // prepare mongo record	
            $bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
            $bulk->delete(['_id' => $block_ix]);
            try {
                $result = $dbm->executeBulkWrite($settings["db_name"] . ".BLOCKROLLBACKS", $bulk); 
                return true;
            } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                $result = $e->getWriteResult();
                return false;
            }
            
        }
?>