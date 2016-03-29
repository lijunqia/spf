<?php
namespace spf\Server;
use spf\Server\Monitor;
use \spf\Server\Manager;
use \Loader;

class Server extends \spf\Base
{
    protected $args;
    const SupervisionReq = 'ctrl';
    const ServerReq = 'serv';
    protected $serverConfig;
    protected $spfConfig;
    protected $name;
    static $configs=[];
    function __construct($args)
    {
        parent::__construct();
        $this->args = $args;
        $config = $this->spfConfig = self::getConfig('spf');
        date_default_timezone_set($config['timezone']);
        foreach ($config['ini_set'] as $k=>$v)ini_set($k,$v);
    }
    static function getServerConfig($serverName,$key=NULL,$default=NULL)
    {
        return self::getConfig("vhost/{$serverName}",$key,$default);
    }
    static function &getConfig($package,$key=NULL,$default=NULL)
    {
        if(!isset(self::$configs[$package]))
        {
            $file = \Loader::getInstance()->findFile("conf/{$package}.php");
            if(!$file)throw new \Exception("The config file {$package} not found.");
            self::$configs[$package] = include $file;
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

    function run()
    {
        $args = $this->args;
        $cmd = $args['cmd'];
        $name = $args['name'];
        if ($cmd === 'list') return $this->listall();
        try {
            if (($args['type'] === self::SupervisionReq)) {
                $server =new Monitor($name);
            } else {
                $server = new Manager(self::getServerConfig($name));
            }
            return $server->run($cmd, $name);
        } catch (\Exception $e) {
            $this->exceptionHandler($e);
        }
    }

    public function listall()
    {
        echo "Servers Available:", PHP_EOL;
        foreach ($this->getVHost() as $v)
            echo "\t\e[32;40m{$v}\e[0m", PHP_EOL;
    }
    function getVHost()
    {
        $files = glob(SPF_APP_PATH . "/conf/vhost/*.php");
        $ret = [];
        foreach ($files as $file) $ret[] = basename($file, '.php');
        return $ret;
    }
}