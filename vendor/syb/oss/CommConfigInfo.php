<?php
//**********************************************************
// File name: CommConfigInfo.class.php
// Class name: CommConfigInfo
// Create date: 2011/8/25
// Update date: 2011/8/269
// Author: parkerzhu
// Description: 统一配置读取
//**********************************************************
namespace syb\oss;
/**
 * 定义节点下多个服务器获取的策略
 * @author parkerzhu
 *
 */
class ChooseType
{
    const NONE = 0;
    const RANDOM = 1;
    const FIRST = 2;
}

/**
 * 公用配置获取类
 * @author parkerzhu
 *
 */
class CommConfigInfo
{
    const COMM_CONFIG_PATH = '/usr/local/commweb/cfg/CommConfig/';
    const COMM_CONFIG_MEMCACHE_PATH = '/usr/local/commweb/cfg/CommMemcache/';
    const DB_CONFIG_FILE = 'commconf_php.cfg';
    const IDIP_CONFIG_FILE = 'commconf_php.cfg';
    const CONDITION_CONFIG_FILE = 'commconf_php.cfg';
    const MEMCACHE_CONFIG_FILE = 'apps_memcache.cfg';
    const JF_SVR_CONFIG_FILE = 'jf_svr_conf_php.cfg';

    private static $configs = array();

    private static $file = "";
    private static $node = "";
    private static $item = "";

    private static function file($file)
    {
        self::$file = $file;
    }

    private static function node($node)
    {
        self::$node = $node;
    }

    private static function item($item)
    {
        self::$item = $item;
    }

    private static function result()
    {
        if (empty(self::$file)) return FALSE;

        $config = array();
        if (isset($configs[self::$file])) {
            $config = $configs[self::$file];
        } else {
            $config = \parse_ini_file(self::$file, true);
            $configs[self::$file] = $config;
        }

        if (!empty(self::$node) && !empty($config)) {
            $config_node = $config[self::$node];
            if (!empty(self::$item) && !empty($config_node)) {
                return $config_node[self::$item];
            } else {
                return $config_node;
            }
        } else {
            return $config;
        }
    }

    private static function getInfo($node, $item = "")
    {
        self::node($node);
        self::item($item);
        return self::result();
    }

    private static function randomConfig($config, $choose, $keys_per_item = 2)
    {
        $sum = \array_values(\array_slice($config, 0, 1));
        if (empty($sum)) return FALSE;
        $sum = intval($sum[0]);

        if ($choose == ChooseType::FIRST) {
            return \array_values(\array_slice($config, 1, $keys_per_item));
        } else {
            $rand = mt_rand(0, $sum - 1);
            return \array_values(\array_slice($config, ($rand * $keys_per_item) + 1, $keys_per_item));
        }
    }

    /**
     * @desc 获取db直连的IP和端口
     * @param string $node 节点名
     * @return array 通过数组的ip和port元素取得信息
     */
    public static function GetDBInfo($node)
    {
        self::file(self::COMM_CONFIG_PATH . self::DB_CONFIG_FILE);
        return array(
            'ip' => self::getInfo($node, "db_ip"),
            'port' => self::getInfo($node, "db_port")
        );
    }

    /**
     * @desc 获取dbproxy的IP和端口
     * @param string $node 节点名
     * @return array 通过数组的ip和port元素取得信息
     */
    public static function GetDBProxyInfo($node)
    {
        self::file(self::COMM_CONFIG_PATH . self::DB_CONFIG_FILE);
        return array(
            'ip' => self::getInfo($node, "proxy_ip"),
            'port' => self::getInfo($node, "proxy_port")
        );
    }

    /**
     * @desc 获取IDIP的IP和端口
     * @param string $node 节点名
     * @param int $choose 自动选择节点类型，NONE为返回整个节点，RANDOM为随机选择节点下某一条配置，FIRST为始终选择第一个
     * @return array 通过数组的ip和port元素取得信息，或者所有服务器信息列表
     */
    public static function GetIDIPInfo($node = "default_idip", $choose = ChooseType::RANDOM)
    {
        self::file(self::COMM_CONFIG_PATH . self::IDIP_CONFIG_FILE);
        switch ($choose) {
            case ChooseType::RANDOM:
            case ChooseType::FIRST: {
                $config = self::getInfo($node);
                $rand = self::randomConfig($config, $choose);
                return array(
                    'ip' => $rand[0],
                    'port' => $rand[1]
                );
            }
            case ChooseType::NONE:
                return self::getInfo($node);
            default:
                return self::getInfo($node);
        }
    }

    /**
     * @desc 获取条件服务器的IP和端口
     * @param string $node 节点名
     * @param int $choose 自动选择节点类型，NONE为返回整个节点，RANDOM为随机选择节点下某一条配置，FIRST为始终选择第一个
     * @return array 通过数组的ip和port元素取得信息，或者所有服务器信息列表
     */
    public static function GetConditionServerInfo($node = "default_condition", $choose = ChooseType::RANDOM)
    {
        self::file(self::COMM_CONFIG_PATH . self::CONDITION_CONFIG_FILE);
        switch ($choose) {
            case ChooseType::RANDOM:
            case ChooseType::FIRST: {
                $config = self::getInfo($node);
                $rand = self::randomConfig($config, $choose);
                return array(
                    'ip' => $rand[0],
                    'port' => $rand[1]
                );
            }
            case ChooseType::NONE:
                return self::getInfo($node);
            default:
                return self::getInfo($node);
        }
    }

    /**
     * @desc 获取memcache服务器信息
     * @param string $node 节点名 默认为服务器列表
     * @param int $choose 选择方式 默认为NONE，即选择返回所有服务器列表信息，可选RANDOM,FIRST
     * @return array 通过数组的ip和port元素取得信息，或者所有服务器信息列表
     */
    public static function GetMemcacheInfo($node = "cacheserverlist", $choose = ChooseType::NONE)
    {
        self::file(self::COMM_CONFIG_MEMCACHE_PATH . self::MEMCACHE_CONFIG_FILE);
        switch ($choose) {
            case ChooseType::RANDOM:
            case ChooseType::FIRST: {
                $config = self::getInfo($node);
                $rand = self::randomConfig($config, $choose);
                return array(
                    'ip' => $rand[0],
                    'port' => $rand[1]
                );
            }
            case ChooseType::NONE:
                return self::getInfo($node);
            default:
                return self::getInfo($node);
        }
    }
}