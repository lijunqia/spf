<?php
namespace syb\mvc;
class App
{
	static $instance;
	protected $request;
	protected $respone;
	function __construct()
	{

	}
	static function getInstance()
	{
		return self::$instance;
	}
	static function run($request,$response)
	{
		$request_time = microtime(TRUE);
		self::$instance = $app = new self;
		$app->request = $request;
		$app->respone = $response;
		try{
			$req = self::parseUri($request->server['request_uri']);
			$class = '\syb\\'.strtolower($req['m']).'\controller\\'.strtolower($req['c']);
			$action = 'action'.$req['a'];
			if(!class_exists($class) || !method_exists($class,$action))self::error404();
			$controller = new $class();
			ob_start();
			call_user_func_array([$controller,$action],$req['args']);
			$content = ob_get_clean();
			//$time = number_format(microtime(TRUE) - $request_time,6);
			//$content.="<pre>RequestTime:{$time}</pre>";
			$response->end($content);
		}catch(\LogicException $e){
			//
		}catch(\Exception $e){
			throw $e;
		}
	}
	static function error404()
	{
		$app = self::getInstance();
		$response = $app->respone;
		$response->header("Content-Type", "text/html; charset=utf-8");
		$response->status(404);
		$response->end('Page Not Found!');

	}
	static function parseUri($uri)
	{
		$uri = trim($uri,'/ ');
		$ret = ['m'=>'','c'=>'','a'=>'','args'=>''];
		$arr=$uri?explode('/',$uri,4):[];
		switch(count($arr)) {
			case 4:
				break;
			case 3:
				$arr=array_merge($arr,['']);
				break;
			case 2:
				$arr=array_merge($arr,['index','']);
				break;
			case 1:
				$arr=array_merge($arr,['index','index','']);
				break;
			default:
				$arr=['common','index','index',''];
		}
		list($ret['m'],$ret['c'],$ret['a'],$ret['args']) = $arr;
		$ret['args'] = $ret['args']?explode('/',$ret['args']):[];
		return $ret;
	}
	/*	function onError()
		{
			$error = error_get_last();
			if (!isset($error['type'])) {
				return;
			}
			switch ($error['type']) {
			case E_ERROR :
			case E_PARSE :
			case E_DEPRECATED:
			case E_CORE_ERROR :
			case E_COMPILE_ERROR :
				break;
			default:
				return;
			}
			$errorMsg = "{$error['message']} ({$error['file']}:{$error['line']})";
			$message = self::SOFTWARE . " Application Error: " . $errorMsg;
			if (empty($this->response)) $this->response = new \swoole_http_response();
			$this->response->send_http_status(500);
			$this->response->body = $message;
			$this->response($this->currentRequest, $this->response);
		}

		function httpError($code, $response, $content = '')
		{
			$response->send_http_status($code);
			$response->head['Content-Type'] = 'text/html';
			//$response->body = Swoole\Error::info(Swoole\Http\Response::$HTTP_HEADERS[$code], "<p>$content</p><hr><address>" . self::SOFTWARE . " at {$this->server->host} Port {$this->server->port}</address>");
		}*/
}