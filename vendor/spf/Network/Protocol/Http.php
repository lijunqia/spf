<?php
namespace spf\Network\Protocol;

use \spf\Server\Spf;

class Http extends Base
{
    public $name;

    /**
     * @param $server
     * @param $workerId
     */
    function on_start($server, $workerId)
    {
    }

    function on_request(\swoole_http_request $request, \swoole_http_response $response)
    {
        $callback = Spf::get_vhost_conf($this->name)['request_callback'];
        return $callback($request, $response);
    }
}