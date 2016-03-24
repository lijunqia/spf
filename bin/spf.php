#!/usr/bin/env php
<?php
define('SPF_APP_PATH', dirname(dirname(__FILE__)));
const phpBin = PHP_BINDIR . '/php';
const execBin = __FILE__;
const inDev = true;
const cmds = ['start', 'stop', 'reload', 'restart', 'shutdown', 'status', 'list'];
set_include_path(SPF_APP_PATH);
include '../vendor/loader.php';
$args = getOptions($argv);
(new spf\Server($args))->run();

////////////////////////////////////////////////////////////////////////////////
//
//                                     不修改部分
//
////////////////////////////////////////////////////////////////////////////////
/**
 * 取命令行参数
 * @param $args
 *
 * @return array
 */
function getOptions($args)
{
	$args = array_slice($args, 1);
	if((isset($args[0]) && $args[0] === spf\Server::ServerReq))
	{
		$type = spf\Server::ServerReq;
		array_shift($args);
	}else{
		$type = spf\Server::SupervisionReq;
	}
	$cmd = $name = null;
	$numArgs = count($args);
	if ($numArgs > 1) {
		list($cmd, $name) = $args;
	} elseif ($numArgs === 1) {
		$cmd = $args[0];
	} else {
		help();
	}
	if (!in_array($cmd, cmds))help();
	return ['type'=>$type, 'cmd'=>$cmd, 'name'=>$name];
}

/**
 * 帮助
 */
function help()
{
	echo <<<'HEREDOC'
SF version: sf/0.2.1
Usage: sf start|stop|reload|restart|shutdown|status|list [name]
<name> server name
Options:
  -?,-h         : this help
  -v            : show version and exit
  -V            : show version and configure options then exit

HEREDOC;
	exit;
}
