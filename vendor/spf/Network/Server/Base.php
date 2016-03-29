<?php
namespace spf\Network\Server;
use spf\Process\Control;
use spf\Log;
class Base
{
	public $name;
	public $config;
	protected $swoole;
	protected $protocol;



	function __construct($config)
	{
		$this->config = $config;
	}
	function __destruct()
	{
		unset($this->config,$this->swoole,$this->protocol);
	}

	function start()
	{
		$listen = $this->translateListen($this->config['listen']);
		list($host, $port, $type) = array_shift($listen);
		$this->swoole = $swoole = $this->listen($host, $port, $type);
		foreach ($listen as $v) {
			list($host, $port, $type) = $v;
			$swoole->addlistener($host, $port, $type);
		}
		$swoole->set($this->config['setting']);
		$this->bindEvent();
		$swoole->start();
	}
	protected function bindEvent()
	{
		$swoole = $this->swoole;
		$swoole->on('start', array($this, 'onStart'));
		$swoole->on('managerstart', array($this, 'onManagerStart'));
		$swoole->on('workerstart', array($this, 'onWorkerStart'));
		$swoole->on('workerstop', array($this, 'onWorkerStop'));
		$swoole->on('connect', array($this, 'onConnect'));
		$swoole->on('close', array($this, 'onClose'));
		$swoole->on('receive', array($this, 'onReceive'));
		$swoole->on('timer', array($this, 'onTimer'));
		if (isset($this->config['setting']['task_worker_num'])) {
			$swoole->on('Task', array($this, 'onTask'));
			$swoole->on('Finish', array($this, 'onFinish'));
		}
	}

	public function onStart($server)
	{
		echo "onMasterStart:\n";
		$config = $this->config;
		Control::setName($config['master_process_name']);
		file_put_contents($config['master_pid_file'], $server->master_pid);
		file_put_contents($config['manager_pid_file'], $server->manager_pid);
		if ($this->config['user'])Control::changeUser($this->config['user']);
	}

	public function onManagerStart($server)
	{
		echo "onManagerStart:\n";
		Control::setName($this->config['manager_process_name']);
		if ($this->config['user'])Control::changeUser($this->config['user']);
	}

	public function onWorkerStart($server, $workerId)
	{
		//$this->setProtocol($protocol);//please set at children class
		$this->protocol->name = $this->name;
		$config = $this->config;
		$name = ($workerId >= $config['setting']['worker_num'])?'task':'event';
		$workProcessName = sprintf($config['worker_process_name'],$name,$workerId);
		echo "onWorkerStart:{$workProcessName}\n";
		Control::setName($workProcessName);
		if ($this->config['user'])Control::changeUser($this->config['user']);
		$this->protocol->onStart($server, $workerId);
	}

	public function onWorkerStop($server, $workerId)
	{
		echo "onWorkerStop:{$workerId}\n";
		//$this->protocol->onShutdown($server, $workerId);
	}
	function onConnect($server, $fd, $fromId)
	{
		echo "Client: Connect.\n";
		$this->protocol->onConnect($server, $fd, $fromId);
	}
	function onReceive($server, $fd, $fromId, $data)
	{
		echo "Server Received:".$data."\n";
//		$server->send($fd, "Server Received:".$data."\n");
		switch($data){
			case 'reload':
				$ret = intval($server->reload());
				$server->send($fd, $ret);
				break;
			case 'info':
				$info = $server->connection_info($fd);
				$server->send($fd, 'Info: ' . var_export($info, true) . PHP_EOL);
				break;
			case 'stats':
				$serv_stats = $server->stats();
				$server->send($fd, 'Stats: ' . var_export($serv_stats, true) . PHP_EOL);
				break;
			case 'shutdown':
				$server->shutdown();
				break;
			default:
				$this->protocol->onReceive($server, $fd, $fromId, $data);
		}
	}
	function onClose($server, $fd, $fromId)
	{
		echo "Client: Close {$fromId} \n";
		$this->protocol->onClose($server, $fd, $fromId);
	}
	public function onTimer($server, $interval)
	{
		echo "onTimer:\n";
		$this->protocol->onTimer($server, $interval);
	}
	public function onTask($server, $taskId, $fromId, $data)
	{
		echo "onTask:\n";
		$this->protocol->onTask($server, $taskId, $fromId, $data);
	}

	public function onFinish($server, $taskId, $data)
	{
		echo "onFinish:\n";
		$this->protocol->onFinish($server, $taskId, $data);
	}
	public function close($client_id)
	{
		\swoole_server_close($this->swoole, $client_id);
	}
	public function send($client_id, $data)
	{
		\swoole_server_send($this->swoole, $client_id, $data);
	}
	protected function translateListen($listen)
	{
		$ret = [];
		if(is_scalar($listen))$listen = [[$listen]];
		foreach ($listen as $v) {
			if (is_scalar($v)) $v = [$v];
			if (!isset($v[1])) array_unshift($v, '0.0.0.0');
			if (!isset($v[2])) $v[] = SWOOLE_SOCK_TCP;
			$ret[] = $v;
		}
		return $ret;
	}
}