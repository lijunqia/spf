<?php
$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_connect($sock, "255.255.255.255", 10000);
socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
$buf = "Hello World!";
socket_write($sock, $buf, strlen($buf));
socket_close($sock);