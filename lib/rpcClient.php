<?php
use Decimal\Decimal;

class rpcClient {
	
	private $url;
	private $rid;
	private $connect_timeout;
	private $request_timeout;
	private $key;
	private $max_attempts;
	private $retry_base_usec;

	// Calls that change daemon state are never retried: a timeout after the
	// daemon accepted the request would otherwise re-submit it.
	private static $no_retry_methods = ['sendrawtransaction'];

	public function __construct($url, $connect_timeout = null, $request_timeout = null, $key = null) {
		$this->url = $url;
		$this->rid = rand();
		$this->connect_timeout = (is_null($connect_timeout)) ? 5 : $connect_timeout;
		$this->request_timeout = (is_null($request_timeout)) ? 30 : $request_timeout;
		$this->key = $key;
		// Bounded retry for transient transport faults. A hiccup that used to
		// surface as a null result -- and, one layer up, as a hard die() in the
		// middle of a block -- is now retried before it is believed.
		$this->max_attempts = 3;
		$this->retry_base_usec = 250000; // 0.25s, doubled per attempt
	}

	/**
	 * Build a well-formed error response. Assigning straight onto $res->error
	 * is unsafe: when the daemon returns a scalar error, or none at all, the
	 * property access either throws or silently vivifies, and an Error is not
	 * caught by `catch (Exception)`.
	 */
	private function errorResponse($code, $message){
		$res = new stdClass();
		$res->error = new stdClass();
		$res->error->code = $code;
		$res->error->message = $message;
		$res->result = null;
		return $res;
	}

	/** Transport-level faults worth retrying; a daemon that answers is not one. */
	private function isTransient($curl_errno, $http_code){
		if (in_array($curl_errno, [CURLE_OPERATION_TIMEOUTED, CURLE_COULDNT_CONNECT,
		                           CURLE_GOT_NOTHING, CURLE_RECV_ERROR, CURLE_SEND_ERROR,
		                           CURLE_PARTIAL_FILE, CURLE_COULDNT_RESOLVE_HOST], true)){
			return true;
		}
		// 5xx and 429 are the daemon being busy rather than refusing.
		if ($http_code >= 500 || $http_code == 429){ return true; }
		return false;
	}

	public function __call($method,$parameters) {
		if (!is_scalar($method)) {
			return $this->errorResponse(3, 'wrong method format');
		}
		if (!is_array($parameters)) {
			return $this->errorResponse(4, 'wrong parameters format');
		}
		$parameters = array_values($parameters);

		$attempts = in_array(strtolower($method), self::$no_retry_methods, true) ? 1 : $this->max_attempts;

		$last = null;
		for ($attempt = 1; $attempt <= $attempts; $attempt++){
			$last = $this->executeOnce($method, $parameters);
			if (is_null($last->error)) { return $last; }
			// Only transport faults are worth another go; a JSON-RPC error from
			// the daemon is a real answer and must be returned as-is.
			if (!isset($last->error->transient) || !$last->error->transient) { return $last; }
			if ($attempt < $attempts){
				usleep($this->retry_base_usec * (1 << ($attempt - 1)));
			}
		}
		return $last;
	}

	private function executeOnce($method, $parameters){
		try {
			// A per-call id, so a stale or crossed response can actually be
			// detected. Previously every call from a client reused one id fixed
			// at construction, which made the check below inert. Kept numeric:
			// the daemons behind this explorer echo integer ids back verbatim,
			// where a string id risks being normalised and failing the check.
			$this->rid++;
			$rid = $this->rid;

			$req = new stdClass();
			$req->id      = $rid;
			$req->jsonrpc = "1.0";
			$req->method  = $method;
			$req->params  = $parameters;
			if (!is_null($this->key)){
				$req->key = (string)$this->key;
			}
			$qstr = json_encode($req);

			$ch = curl_init($this->url);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
			//curl_setopt($ch, CURLOPT_HTTPAUTH,  CURLAUTH_BASIC |  CURLAUTH_DIGEST);
			//curl_setopt($ch, CURLOPT_USERPWD, "user" . ":" . "password");
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $qstr);
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);

			$body      = curl_exec($ch);
			$curl_err  = curl_errno($ch);
			$curl_msg  = curl_error($ch);
			$http_code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// Distinguish "the transport failed" from "the daemon said no".
			// Both used to collapse into a null result.
			if ($body === false || $curl_err !== 0){
				$res = $this->errorResponse(1, "transport failure (curl ".$curl_err."): ".$curl_msg);
				$res->error->transient = $this->isTransient($curl_err, $http_code);
				return $res;
			}
			if ($http_code >= 400){
				$res = $this->errorResponse(5, "http status ".$http_code." from rpc endpoint");
				$res->error->transient = $this->isTransient(0, $http_code);
				return $res;
			}

			$res = json_decode($body);
			if (is_null($res) || !is_object($res)){
				// Unparseable body, or JSON that is not a response object:
				// truncated, or an error page. Treat as transient -- a partial
				// read is exactly what retry is for.
				$res = $this->errorResponse(6, "malformed json response (".strlen((string)$body)." bytes)");
				$res->error->transient = true;
				return $res;
			}
			if (!isset($res->error)) { $res->error = null; }
			if (!isset($res->result)) { $res->result = null; }

			if (isset($res->id) && $res->id != $rid){
				$out = $this->errorResponse(2, "response id mismatch");
				$out->error->transient = true;
				return $out;
			}
			if (is_null($res->error)){
				// re-format floats
				$res->result = json_decode(preg_replace('/"\: *([0-9]+\.[0-9e+\-]*|-[0-9]+\.[0-9e+\-]*)/', '":"\\1"', json_encode($res->result)),false);
			}
			return $res;
		} catch (Throwable $e){
			return $this->errorResponse(7, $e->getMessage());
		}
	}
	
  // public function execute($method,$parameters = []) {
	// 	$this->__call($method,$parameters);
	// 		if (!is_scalar($method)) {
	// 			throw new Exception('wrong method format');
	// 		}
			
	// 		if (is_array($parameters)) {
	// 			$parameters = array_values($parameters);
	// 		} else {
	// 			throw new Exception('wrong parameters format');
	// 		}
			
	// 		// create request
	// 		$req = array(
	// 			'method' => $method,
	// 			'params' => $parameters,
	// 			'id' => $this->rid
	// 		);			
	// 		if (!is_null($this->key)){
	// 			$req["key"] = (string)$this->key;
	// 		}
	// 		$req = json_encode($req);

	// 		//set POST options
	// 		$options = array (
	// 			'http' => array (
	// 				'method'  => 'POST',
	// 				'header'  => 'Content-type: application/json',
	// 				'content' => $req
	// 			)
	// 		);
	// 		if(!is_null($this->timeout) && is_numeric($this->timeout)){ $options['http']['timeout'] = $this->timeout;}
	// 		$context  = stream_context_create($options);

	// 			if ($fp = fopen($this->url, 'r', false, $context)) {
	// 				$fp = fopen($this->url, 'r', false, $context);
	// 				$res = '';
	// 				while($row = fgets($fp)) {
	// 					$res.= trim($row);//."\n";
	// 				}
	// 				//convert float values to string
	// 				$res = preg_replace('/"\: *([0-9]+\.[0-9e+\-]*|-[0-9]+\.[0-9e+\-]*)/', '":"\\1"', $res);
					
	// 				$res = json_decode($res,false);

	// 				if (isset($res->id) && $res->id != $this->rid) {
	// 					throw new Exception('wrong response id (requestid: '.$this->rid.', responseid: '.$res->id.')');
	// 				}
	// 				if (isset($res->error) && !is_null($res->error)) {
	// 					throw new Exception($res->error);
	// 				}
	// 				return $res->result;
	// 			} else {
	// 				throw new Exception('server request failed');
	// 			}	
		
	// }

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