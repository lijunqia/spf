<?php
namespace spf\Network\Server;

use spf\Process\ProcessControl as ProcessProcessControl;
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
		unset($this->config, $this->swoole, $this->protocol);
	}

	function start()
	{
		$listen = $this->translate_listen($this->config['listen']);
		list($host, $port, $type) = array_shift($listen);
		$this->swoole = $swoole = $this->listen($host, $port, $type);
		foreach ($listen as $v) {
			list($host, $port, $type) = $v;
			$swoole->addlistener($host, $port, $type);
		}
		$swoole->set($this->config['setting']);
		$this->bind_event();
		$swoole->start();
	}

	protected function bind_event()
	{
		$swoole = $this->swoole;
		$swoole->on('start', array($this, 'on_start'));
		$swoole->on('managerstart', array($this, 'on_manager_start'));
		$swoole->on('workerstart', array($this, 'on_worker_start'));
		$swoole->on('workerstop', array($this, 'on_worker_stop'));
		$swoole->on('connect', array($this, 'on_connect'));
		$swoole->on('close', array($this, 'on_close'));
		$swoole->on('receive', array($this, 'on_receive'));
		$swoole->on('timer', array($this, 'on_timer'));
		if (isset($this->config['setting']['task_worker_num'])) {
			$swoole->on('task', array($this, 'on_task'));
			$swoole->on('finish', array($this, 'on_finish'));
		}
	}

	public function on_start($server)
	{
		echo "onMasterStart:\n";
		$config = $this->config;
		ProcessControl::set_name($config['master_process_name']);
		file_put_contents($config['master_pid_file'], $server->master_pid);
		file_put_contents($config['manager_pid_file'], $server->manager_pid);
		if ($this->config['user']) {
			ProcessControl::change_user($this->config['user']);
		}
	}

	public function on_manager_start($server)
	{
		echo "onManagerStart:\n";
		ProcessControl::set_name($this->config['manager_process_name']);
		if ($this->config['user']) {
			ProcessControl::change_user($this->config['user']);
		}
	}

	public function on_worker_start($server, $worker_id)
	{
		//$this->setProtocol($protocol);//please set at children class
		$this->protocol->name = $this->name;
		$config = $this->config;
		$name = ($worker_id >= $config['setting']['worker_num']) ? 'task' : 'event';
		$work_process_name = sprintf($config['worker_process_name'], $name, $worker_id);
		echo "onWorkerStart:{$work_process_name}\n";
		ProcessControl::set_name($work_process_name);
		if ($this->config['user']) {
			ProcessControl::change_user($this->config['user']);
		}
		$this->protocol->on_start($server, $worker_id);
	}

	public function on_worker_stop($server, $worker_id)
	{
		echo "onWorkerStop:{$worker_id}\n";
		$this->protocol->on_shutdown($server, $worker_id);
	}

	function on_connect($server, $fd, $from_id)
	{
		echo "Client: Connect.\n";
		$this->protocol->on_connect($server, $fd, $from_id);
	}

	function on_receive($server, $fd, $from_id, $data)
	{
		echo "Server Received:" . $data . "\n";//$server->send($fd, "Server Received:".$data."\n");
		switch ($data) {
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
			$server->send(
				$fd, 'Stats: ' . var_export($serv_stats, true) . PHP_EOL
			);
			break;
		case 'shutdown':
			$server->shutdown();
			break;
		default:
			$this->protocol->on_receive($server, $fd, $from_id, $data);
		}
	}

	function on_close($server, $fd, $from_id)
	{
		echo "Client: Close {$from_id} \n";
		$this->protocol->on_close($server, $fd, $from_id);
	}

	public function on_timer($server, $interval)
	{
		echo "onTimer:\n";
		$this->protocol->on_timer($server, $interval);
	}

	public function on_task($server, $task_id, $from_id, $data)
	{
		echo "onTask:\n";
		$this->protocol->on_task($server, $task_id, $from_id, $data);
	}

	public function on_finish($server, $task_id, $data)
	{
		echo "onFinish:\n";
		$this->protocol->on_finish($server, $task_id, $data);
	}

	public function close($client_id)
	{
		\swoole_server_close($this->swoole, $client_id);
	}

	public function send($client_id, $data)
	{
		\swoole_server_send($this->swoole, $client_id, $data);
	}

	protected function translate_listen($listen)
	{
		$ret = [];
		if (is_scalar($listen)) {
			$listen = [[$listen]];
		}
		foreach ($listen as $v) {
			if (is_scalar($v)) {
				$v = [$v];
			}
			if (!isset($v[1])) {
				array_unshift($v, '0.0.0.0');
			}
			if (!isset($v[2])) {
				$v[] = SWOOLE_SOCK_TCP;
			}
			$ret[] = $v;
		}
		return $ret;
	}
}