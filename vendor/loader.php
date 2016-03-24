<?php

class Loader
{
	static $instance;
	protected $autoloadDir = [];
	protected $includePath = [];
	protected $realPathCache;

	function __construct($autoloadDir)
	{
		$this->autoloadDir = $autoloadDir;
		$this->includePath = explode(PATH_SEPARATOR, get_include_path());
		$this->realPathCache = realpath_cache_get();
		self::$instance = $this;
	}

	static function getInstance()
	{
		return self::$instance;
	}

	function autoload($class)
	{
		$class = str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\'));
		$file = $this->findFile($class . '.php', true);
		return $file ? (include $file) : $file;
	}

	function findFile($file, $isClass = false)
	{
		$path = ($isClass === true) ? $this->autoloadDir : $this->includePath;
		$cache = $this->realPathCache;
		$files = [];
		foreach ($path as $p) {
			$file = $files[] = $p . DIRECTORY_SEPARATOR . $file;
			if (isset($cache[$file]))return $file;
		}
		foreach ($files as $file)if (is_file($file))return $file;
		return false;
	}
}
define('VENDOR_PATH',dirname(__FILE__));
if (isset($_autoload_path_arr))$_autoload_path_arr = [];
$_autoload_path_arr = [VENDOR_PATH];
spl_autoload_register([new Loader($_autoload_path_arr), 'autoload']);