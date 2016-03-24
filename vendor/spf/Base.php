<?php
namespace spf;
class Base
{
	static $instances;

	function __construct()
	{
		$class = get_called_class();
		self::$instances[$class] = &$this;
	}

	static function getInstance()
	{
		$class = get_called_class();
		if (isset(self::$instances[$class])) {
			return self::$instances[$class];
		}
		return new $class;
	}
}