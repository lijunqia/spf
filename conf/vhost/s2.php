<?php
return [
	'server'=>[
		//'type'=>'Socket',//server type: Socket/Http/WebSocket
		//'listen'=>[['0.0.0.0',8081,SWOOLE_SOCK_TCP],['0.0.0.0',8082,SWOOLE_SOCK_UDP],['/tmp/s2.sock',0,SWOOLE_UNIX_STREAM]],//port
		'type'=>'Http',//server type: Socket/Http/WebSocket
		'listen'=>[['0.0.0.0',8083],8084],
		//'index'=>'/opt/htdocs/psf/bin/socket.php',
		'process_name'=>$process_name='httpserver2',
		'master_pid_file' => "/tmp/{$process_name}-master.pid",
		'manager_pid_file' => "/tmp/{$process_name}-manager.pid",
	],
	'setting'=>[
		'worker_num'=>2,
		'task_worker_num'=>0,
		'dispatch_mode'=>2,
		'daemonize'=>0,
		'package_max_length'=>12582912,//post max length
		'buffer_output_size'=>12582912,
		'log_file'=>SPF_APP_PATH.'/var/log/swoole.log',
		'heartbeat_check_interval'=>60,
		'heartbeat_idle_time'=>600,
		'open_eof_check'=>false,
		'open_eof_split'=>false,
	]
];
