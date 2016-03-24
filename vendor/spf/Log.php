<?php
namespace spf;
class Log{
use Logger;
	static function load_config()
	{
		return Server::getInstance()->loadSpfConfig()['logger'];
	}
}