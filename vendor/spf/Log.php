<?php
namespace spf;

use spf\Server\Spf;
use spf\Logger;
class Log
{
    /**
     * @var Logger
     */
    static $instance;

    static function init($instance = NULL)
    {
        self::$instance = $instance ?: (new Logger(Spf::get_conf('spf')['logger']));
    }

    public static function trace($msg, $group = '')
    {
        return self::$instance->log('trace', $msg, $group);
    }

    public static function debug($msg, $group = '')
    {
        return self::$instance->log('debug', $msg, $group);
    }

    public static function info($msg, $group = '')
    {
        return self::$instance->log('info', $msg, $group);
    }

    public static function user1($msg, $group = '')
    {
        return self::$instance->log('user1', $msg, $group);
    }

    public static function user2($msg, $group = '')
    {
        return self::$instance->log('user2', $msg, $group);
    }

    public static function warn($msg, $group = '')
    {
        return self::$instance->log('warn', $msg, $group);
    }

    public static function error($msg, $group = '')
    {
        return self::$instance->log('error', $msg, $group);
    }

    public static function fatal($msg, $group = '')
    {
        return self::$instance->log('fatal', $msg, $group);
    }

    protected static function log($type, $msg, $group = '')
    {
        self::$instance->log($type, $msg, $group);
    }

}