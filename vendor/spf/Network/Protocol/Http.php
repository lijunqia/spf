<?php
namespace spf\Network\Protocol;

use \spf\Server\Server;

class Http extends Base
{
    public $name;

    /**
     * @param $server
     * @param $workerId
     */
    function onStart($server, $workerId)
    {
    }

    function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $callback = Server::getServerConfig($this->name)['request_callback'];
        return $callback($request, $response);
    }
}