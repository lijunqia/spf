<?php
namespace spf\Network\Protocol;
class Http extends Base
{
	const dateFormatHttp = 'D, d-M-Y H:i:s T';
	const httpEof = "\r\n\r\n";
	protected $response;
	protected $keepalive = TRUE;
	protected $expire;
	protected $gzip;

	function init()
	{
	}
	function onRequest($request)
	{
	}
	function response($request, $response)
	{
		if (!isset($response->head['Date']))$response->head['Date'] = gmdate("D, d M Y H:i:s T");
		if (!isset($response->head['Connection'])) {
			if ($this->keepalive && (isset($request->head['Connection']) && strcasecmp($request->head['Connection'],'keep-alive') ===0)) {
				$response->head['KeepAlive'] = 'on';
				$response->head['Connection'] = 'keep-alive';
			} else {
				$response->head['KeepAlive'] = 'off';
				$response->head['Connection'] = 'close';
			}
		}
		if ($this->expire and $response->http_status == 304){//过期命中
			$out = $response->getHeader();
			return $this->server->send($request->fd, $out);
		}
		//压缩
		if ($this->gzip) {
			$response->head['Content-Encoding'] = 'deflate';
			$response->body = gzdeflate($response->body, $this->config['server']['gzip_level']);
		}
		$out = $response->getHeader() . $response->body;
		$ret = $this->server->send($request->fd, $out);
		$this->afterResponse($request, $response);
		return $ret;
	}

	function afterResponse($request,$response)
	{
		if (!$this->keepalive or $response->head['Connection'] == 'close')$this->server->close($request->fd);
		unset($this->requests[$request->fd]);
		unset($request);
		unset($response);
	}

	function onError()
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
		if (empty($this->response)) {
			$this->response = new Swoole\Http\Response();
		}
		$this->response->send_http_status(500);
		$this->response->body = $message;
		$this->response($this->currentRequest, $this->response);
	}

	function httpError($code, $response, $content = '')
	{
		$response->send_http_status($code);
		$response->head['Content-Type'] = 'text/html';
		//$response->body = Swoole\Error::info(Swoole\Http\Response::$HTTP_HEADERS[$code], "<p>$content</p><hr><address>" . self::SOFTWARE . " at {$this->server->host} Port {$this->server->port}</address>");
	}
}