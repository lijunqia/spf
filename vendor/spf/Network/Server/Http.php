<?php
namespace spf\Network\Server;
class Http extends Base
{
	protected function listen($host,$port)
	{
		return new \swoole_http_server($host,$port);
	}
	protected function bindEvent()
	{
		$this->swoole->on('request', [$this,'onRequest']);
		parent::bindEvent();
	}
	function onRequest(\swoole_http_request $request, \swoole_http_response $response)
	{
		$request->scheduler = $this->swoole->scheduler;
		//$this->protocol->onRequest($request, $response);
		ob_start();
		echo "<h1>Hello World. #".rand(1000, 9999)."</h1><pre>";
		print_r($this);
		//$response->header("Content-Type", "text/html; charset=utf-8");
		echo '</pre>';
		$content = ob_get_clean();
		//print_r($request);
		echo $request->server['request_time'],"\n";
		$response->end($content);
	}

	/**
	 * @param $server
	 * @param $workerId
	 */
	public function onWorkerStart($server, $workerId)
	{
		parent::onWorkerStart($server, $workerId);
		$this->protocol = new \spf\Network\Protocol\Http();
	}
}