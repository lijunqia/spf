<?php
defined('VENDOR_PATH') ?: define('VENDOR_PATH', dirname(__FILE__));

class Loader
{
	static $instance;
	public $autoload_paths = [VENDOR_PATH];
	public $include_path = [];

	/**
	 * @return Loader
	 */
	static function get_instance()
	{
		return static::$instance ?: (new self);
	}

	function __construct()
	{
		spl_autoload_register([$this, 'autoload'], true, true);//添加到队列之首
		self::$instance = $this;
	}

	function __destruct()
	{
		spl_autoload_unregister([$this, 'autoload']);
		unset($this->autoload_paths, $this->include_path);
	}

	/**
	 * 自动加载类
	 *
	 * @param $class
	 *
	 * @return bool
	 */
	function autoload($class)
	{
		$file = $this->find_class($class);
		if ($file) {
			return include $file;
		} else {
			return false;
		}
	}

	/**
	 * 设置类库加载路径
	 *
	 * @param string|array $paths  路径,可以字符串或数组
	 * @param bool         $prefix 默认加在其它路径之前,为false则追加路径在后
	 */
	function set_autoload_path($paths, $prefix = true)
	{
		array_splice($this->autoload_paths, ($prefix ? 0 : count($this->autoload_paths)), 0, $paths);
	}

	/**
	 * 设置文件查找路径
	 *
	 * @param string|array $paths
	 * @param bool         $prefix
	 */
	function set_include_path($paths, $prefix = true)
	{
		array_splice($this->include_path, ($prefix ? 0 : count($this->autoload_paths)), 0, $paths);
	}

	/**
	 * 查找类对应的文件
	 *
	 * @param       $class
	 * @param array $paths 查找路径
	 *
	 * @return bool|string
	 */
	function find_class($class, $paths = [])
	{
		return $this->find_file(str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')) . '.php', $paths ?: $this->autoload_paths);
	}

	/**
	 * 查找文件
	 *
	 * @param       $file
	 * @param array $path
	 *
	 * @return bool|string
	 */
	function find_file($file, $path = [])
	{
		$cache = realpath_cache_get();
		$files = [];
		foreach (($path ?: $this->include_path) as $p) {
			$f = $p . DIRECTORY_SEPARATOR . $file;
			if (isset($cache[$f])) {
				return $f;
			}
			$files[] = $f;
		}
		foreach ($files as $file) {
			if (is_file($file)) {
				return $file;
			}
		}
		return false;
	}
}

(new Loader);