<?php
namespace phplib;
class Loader extends \Loader{
    static $instance;
    public $autoloadPaths = [VENDOR_PATH];
    public $includePath = [];
    public $a = 'test';
    function __construct()
    {
        $this->realPathCache = realpath_cache_get();
        spl_autoload_register([$this, 'autoload'],true,true);//添加到队列之首
    }
}