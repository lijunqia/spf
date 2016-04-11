<?php
$serv = new swoole_server("127.0.0.1", 9501, SWOOLE_BASE, SWOOLE_SOCK_TCP);
//$serv = new swoole_server("127.0.0.1", 9501);
//$serv = new swoole_server("127.0.0.1", 9501, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);
$serv->set(array('worker_num' => 1, 'daemonize' => false));


$serv->set(array(
    'worker_num' => 1,   //工作进程数量
    'daemonize' => false, //是否作为守护进程
));
$serv->on('connect', function ($serv, $fd) {
    echo "Client:Connect.\n";
});
$serv->on('receive', function ($serv, $fd, $from_id, $data) {
    $serv->send($fd, 'Swoole: ' . $data);
    $serv->close($fd);
});
$serv->on('close', function ($serv, $fd) {
    echo "Client: Close.\n";
});
$serv->start();