<?php
    require __DIR__ . '/../../../settings.php';    
    global $settings;
    include_once __DIR__ . '/../../../lib/explorerDb.php';
    include_once __DIR__ . '/../../../lib/explorerUi.php';
    include_once __DIR__ . '/../../../lib/explorerRpc.php';
    include_once __DIR__ . '/../../../lib/explorerSec.php';
    include_once __DIR__ . '/../../../lib/timeTracker.php';
    global $rpc;
    $apt = new TimeTracker;
    $apt->createTimer("total",0);
    $cip = ui_GetUserIpAddress();

    $session = sec_Authenticate($cip, ui_GetUserAgent());
    $msg = new stdClass();

    if (!$session->authenticated || !sec_sameOrigin()) {
        // not authorized
        header('HTTP/1.0 401 Unauthorized', true, 401);
        header('Content-Type: application/json');
        echo '{"status":"error","error":{"code": 401,"message":"Unauthorized"}}'; 
    } else {
        $execute = true;
        $msg->status = "success";
        $msg->format = "json";
        // continue with execution
            // read POST data
            $cmd = isset($_POST['cmd']) ? sanitizeInput('cmd',"POST") : '';
            $force = isset($_POST['force']) ? sanitizeInput('force',"POST") : 'false';
            if (strtolower($force)=="true"){
                $force = true;
            } else {
                $force = false;
            }

            // validate
            if (is_string($cmd)){
                if (strlen($cmd)>0){
                    // analyse command -> get method and params
                        $params = explode(" ",$cmd);
                        $method = $params[0];
                        unset($params[0]);
                        foreach ($params as $i=>$val){
                            if (is_numeric($val)){
                                $v = @cdec($val);
                                if ($v->isInteger()) {
                                    $params[$i] = $v->toInt();
                                } else if ($v->isZero()){
                                    $params[$i] = 0;
                                } else {
                                    $params[$i] = $v->toString();
                                }
                            } else if (strtolower($val) == "true"){
                                $params[$i] = true;
                            } else if (strtolower($val) == "false"){
                                $params[$i] = false;
                            }
                        }

                    // Read-only methods only. Methods that change network state need force=true.
                        if (!sec_consoleMethodAllowed($method, array_values($params), $force)){
                            $execute = false;
                            $msg->status = "error";
                            $msg->result = sec_consoleMethodAllowed($method, array_values($params), true)
                                ? "Error: requested method needs force=true"
                                : "Error: requested method is not allowed in the console";
                        }

                    // execute
                    if ($execute) {
                        try {
                            $res = $rpc->__call($method,$params);

                            if (is_null($res->error)){
                                $msg->result = $res->result;
                            } else {
                                $msg->status = "error";
                                $msg->result = "Error: ".$res->error->message;
                            }

                        } catch (Exception $ex){
                            $msg->status = "error";
                            $msg->result = "Error: ".$ex->getMessage();
                        }
                    }                    
                    // log

                }else{
                    $msg->status = "error";
                    $msg->result = "Error: method not set";
                }
            } else {
                $msg->status = "error";
                $msg->result = "Error: unsupported command format";
            }
            


        //return result
        header('Content-Type: application/json');
        if (!is_object($msg->result) && !is_array($msg->result)){
            if (!is_numeric($msg->result)){
                // it's a string
                $msg->format = "string";
            }
        }
        echo json_encode($msg);

        $apt->stopTimer("total");
        ui_LogPageEvent($cip,"_up/debug",(int)$apt->getDurationMicroSeconds("total"),["method" => $method ?? ""]);
    }
?>