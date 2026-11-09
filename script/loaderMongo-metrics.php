<?php
    require __DIR__ . '/../settings.php';
    global $settings;
    include_once __DIR__ . '/../lib/explorerDb.php';
    include_once __DIR__ . '/../lib/explorerMarkets.php';
    include_once __DIR__ . '/../lib/websocketClient.php';
    include_once __DIR__ . '/../lib/explorerUi.php';
    include_once __DIR__ . '/../lib/explorerRpc.php';
    include_once __DIR__ . '/../lib/explorerCustom.php';
    use Decimal\Decimal;

    function createPidFile($since_time){
        $pid = getmypid();
        $str = array("pid" => $pid, "since" => $since_time, "name" => basename(__FILE__), "active" => time());
        $fs = fopen(__DIR__. '/tmp/metrics.pid', 'w');
        fwrite($fs, json_encode($str));
        fclose($fs);
    }
    function deletePidFile(){
        unlink(__DIR__. '/tmp/metrics.pid');
    }
    function checkPidFile(){
        global $settings;
        // check if stop files exist
        if ( file_exists(__DIR__ . '/tmp/stop.metrics' ) || file_exists(__DIR__ . '/tmp/stop.all' )){
            print("Stop-file exists in 'tmp' directory. \nLoader disabled until stop-file removed.\nExiting now...\n\n");
            exit(0);
        }

        // check pidfile exists
        if ( !file_exists( __DIR__ . '/tmp/metrics.pid' )){
            //pid file doesn't exist
            return true;
        }

        // read pidfile
        $piddata = json_decode( file_get_contents( __DIR__.'/tmp/metrics.pid' ) );
        
        // check pid is running
        if (!file_exists( "/proc/$piddata->pid" )){
            // process with pid is not running
            deletePidFile();
            return true;
        }
            
        // check pid number is running same application
        $cmd = trim(file_get_contents("/proc/$piddata->pid/cmdline"), "\0");
        if (\strpos($cmd, basename(__FILE__)) == false) {
            // pid used by another appliction
            deletePidFile();
            return true;
        }
        
        // check since time is exceeding timeout
        if (array_key_exists("loader_timeout_sec",$settings)){ 
            $to = $settings["loader_timeout_sec"];
        } else {
            $to = 120;
        }
        if ($piddata->active + $to < time()){
            // active exceeded - kill process, delete pidfile, return true
            exec("kill -9 ".(int)$piddata->pid);
            deletePidFile();
            return true;
        } else {
            // active not exceeded
            print("MetricsLoader already running with pid=$piddata->pid. \nActivity timeout set to $to seconds was not exceeded.\nExiting now...\n\n");
            exit(0);
        }
    }
    function createCpuMetricFile(){
        if ( file_exists( __DIR__ . '/tmp/metrics.cpu' )){
            unlink(__DIR__. '/tmp/metrics.cpu');
        }

        $stat = file('/proc/stat');
        $cpu = explode(" ", preg_replace("!cpu +!", "", $stat[0])); 
        $proc = new stdClass();
        $proc->user = $cpu[0];
        $proc->nice = $cpu[1];
        $proc->system = $cpu[2];
        $proc->idle = $cpu[3];
        $tmp = explode(' ', file_get_contents('/proc/uptime'));
        $boottime = time() - intval($tmp[0]);
        $data = new stdClass();
        $data->boottime = $boottime;
        $data->since = milliseconds();
        $data->proc = $proc;
        $fs = fopen(__DIR__. '/tmp/metrics.cpu', 'w');
        fwrite($fs, json_encode($data));
        fflush($fs);
        fclose($fs);
    }

    function createNetMetricFile($net_stats){
        if ( file_exists( __DIR__ . '/tmp/metrics.net' )){
            unlink(__DIR__. '/tmp/metrics.net');
        }
        $fs = fopen(__DIR__. '/tmp/metrics.net', 'w');
        fwrite($fs, json_encode($net_stats));
        fflush($fs);
        fclose($fs);
    }

    function getNetMetrics(){
        // get current data
        $now = time();
        $res = rpc_getnettotals();
        $data = new stdClass();
        $data->since = $now;
        $data->recv_bytes = (isset($res->totalbytesrecv)) ? $res->totalbytesrecv : null;
        $data->sent_bytes = (isset($res->totalbytessent)) ? $res->totalbytessent : null;

        $result = new stdClass();
        $result->interval = null;
        $result->recv_vol = null; 
        $result->recv_bps = null;
        $result->sent_vol = null; 
        $result->sent_bps = null;

        // get previous data
        if ( file_exists( __DIR__ . '/tmp/metrics.net' )){
            // prev data available
            $prev_data = json_decode( file_get_contents( __DIR__.'/tmp/metrics.net' ) );
            
            if (!is_null($prev_data->since)) {$result->interval = $now - $prev_data->since;}
            if (!is_null($data->recv_bytes) && !is_null($prev_data->recv_bytes) && $data->recv_bytes >= $prev_data->recv_bytes){
                $result->recv_vol = $data->recv_bytes - $prev_data->recv_bytes;
                if (!is_null($result->interval) && $result->interval !=0 ){
                    $result->recv_bps = (8*@cdec($result->recv_vol)/$result->interval)->toInt();
                }
            } else {$result->recv_vol = null; $result->recv_bps = null;}
            if (!is_null($data->sent_bytes) && !is_null($prev_data->sent_bytes) && $data->sent_bytes >= $prev_data->sent_bytes){
                $result->sent_vol = $data->sent_bytes - $prev_data->sent_bytes;
                if (!is_null($result->interval) && $result->interval !=0 ){
                    $result->sent_bps = (8*@cdec($result->sent_vol)/$result->interval)->toInt();
                }
            } else {$result->sent_vol = null; $result->sent_bps = null;}
        }
        createNetMetricFile($data);
        return $result;

    }
    function getCpuMetric(){
        //global $settings;
        $proc = new stdClass();
        $proc->user = null;
        $proc->nice = null;
        $proc->system = null;
        $proc->idle = null;
        $proc->interval = null;
        // check pidfile exists
        if ( !file_exists( __DIR__ . '/tmp/metrics.cpu' )){
            //metrics.cpu file doesn't exist
            $proc->err = "no metrics.cpu file found";
            return $proc;
        }

        $tmp = explode(' ', file_get_contents('/proc/uptime'));
        $boottime = time() - intval($tmp[0]);

        // read metric file
        $data = json_decode( file_get_contents( __DIR__.'/tmp/metrics.cpu' ) );
        
        if (isset($data->boottime) && isset($data->since) && isset($data->proc)){
            if (round($boottime,-1) == round($data->boottime,-1)) {
                $stat = file('/proc/stat');
                $proc->interval = (milliseconds() - $data->since)/1000;
                $cpu = explode(" ", preg_replace("!cpu +!", "", $stat[0])); 
                $user = $cpu[0] - $data->proc->user;
                $nice = $cpu[1] - $data->proc->nice;
                $system = $cpu[2] - $data->proc->system;
                $idle = $cpu[3] -$data->proc->idle;
                $total = $user + $nice + $system + $idle;
                $proc->user = 100*$user/$total;
                $proc->nice = 100*$nice/$total;
                $proc->system = 100*$system/$total;
                $proc->idle = 100*$idle/$total;
            } else {
                $proc->err = "boottime doesn't match: current=".$boottime." vs. file=".$data->boottime;
            }
        } else {
            $proc->err = "missing metrics.cpu contents";
        }
        return $proc;
    }

    function initialize_clients(){
        global $settings;
        global $dbm;

        // check db connection
        print("Connecting to database: ");
        if (db_checkDbConnected()){
            print("Successful \n");
        } else {
            print("FAILED! \n");
            print("Error Message: database server connection failed \n");
            deletePidFile();
            exit(1);
        }

        db_checkCollections($dbm, $settings['db_name']);

    }


    //----------------------//
    //    Start is here     //
    //----------------------//

    date_default_timezone_set($settings["timezone"]);
    $since_time = time();
    global $rpc;
    global $dbm;
    
    checkPidFile();
    createPidFile($since_time);

    if (!isset($settings["loader_random_delay"]) || $settings["loader_random_delay"]){
        $rd = rand(0, 10);
        echo "Waiting for the random delay of ".$rd." seconds...".PHP_EOL;
        sleep($rd);

    }

    initialize_clients();

    $metric_date = new DateTime();
    $metric_timestamp = mktime($metric_date->format("H"),$metric_date->format("i"),0,$metric_date->format("m"),$metric_date->format("d"),$metric_date->format("Y"));

    $market_metric  = new stdClass();
    (int)$market_metric->_id = $metric_timestamp;
    $market_metric->base_prices = array();
    $market_metric->metrics = array();

    $chain_metric  = new stdClass();
    (int)$chain_metric->_id = $metric_timestamp;
    $chain_metric->metrics = array();

    $host_metric   = new stdClass();
    (int)$host_metric->_id = $metric_timestamp;
    $host_metric->daemon = array();
    $host_metric->system = array();
    $host_metric->database = array();

    // collect host metrics
        // daemon_response_time
            print("Connecting to daemon: ");
            try {
                $dcst = milliseconds();
                $bc_info=rpc_getblockcount();
                $daemon_response_time = round((milliseconds() - $dcst)/100,3);
                print("Successful\n");
                print(" Current block height: ".$bc_info."\n");
            } catch (Exception $e) {
                print("FAILED! \n");
                print("Error Message: ".$e->getMessage()."\n");
                $daemon_response_time = -1;
            }
            print("Collecting Host Metrics ".PHP_EOL);
            $host_metric->daemon['daemon_response_time'] = $daemon_response_time;
        // daemon net traffic
            print(str_pad(" Collecting NET TRAFFIC mertic...",60," "));
            $host_metric->daemon['traffic'] = getNetMetrics();
            print("Done".PHP_EOL);
        // cpu load 
            print(str_pad(" Collecting CPU LOAD mertic...",60," "));
            $host_metric->system['cpu_load'] = getCpuMetric();
            createCpuMetricFile();
            print("Done".PHP_EOL);
        // ram usage
            print(str_pad(" Collecting MEMORY USAGE mertic...",60," "));
            $mem_usage = new stdClass();
            $mem_usage->total = null;
            $mem_usage->used = null;
            $mem_usage->free = null;
            $mem_usage->shared = null; 
            $mem_usage->cache = null;
            $mem_usage->available = null;

            $swap_usage = new stdClass();
            $swap_usage->total = null;
            $swap_usage->used = null;
            $swap_usage->free = null;

            $free = shell_exec('free -b');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            
            $mem = explode(" ", $free_arr[1]);
            if ($mem[0] == 'Mem:' || $mem[0] == 'Memory:'){
               $mem = array_filter($mem,'strlen');
                $mem = array_merge($mem);

                $mem_usage->total = (int)$mem[1];
                $mem_usage->used = (int)$mem[2]; 
                $mem_usage->free = (int)$mem[3];
                $mem_usage->shared = (int)$mem[4];
                $mem_usage->cache = (int)$mem[5];
                $mem_usage->available = (int)$mem[6];
            }
            if (count($free_arr)>2 ){
                $swap = explode(" ", $free_arr[2]);
                if ($swap[0] == 'Swap:' || $swap[0] == 'Swp:'){
                    $swap = array_filter($swap, 'strlen');
                    $swap = array_merge($swap); 

                    $swap_usage->total = (int)$swap[1];
                    $swap_usage->used = (int)$swap[2];
                    $swap_usage->free = (int)$swap[3];
                }
            }
            $host_metric->system['memory_usage'] = $mem_usage;
            $host_metric->system['swap_usage'] = $swap_usage;
            print("Done".PHP_EOL);
        // disk usage
            print(str_pad(" Collecting DISK USAGE mertic...",60," "));
            $disk = new stdClass();
            $disk->free = disk_free_space ( "/" );
            $disk->total = disk_total_space ( "/" );
            $host_metric->system['disk_usage'] = $disk;
            print("Done".PHP_EOL);
        // db size
            print(str_pad(" Collecting DB SIZE mertic...",60," "));
            $db = db_sizeDb();
            $db_size = new stdClass();
            $db_size->data = null;
            $db_size->storage = null;
            $db_size->index = null;
            $db_size->total = null;
            if (!empty($db)){
                $db_size->data = $db['data'];
                $db_size->storage = $db['storage'];
                $db_size->index = $db['index'];
                $db_size->total = $db['total'];
            } 
            $host_metric->database['db_size'] = $db_size;
            print("Done".PHP_EOL);
        db_recordHostDataBulk($host_metric);

        

    // collect market metrics
        if (array_key_exists("load_markets",$settings)){
            print("Collecting Market Metrics".PHP_EOL);
            if ($settings["load_markets"]){
                if (array_key_exists("markets",$settings)){
                    foreach($settings["markets"] as $market){
                        if ($market["exchange"] == "coinbase-pro"){
                            print(str_pad(" Collecting Coinbase Pro (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getCoinbaseProMetrics($market["base"],$market["quote"]);
                            if(!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "quantadex"){
                            print(str_pad(" Collecting QuantaDex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getQuantadexMetrics($market["base"],$market["quote"]);
                            if(!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "crex24") {
                            print(str_pad(" Collecting Crex24 (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getCrex24v2Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "graviex") {
                            print(str_pad(" Collecting Graviex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getGraviexMetrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "stex") {
                            print(str_pad(" Collecting STEX (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getStexv3Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "binance") {
                            print(str_pad(" Collecting Binance (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getBinancev1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "escodex") {
                            print(str_pad(" Collecting ESCODEX (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getEscodexMetrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "bitforex") {
                            print(str_pad(" Collecting BitForex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getBitforexv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "hitbtc") {
                            print(str_pad(" Collecting HitBTC (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getHitbtcv2Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "kukoin") {
                            print(str_pad(" Collecting KuKoin (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getKukoinv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "exmo") {
                            print(str_pad(" Collecting Exmo (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getExmov1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "tradesatoshi") {
                            print(str_pad(" Collecting Tradesatoshi (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getTradesatoshiMetrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "coinexchange") {
                            print(str_pad(" Collecting Coinexchange (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getCoinexchangev1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "bitfinex") {
                            print(str_pad(" Collecting Bitfinex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getBitfinexv2Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "poloniex") {
                            print(str_pad(" Collecting Poloniex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getPoloniexv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "bittrex") {
                            print(str_pad(" Collecting Bittrex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getBittrexv3Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "birake") {
                            print(str_pad(" Collecting Birake (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getBirakev1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "coinex") {
                            print(str_pad(" Collecting CoinEx (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getCoinexv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "livecoin") {
                            print(str_pad(" Collecting Livecoin (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getLivecoinv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "mercatox") {
                            print(str_pad(" Collecting Mercatox (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getMercatoxv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "bisq") {
                            print(str_pad(" Collecting Bisq (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getMercatoxv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "deliondex") {
                            print(str_pad(" Collecting DelionDEX (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getDelionv2Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "probit") {
                            print(str_pad(" Collecting PROBIT (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getProbitv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "bitfineon") {
                            print(str_pad(" Collecting Bitfineon (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getBitfineonv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "huobi") {
                            print(str_pad(" Collecting Huobi (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getHuobiv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "southxchange") {
                            print(str_pad(" Collecting SouthXchange (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getSouthxchangev1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "hotbit") {
                            print(str_pad(" Collecting Hotbit (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getHotbitv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "altilly") {
                            print(str_pad(" Collecting Altilly (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getAltillyv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "finexbox"){ 
                            print(str_pad(" Collecting Finexbox (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getFinexboxv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "okex"){ 
                            print(str_pad(" Collecting OKex (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getOkexv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "vcc"){ 
                            print(str_pad(" Collecting VCC (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getVccv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "exchangeassets"){ 
                            print(str_pad(" Collecting ExchangeAssets (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getExchangeAssetsv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "dex-trade"){ 
                            print(str_pad(" Collecting Dex-Trade (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getDexTradev1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "xt.com"){ 
                            print(str_pad(" Collecting XT.com (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getXTv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                            
                        } else if ($market["exchange"] == "coinsbit.io"){ 
                            print(str_pad(" Collecting coinsbit.io (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getCoinsBitv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "quan2um"){ 
                            print(str_pad(" Collecting quan2um (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getQuan2umv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "tradeogre"){ 
                            print(str_pad(" Collecting TradeOgre (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getTradeOgrev1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "whitebit"){ 
                            print(str_pad(" Collecting WhiteBit (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getWhiteBitv4Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        } else if ($market["exchange"] == "xeggex"){ 
                            print(str_pad(" Collecting XeggeX (".$market["base"].") mertic...",60," "));
                            $mmetric = mkt_getXeggexv1Metrics($market["base"],$market["quote"]);
                            if (!empty($mmetric)){
                                array_push($market_metric->metrics,$mmetric);
                                print("Done".PHP_EOL);
                            }
                        }
                    }
                }
                if (array_key_exists("base_prices",$settings)){
                    foreach($settings["base_prices"] as $base){
                        if ($base["exchange"] == "coinbase-pro"){
                            print(str_pad(" Collecting Coinbase Pro (".$base["fiat"].") price...",60," "));
                            $mmetric = mkt_getCoinbaseProMetrics($base["base"],$base["fiat"]);
                            if (!empty($mmetric)){
                                $market_metric->base_prices[$base["fiat"]] = $mmetric->latest;
                                $market_metric->base_changes[$base["fiat"]] = $mmetric->percent_change;
                                print("Done".PHP_EOL);
                            }
                        }
                    }
                }
                // calculate vwap (volume weighted average price) & volume_total
                if (count($market_metric->metrics)>0){
                    $p_num = @cdec(0);
                    $vol = @cdec(0);
                    foreach ($market_metric->metrics as $mm){
                        $qv = @cdec($mm->quote_volume);
                        if ($qv > 0){
                            $p_num += (@cdec($mm->latest) * $qv);
                            $vol += $qv;
                        }
                    }
                    if ($vol > 0){
                        $market_metric->vwap = @cmdec(($p_num/$vol)->round(max($settings['market_precision'],$settings['processing_precision'],8)));
                    } else {
                        $market_metric->vwap = null;
                    }
                    $market_metric->volume_total = @cmdec($vol);
                } else {
                    $market_metric->vwap = @cmdec(0);
                    $metric_date->volume_total = @cmdec(0);
                }
                // record market metrics 
                if (count($market_metric->metrics) > 0 || count($market_metric->base_prices) > 0){
                    db_recordMarketDataBulk($market_metric);
                } 
            }
        }

    // collect chain metrics
        print("Collecting Chain Metrics".PHP_EOL);
        try {
            // hashrate
            print(str_pad(" Collecting HASHRATE mertic...",60," "));
            try {
                $hr = @cdec(rpc_getnetworkhashps());
                $hr = cmdec($hr);
                $chain_metric->metrics['hashrate'] = $hr;
                print("Done".PHP_EOL);
            } catch (Exception $e){
                print("Failed".PHP_EOL);
            }
            // difficulty
            print(str_pad(" Collecting DIFFICULTY mertic...",60," "));
            $dif = @cdec(rpc_getdifficulty());
            $dif = cmdec($dif);
            $chain_metric->metrics['difficulty'] = $dif;
            print("Done".PHP_EOL);
            // masternode count
            print(str_pad(" Collecting MASTERNODE_COUNT mertic...",60," "));
            $dummy = new stdClass();
                $dummy->total = null;
                $dummy->enabled = null;
                $dummy->stable = null;
                $dummy->inqueue = null;
                $dummy->ipv4 = null;
                $dummy->ipv6 = null;
                $dummy->onion = null;
            $chain_metric->metrics['masternode_count'] = (isset($settings["show_pages"]["masternodes"]) && $settings["show_pages"]["masternodes"]) ? ui_getMasternodeCount() : $dummy ;
            print("Done".PHP_EOL);
            // record chain metrics 
            if (count($chain_metric->metrics) > 0){
                db_recordChainDataBulk($chain_metric);
            } 
        } catch (Exception $ex) {
            print(PHP_EOL);
            var_dump($ex->getMessage());
            print(PHP_EOL);
        }

    // collect masternode list cache
        if (isset($settings["masternode_caching_enabled"]) && $settings["masternode_caching_enabled"]){
            try {
                $mncache_metric = new stdClass();
                $mncache_metric->timestamp = new MongoDB\BSON\UTCDateTime(); //$metric_timestamp;
                // 1. get MN count
                print(str_pad("Collecting MASTERNODE COUNT cache...",60," "));
                $mncache_metric->count =  $chain_metric->metrics['masternode_count'];
                print("Done".PHP_EOL);

                // 2. get MN list
                print(str_pad("Collecting MASTERNODE LIST cache...",60," "));
                $mn_list = ui_getMasternodeStatus("", true);
                $mncache_metric->list = array_values($mn_list);
                print("Done".PHP_EOL);
                if (count($mncache_metric->list)>0){
                    db_recordMnCacheDataBulk($mncache_metric);
                }
                
            } catch (Exception $e){
                print("Failed".PHP_EOL);
                var_dump($e->getMessage());
                print(PHP_EOL);
            }
        }

    // calculate chain stats
        if (isset($settings["calculate_chain_stats"]) && $settings["calculate_chain_stats"]){
            print(str_pad("Calculating CHAINSTATS data...",60," "));
            try {
        
            $granularity_sec = 86400;
            $recalculate = false;
            $actual_time = time();

            //   MAIN PROCEDURE   //
            // I. Get last calculated data
                $last = db_getLastChainStats();
                $last_block = db_getLastBlocks(1,0);
                // 1. get genesis timestamp
                $genesis = db_getBlockByHeight(0);
                $min_time = $genesis[0]->block_time_sec - $genesis[0]->block_time_sec % $granularity_sec;
                global $min_time;
                $to_time = $actual_time - $actual_time % $granularity_sec + $granularity_sec;

                if (count($last_block)>0) {
                    $actual_time = $last_block[0]->block_time_sec;
                    $to_time = $actual_time - $actual_time % $granularity_sec + $granularity_sec;

                    if (count($last)>0 && !$recalculate){
                        // do recursive validation
                        $valid = ui_validateChainStats($last[0], $granularity_sec);

                        if ($last[0]->_id+$granularity_sec < $to_time ){
                            for ($timestamp = $last[0]->_id+$granularity_sec; $timestamp < $to_time; $timestamp += $granularity_sec){
                                // 2. load block stats
                                $chain_stats = ui_calculateBlockStats($timestamp, $granularity_sec);
                                if (!is_null($chain_stats)){
                                    // calculate extensions
                                    $chain_stats = ui_calculateBlockStatsExtensions($chain_stats,$granularity_sec);

                                    // record $chain_stats
                                    $result = db_updateChainStats($chain_stats);
                                }
                            }
                        }
                        print("Done".PHP_EOL);
                    } else {
                        // initial load from zero or complete re-calc
                        if (count($genesis)>0){
                            
                            //iterate through each granularity period until $actual_time
                            for ($timestamp = $min_time; $timestamp < $to_time; $timestamp += $granularity_sec){
                                // 2. load block stats
                                $chain_stats = ui_calculateBlockStats($timestamp, $granularity_sec);
                                if (!is_null($chain_stats)){
                                    // calculate extensions
                                    $chain_stats = ui_calculateBlockStatsExtensions($chain_stats,$granularity_sec);
                                    // record $chain_stats
                                    $result = db_updateChainStats($chain_stats);
                                }
                            }
                            print("Done".PHP_EOL);
                        } else {
                            echo "Genesis block not found, skipping.".PHP_EOL;
                        }
                    }

                } else {
                    echo "Cannot get last block, skipping.".PHP_EOL;
                }
                
            } catch(Exception $e) {
                print("Failed".PHP_EOL);
                var_dump($e->getMessage());
                print(PHP_EOL);
            }
        }        

    // special features integration
        if (isset($settings["special_features"]) && count($settings["special_features"])>0){
            print("Processing Special Features Metrics".PHP_EOL);
            // MDAO API
            if (isset($settings["special_features"]["MDAO_API"]) && $settings["special_features"]["MDAO_API"]){
                print(str_pad(" Collecting MDAO balances...",60," "));
                $mdao_balances = cf_getMdaoBalances();
                // save data to db
                $success = db_insertMdaoBalances($mdao_balances);
                print("Done".PHP_EOL);
            }
        }


    deletePidFile();
?>