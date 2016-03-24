<?php
namespace spf\Network\Server;
class Socket extends Base{
	protected function listen($host, $port, $type = SWOOLE_SOCK_TCP)
	{
		return new \swoole_server($host, $port, SWOOLE_PROCESS, $type);
	}
	protected function bindEvent()
	{
		//$swoole->on('receive', array($this, 'onReceive'));
		parent::bindEvent();
	}
}