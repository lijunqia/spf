<?php
//同步堵塞 SWOOLE_SOCK_SYNC,异步 SWOOLE_SOCK_ASYNC
$client = new swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
//$client = new swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_ASYNC);
/*$client->on("connect", function (swoole_client $cli) {
    $cli->send("GET / HTTP/1.1\r\n\r\n");
});
$client->on("receive", function (swoole_client $cli, $data) {
    echo "Receive: $data";
    $cli->send(str_repeat('A', 100) . "\n");
    sleep(1);
});
$client->on("error", function (swoole_client $cli) {
    echo "error\n";
});
$client->on("close", function (swoole_client $cli) {
    echo "Connection close\n";
});
$client->connect('127.0.0.1', 9501);
*/



/*$ret = $client->connect('127.0.0.1', 9501, 0.5, 0);
$client->send("hello world\n");
$data = $client->recv(1024);
echo $data;
unset($client);
*/


$client = new swoole_client(SWOOLE_SOCK_UDP, SWOOLE_SOCK_SYNC);
$client->connect('127.0.0.1', 9501);

for ($i = 0; $i < 100; $i++)
{
    $client->send("admin");
    echo $client->recv()."\n";
    sleep(1);
}
