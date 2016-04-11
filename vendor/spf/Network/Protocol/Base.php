<?php
namespace spf\Network\Protocol;


class Base implements IProtocol
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
		unset($this->config, $this->server, $this->workId);
	}

	function init()
	{
	}

	function on_start($server, $workerId)
	{
	}

	function on_connect($server, $clientId, $fromId)
	{
	}

	function on_receive($server, $clientId, $fromId, $data)
	{
	}

	function on_close($server, $clientId, $fromId)
	{
	}

	function on_shutdown($server, $workerId)
	{
	}

	function on_task($server, $taskId, $fromId, $data)
	{
	}

	function on_finish($server, $taskId, $data)
	{
	}

	function on_timer($server, $interval)
	{
	}
}