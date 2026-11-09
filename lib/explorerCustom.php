<?php
    use Decimal\Decimal;

    function cf_getMdaoBalances(){
        // MDAO API: https://app.malwarechain.com/api/masternodes/?format=json
        $mdao_balances = array();
        try {
            $response = json_decode(file_get_contents("https://app.malwarechain.com/api/masternodes/?format=json"));
        } catch (Exception $ex){
            print("  Error getting MDAO balances.".PHP_EOL);
            return [];
        }
        if (isset($response->count) && $response->count > 0){
            if (isset($response->results)){
                foreach ($response->results as $result){
                    $r = (object)["_id" => $result->addr, "balance" => cmdec(new Decimal($result->balance),18), "updated" => new MongoDB\BSON\UTCDateTime()];
                    array_push($mdao_balances, $r);
                }
            }

            $go_next = false;
            if (isset($response->next) && !is_null($response->next)) { $go_next = true; }

            while ($go_next) {
                try {
                    $response = json_decode(file_get_contents($response->next));
                    if (isset($response->results)){
                        foreach ($response->results as $result){
                            $r = (object)["_id" => $result->addr, "balance" => cmdec(new Decimal($result->balance),18), "updated" => new MongoDB\BSON\UTCDateTime()];
                            array_push($mdao_balances, $r);
                        }
                    }
                    if (!isset($response->next) || is_null($response->next)) { $go_next = false; }        
                } catch (Exception $ex){
                }
            }
        }

        return $mdao_balances;
    }

    function rvn_getAssetTypeByName($name){
        $type = "unknown";
        // MAIN!               - regular_admin
        // |-> MAIN            - regular
        // |-> MAIN/SUB!       - subasset_admin
        // |   |-> MAIN/SUB    - subasset
        // |-> MAIN#UNIQUE     - unique
        // |-> $MAIN           - restricted
        // |-> MAIN~CHANNEL    - message_channel SIP-028

        // + #TAG                - tag
        // + |-> #TAG/#SUB       - subtag
        if (substr($name,0,1) == "$" && substr_count($name, "/",1) > 0){
            // ?? |-> $MAIN/SUB           - subrestricted
            $type = "subrestricted";
        } else if (substr($name,0,1) == "$"){
            // |-> $MAIN           - restricted
            $type = "restricted";
        } else if (substr($name,0,1) == "#" && substr_count($name, "/#",1)){
            // |-> #TAG/#SUB       - subtag
            $type = "subtag";
        } else if (substr($name,0,1) == "#"){
            // #TAG                - tag
            $type = "tag";
        } else if (substr_count($name, "~",1) > 0) { //SIP-028
            // |-> MAIN~CHANNEL    - message_channel
            $type = "message_channel";
        } else if (substr_count($name, "#",1) > 0) {
            // |-> MAIN#UNIQUE     - unique
            $type = "unique";
        } else if (substr_count($name, "/",1) > 0) {
            // |   |-> MAIN/SUB    - subasset
            $type = "subasset";
        } else {
            $type = "regular";
        }
          
        if (($type == "regular" || $type == "subasset" ) && substr($name,-1,1) == "!") {
            $type = $type."_admin";
          }
        return $type;
    }

?>