<?php
namespace spf\Server;
trait Base
{
	protected $serverConfig;
	protected $spfConfig;
	protected $name;
	static $configs=[];
	static function getServerConfig($serverName,$key=NULL,$default=NULL)
	{
		return self::getConfig("vhost/{$serverName}",$key,$default);
	}
	static function &getConfig($package,$key=NULL,$default=NULL)
	{
		if(!isset(self::$configs[$package]))
		{
			$file=\Loader::getInstance()->findFile("conf/{$package}.php");
			if(!$file)throw new \Exception("The config file {$package} not found.");
			self::$configs[$package]=include $file;
		}
		$config = &self::$configs[$package];
		if($key===NULL){
			return $config;
		}elseif(isset($config[$key])){
			return $config[$key];
		}else{
			return $default;
		}
	}
	function init()
	{
		$config = $this->spfConfig;
		date_default_timezone_set($config['timezone']);
		foreach ($config['ini_set'] as $k=>$v)ini_set($k,$v);
		\set_error_handler(array($this, 'errorHandler'));
		\set_exception_handler(array($this, 'exceptionHandler'));
	}
	/**
	 * PHP错误接收函数，转为异常，异常再由异常处理接收
	 */
	function errorHandler($errno, $errstr, $file, $line)
	{
		if (error_reporting() & $errno) {
			throw new \ErrorException($errstr, 0, $errno, $file, $line);
		} else {
			\error_log("Error:{$errstr} (code:{$errno}) at:{$file} line:{$line}",3,ini_get('error_log'));
		}
	}
	/**
	 * 接收异常
	 *
	 * @param $e Exception
	 */
	function exceptionHandler($e)
	{
		$msg = $e->__toString();
		\spf\Log::error($msg);
		echo $msg;
	}
}