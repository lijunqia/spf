<?php
namespace spf\Network\Server;
class Http extends Base
{
    protected function listen($host, $port)
    {
        return new \swoole_http_server($host, $port);
    }

    protected function bindEvent()
    {
        $this->swoole->on('request', [$this, 'onRequest']);
        parent::bindEvent();
    }

    function onRequest(\swoole_http_request $request, \swoole_http_response $response)
    {
        $request->server['request_microtime'] = microtime(TRUE);
        $this->protocol->onRequest($request, $response);
    }

    /**
     * @param $server
     * @param $workerId
     */
    public function onWorkerStart($server, $workerId)
    {
        $class = $this->config['protocol_class'];
        $this->protocol = new $class($server, $workerId,$this->config);
        $this->protocol->config = $this->config;
        parent::onWorkerStart($server, $workerId);
    }
}