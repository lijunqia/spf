<?php
namespace spf\Server;
use spf\Server;
use \swoole_process;
class Monitor
{
	protected $name;
	protected $cmd;
	protected $process;
	protected $processExist = NULL;
	protected $monitorProcessName;
	protected $unisockPath;
	function __construct($name)
	{
		$this->name = $name;
		$config = Server::getServerConfig($name,'server');
		$this->monitorProcessName=$config['monitor_process_name'];
		$this->unisockPath=$config['monitor_unisock_path'];
		$this->processExist = \spf\Process\Control::exists($this->monitorProcessName);
	}

	/**
	 * @param $argv
	 */
	function run($cmd)
	{
		$this->cmd = $cmd;
		$name = $this->name;
		$redirect_stdin_stdout =($cmd==='start' || inDev===TRUE)?FALSE:TRUE;
		$process = new swoole_process(function(swoole_process $worker)use($cmd,$name){
			$worker->exec(phpBin, array(execBin, Server::ServerReq ,$cmd, $name));
		},$redirect_stdin_stdout);
		$pid = $process->start();
	}
	/**
	 * 检查进程是否已经启动
	 */
	protected function checkProcessExist()
	{
		if (!$this->processExist) {
			throw new \LogicException("The process {$this->monitorProcessName} is not running,please check it.");
		}
	}
	/**
	 * 用于和守护进程进行通信
	 * @param $data
	 * @return mixed
	 */
	protected function sendCmd($data)
	{
		$client = new swoole_client(SWOOLE_UNIX_STREAM, SWOOLE_SOCK_SYNC);
		$client->connect($this->unisockPath, 0);
		$client->send(json_encode($data));
		$ret = $client->recv();
		//if(!empty($ret['code']))throw new \Exception();
		$ret = json_decode($ret, true);
		$client->close();
		return $ret;
	}
	protected function showCmdReturn($cmd, $name, $ret)
	{
		$msg = "call: `php {$cmd} {$name}``,The return is ".print_r($ret, true);
		echo $msg;
	}
}