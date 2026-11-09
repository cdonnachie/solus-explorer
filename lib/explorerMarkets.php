<?php
//include_once "explorerDb.php";
use Decimal\Decimal;
    // BitShares API endpoints:
    //   GET: https://explorer.bitshares-kibana.info/ticker?base=BRIDGE.BTC&quote=BRIDGE.MARC
    //   WebSocket: wss://bitshares.openledger.info/ws

// ---- ALTILLY ----
    function mkt_getAltillyv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Altilly";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.altilly.com/api/public/ticker/".$quote.$base));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (!isset($response->error)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->last));

                if (!empty($ticker->open)){
                    $metric->percent_change = cmdec((100*@cdec($ticker->last)/@cdec($ticker->open))-100);
                } else {
                    $metric->percent_change = cmdec(0);
                }

                $metric->base_volume = cmdec(@cdec($ticker->volumeQuote));
                $metric->quote_volume = cmdec(@cdec($ticker->volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Altilly ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }    

// ---- BINANCE ----
    function mkt_getBinancev1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Binance";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.binance.com/api/v1/ticker/24hr?symbol=".$quote.$base));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (!isset($response->success)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->lastPrice)) ;
                $metric->percent_change = cmdec(@cdec($ticker->priceChangePercent));
                $metric->base_volume = cmdec(@cdec($ticker->quoteVolume));
                $metric->quote_volume = cmdec(@cdec($ticker->volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->askPrice)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bidPrice));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Binance ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- BIRAKE ----
    function mkt_getBirakev1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Birake";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.birake.com/public/v3/ticker"));
        } catch (Exception $ex){
        }
        if (isset($response)){
            //var_dump($response);
            //readline();
            if (is_array($response)){ 
                foreach ($response as $ticker){
                    if ($ticker->tradingPairs == $quote."_".$base){
                        $metric->latest = cmdec(@cdec($ticker->lastPrice));
                        $metric->percent_change = null;
                        $metric->base_volume = cmdec(@cdec($ticker->baseVolume24h));
                        $metric->quote_volume = cmdec(@cdec($ticker->quoteVolume24h));
                        $metric->lowest_ask = cmdec(@cdec($ticker->lowestAsk)); 
                        $metric->highest_bid = cmdec(@cdec($ticker->highestBid));
                        
                        break;
                    }
                }

            } else {
                print(PHP_EOL . "Error: failed to request Birake ticker: request returned no data" . PHP_EOL);
                return null;
                
            }
        } else {
            print(PHP_EOL . "Error: failed to request Birake ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- BISQ ----
    function mkt_getBisqv1Metrics($base, $quote){
        //https://bisq.network/markets/?currency=aeon_btc
        $metric  = new stdClass();
        $metric->exchange = "Bisq";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://markets.bisq.network/api/ticker/?market=".strtolower($quote)."_".strtolower($base)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->data)){
                $ticker = $response->data;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = null;
                $metric->base_volume = cmdec(@cdec($ticker->volume_right)) ;
                $metric->quote_volume = cmdec(@cdec($ticker->volume_left));
                $metric->lowest_ask = cmdec(@cdec($ticker->sell)); 
                $metric->highest_bid = cmdec(@cdec($ticker->buy));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Bisq ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- Bitfineon ----
    function mkt_getBitfineonv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Bitfineon";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        $pair = strtoupper($base)."_".strtoupper($quote);
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://node1.bitfineon.com/market/get-market-summary/".$pair));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->status) && $response->status == "Success"){
                $ticker = $response->data;
                $metric->latest = cmdec(@cdec($ticker->Last));
                $metric->percent_change = cmdec(@cdec($ticker->PercentChange));
                $metric->base_volume = cmdec(@cdec($ticker->BaseVolume)); ;
                $metric->quote_volume = cmdec(@cdec($ticker->QuoteVolume));
                $metric->lowest_ask = cmdec(@cdec($ticker->LowestAsk));
                $metric->highest_bid = cmdec(@cdec($ticker->HeighestBid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Bitfineon ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- BITFINEX ----
    function mkt_getBitfinexv2Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Bitfinex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api-pub.bitfinex.com/v2/ticker/t".$quote.$base));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (is_array($response)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker[6]));
                $metric->percent_change = cmdec(@cdec($ticker[5]));
                $metric->base_volume = null;
                $metric->quote_volume = cmdec(@cdec($ticker[7]));
                $metric->lowest_ask = cmdec(@cdec($ticker[2])); 
                $metric->highest_bid = cmdec(@cdec($ticker[0]));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Bitfinex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- BITFOREX ----
    function mkt_getBitforexv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "BitForex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.bitforex.com/api/v1/market/ticker?symbol=coin-".strtolower($base)."-".strtolower($quote)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->data)){
                $ticker = $response->data;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = null;
                $metric->base_volume = null ;
                $metric->quote_volume = cmdec(@cdec($ticker->vol));
                $metric->lowest_ask = cmdec(@cdec($ticker->sell)); 
                $metric->highest_bid = cmdec(@cdec($ticker->buy));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request BitForex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- BITTREX ----
    //https://api.bittrex.com/api/v1.1/public/getmarketsummary?market=btc-ltc
    //https://international.bittrex.com/Market/Index?MarketName=BTC-RVN
    function mkt_getBittrexv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Bittrex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.bittrex.com/api/v1.1/public/getmarketsummary?market=".strtolower($base)."-".strtolower($quote)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->result)){
                $ticker = $response->result[0];
                $metric->latest = cmdec(@cdec($ticker->Last));
                $metric->percent_change = cmdec(100*@cdec($ticker->Last)/@cdec($ticker->PrevDay) - 100);
                $metric->base_volume = cmdec(@cdec($ticker->BaseVolume)) ;
                $metric->quote_volume = cmdec(@cdec($ticker->Volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->Ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->Bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Bittrex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

    //https://api.bittrex.com/v3/markets/LTC-BTC/summary //(base: LTC, quote: BTC)
    //https://api.bittrex.com/v3/markets/LTC-BTC/ticker
    function mkt_getBittrexv3Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Bittrex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.bittrex.com/v3/markets/".strtoupper($quote)."-".strtoupper($base)."/ticker"));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->symbol)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->lastTradeRate));
                $metric->lowest_ask = cmdec(@cdec($ticker->askRate)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bidRate));     
                try {
                    $ticker = json_decode(file_get_contents("https://api.bittrex.com/v3/markets/".strtoupper($quote)."-".strtoupper($base)."/summary"));
                    $metric->percent_change = cmdec(@cdec($ticker->percentChange));
                    $metric->base_volume = cmdec(@cdec($ticker->quoteVolume)) ;
                    $metric->quote_volume = cmdec(@cdec($ticker->volume));
                } catch (Exception $ex){
                    $metric->percent_change = null;
                    $metric->base_volume = null;
                    $metric->quote_volume = null;
                }
           
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Bittrex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- Coinbase Pro ----
    function mkt_getCoinbaseProMetrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Coinbase Pro";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $curl = curl_init("https://api.exchange.coinbase.com/products/".$base."-".$quote."/ticker");
            curl_setopt($curl, CURLOPT_USERAGENT, "cURL");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $res = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($res);
        } catch (Exception $ex){
        }
        if (is_object($response)){
            if (isset($response->price)){
                $metric->latest = cmdec(@cdec($response->price));
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = cmdec(@cdec($response->volume));
                $metric->lowest_ask = cmdec(@cdec($response->ask));
                $metric->highest_bid = cmdec(@cdec($response->bid));
                
                // get stats
                $curl = curl_init("https://api.exchange.coinbase.com/products/".$base."-".$quote."/stats");
                curl_setopt($curl, CURLOPT_USERAGENT, "cURL");
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                $sres = curl_exec($curl);
                curl_close($curl);
                $sresponse = json_decode($sres);
                if (is_object($sresponse)){
                    if (isset($sresponse->open)){
                        try {
                        $pcent =  100*(@cdec($sresponse->last) - @cdec($sresponse->open))/@cdec($sresponse->open);
                        $metric->percent_change = cmdec($pcent);
                        } catch (Exception $e) {

                        }
                    }
                }

            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }

            
        } else {
            print(PHP_EOL . "Error: failed to request Coinbase Pro ".$base."-".$quote." ticker" . PHP_EOL);
            return null;
        }
        
        
        return $metric;
    }

// ---- CoinsBit.io ----
    // https://coinsbit.io/api/v1/public/ticker?market=ETH_BTC
    function mkt_getCoinsBitv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "coinsbit.io";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://coinsbit.io/api/v1/public/ticker?market=".strtoupper($quote)."_".strtoupper($base)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->success) && $response->success && isset($response->result)){
                $ticker = $response->result;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = cmdec(@cdec($ticker->change));
                $metric->base_volume = null ;
                $metric->quote_volume = cmdec(@cdec($ticker->volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request coinsbit.io ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- CoinEx ----
    function mkt_getCoinexv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "CoinEx";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.coinex.com/v1/market/ticker?market=".strtoupper($quote).strtoupper($base)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->data) && isset($response->data->ticker)){
                $ticker = $response->data->ticker;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = null;
                $metric->base_volume = null ;
                $metric->quote_volume = cmdec(@cdec($ticker->vol));
                $metric->lowest_ask = cmdec(@cdec($ticker->sell)); 
                $metric->highest_bid = cmdec(@cdec($ticker->buy));
                try {
                    $metric->percent_change = cmdec(100*((@cdec($ticker->last)/@cdec($ticker->open))-1));
                } catch (Exception $e){}
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request CoinEx ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- COINEXCHANGE.IO dead ----
    function mkt_getCoinexchangev1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Coinexchange";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        $metric->latest = null;
        // get all currency pairs for base 
        try {
            $response = json_decode(file_get_contents("https://www.coinexchange.io/api/v1/getmarkets"));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (isset($response->success) && $response->success){
                if (isset($response->result) && is_array($response->result)){
                    $n = array_filter($response->result, function($v) use ($base, $quote) {
                        return $v->BaseCurrencyCode == $base && $v->MarketAssetCode == $quote && $v->Active;
                        }, 0);
                    if(count($n)>0 && isset($n[(array_keys($n)[0])]->MarketID)) {$market_id = (int)$n[(array_keys($n)[0])]->MarketID;}
                    if (is_int($market_id)){
                        $response = json_decode(file_get_contents("https://www.coinexchange.io/api/v1/getmarketsummary?market_id=".$market_id));
                        if (isset($response)){
                            if (isset($response->success) && $response->success == 1){
                                if (isset($response->result) && is_object($response->result)){
                                    $ticker = $response->result;
                                    $metric->latest = cmdec(@cdec($ticker->LastPrice));
                                    $metric->percent_change = cmdec(@cdec($ticker->Change));
                                    $metric->base_volume = cmdec(@cdec($ticker->Volume)) ;
                                    $metric->quote_volume = null;
                                    $metric->lowest_ask = cmdec(@cdec($ticker->AskPrice)); 
                                    $metric->highest_bid = cmdec(@cdec($ticker->BidPrice));
                                }
                            } else {
                                print(PHP_EOL . "Error: failed to request Coinexchange ticker" . PHP_EOL);
                                return null; 
                            }
                        }
                    } else {
                        print(PHP_EOL . "Error: failed to request Coinexchange - ticker not found" . PHP_EOL);
                        return null; 
                    }
                }
            } else {
                print(PHP_EOL . "Error: failed to request Coinexchange markets list" . PHP_EOL);
                return null; 
            }
        }
        if (is_null($metric->latest)){
            $metric->latest = null;
            $metric->percent_change = null;
            $metric->base_volume = null;
            $metric->quote_volume = null;
            $metric->lowest_ask = null;
            $metric->highest_bid = null;
        }
        return $metric;
    }

// ---- CREX24 ----
    function mkt_getCrex24Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Crex24";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.crex24.com/CryptoExchangeService/BotPublic/ReturnTicker?request=[NamePairs=".$base."_".$quote."]"));
            if (is_object($response)){
                if (isset($response->Tickers)){
                    $ticker = $response->Tickers[0];
                    $metric->latest = cmdec(@cdec($ticker->Last));
                    $metric->percent_change = cmdec(@cdec($ticker->PercentChange));
                    $metric->base_volume = cmdec(@cdec($ticker->BaseVolume));
                    $metric->quote_volume = cmdec(@cdec($ticker->QuoteVolume));
                } else {
                    $metric->latest = null;
                    $metric->percent_change = null;
                    $metric->base_volume = null;
                    $metric->quote_volume = null;
                }
            } else {
                print(PHP_EOL . "Error: failed to request Crex24 ticker" . PHP_EOL);
                return null;
            }
            // get order book
            $response = json_decode(file_get_contents("https://api.crex24.com/CryptoExchangeService/BotPublic/ReturnOrderBook?request=[PairName=".$base."_".$quote."]"));
        } catch (Exception $ex){
        }
        if (is_object($response)){
            if (isset($response->SellOrders)) {
                if (count($response->SellOrders)>0){
                    $metric->lowest_ask = cmdec(@cdec($response->SellOrders[0]->CoinPrice));
                } else {
                    $metric->lowest_ask = null;
                }
            }
            if (isset($response->BuyOrders)) {
                if (count($response->BuyOrders)>0){
                    $metric->highest_bid = cmdec(@cdec($response->BuyOrders[0]->CoinPrice));
                } else {
                    $metric->highest_bid = null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request Crex24 order book." . PHP_EOL);
        }
        
        return $metric;
    }
    function mkt_getCrex24v2Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Crex24";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.crex24.com/v2/public/tickers?instrument=".$quote."-".$base));
        } catch (Exception $ex){
        }
        if (count($response)>0){
            if (isset($response[0])){
                $ticker = $response[0];
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = cmdec(@cdec($ticker->percentChange));
                $metric->base_volume = cmdec(@cdec($ticker->quoteVolume));
                $metric->quote_volume = cmdec(@cdec($ticker->baseVolume));
                $metric->lowest_ask = cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Crex24 ticker" . PHP_EOL);
            return null;
        }
        
        $metric->exchange = "Crex24";
        return $metric;
    }
// ---- CRYPTO-BRIDGE dead ----
    function mkt_getBridgeMetrics($base, $quote){
        $metric  = new stdClass();
        $wsclient = new WebSocket\Client("wss://bitshares.openledger.info/ws");
        $wsclient->send('{"id":1, "method":"call", "params":[0,"get_ticker",["BRIDGE.'.$base.'","BRIDGE.'.$quote.'"]]}');
        $res = json_decode($wsclient->receive());
        if (isset($res->result)){
            $metric->exchange = "Crypto-Bridge";
            $metric->time = date("c");
            $metric->base  = $base;
            $metric->quote = $quote;
            $metric->latest = (float)$res->result->latest;
            $metric->percent_change = (float)$res->result->percent_change;
            $metric->base_volume = (float)$res->result->base_volume;
            $metric->quote_volume = (float)$res->result->quote_volume;
            $metric->lowest_ask = (float)$res->result->lowest_ask;
            $metric->highest_bid = (float)$res->result->highest_bid;
            
            return $metric;
        } else if (isset($res->error)){
            print(PHP_EOL . "Error:" . $res->error->message . PHP_EOL);
            return null;
        } else {
            print(PHP_EOL . "Error: request didn't return expected result." . PHP_EOL);
            return null;
        }
    }
// ---- DELION ----
    function mkt_getDelionMetrics($base, $quote){
        $metric  = new stdClass();
        try {
            $wsclient = new WebSocket\Client("wss://bitshares.openledger.info/ws");
            $wsclient->send('{"id":1, "method":"call", "params":[0,"get_ticker",["DELION.'.$base.'","DELION.'.$quote.'"]]}');
            $res = json_decode($wsclient->receive());
        } catch (Exception $ex){
        }
        if (isset($res->result)){
            $metric->exchange = "DelionDEX";
            $metric->time = date("c");
            $metric->base  = $base;
            $metric->quote = $quote;
            $metric->latest = (float)$res->result->latest;
            $metric->percent_change = (float)$res->result->percent_change;
            $metric->base_volume = (float)$res->result->base_volume;
            $metric->quote_volume = (float)$res->result->quote_volume;
            $metric->lowest_ask = (float)$res->result->lowest_ask;
            $metric->highest_bid = (float)$res->result->highest_bid;
            
            return $metric;
        } else if (isset($res->error)){
            print(PHP_EOL . "Error:" . $res->error->message . PHP_EOL);
            return null;
        } else {
            print(PHP_EOL . "Error: request didn't return expected result." . PHP_EOL);
            return null;
        }
    }
    function mkt_getDelionv2Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "DelionDex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        $pair = strtoupper($quote)."_".strtoupper($base);
        // get ticker
        try{
            $response = json_decode(file_get_contents("https://api.delion.online/public/v1/tickers/".$pair));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response) && property_exists($response,$pair)){
            $ticker = $response->$pair;
            if (isset($metric->latest)){
                $metric->latest = cmdec(@cdec($ticker->latest));
                $metric->percent_change = cmdec(@cdec($ticker->percent_change));
                $metric->base_volume = cmdec(@cdec($ticker->base_volume)); ;
                $metric->quote_volume = cmdec(@cdec($ticker->quote_volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->lowest_ask));
                $metric->highest_bid = cmdec(@cdec($ticker->highest_bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request DelionDEX ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- Dex-Trade ----
    function mkt_getDexTradev1Metrics($base, $quote){
            $metric  = new stdClass();
            $metric->exchange = "Dex-Trade";
            $metric->time = date("c");
            $metric->base  = $base;
            $metric->quote = $quote;
            // get ticker
            try {
                $c = curl_init('https://api.dex-trade.com/v1/public/ticker?pair='.strtoupper($quote).strtoupper($base));
                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                $z = curl_exec($c);
                $response = json_decode($z);// `https://api.dex-trade.com/v1/public/ticker?pair=BTCUSD`
                curl_close($c);
            } catch (Exception $ex){
            }
            
            if (isset($response->status)){
                if ($response->status && isset($response->data)){ 
                    if (is_object($response->data)){
                        $m = $response->data;
                        $metric->latest         = cmdec(@cdec($m->last));
                        $metric->percent_change = ($m->open != 0) ? cmdec( 100*(@cdec($m->last)-@cdec($m->open))/@cdec($m->open) - 100) : null;
                        $metric->base_volume    = null;
                        $metric->quote_volume   = cmdec(@cdec($m->volume_24H));

                        $metric->lowest_ask = null; 
                        $metric->highest_bid = null;
                        // request ticker bid/ask
                        // https://xapi.finexbox.com/v1/orders?market=xlt_btc&count=1
                        // {"success":"true","result":{"buy":[{"id":20038571,"price":"0.000010000000","amount":"4.44000000","timestamp":1612777074}],"sell":[{"id":20810351,"price":"0.000078900000","amount":"10.98100000","timestamp":1612777074}]}}

                        try {
                            $c = curl_init("https://api.dex-trade.com/v1/public/book?pair=".strtoupper($quote).strtoupper($base));
                            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                            $res = json_decode(curl_exec($c));
                            curl_close($c);
                            if (isset($res->status) && ($res->status) && isset($res->data)) {

                                if (isset($res->data->buy) && is_array($res->data->buy)){
                                    $r = $res->data->buy[0];
                                    $metric->highest_bid = cmdec(@cdec($r->rate));
                                }
                                if (isset($res->data->sell) && is_array($res->data->sell)){
                                    $r = $res->data->sell[0];
                                    $metric->lowest_ask = cmdec(@cdec($r->rate));
                                }
                            }

                        } catch (Exception $e){
                        }

                    
                    
                            
                        
                    
                    } else {
                        print(PHP_EOL . "Error: failed to request Dex-Trade ticker: request returned no data" . PHP_EOL);
                        return null;
                    }
                }
            } else {
                print(PHP_EOL . "Error: failed to request Dex-Trade ticker" . PHP_EOL);
                return null;
            }
            
            return $metric;
    }


// ---- ESCODEX dead ----
    function mkt_getEscodexMetrics($base, $quote){
        $metric  = new stdClass();
        $wsclient = new WebSocket\Client("wss://bitshares.openledger.info/ws");
        $wsclient->send('{"id":1, "method":"call", "params":[0,"get_ticker",["ESCODEX.'.$base.'","ESCODEX.'.$quote.'"]]}');
        $res = json_decode($wsclient->receive());
        if (isset($res->result)){
            $metric->exchange = "ESCODEX";
            $metric->time = date("c");
            $metric->base  = $base;
            $metric->quote = $quote;
            $metric->latest = (float)$res->result->latest;
            $metric->percent_change = (float)$res->result->percent_change;
            $metric->base_volume = (float)$res->result->base_volume;
            $metric->quote_volume = (float)$res->result->quote_volume;
            $metric->lowest_ask = (float)$res->result->lowest_ask;
            $metric->highest_bid = (float)$res->result->highest_bid;
            
            return $metric;
        } else if (isset($res->error)){
            print(PHP_EOL . "Error:" . $res->error->message . PHP_EOL);
            return null;
        } else {
            print(PHP_EOL . "Error: request didn't return expected result." . PHP_EOL);
            return null;
        }
    }
// ---- Exchange-Assets ----
    function mkt_getExchangeAssetsv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "ExchangeAssets";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $c = curl_init("https://exchange-assets.com/api/public/v2/ticker/");
            curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
            $z = curl_exec($c);
            $response = json_decode($z);// https://xapi.finexbox.com/v1/ticker?market=xlt_btc
            curl_close($c);
        } catch (Exception $ex){
        }
        
        if (isset($response->result)){
            if ($response->result == "success" && isset($response->ticker)){ 
                if (is_array($response->ticker)){
                    foreach ($response->ticker as $m){
                        if ($m->pair == strtolower($quote)."_".strtolower($base)){
                            //$m = $response->result;
                            $metric->latest         = cmdec(@cdec($m->last_price));
                            $metric->percent_change = cmdec(@cdec($m->last_price_percent_change));
                            $metric->base_volume    = cmdec(@cdec($m->vol_24h_2));
                            $metric->quote_volume   = cmdec(@cdec($m->vol_24h_1));

                            $metric->lowest_ask = null; 
                            $metric->highest_bid = null;
                            // request ticker bid/ask
                            // https://xapi.finexbox.com/v1/orders?market=xlt_btc&count=1
                            // {"success":"true","result":{"buy":[{"id":20038571,"price":"0.000010000000","amount":"4.44000000","timestamp":1612777074}],"sell":[{"id":20810351,"price":"0.000078900000","amount":"10.98100000","timestamp":1612777074}]}}

                            try {
                                $c = curl_init("https://exchange-assets.com/api/public/v2/order_book/");
                                $param['pairs'] = array(strtolower($quote).'_'.strtolower($base));
                                $param['limit'] = 1;
                                curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
                                curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($param));
                                $res = json_decode(curl_exec($c));
                                curl_close($c);
                                if (is_array($res) && count($res)>0) {
                                    $r = $res[0];
                                    if (isset($r->result) && $r->result == "success"){

                                        if (isset($r->asks) && count($r->asks)>0){
                                            $metric->lowest_ask = cmdec(@cdec($r->asks[0][0]));
                                        }
                                        if (isset($r->bids) && count($r->bids)>0){
                                            $metric->highest_bid = cmdec(@cdec($r->bids[0][0]));
                                        }
                                    }
                                }

                            } catch (Exception $e){
                            }

                            break;
                        
                        }
                    }
                
                } else {
                    print(PHP_EOL . "Error: failed to request ExchangeAssets ticker: request returned no data" . PHP_EOL);
                    return null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request ExchangeAssets ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- EXMO ----
    function mkt_getExmov1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Exmo";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.exmo.com/v1/ticker/"));
        } catch (Exception $ex){
        }
        
        if (isset($response)){
            //var_dump($response);
            //readline();
            if (is_object($response)){ 
                if (property_exists($response,$quote."_".$base)){
                    $ticker = $response->{$quote."_".$base};
                    $metric->latest = cmdec(@cdec($ticker->last_trade));
                    $metric->percent_change = null;
                    $metric->base_volume = cmdec(@cdec($ticker->vol_curr));
                    $metric->quote_volume = cmdec(@cdec($ticker->vol));
                    $metric->lowest_ask = cmdec(@cdec($ticker->buy_price)); 
                    $metric->highest_bid = cmdec(@cdec($ticker->sell_price));
                } else {
                    print(PHP_EOL . "Error: failed to request Exmo ticker: request returned no data" . PHP_EOL);
                    return null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request Exmo ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- Finexbox ----
    function mkt_getFinexboxv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Finexbox";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        //{"success":"true","result":{"volume":"191505.000000000000","high":"0.000029990000","low":"0.000029980000","open":"0.000029990000","price":"0.000029990000","average":"0.00002998","percent":0,"timestamp":1613136654}}
        try {
            $response = json_decode(file_get_contents("https://xapi.finexbox.com/v1/market"));// https://xapi.finexbox.com/v1/ticker?market=xlt_btc
        } catch (Exception $ex){
        }
        
        if (isset($response->success)){
            if ($response->success && isset($response->result)){ 
                if (is_array($response->result)){
                    foreach ($response->result as $m){
                        if ($m->market == strtoupper($quote)."_".strtoupper($base)){
                            //$m = $response->result;
                            $avg = @cdec($m->average);
                            $quote_vol = @cdec($m->volume);
                            $base_vol = $quote_vol * $avg;
                            $metric->latest = cmdec(@cdec($m->price));
                            $metric->percent_change = cmdec(@cdec($m->percent));
                            $metric->base_volume = cmdec($base_vol);
                            $metric->quote_volume = cmdec($quote_vol);

                            $metric->lowest_ask = null; 
                            $metric->highest_bid = null;
                            // request ticker bid/ask
                            // https://xapi.finexbox.com/v1/orders?market=xlt_btc&count=1
                            // {"success":"true","result":{"buy":[{"id":20038571,"price":"0.000010000000","amount":"4.44000000","timestamp":1612777074}],"sell":[{"id":20810351,"price":"0.000078900000","amount":"10.98100000","timestamp":1612777074}]}}

                            try {
                                $res = json_decode(file_get_contents("https://xapi.finexbox.com/v1/orders?market=".strtolower($quote)."_".strtolower($base)."&count=1"));
                                if (isset($res->success) && $res->success && isset($res->result)){
                                    $r = $res->result;
                                    if (isset($r->buy) && count($r->buy)>0){
                                        $metric->highest_bid = cmdec(@cdec($r->buy[0]->price));
                                    }
                                    if (isset($r->sell) && count($r->sell)>0){
                                        $metric->lowest_ask = cmdec(@cdec($r->sell[0]->price));
                                    }
                                }

                            } catch (Exception $e){
                            }

                            break;
                        
                        }
                    }
                
                } else {
                    print(PHP_EOL . "Error: failed to request Finexbox ticker: request returned no data" . PHP_EOL);
                    return null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request Finexbox ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- GRAVIEX ----
    function mkt_getGraviexMetrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Graviex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://graviex.net:443//api/v2/tickers/".strtolower($quote).strtolower($base).".json"));
        } catch (Exception $ex){
        }
        //var_dump($response);
        //readline();
        if (isset($response)){
            if (isset($response->ticker)){
                $ticker = $response->ticker;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = cmdec(100*@cdec($ticker->change));
                $metric->base_volume = cmdec(@cdec($ticker->volbtc)) ;
                $metric->quote_volume = cmdec(@cdec($ticker->vol));
                $metric->lowest_ask = cmdec(@cdec($ticker->sell)); 
                $metric->highest_bid = cmdec(@cdec($ticker->buy));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Graviex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- HITBTC ----
    function mkt_getHitbtcv2Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "HitBTC";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.hitbtc.com/api/2/public/ticker/".$quote.$base));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (!isset($response->error)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->last));

                if (!empty($ticker->open)){
                    $metric->percent_change = cmdec((100*@cdec($ticker->last)/@cdec($ticker->open))-100);
                } else {
                    $metric->percent_change = cmdec(0);
                }

                $metric->base_volume = cmdec(@cdec($ticker->volumeQuote));
                $metric->quote_volume = cmdec(@cdec($ticker->volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request HitBTC ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- HOTBIT.IO
    // https://api.hotbit.io/api/v1/market.status?market=MODIC/BTC&period=86400
    // {
    //     "error": null, 
    //     "result": {
    //         "period": 86400, 
    //         "last": "0.00007636", 
    //         "open": "0.00007782", 
    //         "close": "0.00007636", 
    //         "high": "0.00007782", 
    //         "low": "0.00006587", 
    //         "volume": "17594.33", 
    //         "deal": "1.3427556289"
    //     }, 
    //     "id": 1605018505
    // }
    function mkt_getHotbitv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Hotbit";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.hotbit.io/api/v1/market.status?market=".$quote."/".$base."&period=86400"));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if ((!isset($response->error) || is_null($response->error)) && isset($response->result) ){
                $ticker = $response->result;
                $metric->latest = cmdec(@cdec($ticker->last));

                if (!empty($ticker->open)){
                    $metric->percent_change = cmdec((100*@cdec($ticker->close)/@cdec($ticker->open))-100);
                } else {
                    $metric->percent_change = cmdec(0);
                }

                $metric->base_volume = cmdec(@cdec($ticker->deal));
                $metric->quote_volume = cmdec(@cdec($ticker->volume));
                $metric->lowest_ask = null; //cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = null; //cmdec(@cdec($ticker->bid));

                try {
                    $rx = json_decode(file_get_contents("https://api.hotbit.io/api/v1/allticker"));
                } catch (Exception $ex){
                }
                // https://api.hotbit.io/api/v1/allticker
                // {
                // ticker: [
                //     {
                //         symbol: "HTB_ETH",      # name of transaction pair
                //         buy: "0.0000077393",    # buy one price
                //         sell: "0.0000078169",   # sell one price
                //         open: "0.0000078205",   # open price
                //         close: "0.0000077506",  # close price
                //         high: "0.0000080946",   # highest price
                //         low: "0.000007551",     # lowest price
                //         last: "0.0000077506",   # latest price
                //         vol: "98842592"         # transaction volume
                //     },
                //     {
                //         symbol: "DELTA_ETH",
                //         buy: "6.86e-8",
                //         sell: "6.89e-8",
                //         open: "7.13e-8",
                //         close: "6.87e-8",
                //         high: "7.19e-8",
                //         low: "6.87e-8",
                //         last: "6.87e-8",
                //         vol: "143957900"
                //     }]
                // }
                if (isset($rx) && isset($rx->ticker)){
                    foreach($rx->ticker as $pair){
                        if (isset($pair->symbol) && $pair->symbol == $quote."_".$base){
                            $metric->lowest_ask = cmdec(@cdec($pair->sell)); 
                            $metric->highest_bid = cmdec(@cdec($pair->buy));
                            break;
                        }
                    }
                }
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Hotbit ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- HUOBI ----
    // https://api.huobi.pro/market/detail/merged?symbol=ethusdt
        // "data": {
        //   "id":1499225271,           // The internal identity
        //   "ts":1499225271000,        //
        //   "close":1885.0000,         // The last price of last 24 hours (rotating 24h)
        //   "open":1960.0000,          // The opening price of last 24 hours (rotating 24h)
        //   "high":1985.0000,          // The high price of last 24 hours (rotating 24h)
        //   "low":1856.0000,           // The low price of last 24 hours (rotating 24h)
        //   "amount":81486.2926,       // Accumulated trading volume of last 24 hours (rotating 24h), in base currency
        //   "count":42122,             // The number of completed trades (rotating 24h)
        //   "vol":157052744.85708200,  // Accumulated trading value of last 24 hours (rotating 24h), in quote currency
        //   "ask":[1885.0000,21.8804], // The current best ask in format [price, size]
        //   "bid":[1884.0000,1.6702]   // The current best bid in format [price, size]
        // }
    function mkt_getHuobiv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Huobi";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.huobi.pro/market/detail/merged?symbol=".strtolower($quote).strtolower($base)));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (isset($response->tick)){
                $ticker = $response->tick;
                $metric->latest = cmdec(@cdec($ticker->close));

                if (!empty($ticker->open) && $ticker->open != 0){
                    $metric->percent_change = cmdec((100*@cdec($ticker->close)/@cdec($ticker->open))-100);
                } else {
                    $metric->percent_change = cmdec(0);
                }

                $metric->base_volume = cmdec(@cdec($ticker->vol));
                $metric->quote_volume = cmdec(@cdec($ticker->amount));
                try{
                    $metric->lowest_ask = cmdec(@cdec($ticker->ask[0])); 
                } catch (Exception $e) {
                    $metric->lowest_ask = null; 
                }
                try{
                    $metric->highest_bid = cmdec(@cdec($ticker->bid[0]));
                } catch (Exception $e) {
                    $metric->highest_bid = null;
                }
                
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request Huobi ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- Kraken (tbd)----
    // https://trade.kraken.com/ru-ru/charts/KRAKEN:ZEC-EUR?period=1h
    // https://api.kraken.com/0/public/Ticker?pair=ZECBTC
        // {
        //     "error":[],
        //     "result":{
        //         "XZECXXBT":{
        //             "a":["0.004000","190","190.000"],    //a = аски(<цена>, <объем всех лотов>, <объем лота>),
        //             "b":["0.003980","322","322.000"],    //b = биды(<цена>, <объем всех лотов>, <объем лота>),
        //             "c":["0.003980","0.86266082"],       //c = последняя торгуемая цена(<цена>, <объем лота>),
        //             "v":["848.61256714","941.41918009"], //v = объем(<сегодня>, <последние 24 часа>),
        //             "p":["0.003965","0.003953"],         //p = средневзвешенная по объему цена(<сегодня>, <последние 24 часа>),
        //             "t":[299,336],                       //t = количество торгов(<сегодня>, <последние 24 часа>),
        //             "l":["0.003800","0.003780"],         //l = минимум(<сегодня>, <последние 24 часа>),
        //             "h":["0.004070","0.004070"],         //h = максимум(<сегодня>, <последние 24 часа>),
        //             "o":"0.003820"                       //o = цена открытия сегодня
        //         }
        //     }
        // }
        // <pair_name> = pair name
        //     a = ask array(<price>, <whole lot volume>, <lot volume>),
        //     b = bid array(<price>, <whole lot volume>, <lot volume>),
        //     c = last trade closed array(<price>, <lot volume>),
        //     v = volume array(<today>, <last 24 hours>),
        //     p = volume weighted average price array(<today>, <last 24 hours>),
        //     t = number of trades array(<today>, <last 24 hours>),
        //     l = low array(<today>, <last 24 hours>),
        //     h = high array(<today>, <last 24 hours>),
        //     o = today's opening price



// ---- KUKOIN ----
    function mkt_getKukoinv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "KuKoin";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.kucoin.com/api/v1/market/stats?symbol=".$quote."-".$base));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (isset($response->data)){ 
                if (isset($response->data->last)){
                    $ticker = $response->data;
                    $metric->latest = cmdec(@cdec($ticker->last));
                    $metric->percent_change = cmdec(100*@cdec($ticker->changeRate));
                    $metric->base_volume = cmdec(@cdec($ticker->volValue));
                    $metric->quote_volume = cmdec(@cdec($ticker->vol));
                    $metric->lowest_ask = cmdec(@cdec($ticker->sell)); 
                    $metric->highest_bid = cmdec(@cdec($ticker->buy));
                } else {
                    print(PHP_EOL . "Error: failed to request KuKoin ticker: request returned no data" . PHP_EOL);
                    return null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request KuKoin ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- Livecoin dead ----
    function mkt_getLivecoinv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Livecoin";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.livecoin.net/exchange/ticker?currencyPair=".strtoupper($quote)."/".strtoupper($base)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){

                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = null;
                $metric->base_volume = cmdec(@cdec($ticker->volume) * @cdec($ticker->vwap));
                $metric->quote_volume = cmdec(@cdec($ticker->volume));
                $metric->lowest_ask = cmdec(@cdec($ticker->best_ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->best_bid));
                // try {
                //     $metric->percent_change = 100*(((float)$ticker->last/(float)$ticker->buy)-1); //?
                // } catch (Exception $e){}
        } else {
            print(PHP_EOL . "Error: failed to request Livecoin ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- OKex ----
    function mkt_getOkexv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "OKex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://www.okex.com/api/v5/market/ticker?instId=".strtoupper($quote)."-".strtoupper($base)));
        } catch (Exception $ex){
        }
        // {
            //     "code": "0",
            //     "msg": "",
            //     "data": [
            //       {
            //         "instType": "SPOT",
            //         "instId": "RVN-BTC",
            //         "last": "0.00000307",
            //         "lastSz": "6622.9207",
            //         "askPx": "0.000003084",
            //         "askSz": "385.7101",
            //         "bidPx": "0.000003055",
            //         "bidSz": "20914",
            //         "open24h": "0.000003128",
            //         "high24h": "0.000003299",
            //         "low24h": "0.000003061",
            //         "volCcy24h": "212.2277",
            //         "vol24h": "67062506.3111",
            //         "ts": "1614866434678",
            //         "sodUtc0": "0.000003197",
            //         "sodUtc8": "0.000003237"
            //       }
            //     ]
            //   }
        if (isset($response) && isset($response->data) && is_array($response->data)){

                $ticker = $response->data[0];
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = (@cdec($ticker->open24h) != 0) ? cmdec( 100*(@cdec($ticker->last) - @cdec($ticker->open24h))/@cdec($ticker->open24h) ): null;
                $metric->base_volume = cmdec(@cdec($ticker->volCcy24h));
                $metric->quote_volume = cmdec(@cdec($ticker->vol24h));
                $metric->lowest_ask = cmdec(@cdec($ticker->askPx)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bidPx));
                // try {
                //     $metric->percent_change = 100*(((float)$ticker->last/(float)$ticker->buy)-1); //?
                // } catch (Exception $e){}
        } else {
            print(PHP_EOL . "Error: failed to request OKex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- Mercatox ----
    function mkt_getMercatoxv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Mercatox";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://mercatox.com/api/public/v1/ticker"));
        } catch (Exception $ex){
        }
        if (isset($response)){
            //var_dump($response);
            //readline();
            if (is_object($response)){ 
                if (property_exists($response,$quote."_".$base)){
                    $ticker = $response->{$quote."_".$base};
                    $metric->latest = cmdec(@cdec($ticker->last_price));
                    $metric->percent_change = cmdec(@cdec($ticker->percentChange));
                    $metric->base_volume = cmdec(@cdec($ticker->quote_volume));
                    $metric->quote_volume = cmdec(@cdec($ticker->base_volume));
                    $metric->lowest_ask = cmdec(@cdec($ticker->lowestAsk)); 
                    $metric->highest_bid = cmdec(@cdec($ticker->highestBid));
                } else {
                    print(PHP_EOL . "Error: failed to request Mercatox ticker: request returned no data" . PHP_EOL);
                    return null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request Mercatox ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- POLONIEX ----
    function mkt_getPoloniexv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Poloniex";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://poloniex.com/public?command=returnTicker"));
        } catch (Exception $ex){
        }
        if (isset($response) && is_array($response)){
            $t = $response[$base."_".$quote];
            if (!is_null($t)){ 
                $ticker = $t;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = cmdec(100*@cdec($ticker->percentChange));
                $metric->base_volume = cmdec(@cdec($ticker->baseVolume));
                $metric->quote_volume = cmdec(@cdec($ticker->quoteVolume));
                $metric->lowest_ask = cmdec(@cdec($ticker->lowestAsk)); 
                $metric->highest_bid = cmdec(@cdec($ticker->highestBid));
            } else {
                print(PHP_EOL . "Error: failed to request Poloniex ticker: request returned no data" . PHP_EOL);
                return null;            
            }
        } else {
            print(PHP_EOL . "Error: failed to request Poloniex ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- PROBIT ----
    function mkt_getProbitv1Metrics($base, $quote){
        // {
        //     "data": [
        //       {
        //         "last": "3252.4",
        //         "low": "3235",
        //         "high": "3273.8",
        //         "change": "-23.4",
        //         "base_volume": "0.16690818",
        //         "quote_volume": "543.142095541",
        //         "market_id": "BTC-USDT",
        //         "time": "2018-12-17T06:49:08.000Z"
        //       }
        //     ]
        // }
        $metric  = new stdClass();
        $metric->exchange = "PROBIT";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.probit.com/api/exchange/v1/ticker?market_ids=".$quote."-".$base));
            
            if (isset($response->data) && is_array($response->data)){
                if (isset($response->data[0])){
                    $ticker = $response->data[0];
                    $metric->latest = cmdec(@cdec($ticker->last)) ;
                    $metric->percent_change = cmdec(@cdec($ticker->change));
                    $metric->base_volume = cmdec(@cdec($ticker->quote_volume));
                    $metric->quote_volume = cmdec(@cdec($ticker->base_volume));
                    $metric->lowest_ask = null; //cmdec(@cdec($ticker->askPrice)); 
                    $metric->highest_bid = null; //cmdec(@cdec($ticker->bidPrice));
                } else {
                    $metric->latest = null;
                    $metric->percent_change = null;
                    $metric->base_volume = null;
                    $metric->quote_volume = null;
                    $metric->lowest_ask = null;
                    $metric->highest_bid = null;
                    return $metric;
                }
            } else {
                print(PHP_EOL . "Error: failed to request Binance ticker" . PHP_EOL);
                return null;
            }
            
            // get order book
            $response = json_decode(file_get_contents("https://api.probit.com/api/exchange/v1/order_book?market_id=".$quote."-".$base));
        } catch (Exception $ex){
        }
        if (isset($response->data) && is_array($response->data)){
            $metric->lowest_ask = null;
            $metric->highest_bid = null;
            foreach ($response->data as $order){
                // 0:{
                //     "side":"buy"
                //     "price":"0.0000002"
                //     "quantity":"507"
                //     }
                // 1:{
                //     "side":"sell"
                //     "price":"0.000295"
                //     "quantity":"3.76"
                //     }

                if ($order->side == "sell") {
                    if (is_null($metric->lowest_ask)) {
                        $metric->lowest_ask = @cdec($order->price);
                    } else if ($metric->lowest_ask > @cdec($order->price)){
                        $metric->lowest_ask = @cdec($order->price);
                    }
                } else if ($order->side == "buy"){
                    if (is_null($metric->highest_bid)) {
                        $metric->highest_bid = @cdec($order->price);
                    } else if ($metric->highest_bid < @cdec($order->price)){
                        $metric->highest_bid = @cdec($order->price);
                    }
                }
            }
            if (!is_null($metric->lowest_ask)) {$metric->lowest_ask = cmdec($metric->lowest_ask);}
            if (!is_null($metric->highest_bid)) {$metric->highest_bid = cmdec($metric->highest_bid);} 
        }


        return $metric;
    }
// ---- Quan2um.com ----
    function mkt_getQuan2umv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "quan2um";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.quan2um.com/api/tradeview/market/".strtoupper($quote)."_".strtoupper($base)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            if (isset($response->last_price)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->last_price));
                $metric->percent_change = cmdec(@cdec($ticker->change));
                $metric->base_volume = cmdec(@cdec($ticker->base_volume)) ;
                $metric->quote_volume = cmdec(@cdec($ticker->main_volume));
                if (isset($ticker->order_book) && isset($ticker->order_book->asks) && count($ticker->order_book->asks)>0){
                    $t = $ticker->order_book->asks[0];
                    if (isset($t->p)){
                        $metric->lowest_ask = cmdec(@cdec($t->p)); 
                    }
                }
                if (isset($ticker->order_book) && isset($ticker->order_book->bids) && count($ticker->order_book->bids)>0){
                    $t = $ticker->order_book->bids[0];
                    if (isset($t->p)){
                        $metric->highest_bid = cmdec(@cdec($t->p)); 
                    }
                }
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request quan2um.com ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- QUANTADEX dead ----
    function mkt_getQuantadexMetrics($base, $quote){
        $metric  = new stdClass();
        try {
            $wsclient = new WebSocket\Client("wss://bitshares.openledger.info/ws");
            $wsclient->send('{"id":1, "method":"call", "params":[0,"get_ticker",["QUANTADEX.'.$base.'","QUANTADEX.'.$quote.'"]]}');
            $res = json_decode($wsclient->receive());
        } catch (Exception $ex){
        }
        if (isset($res->result)){
            $metric->exchange = "QuantaDEX";
            $metric->time = date("c");
            $metric->base  = $base;
            $metric->quote = $quote;
            $metric->latest = cmdec(@cdec($res->result->latest));
            $metric->percent_change = cmdec(@cdec($res->result->percent_change));
            $metric->base_volume = cmdec(@cdec($res->result->base_volume));
            $metric->quote_volume = cmdec(@cdec($res->result->quote_volume));
            $metric->lowest_ask = cmdec(@cdec($res->result->lowest_ask));
            $metric->highest_bid = cmdec(@cdec($res->result->highest_bid));
            
            return $metric;
        } else if (isset($res->error)){
            print(PHP_EOL . "Error:" . $res->error->message . PHP_EOL);
            return null;
        } else {
            print(PHP_EOL . "Error: request didn't return expected result." . PHP_EOL);
            return null;
        }
    }
// ---- SOUTHXCHANGE ----
    // Market:      https://www.southxchange.com/Market/Book/MODIC/BTC
    // API:         https://www.southxchange.com/api/price/MODIC/BTC 
    // Response:    {"Bid":0.000059190,"Ask":0.000062550000000000,"Last":0.000059200000000000,"Variation24Hr":-5.35,"Volume24Hr":7020.716945670000000000}
    function mkt_getSouthxchangev1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "SouthXchange";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://www.southxchange.com/api/price/".$quote."/".$base));
        } catch (Exception $ex){
        }
        if (isset($response) ){
            if (isset($response->Last)){ 
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->Last));
                $metric->percent_change = cmdec(@cdec($ticker->Variation24Hr));
                $metric->base_volume = null;
                $metric->quote_volume = cmdec(@cdec($ticker->Volume24Hr));
                $metric->lowest_ask = cmdec(@cdec($ticker->Ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->Bid));
            } else {
                print(PHP_EOL . "Error: failed to request SouthXchange ticker: request returned no data" . PHP_EOL);
                return null;            
            }
        } else {
            print(PHP_EOL . "Error: failed to request SouthXchange ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }




// ---- STEX ----
    function mkt_getStexv3Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "STEX";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        $metric->latest = null;
        // get all currency pairs for base 
        try {
            $response = json_decode(file_get_contents("https://api3.stex.com/public/currency_pairs/list/".strtoupper($base)));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (isset($response->success) && $response->success){
                if (isset($response->data) && is_array($response->data)){
                    $n = array_search($quote."_".$base, array_column($response->data, 'symbol'));
                    if (is_int($n)){
                        $currency_pair_id = $response->data[$n]->id;
                        $response = json_decode(file_get_contents("https://api3.stex.com/public/ticker/".$currency_pair_id));
                        if (isset($response)){
                            if (isset($response->success) && $response->success){
                                if (isset($response->data) && is_object($response->data)){
                                    $ticker = $response->data;
                                    $metric->latest = cmdec(@cdec($ticker->last));
                                    if (!empty($ticker->open)){
                                        $metric->percent_change = cmdec((100*@cdec($ticker->last)/@cdec($ticker->open))-100);
                                    } else {
                                        $metric->percent_change = cmdec(0);
                                    }
                                    $metric->base_volume = cmdec(@cdec($ticker->volume)) ;
                                    $metric->quote_volume = cmdec(@cdec($ticker->volumeQuote));
                                    $metric->lowest_ask = cmdec(@cmdec($ticker->ask)) ; 
                                    $metric->highest_bid = cmdec(@cdec($ticker->bid)) ;
                                }
                            } else {
                                print(PHP_EOL . "Error: failed to request STEX ticker" . PHP_EOL);
                                return null; 
                            }
                        }
                    } else {
                        print(PHP_EOL . "Error: failed to request STEX - ticker not found" . PHP_EOL);
                        return null; 
                    }
                }
            } else {
                print(PHP_EOL . "Error: failed to request STEX currency pair list" . PHP_EOL);
                return null; 
            }
        }
        if (is_null($metric->latest)){
            $metric->latest = null;
            $metric->percent_change = null;
            $metric->base_volume = null;
            $metric->quote_volume = null;
            $metric->lowest_ask = null;
            $metric->highest_bid = null;
        }
        return $metric;
    }

// ---- TradeOgre ----
    function mkt_getTradeOgrev1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "TradeOgre";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://tradeogre.com/api/v1/ticker/".$base."-".$quote));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (isset($response->success) && $response->success){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->price));

                if (!empty($ticker->initialprice)){
                    $metric->percent_change = cmdec((100*@cdec($ticker->price)/@cdec($ticker->initialprice))-100);
                } else {
                    $metric->percent_change = cmdec(0);
                }

                $metric->base_volume = cmdec(@cdec($ticker->volume));
                $metric->quote_volume = cmdec(@cdec($ticker->volume)/@cdec($ticker->price));
                $metric->lowest_ask = cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bid));
            } else {
                $metric->latest = null;
                $metric->percent_change = null;
                $metric->base_volume = null;
                $metric->quote_volume = null;
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request TradeOgre ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- TRADESATOSHI dead ----
    function mkt_getTradesatoshiMetrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "Tradesatoshi";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://tradesatoshi.com/api/public/getmarketsummary?market=".$quote."_".$base));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (is_object($response)){ 
                if (isset($response->success) && ($response->success)){
                    $ticker = $response->result;
                    $metric->latest = cmdec(@cdec($ticker->last)) ;
                    $metric->percent_change = cmdec(@cdec($ticker->change)) ;
                    $metric->base_volume = cmdec(@cdec($ticker->baseVolume)) ;
                    $metric->quote_volume = cmdec(@cdec($ticker->volume)) ;
                    $metric->lowest_ask = cmdec(@cdec($ticker->ask)) ; 
                    $metric->highest_bid = cmdec(@cdec($ticker->bid)) ;
                } else {
                    print(PHP_EOL . "Error: failed to request Exmo ticker: request returned no data" . PHP_EOL);
                    return null;
                }
            }
        } else {
            print(PHP_EOL . "Error: failed to request Exmo ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }
// ---- VCC ----
    function mkt_getVccv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "VCC";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.vcc.exchange/v3/summary"));
        } catch (Exception $ex){
        }
        // {
            //     "message": null,
            //     "dataVersion": "d1b03a976b54c2b9dc9b3716d6ddb6950b082577",
            //     "data": {
            //       "eth_btc": {
            //         "last": "0.03183300",
            //         "percentChange": "1.87538004",
            //         "baseVolume": "130067.2470000000",
            //         "quoteVolume": "4091.5664610710",
            //         "isFrozen": 0,
            //         "high24hr": "0.0319070000",
            //         "low24hr": "0.0310560000",
            //         "lowestAsk": "0.031827",
            //         "lowestAskQuantity": "0.121",
            //         "highestBid": "0.031826",
            //         "highestBidQuantity": "50.000"
            //       },
            //       {},
            //    }
            // }
        $pair = strtolower($quote)."_".strtolower($base);
        if (isset($response) && isset($response->data) && is_object($response->data) && property_exists($response->data,$pair)){
            $ticker = $response->data->$pair;
                $metric->latest = cmdec(@cdec($ticker->last));
                $metric->percent_change = cmdec( @cdec($ticker->percentChange) );
                $metric->base_volume = cmdec(@cdec($ticker->quoteVolume));
                $metric->quote_volume = cmdec(@cdec($ticker->baseVolume));
                $metric->lowest_ask = cmdec(@cdec($ticker->lowestAsk)); 
                $metric->highest_bid = cmdec(@cdec($ticker->highestBid));
                // try {
                //     $metric->percent_change = 100*(((float)$ticker->last/(float)$ticker->buy)-1); //?
                // } catch (Exception $e){}
        } else {
            print("Error: failed to request VCC ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- WhiteBit ----
    //https://whitebit.com/api/v4/public/ticker
    //https://whitebit.com//api/v4/public/orderbook/PIVX_BTC?limit=1&level=1
    function mkt_getWhiteBitv4Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "WhiteBit";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        $market = strtoupper($quote)."_".strtoupper($base);
        try {
            $response = json_decode(file_get_contents("https://whitebit.com/api/v4/public/ticker"));
        } catch (Exception $ex){
        }

        if (isset($response) && isset($response->$market)){
            $metric->latest = null;
            $metric->percent_change = null;
            $metric->base_volume = null;
            $metric->quote_volume = null;
            $metric->lowest_ask = null;
            $metric->highest_bid = null;

            $ticker = $response->$market;
            $metric->latest = cmdec(@cdec($ticker->last_price));
            $metric->base_volume = cmdec(@cdec($ticker->quote_volume));
            $metric->quote_volume = cmdec(@cdec($ticker->base_volume));
            $metric->percent_change = cmdec(@cdec($ticker->change));

            $orders = json_decode(file_get_contents("https://whitebit.com/api/v4/public/orderbook/".$market."?limit=1&level=1"));

            if (isset($orders)){
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
                if (isset($orders->asks) && count($orders->asks)>0){
                    $ask = $orders->asks[0];
                    $metric->lowest_ask = cmdec(@cdec($ask[0])); 
                }
                if (isset($orders->bids) && count($orders->bids)>0){
                    $bid = $orders->bids[0];
                    $metric->highest_bid = cmdec(@cdec($bid[0])); 
                }
            } else {
                $metric->lowest_ask = null;
                $metric->highest_bid = null;
            }
                
        } else {
            print(PHP_EOL . "Error: failed to request WhiteBit ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }

// ---- XeggeX ----
    function mkt_getXeggexv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "XeggeX";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.xeggex.com/api/v2/market/getbysymbol/".strtoupper($quote)."_".strtoupper($base)));
        } catch (Exception $ex){
        }
        // var_dump($response);
        // readline();
        if (isset($response)){
            // if (isset($response->data) && isset($response->data->ticker)){
                //$ticker = $response->data->ticker;
                $metric->latest = cmdec(@cdec($response->lastPrice));
                $metric->percent_change = cmdec(@cdec($response->changePercentNumber));
                $metric->base_volume = cmdec(@cdec($response->volumeSecondaryNumber)) ;
                $metric->quote_volume = cmdec(@cdec($response->volumeNumber));
                $metric->lowest_ask = cmdec(@cdec($response->bestAsk)); 
                $metric->highest_bid = cmdec(@cdec($response->bestBid));
                // try {
                //     $metric->percent_change = cmdec(100*((@cdec($ticker->last)/@cdec($ticker->open))-1));
                // } catch (Exception $e){}
            // } else {
            //     $metric->latest = null;
            //     $metric->percent_change = null;
            //     $metric->base_volume = null;
            //     $metric->quote_volume = null;
            //     $metric->lowest_ask = null;
            //     $metric->highest_bid = null;
            // }
        } else {
            print(PHP_EOL . "Error: failed to request XeggeX ticker" . PHP_EOL);
            return null;
        }
        
        return $metric;
    }



// ---- XT.com ----
    // https://api.xt.com/data/api/v1/getTicker?market=btc_usdt
    // {"high":31900.41,"low":29330.78,"rate":-0.0531,"price":29951.18,"moneyVol":862450094.09664366,"coinVol":28357.587508,"ask":29951.18,"bid":29950.79,"askVol":0.561444,"bidVol":1.058625,"depthTime":1654164637416}
    function mkt_getXTv1Metrics($base, $quote){
        $metric  = new stdClass();
        $metric->exchange = "XT.com";
        $metric->time = date("c");
        $metric->base  = $base;
        $metric->quote = $quote;
        // get ticker
        try {
            $response = json_decode(file_get_contents("https://api.xt.com/data/api/v1/getTicker?market=".strtolower($quote)."_".strtolower($base)));
        } catch (Exception $ex){
        }
        if (isset($response)){
            if (isset($response->price)){
                $ticker = $response;
                $metric->latest = cmdec(@cdec($ticker->price));
                $metric->percent_change = cmdec(@cdec($ticker->rate));
                $metric->base_volume = cmdec(@cdec($ticker->moneyVol));
                $metric->quote_volume = cmdec(@cdec($ticker->coinVol));
                $metric->lowest_ask = cmdec(@cdec($ticker->ask)); 
                $metric->highest_bid = cmdec(@cdec($ticker->bid));
            } else {
                print(PHP_EOL . "Error: failed to request XT.com ticker: request returned no data" . PHP_EOL);
                return null;
            }
        } else {
            print(PHP_EOL . "Error: failed to request XT.com ticker" . PHP_EOL);
            return null;
        }
        return $metric;
    }


?>
