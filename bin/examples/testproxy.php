<?php
$client = new swoole_client(SWOOLE_SOCK_TCP);

//连接到服务器
//if (!$client->connect('10.225.164.177', 8080, 0.5)) {
//if (!$client->connect('140.207.128.212', 9001, 0.5)) {
if (!$client->connect('10.219.9.24', 10060, 0.5)) {
    die("connect failed.");
}
//向服务器发送数据
if (!$client->send("hello world")) {
    die("send failed.");
}
//从服务器接收数据
$data = $client->recv();
if (!$data) {
    die("recv failed.");
}
echo 'received:', $data, "\n";
//关闭连接
$client->close();
