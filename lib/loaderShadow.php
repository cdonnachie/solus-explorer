<?php
    use Decimal\Decimal;

    /**
     * Shadow ledger support (Blackcoin QuantumQuasar "Gold Rush").
     *
     * Gold Rush pays a scheduled subsidy as deterministic *synthetic* outputs. They are real,
     * spendable active-chain UTXOs - gettxout resolves them - but they are never serialised into
     * a block and are outside the transaction Merkle tree, so getrawtransaction cannot see them
     * and a block-walking loader misses them entirely.
     *
     * The only source that does not require -shadowindex is getgoldrushblock, which lists the
     * payouts anchored to one active-chain block.
     *
     * Everything here is inert unless "shadow_accounting" is enabled in the coin settings.
     */

    define('SHADOW_EMISSION_ADDRESS', 'Gold Rush');
    define('SHADOW_MOVE_TYPE', 'shadow');

    /**
     * @return bool true when shadow accounting is enabled for this coin
     */
    function shadow_enabled(){
        global $settings;
        return (isset($settings["shadow_accounting"]) && $settings["shadow_accounting"]);
    }

    /**
     * Reads a key from the "shadow_config" settings block.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function shadow_cfg($key, $default = null){
        global $settings;
        if (isset($settings["shadow_config"]) && is_array($settings["shadow_config"])
            && array_key_exists($key, $settings["shadow_config"])){
            return $settings["shadow_config"][$key];
        }
        return $default;
    }

    /**
     * The reward window is a closed height range. Outside it the daemon reports
     * gold_rush_reward_active=false with zero payouts anyway, so this is a cost
     * optimisation rather than a correctness guard.
     *
     * @param int $block_ix
     * @return bool
     */
    function shadow_inRange($block_ix){
        $start = (int)shadow_cfg("start_height", 0);
        $end   = shadow_cfg("end_height", null);
        if ($block_ix < $start) { return false; }
        if (!is_null($end) && $block_ix > (int)$end) { return false; }
        return true;
    }

    /**
     * Derives a vout type from a raw witness scriptPubKey hex, matching the names the daemon
     * reports for ordinary outputs. Gold Rush payouts are native witness v16 programs, which
     * bitcore reports as 'witness_unknown'.
     *
     * @param string $hex
     * @return string
     */
    function shadow_scriptType($hex){
        if (empty($hex) || strlen($hex) < 6 || strlen($hex) % 2 != 0) { return "nonstandard"; }
        $op  = hexdec(substr($hex, 0, 2));
        $len = hexdec(substr($hex, 2, 2));
        if (strlen($hex) != ($len * 2) + 4) { return "nonstandard"; }
        if ($op === 0x00){
            if ($len === 20) { return "witness_v0_keyhash"; }
            if ($len === 32) { return "witness_v0_scripthash"; }
            return "nonstandard";
        }
        if ($op === 0x51 && $len === 32) { return "witness_v1_taproot"; }
        if ($op >= 0x51 && $op <= 0x60) { return "witness_unknown"; }
        return "nonstandard";
    }

    /**
     * Loads every synthetic payout anchored to one active-chain block.
     *
     * NOTE on paging: the RPC documents a 'next_offset' field but this daemon build always
     * reports it as null, so paging is driven by 'total_payouts' and the number of records
     * actually returned. A page that returns nothing stops the loop.
     *
     * @param int $block_ix
     * @return object|null null when the daemon returned nothing usable
     */
    function shadow_loadBlockPayouts($block_ix){
        $page = (int)shadow_cfg("page_size", 100);
        if ($page < 1)    { $page = 1; }
        if ($page > 1000) { $page = 1000; }

        $first = rpc_getgoldrushblock($block_ix, 0, $page, true);
        if (is_null($first) || !isset($first->total_payouts)) { return null; }

        $res = new stdClass();
        $res->height             = $block_ix;
        $res->blockhash          = isset($first->blockhash) ? $first->blockhash : null;
        $res->reward_active      = isset($first->gold_rush_reward_active) ? (bool)$first->gold_rush_reward_active : false;
        $res->scheduled_reward   = isset($first->scheduled_reward) ? @cdec($first->scheduled_reward) : @cdec(0);
        $res->total_payouts      = (int)$first->total_payouts;
        $res->pow_payout_total   = isset($first->pow_payout_total) ? @cdec($first->pow_payout_total) : @cdec(0);
        $res->pos_payout_total   = isset($first->pos_payout_total) ? @cdec($first->pos_payout_total) : @cdec(0);
        $res->proof_observations = isset($first->proof_observations) ? $first->proof_observations : array();
        $res->payouts            = array();

        if ($res->total_payouts < 1) { return $res; }

        $offset = 0;
        while (count($res->payouts) < $res->total_payouts){
            $grb = ($offset === 0) ? $first : rpc_getgoldrushblock($block_ix, $offset, $page, true);
            if (is_null($grb) || !isset($grb->payouts) || count($grb->payouts) < 1){
                // no forward progress - stop rather than spin
                break;
            }
            foreach ($grb->payouts as $p){
                array_push($res->payouts, $p);
            }
            $offset += count($grb->payouts);
        }

        if (count($res->payouts) != $res->total_payouts){
            print(PHP_EOL."Warning: Gold Rush block ".$block_ix." reported ".$res->total_payouts
                ." payouts, ".count($res->payouts)." retrieved.".PHP_EOL);
        }

        return $res;
    }

    /**
     * Best-effort link from a PoW payout to the base-chain transaction that claimed it.
     *
     * getgoldrushblock returns payouts and proof observations as two separate arrays correlated
     * only by order within mode == "pow". Rejected claims produce an observation with no payout,
     * so the mapping is only safe when the counts agree - otherwise no link is reported.
     *
     * @param object $sb result of shadow_loadBlockPayouts()
     * @return array synthetic_txid => claim txid
     */
    function shadow_mapPowClaims($sb){
        $links = array();
        if (is_null($sb) || !isset($sb->payouts)) { return $links; }

        $pow_payouts = array();
        foreach ($sb->payouts as $p){
            if (isset($p->mode) && $p->mode === "pow"){ array_push($pow_payouts, $p); }
        }
        if (count($pow_payouts) < 1) { return $links; }

        $credited = array();
        foreach ($sb->proof_observations as $o){
            $channel = isset($o->credit_channel) ? $o->credit_channel : "";
            $reason  = isset($o->non_credit_reason) ? $o->non_credit_reason : "";
            if ($channel === "pow" && $reason === "" && isset($o->txid)){
                array_push($credited, $o->txid);
            }
        }
        // ambiguous - do not guess
        if (count($credited) != count($pow_payouts)) { return $links; }

        foreach ($pow_payouts as $i => $p){
            $links[$p->synthetic_txid] = $credited[$i];
        }
        return $links;
    }
?>
