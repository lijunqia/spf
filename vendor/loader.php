<?php
if (!defined('VENDOR_PATH')) {
    define('VENDOR_PATH', dirname(__FILE__));
}

class Loader
{
    static $instance;
    public $autoloadPaths = [VENDOR_PATH];
    public $includePath = [];
    public $realPathCache;

    /**
     * @return Loader
     */
    static function getInstance()
    {
        $class = get_called_class();
        return static::$instance?:(new $class);
    }
    function __construct()
    {
        spl_autoload_register([$this, 'autoload'],true,true);//添加到队列之首
        self::$instance = $this;
    }

    /**
     * 自动加载类
     * @param $class
     * @return bool
     */
    function autoload($class)
    {
        $file = $this->findClass($class);
        if($file){
            return include $file;
        }else{
            return false;
        }
    }
    /**
     * 设置类库加载路径
     * @param string|array $paths   路径,可以字符串或数组
     * @param bool $prefix  默认加在其它路径之前,为false则追加路径在后
     */
    function setAutoloadPath($paths, $prefix = true)
    {
        array_splice($this->autoloadPaths, ($prefix ? 0 : count($this->autoloadPaths)), 0, $paths);
    }

    /**
     * 设置文件查找路径
     * @param string|array $paths
     * @param bool $prefix
     */
    function setIncludePath($paths, $prefix = true)
    {
        array_splice($this->includePath, ($prefix ? 0 : count($this->autoloadPaths)), 0, $paths);
    }
    /**
     * 查找类对应的文件
     * @param $class
     * @param array $paths  查找路径
     * @return bool|string
     */
    function findClass($class,$paths=[])
    {
        return $this->findFile(str_replace('\\', DIRECTORY_SEPARATOR, ltrim($class, '\\')) . '.php', $paths?:$this->autoloadPaths);
    }

    /**
     * 查找文件
     * @param $file
     * @param array $path
     * @return bool|string
     */
    function findFile($file, $path=[])
    {
        $cache = realpath_cache_get();
        $files = [];
        foreach (($path?:$this->includePath) as $p) {
            $f = $p . DIRECTORY_SEPARATOR . $file;
            if (isset($cache[$f]))return $f;
            $files[] = $f;
        }
        foreach ($files as $file) {
            if (is_file($file))return $file;
        }
        return false;
    }
    function __destruct()
    {
        spl_autoload_unregister([$this,'autoload']);
        unset($this->autoloadPaths,$this->includePath);
    }
}
(new Loader);