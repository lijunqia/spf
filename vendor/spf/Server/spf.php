<?php
namespace spf\Server;

use \spf\Server\Monitor;
use \spf\Server\Manager;
use spf\Log;

class Spf extends \spf\Base
{
    static $configs = [];
    const SupervisionReq = 'ctrl';
    const ServerReq = 'serv';
    protected $args;
    protected $spf_conf_arr;
    protected $name;

    function __construct($args)
    {
        parent::__construct();
        $this->args = $args;
        $config = $this->spf_conf_arr = self::get_conf('spf');
        date_default_timezone_set($config['timezone']);
        foreach ($config['ini_set'] as $k => $v) ini_set($k, $v);
        //日志初始化
        Log::init();
    }

    static function get_vhost_conf($serverName, $key = NULL, $default = NULL)
    {
        return self::get_conf("vhost/{$serverName}", $key, $default);
    }

    static function &get_conf($package, $key = NULL, $default = NULL)
    {
        if (!isset(self::$configs[$package])) {
            $file = \Loader::get_instance()->find_file("conf/{$package}.php");
            if (!$file) throw new \Exception("The config file {$package} not found.");
            self::$configs[$package] = include $file;
        }
        $config = &self::$configs[$package];
        if ($key === NULL) {
            return $config;
        } elseif (isset($config[$key])) {
            return $config[$key];
        } else {
            return $default;
        }
    }

    function run()
    {
        $args = $this->args;
        $cmd = $args['cmd'];
        $name = $args['name'];
        if ($cmd === 'list') return $this->listall();
        if (($args['type'] === self::SupervisionReq)) {
            $server = new Monitor($name);
        } else {
            $server = new Manager(self::get_vhost_conf($name));
        }
        return $server->run($cmd, $name);
    }

    public function listall()
    {
        echo "Servers Available:", PHP_EOL;
        foreach ($this->get_vhost_files() as $v)
            echo "\t\e[32;40m{$v}\e[0m", PHP_EOL;
    }

    function get_vhost_files()
    {
        $files = glob(SPF_APP_PATH . "/conf/vhost/*.php");
        $ret = [];
        foreach ($files as $file) $ret[] = basename($file, '.php');
        return $ret;
    }
    static function print_red($str='[Failed]')
    {
        return "\e[31;40m{$str}\e[0m";
    }
    static function print_green($str='[OK]')
    {
        return "\e[32;40m{$str}\e[0m";
    }
}