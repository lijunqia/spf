<?php
namespace phplib;

use spf\Log;

use spf\Network\Protocol;

use spf\Network\Protocol\Base;

class SwooleHttpProtocol extends Base implements Protocol
{
    function init()
    {
        \Loader::getInstance()->setIncludePath($this->config['root']);
    }
    function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $app = new SwooleApp($request,$response,$this->config);
    }
}