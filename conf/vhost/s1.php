<?php
return [
    'type' => 'Http',//server type: Socket/Http/WebSocket
    'listen' => [8081, ['0.0.0.0', 8082]],//port
    'root' => SPF_APP_PATH . '/public',
    'protocol_class' => '\phplib\SwooleHttpProtocol',
    'monitor_unisock_path' => SPF_APP_PATH . '/var/run/swoole-s1-monitor.sock',
    'monitor_process_name' => 'swoole-s1-monitor',
    'master_process_name' => 'swoole-s1-master',
    'manager_process_name' => 'swoole-s1-manager',
    'worker_process_name' => 'swoole-s1-%s-work-%d',
    'master_pid_file' => SPF_APP_PATH . "/var/run/swoole-s1-master.pid",
    'manager_pid_file' => SPF_APP_PATH . "/var/run/swoole-s1-manager.pid",
    'user' => 'zhangjl',
    'gzip' => 0,
    'gzip_level' => 6,
    'setting' => [
        'worker_num' => 2,
        'task_worker_num' => 0,
        'dispatch_mode' => 2,
        'daemonize' => 0,
        'package_max_length' => 12582912,//post max length
        'buffer_output_size' => 12582912,
        'log_file' => SPF_APP_PATH . '/var/log/swoole.log',
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 600,
        'open_eof_check' => false,
        'open_eof_split' => false,
    ],
];
