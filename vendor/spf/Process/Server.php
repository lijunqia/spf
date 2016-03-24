<?php
namespace spf\Process;
use \spf\Base;
class Server extends Base{
	use \spf\Application;
	function __construct()
	{
		$this->initConfig();
		$this->init();
	}
	function run($cmd,$name)
	{
		$this->initConfig();
		$conf=[];
		if($name){
			if(!isset($this->serverConfig[$name])){
				throw new \Exception("No config file defined for the server {$name}.");
			}
			$conf[$name]=$this->serverConfig[$name];
		}else{
			if($cmd==='start')throw new \Exception("please input cmd and server name.");
			$conf=$this->serverConfig;//操作所有server
		}
		foreach($conf as $file){
			$config = include $file;
			$class = '\spf\Network\Server\\'.$config['server']['type'];
			$server = new $class($config);
			$server->run($cmd);
		}
	}
}
 