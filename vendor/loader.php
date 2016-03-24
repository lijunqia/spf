<?php
/*function autoload($class)
{
	global $_autoload_path_arr;
	$file = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')). '.php';
	$file = findFile($file,$_autoload_path_arr);
	return $file ?(include $file):$file;
}
function findFile($file,$path=[])
{
	if(!$path)$path=getIncludePath();
	$cache = realpath_cache_get();
	$files = [];
	foreach ($path as $p) {
		$file = $files[] = $p . DIRECTORY_SEPARATOR . $file;
		if (isset($cache[$file]))return $file;
	}
	foreach ($files as $file) {
		if (is_file($file))return $file;
	}
	return FALSE;
}
function getIncludePath()
{
	static $path;
	if(!$path)$path = explode(PATH_SEPARATOR,get_include_path());
	return $path;
}
*/
class Loader{
	static $instance;
	protected $autoloadDir=[];
	protected $includePath=[];
	protected $realPathCache;
	function __construct($autoloadDir)
	{
		$this->autoloadDir = $autoloadDir;
		$this->includePath = explode(PATH_SEPARATOR,get_include_path());
		$this->realPathCache = realpath_cache_get();
		self::$instance = $this;
	}
	static function getInstance(){
		return self::$instance;
	}
	function autoload($class)
	{
		$class = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\'));
		$file = $this->findFile($class. '.php',TRUE);
		return $file?(include $file):$file;
	}
	function findFile($file,$isClass=FALSE)
	{
		$path=($isClass===TRUE)?$this->autoloadDir:$this->includePath;
		$cache = $this->realPathCache;
		$files = [];
		foreach ($path as $p) {
			$file = $files[] = $p . DIRECTORY_SEPARATOR . $file;
			if (isset($cache[$file]))return $file;
		}
		foreach ($files as $file) {
			if (is_file($file))return $file;
		}
		return FALSE;
	}
}
if(isset($_autoload_path_arr))$_autoload_path_arr=[];
$_autoload_path_arr=[dirname(__FILE__)];
spl_autoload_register([new Loader($_autoload_path_arr),'autoload']);