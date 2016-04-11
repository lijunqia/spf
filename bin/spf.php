#!/usr/bin/env php
<?php
/**
 * SPF 是否处于开发调试模式
 */
const inDev = true;

define('SPF_APP_PATH', dirname(dirname(__FILE__)));
define('VENDOR_PATH',SPF_APP_PATH.DIRECTORY_SEPARATOR.'vendor');
require VENDOR_PATH .DIRECTORY_SEPARATOR. 'Loader.php';

////////////////////////////////////////////////////////////////////////////////
//
//                                     不修改部分
//
////////////////////////////////////////////////////////////////////////////////
const inSpf = true;
const execBin = __FILE__;
const cmds = ['start', 'stop', 'reload', 'restart', 'shutdown', 'status', 'list'];


//设定加载目录
Loader::get_instance()->set_include_path(SPF_APP_PATH);
//运行spf
use spf\Server\Spf;
(new Spf(get_options($argv)))->run();

/**
 * 取命令行参数
 * @param $args
 *
 * @return array
 */

function get_options($args)
{
    $args = array_slice($args, 1);
    if ((isset($args[0]) && $args[0] === Spf::ServerReq)) {
        $type = Spf::ServerReq;
        array_shift($args);
    } else {
        $type = Spf::SupervisionReq;
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
    if (!in_array($cmd, cmds)) help();
    return ['type' => $type, 'cmd' => $cmd, 'name' => $name];
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