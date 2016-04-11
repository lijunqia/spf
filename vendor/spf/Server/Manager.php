<?php
namespace spf\Server;

use spf\Log;
use spf\Process\Control;

class Manager extends \spf\Base
{
	public $config = [];

	function __construct($config)
	{
		parent::__construct();
		$this->config = $config;
		$loader = \Loader::get_instance();
	}

	function run($cmd, $name)
	{
		return $this->$cmd($name);
	}

	protected function start($name)
	{
		$config = $this->config;
		$class = '\spf\Network\Server\\' . $config['type'];
		$server = new $class($config);
		$server->name = $name;
		return $server->start();
	}

	protected function stop()
	{
		$this->shutdown();
	}

	protected function shutdown()
	{
		$master_id = $this->get_master_pid();
		if (!$master_id) {
			echo $msg = "[Failed] Stop Master: can not find master pid file.", Spf::print_red('[Failed]'), PHP_EOL;
			Log::warn($msg, 'spf');
			return false;
		} elseif (!posix_kill($master_id, 15)) {// && !posix_kill($masterId, 15)
			echo $msg = "[Failed] Stop Master: send signal to master failed.", Spf::print_red('[Failed]'), PHP_EOL;
			Log::error($msg, 'spf');
			return false;
		}
		($file = $this->get_master_pid_file()) && unlink($file);
		($file = $this->get_manager_pid_file()) && unlink($file);
		usleep(50000);
		echo $msg = "Stop Master:{$master_id} sucess.", Spf::print_green('[OK]'), PHP_EOL;
		Log::info($msg, 'spf');
		return true;
	}

	protected function reload()
	{
		$manager_id = $this->get_manager_pid();
		if (!$manager_id) {
			$msg = "[warning] can not find manager pid file.Manager reload failed!";
			echo  $msg,Spf::print_red('[Failed]'), PHP_EOL;
			Log::warn($msg, 'spf');
			return false;
		}
		if (!posix_kill($manager_id, 10)) {//USR1
			echo $msg = "Manager {$manager_id} stop \e[31;40m [FAIL] \e[0m[warning] send signal to manager failed.", Spf::print_green('[OK]'), PHP_EOL;
			Log::warn($msg, 'spf');
			return false;
		}
		echo $msg = "Manager {$manager_id} reload OK!", Spf::print_green('[OK]'), PHP_EOL;;
		Log::info($msg, 'spf');
		return true;
	}

	protected function status()
	{
		$version = SWOOLE_VERSION;
		$running = $this->is_running() ? Spf::print_green('[OK]'):Spf::print_red('[Failed]');
		$master = $this->get_master_pid();
		$manager = $this->get_manager_pid();
		echo <<<HEREDOC
*****************************************************************
Summary:
Swoole Version: {$version}
Swoole-SPF: is running {$running}
master pid is : {$master}
manager pid is : {$manager}
*****************************************************************

HEREDOC;
	}

	protected function get_master_pid()
	{
		$master_pid_file = $this->get_master_pid_file();
		return is_file($master_pid_file) ? file_get_contents($master_pid_file) : false;
	}

	protected function get_manager_pid()
	{
		$manager_pid_file = $this->get_manager_pid_file();
		return is_file($manager_pid_file) ? file_get_contents($manager_pid_file) : false;
	}

	protected function get_master_pid_file()
	{
		return $this->config['master_pid_file'];
	}

	protected function get_manager_pid_file()
	{
		return $this->config['manager_pid_file'];
	}

	protected function is_running()
	{
		$pid = $this->get_master_pid();
		return $pid && Control::check_pid_is_running($pid);
	}

	function get_local_ip()
	{
		return swoole_get_local_ip();
	}
}
