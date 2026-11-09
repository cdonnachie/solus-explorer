<?php
use Decimal\Decimal;

class rpcClient {
	
	private $url;
	private $rid;

	public function __construct($url, $timeout = null, $key = null) {
		$this->url = $url;
		$this->rid = 1;
		$this->timeout = $timeout;
		$this->key = $key;
	}

	public function __call($method,$parameters) {
		try { 
			if (!is_scalar($method)) {
				throw new Exception('Wrong method format');
			}
			
			if (is_array($parameters)) {
				$parameters = array_values($parameters);
			} else {
				throw new Exception('Wrong parameters format');
			}
			
			// create request
			$req = array(
				'method' => $method,
				'params' => $parameters,
				'id' => $this->rid
			);			
			if (!is_null($this->key)){
				$req["key"] = (string)$this->key;
			}
			$req = json_encode($req);

			//set POST options
			$options = array (
				'http' => array (
					'method'  => 'POST',
					'header'  => 'Content-type: application/json',
					'content' => $req
				)
			);
			if(!is_null($this->timeout) && is_numeric($this->timeout)){ $options['http']['timeout'] = $this->timeout;}
			$context  = stream_context_create($options);
			if ($fp = fopen($this->url, 'r', false, $context)) {
				$res = '';
				while($row = fgets($fp)) {
					$res.= trim($row);//."\n";
				}
				//convert float values to string
				$res = preg_replace('/"\: *([0-9]+\.[0-9e+\-]*|-[0-9]+\.[0-9e+\-]*)/', '":"\\1"', $res);
				
				$res = json_decode($res,false);

				if (isset($res->id) && $res->id != $this->rid) {
					throw new Exception('Wrong response id (requestid: '.$this->rid.', responseid: '.$res->id.')');
				}
				if (isset($res->error) && !is_null($res->error)) {
					throw new Exception('Error: '.$res->error);
				}
				return $res->result;
			} else {
				throw new Exception('Failed to connect to host');
			}			
		} catch (Exception $e){
			return null;			
		}
	}
	
	public function execute($method,$parameters = []) {
			if (!is_scalar($method)) {
				throw new Exception('wrong method format');
			}
			
			if (is_array($parameters)) {
				$parameters = array_values($parameters);
			} else {
				throw new Exception('wrong parameters format');
			}
			
			// create request
			$req = array(
				'method' => $method,
				'params' => $parameters,
				'id' => $this->rid
			);			
			if (!is_null($this->key)){
				$req["key"] = (string)$this->key;
			}
			$req = json_encode($req);

			//set POST options
			$options = array (
				'http' => array (
					'method'  => 'POST',
					'header'  => 'Content-type: application/json',
					'content' => $req
				)
			);
			if(!is_null($this->timeout) && is_numeric($this->timeout)){ $options['http']['timeout'] = $this->timeout;}
			$context  = stream_context_create($options);

				if ($fp = fopen($this->url, 'r', false, $context)) {
					$fp = fopen($this->url, 'r', false, $context);
					$res = '';
					while($row = fgets($fp)) {
						$res.= trim($row);//."\n";
					}
					//convert float values to string
					$res = preg_replace('/"\: *([0-9]+\.[0-9e+\-]*|-[0-9]+\.[0-9e+\-]*)/', '":"\\1"', $res);
					
					$res = json_decode($res,false);

					if (isset($res->id) && $res->id != $this->rid) {
						throw new Exception('wrong response id (requestid: '.$this->rid.', responseid: '.$res->id.')');
					}
					if (isset($res->error) && !is_null($res->error)) {
						throw new Exception($res->error);
					}
					return $res->result;
				} else {
					throw new Exception('server request failed');
				}	
		
	}

}
function rpc_getServerLink($host, $port = null, $protocol = null, $user = null, $password = null){
	$link='';
	if (!empty($protocol)){ $link = $link.$protocol."://"; }
	if (!empty($user)) { $link = $link.$user ;}
	if (!empty($password)) { $link = $link.":".$password; }
	if (!empty($user)) { $link = $link."@"; }
	if (!empty($host)) { $link = $link.$host; }
	if (!empty($port)) { $link = $link.":".$port; }
	if (!empty($host)) { $link = $link."/"; }
	
	return $link;
}
?>