<?php
namespace spf\Server;
use spf\Server\Server;
use spf\Log;
class Manager extends \spf\Base
{
	public $config=[];
	function __construct($config)
	{
		parent::__construct();
		$this->config = $config;
		$loader = \Loader::getInstance();
	}
	function run($cmd,$name)
	{
		return $this->$cmd($name);
	}
	protected function start($name)
	{
		$config = $this->config;
		$class = '\spf\Network\Server\\'.$config['type'];
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
		$masterId = $this->getMasterPid();
		if (!$masterId) {
			echo $msg = "Stop Master: can not find master pid file.\e[31;40m[Failed]\e[0m",PHP_EOL;
			Log::warn("Stop Master Failed: can not find master pid file.",'spf');
			return false;
		} elseif (!posix_kill($masterId, 15)) {// && !posix_kill($masterId, 15)
			echo $msg = "Stop Master: send signal to master failed.\e[31;40m[Failed]\e[0m",PHP_EOL;
			Log::error("Stop Master Failed: send signal to master failed",'spf');
			return false;
		}
		($file = $this->getMasterPidFile()) && unlink($file);
		($file = $this->getManagerPidFile()) && unlink($file);
		usleep(50000);
		echo $msg = "Stop Master:{$masterId} sucess.\e[32;40m[OK]\e[0m",PHP_EOL;
		Log::info("Stop Master:{$masterId} sucess.",'spf');
		return true;
	}
	protected function reload()
	{
		$managerId = $this->getManagerPid();
		if (!$managerId) {
			echo '[warning] can not find manager pid file.',PHP_EOL,"Manager reload\e[31;40m [FAIL] \e[0m",PHP_EOL;
			Log::warn("[warning] can not find manager pid file.\nManager reload [FAIL]",'spf');
			return false;
		}
		if (!posix_kill($managerId, 10)){//USR1
			echo $msg = "Manager {$managerId} stop \e[31;40m [FAIL] \e[0m[warning] send signal to manager failed.",PHP_EOL;
			Log::warn("Manager {$managerId} stop failed, send signal to manager failed.",'spf');
			return false;
		}
		echo $msg ="Manager {$managerId} reload \033[32;40m [OK] \033[0m",PHP_EOL;
		Log::info("Manager {$managerId} reload [OK]",'spf');
		return true;
	}

	protected function  status()
	{
		$version = SWOOLE_VERSION;
		$running = $this->checkServerIsRunning()?"\e[32;40m [OK] \e[0m":"\e[31;40m [FAIL] \e[0m";
		$master=$this->getMasterPid();
		$manager=$this->getManagerPid();
		$msg = <<<HEREDOC
*****************************************************************
Summary:
Swoole Version: {$version}
Swoole-SPF: is running {$running}
master pid is : {$master}
manager pid is : {$manager}
*****************************************************************

HEREDOC;
		echo $msg;
	}
	protected function getMasterPid()
	{
		$masterPidFile=$this->getMasterPidFile();
		return is_file($masterPidFile)?file_get_contents($masterPidFile):FALSE;
	}
	protected function getManagerPid()
	{
		$managerPidFile=$this->getManagerPidFile();
		return is_file($managerPidFile)?file_get_contents($managerPidFile):FALSE;
	}
	protected function getMasterPidFile()
	{
		return $this->config['master_pid_file'];
	}
	protected function getManagerPidFile()
	{
		return $this->config['manager_pid_file'];
	}
	protected function checkServerIsRunning()
	{
		$pid = $this->getMasterPid();
		return $pid && Control::checkPidIsRunning($pid);
	}
	function getLocalIP()
	{
		return swoole_get_local_ip();
	}
}
