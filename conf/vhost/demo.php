<?php
return [
    'type' => 'Http',//server type: Socket/Http/WebSocket
    'listen' => [8080, ['0.0.0.0', 8081]],//port
    'root' => SPF_APP_PATH . '/public',
    'protocol_class' => '\demo\HttpProtocol',
    'monitor_unisock_path' => SPF_APP_PATH . '/var/run/swoole-demo-monitor.sock',
    'monitor_process_name' => 'swoole-demo-monitor',
    'master_process_name' => 'swoole-demo-master',
    'manager_process_name' => 'swoole-demo-manager',
    'worker_process_name' => 'swoole-demo-%s-work-%d',
    'master_pid_file' => SPF_APP_PATH . "/var/run/swoole-demo-master.pid",
    'manager_pid_file' => SPF_APP_PATH . "/var/run/swoole-demo-manager.pid",
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
        'open_eof_check' => FALSE,
        'open_eof_split' => FALSE,
    ],
];