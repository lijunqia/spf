<?php
$serv = new swoole_server("127.0.0.1", 9502, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);
$serv->on('Packet', function ($serv, $data, $client) {
    $serv->sendTo($client['address'], $client['port'], "Server " . $data);
    print_r([$data, $client]);
});
//启动服务器
$serv->start();