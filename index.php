<?php
// DIE IF ITS A OPTIONS REQUESTS, ALONG WITH CORRECT HEADERS
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS')
{
	http_response_code(200);
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
	if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) header("Access-Control-Allow-Headers:  X-Authorization, authorization, version, {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
	die();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('DEV', 1);
define('HTTP_METHOD', strtoupper($_SERVER['REQUEST_METHOD']));
define('DS', DIRECTORY_SEPARATOR);
define('FCPATH', dirname(__FILE__) . DS);

http_response_code(412);
// define('TOKEN',$token);
// define('UID',$id);
// define('UCLIENT',$client);
// define('CONTROLLER',$CTRL);
// define('CONTROLLER_FUNCTION',$FUNC);
// define('URI',implode('/',$ARGS));
// DEFINE('ONESIGNAL_APP_ID','');
// DEFINE('ONESIGNAL_PASS','');

date_default_timezone_set('Africa/Cairo');//GMT
ini_set("date.timezone", "Africa/Cairo");

function getRoute(){
	$script = explode('/',$_SERVER['SCRIPT_NAME']);
	array_pop($script);
	$script = implode('/', $script);
	$uri = str_replace($script, '', $_SERVER['REQUEST_URI']);
	$uri = str_replace('//', '/', $uri);
	$uri = str_replace('/null', '/0', $uri);
	$uri = str_replace('/undefined', '/0', $uri);
	$uri = str_replace('/false', '/0', $uri);
	$uri = rtrim(str_replace($_SERVER['QUERY_STRING'], '', $uri),'?');
	$uri = explode('/', trim($uri,"/"));

	$CTRL = array_shift($uri);
	$FUNC = ucfirst(strtolower(HTTP_METHOD));
	$ARGS = [];
	foreach ($uri as $value) {
		if( is_numeric($value) OR
				strpos($value, "'") > -1 OR
				$value === 0 OR
				$value === "0" ){
			$ARGS[] = is_numeric($value) ? (int)$value : strtolower($value);
		}else{
			$FUNC .= preg_replace("/[^a-zA-Z _-]/", "", ucfirst(strtolower($value)));
		}
	}

	define('CONTROLLER',$CTRL);
	define('CONTROLLER_FUNCTION',$FUNC);
	define('URI',implode('/',$ARGS));
	//print_r(['controller'=>$CTRL,'function'=>$FUNC,'uri'=>$ARGS]);
	return ['controller'=>$CTRL,'function'=>$FUNC,'uri'=>$ARGS];
}
function array_keys_exists(array $keys, array $arr) {
   return !array_diff_key(array_flip($keys), $arr);
}
/**
 * Response Class used as a wrapper for responses from routes
 * usage 1 : return new R(200,$data);
 * usage 2 : $r = new R(); $r->code = 200; return $r;
 *
 * check for using : $ret instanceof R
 *
 * Codes:
 * 	200 : ok
 * 	201 : Created
 * 	202 : Accepted
 * 	304 : Not modified
 * 	401 : invalid username and password.. // used in login only
 * 	403 : autorization required or invalid token
 * 	409 : confict
 * 	412 : precondition fail //access deined, role not matching etc..
 * 	500 : server error
 * Request Headers
 * 	If-None-Match: [contain Etag for get.. if matched return 304 and no body]
 * 	If-Unmodified-Since [date of time_updated if match return 304]
 * Response Headers
 *  ETag [version of resource]
 *  Last-Modified [should contain time_updated value] and client should send If-Unmodified-Since
 */
class R{
	public $code = 200;
	public $data = [];

	// variables defined by helper function;;
	public $name = CONTROLLER;
	public $function = CONTROLLER_FUNCTION;
	protected $uri = URI;

	public function __construct($code=null,$data=null,$name='') {
        if($code) $this->code = $code;
        if($data) $this->data = $data;
        if($name) $this->name = $name;
  }

  public function __destruct() {
  	// run loging code here.. you can use $name or $function to know the exact
  }
}

$auth = $token = $id = $client = $version = null;
@$auth = getallheaders()['Authorization'];

@$auth = end(explode(' ',trim($auth))) OR '';
@list($token,$id) = explode('.',$auth);
define('TOKEN',$token.'.'.$id);
define('UID',$id);
sleep(1);


try{
	require_once('./db.php');
	require_once './config/dev.db.php';
	require_once('./base.php');

	////////////////////////////////////////////////
	////////////////   Autoload                 ////
	/*///////////////////////////////////////////////
	spl_autoload_register(function ($name) {
	    if(!file_exists(FCPATH.'models'.DS.strtolower($name).'.php')){
	    	throw new Exception($name.' resource not found ',404);
	    }
	    require_once(FCPATH.'models'.DS.strtolower($name)+'.php');
	});*/


	////////////////////////////////////////////////
	////////////////   ROUTER                   ////
	////////////////////////////////////////////////
	$ROUTER = getRoute(); //[$CTRL,$FUNC,$ARGS]

	if(!file_exists('./routes/'.$ROUTER['controller'].'.php'))
		throw new Exception('page not found',404);

	require('./routes/'.$ROUTER['controller'].'.php');

	// Create new ISchool App. caching should be done here..
	$CTRL = new $ROUTER['controller']();

	if(!method_exists($CTRL, $ROUTER['function']) OR !is_callable([$CTRL, $ROUTER['function']]) ){

		throw new Exception($ROUTER['function'].' resource not found ',404);

	}


	$return = call_user_func_array([$CTRL,$ROUTER['function']],$ROUTER['uri']);

	if(!$return)
		throw new Exception('resource returned invalid response',500);

	if($return instanceof R){
		$code = (int) $return->code;
		$data = $return->data;
	} else {
		$code = 200;
		$data = $return;
	}

	http_response_code( $code );
	header('Content-Type: application/json;charset=utf-8');
	header('Access-Control-Expose-Headers: X-Authorization, Authorization');
	header('Access-Control-Max-Age: 6000'); // set preflight cache to 1 hour


	$payload= is_string($data) ? ['message'=>$data] : $data;
	$body 	= json_encode( $payload );

	$j = str_replace('"0000-00-00"', 'null', preg_replace('/"(\d+\.?\d*)"([^:])/', '$1$2', $body));

	file_put_contents('log.log', PHP_EOL.json_encode(['router'=>$ROUTER,'resp'=>$j]),FILE_APPEND | LOCK_EX);
	echo $j;
}catch(Exception $e){
	$r = [];
	$r['message'] = $e->getMessage();
	$code = ($e->getCode() > 199) ? $e->getCode() : 409;

	http_response_code( $code );
	echo json_encode($r);
	exit(3);
}
