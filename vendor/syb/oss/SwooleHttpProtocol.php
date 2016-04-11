<?php
namespace syb\oss;

use spf\Network\Protocol;

use spf\Network\Protocol\Base;

use syb\wup\Taf_svr;

class SwooleHttpProtocol extends Base implements Protocol
{
    /**
     * 创建worker进程时,进行初始化,后续请求都可直接使用这里加载的资源
     */
    function init()
    {
        \Loader::get_instance()->set_include_path($this->config['root']);
        Taf_svr::loadDepends();
    }

    /**
     * 用户请求回调
     * @param \swoole_http_request $request
     * @param \swoole_http_response $response
     */
    function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        new SwooleApp($request,$response,$this->config);
    }
}