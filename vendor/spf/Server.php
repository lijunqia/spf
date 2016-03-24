<?php
namespace spf;
use spf\Server\Monitor;
use \spf\Server\Manager;
use \Loader;
class Server extends Base{
	use \spf\Server\Base;
	protected $args;
	const SupervisionReq = 'ctrl';
	const ServerReq = 'serv';
	function __construct($args)
	{
		parent::__construct();
		$this->args=$args;
		$this->spfConfig=self::getConfig('spf');
		$config['test']='123';
		$this->init();
	}
	function run()
	{
		$args = $this->args;
		$cmd = $args['cmd'];
		$name = $args['name'];
		if($cmd==='list')return $this->listall();
		if(($args['type']===self::SupervisionReq))
		{
			return (new Monitor($name))->run($cmd,$name);
		}else{
			$server = new Manager(self::getServerConfig($name));
			$server->run($cmd,$name);
		}
	}
	public function listall()
	{
		echo "Servers Available:",PHP_EOL;
		foreach($this->getVHost() as $v)
			echo "\t\e[32;40m{$v}\e[0m",PHP_EOL;
	}
	function getVHost()
	{
		$files = glob(SPF_APP_PATH."/conf/vhost/*.php");
		$ret =[];
		foreach($files as $file) $ret[] = basename($file,'.php');
		return $ret;
	}
}