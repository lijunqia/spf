<?php
namespace spf\Network\Server;
class WebSocket extends Base
{
	protected function listen($host,$port)
	{
		return new \swoole_websocket_server($host,$port);
	}
}