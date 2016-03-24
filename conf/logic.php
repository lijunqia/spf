<?php
$config = [
	'wx_appid_info' =>[
		'appid' => 'wxd778a8180f9c77b7',
		'secret' => 'cbe359965d17091644fe518a79e6d45c'
	],
	'qq_appid_info' => [
		'appid' => 100689805,
		'secret' => '93275a0a24d562c5e4ad2a12356d27b3'
	],
	'cookie' => 'syb_service',
	'activityid'=>[
		2080=>1,//票选酷跑男孩, 2080是活动id, 为1 can_help_self, 为0则相反
		2082=>1,//酷跑3d
		2084=>0,//爱消除春节
		2086=>1,//CF春节活
		2090=>1,//星河战神新机甲分享
		2091=>1,//星河战神新机甲登录
	],
	'OAUTH_TOKEN_URL' => 'http://10.194.94.113:12361/sns/oauth2/access_token?simple_get_token=1&',
	'OAUTH_USERINFO_URL' => 'http://10.194.94.113:12361/sns/userinfo?simple_get_token=1&',
	'time_out'=>5000,
];
return $config;