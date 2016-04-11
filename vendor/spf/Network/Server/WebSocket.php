<?php
namespace spf\Network\Server;
class WebSocket extends Http
{
	protected function listen($host,$port)
	{
		return new \swoole_websocket_server($host,$port);
	}
}