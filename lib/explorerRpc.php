<?php
    include_once 'rpcClient.php';
	/**
	 * Performs a 'getblockchaininfo' jsonRCP request returns results as object
	 *
	 * @return object
	 */
    function rpc_getblockchaininfo($is_fatal = false){
        $res = new stdClass();
        global $rpc;
        $res = $rpc->getblockchaininfo();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getblockchaininfo):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
	/**
	 * Performs a 'getblockcount' jsonRCP request returns results as int
	 *
	 * @return int
	 */
    function rpc_getblockcount($is_fatal = false){
        (int)$res = -1;
        global $rpc;
        $res = $rpc->getblockcount();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getblockcount):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
	/**
	 * Performs a 'getpeerinfo' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_getpeerinfo($is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getpeerinfo();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getpeerinfo):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
	/**
	 * Performs a 'getmempoolinfo' jsonRCP request returns results as object
	 *
	 * @return object
	 */
    function rpc_getmempoolinfo($is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getmempoolinfo();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getmempoolinfo):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    /**
	 * Performs a 'rpc_getrawmempool' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_getrawmempool($is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getrawmempool();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getrawmempool):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    /**
	 * Performs a 'rpc_sendrawtransaction' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_sendrawtransaction($hex, $allowhighfees = null, $is_fatal = false){
        $res = array();
        global $rpc;
        if (!is_null($allowhighfees)){
            $res = $rpc->sendrawtransaction($hex, $allowhighfees);
        } else {
            $res = $rpc->sendrawtransaction($hex);
        }
        
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_sendrawtransaction):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res;
    }




    /**
	 * Performs a 'getblockhash' jsonRCP request returns results as string
	 *
     * @param int $block_height
	 * @return string
	 */
    function rpc_getblockhash($block_height, $is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getblockhash($block_height);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getblockhash):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    /**
	 * Performs a 'getblock' jsonRCP request returns results as object
	 *
     * @param string $block_hash
	 * @return object
	 */
    function rpc_getblock($block_hash, $is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getblock($block_hash);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getblock):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    /**
	 * Performs a 'getblockheader' jsonRCP request returns results as object
	 *
     * @param string $block_hash
	 * @return object|null
	 */
    function rpc_getblockheader($block_hash){
        global $rpc;
        $res = $rpc->getblockheader($block_hash, true);
        return is_null($res->error) ? $res->result : null;
    }
    /**
	 * Performs a 'getrawtransaction' jsonRCP request returns results as object
	 *
     * @param string $tx_id
	 * @return object
	 */
    function rpc_getrawtransaction($tx_id, $decode = 1, $is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getrawtransaction($tx_id, $decode);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getrawtransaction):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'decoderawtransaction' jsonRCP request returns results as object
	 *
     * @param string $hex
	 * @return object
	 */
    function rpc_decoderawtransaction($hex, $is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->decoderawtransaction($hex);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_decoderawtransaction): ".$res->error->message.PHP_EOL);
                die();
            }
        }
        return $res;
    }

    /**
	 * Performs a 'gettxout' jsonRCP request returns results as object
	 *
     * @param string $hex
     * @param int $n
	 * @return object
	 */
    function rpc_gettxout($hex, $n, $is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->gettxout($hex,$n);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_gettxout): ".$res->error->message.PHP_EOL);
                die();
            }
        }
        return $res;
    }

    /**
	 * Performs a 'getconnectioncount' jsonRCP request returns results integer
	 *
	 * @return int
	 */
    function rpc_getconnectioncount($is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getconnectioncount();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getconnectioncount):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getinfo' jsonRCP request returns results as object
	 *
     * @param string $method ('getnetworkinfo' for bitcore 0.17+; 'getinfo' for older)
	 * @return object
	 */
    function rpc_getinfo($is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getinfo();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getinfo):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    function rpc_getnetworkinfo($is_fatal = false){
        $res = array();
        global $rpc;
        $res = $rpc->getnetworkinfo();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getnetworkinfo):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'listmasternodes' jsonRCP request returns results as array
	 *
     * @param string $filter ( optional filter for masternode search (address or txid))
	 * @return array
	 */
    function rpc_listmasternodes($filter = "", $is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->listmasternodes($filter);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_listmasternodes):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    function rpc_masternode_list($filter = "", $is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->masternode("list",$filter);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_masternode_list):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    function rpc_smartnode_list_json($filter = "", $is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->smartnode("list","json",$filter);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_smartnode_list_json):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    function rpc_masternodelist($filter = "", $is_fatal = false){  //DIP2
        (string)$res = "";
        global $rpc;
        $res = $rpc->masternodelist("json", $filter);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_masternodelist):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    function rpc_masternodelistfull($filter = "", $is_fatal = false){ //PHC
        (string)$res = "";
        global $rpc;
        $res = $rpc->masternode("list", "full", $filter);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_masternodelistfull):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    function rpc_masternodelistdetails($filter = "", $is_fatal = false){ //PHC
        (string)$res = "";
        global $rpc;
        $res = $rpc->masternode("list-details", $filter);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_masternodelistdetails):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    /**
	 * Performs a 'masternodecount' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_masternodecount($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->masternode("count");
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_masternodecount):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getmasternodecount' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_getmasternodecount($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getmasternodecount();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getmasternodecount:" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'smartnodecount' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_smartnodecount($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->smartnode("count");
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_smartnodecount):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'mnsync status' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_mnsyncstatus($is_fatal = false){
        $res = new stdClass();
        global $rpc;
        try {
            $res = $rpc->mnsync("status");
        } catch (Exception $e) {
            $res->error->message = $e->getMessage();
            $res->result = null;
        }
        if (is_null($res)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_mnsyncstatus:" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }


    /**
	 * Performs a 'getnetworkhashps' jsonRCP request returns results as number
	 *
	 * @return array
	 */
    function rpc_getnetworkhashps($is_fatal = false){
        $res = new stdClass();
        global $rpc;
        global $settings;
        $mult = isset($settings["gethashps_multiplier"]) ? $settings["gethashps_multiplier"] : 1;
        try {
            $res = $rpc->getnetworkhashps();
            $res->result = $res->result * $mult;
        } catch (Exception $e) {
            $res->error->message = $e->getMessage();
            $res->result = null;
        }
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getnetworkhashps:" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }
    /**
	 * Performs a 'getdifficulty' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_getdifficulty($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getdifficulty();

        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getdifficulty:" . $res->error->message.PHP_EOL);
                die();
            }
        } else if (!is_numeric($res->result)){     
            $r = json_decode(json_encode($res->result), true);
            if (array_key_exists("proof-of-work", $r)){
                return $r["proof-of-work"];
            }
        }
        
        return $res->result;
    }
    /**
	 * Performs a 'getassetdata' jsonRCP request returns results as array
	 *
     * @param int $asset_name
	 * @return array
	 */
    function rpc_getassetdata($asset_name, $is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getassetdata($asset_name);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getassetdata($asset_name):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getstakingstatus' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_getstakingstatus($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getstakingstatus();
        if (!is_null($res->result)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getstakingstatus():" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getstakesplitthreshold' jsonRCP request returns results as array
	 *
	 * @return array
	 */
    function rpc_getstakesplitthreshold($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getstakesplitthreshold();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getstakesplitthreshold():" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }


    /**
	 * Performs a 'getdelegatedbalance' jsonRCP request returns results as array
	 *
     * @param string $account
	 * @return array
	 */

    /**
	 * Performs a 'liststakingaddresses' jsonRCP request returns results as array
	 *
     * @param string $address
	 * @return array
	 */
    function rpc_liststakingaddresses($address = null, $is_fatal = false){
        (string)$res = "";
        global $rpc;

        $res = (is_null($address)) ? $rpc->liststakingaddresses() : $rpc->liststakingaddresses($address);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_liststakingaddresses(".$address."):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'validateaddress' jsonRCP request returns results as object
	 *
     * @param string $address
	 * @return object
	 */
    function rpc_validateaddress($address = null, $is_fatal = false){
        (string)$res = "";
        global $rpc;

        $res = $rpc->validateaddress($address);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_validateaddress(".$address."):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'verifymessage' jsonRCP request returns results as array
	 *
     * @param string $address
     * @param string $signature
     * @param string $message
	 * @return array
	 */
    function rpc_verifymessage($address = null, $signature = null, $message =null, $is_fatal = false){
        (string)$res = "";
        global $rpc;

        $res = $rpc->verifymessage($address,$signature,$message);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_verifymessage(".$address.",".$signature.",".$message."):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getnewstakingaddress' jsonRCP request returns results as array
	 *
     * @param string $account
	 * @return array
	 */
    function rpc_getnewstakingaddress($account = "", $is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getnewstakingaddress($account);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error: rpc_getnewstakingaddress(".$account."):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'delegatoradd' jsonRCP request returns results as array
	 *
     * @param string $account
	 * @return array
	 */
    /**
	 * Performs a 'delegatorremove' jsonRCP request returns results as object
	 *
     * @param string $account
	 * @return object
	 */

    /**
	 * Performs a 'backupwallet' jsonRCP request returns results as array
	 *
     * @param string $account
	 * @return array
	 */

    /**
	 * Performs a 'getcoldstakingbalance' jsonRCP request returns results as array
	 *
     * @param string $account
	 * @return array
	 */

    /**
	 * Performs a 'setban' jsonRCP request returns results as array
	 *
     * @param string $ip_address
     * @param int $bantime
	 * @return array
	 */

    /**
	 * Performs a 'listdelegators' jsonRCP request returns results as array
	 *
     * @param bool $with_blacklisted
	 * @return array
	 */

    /**
	 * Performs a 'getnettotals' jsonRCP request returns results as object
	 *
	 * @return object
	 */
    function rpc_getnettotals($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getnettotals();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getnettotals):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getblocktemplate' jsonRCP request returns results as object
	 *
	 * @return object
	 */
    function rpc_getblocktemplate($is_fatal = false){
        (string)$res = "";
        global $rpc;
        $res = $rpc->getblocktemplate();
        // Bitcoin Core 0.13+ refuses a template request without the segwit rule.
        if (!is_null($res->error)){
            $res = $rpc->getblocktemplate(["rules" => ["segwit"]]);
        }
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getblocktemplate):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getgoldrushblock' jsonRCP request, returns the synthetic shadow-ledger
	 * payouts anchored to one active-chain block.
	 * Available without -shadowindex.
	 *
     * @param int $block_ix - active chain height
     * @param int $offset - zero-based payout offset
     * @param int $count - max payouts to return (1-1000)
	 * @return object
	 */
    function rpc_getgoldrushblock($block_ix, $offset = 0, $count = 100, $is_fatal = false){
        $res = new stdClass();
        global $rpc;
        $res = $rpc->getgoldrushblock((int)$block_ix, (int)$offset, (int)$count);
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getgoldrushblock):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getgoldrushstate' jsonRCP request returns results as object
	 *
	 * @return object
	 */
    function rpc_getgoldrushstate($is_fatal = false){
        $res = new stdClass();
        global $rpc;
        $res = $rpc->getgoldrushstate();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getgoldrushstate):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    /**
	 * Performs a 'getquantumquasarinfo' jsonRCP request returns results as object
	 *
	 * @return object
	 */
    function rpc_getquantumquasarinfo($is_fatal = false){
        $res = new stdClass();
        global $rpc;
        $res = $rpc->getquantumquasarinfo();
        if (!is_null($res->error)){
            if ($is_fatal) {
                throw new Exception(PHP_EOL."Fatal RPC Error (rpc_getquantumquasarinfo):" . $res->error->message.PHP_EOL);
                die();
            }
        }
        return $res->result;
    }

    //// INIT CONNECTION ////
    global $settings;
    global $rpc;

    $rpc = new rpcClient(rpc_getServerLink($settings['rpc_host'], $settings['rpc_port'], $settings['rpc_protocol'], $settings['rpc_user'], $settings['rpc_pass']));


?>