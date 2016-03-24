<?php
$config = [
	'env'=>'production',
	'timezone'=>'Asia/Shanghai',
	'ini_set' => [
		'error_reporting' => E_ALL,
		'display_errors' => 0,
		'log_errors' => 1,
		'error_log' => SPF_APP_PATH.'/var/log/phperror.log'
	],
	'super_process_name'=>'SPFDaemon',
	'unisock_path'=>'/var/run/SPFDaemon.sock',
	'logger'=>[
		'threshold' => 'info',  //日志级别:all,fatal,error,warn,info,debug,trace,off
		'optmizer'=>0,//是否在最后一次性写入
		'writer' => [
			'file'=>[//日志写入类型，下标可选为scribe,file
				'path' => SPF_APP_PATH.'/var/log',
				'base_name'=>'error.log',
				'layout_callback' => '',//日志格式化回调函数 spf\logger_layout_common
				'group_as_dir'=>1,//是否按组名分目录
			],
			/*'scribe'=>array(
				'host' => '10.10.5.136',
				'port' => 1463
			),*/
		],
	]
];

if($config['env']==='development')
{
	require('./spf.dev.php');
}
return $config;