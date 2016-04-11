<?php
namespace spf\Network\Server;
class Http extends Base
{
    protected function listen($host, $port)
    {
        return new \swoole_http_server($host, $port);
    }

    protected function bind_event()
    {
        $this->swoole->on('request', [$this, 'on_request']);
        parent::bind_event();
    }

    function on_request(\swoole_http_request $request, \swoole_http_response $response)
    {
        $request->server['request_microtime'] = microtime(TRUE);
        $this->protocol->on_request($request, $response);
    }

    /**
     * @param $server
     * @param $workerId
     */
    public function on_worker_start($server, $workerId)
    {
        $class = $this->config['protocol_class'];
        $this->protocol = new $class($server, $workerId,$this->config);
        $this->protocol->config = $this->config;
        parent::on_worker_start($server, $workerId);
    }
}