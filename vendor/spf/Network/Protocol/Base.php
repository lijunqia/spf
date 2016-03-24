<?php
namespace spf\Network\Protocol;

use spf\Network\Protocol;

class Base implements Protocol
{
	public $server;

	function __construct()
	{
		$this->init();
	}

	function init()
	{

	}
	function setServer($server)
	{
		$this->server = $server;
	}
	function onStart($server, $workerId)
	{
	}

	function onConnect($server, $clientId, $fromId)
	{
	}

	function onReceive($server, $clientId, $fromId, $data)
	{
	}

	function onClose($server, $clientId, $fromId)
	{
	}

	function onShutdown($server, $workerId)
	{
	}

	function onTask($server, $taskId, $fromId, $data)
	{
	}

	function onFinish($server, $taskId, $data)
	{
	}

	function onTimer($server, $interval)
	{
	}
}