<?php

use function Bech32\encodeSegwit;
use Decimal\Decimal;
//require '../settings.php';
global $settings;
require 'explorerCustom.php';
require_once 'loaderShadow.php';

/**
 * Stop the loader on a fault, recording why. Every fatal stop of the block
 * loader ends here (directly, or through the handlers installed by
 * ldr_installStopHandlers()), so it is visible in PROBLEMLOG, in
 * api/monitoring/getsummary and on the status page, not only on a console.
 *
 * CLI runs write a PROBLEMLOG entry flagged loader_stop; web requests that
 * include the loader classes only print. The pid file is released when this
 * process created it, and the exit code is 1.
 *
 * @param string      $description  operator-facing reason
 * @param array       $context      extra diagnostic key => value pairs
 * @param int|null    $block_ix     height being worked on; null = current loader height or -1
 * @param string|null $block_hash   block hash, when known
 * @param string|null $outcome      what the database was left with, printed last
 */
function ldr_abort($description, $context = array(), $block_ix = null, $block_hash = null, $outcome = null){
    $GLOBALS['ldr_stop_handled'] = true;
    if (is_null($block_ix)){
        $block_ix = isset($GLOBALS['ldr_current_height']) ? (int)$GLOBALS['ldr_current_height'] : -1;
    }
    if (PHP_SAPI == 'cli' && function_exists('db_recordLogData')){
        $now = time();
        $log_record = new stdClass();
        $log_record->log_id           = $block_ix."@".$now;
        $log_record->detectiontime_tz = date('c', $now);
        $log_record->detected_at      = $now;
        $log_record->block_ix         = (int)$block_ix;
        $log_record->detected_by      = "blockLoader";
        $log_record->description      = (string)$description;
        $log_record->object_class     = is_null($block_hash) ? "LOADER" : "BLOCK";
        $log_record->object_id        = (string)$block_hash;
        $log_record->level            = 2;
        $log_record->loader_stop      = true;
        if (count($context)>0){
            $log_record->context = $context;
        }
        // Never let a logging failure mask the original fault.
        try { db_recordLogData($log_record); } catch (Throwable $e) {}
    }

    print(PHP_EOL."LOADER STOPPED".($block_ix >= 0 ? " at block ".$block_ix : "").PHP_EOL);
    print("  ".$description.PHP_EOL);
    foreach ($context as $k => $v){
        print("  ".$k.": ".(is_scalar($v) ? $v : json_encode($v)).PHP_EOL);
    }
    if (!is_null($outcome)){ print("  ".$outcome.PHP_EOL); }
    print(PHP_EOL);

    if (!empty($GLOBALS['ldr_owns_pidfile']) && function_exists('deletePidFile')) { @deletePidFile(); }
    exit(1);
}

/**
 * Abort a block before any further mutation. Callers invoke this BEFORE
 * recordBlockData() has started, or after a rollback, unless $outcome says
 * otherwise.
 */
function ldr_abortBlock($block_ix, $block_hash, $description, $context = array(),
                        $outcome = "Nothing was written for this block."){
    ldr_abort($description, $context, $block_ix, $block_hash, $outcome);
}

/** Deliberate exit that is not a fault: stop file, lock held, subscription expired, run finished. */
function ldr_quietExit($message = ''){
    $GLOBALS['ldr_stop_handled'] = true;
    if ($message !== ''){ print($message); }
    exit(0);
}

/**
 * Turn every other way the loader process can end into an ldr_abort(): an
 * uncaught exception, a PHP fatal error, or a die()/exit() in a library.
 * The last console lines are kept as context for the latter.
 */
function ldr_installStopHandlers(){
    $GLOBALS['ldr_stop_handled'] = false;
    $GLOBALS['ldr_output_tail'] = '';
    ob_start(function($buf){
        $GLOBALS['ldr_output_tail'] = substr($GLOBALS['ldr_output_tail'].$buf, -1000);
        return $buf;
    }, 1);
    // Descriptions are published by getsummary; runtime messages stay in the context.
    set_exception_handler(function($e){
        ldr_abort("Uncaught ".get_class($e)." at ".basename($e->getFile()).":".$e->getLine().".",
            array("message" => trim($e->getMessage())));
    });
    register_shutdown_function(function(){
        if (!empty($GLOBALS['ldr_stop_handled'])){ return; }
        $err = error_get_last();
        $context = array();
        if (!is_null($err) && in_array($err['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR))){
            $description = "PHP fatal error at ".basename($err['file']).":".$err['line'].".";
            $context['message'] = $err['message'];
        } else {
            $description = "Loader stopped by die()/exit() outside the stop helpers.";
        }
        $lines = array_values(array_filter(array_map('trim', explode("\n", $GLOBALS['ldr_output_tail'])), 'strlen'));
        if (count($lines)>0){
            $context['last_output'] = implode(" | ", array_slice($lines, -4));
        }
        ldr_abort($description, $context);
    });
}

/**
 * Decide whether VOUTs the database lacks can be repaired by a rollback.
 *
 * Each VOUT must be known to the daemon, confirmed in a block that is
 * loaded with the same hash, and rollback records must cover every height
 * from the lowest such block up to $tip. Returns ['height' => lowest,
 * 'vouts' => [ref => height]] or ['error' => reason].
 */
function ldr_missingVoutRepairTarget($vin_ids, $tip, $already_repaired){
    $heights = array();
    foreach ($vin_ids as $ref => $vin_id){
        if (isset($already_repaired[$ref]) || db_countAutoRollbacksForVout($ref) > 0){
            return array('error' => $ref." is missing again after an auto-rollback");
        }
        $p = explode('-', $ref, 2);
        if (count($p) != 2 || !ctype_xdigit($p[0]) || !ctype_digit($p[1])){
            return array('error' => $ref." is not a txid-n reference");
        }
        $tx = rpc_getrawtransaction($p[0], 1);
        if (!is_object($tx) || !isset($tx->vout) || !is_array($tx->vout)){
            return array('error' => $ref.": transaction unknown to the daemon");
        }
        $found = false;
        foreach ($tx->vout as $o){
            if (isset($o->n) && (int)$o->n === (int)$p[1]){ $found = true; break; }
        }
        if (!$found){
            return array('error' => $ref.": the daemon's transaction has no output ".$p[1]);
        }
        if (empty($tx->blockhash)){
            return array('error' => $ref.": transaction is not confirmed");
        }
        $hdr = rpc_getblockheader($tx->blockhash);
        if (!is_object($hdr) || !isset($hdr->height)){
            return array('error' => $ref.": no header for block ".$tx->blockhash);
        }
        $h = (int)$hdr->height;
        $db_blk = $h <= $tip ? db_getBlockByHeight($h) : array();
        if (count($db_blk) == 0 || $db_blk[0]->_id != $tx->blockhash){
            return array('error' => $ref.": block ".$h." is not loaded with hash ".$tx->blockhash);
        }
        $heights[$ref] = $h;
    }
    $lowest = min($heights);
    $have = db_countRollbackRange($lowest, $tip);
    if ($have != $tip - $lowest + 1){
        return array('error' => "rollback records cover ".$have." of ".($tip - $lowest + 1)." heights ".$lowest."-".$tip);
    }
    return array('height' => $lowest, 'vouts' => $heights);
}

/**
 * Raised instead of the "Unresolved transaction inputs" abort when the loader
 * opted in via Block::$auto_repair_missing_vouts. Nothing has been written for
 * the block at that point. The loader checks the referenced VOUTs against the
 * daemon and either rolls back and re-loads, or calls ldr_abortBlock() with
 * the original description and context.
 */
/*
 * The miner's tag from a coinbase script, or null.
 *
 * A coinbase script holds the BIP34 height, an extranonce, and whatever the miner
 * chose to put there - which by convention identifies the pool: "/avian-sv2/",
 * "F4pool", "pool.rplant.xyz", "/zpool.ca/". This is the only way to attribute
 * blocks from a pool that pays out to a fresh address every block, because there is
 * no stable address for an operator to label.
 *
 * Runs of three or more printable characters are kept and joined with a space. The
 * height and extranonce bytes seldom form one, so what survives is the tag. The
 * extracted text is stored rather than the raw script: it is what a lookup matches
 * against, and it is roughly a tenth of the size over a multi-million-block chain.
 * The cost of that choice is that recovering anything else from the script later
 * would need a reindex.
 */
function ldr_coinbaseTag($hex, $max_len = 120){
    if (!is_string($hex) || $hex === '') { return null; }
    $raw = @hex2bin($hex);
    if ($raw === false || $raw === '') { return null; }
    // BIP34: the script opens with a push of the block height. Its low bytes change
    // every block and are frequently printable, so on a short coinbase they run into
    // the tag - height 5385719 is "f7 2d 52", which is why /avian-sv2/ arrived as
    // "-R/avian-sv2/yyz/". The push opcode gives the length, so dropping it is exact
    // rather than a guess, and anything that is not a plausible height push is left.
    $n = ord($raw[0]);
    if ($n >= 1 && $n <= 8 && strlen($raw) > $n) { $raw = substr($raw, 1 + $n); }
    // Pool software conventionally follows the height with a four-byte timestamp
    // push. Those bytes change every block and can all land in the printable range:
    // block 1000's was 0x612f4851, which read as "QH/a". Drop that push when it is
    // present. A tag never begins with 0x04, so this cannot eat one. The bound is
    // >= because the push is five bytes including its opcode: a script that ends
    // here carries no tag at all, and must yield null rather than its timestamp.
    if (strlen($raw) >= 5 && ord($raw[0]) === 4) { $raw = substr($raw, 5); }
    // The extranonce follows as an eight-byte push: counter and nonce data, which
    // is printable often enough to form a run of its own. It is skipped only when
    // the content is mostly binary, so a pool whose tag is itself eight characters
    // and pushed in this position is not eaten. Nine bytes including the opcode,
    // hence >=, for the same reason as the timestamp bound above.
    if (strlen($raw) >= 9 && ord($raw[0]) === 8) {
        $d = substr($raw, 1, 8);
        if (strlen(preg_replace('/[^\x20-\x7e]/', '', $d)) < 6) { $raw = substr($raw, 9); }
    }
    if (!preg_match_all('/[\x20-\x7e]{3,}/', $raw, $m)) { return null; }
    $tag = trim(implode(' ', $m[0]));
    if ($tag === '') { return null; }
    return substr($tag, 0, $max_len);
}

class LdrMissingVoutsException extends Exception {
    public $block_ix;
    public $block_hash;
    public $description;
    public $context;
    public $vin_ids;                        // ref_vout ("<txid>-<n>") => spending vin id

    public function __construct($block_ix, $block_hash, $description, $context, $vin_ids){
        parent::__construct($description);
        $this->block_ix    = $block_ix;
        $this->block_hash  = $block_hash;
        $this->description = $description;
        $this->context     = $context;
        $this->vin_ids     = $vin_ids;
    }
}

class Block {
    public static $auto_repair_missing_vouts = false;

    public $block_ix;                       // block hight
    public $_id;                            // block hash
    public $block_time_sec      = null;     // block timestamp
    public $block_size          = null;     // block size in bytes
    public $block_version       = null;     // block version
    
    public $difficulty          = null;     // block difficuply
    public $moneysupply;                    // total coin circulation at block height
    public $z_supply            = null;     // zerocoin supply

    public $block_reward;                   // total block reward amount
    public $stake_reward;                   // pos reward amount
    public $pow_reward;                     // pow reward amount
    public $mn_reward;                      // MN reward amount
    public $dev_reward;                     // Dev fee reward
    public $shadow_reward;                  // shadow ledger (Gold Rush) payout amount
    public $shadow_pow_reward;              // shadow ledger payouts credited through the PoW channel
    public $shadow_pos_reward;              // shadow ledger payouts credited through the PoS channel
    public $stake_base;                     // pos vins amount
    public $vins_amount;                    // sum of all vins
    public $vouts_amount;                   // sum of all vouts
    public $move_amount;                    // sum of all movements
    public $fee_amount;                     // sum of all fees

    public $pos_winner          = null;     // pos winner address
    public $pow_winner          = null;     // pow winner address
    public $mn_winner           = null;     // MN winner address
    public $dev_winner          = null;     // Dev fee wallet

    public $tx_count            = 0;        // total number of txs
    public $coinbase_tag        = null;     // miner/pool tag from the coinbase script
    //public $creditPoolBalance   = null;

    public function __construct($block_ix, $empty = false) {
        global $settings;
        global $tt;
        global $pp;
        if (!$empty){
            $tt->createTimer('init_props_p1',2, true);
        }
        $this->moneysupply         = @cdec(0);
        $this->block_reward        = @cdec(0);    // total block reward amount
        $this->stake_reward        = @cdec(0);    // pos reward amount
        $this->pow_reward          = @cdec(0);    // pow reward amount
        $this->mn_reward           = @cdec(0);    // MN reward amount
        $this->dev_reward          = @cdec(0);    // Dev fee reward
        $this->shadow_reward       = @cdec(0);    // shadow ledger (Gold Rush) payout amount
        $this->shadow_pow_reward   = @cdec(0);    // shadow payouts credited through the PoW channel
        $this->shadow_pos_reward   = @cdec(0);    // shadow payouts credited through the PoS channel
        $this->shadow_payouts      = array();     // synthetic payout records anchored to this block
        $this->stake_base          = @cdec(0);    // pos vins amount
        $this->vins_amount         = @cdec(0);    // sum of all vins
        $this->vouts_amount        = @cdec(0);    // sum of all vouts
        $this->move_amount         = @cdec(0);    // sum of all movements
        $this->fee_amount          = @cdec(0);    // sum of all fees

        $this->txs                 = array();     // txs data
        $this->vins                = array();     // vins data 
        $this->vouts               = array();     // vouts data
        $this->movements           = array();     // block coin movements
        $this->asset_movements     = array();     // block asset movements
        $this->assets              = array();     // list of assets
        $this->addresses           = array();     // list of addresses
        $this->addressgroups       = array();     // list of addressgroups
        $this->merged_groups       = array();     // list of merged groups
        $this->master_groups       = array();     // list of master groups
        $this->asset_vins          = array();     // asset vins data
        $this->asset_vouts         = array();     // asset vouts data    
        $this->rollbacks           = array();     // roll-back records
        $this->reward_txs          = array();     // tx data of reward txs
        $this->reward_tx           = null;        // block reward tx -> processed separately
        $this->is_genesis          = false;       // genesis block flag


        $this->block_ix = $block_ix;
        $this->rollbacks['_id'] = $this->block_ix;
        $this->rollbacks['addresses'] = array('update'=>[],'delete'=>[]);
        $this->rollbacks['groups'] = array('update'=>[],'delete'=>[]);
        $this->rollbacks['block_stats'] = array('update'=>[],'delete'=>[]);
        $this->rollbacks['assets'] = array('update'=>[], 'delete'=>[]);
        $this->rollbacks['vouts'] = array('set_unspent'=>[]);

        if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
            $this->rollbacks['airdrops'] = array('set_unclaimed'=>[]);
        }
        
        $this->vout_spent = array();
        $this->block_stats = new stdClass();
        $this->vout_types = array();
        $this->tx_types = array();
        $this->tx_sizes = array();
        $this->cdd = @cdec(0);

        if (!$empty){
            $tt->stopTimer('init_props_p1', null);

            $tt->createTimer('get_block_hash_rpc',2, true);
            $this->_id = rpc_getblockhash($this->block_ix,true);
            if (is_null($this->_id)){
                ldr_abortBlock($this->block_ix, null, "Failed to retrieve the block hash from the daemon.");
            }
            $tt->stopTimer('get_block_hash_rpc', null);

            $tt->createTimer('init_props_p2',2, true);
        }
        
        $this->_id == $settings["genesis_block"] ? $this->is_genesis = true : $this->is_genesis = false;
        if (!$empty){
            $tt->stopTimer('init_props_p2', null);
        }
	}

    public function processBlock(){
        global $settings;
        global $tt;
        global $pp;

        // load block from daemon
        $tt->createTimer('load_block_rpc',1, true);
        $block_data = rpc_getblock($this->_id,true);
        if (is_null($block_data) || !isset($block_data->hash) || $block_data->hash != $this->_id){
            ldr_abortBlock($this->block_ix, $this->_id, "Failed to retrieve the block data from the daemon.");
        }        
        $tt->stopTimer('load_block_rpc', null);

        $this->block_time_sec   = isset($block_data->time) ? $block_data->time : null ;
        $this->block_size       = isset($block_data->size) ? $block_data->size : null ;
        $this->block_version    = isset($block_data->version) ? $block_data->version : null ;

        $this->moneysupply      = isset($block_data->moneysupply) ? @cdec($block_data->moneysupply) : @cdec(0);
        $this->z_supply         = property_exists($block_data,$settings['z_supply_mark']) ? get_object_vars($block_data)[$settings['z_supply_mark']]->total : null;
        $this->difficulty       = isset($block_data->difficulty) ? @cdec($block_data->difficulty) : null;

        $this->tx_count         = isset($block_data->tx) ? count($block_data->tx) : 0 ;

        // select genesis block vs. regular block
        $tt->createTimer('process_block',1, true);
        if ($this->is_genesis){
            if (isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
                $this->processGenesisBlockWithAirdrop($block_data);
            } else {
                $this->processGenesisBlock($block_data);
            }
        } else {
            $this->processRegularBlock($block_data);
        }
        $tt->stopTimer('process_block', null);

        $print_extended_data = $settings['debug_enabled'];
        if ($print_extended_data){
            foreach ($this->txs as $tx){
                print(PHP_EOL . "TX : " . $tx->_id . PHP_EOL);
                foreach ($tx->vins as $vin){
                    print(" ->║    (".str_pad($vin->vin_ix,4," ",STR_PAD_LEFT).") " .str_pad($vin->address,40,' ',STR_PAD_RIGHT)." ".str_pad(number_format($vin->amount,8), 15,' ', STR_PAD_LEFT).PHP_EOL);
                }
                foreach ($tx->vouts as $vout){
                    print("   ║->  (".str_pad($vout->vout_ix,4," ",STR_PAD_LEFT).") " .str_pad($vout->address,40,' ',STR_PAD_RIGHT)." ".str_pad(number_format($vout->amount,8), 15,' ', STR_PAD_LEFT).PHP_EOL);
                }
            }
            if (!$this->is_genesis){
                print(PHP_EOL . "REWARD : " . $this->reward_tx->_id . PHP_EOL);
                foreach ($this->reward_tx->vins as $vin){
                    print(" ->║    (".str_pad($vin->vin_ix,4," ",STR_PAD_LEFT).") " .str_pad($vin->address,40,' ',STR_PAD_RIGHT)." ".str_pad(number_format($vin->amount,8), 15,' ', STR_PAD_LEFT).PHP_EOL);
                }
                foreach ($this->reward_tx->vouts as $vout){
                    print("   ║->  (".str_pad($vout->vout_ix,4," ",STR_PAD_LEFT).") " .str_pad($vout->address,40,' ',STR_PAD_RIGHT)." ".str_pad(number_format($vout->amount,8), 15,' ', STR_PAD_LEFT).PHP_EOL);
                }
            }
            print(PHP_EOL . "BR: " . $this->block_reward . " / PoW: " . $this->pow_reward . " / PoS: ". $this->stake_reward . " / MN: " . $this->mn_reward. " / SB: " .$this->stake_base. PHP_EOL);

            print(" PoW Winner: ".$this->pow_winner.PHP_EOL);
            print(" PoS Winner: ".$this->pos_winner.PHP_EOL);
            print(" MN Winner:  ".$this->mn_winner.PHP_EOL); 
        }
        //////     RECORDING    //////
        $tt->createTimer('record_block_data',1, true);
        $this->recordBlockData();
        $tt->stopTimer('record_block_data', null);
        //////
        if ($print_extended_data){
            print("Processed TXs" );
        }
    }

    private function processGenesisBlock($block_data){
        global $pp;
        foreach($block_data->tx as $n=>$txid){
            $tx = new Tx($txid, $this->block_ix, $n);
            //$tx->readTx();
            $tx->tx_size         = 0;
            $tx->tx_time_sec     = $this->block_time_sec;
            $tx->vins_amount     = @cdec(0);
            $tx->vouts_amount    = @cdec(0);
            $tx->fee_amount      = @cdec(0);
            $tx->move_amount     = @cdec(0);
            $tx->is_reward       = true;
            $tx->reward_type     = 'genesis';
            $tx->is_mn_reward    = false;
            $tx->type            = "genesis";
            $this->txs[$txid] = $tx;
        }
        $this->type="Genesis";
        // collect block stats
        $this->consolidateBlockStats();


    }

    private function processGenesisBlockWithAirdrop($block_data){
        global $tt;
        // pre-process txs
        $tt->createTimer('pre-process_txs',2, true);
        foreach($block_data->tx as $n=>$txid){
            $tx = new Tx($txid, $this->block_ix, $n);
            $tx->readTx($this);
            $tx->is_reward       = true;
            $tx->reward_type     = 'genesis';
            $tx->type            = "genesis";
            $this->txs[$txid] = $tx;
            $this->vins = array_merge($this->vins, $tx->vins);
            $this->vouts = array_merge($this->vouts, $tx->vouts);
            $this->asset_vins = array_merge($this->asset_vins, $tx->asset_vins);
            $this->asset_vouts = array_merge($this->asset_vouts, $tx->asset_vouts);
            if ($tx->is_reward){ 
                if (array_key_exists("reward", $this->tx_types)){
                    $this->tx_types["reward"] += 1;
                } else {
                    $this->tx_types["reward"] = 1;
                }
                if (array_key_exists("reward", $this->tx_sizes)){
                    $this->tx_sizes["reward"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["reward"] = $tx->tx_size;
                }
            }
            $this->vouts_amount = $this->vouts_amount+@cdec($tx->vouts_amount);
        }
        $tt->stopTimer('pre-process_txs', 'tx_count = '.count($block_data->tx));

        // update vins data (get addresses and amounts)
        $tt->createTimer('update_vins_data',2, true);
        $this->updateVinsData();
        $tt->stopTimer('update_vins_data', 'vins_count = '.count($this->vins));
        
        // // process reward tx
        // $tt->createTimer('process_reward_tx',2,true);
        // $this->processRewardTx();
        // $tt->stopTimer('process_reward_tx', null);

        // process regular txs
        $tt->createTimer('process_regular_txs',2, true);
        $this->processGenesisAirdropTxs();
        $tt->stopTimer('process_regular_txs', null);

        // collect block stats
        $tt->createTimer('consolidate_block_stats',2, true);
        $this->consolidateBlockStats();
        $tt->stopTimer('consolidate_block_stats', null);

    }

    private function processRegularBlock($block_data){
        global $tt;

        // additional block data
        if (isset($block_data->cbTx->creditPoolBalance)){
            $this->creditPoolBalance = @cdec($block_data->cbTx->creditPoolBalance);
        }

        // pre-process txs
        $tt->createTimer('pre-process_txs',2, true);
        foreach($block_data->tx as $n=>$txid){
            $tx = new Tx($txid, $this->block_ix, $n);
            $tx->readTx($this);
            $this->txs[$txid] = $tx;
            $this->vins = array_merge($this->vins, $tx->vins);
            $this->vouts = array_merge($this->vouts, $tx->vouts);
            $this->asset_vins = array_merge($this->asset_vins, $tx->asset_vins);
            $this->asset_vouts = array_merge($this->asset_vouts, $tx->asset_vouts);
            if ($tx->is_reward){ 
                array_push($this->reward_txs, $txid); 
                if (array_key_exists("reward", $this->tx_types)){
                    $this->tx_types["reward"] += 1;
                } else {
                    $this->tx_types["reward"] = 1;
                }
                if (array_key_exists("reward", $this->tx_sizes)){
                    $this->tx_sizes["reward"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["reward"] = $tx->tx_size;
                }
            }
            $this->vouts_amount = $this->vouts_amount+@cdec($tx->vouts_amount);
        }
        $tt->stopTimer('pre-process_txs', 'tx_count = '.count($block_data->tx));

        // update vins data (get addresses and amounts)
        $tt->createTimer('update_vins_data',2, true);
        $this->updateVinsData();
        $tt->stopTimer('update_vins_data', 'vins_count = '.count($this->vins));
        
        // process reward tx
        $tt->createTimer('process_reward_tx',2,true);
        $this->processRewardTx();
        $tt->stopTimer('process_reward_tx', null);

        // process regular txs
        $tt->createTimer('process_regular_txs',2, true);
        $this->processRegularTxs();
        $tt->stopTimer('process_regular_txs', null);

        // process shadow ledger (Gold Rush) payouts
        // must run after the regular txs and before loadAddressData(), so that the synthetic
        // movements are picked up by the normal address / group / rollback machinery
        if (shadow_enabled() && shadow_inRange($this->block_ix)){
            $tt->createTimer('process_shadow_payouts',2, true);
            $this->processShadowPayouts();
            $tt->stopTimer('process_shadow_payouts', 'payouts = '.count($this->shadow_payouts));
        }

        // load addresses
        $tt->createTimer('load_address_data',2,true);
        $this->loadAddressData();
        $tt->stopTimer('load_address_data', 'address_count = '. count($this->addresses));

        // load addressgroups
        $tt->createTimer('load_address_groups',2,true);
        $this->loadGroupData();
        $tt->stopTimer('load_address_groups', 'group_count = '. count($this->addressgroups));

        // process movements
        $tt->createTimer('process_movements',2,true);
        $this->processMovements();
        $tt->stopTimer('process_movements', 'moves = '. count($this->movements) . '; asset_moves = '. count($this->asset_movements));

        // merge groups
        $tt->createTimer('merge_groups',2, true);
        $this->mergeGroups();
        $tt->stopTimer('merge_groups', 'merged_groups = '. count($this->merged_groups));

        // collect block stats
        $tt->createTimer('consolidate_block_stats',2, true);
        $this->consolidateBlockStats();
        $tt->stopTimer('consolidate_block_stats', null);

    }

    private function updateVinsData(){
        global $pp;
        global $settings;
        $vin_ids = array();
        
        foreach ($this->vins as $key=>$vin){
            if (isset($vin->ref_vout)){
                // check vin exists in block vouts  
                if (array_key_exists($vin->ref_vout, $this->vouts)){
                    // create vout rollback
                    $this->vout_spent[$vin->ref_vout] = $vin->_id;

                    // update from vouts
                    $vout = $this->vouts[$vin->ref_vout];
                    $vin->address = $vout->address;
                    $vin->type = $vout->type;
                    $vin->amount = @cdec($vout->amount);  
                    $vin->time_sec = $vout->time_sec;
                    $vin->nested_cdd = isset($vout->nested_cdd)? @cdec($vout->nested_cdd) : @cdec(0);
                    if (isset($vout->asset)){ $vin->asset = $vout->asset; }
                    $this->vins[$key] = $vin;
                    $this->txs[$vin->tx_id]->vins[$key] = $vin;
                    $this->txs[$vin->tx_id]->vins_amount = $this->txs[$vin->tx_id]->vins_amount+ @cdec($vin->amount); // Decimal removed fpp
                    $this->vins_amount = $this->vins_amount  + @cdec($vin->amount);  // Decimal removed fpp
                    if ($vout->type == "transfer_asset" || $vout->type=="new_asset" || $vout->type=="reissue_asset"){
                        $asset_vin = $this->asset_vouts[$vin->ref_vout];
                        $this->asset_vins[$key] = $asset_vin;
                        $this->txs[explode('-',$vin->_id,2)[0]]->asset_vins[$key] = $asset_vin;
                    }
                } else {
                    // need to load from DB
                    $vin_ids[$vin->ref_vout] = $vin->_id;
                }
            } else if ($vin->type == "coinbase") {
                $tx = $this->txs[explode('-',$vin->_id,2)[0]];
                $vin->amount = @cdec($tx->vouts_amount); // Decimal removed fpp
                $this->txs[$vin->tx_id]->vins_amount = $this->txs[$vin->tx_id]->vins_amount+ @cdec($vin->amount); // Decimal removed fpp
                $this->vins_amount = $this->vins_amount + @cdec($vin->amount);  // Decimal removed fpp
            } else if ($vin->type == "zerocoin" || $vin->type == "zerocoinmint" || $vin->type == "zerocoinspend"){
                $tx = $this->txs[explode('-',$vin->_id,2)[0]];
                $this->txs[$vin->tx_id]->vins_amount = $this->txs[$vin->tx_id]->vins_amount+ @cdec($vin->amount);
                $this->vins_amount = $this->vins_amount + @cdec($vin->amount); 
            } else if ($vin->type == "shield"){
                // anything to do with shield inputs???
            } else if ($vin->type == "creditpool_unlock"){
                // currently nothing special for the credit pool unlock
            } else {
                ldr_abortBlock($this->block_ix, $this->_id, "Unprocessed VIN type.",
                    array("vin" => (string)$vin->_id, "type" => (string)$vin->type));
            }
            
        }

        // load vins from DB
        if (count($vin_ids)>0){
            $requested_vouts = count($vin_ids);
            $db_vouts = db_getVoutByIdMany(array_keys($vin_ids));
            // A short result means the referenced VOUTs are absent from the
            // database, which points at an earlier block having been written
            // incompletely. Capture the requested/returned counts here: by the
            // time the leftovers are checked below, the airdrop fallback has
            // already consumed part of the set.
            $missing_vouts = $requested_vouts - count($db_vouts);
            foreach ($db_vouts as $vout){
                // create vout rollback

                /// VALIDATE THIS CHANGE!!!!
                $this->vout_spent[$vout->_id] = $vin_ids[$vout->_id]; 
                // previously was
                // $this->vout_spent[$vin->ref_vout] = $vin->_id;

                if (isset($settings["storage_improvement"]) && $settings["storage_improvement"]) {
                    array_push($this->rollbacks['vouts']['set_unspent'],$vout);
                }

                $vin = $this->vins[$vin_ids[$vout->_id]];
                $vin->address = $vout->address;
                $vin->type = $vout->type;
                $vin->amount = @cdec($vout->amount); // Decimal removed fpp
                $vin->time_sec = $vout->time_sec;
                //$vin->nested_cdd = isset($vout->nested_cdd)? @cdec($vout->nested_cdd) : @cdec(0);
                if ($vout->type == "coldstake"){
                    if (isset($vout->staker) && !empty($vout->staker)){$vin->staker = $vout->staker;}
                }
                if (isset($vout->asset)){ 
                    $vin->asset = $vout->asset; 
                    $vin->asset->amount = @cdec($vout->asset->amount);
                }

                $this->vins[$vin->_id] = $vin;
                $this->txs[$vin->tx_id]->vins[$vin->_id] = $vin;
                $this->txs[$vin->tx_id]->vins_amount = $this->txs[$vin->tx_id]->vins_amount+ @cdec($vin->amount);  // Decimal removed fpp
                $this->vins_amount = $this->vins_amount + @cdec($vin->amount);  // Decimal removed fpp
                if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                    $asset_vin = $vout;
                    $this->asset_vins[$key] = $asset_vin;
                    $this->txs[$vin->tx_id]->asset_vins[$key] = $asset_vin;
                }
                unset($vin_ids[$vout->_id]);
            }
        }

        // check for genesis airdrop
        if (count($vin_ids)>0 && isset($settings["genesis_airdrop_enabled"]) && $settings["genesis_airdrop_enabled"]){
            $db_airdrops = db_getAirdropByIdMany(array_keys($vin_ids));
            if (count($db_airdrops)>0){
                $this->claimed_airdrops = array();
            }
            
            foreach ($db_airdrops as $airdrop){
                $this->claimed_airdrops[$airdrop->_id] = $vin_ids[$airdrop->_id];
                array_push($this->rollbacks['airdrops']['set_unclaimed'],$airdrop);

                $vin = $this->vins[$vin_ids[$airdrop->_id]];
                $vin->address = "Airdrop";
                $vin->staker = $airdrop->address;
                $vin->type = "airdrop";
                $vin->amount = @cdec($airdrop->amount);
                $vin->time_sec = $this->block_time_sec;
                if (isset($this->airdrop_amount)){
                    $this->airdrop_amount += $vin->amount;
                } else {
                    $this->airdrop_amount = $vin->amount;
                }
                $this->vins[$vin->_id] = $vin;
                $this->txs[$vin->tx_id]->vins[$vin->_id] = $vin;
                $this->txs[$vin->tx_id]->vins_amount = $this->txs[$vin->tx_id]->vins_amount+ @cdec($vin->amount);
                $this->vins_amount = $this->vins_amount + @cdec($vin->amount);
                unset($vin_ids[$airdrop->_id]);
            }
        }
        // Fail closed on unresolved inputs. Every vin must resolve to a VOUT
        // (in this block, in the database, or to a genesis airdrop); anything
        // left over would otherwise be written with a zero amount and a null
        // address, silently corrupting fees, balances and moneysupply from
        // this height onward.
        if (count($vin_ids)>0){
            $unresolved = array();
            foreach ($vin_ids as $ref_vout => $vin_id){
                $unresolved[] = $ref_vout." (spent by ".$vin_id.")";
            }
            $description = "Unresolved transaction inputs: ".count($vin_ids)." VIN(s) reference VOUTs that are ".
                "not present in the database. An earlier block was most likely stored incompletely; ".
                "roll back to before the referenced VOUTs and re-load.";
            $context = array(
                "unresolved_count"  => count($vin_ids),
                "db_lookup_missing" => isset($missing_vouts) ? $missing_vouts : 0,
                "unresolved_vouts"  => array_slice($unresolved, 0, 25),
            );
            if (self::$auto_repair_missing_vouts){
                throw new LdrMissingVoutsException($this->block_ix, $this->_id, $description, $context, $vin_ids);
            }
            ldr_abortBlock($this->block_ix, $this->_id, $description, $context);
        }


    }

    private function processRewardTx(){
        global $settings;
        global $pp;
        if (count($this->reward_txs) != 1){
            ldr_abortBlock($this->block_ix, $this->_id, "Expected exactly one reward TX.",
                array("reward_txs" => count($this->reward_txs)));
        }
        $tx = $this->txs[$this->reward_txs[0]];
        $this->type = $tx->reward_type;
        if ($tx->reward_type == "PoW"){
            $tx = $this->processRewardTxPoW($tx);
        } else if ($tx->reward_type == "PoS"){
            $tx = $this->processRewardTxPoS($tx);
        }

        // calculate moved amount
        foreach ($tx->movements as $move){
            if ($move->amount > 0){ $tx->move_amount = $tx->move_amount + @cdec($move->amount); }  // Decimal removed fpp
        }

        $tx->fee_amount = @cdec(0);  // Decimal
        $this->reward_tx = $tx;

        $this->move_amount      = $this->move_amount + @cdec($tx->move_amount);  // Decimal removed fpp
        $this->movements        = array_merge($this->movements, $tx->movements);
        $this->asset_movements  = array_merge($this->asset_movements, $tx->asset_movements);

        unset($this->txs[$tx->_id]);
    }
    
    private function processRewardTxPoW($tx){
        global $settings;
        global $pp;
        $reward_vouts = array();
        if ( isset($settings["block_schemes"]["PoW+MLC"]) ) {
            $tx = $this->processRewardTxPoWMLC($tx);
        } else {
            foreach ($tx->vouts as $vout){
                if (($vout->address != 'nulldata' && $vout->address != 'nonstandard') || $vout->amount != 0){
                    $reward_vouts[$vout->vout_ix] = $vout;
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                }
                $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
                if (array_key_exists($vout->type, $this->vout_types)){
                    $this->vout_types[$vout->type]++;
                } else {
                    $this->vout_types[$vout->type]=1;
                }
            }
            ksort($reward_vouts);
            $reward_vouts = array_values($reward_vouts);

            if (count($reward_vouts)==0) {
                // reward not claimed: the coinbase pays only zero-value OP_RETURN outputs
                $tx->is_mn_reward = false;
                $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
            } else if (count($reward_vouts)==1) {
                // PoW only
                $this->pow_reward = $this->pow_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
                $this->pow_winner = $reward_vouts[0]->address;
                $tx->moveAmountTx($this->pow_reward,$this->pow_winner, $tx->_id."-".$this->pow_winner."-pow",true,true,false,false,false,false);

                $tx->is_mn_reward = false;
                $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
            } else if (count($reward_vouts)==2){
                // PoW + MN / PoW + DEV 
                $go_dev = false;
                if (isset($settings['block_schemes']['PoW+DEV'])){
                    // check expected dev vout address in array
                    $rv = array_values($reward_vouts);
                    if ($settings['block_schemes']['PoW+DEV']["DEV_vout"] == "last"){
                        $eaddr = $rv[count($reward_vouts)-1]->address;
                        $dev_vout_ix = $rv[count($reward_vouts)-1]->vout_ix;
                    } else if ($settings['block_schemes']['PoW+DEV']["DEV_vout"] == "first") {
                        $eaddr = $rv[0]->address;
                        $dev_vout_ix = $rv[0]->vout_ix;
                    } else if ($settings['block_schemes']['PoW+DEV']["DEV_vout"] == "second") {
                        $eaddr = $rv[1]->address;
                        $dev_vout_ix = $rv[1]->vout_ix;
                    } else { // DEV can be any
                        $eaddr = '';
                        $dev_vout_ix = -1;
                        foreach ($rv as $rvout){
                            if (in_array($rvout->address,$settings['block_schemes']['PoW+DEV']['DEV_wallets'])){
                                $eaddr = $rvout->address;
                                $dev_vout_ix = $rvout->vout_ix;
                            }
                        }
                    }
                    if (in_array($eaddr, $settings['block_schemes']['PoW+DEV']['DEV_wallets'])) {
                        $go_dev = true;
                    }
                }
                if (isset($settings['block_schemes']['PoW+DEV']['DEV_vout']) && isset($settings['block_schemes']['PoW+DEV']['DEV_wallets']) && $go_dev){   

                    if ($settings['block_schemes']['PoW+DEV']['DEV_vout'] == 'first' && in_array($reward_vouts[0]->address,$settings['block_schemes']['PoW+DEV']['DEV_wallets'])){
                        //print("\nfirst\n");
                        // PoW+DEV / DEV first
                        $this->pow_reward = $this->pow_reward + @cdec($reward_vouts[1]->amount);  // Decimal removed fpp
                        $this->pow_winner = $reward_vouts[1]->address;
                        $tx->moveAmountTx($this->pow_reward,$this->pow_winner, $tx->_id."-".$this->pow_winner."-pow",true,true,false,false,false,false);
        
                        $this->dev_reward = $this->dev_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
                        $this->dev_winner = $reward_vouts[0]->address;
                        $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);
        
                        $tx->is_mn_reward = false;
                        $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                    } else if ($settings['block_schemes']['PoW+DEV']['DEV_vout'] == 'last' && in_array($reward_vouts[1]->address,$settings['block_schemes']['PoW+DEV']['DEV_wallets'])){
                        //print("\nlast\n");
                        // PoW+DEV / DEV last
                        $this->pow_reward = $this->pow_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
                        $this->pow_winner = $reward_vouts[0]->address;
                        $tx->moveAmountTx($this->pow_reward,$this->pow_winner, $tx->_id."-".$this->pow_winner."-pow",true,true,false,false,false,false);
        
                        $this->dev_reward = $this->dev_reward + @cdec($reward_vouts[1]->amount);  // Decimal removed fpp
                        $this->dev_winner = $reward_vouts[1]->address;
                        $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);
        
                        $tx->is_mn_reward = false;
                        $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                    } else {
                        // PoW+DEV / DEV any
                        foreach ($reward_vouts as $rvout){
                            if ($rvout->vout_ix == $dev_vout_ix){
                                $this->dev_reward = $this->dev_reward + @cdec($rvout->amount);  // Decimal removed fpp
                                $this->dev_winner = $rvout->address;
                                $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);
                            } else {
                                $this->pow_reward = $this->pow_reward + @cdec($rvout->amount);  // Decimal removed fpp
                                $this->pow_winner = $rvout->address;
                                $tx->moveAmountTx($this->pow_reward,$this->pow_winner, $tx->_id."-".$this->pow_winner."-pow",true,true,false,false,false,false);
                            }
                        }
                        $tx->is_mn_reward = false;
                        $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                    }
                } else if (isset($settings['block_schemes']['PoW+MN']['MN_vout']) && $settings['block_schemes']['PoW+MN']['MN_vout'] == 'first'){
                    // PoW+MN / MN is first
                    $this->mn_reward = $this->mn_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
                    $this->mn_winner = $reward_vouts[0]->address;
                    $tx->moveAmountTx($this->mn_reward,$this->mn_winner, $tx->_id."-".$this->mn_winner."-mn",true,false,false,true,false,false);
                    unset($reward_vouts[0]);

                    $max_win = -1;
                    $winner = null;
                    foreach ($reward_vouts as $vout){
                        $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_win){ $winner = $vout->address; $max_reward = @cdec($vout->amount); }
                        $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                    }
                    $this->pow_winner = $winner;
                    $tx->is_mn_reward = true;
                    $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);

                } else if (isset($settings['block_schemes']['PoW+MN']['MN_vout']) && $settings['block_schemes']['PoW+MN']['MN_vout'] == 'last'){
                    // PoW+MN / MN is last
                    $this->mn_reward = $this->mn_reward + @cdec($reward_vouts[1]->amount);  // Decimal removed fpp
                    $this->mn_winner = $reward_vouts[1]->address;
                    $tx->moveAmountTx($this->mn_reward,$this->mn_winner, $tx->_id."-".$this->mn_winner."-mn",true,false,false,true,false,false);
                    unset($reward_vouts[1]);

                    $max_win = -1;
                    $winner = null;
                    foreach ($reward_vouts as $vout){
                        $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_win){ $winner = $vout->address; $max_reward = @cdec($vout->amount); }
                        $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                    }
                    $this->pow_winner = $winner;
                    $tx->is_mn_reward = true;
                    $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);

                } else  {
                    // block structure not detected, just take a sum of vouts as simple PoW reward
                    $max_reward = -1;
                    foreach($reward_vouts as $vout){
                        $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_reward){$this->pow_winner = $vout->address; $max_reward = @cdec($vout->amount);}

                        $is_asset_move = false;
                        ////// transfer_asset
                        if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                            $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false, (isset($vout->asset->message)) ? $vout->asset->message : null);
            
                            if (array_key_exists($vout->asset->name, $this->assets)){
                                $asset = $this->assets[$vout->asset->name];
                                $asset->addSupply($vout->asset->amount);
                            } else {
                                $asset = new Asset($vout->asset->name);
                                $asset->loadAsset();
                                if ($asset->is_new){
                                    array_push($this->rollbacks["assets"]["delete"],$asset->_id);
                                } else {
                                    unset($asset->is_new);
                                    $this->rollbacks["assets"]["update"][$asset->_id] = clone($asset);
                                }
                                $asset->addSupply($vout->asset->amount);
                            }
                            if (isset($vout->asset->reissuable)) { $asset->reissuable =  $vout->asset->reissuable; }
                            if (isset($vout->asset->units)) { $asset->units =  $vout->asset->units; }
                            if ($vout->type == "new_asset"){
                                $asset->genesis_tx = $tx->_id;
                                $asset->genesis_timestamp = $tx->tx_time_sec;
                            } else if ($vout->type == "reissue_asset") {
                                // update ipfs on asset re-issue?
            
                            }
                            $this->assets[$vout->asset->name] = $asset;
                            $is_asset_move = true;
            
                            // create Illegal Asset Supply movement //
                            $tx->moveAssetAmountTx(-$vout->asset->amount, $vout->asset->name, "Illegal Supply", $vout->type, $tx->_id."-Illegal_Supply-".$vout->asset->name, true);
                            $tx->moveAmountTx(-$vout->amount,"Illegal Supply", $tx->_id."-Illegal_Supply",false,false,false,false,false,true,$is_asset_move);
                            $tx->suspect = true;
                        }
                        $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pow",true,true,false,false,false,false,$is_asset_move);
                        // ATT: log unexpected pw reward structure

                    }
                    // mark suspect moves
                    if (isset($tx->suspect) && $tx->suspect){
                        foreach($tx->movements as $move){
                                $move->suspect = true;
                        }
                        foreach($tx->asset_movements as $asset_move){
                                $asset_move->suspect = true;
                        }
                    }
                    $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                }
            } else if (count($reward_vouts)>2 ){
                $go_dev = false;
                if (isset($settings['block_schemes']['PoW+MN+DEV'])) {
                    // check expected dev vout address in array
                    $rv = array_values($reward_vouts);
                    if ($settings['block_schemes']['PoW+MN+DEV']["DEV_vout"] == "last"){
                        $eaddr = $rv[count($reward_vouts)-1]->address;
                        $dev_vout_ix = $rv[count($reward_vouts)-1]->vout_ix;
                    } else if ($settings['block_schemes']['PoW+MN+DEV']["DEV_vout"] == "first") {
                        $eaddr = $rv[0]->address;
                        $dev_vout_ix = $rv[0]->vout_ix;
                    } else if ($settings['block_schemes']['PoW+MN+DEV']["DEV_vout"] == "second"){ // DEV second
                        $eaddr = $rv[1]->address;
                        $dev_vout_ix = $rv[1]->vout_ix;
                    } else { // DEV can be any
                        $eaddr = '';
                        $dev_vout_ix = -1;
                        foreach ($rv as $rvout){
                            if (in_array($rvout->address,$settings['block_schemes']['PoW+MN+DEV']['DEV_wallets'])){
                                $eaddr = $rvout->address;
                                $dev_vout_ix = $rvout->vout_ix;
                            }
                        }
                    }
                    if (in_array($eaddr, $settings['block_schemes']['PoW+MN+DEV']['DEV_wallets'])) {
                        $go_dev = true;
                    }
                } else if (isset($settings['block_schemes']['PoW+DEV'])){
                    // check expected dev vout address in array
                    $rv = array_values($reward_vouts);
                    if ($settings['block_schemes']['PoW+DEV']["DEV_vout"] == "last"){
                        $eaddr = $rv[count($reward_vouts)-1]->address;
                        $dev_vout_ix = $rv[count($reward_vouts)-1]->vout_ix;
                    } else if ($settings['block_schemes']['PoW+DEV']["DEV_vout"] == "first") {
                        $eaddr = $rv[0]->address;
                        $dev_vout_ix = $rv[0]->vout_ix;
                    } else if ($settings['block_schemes']['PoW+DEV']["DEV_vout"] == "second") {
                        $eaddr = $rv[1]->address;
                        $dev_vout_ix = $rv[1]->vout_ix;
                    } else { // DEV can be any
                        $eaddr = '';
                        $dev_vout_ix = -1;
                        foreach ($rv as $rvout){
                            if (in_array($rvout->address,$settings['block_schemes']['PoW+DEV']['DEV_wallets'])){
                                $eaddr = $rvout->address;
                                $dev_vout_ix = $rvout->vout_ix;
                            }
                        }
                    }
                    if (in_array($eaddr, $settings['block_schemes']['PoW+DEV']['DEV_wallets'])) {
                        $go_dev = true;
                    }
                }
                if (isset($settings['block_schemes']['PoW+MN+DEV']) && $go_dev) {
                    // process dev fee
                    $dev_vout = $reward_vouts[$dev_vout_ix];

                    $this->dev_reward = $this->dev_reward + @cdec($dev_vout->amount);  // Decimal removed fpp
                    $this->dev_winner = $dev_vout->address;
                    $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);
                    // remove dev_vout from array
                    unset($reward_vouts[$dev_vout_ix]);
                    
                    if ($settings['block_schemes']['PoW+MN+DEV']["MN_vout"] == "last"){
                        krsort($reward_vouts);
                    } else if ($settings['block_schemes']['PoW+MN+DEV']["MN_vout"] == "first") {
                        ksort($reward_vouts);
                    }
                    $reward_vouts = array_values($reward_vouts);

                    // process MN reward
                    $this->mn_reward = $this->mn_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
                    $this->mn_winner = $reward_vouts[0]->address;
                    $tx->moveAmountTx($this->mn_reward,$this->mn_winner, $tx->_id."-".$this->mn_winner."-mn",true,false,false,true,false,false);
                    unset($reward_vouts[0]);
                    $tx->is_mn_reward = true;

                    // remaining is PoW
                    $max_reward = -1;
                    foreach($reward_vouts as $vout){
                        $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_reward) {$this->pow_winner = $vout->address; $max_reward = @cdec($vout->amount);}
                        $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                    }
                    $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                } else if (isset($settings['block_schemes']['PoW+DEV']) && $go_dev) {
                    // process dev fee
                    $dev_vout = $reward_vouts[$dev_vout_ix];

                    $this->dev_reward = $this->dev_reward + @cdec($dev_vout->amount);  // Decimal removed fpp
                    $this->dev_winner = $dev_vout->address;
                    $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);
                    // remove dev_vout from array
                    unset($reward_vouts[$dev_vout_ix]);

                    // remaining is PoW
                    $max_reward = -1;
                    foreach($reward_vouts as $vout){
                        $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_reward) {$this->pow_winner = $vout->address; $max_reward = @cdec($vout->amount);}
                        $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                    }
                    $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);

                } else if (isset($settings['block_schemes']['PoW+MN'])) {
                    if (isset($settings['block_schemes']['PoW+MN']['MN_vout']) && $settings['block_schemes']['PoW+MN']['MN_vout'] == 'first'){
                        // PoW+MN / MN is first
                        $this->mn_reward = $this->mn_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
                        $this->mn_winner = $reward_vouts[0]->address;
                        $tx->moveAmountTx($this->mn_reward,$this->mn_winner, $tx->_id."-".$this->mn_winner."-mn",true,false,false,true,false,false);
                        unset($reward_vouts[0]);

                        $max_win = -1;
                        $winner = null;
                        foreach ($reward_vouts as $vout){
                            $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                            if ($vout->amount > $max_win && $vout->address != "Credit Pool"){ $winner = $vout->address; $max_win = @cdec($vout->amount); }
                            $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                        }
                        $this->pow_winner = $winner;
                        $tx->is_mn_reward = true;
                        $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
        
                    } else if (isset($settings['block_schemes']['PoW+MN']['MN_vout']) && $settings['block_schemes']['PoW+MN']['MN_vout'] == 'last'){
                        // PoW+MN / MN is last
                        $mn_vout_ix = count($reward_vouts)-1;
                        $this->mn_reward = $this->mn_reward + @cdec($reward_vouts[$mn_vout_ix]->amount);  // Decimal removed fpp
                        $this->mn_winner = $reward_vouts[$mn_vout_ix]->address;
                        $tx->moveAmountTx($this->mn_reward,$this->mn_winner, $tx->_id."-".$this->mn_winner."-mn",true,false,false,true,false,false);
                        unset($reward_vouts[$mn_vout_ix]);
                        $max_win = -1;
                        $winner = null;
                        foreach ($reward_vouts as $vout){
                            $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                            if ($vout->amount > $max_win && $vout->address != "Credit Pool"){ $winner = $vout->address; $max_win = @cdec($vout->amount); }
                            $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
                        }
                        $this->pow_winner = $winner;
                        $tx->is_mn_reward = true;
                        $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);

                    }
                } else {
                    $max_win = -1;
                    $winner = null;
                    foreach ($reward_vouts as $vout){
                        $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_win){ $winner = $vout->address; $max_win = @cdec($vout->amount); }

                        $is_asset_move = false;
                        ////// transfer_asset
                        if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                            $options = array();
                            if(isset($vout->asset->message)) { $options["message"] = $vout->asset->message; }
                            $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false,$options);
            
                            if (array_key_exists($vout->asset->name, $this->assets)){
                                $asset = $this->assets[$vout->asset->name];
                                $asset->addSupply($vout->asset->amount);
                            } else {
                                $asset = new Asset($vout->asset->name);
                                $asset->loadAsset();
                                if ($asset->is_new){
                                    array_push($this->rollbacks["assets"]["delete"],$asset->_id);
                                } else {
                                    unset($asset->is_new);
                                    $this->rollbacks["assets"]["update"][$asset->_id] = clone($asset);
                                }
                                $asset->addSupply($vout->asset->amount);
                            }
                            if (isset($vout->asset->reissuable)) { $asset->reissuable =  $vout->asset->reissuable; }
                            if (isset($vout->asset->units)) { $asset->units =  $vout->asset->units; }
                            if ($vout->type == "new_asset"){
                                $asset->genesis_tx = $tx->_id;
                                $asset->genesis_timestamp = $tx->tx_time_sec;
                            } else if ($vout->type == "reissue_asset") {
                                // update ipfs on asset re-issue?
            
                            }
                            $this->assets[$vout->asset->name] = $asset;
                            $is_asset_move = true;
            
                            // create Illegal Asset Supply movement //
                            $tx->moveAssetAmountTx(-$vout->asset->amount, $vout->asset->name, "Illegal Supply", $vout->type, $tx->_id."-Illegal_Supply-".$vout->asset->name, true);
                            $tx->moveAmountTx(-$vout->amount,"Illegal Supply", $tx->_id."-Illegal_Supply",false,false,false,false,false,true,$is_asset_move);
                            $tx->suspect = true;
                        }
                        $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false,$is_asset_move);
                        $tx->is_mn_reward = false;
                    }
                    
                    // mark suspect moves
                    if (isset($tx->suspect) && $tx->suspect){
                        foreach($tx->movements as $move){
                                $move->suspect = true;
                        }
                        foreach($tx->asset_movements as $asset_move){
                                $asset_move->suspect = true;
                        }
                    }
                    $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                    $this->pow_winner = $winner;
                }
                
            } else {
                // ATT: something strange
                ldr_abortBlock($this->block_ix, $this->_id, "Unexpected number of reward VOUTs.",
                    array("txid" => (string)$tx->_id, "reward_vouts" => count($reward_vouts)));
            }
        }

        return $tx;
    }

    private function processRewardTxPoS($tx){
        global $settings;
        global $pp;

        $addr_cdd   = array();
        $addr_ins   = array();
        $addr_outs  = array();

        if ( isset($settings["block_schemes"]["SPoW"]) ){
            $tx = $this->processRewardTxSPOW($tx);
        } else if ( isset($settings["block_schemes"]["PoS+MN+MLC"]) ) {
            $tx = $this->processRewardTxPoSMLC($tx);
        } else {
            $reward_vouts = array();
            $pos_vins = array();
            foreach ($tx->vins as $vin){
                if ($vin->address != 'nulldata' && $vin->address != 'nonstandard'){
                    $pos_vins[$vin->vin_ix] = $vin;
                    $this->pos_winner   =  $vin->address;
                    $this->stake_base   = $this->stake_base + @cdec($vin->amount);  // Decimal removed fpp
                    if ($vin->type == "coldstake"){
                        $tx->moveAmountTx(  -$vin->amount,                      // 1  : amount
                                            $vin->address,                      // 2  : address
                                            $tx->_id."-".$vin->address."-pos",  // 3  : key
                                            true,                               // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            true,                               // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            true,                               // 9  : is_vin
                                            false,                              // 10 : is_asset_move
                                            null,                               // 11 : custom type
                                            $is_coldstake   = true,             // 12 : is_coldstake
                                            $staker         = (isset($vin->staker)) ? $vin->staker : null      // 13 : staker (cold stake)
                                        );

                    } else {
                        $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                    }   

                    // Coin Days Destroyed calculation
                    if (isset($vin->time_sec) && isset($tx->tx_time_sec)){
                        $days = @cdec($tx->tx_time_sec -$vin->time_sec)/(24*60*60);
                        $cdd = $vin->amount * $days;
                        
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_cdd)){
                            $addr_cdd[$vin->address] += $cdd;
                        } else {
                            $addr_cdd[$vin->address] = $cdd;
                        }

                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_ins)){
                            $addr_ins[$vin->address] += $vin->amount;
                        } else {
                            $addr_ins[$vin->address] = $vin->amount;
                        }
                    }
                } else {
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                }
                $this->block_reward = $this->block_reward - @cdec($vin->amount);  // Decimal removed fpp
                $this->stake_reward = $this->stake_reward - @cdec($vin->amount);  // Decimal removed fpp

            }

            foreach ($tx->vouts as $vout){
                if ($vout->address != 'nulldata' && $vout->address != 'nonstandard'){        
                    $reward_vouts[$vout->vout_ix] = $vout;
                    //$reward_addr[$vout_ix] = $vout->address;
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                }
                $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
                if (array_key_exists($vout->type, $this->vout_types)){
                    $this->vout_types[$vout->type]++;
                } else {
                    $this->vout_types[$vout->type]=1;
                }
            }

            if (count($reward_vouts)==1){
                // single vout, no active MN
                $vout = array_values($reward_vouts)[0];
                if ($vout->type == "coldstake"){
                    $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                            $vout->address,                      // 2  : address
                                            $tx->_id."-".$vout->address."-pos",  // 3  : key
                                            true,                               // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            true,                               // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            false,                               // 9  : is_vin
                                            false,                              // 10 : is_asset_move
                                            null,                               // 11 : custom type
                                            $is_coldstake   = true,             // 12 : is_coldstake
                                            $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                        );
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos",true,false,true,false,false,false);
                }
                $this->stake_reward = $this->stake_reward + @cdec($vout->amount);  // Decimal removed fpp

                // CDD related calculation
                if (array_key_exists($vout->address, $addr_outs)){
                    $addr_outs[$vout->address] += $vout->amount;
                } else {
                    $addr_outs[$vout->address] = $vout->amount;
                }

                //update vout in the reward_vouts
                // $reward_vouts[$vout->vout_ix] = $vout; //WHY???

            }
            if (count($reward_vouts)>1){
                $go_dev = false;
                $go_pos_dev = false;

                if (isset($settings['block_schemes']['PoS+DEV'])){
                    // check expected dev vout address in array
                    $rv = array_values($reward_vouts);
                    $eaddr = '';
                    $pos_dev_vout_ix = -1;
                    $dev_vout_pos = isset($settings['block_schemes']['PoS+DEV']["DEV_vout"]) ? $settings['block_schemes']['PoS+DEV']["DEV_vout"] : "last";
                    if ($dev_vout_pos == "last"){
                        $eaddr = $rv[count($reward_vouts)-1]->address;
                        $pos_dev_vout_ix = $rv[count($reward_vouts)-1]->vout_ix;
                    } else if ($dev_vout_pos == "first") {
                        $eaddr = $rv[0]->address;
                        $pos_dev_vout_ix = $rv[0]->vout_ix;
                    } else { // DEV can be any
                        foreach ($rv as $rvout){
                            if (in_array($rvout->address,$settings['block_schemes']['PoS+DEV']['DEV_wallets'])){
                                $eaddr = $rvout->address;
                                $pos_dev_vout_ix = $rvout->vout_ix;
                            }
                        }
                    }
                    if (in_array($eaddr, $settings['block_schemes']['PoS+DEV']['DEV_wallets'])) {
                        $go_pos_dev = true;
                    }
                }
                if (isset($settings['block_schemes']['PoS+MN+DEV'])){
                    // check expected dev vout address in array
                    $rv = array_values($reward_vouts);
                    if ($settings['block_schemes']['PoS+MN+DEV']["DEV_vout"] == "last"){
                        $eaddr = $rv[count($reward_vouts)-1]->address;
                        $dev_vout_ix = $rv[count($reward_vouts)-1]->vout_ix;
                    } else if ($settings['block_schemes']['PoS+MN+DEV']["DEV_vout"] == "first") {
                        $eaddr = $rv[0]->address;
                        $dev_vout_ix = $rv[0]->vout_ix;
                    }
                    if (in_array($eaddr, $settings['block_schemes']['PoS+MN+DEV']['DEV_wallets'])) {
                        $go_dev = true;
                    }
                }
                if (isset($settings['block_schemes']['PoS+DEV']) && $go_pos_dev){
                    // PoS + DEV: strip the dev fund vout, everything left is stake reward.
                    // The chain has no masternode concept, so no vout may ever become an MN reward.
                    $dev_vout = $reward_vouts[$pos_dev_vout_ix];
                    $this->dev_reward = $this->dev_reward + @cdec($dev_vout->amount);
                    $this->dev_winner = $dev_vout->address;
                    $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);

                    // remove dev_vout from array
                    unset($reward_vouts[$pos_dev_vout_ix]);
                    ksort($reward_vouts);

                    foreach($reward_vouts as $vout){
                        // pos reward
                        $this->stake_reward = $this->stake_reward + @cdec($vout->amount);
                        if ($vout->type == "coldstake"){
                            $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                                    $vout->address,                     // 2  : address
                                                    $tx->_id."-".$vout->address."-pos", // 3  : key
                                                    true,                               // 4  : is_reward
                                                    false,                              // 5  : is_pow_reward
                                                    true,                               // 6  : is_pos_reward
                                                    false,                              // 7  : is_mn_reward
                                                    false,                              // 8  : is_dev_reward
                                                    false,                              // 9  : is_vin
                                                    false,                              // 10 : is_asset_move
                                                    null,                               // 11 : custom type
                                                    $is_coldstake   = true,             // 12 : is_coldstake
                                                    $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                                );
                        } else {
                            $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                        }

                        // CDD related calculation
                        if (array_key_exists($vout->address, $addr_outs)){
                            $addr_outs[$vout->address] += $vout->amount;
                        } else {
                            $addr_outs[$vout->address] = $vout->amount;
                        }
                    }

                } else if (isset($settings['block_schemes']['PoS+MN+DEV']) && $go_dev){
                    // process dev fee
                    $dev_vout = $reward_vouts[$dev_vout_ix];
                    $this->dev_reward = $this->dev_reward + @cdec($dev_vout->amount);  // Decimal removed fpp
                    $this->dev_winner = $dev_vout->address;
                    $tx->moveAmountTx($this->dev_reward,$this->dev_winner, $tx->_id."-".$this->dev_winner."-dev",true,false,false,false,true,false);

                    // remove dev_vout from array
                    unset($reward_vouts[$dev_vout_ix]);
                    
                    if ($settings['block_schemes']['PoS+MN+DEV']["PoS_vout"] == "last"){
                        krsort($reward_vouts);
                    } else if ($settings['block_schemes']['PoS+MN+DEV']["PoS_vout"] == "first") {
                        ksort($reward_vouts);
                    }

                    $reward_vouts = array_values($reward_vouts); 
                    $pos_vout_type = $reward_vouts[0]->type;

                    foreach($reward_vouts as $vout){
                        if ($vout->type == $pos_vout_type && $vout->address == $this->pos_winner){
                            // pos reward
                            $this->stake_reward = $this->stake_reward + @cdec($vout->amount);  // Decimal removed fpp
                            if ($vout->type == "coldstake"){
                                $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                                        $vout->address,                      // 2  : address
                                                        $tx->_id."-".$vout->address."-pos",  // 3  : key
                                                        true,                               // 4  : is_reward
                                                        false,                              // 5  : is_pow_reward
                                                        true,                               // 6  : is_pos_reward
                                                        false,                              // 7  : is_mn_reward
                                                        false,                              // 8  : is_dev_reward
                                                        false,                               // 9  : is_vin
                                                        false,                              // 10 : is_asset_move
                                                        null,                               // 11 : custom type
                                                        $is_coldstake   = true,             // 12 : is_coldstake
                                                        $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                                    );
                            } else {
                                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                            }

                            // CDD related calculation
                            if (array_key_exists($vout->address, $addr_outs)){
                                $addr_outs[$vout->address] += $vout->amount;
                            } else {
                                $addr_outs[$vout->address] = $vout->amount;
                            }
                        } else {
                            // mn reward
                            $this->mn_reward = $this->mn_reward + @cdec($vout->amount);  // Decimal removed fpp
                            $this->mn_winner = $vout->address;
                            $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mn",true,false,false,true,false,false);
                            $tx->is_mn_reward = true;
                        }
                    }

                } else if (isset($settings['block_schemes']['PoS+MN']['MN_vout'])) { 
                    if ($settings['block_schemes']['PoS+MN']['MN_vout'] == "last" ) {
                        // processing from the top
                        ksort($reward_vouts);
                    } else if ($settings['block_schemes']['PoS+MN']['MN_vout'] == "first" ){
                        // processing from the bottom
                        krsort($reward_vouts);
                    }   

                    $reward_vouts = array_values($reward_vouts); 
                    // more than 1 vout, either stake split or pos+mn
                    $pos_vout_type = $reward_vouts[0]->type;
                
                    foreach($reward_vouts as $vout){
                        if ($vout->type == $pos_vout_type && $vout->address == $this->pos_winner){
                            // pos reward
                            $this->stake_reward = $this->stake_reward + @cdec($vout->amount);  // Decimal removed fpp
                            if ($vout->type == "coldstake"){
                                $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                                        $vout->address,                      // 2  : address
                                                        $tx->_id."-".$vout->address."-pos",  // 3  : key
                                                        true,                               // 4  : is_reward
                                                        false,                              // 5  : is_pow_reward
                                                        true,                               // 6  : is_pos_reward
                                                        false,                              // 7  : is_mn_reward
                                                        false,                              // 8  : is_dev_reward
                                                        false,                               // 9  : is_vin
                                                        false,                              // 10 : is_asset_move
                                                        null,                               // 11 : custom type
                                                        $is_coldstake   = true,             // 12 : is_coldstake
                                                        $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                                    );
                            } else {
                                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                            }
                            // CDD related calculation
                            if (array_key_exists($vout->address, $addr_outs)){
                                $addr_outs[$vout->address] += $vout->amount;
                            } else {
                                $addr_outs[$vout->address] = $vout->amount;
                            }
                        } else {
                            // mn reward
                            $this->mn_reward = $this->mn_reward + @cdec($vout->amount);  // Decimal removed fpp
                            $this->mn_winner = $vout->address;
                            $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mn",true,false,false,true,false,false);
                            $tx->is_mn_reward = true;
                        }
                    }



                } else {
                    // unknown scheme, take all as pos rewards
                    $max_win = -1;
                    $winner = null;
                    foreach($reward_vouts as $vout){
                        // pos reward
                        $this->stake_reward = $this->stake_reward + @cdec($vout->amount);  // Decimal removed fpp
                        if ($vout->amount > $max_win){ $winner = $vout->address; $max_win = @cdec($vout->amount); }
                        if ($vout->type == "coldstake"){
                                $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                                        $vout->address,                      // 2  : address
                                                        $tx->_id."-".$vout->address."-pos",  // 3  : key
                                                        true,                               // 4  : is_reward
                                                        false,                              // 5  : is_pow_reward
                                                        true,                               // 6  : is_pos_reward
                                                        false,                              // 7  : is_mn_reward
                                                        false,                              // 8  : is_dev_reward
                                                        false,                               // 9  : is_vin
                                                        false,                              // 10 : is_asset_move
                                                        null,                               // 11 : custom type
                                                        $is_coldstake   = true,             // 12 : is_coldstake
                                                        $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                                    );
                        } else {    
                            $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                        }
                        
                        // CDD related calculation
                        if (array_key_exists($vout->address, $addr_outs)){
                            $addr_outs[$vout->address] += $vout->amount;
                        } else {
                            $addr_outs[$vout->address] = $vout->amount;
                        }
                    } 
                    $this->pos_winner = $winner;
                }
            }

            // calculate TX CDD
            foreach ($addr_cdd as $addr=>$cdd){
                if (array_key_exists($addr, $addr_outs)){
                    // calc payback proportion
                    $k = 1 - ($addr_outs[$addr] / $addr_ins[$addr]);

                    // check if spent more than received
                    if ($k > 0){
                        // adjust cdd to the proportion of spent amount from the address
                        $cdd = $cdd * $k;
                    } else {
                        $cdd = 0;
                    }
                }
                $tx->cdd += $cdd;
            }

            // add virtual PoS Coinbase vin
            $poscb_vin = new Vin($tx->_id,count($tx->vins),$tx->block_ix);
            $poscb_vin->address     = "PoS Coinbase";
            $poscb_vin->type        = "pos_coinbase";
            $poscb_vin->amount      = $tx->vouts_amount - @cdec($tx->vins_amount);  // Decimal removed fpp
            $tx->vins[$poscb_vin->_id] = $poscb_vin;
            $tx->vins_amount = $tx->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            $tx->moveAmountTx(-$poscb_vin->amount,$poscb_vin->address, $tx->_id."-".$poscb_vin->address."-pos", true,false,false,false,false,true);
            $this->vins[$poscb_vin->_id] = $poscb_vin;
            $this->vins_amount = $this->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp


        }
        $this->cdd += $tx->cdd;
        return $tx;
    }

    private function processRewardTxSPOW($tx){
        global $settings;
        global $pp;

        $addr_cdd   = array();
        $addr_ins   = array();
        $addr_outs  = array();

        $this->spow_reward = new Decimal(0);

        // check that SPoW scheme configured with dev and spow addresses
        if (count($settings["block_schemes"]["SPoW"]["DEV_wallets"])>0 & count($settings["block_schemes"]["SPoW"]["SPOW_wallets"])>0){
            $reward_vouts = array();
            $pos_vins = array();
            foreach ($tx->vins as $vin){
                if ($vin->address != 'nulldata' && $vin->address != 'nonstandard'){
                    $pos_vins[$vin->vin_ix] = $vin;
                    $this->pos_winner   =  $vin->address;
                    $this->stake_base   = $this->stake_base + @cdec($vin->amount);  // Decimal removed fpp
                    if ($vin->type == "coldstake"){
                        $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                                $vin->address,                      // 2  : address
                                                $tx->_id."-".$vin->address."-pos",  // 3  : key
                                                true,                               // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                true,                               // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                true,                               // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                null,                               // 11 : custom type
                                                $is_coldstake   = true,             // 12 : is_coldstake
                                                $staker         = (isset($vin->staker)) ? $vin->staker : null      // 13 : staker (cold stake)
                                            );

                    } else {
                        $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                    }
                    // Coin Days Destroyed calculation
                    if (isset($vin->time_sec) && isset($tx->tx_time_sec)){
                        $days = @cdec($tx->tx_time_sec -$vin->time_sec)/(24*60*60);
                        $cdd = $vin->amount * $days;
                        
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_cdd)){
                            $addr_cdd[$vin->address] += $cdd;
                        } else {
                            $addr_cdd[$vin->address] = $cdd;
                        }

                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_ins)){
                            $addr_ins[$vin->address] += $vin->amount;
                        } else {
                            $addr_ins[$vin->address] = $vin->amount;
                        }
                    }
                } else {
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                }
                $this->block_reward = $this->block_reward - @cdec($vin->amount);  // Decimal removed fpp
                $this->stake_reward = $this->stake_reward - @cdec($vin->amount);  // Decimal removed fpp
            }
            foreach ($tx->vouts as $vout){
                if ($vout->address != 'nulldata' && $vout->address != 'nonstandard'){        
                    $reward_vouts[$vout->vout_ix] = $vout;
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                }
                $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
                if (array_key_exists($vout->type, $this->vout_types)){
                    $this->vout_types[$vout->type]++;
                } else {
                    $this->vout_types[$vout->type]=1;
                }
            }
            // MAIN SPOW PROCESSING
            $reward_vouts = array_values($reward_vouts);
            // processing statics
            /// -1: SPoW
            if (in_array($reward_vouts[count($reward_vouts)-1]->address, $settings["block_schemes"]["SPoW"]["SPOW_wallets"])){
                $vout = $reward_vouts[count($reward_vouts)-1];
                $this->spow_reward = $this->spow_reward + @cdec($vout->amount);
                $this->spow_winner = $vout->address;
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-spow", true,false,false,false,false,false,false,"spow");
                // remove processed vout
                unset($reward_vouts[count($reward_vouts)-1]);
            }
            /// -2: DEV
            if (in_array($reward_vouts[count($reward_vouts)-1]->address, $settings["block_schemes"]["SPoW"]["DEV_wallets"])){
                
                $vout = $reward_vouts[count($reward_vouts)-1];
                $this->dev_reward = $this->dev_reward + @cdec($vout->amount);
                $this->dev_winner = $vout->address;
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-dev", true,false,false,false, true,false,false);
                // remove processed vout
                unset($reward_vouts[count($reward_vouts)-1]);
            }

            // Now only PoS and MN remains
            /// PoS
            $vout = $reward_vouts[0];
            $pos_vout_type = null;
            $pos_split_amount = $vout->amount;
            if ($vout->address == $this->pos_winner){
                $pos_vout_type = $vout->type;
                // pos reward
                $this->stake_reward = $this->stake_reward + @cdec($vout->amount);
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                unset($reward_vouts[0]);
                // CDD related calculation
                if (array_key_exists($vout->address, $addr_outs)){
                    $addr_outs[$vout->address] += $vout->amount;
                } else {
                    $addr_outs[$vout->address] = $vout->amount;
                }
            }
            $next_is_mn = false;
            $x = 1;
            if (count($reward_vouts)>0){
                // Going further 
                $vout = $reward_vouts[$x];
                $split_deviation = ($vout->amount - $pos_split_amount)/$pos_split_amount;
                if ($vout->type == $pos_vout_type && $vout->address == $this->pos_winner && $split_deviation->abs() <= @cdec(0.01)){
                    // Most probably it is stake split: same address, vout type and amount is less than 1% different
                    $this->stake_reward = $this->stake_reward + @cdec($vout->amount);
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                    unset($reward_vouts[$x]);
                    $next_is_mn = true;
                    $x++;
                    // CDD related calculation
                    if (array_key_exists($vout->address, $addr_outs)){
                        $addr_outs[$vout->address] += $vout->amount;
                    } else {
                        $addr_outs[$vout->address] = $vout->amount;
                    }
                } else {
                    // should be MN
                    $this->mn_reward = $this->mn_reward + @cdec($vout->amount);
                    $this->mn_winner = $vout->address;
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mn",true,false,false,true,false,false);
                    $tx->is_mn_reward = true;
                    unset($reward_vouts[$x]);
                    $x++;
                }
            }
            if (count($reward_vouts)>0 && $next_is_mn){
                $vout = $reward_vouts[$x];
                $this->mn_reward = $this->mn_reward + @cdec($vout->amount);
                $this->mn_winner = $vout->address;
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mn",true,false,false,true,false,false);
                $tx->is_mn_reward = true;
                unset($reward_vouts[$x]);
                $x++;
            }
            if (count($reward_vouts)>0){
                // could be only MN Ops
                $vout = $reward_vouts[$x];
                $this->mn_reward = $this->mn_reward + @cdec($vout->amount);
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mn_ops",true,false,false,true,false,false,false,"mn_ops");
                unset($reward_vouts[$x]);
            }

            if (count($reward_vouts)>0){
                // something unexpected
                ldr_abortBlock($this->block_ix, $this->_id, "Unexpected content of the reward TX.",
                    array("txid" => (string)$tx->_id, "unprocessed_vouts" => count($reward_vouts)));
            }

            // add virtual PoS Coinbase vin
            $poscb_vin = new Vin($tx->_id,count($tx->vins),$tx->block_ix);
            $poscb_vin->address     = "PoS Coinbase";
            $poscb_vin->type        = "pos_coinbase";
            $poscb_vin->amount      = $tx->vouts_amount - @cdec($tx->vins_amount);  // Decimal removed fpp
            $tx->vins[$poscb_vin->_id] = $poscb_vin;
            $tx->vins_amount = $tx->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            $tx->moveAmountTx(-$poscb_vin->amount,$poscb_vin->address, $tx->_id."-".$poscb_vin->address."-pos", true,false,false,false,false,true);
            $this->vins[$poscb_vin->_id] = $poscb_vin;
            $this->vins_amount = $this->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            // calculate TX CDD
            foreach ($addr_cdd as $addr=>$cdd){
                if (array_key_exists($addr, $addr_outs)){
                    // calc payback proportion
                    $k = 1 - ($addr_outs[$addr] / $addr_ins[$addr]);

                    // check if spent more than received
                    if ($k > 0){
                        // adjust cdd to the proportion of spent amount from the address
                        $cdd = $cdd * $k;
                    }
                }
                $tx->cdd += $cdd;
            }
        } else {
            //process as simple PoS
            $reward_vouts = array();
            $pos_vins = array();
            foreach ($tx->vins as $vin){
                if ($vin->address != 'nulldata' && $vin->address != 'nonstandard'){
                    $pos_vins[$vin->vin_ix] = $vin;
                    $this->pos_winner   =  $vin->address;
                    $this->stake_base   = $this->stake_base + @cdec($vin->amount);  // Decimal removed fpp
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                    // Coin Days Destroyed calculation
                    if (isset($vin->time_sec) && isset($tx->tx_time_sec)){
                        $days = @cdec($tx->tx_time_sec -$vin->time_sec)/(24*60*60);
                        $cdd = $vin->amount * $days;
                        
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_cdd)){
                            $addr_cdd[$vin->address] += $cdd;
                        } else {
                            $addr_cdd[$vin->address] = $cdd;
                        }
                
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_ins)){
                            $addr_ins[$vin->address] += $vin->amount;
                        } else {
                            $addr_ins[$vin->address] = $vin->amount;
                        }
                    }
                } else {
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                }
                $this->block_reward = $this->block_reward - @cdec($vin->amount);  // Decimal removed fpp
                $this->stake_reward = $this->stake_reward - @cdec($vin->amount);  // Decimal removed fpp
            }
            foreach ($tx->vouts as $vout){
                if ($vout->address != 'nulldata' && $vout->address != 'nonstandard'){        
                    $reward_vouts[$vout->vout_ix] = $vout;   
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                }
                 // CDD related calculation
                 if (array_key_exists($vout->address, $addr_outs)){
                     $addr_outs[$vout->address] += $vout->amount;
                 } else {
                     $addr_outs[$vout->address] = $vout->amount;
                 }
                $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
            }
            $max_win = -1;
            $winner = null;
            foreach($reward_vouts as $vout){
                    // pos reward
                    $this->stake_reward = $this->stake_reward + @cdec($vout->amount);  // Decimal removed fpp
                    if ($vout->amount > $max_win){ $winner = $vout->address; $max_win = @cdec($vout->amount); }
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                // CDD related calculation
                if (array_key_exists($vout->address, $addr_outs)){
                    $addr_outs[$vout->address] += $vout->amount;
                } else {
                    $addr_outs[$vout->address] = $vout->amount;
                }
            } 
            $this->pos_winner = $winner;

            // calculate TX CDD
            foreach ($addr_cdd as $addr=>$cdd){
                if (array_key_exists($addr, $addr_outs)){
                    // calc payback proportion
                    $k = 1 - ($addr_outs[$addr] / $addr_ins[$addr]);
        
                    // check if spent more than received
                    if ($k > 0){
                        // adjust cdd to the proportion of spent amount from the address
                        $cdd = $cdd * $k;
                    } else {
                        $cdd = 0;
                    }
                }
                $tx->cdd += $cdd;
            }
            $poscb_vin = new Vin($tx->_id,count($tx->vins),$tx->block_ix);
            $poscb_vin->address     = "PoS Coinbase";
            $poscb_vin->type        = "pos_coinbase";
            $poscb_vin->amount      = $tx->vouts_amount - @cdec($tx->vins_amount);  // Decimal removed fpp
            $tx->vins[$poscb_vin->_id] = $poscb_vin;
            $tx->vins_amount = $tx->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            $tx->moveAmountTx(-$poscb_vin->amount,$poscb_vin->address, $tx->_id."-".$poscb_vin->address."-pos", true,false,false,false,false,true);
            $this->vins[$poscb_vin->_id] = $poscb_vin;
            $this->vins_amount = $this->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
        }
        return $tx;

    }

    private function processRewardTxPoSMLC($tx){
        // PoS+MN+MLC
        global $settings;
        //global $pp;

        $addr_cdd   = array();
        $addr_ins   = array();
        $addr_outs  = array();

        $this->mlc_reward = new Decimal(0);

        if (count($tx->vouts)>=12){ // proceed as MLC
            $reward_vouts = array();
            $pos_vins = array();

            foreach ($tx->vins as $vin){
                if ($vin->address != 'nulldata' && $vin->address != 'nonstandard'){
                    $pos_vins[$vin->vin_ix] = $vin;
                    $this->pos_winner   =  $vin->address;
                    $this->stake_base   = $this->stake_base + @cdec($vin->amount);  // Decimal removed fpp
                    if ($vin->type == "coldstake"){
                        $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                                $vin->address,                      // 2  : address
                                                $tx->_id."-".$vin->address."-pos",  // 3  : key
                                                true,                               // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                true,                               // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                true,                               // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                null,                               // 11 : custom type
                                                $is_coldstake   = true,             // 12 : is_coldstake
                                                $staker         = (isset($vin->staker)) ? $vin->staker : null      // 13 : staker (cold stake)
                                            );

                    } else {
                        $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                    }
                    // Coin Days Destroyed calculation
                    if (isset($vin->time_sec) && isset($tx->tx_time_sec)){
                        $days = @cdec($tx->tx_time_sec -$vin->time_sec)/(24*60*60);
                        $cdd = $vin->amount * $days;
                        
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_cdd)){
                            $addr_cdd[$vin->address] += $cdd;
                        } else {
                            $addr_cdd[$vin->address] = $cdd;
                        }

                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_ins)){
                            $addr_ins[$vin->address] += $vin->amount;
                        } else {
                            $addr_ins[$vin->address] = $vin->amount;
                        }
                    }
                } else {
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                }
                $this->block_reward = $this->block_reward - @cdec($vin->amount);  // Decimal removed fpp
                $this->stake_reward = $this->stake_reward - @cdec($vin->amount);  // Decimal removed fpp
            }

            foreach ($tx->vouts as $vout){
                if ($vout->address != 'nulldata' && $vout->address != 'nonstandard'){        
                    $reward_vouts[$vout->vout_ix] = $vout;
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                }
                $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
                if (array_key_exists($vout->type, $this->vout_types)){
                    $this->vout_types[$vout->type]++;
                } else {
                    $this->vout_types[$vout->type]=1;
                }
            }

            $reward_vouts = array_values($reward_vouts);
            // PoS+MN+MLC

            // n-1 .. n-10 = MLC
            for ($n = 0; $n < 10; $n++){
                $vout = $reward_vouts[count($reward_vouts)-1];
                $this->mlc_reward = $this->mlc_reward + @cdec($vout->amount);
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mlc", true,false,false,false,false,false,false,"mlc");
                unset($reward_vouts[count($reward_vouts)-1]);
            }

            // Now only PoS and MN remains
            /// 1: PoS
            $vout = $reward_vouts[0];
            $pos_vout_type = null;
            $pos_split_amount = $vout->amount;
            $this->pos_winner = $vout->address; // taking from vout because of the previously existing bug in the coin code
            // pos reward
            $this->stake_reward = $this->stake_reward + @cdec($vout->amount);
            $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
            unset($reward_vouts[0]);
            // CDD related calculation
            if (array_key_exists($vout->address, $addr_outs)){
                $addr_outs[$vout->address] += $vout->amount;
            } else {
                $addr_outs[$vout->address] = $vout->amount;
            }

            if (count($reward_vouts)>0){
                // checkin last one for MN
                $reward_vouts = array_values($reward_vouts);
                $x = count($reward_vouts)-1;
                $vout = $reward_vouts[$x];
                $split_deviation = ($vout->amount - $pos_split_amount)/$pos_split_amount;
                if ($vout->type == $pos_vout_type && $vout->address == $this->pos_winner && $split_deviation->abs() <= @cdec(0.000001)){
                    // Most probably it is stake split: same address, vout type and amount is less than 0.000001 different
                    $this->stake_reward = $this->stake_reward + @cdec($vout->amount);
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                    unset($reward_vouts[$x]);
                    // CDD related calculation
                    if (array_key_exists($vout->address, $addr_outs)){
                        $addr_outs[$vout->address] += $vout->amount;
                    } else {
                        $addr_outs[$vout->address] = $vout->amount;
                    }
                } else {
                    // should be MN
                    $this->mn_reward = $this->mn_reward + @cdec($vout->amount);
                    $this->mn_winner = $vout->address;
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mn",true,false,false,true,false,false);
                    $tx->is_mn_reward = true;
                    unset($reward_vouts[$x]);
                }
                
                // the rest should be PoS
                foreach ($reward_vouts as $vout){
                    $this->stake_reward = $this->stake_reward + @cdec($vout->amount);
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                    unset($reward_vouts[$x]);
                    // CDD related calculation
                    if (array_key_exists($vout->address, $addr_outs)){
                        $addr_outs[$vout->address] += $vout->amount;
                    } else {
                        $addr_outs[$vout->address] = $vout->amount;
                    }
                }
            }
            // add virtual PoS Coinbase vin
            $poscb_vin = new Vin($tx->_id,count($tx->vins),$tx->block_ix);
            $poscb_vin->address     = "PoS Coinbase";
            $poscb_vin->type        = "pos_coinbase";
            $poscb_vin->amount      = $tx->vouts_amount - @cdec($tx->vins_amount);  // Decimal removed fpp
            $tx->vins[$poscb_vin->_id] = $poscb_vin;
            $tx->vins_amount = $tx->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            $tx->moveAmountTx(-$poscb_vin->amount,$poscb_vin->address, $tx->_id."-".$poscb_vin->address."-pos", true,false,false,false,false,true);
            $this->vins[$poscb_vin->_id] = $poscb_vin;
            $this->vins_amount = $this->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            // calculate TX CDD
            foreach ($addr_cdd as $addr=>$cdd){
                if (array_key_exists($addr, $addr_outs)){
                    // calc payback proportion
                    $k = 1 - ($addr_outs[$addr] / $addr_ins[$addr]);

                    // check if spent more than received
                    if ($k > 0){
                        // adjust cdd to the proportion of spent amount from the address
                        $cdd = $cdd * $k;
                    }
                }
                $tx->cdd += $cdd;
            }
        } else {
            //process as simple PoS
            $reward_vouts = array();
            $pos_vins = array();
            foreach ($tx->vins as $vin){
                if ($vin->address != 'nulldata' && $vin->address != 'nonstandard'){
                    $pos_vins[$vin->vin_ix] = $vin;
                    $this->pos_winner   =  $vin->address;
                    $this->stake_base   = $this->stake_base + @cdec($vin->amount);  // Decimal removed fpp
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                    // Coin Days Destroyed calculation
                    if (isset($vin->time_sec) && isset($tx->tx_time_sec)){
                        $days = @cdec($tx->tx_time_sec -$vin->time_sec)/(24*60*60);
                        $cdd = $vin->amount * $days;
                        
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_cdd)){
                            $addr_cdd[$vin->address] += $cdd;
                        } else {
                            $addr_cdd[$vin->address] = $cdd;
                        }
                
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_ins)){
                            $addr_ins[$vin->address] += $vin->amount;
                        } else {
                            $addr_ins[$vin->address] = $vin->amount;
                        }
                    }
                } else {
                    $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address."-pos", true,false,true,false,false,true);
                }
                $this->block_reward = $this->block_reward - @cdec($vin->amount);  // Decimal removed fpp
                $this->stake_reward = $this->stake_reward - @cdec($vin->amount);  // Decimal removed fpp
            }
            foreach ($tx->vouts as $vout){
                if ($vout->address != 'nulldata' && $vout->address != 'nonstandard'){        
                    $reward_vouts[$vout->vout_ix] = $vout;   
                } else {
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                }
                 // CDD related calculation
                 if (array_key_exists($vout->address, $addr_outs)){
                     $addr_outs[$vout->address] += $vout->amount;
                 } else {
                     $addr_outs[$vout->address] = $vout->amount;
                 }
                $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
            }
            $max_win = -1;
            $winner = null;
            foreach($reward_vouts as $vout){
                    // pos reward
                    $this->stake_reward = $this->stake_reward + @cdec($vout->amount);  // Decimal removed fpp
                    if ($vout->amount > $max_win){ $winner = $vout->address; $max_win = @cdec($vout->amount); }
                    $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pos", true,false,true,false,false,false);
                // CDD related calculation
                if (array_key_exists($vout->address, $addr_outs)){
                    $addr_outs[$vout->address] += $vout->amount;
                } else {
                    $addr_outs[$vout->address] = $vout->amount;
                }
            } 
            $this->pos_winner = $winner;

            // calculate TX CDD
            foreach ($addr_cdd as $addr=>$cdd){
                if (array_key_exists($addr, $addr_outs)){
                    // calc payback proportion
                    $k = 1 - ($addr_outs[$addr] / $addr_ins[$addr]);
        
                    // check if spent more than received
                    if ($k > 0){
                        // adjust cdd to the proportion of spent amount from the address
                        $cdd = $cdd * $k;
                    } else {
                        $cdd = 0;
                    }
                }
                $tx->cdd += $cdd;
            }
            $poscb_vin = new Vin($tx->_id,count($tx->vins),$tx->block_ix);
            $poscb_vin->address     = "PoS Coinbase";
            $poscb_vin->type        = "pos_coinbase";
            $poscb_vin->amount      = $tx->vouts_amount - @cdec($tx->vins_amount);  // Decimal removed fpp
            $tx->vins[$poscb_vin->_id] = $poscb_vin;
            $tx->vins_amount = $tx->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
            $tx->moveAmountTx(-$poscb_vin->amount,$poscb_vin->address, $tx->_id."-".$poscb_vin->address."-pos", true,false,false,false,false,true);
            $this->vins[$poscb_vin->_id] = $poscb_vin;
            $this->vins_amount = $this->vins_amount + @cdec($poscb_vin->amount);  // Decimal removed fpp
        }
        
        return $tx;

    }
    private function processRewardTxPoWMLC($tx){
        // PoW+MLC scheme
        global $settings;
        global $pp;
        
        $reward_vouts = array();
        $this->mlc_reward = @cdec(0);

        foreach ($tx->vouts as $vout){
            if (($vout->address != 'nulldata' && $vout->address != 'nonstandard') || $vout->amount != 0){
                $reward_vouts[$vout->vout_ix] = $vout;

            } else {
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-pow",true,true,false,false,false,false);
            }
            $this->block_reward = $this->block_reward + @cdec($vout->amount);  // Decimal removed fpp
            if (array_key_exists($vout->type, $this->vout_types)){
                $this->vout_types[$vout->type]++;
            } else {
                $this->vout_types[$vout->type]=1;
            }
        }
        ksort($reward_vouts);
        $reward_vouts = array_values($reward_vouts);
        if (count($reward_vouts)==0){
            // reward not claimed: the coinbase pays only zero-value OP_RETURN outputs
            $tx->is_mn_reward = false;
            $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
        } else if (count($reward_vouts)>=11){
            // n-1 .. n-11 are MLC
            for ($n = 0; $n < 10; $n++){
                $vout = $reward_vouts[count($reward_vouts)-1];
                $this->mlc_reward = $this->mlc_reward + @cdec($vout->amount);
                $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address."-mlc", true,false,false,false,false,false,false,"mlc");
                unset($reward_vouts[count($reward_vouts)-1]);
            }
            // only PoW remining
            $max_win = -1;
            $winner = null;
            foreach ($reward_vouts as $vout){
                $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                if ($vout->amount > $max_win){ $winner = $vout->address; $max_win = @cdec($vout->amount); }
                $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false,false);
                $tx->is_mn_reward = false;
            }
            $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
            $this->pow_winner = $winner;


        } else if (count($reward_vouts)==1) {
            // PoW only
            $this->pow_reward = $this->pow_reward + @cdec($reward_vouts[0]->amount);  // Decimal removed fpp
            $this->pow_winner = $reward_vouts[0]->address;
            $tx->moveAmountTx($this->pow_reward,$this->pow_winner, $tx->_id."-".$this->pow_winner."-pow",true,true,false,false,false,false);

            $tx->is_mn_reward = false;
            $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
        } else if (count($reward_vouts)>=2 ){
            // process all as PoW
                $max_win = -1;
                $winner = null;
                foreach ($reward_vouts as $vout){
                    $this->pow_reward = $this->pow_reward + @cdec($vout->amount);  // Decimal removed fpp
                    if ($vout->amount > $max_win){ $winner = $vout->address; $max_win = @cdec($vout->amount); }

                    $is_asset_move = false;
                    ////// transfer_asset
                    if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                        $options = array();
                        if(isset($vout->asset->message)) { $options["message"] = $vout->asset->message; }
                        $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false,$options);
        
                        if (array_key_exists($vout->asset->name, $this->assets)){
                            $asset = $this->assets[$vout->asset->name];
                            $asset->addSupply($vout->asset->amount);
                        } else {
                            $asset = new Asset($vout->asset->name);
                            $asset->loadAsset();
                            if ($asset->is_new){
                                array_push($this->rollbacks["assets"]["delete"],$asset->_id);
                            } else {
                                unset($asset->is_new);
                                $this->rollbacks["assets"]["update"][$asset->_id] = clone($asset);
                            }
                            $asset->addSupply($vout->asset->amount);
                        }
                        if (isset($vout->asset->reissuable)) { $asset->reissuable =  $vout->asset->reissuable; }
                        if (isset($vout->asset->units)) { $asset->units =  $vout->asset->units; }
                        if ($vout->type == "new_asset"){
                            $asset->genesis_tx = $tx->_id;
                            $asset->genesis_timestamp = $tx->tx_time_sec;
                        } else if ($vout->type == "reissue_asset") {
                            // update ipfs on asset re-issue?
        
                        }
                        $this->assets[$vout->asset->name] = $asset;
                        $is_asset_move = true;
        
                        // create Illegal Asset Supply movement //
                        $tx->moveAssetAmountTx(-$vout->asset->amount, $vout->asset->name, "Illegal Supply", $vout->type, $tx->_id."-Illegal_Supply-".$vout->asset->name, true);
                        $tx->moveAmountTx(-$vout->amount,"Illegal Supply", $tx->_id."-Illegal_Supply",false,false,false,false,false,true,$is_asset_move);
                        $tx->suspect = true;
                    }
                    $tx->moveAmountTx($vout->amount, $vout->address,$tx->_id."-".$vout->address."-pow",true,true,false,false,false,false,$is_asset_move);
                    $tx->is_mn_reward = false;
                }
                
                // mark suspect moves
                if (isset($tx->suspect) && $tx->suspect){
                    foreach($tx->movements as $move){
                            $move->suspect = true;
                    }
                    foreach($tx->asset_movements as $asset_move){
                            $asset_move->suspect = true;
                    }
                }
                $tx->moveAmountTx(-$this->block_reward,"Coinbase", $tx->_id."-Coinbase",true,false,false,false,false,true);
                $this->pow_winner = $winner;
            
            
        } else {
            // ATT: something strange
            ldr_abortBlock($this->block_ix, $this->_id, "Unexpected number of reward VOUTs.",
                array("txid" => (string)$tx->_id, "reward_vouts" => count($reward_vouts)));
        }

        return $tx;
    }

    private function processGenesisAirdropTxs(){
        $this->airdrops = array();
        foreach ($this->txs as $tx){
            foreach ($tx->vouts as $vout){
                $vout->type = "airdrop";
                $this->airdrops[$vout->_id] = $vout;
            }
            $tx->tx_size         = 0;
            $tx->tx_time_sec     = $this->block_time_sec;
            $tx->vins_amount     = @cdec(0);
            $tx->vouts_amount    = @cdec(0);
            $tx->fee_amount      = @cdec(0);
            $tx->move_amount     = @cdec(0);
            $tx->is_reward       = true;
            $tx->reward_type     = 'genesis';
            $tx->is_mn_reward    = false;
            $tx->type            = "genesis";
            if (isset($tx->op_return)) {$this->has_op_return = true; }
            $this->txs[$tx->_id] = $tx;
        }
        $this->vins_amount     = @cdec(0);
        $this->vouts_amount    = @cdec(0);
        $this->fee_amount      = @cdec(0);
        $this->move_amount     = @cdec(0);
        $this->vins                = array();     // vins data 
        $this->vouts               = array();     // vouts data
        $this->movements           = array();     // block coin movements
        $this->asset_movements     = array();     // block asset movements
        $this->assets              = array();     // list of assets
        $this->addresses           = array();     // list of addresses
        $this->addressgroups       = array();     // list of addressgroups
        $this->merged_groups       = array();     // list of merged groups
        $this->master_groups       = array();     // list of master groups
        $this->asset_vins          = array();     // asset vins data
        $this->asset_vouts         = array();     // asset vouts data           
    }

    private function processRegularTxs(){
        //global $settings;
        //global $pp;
        $addr_cdd   = array();
        $addr_ins   = array();
        $addr_outs  = array();

        foreach ($this->txs as $tx){
            foreach ($tx->vouts as $vout){

                $is_asset_move = false;
                ////// transfer_asset
                if ($vout->type == "transfer_asset" || $vout->type == "new_asset" || $vout->type=="reissue_asset"){
                    $options = array();
                    if (isset($vout->asset->message)) { $options["message"] = $vout->asset->message; }
                    $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false,$options);
                    $is_asset_move = true;
                    if ($vout->type == "transfer_asset") {
                        if ((!isset($tx->type)) || ($tx->type != "new_asset" && $tx->type != "reissue_asset")){
                            $tx->type = "transfer_asset";
                        }
                    } else {
                        $tx->type = $vout->type;
                    }
                }
                // nullassetdata
                if ($vout->type == "nullassetdata"){
                    if ($vout->address != "nullassetdata"){
                        $options = array();
                        if (isset($vout->asset->message)) { $options["message"] = $vout->asset->message; }
                        if (isset($vout->asset->qualifier_type)) { $options["qualifier_type"] = $vout->asset->qualifier_type; }
                        $tx->moveAssetAmountTx($vout->asset->amount, $vout->asset->name, $vout->address, $vout->type, $tx->_id."-".$vout->address."-".$vout->asset->name, false, $options);
                        $is_asset_move = true;
                    }
                }
                

                ////// new_asset -> create phantom Assetbase vin
                if ($vout->type == "new_asset" || $vout->type == "reissue_asset" ){
                    // create/update new asset record
                    
                    if (array_key_exists($vout->asset->name, $this->assets)){
                        $asset = $this->assets[$vout->asset->name];
                        $asset->addSupply($vout->asset->amount);
                    } else {
                        $asset = new Asset($vout->asset->name);
                        $asset->loadAsset();
                        if ($asset->is_new){
                            array_push($this->rollbacks["assets"]["delete"],$asset->_id);
                        } else {
                            unset($asset->is_new);
                            $this->rollbacks["assets"]["update"][$asset->_id] = clone($asset);
                        }
                        $asset->addSupply($vout->asset->amount);
                    }
                    if (isset($vout->asset->reissuable)) { $asset->reissuable =  $vout->asset->reissuable; }
                    if (isset($vout->asset->units)) { $asset->units =  $vout->asset->units; }
                    if ($vout->type == "new_asset"){
                        $asset->genesis_tx = $tx->_id;
                        $asset->genesis_timestamp = $tx->tx_time_sec;
                    } else if ($vout->type == "reissue_asset") {
                        // update ipfs on asset re-issue?

                    }
                    $this->assets[$vout->asset->name] = $asset;
                    
                    // create New Asset movement //
                    $tx->moveAssetAmountTx(-$vout->asset->amount, $vout->asset->name, "New Asset", $vout->type, $tx->_id."-New_Asset-".$vout->asset->name, true);
                    $is_asset_move = true;
                } 

                /////// Cold Stake contract
                if ($vout->type == "coldstake"){
                    $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                            $vout->address,                     // 2  : address
                                            $tx->_id."-".$vout->address,        // 3  : key
                                            false,                              // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            false,                              // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            false,                              // 9  : is_vin
                                            $is_asset_move,                     // 10 : is_asset_move
                                            null,                               // 11 : custom type
                                            $is_coldstake   = true,             // 12 : is_coldstake
                                            $staker         = (isset($vout->staker)) ? $vout->staker : null      // 13 : staker (cold stake)
                                        );
                    $tx->type = "coldstake";

                } else {
                    if ($vout->type == "shield") {
                        $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                                $vout->address,                     // 2  : address
                                                $tx->_id."-".$vout->address,        // 3  : key
                                                false,                              // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                false,                              // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                false,                              // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                $vout->type                         // 11 : custom type
                                            );
                        // remove Shield vouts from list to be recorded
                        unset($this->vouts[$vout->_id]);
                    } else if ($vout->type == "creditpool_lock") {
                        $tx->moveAmountTx(      $vout->amount,                      // 1  : amount
                                                $vout->address,                     // 2  : address
                                                $tx->_id."-".$vout->address,        // 3  : key
                                                false,                              // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                false,                              // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                false,                              // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                $vout->type                         // 11 : custom type
                                            );
                        unset($this->vouts[$vout->_id]);
                    } else {
                        $tx->moveAmountTx($vout->amount,$vout->address, $tx->_id."-".$vout->address,false,false,false,false,false,false,$is_asset_move); 
                    }
                }
                if (array_key_exists($vout->type, $this->vout_types)){
                    $this->vout_types[$vout->type]++;
                } else {
                    $this->vout_types[$vout->type]=1;
                }

                // CDD related calculation
                if (array_key_exists($vout->address, $addr_outs)){
                    $addr_outs[$vout->address] += $vout->amount;
                } else {
                    $addr_outs[$vout->address] = $vout->amount;
                }
            }
            foreach ($tx->vins as $vin){
                $is_asset_move = false;

                ////// transfer_asset
                if ($vin->type == "transfer_asset" || $vin->type == "new_asset" || $vin->type=="reissue_asset"){
                    $tx->moveAssetAmountTx(-$vin->asset->amount, $vin->asset->name, $vin->address, $vin->type, $tx->_id."-".$vin->address."-".$vin->asset->name, true);
                    $is_asset_move = true;
                }
                ///////
                if ($vin->type == "coldstake"){
                    $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                            $vin->address,                      // 2  : address
                                            $tx->_id."-".$vin->address,         // 3  : key
                                            false,                              // 4  : is_reward
                                            false,                              // 5  : is_pow_reward
                                            false,                              // 6  : is_pos_reward
                                            false,                              // 7  : is_mn_reward
                                            false,                              // 8  : is_dev_reward
                                            true,                               // 9  : is_vin
                                            false,                              // 10 : is_asset_move
                                            null,                               // 11 : custom type
                                            $is_coldstake   = true,             // 12 : is_coldstake
                                            $staker         = (isset($vin->staker)) ? $vin->staker : null      // 13 : staker (cold stake)
                                        );
                } else {
                    if ($vin->type == "shield"){
                        $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                                $vin->address,                      // 2  : address
                                                $tx->_id."-".$vin->address,         // 3  : key
                                                false,                              // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                false,                              // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                true,                               // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                $vin->type                          // 11 : custom type
                                            );
                    } else if ($vin->type == "creditpool_unlock"){
                        $tx->moveAmountTx(      -$vin->amount,                      // 1  : amount
                                                $vin->address,                      // 2  : address
                                                $tx->_id."-".$vin->address,         // 3  : key
                                                false,                              // 4  : is_reward
                                                false,                              // 5  : is_pow_reward
                                                false,                              // 6  : is_pos_reward
                                                false,                              // 7  : is_mn_reward
                                                false,                              // 8  : is_dev_reward
                                                true,                               // 9  : is_vin
                                                false,                              // 10 : is_asset_move
                                                $vin->type                          // 11 : custom type
                                            );
                    } else if ($vin->type == "airdrop") {
                        $owner = (isset($vin->staker)) ? $vin->staker : "";
                        $tx->moveAmountTx(      -$vin->amount,                                          // 1  : amount
                                                $vin->address,                                          // 2  : address
                                                $tx->_id."-".$vin->address."-".$owner,                  // 3  : key
                                                false,                                                  // 4  : is_reward
                                                false,                                                  // 5  : is_pow_reward
                                                false,                                                  // 6  : is_pos_reward
                                                false,                                                  // 7  : is_mn_reward
                                                false,                                                  // 8  : is_dev_reward
                                                true,                                                   // 9  : is_vin
                                                false,                                                  // 10 : is_asset_move
                                                $vin->type,                                             // 11 : custom type
                                                $is_coldstake   = false,                                // 12 : is_coldstake
                                                $staker         = (!empty($owner)) ? $owner : null      // 13 : staker (airdrop owner)                                            
                                            );
                    } else {
                        $tx->moveAmountTx(-$vin->amount,$vin->address, $tx->_id."-".$vin->address,false,false,false,false,false,true,$is_asset_move); 
                    }
                }

                if ($vin->type != "shield" && $vin->type != "creditpool_unlock"){
                    // Coin Days Broken calculation
                    if (isset($vin->time_sec) && isset($tx->tx_time_sec)){
                        $days = @cdec($tx->tx_time_sec - $vin->time_sec)/(24*60*60);
                        $cdd = $vin->amount * $days;
                        
                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_cdd)){
                            $addr_cdd[$vin->address] += $cdd;
                        } else {
                            $addr_cdd[$vin->address] = $cdd;
                        }

                        // CDD related calculation
                        if (array_key_exists($vin->address, $addr_ins)){
                            $addr_ins[$vin->address] += $vin->amount;
                        } else {
                            $addr_ins[$vin->address] = $vin->amount;
                        }
                    }
                }
            }

            // calculate TX CDD
            foreach ($addr_cdd as $addr=>$cdd){
                if (array_key_exists($addr, $addr_outs) && $addr_ins[$addr]>0){
                    // calc payback proportion
                    $k = 1 - ($addr_outs[$addr] / $addr_ins[$addr]);

                    // check if spent more than received
                    if ($k > 0){
                        // adjust cdd to the proportion of spent amount from the address
                        $cdd = $cdd * $k;
                    } else {
                        $cdd = 0;
                    }
                }
                $tx->cdd += $cdd;
            }

            // calculate tx fee
            $tx->fee_amount = $tx->vins_amount - @cdec($tx->vouts_amount);  // Decimal removed fpp

            // detect illegal emission
            if ( $tx->fee_amount < 0) {
                // set fee to zero
                    $tx->fee_amount = 0;

                // calculate illegal emission
                    $ie = $tx->vouts_amount - $tx->vins_amount;

                // create illegal emission vin
                    $tx->moveAmountTx(-$ie,"Illegal Supply", $tx->_id."-"."Illegal_Supply",false,false,false,false,false,true,false);
                // mark tx suspect
                    $tx->suspect = true;

                // create problem_log record
                /// ATT: problem lvl 0
            }

            // calculate tx move
            foreach($tx->movements as $move){

                if (isset($tx->suspect) && $tx->suspect){
                    if (is_null($move->address)){
                        $move->address = "{Missing VIN}";
                        $move->suspect = true;
                    } else if($move->address == "Illegal Supply") {
                        $move->suspect = true;
                    }

                }
                if ($move->amount > 0){ 
                    $tx->move_amount = @cdec($tx->move_amount) + @cdec($move->amount); 
                    // mark illegal supply suspect
                    if (isset($tx->suspect) && $tx->suspect){
                        $move->suspect = true;
                    }
                } 
            }

            $this->fee_amount       = @cdec($this->fee_amount) + @cdec($tx->fee_amount);  // Decimal removed fpp
            $this->move_amount      = @cdec($this->move_amount) + @cdec($tx->move_amount);  // Decimal removed fpp
            $this->movements        = array_merge($this->movements, $tx->movements);
            $this->asset_movements  = array_merge($this->asset_movements, $tx->asset_movements);
            if (isset($tx->op_return)) {$this->has_op_return = true; }
            $this->txs[$tx->_id] = $tx;
            $this->cdd += $tx->cdd;
        }

    }

    /**
     * Ingests the shadow ledger (Gold Rush) payouts anchored to this block.
     *
     * Each payout is a real, spendable active-chain UTXO that is never serialised into a block.
     * It carries its own txid and output index, so it is recorded through the ordinary
     * TX / VOUT / MOVEMENT path with a 'synthetic' marker and the anchor height as block_ix.
     * That gives address balances, groups, rich list and block rollback for free, and lets
     * updateVinsData() resolve the payout from the DB once quantum spends activate.
     */
    private function processShadowPayouts(){
        $sb = shadow_loadBlockPayouts($this->block_ix);
        if (is_null($sb) || count($sb->payouts) < 1){ return; }

        $claim_links = shadow_mapPowClaims($sb);

        $this->shadow_scheduled_reward = $sb->scheduled_reward;
        $this->shadow_payouts = $sb->payouts;

        foreach ($sb->payouts as $n => $p){
            if (!isset($p->synthetic_txid) || !isset($p->address) || !isset($p->amount)){
                print(PHP_EOL."Warning: malformed Gold Rush payout at block ".$this->block_ix.", skipped.".PHP_EOL);
                continue;
            }
            $amount   = @cdec($p->amount);
            $vout_ix  = isset($p->vout) ? (int)$p->vout : 0;
            $mode     = isset($p->mode) ? $p->mode : "unknown";

            // synthetic transaction - one payout per txid
            $tx = new Tx($p->synthetic_txid, $this->block_ix, -1);
            $tx->tx_time_sec  = $this->block_time_sec;
            $tx->tx_size      = 0;
            $tx->type         = SHADOW_MOVE_TYPE;
            $tx->synthetic    = true;
            $tx->shadow_mode  = $mode;
            $tx->base_anchor  = (object)[
                "height"      => $this->block_ix,
                "blockhash"   => $this->_id,
                "time"        => $this->block_time_sec,
                "claim_index" => isset($p->index) ? (int)$p->index : $n,
            ];
            if (array_key_exists($p->synthetic_txid, $claim_links)){
                $tx->claim_txid = $claim_links[$p->synthetic_txid];
            }

            // synthetic output
            $vout = new Vout($p->synthetic_txid, $vout_ix, $this->block_ix, $this->block_time_sec);
            $vout->address        = $p->address;
            $vout->type           = shadow_scriptType(isset($p->scriptPubKey) ? $p->scriptPubKey : "");
            $vout->amount         = $amount;
            $vout->nominal_amount = $amount;   // demurrage will make the effective value diverge
            $vout->synthetic      = true;
            $vout->shadow_mode    = $mode;
            $tx->vouts[$vout->_id] = $vout;
            $tx->vouts_count       = 1;
            $tx->vouts_amount      = $amount;

            // virtual emission input, mirroring the "PoS Coinbase" / "Coinbase" pattern
            $vin = new Vin($p->synthetic_txid, 0, $this->block_ix);
            $vin->address  = SHADOW_EMISSION_ADDRESS;
            $vin->type     = SHADOW_MOVE_TYPE;
            $vin->amount   = $amount;
            $vin->time_sec = $this->block_time_sec;
            $tx->vins[$vin->_id] = $vin;
            $tx->vins_count      = 1;
            $tx->vins_amount     = $amount;
            $tx->fee_amount      = @cdec(0);
            $tx->move_amount     = $amount;

            // movements: credit the quantum address, debit the emission source
            $tx->moveAmountTx(  $amount,                                        // 1  : amount
                                $p->address,                                    // 2  : address
                                $tx->_id."-".$p->address."-shadow",             // 3  : key
                                true,                                           // 4  : is_reward
                                ($mode === "pow"),                              // 5  : is_pow_reward
                                ($mode === "pos"),                              // 6  : is_pos_reward
                                false,                                          // 7  : is_mn_reward
                                false,                                          // 8  : is_dev_reward
                                false,                                          // 9  : is_vin
                                false,                                          // 10 : is_asset_move
                                SHADOW_MOVE_TYPE                                // 11 : custom type
                            );
            $tx->moveAmountTx(  -$amount,                                       // 1  : amount
                                SHADOW_EMISSION_ADDRESS,                        // 2  : address
                                $tx->_id."-".SHADOW_EMISSION_ADDRESS."-shadow", // 3  : key
                                true,                                           // 4  : is_reward
                                false,                                          // 5  : is_pow_reward
                                false,                                          // 6  : is_pos_reward
                                false,                                          // 7  : is_mn_reward
                                false,                                          // 8  : is_dev_reward
                                true,                                           // 9  : is_vin
                                false,                                          // 10 : is_asset_move
                                SHADOW_MOVE_TYPE                                // 11 : custom type
                            );

            // shadow issuance is tracked apart from block_reward, which drives the PoW/PoS
            // reward charts and would be swamped by a payout several orders of magnitude larger
            $this->shadow_reward = $this->shadow_reward + $amount;
            if ($mode === "pow"){
                $this->shadow_pow_reward = $this->shadow_pow_reward + $amount;
            } else if ($mode === "pos"){
                $this->shadow_pos_reward = $this->shadow_pos_reward + $amount;
            }

            $this->vouts[$vout->_id] = $vout;
            $this->vouts_amount      = $this->vouts_amount + $amount;
            $this->move_amount       = $this->move_amount + $amount;
            $this->movements         = array_merge($this->movements, $tx->movements);
            $this->txs[$tx->_id]     = $tx;
        }
    }

    private function loadAddressData(){
        global $pp;
        // ----- Loader Optimisation Attempt (SIP-021)-----
        //   bulk read of address data

        $addresses_preload = array();
        foreach ($this->movements as $move){
            if (!array_key_exists($move->address, $addresses_preload)){
                $addresses_preload[$move->address] = null;
            }
        }

        $db_addresses = db_getAddressDataMany(array_keys($addresses_preload));
        foreach ($db_addresses as $db_address){
            $addresses_preload[$db_address->_id] = $db_address;
        }
        
        // ----- eof SIP-021

        // load addresses appearing in movements
        foreach ($this->movements as $move){
            global $settings;
            if (!array_key_exists($move->address, $this->addresses)){
                // adding address
                $addr = new Address($move->address);
                //$addr->loadAddress(); // SIP-021 replaced by checkPreloadedAddress
                $addr->checkPreloadedAddress($addresses_preload);

                // save initial state to address rollbacks  
                if (!$addr->is_new){
                    // existing address, rollback type is  'update'
                    if (!array_key_exists($addr->_id, $this->rollbacks['addresses']['update'])){ 
                        $ax = clone $addr;
                        $ax->stripData();
                        $this->rollbacks['addresses']['update'][$addr->_id] = $ax; 
                    }
                } else {
                    // new address, rollback type 'delete'
                    if (!array_key_exists($addr->_id, $this->rollbacks['addresses']['delete'])){ 
                        array_push($this->rollbacks['addresses']['delete'], $addr->_id); 
                    }
                }
                $this->addresses[$move->address] = $addr;
            }
        }
        // load fee collector if block fees > 0
        if ($this->fee_amount > 0) {
            $addr = new Address("Fee Collector");
            $addr->loadAddress();
            // save initial state to address rollbacks  
            if (!$addr->is_new){
                // existing address, rollback type is  'update'
                if (!array_key_exists($addr->_id, $this->rollbacks['addresses']['update'])){ 
                    $ax = clone $addr;
                    $ax->stripData();
                    $this->rollbacks['addresses']['update'][$addr->_id] = clone $ax; 
                }
            } else {
                // new address, rollback type 'delete'
                if (!array_key_exists($addr->_id, $this->rollbacks['addresses']['delete'])){ 
                    array_push($this->rollbacks['addresses']['delete'], $addr->_id); 
                }
            }
            $this->addresses[$addr->_id] = $addr;
        }
    }

    private function loadGroupData(){
        // ----- Loader Optimisation Attempt (SIP-022)-----
        //   bulk read of addressgroup data

        $groups_preload = array();
        foreach ($this->addresses as $addr){
            if (!array_key_exists($addr->group_id,$groups_preload)){
                $groups_preload[$addr->group_id] = null;
            }
        }
        if (count($groups_preload)>0) {
            $db_groups = db_getAddressGroupMany(array_keys($groups_preload));
            foreach ($db_groups as $db_group){
                $groups_preload[$db_group->_id] = $db_group;
            }
        }
        // ----- eof SIP-022
        // load group data for each address 
        foreach ($this->addresses as $addr){
            if (!array_key_exists($addr->group_id, $this->addressgroups)){
                $grp = new AddressGroup($addr->group_id);
                // $grp->loadGroup(); // SIP-022 replaced by checkPreloadedGroup
                $grp->checkPreloadedGroup($groups_preload);
                $this->addressgroups[$grp->_id] = $grp;

                // create rollback record
                if (!$grp->is_new){
                    // update
                    $gx = clone $grp;
                    $gx->stripData();
                    $this->rollbacks['groups']['update'][$grp->_id] = $gx;
                } else {
                    // delete
                    array_push($this->rollbacks['groups']['delete'],$grp->_id);
                }
            }
        }
    }

    private function processMovements(){ // SIP-030
        // process address moves
        foreach ($this->movements as $move){
            $addr = $this->addresses[$move->address];

            // process movements to addresses
            $addr->balance      = $addr->balance + @cdec($move->amount);  // Decimal removed fpp
            $addr->received     = $addr->received + @cdec($move->vouts_amount);  // Decimal removed fpp
            $addr->sent         = $addr->sent + @cdec($move->vins_amount);  // Decimal removed fpp
            $addr->last_block   = $this->block_ix;
            if ($move->is_reward){
                if ($move->is_pos_reward || $move->is_mn_reward){
                    // pos and mn rewards considered as passive income
                    $addr->passive_in   = $addr->passive_in + @cdec($move->amount);  // Decimal removed fpp
                } else if ($move->is_pow_reward || $move->is_dev_reward) {
                    // pow reward and dev fee considered as deposit
                    $addr->deposited = $addr->deposited + @cdec($move->amount);  // Decimal removed fpp
                } else if (isset($move->type) && ( $move->type == "mlc" )){
                    $addr->passive_in   = $addr->passive_in + @cdec($move->amount);
                }
            } else {
                if ($move->amount > 0){  // Decimal removed fpp
                    $addr->deposited = $addr->deposited + @cdec($move->amount);  // Decimal removed fpp
                } else if ($move->amount < 0) {  // Decimal removed fpp
                    if ($move->tx_id != $this->reward_tx->_id) {
                        $addr->withdrawn = $addr->withdrawn + @cdec($move->amount);  // Decimal removed fpp
                    }
                }
            }
            $this->addresses[$move->address] = $addr;
            $this->addressgroups[$addr->group_id]->balance += @cdec($move->amount);
        }

        // process asset moves
        foreach ($this->asset_movements as $aam){
            if (array_key_exists($aam->address, $this->addresses)){
                $addr = $this->addresses[$aam->address];
            } else {
                // adding address
                $addr = new Address($aam->address);
                $addr->loadAddress();

                if ($aam->address != "New Asset"){
                    ldr_abortBlock($this->block_ix, $this->_id, "Asset movement address was not preloaded in movements.",
                        array("address" => (string)$aam->address, "asset_movement" => json_encode($aam)));
                }
                // save initial state to address rollbacks  
                if (!$addr->is_new){
                    // existing address, rollback type is  'update'
                    if (!array_key_exists($addr->_id, $this->rollbacks['addresses']['update'])){ 
                        $ax = clone $addr;
                        $ax->stripData();
                        $this->rollbacks['addresses']['update'][$addr->_id] = $ax; 
                    }
                } else {
                    // new address, rollback type 'delete'
                    if (!array_key_exists($addr->_id, $this->rollbacks['addresses']['delete'])){ 
                        array_push($this->rollbacks['addresses']['delete'], $addr->_id); 
                    }
                }
                $addr->last_block   = $this->block_ix;
                $this->addresses[$aam->address] = $addr;
            }

            if ($addr->_id != "New Asset"){             
                if (!isset($addr->assets) || is_null($addr->assets)){
                    $addr->assets=array();
                }
                if (array_key_exists(generateHash($aam->name), $addr->assets)){ //asset change
                    $addr->assets[generateHash($aam->name)]->value = @cdec($addr->assets[generateHash($aam->name)]->value) + @cdec($aam->amount);
                } else {
                    $addr->assets[generateHash($aam->name)] = (object) array('name' => $aam->name, 'value' => @cdec($aam->amount));
                }
            } else {
                $addr->assets=array();
            }

            $this->addresses[$aam->address] = $addr;
        }

        // process fee (burning to 'Fee Collector' address)
        if ($this->fee_amount > 0) { 
            $addr = $this->addresses['Fee Collector'];
            $addr->balance      = @cdec($addr->balance) + @cdec($this->fee_amount);  
            $addr->received     = @cdec($addr->received) + @cdec($this->fee_amount); 
            $addr->last_block   = $this->block_ix;
            $this->addresses[$addr->_id] = $addr;
            $this->addressgroups[$addr->group_id]->balance += @cdec($this->fee_amount);
        }
    }

    private function mergeGroups(){
        foreach ($this->txs as $tx){
            if (count($tx->vins)>1){
                $merged_groups = array();
                $master_group = null;
                $max_members = 0;
                foreach ($tx->vins as $vin){
                    if (is_null($vin->address)) {$vin->address = "{Missing VIN}";}
                    if (isset($settings["stop_on_missing_vins"]) && $settings["stop_on_missing_vins"] && $vin->address == "{Missing VIN}"){
                        ldr_abortBlock($this->block_ix, $this->_id, "Missing VIN address (stop_on_missing_vins is set).",
                            array("vin" => (string)$vin->_id, "txid" => (string)$tx->_id));
                    }
                    $ai = $this->addresses[$vin->address];
                    $block_master = null;
                    if (isset($ai->group_id) && $vin->address != "Airdrop"){ 
                        if ($ai->group_id != $master_group){
                            // check group not processed before
                            if (!array_key_exists($ai->group_id, $merged_groups)){

                                // check that group not in block master_groups
                                if (array_key_exists($ai->group_id, $this->master_groups)){
                                    // exist in block masters
                                    $block_master = $ai->group_id;
                                } else if (array_key_exists($ai->group_id, $this->merged_groups)){
                                    // exists in merged
                                    $block_master = $this->merged_groups[$ai->group_id];
                                }
                                if (!empty($block_master) ){
                                    // compare block_master size with max_members
                                    if ($this->addressgroups[$block_master]->address_count >= $max_members){
                                        // block master is bigger
                                        $master_group = $block_master;
                                        $max_members = $this->addressgroups[$block_master]->address_count;
                                        $merged_groups = array_unique(array_merge($merged_groups, $this->master_groups[$master_group]));
                                        unset($this->master_groups[$block_master]);
                                    } else {
                                        // block master is smaller
                                        $merged_groups = array_unique(array_merge($merged_groups, $this->master_groups[$block_master]));
                                        unset($this->master_groups[$block_master]);
                                    }
            
                                } else {
                                    // process first time
                                    if ($this->addressgroups[$ai->group_id]->address_count > $max_members){
                                        $master_group = $ai->group_id;
                                        $max_members = $this->addressgroups[$ai->group_id]->address_count;
                                        $merged_groups[$ai->group_id] = $ai->group_id;
                                    } else {
                                        $merged_groups[$ai->group_id] = $ai->group_id;
                                    }
                                }
                            } 
                        }
                    } else {
                        // no group found!
                        $s = null;
                    }
                }
                // integrate results to block
                if (count($merged_groups)>1) {
                    $this->master_groups[$master_group] = $merged_groups;
                    foreach(array_keys($merged_groups) as $merged_group){
                        if ($merged_group != $master_group) {$this->merged_groups[$merged_group] = $master_group;}
                        // echo "merged: '".$merged_group."' | master: '".$master_group."'\n";
                    }
                }
            }
        }
        // all txs processed
        // post-process merges

        // remove master groups from merged
        foreach (array_keys($this->master_groups) as $master_group){
            unset($this->master_groups[$master_group][$master_group]);
            unset($this->merged_groups[$master_group]);
            // SIP-030 debug
            // echo "master_group '".$master_group."' => merged group count: ". count($this->master_groups[$master_group]) ."\n";
        }

        // remove merged groups from addressgroups
        foreach($this->merged_groups as $merged_group=>$master_group){
            $meg = $this->addressgroups[$merged_group];
            // merge tags
            if (isset($meg->tags) && count($meg->tags)>0){
                if (isset($this->addressgroups[$master_group]->tags)) { // SIP-024
                    $this->addressgroups[$master_group]->tags = array_unique(array_merge($this->addressgroups[$master_group]->tags, $meg->tags));
                } else {
                    $this->addressgroups[$master_group]->tags = array_unique($meg->tags); // SIP-024
                }
            }

            // SIP-030 merge balances & address counts
            $this->addressgroups[$master_group]->balance += $meg->balance;
            $this->addressgroups[$master_group]->address_count += $meg->address_count;
            // eof SIP-030
            
            unset($this->addressgroups[$merged_group]);
        }
    }

    private function consolidateBlockStats() {
        global $settings;
        //global $pp;
        $this->block_stats->_id = $this->block_time_sec  - ($this->block_time_sec % 3600);
        $block_size = is_null($this->block_size) ? 0 : $this->block_size;
        $this->analyseTxTypes();
        // check if _id already exists in db
        $db_bs = db_getBlockStats($this->block_stats->_id);
        if (count($db_bs)>0){
            // record exists, saving in rollback 'update'
            $this->rollbacks['block_stats']['update']= clone $db_bs[0];
            $this->block_stats->_id = $db_bs[0]->_id;
            $this->block_stats->coinsupply          = @cdec($db_bs[0]->coinsupply);
            $this->block_stats->zerocoinsupply      = $db_bs[0]->zerocoinsupply;
            $this->block_stats->coincirculation     = @cdec($db_bs[0]->coincirculation);
            $this->block_stats->blockRewardsTotal   = @cdec($db_bs[0]->blockRewardsTotal);
            $this->block_stats->stakeRewardsTotal   = @cdec($db_bs[0]->stakeRewardsTotal);
            $this->block_stats->stakeBaseTotal      = @cdec($db_bs[0]->stakeBaseTotal);
            $this->block_stats->powRewardsTotal     = @cdec($db_bs[0]->powRewardsTotal);
            $this->block_stats->mnRewardsTotal      = @cdec($db_bs[0]->mnRewardsTotal);
            $this->block_stats->devRewardsTotal     = @cdec($db_bs[0]->devRewardsTotal);
            if (shadow_enabled()){
                $this->block_stats->shadowRewardsTotal  = @cdec(isset($db_bs[0]->shadowRewardsTotal) ? $db_bs[0]->shadowRewardsTotal : 0);
                $this->block_stats->shadowPowTotal      = @cdec(isset($db_bs[0]->shadowPowTotal) ? $db_bs[0]->shadowPowTotal : 0);
                $this->block_stats->shadowPosTotal      = @cdec(isset($db_bs[0]->shadowPosTotal) ? $db_bs[0]->shadowPosTotal : 0);
            }
            $this->block_stats->blocksNumber        = $db_bs[0]->blocksNumber;
            $this->block_stats->addressCount        = isset($db_bs[0]->addressCount) ? $db_bs[0]->addressCount : 0;
            $this->block_stats->groupCount          = isset($db_bs[0]->groupCount) ? $db_bs[0]->groupCount : 0;
            $this->block_stats->txCount             = isset($db_bs[0]->txCount) ? $db_bs[0]->txCount : 0;
            $this->block_stats->assetCount          = isset($db_bs[0]->assetCount) ? $db_bs[0]->assetCount : 0;
            $this->block_stats->blocksSizeTotal     = isset($db_bs[0]->blocksSizeTotal) ? $db_bs[0]->blocksSizeTotal : 0;
            $this->block_stats->lastBlock           = $db_bs[0]->lastBlock;
            $this->block_stats->burned              = @cdec(isset($db_bs[0]->burned) ? $db_bs[0]->burned : 0);
            $this->block_stats->movementsTotal      = @cdec(isset($db_bs[0]->movementsTotal) ? $db_bs[0]->movementsTotal : 0);
            $this->block_stats->txNumber            = isset($db_bs[0]->txNumber) ? $db_bs[0]->txNumber : 0;
            $this->block_stats->blockVersions       = isset($db_bs[0]->blockVersions) ? json_decode(json_encode($db_bs[0]->blockVersions),true) : [] ;
            $this->block_stats->voutTypes           = isset($db_bs[0]->voutTypes) ? json_decode(json_encode($db_bs[0]->voutTypes),true) : [] ;
            $this->block_stats->txTypes             = isset($db_bs[0]->txTypes) ? json_decode(json_encode($db_bs[0]->txTypes),true) : [] ;
            $this->block_stats->txSizes             = isset($db_bs[0]->txSizes) ? json_decode(json_encode($db_bs[0]->txSizes),true) : [] ;
            $this->block_stats->blockTypes          = isset($db_bs[0]->blockTypes) ? json_decode(json_encode($db_bs[0]->blockTypes),true) : [] ;
            $this->block_stats->cdd                 = isset($db_bs[0]->cdd) ? @cdec($db_bs[0]->cdd) : @cdec(0);
            $this->block_stats->feeTotal            = isset($db_bs[0]->feeTotal) ? @cdec($db_bs[0]->feeTotal) : @cdec(0);   // SIP-041
            $this->block_stats->feeSubjectBytes     = isset($db_bs[0]->feeSubjectBytes) ? $db_bs[0]->feeSubjectBytes : 0;   // SIP-041
        } else {
            // record doesn't exists, saving in rollback 'delete'
            $this->rollbacks['block_stats']['delete'] = $this->block_stats->_id;
            $this->block_stats->coinsupply          = @cdec(0);
            $this->block_stats->zerocoinsupply      = 0;
            $this->block_stats->coincirculation     = @cdec(0);
            $this->block_stats->blockRewardsTotal   = @cdec(0);
            $this->block_stats->stakeRewardsTotal   = @cdec(0);
            $this->block_stats->stakeBaseTotal      = @cdec(0);
            $this->block_stats->powRewardsTotal     = @cdec(0);
            $this->block_stats->mnRewardsTotal      = @cdec(0);
            $this->block_stats->devRewardsTotal     = @cdec(0);
            if (shadow_enabled()){
                $this->block_stats->shadowRewardsTotal  = @cdec(0);
                $this->block_stats->shadowPowTotal      = @cdec(0);
                $this->block_stats->shadowPosTotal      = @cdec(0);
            }
            $this->block_stats->blocksNumber        = 0;
            $this->block_stats->addressCount        = 0;
            $this->block_stats->groupCount          = 0;
            $this->block_stats->txCount             = 0;
            $this->block_stats->assetCount          = 0;
            $this->block_stats->blocksSizeTotal     = 0;
            $this->block_stats->lastBlock           = 0;
            $this->block_stats->burned              = @cdec(0);
            $this->block_stats->movementsTotal      = @cdec(0);
            $this->block_stats->txNumber            = 0;
            $this->block_stats->blockVersions       = [];
            $this->block_stats->voutTypes           = [];
            $this->block_stats->txTypes             = [];
            $this->block_stats->txSizes             = [];
            $this->block_stats->blockTypes          = [];
            $this->block_stats->cdd                 = @cdec(0);
            $this->block_stats->feeTotal            = @cdec(0);   // SIP-041
            $this->block_stats->feeSubjectBytes     = 0;          // SIP-041
        }

        $db_cs = db_getDbCoinSupply();
        
        if ($this->moneysupply > 0){
            $this->block_stats->coinsupply          = @cdec($this->moneysupply);
            $this->block_stats->coincirculation     =  @cdec($db_cs->coincirculation) + $this->block_reward; // Decimal removed fpp
            if (isset($this->airdrop_amount)){
                $this->block_stats->coinsupply      += $this->airdrop_amount;
            }
        } else {
            $this->block_stats->coinsupply          = @cdec($db_cs->moneysupply) + $this->block_reward;
            $this->block_stats->coincirculation     = @cdec($db_cs->coincirculation) + $this->block_reward; // Decimal removed fpp
            if (isset($this->airdrop_amount)){
                $this->block_stats->coinsupply      += $this->airdrop_amount;
                $this->block_stats->coincirculation += $this->airdrop_amount;
            }
        }

        // shadow ledger (Gold Rush) issuance is real, spendable supply that never appears in a
        // block, so it is added on top of the base-chain figures rather than through block_reward
        if (shadow_enabled() && shadow_cfg("include_in_supply", true) && $this->shadow_reward > 0){
            $this->block_stats->coinsupply      += $this->shadow_reward;
            $this->block_stats->coincirculation += $this->shadow_reward;
        }

        if (!is_null($this->z_supply)){
            $this->block_stats->zerocoinsupply  = $this->z_supply;
        } else {
            $this->block_stats->zerocoinsupply  = $db_cs->zerocoinsupply;
        }

        $this->block_stats->blockRewardsTotal   = $this->block_stats->blockRewardsTotal + $this->block_reward; // Decimal removed fpp
        $this->block_stats->stakeRewardsTotal   = $this->block_stats->stakeRewardsTotal + $this->stake_reward; // Decimal removed fpp
        $this->block_stats->stakeBaseTotal      = $this->block_stats->stakeBaseTotal + $this->stake_base; // Decimal removed fpp
        $this->block_stats->powRewardsTotal     = $this->block_stats->powRewardsTotal + $this->pow_reward; // Decimal removed fpp
        $this->block_stats->mnRewardsTotal      = $this->block_stats->mnRewardsTotal + $this->mn_reward; // Decimal removed fpp
        $this->block_stats->devRewardsTotal     = $this->block_stats->devRewardsTotal + $this->dev_reward; // Decimal removed fpp
        if (shadow_enabled()){
            $this->block_stats->shadowRewardsTotal  = $this->block_stats->shadowRewardsTotal + $this->shadow_reward;
            $this->block_stats->shadowPowTotal      = $this->block_stats->shadowPowTotal + $this->shadow_pow_reward;
            $this->block_stats->shadowPosTotal      = $this->block_stats->shadowPosTotal + $this->shadow_pos_reward;
        }
        $this->block_stats->blocksNumber        += 1;
        $this->block_stats->lastBlock           = $this->block_ix;
        $this->block_stats->burned              = $db_cs->burned;
        $this->block_stats->movementsTotal      = $this->block_stats->movementsTotal + $this->move_amount;
        $this->block_stats->txNumber             = $this->block_stats->txNumber + $this->tx_count;
        $this->block_stats->blocksSizeTotal     += $block_size;

        if (array_key_exists($this->block_version, $this->block_stats->blockVersions)){
            $this->block_stats->blockVersions[$this->block_version]++;
        } else {
            $this->block_stats->blockVersions[$this->block_version] = 1;
        }

        if (isset($this->vout_types) && isset($this->block_stats->voutTypes)){
            foreach ($this->vout_types as $vout_type=>$n){
                if (array_key_exists($vout_type, $this->block_stats->voutTypes)){
                    $this->block_stats->voutTypes[$vout_type]+=$n;
                } else {
                    $this->block_stats->voutTypes[$vout_type]=$n;
                }
            }
        }
        if (isset($this->tx_types) && isset($this->block_stats->txTypes)){
            foreach ($this->tx_types as $tx_type=>$n){
                if (array_key_exists($tx_type, $this->block_stats->txTypes)){
                    $this->block_stats->txTypes[$tx_type]+=$n;
                } else {
                    $this->block_stats->txTypes[$tx_type]=$n;
                }
            }
        }
        if (isset($this->tx_sizes) && isset($this->block_stats->txSizes)){
            foreach ($this->tx_sizes as $tx_type=>$n){
                if (!in_array($tx_type,['nonstandard','reward','genesis'])){
                    $this->block_stats->feeSubjectBytes += $n;
                }
                if (array_key_exists($tx_type, $this->block_stats->txSizes)){
                    $this->block_stats->txSizes[$tx_type]+=$n;
                } else {
                    $this->block_stats->txSizes[$tx_type]=$n;
                }
            }
        }
        if (isset($this->type) && isset($this->block_stats->blockTypes)){
            if (array_key_exists($this->type, $this->block_stats->blockTypes)){
                $this->block_stats->blockTypes[$this->type]+=1;
            } else {
                $this->block_stats->blockTypes[$this->type]=1;
            }
        }
        $this->block_stats->cdd         += $this->cdd;
        $this->block_stats->feeTotal    += $this->fee_amount;   // SIP-041

        return $this->stripStatsMongo() ;

    }

    private function analyseTxTypes(){
        foreach ($this->txs as $tx) {
            if (count($tx->asset_vouts)>0) {
                // asset tx
                if (array_key_exists($tx->type, $this->tx_types)){
                    $this->tx_types[$tx->type] += 1;
                } else {
                    $this->tx_types[$tx->type] = 1;
                }
                if (array_key_exists($tx->type, $this->tx_sizes)){
                    $this->tx_sizes[$tx->type] += $tx->tx_size;
                } else {
                    $this->tx_sizes[$tx->type] = $tx->tx_size;
                }
            } else if (isset($tx->dip2_type)){
                // dip2 tx
                if (array_key_exists("dip2", $this->tx_types)){
                    $this->tx_types["dip2"] += 1;
                } else {
                    $this->tx_types["dip2"] = 1;
                }
                if (array_key_exists("dip2", $this->tx_sizes)){
                    $this->tx_sizes["dip2"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["dip2"] = $tx->tx_size;
                }
            } else if (isset($tx->type) && $tx->type == "coldstake"){
                // coldstake tx
                if (array_key_exists("coldstake", $this->tx_types)){
                    $this->tx_types["coldstake"] += 1;
                } else {
                    $this->tx_types["coldstake"] = 1;
                }
                if (array_key_exists("coldstake", $this->tx_sizes)){
                    $this->tx_sizes["coldstake"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["coldstake"] = $tx->tx_size;
                }
            } else if (isset($tx->type) && $tx->type == SHADOW_MOVE_TYPE){
                // shadow ledger (Gold Rush) synthetic payout
                if (array_key_exists(SHADOW_MOVE_TYPE, $this->tx_types)){
                    $this->tx_types[SHADOW_MOVE_TYPE] += 1;
                } else {
                    $this->tx_types[SHADOW_MOVE_TYPE] = 1;
                }
                if (array_key_exists(SHADOW_MOVE_TYPE, $this->tx_sizes)){
                    $this->tx_sizes[SHADOW_MOVE_TYPE] += $tx->tx_size;
                } else {
                    $this->tx_sizes[SHADOW_MOVE_TYPE] = $tx->tx_size;
                }
            } else if (isset($tx->type) && $tx->type == "nonstandard"){
                // coldstake tx
                if (array_key_exists("nonstandard", $this->tx_types)){
                    $this->tx_types["nonstandard"] += 1;
                } else {
                    $this->tx_types["nonstandard"] = 1;
                }
                if (array_key_exists("nonstandard", $this->tx_sizes)){
                    $this->tx_sizes["nonstandard"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["nonstandard"] = $tx->tx_size;
                }
            } else if (isset($tx->type) && $tx->type == "genesis"){
                // genesis tx
                if (array_key_exists("genesis", $this->tx_types)){
                    $this->tx_types["genesis"] += 1;
                } else {
                    $this->tx_types["genesis"] = 1;
                }
                if (array_key_exists("genesis", $this->tx_sizes)){
                    $this->tx_sizes["genesis"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["genesis"] = $tx->tx_size;
                }
            } else {
                // regular tx
                if (array_key_exists("regular", $this->tx_types)){
                    $this->tx_types["regular"] += 1;
                } else {
                    $this->tx_types["regular"] = 1;
                }
                if (array_key_exists("regular", $this->tx_sizes)){
                    $this->tx_sizes["regular"] += $tx->tx_size;
                } else {
                    $this->tx_sizes["regular"] = $tx->tx_size;
                }
            }
        }
    }

    private function attachDatabaseStats($tt){
        global $settings;
        global $dbm;
        // addresses
            $tt->createTimer('count_addresses',3,true);
            $z = db_getAddressCount();
            $tt->stopTimer('count_addresses', null);
            $this->block_stats->addressCount = $z;

        // addressgroups
            $tt->createTimer('count_addressgroups',3,true);
            $z = db_getAddressGroupCount() ;
            $tt->stopTimer('count_addressgroups', null);
            $this->block_stats->groupCount = $z;


        // transactions
            $tt->createTimer('count_txs',3,true);
            $z = db_getTxCount() ;
            $tt->stopTimer('count_txs', null);
            $this->block_stats->txCount = $z;

        // assets
            $tt->createTimer('count_assets',3,true);
            $z = db_getAssetCount() ;
            $tt->stopTimer('count_assets', null);
            $this->block_stats->assetCount = $z;
        // asset types
            $tt->createTimer('count_asset_types',3,true);
            $z = db_getAssetTypeCount() ;
            if (count($z) == 0) {$z = new stdClass();}
            $tt->stopTimer('count_asset_types', null);
            $this->block_stats->assetTypes = $z;
        return $tt;
    }

    /**
     * Stop after a failed write inside recordBlockData(). The BLOCKSTATE
     * marker set at its start stays in place, so the next run's
     * recoverUnfinishedBlocks() unwinds the height before loading it again.
     */
    private function abortWrite($step){
        ldr_abortBlock($this->block_ix, $this->_id, $step." failed while writing the block.",
            array("step" => $step),
            "The height may be partially written; its BLOCKSTATE marker stays for the next run's recovery.");
    }

    private function recordBlockData(){
        global $tt;
        global $dbm;
        global $settings;

        // 0. mark the height in flight, before anything is mutated. A crash
        //    from here on leaves a BLOCKSTATE row that startup recovery finds.
        db_setBlockState($this->block_ix, 'pending', $this->_id);

        // 1. record rollback data
        if (isset($settings['rollback_depth']) && $settings['rollback_depth'] > 0) {
            $tt->createTimer('insert_block_rollbacks',2,true);
            $z = db_insertBlockRollbacks($this);
            $tt->stopTimer('insert_block_rollbacks', null);
            if (!$z) { $this->abortWrite("Insert Block Rollback"); }
            // Undo data now exists: a crash past this point is recoverable by
            // rolling the height back rather than by discarding it. The
            // owner-checked transition fails if a recovery has claimed this
            // height away from us -- then nothing further may be written.
            if (!db_transitionBlockState($this->block_ix, 'rollback_ready')){
                ldr_abortBlock($this->block_ix, $this->_id,
                    "Height was claimed by a recovery while this loader was writing it. ".
                    "Stopping before any collection is mutated; the claimer unwinds the height.");
            }
        }
        // 2. insert vouts
            $tt->createTimer('insert_vouts',2,true);
            $z = db_insertVouts($this);
            $tt->stopTimer('insert_vouts', 'vouts_count = ' . count($this->vouts));
            if (!$z) { $this->abortWrite("Insert Vouts"); }

        // 3. update spent vouts
            $tt->createTimer('update_vouts_spent',2,true);
            $z = db_updateVoutsSpent($this);
            $tt->stopTimer('update_vouts_spent', 'vouts_count = ' . count($this->vouts));
            if (!$z) { $this->abortWrite("Update VoutsSpent"); }

          
        // 4. insert airdrops
        if (isset($this->airdrops)){  
            $tt->createTimer('insert_airdrops',2,true);
            $z = db_insertAirdrops($this);
            $tt->stopTimer('insert_airdrops', 'airdrops_count = ' . count($this->airdrops));
            if (!$z) { $this->abortWrite("Insert Airdrops"); }
        }

        // 5. update claimed airdrops
        if (isset($this->claimed_airdrops)){
            $tt->createTimer('update_airdrops_claimed',2,true);
            $z = db_updateAirdropsClaimed($this);
            $tt->stopTimer('update_airdrops_claimed', 'airdrops_count = ' . count($this->claimed_airdrops));
            if (!$z) { $this->abortWrite("Update AirdropsClaimed"); }
        }

        // 4. insert txs
            $tt->createTimer('insert_txs',2,true);
            $z = db_insertTxs($this);
            $tt->stopTimer('insert_txs', null);
            if (!$z) { $this->abortWrite("Insert Txs"); }

        // 5. insert address moves
            $tt->createTimer('insert_address_moves',2,true);
            $z = db_insertAddressMoves($this);
            $tt->stopTimer('insert_address_moves', 'addressmoves_count = '.count($this->movements));
            if (!$z) { $this->abortWrite("Insert AddressMoves"); }

        // 6. insert asset moves
            $tt->createTimer('insert_asset_moves',2,true);
            $z = db_insertAssetMoves($this);
            $tt->stopTimer('insert_asset_moves', 'assetmoves_count = '.count($this->asset_movements));
            if (!$z) { $this->abortWrite("Insert AssetMoves"); }

        // 7. update assets
            $tt->createTimer('update_assets',2,true);
            $z = db_updateAssets($this);
            $tt->stopTimer('update_assets', 'assets_count = '.count($this->assets));
            if (!$z) { $this->abortWrite("Insert Assets"); }

        // 8. update addresses
            $tt->createTimer('update_addresses',2,true);
            $z = db_updateAddresses($this);
            $tt->stopTimer('update_addresses', 'address_count = '.count($this->addresses));
            if (!$z) { $this->abortWrite("Update Addresses"); }

        // 9. update groups
            $tt->createTimer('update_groups',2,true);
            $z = db_updateGroups($this);
            $tt->stopTimer('update_groups', 'update = '.count($this->addressgroups) . ' / delete = ' . count($this->merged_groups));
            if (!$z) { $this->abortWrite("Update Groups"); }

        // 10. attach database_stats
            $tt->createTimer('attach_db_stats',2,true);
            $tt = $this->attachDatabaseStats($tt); //// call for addressesCount, groupsCount, txsCount, assetsCount to database and add to the block_stats
            $tt->stopTimer('attach_db_stats', null);

        // 10. update block_stats
            $tt->createTimer('update_blockstats',2,true);
            $z = db_updateBlockStats($this); 
            $tt->stopTimer('update_blockstats', null);
            if (!$z) { $this->abortWrite("Update BlockStats"); }

        // 11. insert log records
            /// ATT: later need to record log
            global $pLog;
            $pLog->addMetrics([
                "size"      => $this->block_size,
                "txs"       => $this->tx_count,
                "vins"      => count($this->vins),
                "vouts"     => count($this->vouts),
                "addr"      => count($this->addresses),
                "grp"       => count($this->addressgroups),
                "assets"    => count($this->assets),
                "addrmoves" => count($this->movements),
                "assetmoves"=> count($this->asset_movements),
            ]);

        // 11a. verify what actually landed before publishing the commit marker
            $tt->createTimer('verify_written_data',2,true);
            $this->verifyWrittenData();
            $tt->stopTimer('verify_written_data', null);
            // Last fence before the commit marker: if a recovery claimed this
            // height mid-write, its roll-back is (or will be) undoing the data
            // just verified -- publishing the block record now would leave a
            // committed height with no data behind it (a "ghost block").
            if (!db_transitionBlockState($this->block_ix, 'data_written')){
                ldr_abortBlock($this->block_ix, $this->_id,
                    "Height was claimed by a recovery after its data was written. ".
                    "The commit marker was NOT published; the claimer unwinds the height.");
            }

        // 12. insert block data
            $tt->createTimer('insert_block',2,true);
            $z = db_insertBlock($this);
            $tt->stopTimer('insert_block', null);
            if (!$z) { $this->abortWrite("Insert Block"); }

        // 13. commit marker is in place -- the height is no longer in flight
            db_clearBlockState($this->block_ix);
    }

    /**
     * Count what reached the database and compare it with what was parsed.
     *
     * The block record is the loader's commit marker: once it exists, the
     * height counts as done and the loader moves on. Bulk writes here are
     * unordered, so a bulk can fail after partially applying -- this is the
     * last point at which such a block can still be refused. Runs before
     * db_insertBlock() so an abort leaves the height uncommitted and the
     * existing incomplete-block recovery can undo it.
     */
    private function verifyWrittenData(){
        global $settings;
        $mismatch = array();

        // mongo_countDbCollectionQuery() reports an empty result as -1 rather
        // than 0; without this a block with no movements would fail below.
        $count = function($n){ return ($n < 0) ? 0 : (int)$n; };

        $storage_improvement = (isset($settings["storage_improvement"]) && $settings["storage_improvement"]);

        // VOUTS. Two reasons a parsed output never appears in the collection,
        // both of which must be mirrored here or the count is meaningless:
        //  - db_insertVouts() skips carrier outputs entirely, whatever the
        //    storage_improvement setting (see mongo_insertVouts);
        //  - with storage_improvement, an output created and spent inside this
        //    same block is written and then deleted again by db_updateVoutsSpent.
        $unstored = db_unstoredVoutAddresses();
        $vouts_expected = 0;
        foreach ($this->vouts as $vout_id => $vout){
            if (in_array($vout->address, $unstored, true)){ continue; }
            if ($storage_improvement && isset($this->vout_spent[$vout_id])){ continue; }
            $vouts_expected++;
        }
        $vouts_actual = $count(db_getVoutCountByBlockIx($this->block_ix));
        if ($vouts_actual != $vouts_expected){
            $mismatch["vouts"] = "expected ".$vouts_expected.", found ".$vouts_actual;
        }

        // TXS. The reward tx is moved out of $txs into $reward_tx by
        // processRewardTx(), and both are written by db_insertTxs().
        $txs_expected = count($this->txs) + (is_null($this->reward_tx) ? 0 : 1);
        $txs_actual = $count(db_getTxCountByBlockIx($this->block_ix));
        if ($txs_actual != $txs_expected){
            $mismatch["txs"] = "expected ".$txs_expected.", found ".$txs_actual;
        }
        // Cross-check the parse against the daemon's own tx list. Synthetic
        // shadow-ledger (Gold Rush) txs are injected by processShadowPayouts()
        // and are deliberately absent from the daemon's block tx array
        // (getgoldrushblock: "not entries in the legacy block transaction
        // array"), so they are excluded from this one comparison. They stay
        // counted in the txs check above, where the database mirrors the parse.
        $synthetic_txs = 0;
        foreach ($this->txs as $parsed_tx){
            if (isset($parsed_tx->synthetic) && $parsed_tx->synthetic){ $synthetic_txs++; }
        }
        $daemon_txs_parsed = $txs_expected - $synthetic_txs;
        if ($this->tx_count > 0 && $daemon_txs_parsed != $this->tx_count){
            $mismatch["tx_parse"] = "daemon reported ".$this->tx_count." txs, parsed ".$daemon_txs_parsed;
        }

        // ADDRESSMOVES are inserted (not upserted), so a duplicate count here
        // also catches a block being re-processed without a prior rollback.
        $moves_expected = count($this->movements);
        $moves_actual = $count(db_getAddressMovesCountByBlockIx($this->block_ix));
        if ($moves_actual != $moves_expected){
            $mismatch["address_moves"] = "expected ".$moves_expected.", found ".$moves_actual;
        }

        if (count($mismatch)>0){
            $context = $mismatch;
            $context["storage_improvement"] = $storage_improvement ? "on" : "off";
            ldr_abortBlock(
                $this->block_ix,
                $this->_id,
                "Block data written to the database does not match the data parsed from the daemon. ".
                "The block record was NOT written, so this height is uncommitted; the loader will ".
                "retry it on the next run. If this repeats, roll back this height and re-load.",
                $context
            );
        }
    }

    public function stripData(){
        $this->txs = array_unique(array_merge(array_keys($this->txs), array_values($this->reward_txs)));
        unset($this->vins);
        unset($this->vouts);
        unset($this->movements);
        unset($this->asset_movements);
        unset($this->addresses);
        unset($this->addressgroups);
        unset($this->merged_groups); 
        unset($this->master_groups);
        unset($this->asset_vins);
        unset($this->asset_vouts);
        unset($this->rollbacks);
        unset($this->block_stats);
        unset($this->reward_txs);
        unset($this->reward_tx);
        unset($this->is_genesis);
        unset($this->vout_spent);
        unset($this->assets);
        unset($this->vout_types);
        unset($this->tx_types);
        unset($this->tx_sizes);
        // the payout rows themselves live in VOUTS (block_ix + synthetic), only the block level
        // summary is kept here
        unset($this->shadow_payouts);
        if (!shadow_enabled()){
            // keep the stored document identical to what a build without shadow support writes
            unset($this->shadow_reward);
            unset($this->shadow_pow_reward);
            unset($this->shadow_pos_reward);
            unset($this->shadow_scheduled_reward);
        }

        unset($this->txs);

        global $settings;
        return $this->stripMongo() ;
    }
    private function stripMongo(){
        $this->moneysupply         = cmdec($this->moneysupply);
        $this->difficulty          = !is_null($this->difficulty) ? cmdec($this->difficulty) : null;
        $this->block_reward        = cmdec($this->block_reward);
        $this->stake_reward        = cmdec($this->stake_reward);
        $this->pow_reward          = cmdec($this->pow_reward);
        $this->mn_reward           = cmdec($this->mn_reward);
        $this->dev_reward          = cmdec($this->dev_reward);
        if (isset($this->shadow_reward))     {$this->shadow_reward     = cmdec($this->shadow_reward);}
        if (isset($this->shadow_pow_reward)) {$this->shadow_pow_reward = cmdec($this->shadow_pow_reward);}
        if (isset($this->shadow_pos_reward)) {$this->shadow_pos_reward = cmdec($this->shadow_pos_reward);}
        if (isset($this->shadow_scheduled_reward)) {$this->shadow_scheduled_reward = cmdec($this->shadow_scheduled_reward);}
        $this->stake_base          = cmdec($this->stake_base);
        $this->vins_amount         = cmdec($this->vins_amount);
        $this->vouts_amount        = cmdec($this->vouts_amount);
        $this->move_amount         = cmdec($this->move_amount);
        $this->fee_amount          = cmdec($this->fee_amount);
        if (isset($this->spow_reward)) {$this->spow_reward        = cmdec($this->spow_reward);}
        if (isset($this->mlc_reward)) {$this->mlc_reward        = cmdec($this->mlc_reward);}   // fix 2021-04-22
        $this->cdd                 = cmdec($this->cdd);
        if (isset($this->creditPoolBalance)) {$this->creditPoolBalance = cmdec($this->creditPoolBalance);}
    }
    private function stripStatsMongo(){
        // strip block_stats data for recording
        $this->block_stats->coinsupply          = cmdec($this->block_stats->coinsupply);
        $this->block_stats->coincirculation     = cmdec($this->block_stats->coincirculation);
        $this->block_stats->burned              = cmdec($this->block_stats->burned);
        $this->block_stats->blockRewardsTotal   = cmdec($this->block_stats->blockRewardsTotal);
        $this->block_stats->stakeRewardsTotal   = cmdec($this->block_stats->stakeRewardsTotal);
        $this->block_stats->stakeBaseTotal      = cmdec($this->block_stats->stakeBaseTotal);
        $this->block_stats->powRewardsTotal     = cmdec($this->block_stats->powRewardsTotal);
        $this->block_stats->mnRewardsTotal      = cmdec($this->block_stats->mnRewardsTotal);
        $this->block_stats->devRewardsTotal     = cmdec($this->block_stats->devRewardsTotal);
        if (isset($this->block_stats->shadowRewardsTotal)){
            $this->block_stats->shadowRewardsTotal  = cmdec($this->block_stats->shadowRewardsTotal);
            $this->block_stats->shadowPowTotal      = cmdec($this->block_stats->shadowPowTotal);
            $this->block_stats->shadowPosTotal      = cmdec($this->block_stats->shadowPosTotal);
        }
        $this->block_stats->movementsTotal      = cmdec($this->block_stats->movementsTotal);
        $this->block_stats->cdd                 = cmdec($this->block_stats->cdd);
        $this->block_stats->feeTotal            = cmdec($this->block_stats->feeTotal);   // SIP-041
        if (count($this->block_stats->voutTypes)==0){
            $this->block_stats->voutTypes = new stdClass();
        }
        if (count($this->block_stats->txTypes)==0){
            $this->block_stats->txTypes = new stdClass();
        }
        if (count($this->block_stats->txSizes)==0){
            $this->block_stats->txSizes = new stdClass();
        }
        if (count($this->block_stats->blockTypes)==0){
            $this->block_stats->blockTypes = new stdClass();
        }
        if (isset($this->creditPoolBalance)) {$this->creditPoolBalance = cmdec($this->creditPoolBalance);}
    }
}

class Tx {

    public $_id;            // tx hash
    public $block_ix;
    public $tx_ix;
    public $tx_size         = null;
    public $tx_time_sec     = null;
    public $vins_amount     = null;
    public $vouts_amount    = null;
    public $vins_count      = 0;
    public $vouts_count     = 0;
    public $fee_amount      = 0;
    public $move_amount     = 0;
    public $is_reward       = null;
    public $reward_type     = null;
    public $is_mn_reward    = null;
    public $cdd             = null;

    private $coinbase_vin = false;      // remove before insert
    private $nonstandard_vout = false;  // remove before insert
    private $nulldata_vout = false;     // remove before insert
    private $transfer_asset = false;    // remove before insert

    public function __construct($txid, $block_ix, $tx_ix){
        global $pp;
        $this->_id              = $txid;
        $this->block_ix         = $block_ix;
        $this->tx_ix            = $tx_ix;
        $this->vins             = array();  // remove before insert
        $this->vouts            = array();  // remove before insert
        $this->asset_vins       = array();  // remove before insert
        $this->asset_vouts      = array();  // remove before insert

        $this->movements        = array();  // insert only values 
        $this->asset_movements  = array();  // insert only values

        $this->vins_amount      = @cdec(0);
        $this->vouts_amount     = @cdec(0);
        $this->vins_count       = 0;
        $this->vouts_count      = 0;
        $this->fee_amount       = @cdec(0);
        $this->move_amount      = @cdec(0);
        $this->cdd              = @cdec(0);

    }
    
    public function readTx($block, $allow_unconfirmed = false){
        global $settings;
        if ($allow_unconfirmed) {
            $txdata = rpc_getrawtransaction($this->_id, 1, false);
        } else {
            $txdata = rpc_getrawtransaction($this->_id, 1, true);
            if (is_null($txdata) || !isset($txdata->txid) || $txdata->txid != $this->_id || !isset($txdata->blockhash)){
                ldr_abort("Failed to retrieve the transaction data from the daemon.", array("txid" => (string)$this->_id));
            } 
        }
        $this->tx_size = round(strlen($txdata->hex)/2,0);
        $this->tx_time_sec = $txdata->time;

        // add DIP2 contents
        if (isset($settings["dip2_support"]) && $settings["dip2_support"]){
            if (isset($txdata->extraPayload)){
                if (isset($txdata->proRegTx)){
                    $this->dip2_type = "ProRegTx";
                    $this->dip2_msg  = $txdata->proRegTx;
                } else if (isset($txdata->proUpServTx)){
                    $this->dip2_type = "ProUpServTx";
                    $this->dip2_msg  = $txdata->proUpServTx;
                } else if (isset($txdata->proUpRegTx)){
                    $this->dip2_type = "ProUpRegTx";
                    $this->dip2_msg  = $txdata->proUpRegTx;
                } else if (isset($txdata->proUpRevTx)){
                    $this->dip2_type = "ProUpRevTx";
                    $this->dip2_msg  = $txdata->proUpRevTx;
                } else if (isset($txdata->cbTx)){
                    $this->dip2_type = "CbTx";
                    $this->dip2_msg  = $txdata->cbTx;
                } else if (isset($txdata->qcTx)){
                    $this->dip2_type = "QcTx";
                    $this->dip2_msg  = $txdata->qcTx;
                } else if (isset($txdata->mnhfTx)){
                    $this->dip2_type = "MnHfTx";
                    $this->dip2_msg  = $txdata->mnhfTx;
                } else if (isset($txdata->assetLockTx)){
                    $this->dip2_type = "AssetLockTx";
                    $this->dip2_msg  = $txdata->assetLockTx;
                } else if (isset($txdata->assetUnlockTx)){
                    $this->dip2_type = "AssetUnlockTx";
                    $this->dip2_msg  = $txdata->assetUnlockTx;
                }
            }
        }

        // reading vouts
        if (count($txdata->vout)>0 || (isset($txdata->vShieldOutput) && count($txdata->vShieldOutput)>0)){
            foreach ($txdata->vout as $n=>$txvout){
                $this->vouts_count++;
                $vout = new Vout($this->_id, $txvout->n, $this->block_ix,$txdata->time);
                $vout->readVout($txvout);
                
                if($vout->type=="nonstandard")      {$this->nonstandard_vout    = true;}
                if($vout->type=="nulldata")         {
                    if (isset($txdata->cbTx->creditPoolBalance)){
                        // this is credit pool tx, so don't mark as nulldata_vout
                        $vout->address = "Credit Pool";
                        $vout->type = "creditpool_lock";
                        if (!empty($vout->op_return)){              
                            $this->op_return = $vout->op_return;    // OP_RETURN change
                        }
                    } else {
                        $this->nulldata_vout       = true;
                        if (!empty($vout->op_return)){              
                            $this->op_return = $vout->op_return;    // OP_RETURN change
                        }
                    }
                }
                if($vout->type=="transfer_asset" || $vout->type=="new_asset" || $vout->type=="reissue_asset")   {
                    $this->transfer_asset      = true;
                    if (isset($vout->asset)){
                        if ($vout->type=="new_asset" || $vout->type=="reissue_asset"){
                            $reissuable = isset($vout->asset->reissuable) ? $vout->asset->reissuable : null;
                            $units = isset($vout->asset->units) ? $vout->asset->units : null;
                        } else { $reissuable = null; $units = null;}
                        $asset_vout = new AssetVout($vout->address,$vout->asset->name, $vout->asset->amount, $vout->type, $reissuable, $units);
                        if (isset($vout->asset->message)){
                            $asset_vout->message = $vout->asset->message;
                        }
                        $this->asset_vouts[$vout->_id] = $asset_vout;
                    }
                }
                // nullassetdata
                if($vout->type=="nullassetdata" && isset($vout->asset))   {
                    $this->transfer_asset      = true;
                    $asset_vout = new AssetVout($vout->address,$vout->asset->name, $vout->asset->amount, $vout->type, null, null);
                    if ( isset($vout->asset->qualifier_type) ){
                        $asset_vout->qualifier_type = $vout->asset->qualifier_type;
                    }
                    $this->asset_vouts[$vout->_id] = $asset_vout;
                }

                $this->vouts_amount = $this->vouts_amount + @cdec($vout->amount);
                
                $this->vouts[$vout->_id] = $vout;
            }

            if (isset($txdata->type) && (int)$txdata->type === 8 && isset($txdata->assetLockTx)){
                $locked_amount = null;
                if (isset($txdata->assetLockTx) && isset($txdata->assetLockTx->creditOutputs) && count($txdata->assetLockTx->creditOutputs) > 0){
                    $locked_amount = @cdec(0);
                    foreach ($txdata->assetLockTx->creditOutputs as $co){
                        if (isset($co->value)){
                            $locked_amount += @cdec($co->value);
                        }
                    }
                }
                foreach ($this->vouts as $vout_id => $vout){
                    if ($vout->type == "nulldata" && @cdec($vout->amount) > 0){
                        if (!is_null($locked_amount)){
                            $old_amount = @cdec($vout->amount);
                            if (@cdec($locked_amount) != $old_amount){
                                $this->vouts_amount = $this->vouts_amount - $old_amount + @cdec($locked_amount);
                                $vout->amount = @cdec($locked_amount);
                            }
                        }
                        $vout->address = "Credit Pool";
                        $vout->type = "creditpool_lock";
                        $this->vouts[$vout_id] = $vout;
                        break;
                    }
                }
            }
            

            if (isset($txdata->vShieldOutput) && count($txdata->vShieldOutput)>0){
                //foreach ($txdata->vShieldOutput as $n=>$shieldOut) {
                    $vout = new Vout($this->_id, "sh", $this->block_ix,$txdata->time);
                    $vout->address = "Shield";
                    $vout->type = "shield";
                    //var_dump($txdata->valueBalance);
                    $smove =  @cdec($txdata->valueBalance);
                    $vout->amount = ($smove < 0) ? -$smove : @cdec(0);

                    $this->souts_count = count($txdata->vShieldOutput);
                    $this->smove_amount = ($smove < 0) ? -$smove : @cdec(0);
                    $this->vouts_count += count($txdata->vShieldOutput);
                    $this->vouts_amount += $vout->amount;

                    $this->vouts[$vout->_id] = $vout;
                    //var_dump($vout);
                //}
            }


        } else {
            if (!isset($this->dip2_type) || ( $this->dip2_type != "QcTx" &&$this->dip2_type != "MnHfTx" ) ){
                ldr_abort("Transaction has no VOUTs or shield outputs.", array("txid" => (string)$this->_id));
            }
        }
        // reading vins
        if (count($txdata->vin)>0 || (isset($txdata->vShieldSpend) && count($txdata->vShieldSpend)>0) || (isset($txdata->type) && (int)$txdata->type === 9 && isset($txdata->assetUnlockTx))){
            foreach ($txdata->vin as $n=>$txvin){
                $this->vins_count++;
                $vin = new Vin($this->_id, $n, $this->block_ix);
                $vin->readVin($txvin);
                
                if($vin->type == "coinbase")        {
                    $this->coinbase_vin = true;
                    // Keep the miner's tag on the block: a pool that pays a fresh
                    // address per block cannot be identified any other way.
                    if ($block instanceof Block && isset($txvin->coinbase) && is_null($block->coinbase_tag)) {
                        $block->coinbase_tag = ldr_coinbaseTag($txvin->coinbase);
                    }
                }

                $this->vins[$vin->_id] = $vin;
            }
            if (isset($txdata->vShieldSpend) && count($txdata->vShieldSpend)>0){
                $vin = new Vin($this->_id, "sh", $this->block_ix);
                $vin->address = "Shield";
                $vin->type = "shield";

                $smove =  @cdec($txdata->valueBalance);
                $vin->amount = ($smove > 0) ? $smove : @cdec(0);

                $this->sins_count = count($txdata->vShieldSpend);
                $this->smove_amount = ($smove > 0) ? -$smove : @cdec(0);
                $this->vins_count += count($txdata->vShieldSpend);
                $this->vins_amount += $vin->amount;

                $this->vins[$vin->_id] = $vin;
                //var_dump($txdata->valueBalance);
                //var_dump($vin);
            } else if (isset($txdata->type) && (int)$txdata->type === 9 && isset($txdata->assetUnlockTx)) {
                $vin = new Vin($this->_id, "cp", $this->block_ix);
                $vin->address = "Credit Pool";
                $vin->type = "creditpool_unlock";
                $fee_duffs = isset($txdata->assetUnlockTx->fee) ? @cdec($txdata->assetUnlockTx->fee) : @cdec(0);
                $fee_dash = $fee_duffs / @cdec(100000000);
                $vin->amount = @cdec($this->vouts_amount) + $fee_dash;
                $vin->time_sec = $this->tx_time_sec;

                $this->vins_count += 1;
                $this->vins_amount += $vin->amount;
                $this->vins[$vin->_id] = $vin;
            }

        } else {
            if (!isset($this->dip2_type) || ( $this->dip2_type != "QcTx" && $this->dip2_type != "MnHfTx") ){
                ldr_abort("Transaction has no VINs or shield spends.", array("txid" => (string)$this->_id));
            }
        }

        // detect reward tx
        if ($this->coinbase_vin && ($this->vouts_amount > 0 || !$this->nonstandard_vout)){
            // coinbase tx -> PoW reward
            $this->is_reward = true;
            $this->reward_type = "PoW";
        } else if (!$this->coinbase_vin && $this->nonstandard_vout  && $this->vouts_amount > 0 && (count($block->reward_txs) == 0  
            || (in_array($block->reward_txs[0], $block->txs) && $block->txs[$block->reward_txs[0]]->reward_type != "PoW"))){
            // pos coinbase tx -> PoS reward
            $this->is_reward = true;
            $this->reward_type = "PoS";
        } else if ($this->coinbase_vin && $this->nonstandard_vout  && $this->vouts_amount == 0){ 
            // nonstandard tx
            $this->type = "nonstandard";
        }


    }

    public function moveAmountTx(   $amount,                    // 1  : amount
                                    $address,                   // 2  : address
                                    $key,                       // 3  : key
                                    $is_reward      = false,    // 4  : is_reward
                                    $is_pow_reward  = false,    // 5  : is_pow_reward
                                    $is_pos_reward  = false,    // 6  : is_pos_reward
                                    $is_mn_reward   = false,    // 7  : is_mn_reward
                                    $is_dev_reward  = false,    // 8  : is_dev_reward
                                    $is_vin,                    // 9  : is_vin
                                    $is_asset_move  = false,    // 10 : is_asset_move
                                    $custom_type    = null,     // 11 : custom type
                                    $is_coldstake   = false,    // 12 : is_coldstake
                                    $staker         = null      // 13 : staker (cold stake)
                                ){
        if (array_key_exists($key, $this->movements)){
            $this->movements[$key]->moveAmount($amount, $is_vin, $is_asset_move);
        } else {
            $am = new Movement($address, $this->block_ix, $this->_id, $this->tx_time_sec, $amount, $is_vin, $is_asset_move, $custom_type);
            $am->is_reward = $is_reward;
            $am->is_pow_reward  = $is_pow_reward;
            $am->is_pos_reward  = $is_pos_reward;
            $am->is_mn_reward   = $is_mn_reward;
            $am->is_dev_reward  = $is_dev_reward;
            if ($is_coldstake){
                $am->is_coldstake   = $is_coldstake;
                $am->staker         = $staker;
            } else if($custom_type != null && !empty($staker)){
                $am->staker         = $staker;
            }
            $this->movements[$key] = $am;
        }
    }

    public function moveAssetAmountTx($amount, $name, $address, $type, $key, $is_vin, $options = []){
        if (array_key_exists($key, $this->asset_movements)){
            $this->asset_movements[$key]->moveAssetAmount($amount, $is_vin);
            if (isset($options) && count($options)>0){
                foreach ($options as $option=>$value){
                    $this->asset_movements[$key]->$option = $value;
                }
            }
        } else {
            $aam = new AssetMovement($address, $this->block_ix, $this->_id, $this->tx_time_sec, $name, $amount, $is_vin);
            if (isset($options) && count($options)>0){
                foreach ($options as $option=>$value){
                    $aam->$option = $value;
                }
            }
            $this->asset_movements[$key] = $aam;
        }
    }

    public function stripData(){
        
        unset($this->vins);
        unset($this->vouts);
        unset($this->asset_vins);
        unset($this->asset_vouts);
        unset($this->coinbase_vin);
        unset($this->nonstandard_vout);
        unset($this->nulldata_vout);
        unset($this->transfer_asset);
        if (isset($this->airdrop_amount)){
            unset($this->airdrop_amount);
        }

        global $settings;
        return $this->stripMongo() ;
    }
    private function stripMongo(){
        global $settings;

        if (isset($settings["storage_improvement"]) && $settings["storage_improvement"]) {
            // movements and asset_movements should be taken from DB collections
            unset($this->movements);
            unset($this->asset_movements);
        } else {
            // strip movements
            $ams = array_values($this->movements);
            $this->movements = array();
            foreach ($ams as $am){
                $cam = clone $am;
                $cam->stripData();
                unset($cam->tx_id);
                array_push($this->movements, $cam);
            }

            // strip asset movements
            $tms = array_values($this->asset_movements);
            $this->asset_movements = array();
            foreach ($tms as $aam){
                $caam = clone $aam;
                $caam->stripData();
                unset($caam->tx_ix);
                array_push($this->asset_movements, $caam);
            } 
        }
        $this->vins_amount      = cmdec($this->vins_amount);
        $this->vouts_amount     = cmdec($this->vouts_amount);
        $this->fee_amount       = cmdec($this->fee_amount);
        $this->move_amount      = cmdec($this->move_amount);
        $this->cdd              = cmdec($this->cdd);
        if (isset($this->smove_amount)){
            $this->smove_amount     = cmdec($this->smove_amount);
        }
    }
}

class Vout {
    public $_id = null;
    public $vout_ix = null;
    public $block_ix = null;
    public $amount = null;
    public $type = null; //pubkey, pubkeyhash, nonstandard, nulldata, zeromint, transfer_asset, mint, coldstake
    public $address = null;
    public $is_spent = false;
    public $spent_in = null;
    public $time_sec = null;

    public function __construct($tx_id, $vout_ix, $block_ix, $block_time){
        global $pp;
        $this->_id = $tx_id.'-'.$vout_ix;
        $this->block_ix = $block_ix;
        $this->vout_ix = $vout_ix;
        $this->amount = @cdec(0);
        $this->time_sec = $block_time;
    }

    public function readVout($tx_vout){
        //global $settings;
        if (isset($tx_vout->scriptPubKey->type)){
            $this->type = $tx_vout->scriptPubKey->type;
        }
        if (isset($tx_vout->value)){
            $this->amount = @cdec($tx_vout->value); 
        }
        if (isset($tx_vout->scriptPubKey->addresses) && count($tx_vout->scriptPubKey->addresses) == 1){
            $this->address = $tx_vout->scriptPubKey->addresses[0];
        } else if (isset($tx_vout->scriptPubKey->addresses) && $tx_vout->scriptPubKey->type != "coldstake" && count($tx_vout->scriptPubKey->addresses)>1) {
            if ($this->type == "multisig") {
                 $this->address = implode(" ",$tx_vout->scriptPubKey->addresses);
            } else {
                throw new Exception("Vout contains more than one address. \n Vout type: ".$tx_vout->scriptPubKey->type."\nVout Id: ". $this->_id ."\n Addresses: " .implode(", ", $tx_vout->scriptPubKey->addresses)."\n");
            }
        } else if ($this->type == "zerocoinmint") {
            $this->address = "Zerocoin";
            //$this->type = "zerocoin";
        } else if (!isset($tx_vout->scriptPubKey->addresses) && isset($tx_vout->scriptPubKey->address)
                    && is_string($tx_vout->scriptPubKey->address) && $tx_vout->scriptPubKey->address !== "") {
            // bitcore 22+ reports a single 'address' instead of the 'addresses' array.
            // The daemon-decoded address is authoritative, so use it for every script type
            // (p2pk included) and keep the base58 derivation below as a fallback only.
            // An empty string is not an address: e.g. the empty-script PoS marker vout reports
            // "address": "" and must fall through to the 'nonstandard' handling below.
            $this->address = $tx_vout->scriptPubKey->address;
        } else if (!isset($tx_vout->scriptPubKey->addresses) && $tx_vout->scriptPubKey->type == "pubkey") {
            // p2pk -> fallback: derive the address from the public key (daemon reported no address)
            global $settings;
            if (!isset($settings["base58"])){
                ldr_abort("Cannot derive a P2PK address: 'base58' is not configured in settings.php.", array("vout" => (string)$this->_id));
            }
            (isset($settings["base58"]["nw_bytes"])) ? $nw_bytes = $settings["base58"]["nw_bytes"] : $nw_bytes = "00";

            $hex = substr($tx_vout->scriptPubKey->hex,2, strlen($tx_vout->scriptPubKey->hex)-4);
            $this->address = getPubKeyAddress($hex,$nw_bytes);
        } else if (isset($tx_vout->scriptPubKey->addresses) && $tx_vout->scriptPubKey->type == "coldstake" && count($tx_vout->scriptPubKey->addresses)>1) {
            $this->address = $tx_vout->scriptPubKey->addresses[1];
            $this->staker = $tx_vout->scriptPubKey->addresses[0];
        }  else if (!isset($tx_vout->scriptPubKey->addresses) && isset($tx_vout->scriptPubKey->address)) {
            $this->address = $tx_vout->scriptPubKey->address;
        }
        // bare multisig: bitcore 22+ reports neither 'addresses' nor 'address', which would leave
        // the vout with a null address. Rebuild the same "addr1 addr2 ..." form from the asm keys.
        if($this->type=="multisig" && empty($this->address) && isset($tx_vout->scriptPubKey->asm)){
            global $settings;
            $ms_addresses = array();
            if (isset($settings["base58"])){
                (isset($settings["base58"]["nw_bytes"])) ? $nw_bytes = $settings["base58"]["nw_bytes"] : $nw_bytes = "00";
                foreach (explode(" ", $tx_vout->scriptPubKey->asm) as $asm_token){
                    if (preg_match('/^(0[23][0-9a-fA-F]{64}|04[0-9a-fA-F]{128})$/', $asm_token)){
                        array_push($ms_addresses, getPubKeyAddress($asm_token, $nw_bytes));
                    }
                }
            }
            $this->address = (count($ms_addresses)>0) ? implode(" ", $ms_addresses) : 'multisig';
        }
        if($this->type=="nonstandard")          {$this->address   = 'nonstandard';}
        if($this->type=="nulldata")             {$this->address   = 'nulldata';
            if ( strpos($tx_vout->scriptPubKey->asm, 'OP_RETURN') !== false  ){
                $op_return = explode(' ',$tx_vout->scriptPubKey->asm,2);
                if (isset($op_return[1])){
                    $this->op_return = explode(' ',$tx_vout->scriptPubKey->asm,2)[1];   // OP_RETURN change
                }
            }
        }
        // nullassetdata
        if($this->type=="nullassetdata")         {
            if (is_null($this->address)) {
                $this->address   = 'nullassetdata';
            } else {
                if (isset($tx_vout->scriptPubKey->asset_data)){
                    $this->asset = new stdClass();
                    $this->asset->name      = $tx_vout->scriptPubKey->asset_data->asset_name;
                    if ( isset($tx_vout->scriptPubKey->asset_data->qualifier_type) ) { 
                        $this->asset->qualifier_type = $tx_vout->scriptPubKey->asset_data->qualifier_type; 
                    }
                    if ( isset($tx_vout->scriptPubKey->asset_data->amount) ) { 
                        $this->asset->amount = @cdec($tx_vout->scriptPubKey->asset->amount); 
                    } else {
                        $this->asset->amount = @cdec(0);
                    } 
                }
            }
        }

        // process segwit address
        if($this->type=="witness_v0_keyhash" && empty($this->address))   {
            global $settings;
            if (isset($settings["segwit"]) && isset($settings["segwit"]["supported"]) && isset($settings["segwit"]["bech32_prefix"]) && isset($settings["segwit"]["bech32_separator"]) && $settings["segwit"]["supported"]){
                $asm = explode(" ", $tx_vout->scriptPubKey->asm);
                $version = $asm[0];
                $bytes = hex2bytes($asm[1]);
                $this->address = encodeSegwit($settings["segwit"]["bech32_prefix"],$settings["segwit"]["bech32_separator"],$version, $bytes);
            } else {
                throw new Exception("Vout type is 'witness_v0_keyhash', but segwit support not configured\n
                vout: '".$this->_id."'\n
                asm: '".$tx_vout->scriptPubKey->asm."'\n");
            }
        }
        if($this->type=="witness_v0_scripthash" && empty($this->address))   {
            global $settings;
            if (isset($settings["segwit"]) && isset($settings["segwit"]["supported"]) && isset($settings["segwit"]["bech32_prefix"]) && isset($settings["segwit"]["bech32_separator"]) && $settings["segwit"]["supported"]){
                $asm = explode(" ", $tx_vout->scriptPubKey->asm);
                $version = $asm[0];
                $bytes = hex2bytes($asm[1]);
                $this->address = encodeSegwit($settings["segwit"]["bech32_prefix"],$settings["segwit"]["bech32_separator"],$version, $bytes);
            } else {
                throw new Exception("Vout type is 'witness_v0_scripthash', segwit logic not created for this type\n
                vout: '".$this->_id."'\n
                asm: '".$tx_vout->scriptPubKey->asm."'\n");
            }
        }
        
        // process transfer_asset
        if($this->type=="transfer_asset" || $this->type=="new_asset" || $this->type=="reissue_asset")   {
            if(isset($tx_vout->scriptPubKey->asset)){
                $this->asset = new stdClass();
                $this->asset->name      = $tx_vout->scriptPubKey->asset->name;
                $this->asset->amount    = @cdec($tx_vout->scriptPubKey->asset->amount);
                if( isset($tx_vout->scriptPubKey->asset->reissuable)) { $this->asset->reissuable = $tx_vout->scriptPubKey->asset->reissuable ;}
                if( isset($tx_vout->scriptPubKey->asset->units)) { $this->asset->units = $tx_vout->scriptPubKey->asset->units ;}
                if( isset($tx_vout->scriptPubKey->asset->message)) { $this->asset->message = $tx_vout->scriptPubKey->asset->message ;}
            }
        }

    }

    public function stripData(){
        unset($this->op_return);
        global $settings;
        return $this->stripMongo() ;
    }
    private function stripMongo(){
        $this->amount          = cmdec($this->amount);
        if (isset($this->asset)){
            $this->asset->amount = cmdec($this->asset->amount);
        }
        if (isset($this->nested_cdd)){
            $this->nested_cdd = cmdec($this->nested_cdd);
        }
        if (isset($this->nominal_amount)){
            $this->nominal_amount = cmdec($this->nominal_amount);
        }
    }
}

class AssetVout {
    public $address;
    public $name;
    public $amount;

    public function __construct($address, $name, $amount, $type, $reissuable = null, $units = null){
        global $pp;
        $this->address      = $address;
        $this->name         = $name;
        $this->amount       = @cdec($amount);
        $this->type         = $type;
        if (!is_null($reissuable)) { $this->reissuable = $reissuable; }
        if (!is_null($units)){ $this->units = $units; }
    }          
}

class Vin {
    public $_id = null;
    public $vin_ix = null;
    public $block_ix = null;
    public $amount = null;
    public $type = null; // transfer, coinbase, pos_coinbase, zerocoin, transfer_asset, zerocoinmint, coldstake
    public $address = null;
    public $ref_vout = null;
    public $time_sec = null;

    public function __construct($tx_id, $vin_ix, $block_ix){
        $this->_id = $tx_id.'-'.$vin_ix;
        $this->block_ix = $block_ix;
        $this->vin_ix = $vin_ix;
        $this->tx_id = $tx_id;
    }

    public function readVin($tx_vin){
        global $settings;
        if (isset($tx_vin->txid) && isset($tx_vin->vout)){
            if (isset($tx_vin->type) && $tx_vin->type == "zerocoinmint"){
                //$this->type     = "zerocoin";
                $this->address  = "Zerocoin";
                $this->amount   = @cdec($tx_vin->sequence);
            } else if ($tx_vin->txid == $settings['z_txid'] || (isset($tx_vin->scriptSig->asm) && strpos($tx_vin->scriptSig->asm,"OP_ZEROCOINSPEND") === 0)){
                $this->type     = "zerocoinspend";
                $this->address  = "Zerocoin";
                $this->amount   = @cdec($tx_vin->sequence);
            } else {
                $this->type = "transfer";
                $this->ref_vout = $tx_vin->txid . '-' . $tx_vin->vout;
            }
        } else if (isset($tx_vin->coinbase)){
            $this->type     = "coinbase";
            $this->address  = "Coinbase";
        }
    }

    public function stripData(){
        unset($this->tx_ix);
    }
}

class AssetVin {
    public $address;
    public $name;
    public $amount;

    public function __construct($address, $name, $amount){
        global $pp;
        $this->$address     = $address;
        $this->name         = $name;
        $this->amount       = @cdec($amount);
    }          
}

class Movement {
    public $address;
    public $block_ix;
    //public $tx_id;
    public $move_time_sec;
    public $amount;
    public $vins_amount;
    public $vouts_amount;
    public $vins_count      = 0;
    public $vouts_count     = 0;
    public $is_reward       = false;
    public $is_pow_reward   = false;
    public $is_pos_reward   = false;
    public $is_mn_reward    = false;
    public $is_dev_reward    = false;

    public function __construct($address, $block_ix, $tx_id, $move_time_sec, $amount, $is_vin, $is_asset_move, $custom_type=null){
        global $pp;
        $this->address          = $address;
        $this->block_ix         = $block_ix;
        $this->tx_id            = $tx_id;
        $this->move_time_sec    = $move_time_sec;
        $this->amount           = @cdec($amount);
        $this->vins_amount      = @cdec(0);
        $this->vouts_amount     = @cdec(0);
        if ($is_vin){
            $this->vins_count += 1;
            $this->vins_amount = $this->vins_amount - @cdec($amount); // Decimal removed fpp
        } else {
            $this->vouts_count += 1;
            $this->vouts_amount = $this->vouts_amount + @cdec($amount); // Decimal removed fpp
        }
        if ($is_asset_move){
            $this->is_asset_move = true;
        }
        if (isset($custom_type)){
            $this->type = $custom_type;
        }
    }

    public function moveAmount($amount, $is_vin, $is_asset_move){
        $this->amount = $this->amount + cdec($amount); // Decimal removed fpp
        if ($is_vin){
            $this->vins_count += 1;
            $this->vins_amount = $this->vins_amount - @cdec($amount); // Decimal removed fpp
        } else {
            $this->vouts_count += 1;
            $this->vouts_amount = $this->vouts_amount + @cdec($amount);  // Decimal removed fpp
        }
        if ($is_asset_move){
            $this->is_asset_move = true;
        }
    }

    public function stripData(){
        global $settings;
        return $this->stripMongo() ;
    }
    private function stripMongo(){
        $this->amount          = cmdec($this->amount);
        $this->vins_amount     = cmdec($this->vins_amount);
        $this->vouts_amount    = cmdec($this->vouts_amount);
    }
}

class AssetMovement{
    //_id set by db engine
    public $address;
    public $block_ix;
    //public $tx_id;
    public $move_time_sec;
    public $name;
    public $amount;
    public $vins_amount;
    public $vouts_amount;
    public $vins_count      = 0;
    public $vouts_count     = 0;

    public function __construct($address, $block_ix, $tx_id, $move_time_sec, $name, $amount, $is_vin){
        global $pp;
        $this->address = $address;
        $this->block_ix = $block_ix;
        $this->tx_id = $tx_id;
        $this->move_time_sec = $move_time_sec;
        $this->name = $name;
        $this->amount = @cdec($amount);
        $this->vins_amount     = @cdec(0);
        $this->vouts_amount    = @cdec(0);

        if ($is_vin){
            $this->vins_count += 1;
            $this->vins_amount = $this->vins_amount - @cdec($amount);  // Decimal removed fpp
        } else {
            $this->vouts_count += 1;
            $this->vouts_amount = $this->vouts_amount + @cdec($amount);  // Decimal removed fpp
        }
    }

    public function moveAssetAmount($amount, $is_vin){
        $this->amount = $this->amount + @cdec($amount);   // Decimal removed fpp

        if ($is_vin){
            $this->vins_count += 1;
            $this->vins_amount = $this->vins_amount - @cdec($amount);  // Decimal removed fpp
        } else {
            $this->vouts_count += 1;
            $this->vouts_amount = $this->vouts_amount + @cdec($amount);  // Decimal removed fpp
        }
    }

    public function stripData(){

        global $settings;
        return $this->stripMongo() ;
    }
    private function stripMongo(){
        $this->amount          = cmdec($this->amount);
        $this->vins_amount     = cmdec($this->vins_amount);
        $this->vouts_amount    = cmdec($this->vouts_amount);

    }
}

class Address {
    public $_id;
    public $group_id;
    public $balance;
    public $received;
    public $sent;

    public $deposited;
    public $withdrawn;
    public $passive_in;
    public $last_block;

    public $assets      = array();

    public function __construct($address){
        global $pp;
        $this->_id = $address;
        $this->is_new      = true;
        $this->balance     = @cdec(0);
        $this->received    = @cdec(0);
        $this->sent        = @cdec(0);
    
        $this->deposited   = @cdec(0);
        $this->withdrawn   = @cdec(0);
        $this->passive_in  = @cdec(0);
    }

    public function loadAddress(){
        global $settings;
        global $pp;
        $ars = db_getAddressData($this->_id);
        if (count($ars)>0){
            $ar = $ars[0];
                $this->group_id     = $ar->group_id;
                $this->balance      = @cdec($ar->balance); // Decimal removerd fpp
                $this->received     = @cdec($ar->received);
                $this->sent         = @cdec($ar->sent);
                $this->deposited    = @cdec($ar->deposited);
                $this->withdrawn    = @cdec($ar->withdrawn);
                $this->passive_in   = @cdec($ar->passive_in);
                $this->last_block   = $ar->last_block;
                if (isset($ar->assets)){
                    foreach ($ar->assets as $key => $asset) {
                        $this->assets[$key] = (object)array('name' => $asset->name, 'value' => cdec($asset->value));
                    }
                } else {
                    $this->assets = array();
                }
                $this->is_new       = false;
        } else {
            // new address, everything is 0, need to generate group_id
            $prefix = 'csag-'.strtolower($settings['coin_symbol']).'-';
            $this->group_id = generateHash($this->_id, $prefix);
        }
    }

    // --- New in (SIP-021)
    public function checkPreloadedAddress($addresses_preload){
        if (array_key_exists($this->_id,$addresses_preload) && !is_null($addresses_preload[$this->_id])){
            $ar = $addresses_preload[$this->_id];
                $this->group_id     = $ar->group_id;
                $this->balance      = @cdec($ar->balance);
                $this->received     = @cdec($ar->received);
                $this->sent         = @cdec($ar->sent);
                $this->deposited    = @cdec($ar->deposited);
                $this->withdrawn    = @cdec($ar->withdrawn);
                $this->passive_in   = @cdec($ar->passive_in);
                $this->last_block   = $ar->last_block;
                if (isset($ar->assets)){
                    foreach ($ar->assets as $key => $asset) {
                        $this->assets[$key] = (object)array('name' => $asset->name, 'value' => cdec($asset->value));
                    }
                } else {
                    $this->assets = array();
                }
                if (isset($ar->alias) && !empty($ar->alias)){$this->alias = $ar->alias;} // SIP-025 
                $this->is_new       = false;
        } else {
            global $settings;
            // new address, everything is 0, need to generate group_id
            $prefix = 'csag-'.strtolower($settings['coin_symbol']).'-';
            $this->group_id = generateHash($this->_id, $prefix);
        }
    }

    public function stripData(){
        global $settings;
        unset($this->is_new);
        return $this->stripMongo() ;

    }
    private function stripMongo(){
        $this->balance      = cmdec($this->balance);
        $this->received     = cmdec($this->received);
        $this->sent         = cmdec($this->sent);
        $this->deposited    = cmdec($this->deposited);
        $this->withdrawn    = cmdec($this->withdrawn);
        $this->passive_in   = cmdec($this->passive_in);
        if (count($this->assets) > 0) {
            $assets = array();
            foreach ($this->assets as $key => $asset){
                if ($asset->value > 0){
                    $assets[$key] = (object) array("name" => $asset->name,"value" => cmdec($asset->value));
                }
            }
            $this->assets = $assets;
        }
    }
}

class AddressGroup{
    public $_id;
    public $address_count   = 0;
    public $balance;

    public function __construct($group_id){
        global $pp;
        $this->_id          = $group_id; 
        $this->is_new          = true;
        $this->balance         = @cdec(0);
    }

    public function loadGroup(){
        global $settings;
        global $pp;
        $gps = db_getAddressGroupById($this->_id);
        if (count($gps)>0){
            $ag = $gps[0];
            if ($ag->_id == $this->_id){
                //$this->group_id         = $ag->_id;
                $this->balance          = @cdec($ag->balance); // Decimal removed fpp
                $this->address_count    = $ag->address_count;
                $this->is_new           = false;
            }
        } else {
            //$this->group_id         = $this->_id;
            $this->balance          = @cdec(0);
            $this->address_count    = 1;
            $this->is_new           = true;
        }
    }

    public function checkPreloadedGroup($groups_preload){
        if (array_key_exists($this->_id,$groups_preload) && !is_null($groups_preload[$this->_id])){
            $ag = $groups_preload[$this->_id];
            if ($ag->_id == $this->_id){
                $this->balance          = @cdec($ag->balance);
                $this->address_count    = $ag->address_count;
                $this->is_new           = false;
                if (isset($ag->tags) && count($ag->tags)>0){ $this->tags = $ag->tags; } // SIP-024 fix
            }
        } else {
            $this->balance          = @cdec(0);
            $this->address_count    = 1;
            $this->is_new           = true;
        }
    }

    public function stripData(){
        global $settings;
        unset($this->is_new);
        return $this->stripMongo() ;
        // return $this;
    }
    private function stripMongo(){
        $this->balance      = cmdec($this->balance);
    }
}

class Asset{
    public $_id; //name
    public $supply;

    function __construct($name){
        $this->_id = $name;
        $this->key = generateHash($name);
        $this->is_new = true;
        $this->type = rvn_getAssetTypeByName($name);
    }

    public function loadAsset(){
        global $pp;
        $ast = db_getAssetData($this->_id);
        if (count($ast)>0){
            $at = $ast[0];
            $this->supply = @cdec($at->supply); // Decimal removed fpp
            $this->is_new = false;
        } else {
            // get info from daemon
            $ad = rpc_getassetdata($this->_id);
            if (!empty($ad) && isset($ad->name)){
                $this->ipfs = (isset($ad->ipfs_hash)) ? $ad->ipfs_hash : null;
            } 
            // 
            $this->supply = @cdec(0);
        }
    }

    function addSupply($amount){
        $this->supply = $this->supply+$amount; // Decimal removed fpp
    }

    function stripData(){
        global $settings;
        unset($this->is_new);
        return $this->stripMongo() ;
        
    }
    private function stripMongo(){
        $this->supply = cmdec($this->supply);
    }
}

class ProcessingLog{
    public $timestamp;
    public $block_ix;
    public $block_hash;
    public $operation;
    public $processing_time = null;
    public $timers = array();

    function __construct($block_ix, $block_hash, $operation ){
        $this->timestamp = new MongoDB\BSON\UTCDateTime();
        $this->block_ix = $block_ix;
        $this->block_hash = $block_hash;
        $this->operation = $operation;

        $this->metrics = array();
    }
    
    public function addTimeData($processing_time, $timeTracker){
        $this->processing_time = $processing_time;
        foreach ($timeTracker->getCollection() as $timer){
            $tr = new stdClass();
            $tr->name = $timer->name;
            $tr->level = $timer->level;
            $tr->duration_usec = $timer->getDurationMicroSeconds();
            $tr->info = $timer->getInfo();
            array_push($this->timers, $tr);
        }
    }

    public function addMetrics($metrics){
        $this->metrics = array_merge($this->metrics, $metrics) ;
    }

    public function record(){
        db_recordLoaderLog($this);
    }
        
    
}


function getPubKeyAddress($asm, $nw_bytes = "00"){
    global $settings;

    $asm = strtoupper($asm);
    //print("1 - Public ECDSA Key :\n");
    //print($asm."\n");
    $ba = hex2bin($asm);
    if (!isset($settings["base58"]["scheme"]) || empty($settings["base58"]["scheme"])) {$scheme = "bitcoin";} else {$scheme = $settings["base58"]["scheme"]; }
    if ($scheme == "bitcoin") {
        //print("2 - SHA-256 hash of 1\n");
        $p2 = hash("sha256", $ba, true);
        //print(strtoupper(bin2hex($p2))."\n");

        //print("3 - RIPEMD-160 Hash of 2\n");
        $p3 = hash("ripemd160", $p2, true);
        //print(strtoupper(bin2hex($p3))."\n");

        //print("4 - Adding network bytes to 3\n");
        $nwb = hex2bin($nw_bytes);
        $p4 = ($nwb.$p3);
        //print(strtoupper(bin2hex($p4))."\n");

        //print("5 - SHA-256 hash of 4\n");
        $p5 = hash("sha256", $p4, true);
        //print(strtoupper(bin2hex($p5))."\n");

        //print("6 - SHA-256 hash of 5\n");
        $p6 = hash("sha256", $p5, true);
        //print(strtoupper(bin2hex($p6))."\n");

        //print("7 - First four bytes of 6\n");
        $p7 = substr($p6, 0, 4);
        //print(strtoupper(bin2hex($p7))."\n");

        //print("8 - Adding 7 at the end of 4\n");
        $p8 = ($p4.$p7);
        //print(strtoupper(bin2hex($p8))."\n");

        //print("9 - Base58 encoding of 8\n");
        $p9 = base58check_encode($p8);
        //print($p9."\n");
    } else if ($scheme == "groestlcoin") {

    }
    return $p9;
}
function base58check_encode($bytes){
    global $settings;
    if (isset($settings["base58"]["alphabet"])){
        $alphabet = $settings["base58"]["alphabet"];
    } else {
        $alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";
    }
    if (isset($settings["base58"]["prefix"])){
        $prefix = $settings["base58"]["prefix"];
    } else {
        $prefix = "1";
    }
    $res = "";
    $hex = bin2hex($bytes);
    $ap = hex2long($hex);
    while ($ap > 0){
        $rem = $ap->mod(58);
        $ap = ($ap/58)->floor();
        $res = $alphabet[($rem)->toInt()].$res;
    }

    for($i=0;$i<strlen($hex)&&substr($hex,$i,2)=="00";$i+=2)
    {
            $res = $prefix.$res;
    }

    return $res;

}
function hex2long($hex){
    $hex=strtoupper($hex);
    $chars="0123456789ABCDEF";
    $res = new Decimal("0",128);
    for($i=0;$i<strlen($hex);$i++) {
        $current=strpos($chars,$hex[$i]);
        $res=$res->mul(16);
        $res=$res+$current;
    }
    return $res;
}
?>