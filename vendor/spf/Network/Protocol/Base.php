<?php
namespace spf\Network\Protocol;

use spf\Network\Protocol;

class Base implements Protocol
{
	protected $server;
	protected $workId;
	public $config;
	function __construct($server, $workerId, $config)
	{
		$this->server = $server;
		$this->workId = $workerId;
		$this->config = $config;
		$this->init();
	}
	function __destruct()
	{
		unset($this->config,$this->server,$this->workId);
	}

	function init()
	{

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