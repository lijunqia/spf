<?php
namespace spf\Http;
class Request
{
	public $fd;//文件描述符
	public $id;
	public $time = 0;//请求时间
	public $remote_ip;
	public $get = array();
	public $post = array();
	public $file = array();
	public $cookie = array();
	public $session = array();
	public $server = array();
	public $attrs;
	public $head = array();
	public $body;
	public $meta = array();
	public $finish = false;
	public $ext_name;
	public $status;

	function setGlobal()
	{
		$_GET = $this->get ?: [];
		$_POST = $this->post ?: [];
		$_FILES = $this->file ?: [];
		$_COOKIE = $this->cookie ?: [];
		$_SERVER = $this->server ?: [];
		$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
		$_SERVER['REQUEST_URI'] = $this->meta['uri'];
		foreach ($this->head as $key => $value) {
			$_SERVER['HTTP_' . strtoupper(strtr('-', '_', $key))] = $value;
		}
		$_SERVER['REMOTE_ADDR'] = $this->remote_ip;
	}

	function unsetGlobal()
	{
		$_REQUEST = $_SESSION = $_COOKIE = $_FILES = $_POST = $_SERVER = $_GET = [];
	}
}