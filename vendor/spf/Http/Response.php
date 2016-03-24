<?php
namespace spf\Http;
use spf\Network\Protocol\Http;
class Response
{
	public $protocol = 'HTTP/1.1';
	public $httpStatus = 200;
	public $head;
	public $cookie;
	public $body;

	static $HTTP_HEADERS = array(
		100 => "100 Continue",
		101 => "101 Switching Protocols",
		200 => "200 OK",
		201 => "201 Created",
		204 => "204 No Content",
		206 => "206 Partial Content",
		300 => "300 Multiple Choices",
		301 => "301 Moved Permanently",
		302 => "302 Found",
		303 => "303 See Other",
		304 => "304 Not Modified",
		307 => "307 Temporary Redirect",
		400 => "400 Bad Request",
		401 => "401 Unauthorized",
		403 => "403 Forbidden",
		404 => "404 Not Found",
		405 => "405 Method Not Allowed",
		406 => "406 Not Acceptable",
		408 => "408 Request Timeout",
		410 => "410 Gone",
		413 => "413 Request Entity Too Large",
		414 => "414 Request URI Too Long",
		415 => "415 Unsupported Media Type",
		416 => "416 Requested Range Not Satisfiable",
		417 => "417 Expectation Failed",
		500 => "500 Internal Server Error",
		501 => "501 Method Not Implemented",
		503 => "503 Service Unavailable",
		506 => "506 Variant Also Negotiates");

	function sendStatus($code)
	{
		$this->head[0] = $this->protocol . ' ' . self::$HTTP_HEADERS[$code];
		$this->httpStatus = $code;
	}

	function send_head($key, $value)
	{
		$this->head[$key] = $value;
	}
	function setcookie($name, $value = null, $expire = null, $path = '/', $domain = null, $secure = null, $httponly = null)
	{
		if ($value === null) $value = 'deleted';
		$date = date("D, d-M-Y H:i:s T", $expire) ;
		$cookie = "{$name}={$value}; expires=Tue, {$date}; path={$path}";
		if ($domain) $cookie .= "; domain={$domain}";
		if ($httponly) $cookie .= '; httponly';
		$this->cookie[] = $cookie;
	}

	function addHeader(array $header)
	{
		$this->head = array_merge($this->head, $header);
	}

	function getHeader($fastcgi = false)
	{
		$out = "HTTP/1.1 200 OK\r\n";
		$httpStatus = self::$HTTP_HEADERS[$this->httpStatus];
		if ($fastcgi) {
			$out = "Status: {$this->httpStatus} {$httpStatus}\r\n";
		} else {
			if (isset($this->head[0])) {
				$out = $this->head[0] . "\r\n";
				unset($this->head[0]);
			}
		}
		if (!isset($this->head['Server'])) $this->head['Server'] = Http::SOFTWARE;
		if (!isset($this->head['Content-Type'])) $this->head['Content-Type'] = 'text/html; charset=utf-8';
		if (!isset($this->head['Content-Length']))$this->head['Content-Length'] = strlen($this->body);
		foreach ($this->head as $k => $v) $out .= $k . ': ' . $v . "\r\n";
		foreach ($this->cookie as $v) $out .= "Set-Cookie: $v\r\n";
		$out .= "\r\n";
		return $out;
	}

	function noCache()
	{
		$this->head['Cache-Control'] = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';
		$this->head['Pragma'] = 'no-cache';
	}
}