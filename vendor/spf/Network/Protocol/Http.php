<?php
namespace spf\Network\Protocol;
class Http extends Base
{
	const dateFormatHttp = 'D, d-M-Y H:i:s T';
	const httpEof = "\r\n\r\n";
	public $name;
	protected $response;
	protected $keepalive = TRUE;
	protected $expire;
	protected $gzip;

	function init()
	{
	}
	/**
	 * @param $server
	 * @param $workerId
	 */
	function onStart($server, $workerId)
	{
	}
	function onRequest(\swoole_http_request $request, \swoole_http_response $response)
	{
		$callback = \spf\Server::getServerConfig($this->name,'server')['request_callback'];
		\call_user_func_array($callback,array($request,$response));
	}
}