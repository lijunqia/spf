<?php
namespace syb\logic;
class Login
{
	protected static $instance;
	/**
	 * 活动id
	 *
	 * @var string
	 */
	protected $activityid = '0';
	/**
	 * 账户类型
	 *
	 * @var int
	 */
	protected $acctype = 0;
	/**
	 * 能够自己帮自己添加分享次数( 用户自己点一次分享就添加一次机会 )
	 *
	 * @var int
	 */
	protected $can_help_self = 0;
	/**
	 * 请求接口超时时间设置
	 *
	 * @var int
	 */
	protected $time_out = 5000;
	//protected $OAUTH_TOKEN_URL;
	//protected $OAUTH_USERINFO_URL;
	//protected $wx_appid_info;
	//protected $qq_appid_info;
	/**
	 * 2080 票选酷跑男孩 2082 酷跑3d 2084 爱消除春节 2086 CF春节活动
	 * 2090 星河战神新机甲分享 2091 星河战神新机甲登录
	 */
	protected $appid_secret_arr = array();
	protected $wx_union_id;
	protected $wx_open_id;
	protected $wx_nick;
	protected $wx_pic_url;
	/**
	 * 所有配置
	 *
	 * @var
	 */
	protected $config;
	/**
	 * 性能上报
	 *
	 * @var Monitor
	 */
	protected $monitor;

	/**
	 * @var \swoole_http_request
	 */
	protected $request;

	function __construct(\swoole_http_request $request)
	{
		$this->request = $request;
	}

	function init()
	{
//		$this->config = $config = \App::get_instance()->get_cfg(null, 'logic');
		//账户类型acctype
		$acctype = isset($_GET['acctype']) ? intval($_GET['acctype']) : 0;//1:qq,2:wx
		$this->acctype = $acctype;
		//活动id
		$act_id = isset($_GET['act_id']) ? intval($_GET['act_id']) : '0';
		$activityids = $config['activityid'];
		if (isset($activityids[$act_id])) {
			$this->activityid = $act_id;
			$this->can_help_self = $activityids[$act_id];
		} else {
			throw new \Exception("参数非法(act_id={$act_id})", 90401);
		}
	}

	/**
	 * 获取当前活动的 appid
	 * 控制活动id的有效期
	 */
	public function get_appid()
	{
		return $this->activityid;
	}

	/**
	 * 返回游戏的appid
	 *
	 * @return mixed
	 */
	public function get_game_appid()
	{
		$acctype = $this->acctype;
		if ($acctype === 3) {//需要判断是wx 登录还是qq登录
			$acctype = isset($_COOKIE['svc_type']) ? intval(
				$_COOKIE['svc_type']
			) : 0;//1:qq,2:wx
		}
		$config = $this->config;
		if ($acctype === 2) {//表示wx
			return $config['wx_appid_info']['appid'];
		} else {//表示qq
			return $config['qq_appid_info']['appid'];
		}
	}

	/**
	 * 根据不同的平台分别判断登录状态
	 *
	 * @para
	 * @ret：
	 */
	public function web_login_check()
	{
		$acctype = $this->acctype;
		if ($acctype == 1) {
			//表示qq
			MONITOR_U_START("uin_login_check");
			$ret = $this->uin_login_check();
			MONITOR_U_STOP("uin_login_check", $ret['errno']);
			if ($ret['errno'] != 0) {
				throw new Exception("您没有登录或登录已失效", 90100);
			}
		} else {
			if ($acctype == 2) {
				//表示wx
				MONITOR_U_START("wx_login_check");
				$ret = $this->wx_login_check();
				MONITOR_U_STOP("wx_login_check", $ret["errno"]);
				if ($ret['errno'] != 0) {
					throw new Exception("您没有登录或登录已失效", 90100);
				}
			} else {
				if ($acctype == 3) {
					//表示syb
					MONITOR_U_START("syb_login_check");
					$ret = $this->syb_login_check();
					MONITOR_U_STOP("syb_login_check", $ret);
					if ($ret != 0) {
						throw new Exception("您没有登录或登录已失效", 90100);
					}
				} else {
					OSS_ERROR("input acctype err. acctype:" . $acctype . "\n");
					throw new Exception("您没有登录或登录已失效", 90100);
				}
			}
		}
	}

	/**
	 * uin 转换为游戏的 openid
	 *
	 * @para: uin: qq号, skey:qq登录的skey
	 * @ret
	 */
	public function uin_to_openid($uin, $skey)
	{
		//uin to commid
		//$skey = $_COOKIE['skey'];
		$clientip = 0;
		$this->load->logic(
			"account_exchange", "account_exchange_instance", "sybsvr"
		);
		$ip_info = $this->get_server_ip_port("syb_im_account");
		$this->account_exchange_instance->init(
			$this->uin, $ip_info, $this->time_out
		);
		$ret = $this->account_exchange_instance->uin_to_commid(
			$uin, $this->qq_appid_info['appid'], $skey, $clientip
		);
		if ($ret['errno'] != 0) {
			OSS_ERROR(
				"[uin:$this->uin,errno: " . $ret['errno'] . ", errinfo: "
				. $ret['error'] . ",cmd:" . $ret['cmd'] . ",uin:" . $uin
				. ",skey:" . $skey . "\n"
			);
			throw new Exception(
				"系统繁忙,请稍后再试[" . $ret['cmd'] . "|" . $ret['errno'] . "]",
				-($ret['errno'] + 93400)
			);
		}
		$commid = $ret['commonid'];
		//commid to openid
		$commonid_list[] = $commid;
		$ret = $this->account_exchange_instance->batch_commid_to_openid(
			$this->qq_appid_info['appid'], $commonid_list
		);
		if ($ret_openid['errno'] != 0) {
			OSS_ERROR(
				"[uin:$this->uin,errno: " . $ret['errno'] . ", errinfo: "
				. $ret['error'] . ",cmd:" . $ret['cmd'] . ",commid_list:"
				. json_encode($commonid_list) . "\n"
			);
			throw new Exception(
				"系统繁忙,请稍后再试[" . $ret['cmd'] . "|" . $ret['errno'] . "]",
				-($ret['errno'] + 93400)
			);
		}
		$info = $ret['info'];
		$count = count($info);
		for ($i = 0; $i < $count; $i++) {
			if ($info[$i]['commonid'] == $commid) {
				return $info[$i]['openid'];
			}
		}
		return "";
	}

	/**
	 * qq 的登录验证，直接通过skey, uin 去验证
	 *
	 * @para：void
	 * @ret ：
	 */
	public function uin_login_check()
	{
		$uin = $_COOKIE['uin'];
		$uin = (float)substr($uin, 1, strlen($uin) - 1);
		$skey = $_COOKIE['skey'];
		$ipt_domainid = -1;
		$this->load->logic(
			"account_exchange", "account_exchange_instance", "sybsvr"
		);
		$ip_info = $this->get_server_ip_port("syb_im_account");
		$this->account_exchange_instance->init(
			$this->uin, $ip_info, $this->time_out
		);
		$ret = $this->account_exchange_instance->uin_login_check(
			$uin, $skey, $ipt_domainid
		);
		if ($ret['errno'] != 0) {
			OSS_ERROR(
				"uin_login_check err: " . json_encode($ret) . ",uin:" . $uin
				. ",skey:" . $skey . ",ip_info:" . json_encode($ip_info) . " \n"
			);
		}
		$this->uin = $uin;
		return $ret;
	}

	/**
	 * 判断wx 是否登录
	 *
	 * @para
	 * @ret:
	 */
	public function wx_login_check()
	{
		$ret_wx_info['errno'] = 0;
		$ret_wx_info['wx_openid'] = '';
		$ret_wx_info['wx_unionid'] = '';
		$ret_wx_info['wx_nick'] = '';
		$ret_wx_info['wx_pic_url'] = '';
		/*
		$wx_nick = $_COOKIE['wx_nick'];
		$wx_pic_url = $_COOKIE['wx_pic_url'];
		$wx_openid = $_COOKIE['wx_openid'];
		$wx_unionid = $_COOKIE['wx_unionid'];
		$wx_access_token = $_COOKIE['wx_access_token'];
		*/
		$pre = $this->appid_secret_arr[$this->activityid]['cookie'];
		$open_id_cookie = 'wx_' . $pre . '_openid';
		$wx_nick = $_COOKIE['wx_nick'];
		$wx_pic_url = $_COOKIE['wx_pic_url'];
		$wx_openid = $_COOKIE[$open_id_cookie];
		$wx_unionid = $_COOKIE['wx_unionid'];
		$token_cookie = 'wx_' . $pre . '_access_token';
		$wx_access_token = $_COOKIE[$token_cookie];
		if (!$wx_openid || empty($wx_openid) || !$wx_unionid
			|| empty($wx_unionid)
		) {
			$code = $_GET['code'];
			if (!$code || empty($code)) {
				OSS_ERROR(
					"login out or code err. wx_openid:" . $wx_openid
					. " , wx_unionid:" . $wx_unionid . " , code:" . $code
					. ", wx_login_act_id:" . $this->activityid . " \n "
				);
				$ret_wx_info['errno'] = -1;
				return $ret_wx_info;
			}
			//通过code获取wx的信息
			$ret = $this->getOauthAccessToken($code, $json);
			if ($ret != 0) {
				OSS_ERROR(
					"getOauthAccessToken err. wx_openid:" . $wx_openid
					. " , wx_unionid:" . $wx_unionid . " , code:" . $code
					. ",json:" . json_encode($json) . ", wx_login_act_id:"
					. $this->activityid . " \n "
				);
				$ret_wx_info['errno'] = -2;
				return $ret_wx_info;
			}
			if (!$json['openid'] || empty($json['openid'])) {
				OSS_ERROR(
					"getOauthAccessToken json err. wx_openid:" . $wx_openid
					. " , wx_unionid:" . $wx_unionid . " , code:" . $code
					. ",json:" . json_encode($json) . ", wx_login_act_id:"
					. $this->activityid . " \n "
				);
				$ret_wx_info['errno'] = -3;
				return $ret_wx_info;
			}
			$wx_unionid = $json['unionid'];
			$wx_openid = $json['openid'];
			$wx_access_token = $json['access_token'];
			$wx_info = $this->getOauthUserinfo($wx_access_token, $wx_openid);
			$wx_nick = "";
			$wx_pic_url = "";
			if ($wx_info) {
				$wx_nick = $wx_info['nickname'];
				$wx_pic_url = $wx_info['headimgurl'];
			}
			$now_time = time();
			setcookie("wx_unionid", "", $now_time - 3600, "/", "qq.com", 1);
			setcookie($open_id_cookie, "", $now_time - 3600, "/", "qq.com", 1);
			setcookie($token_cookie, "", $now_time - 3600, "/", "qq.com", 1);
			setcookie("wx_nick", "", $now_time - 3600, "/", "qq.com", 1);
			setcookie("wx_pic_url", "", $now_time - 3600, "/", "qq.com", 1);
			setcookie("wx_unionid", $wx_unionid, 0, "/", "qq.com", 0);
			setcookie($open_id_cookie, $wx_openid, 0, "/", "qq.com", 0);
			setcookie($token_cookie, $wx_access_token, 0, "/", "qq.com", 0);
			setcookie("wx_nick", $wx_nick, 0, "/", "qq.com", 0);
			setcookie("wx_pic_url", $wx_pic_url, 0, "/", "qq.com", 0);
		}
		$ret_wx_info['wx_openid'] = $wx_openid;
		$ret_wx_info['wx_unionid'] = $wx_unionid;
		$ret_wx_info['wx_nick'] = $wx_nick;
		$ret_wx_info['wx_pic_url'] = $wx_pic_url;
		$this->wx_union_id = $wx_unionid;
		$this->wx_open_id = $wx_openid;
		$this->wx_nick = $wx_nick;
		$this->wx_pic_url = $wx_pic_url;
		return $ret_wx_info;
	}

	/*
     * 通过code获取Access Token
     * @return array {access_token,expires_in,refresh_token,openid,scope}
     */
	private function getOauthAccessToken($code, & $json)
	{
		$this->curl = new Curl();
		$url = self::OAUTH_TOKEN_URL . 'appid=' . $this->wx_appid_info['appid']
			. '&secret=' . $this->wx_appid_info['secret'] . '&code=' . $code
			. '&grant_type=authorization_code';
		$ret = $this->curl->sockopen($url);
		OSS_INFO(
			"wechat oauth token : " . json_encode($ret) . ",code:" . $code
			. ",url: " . $url . " \n"
		);
		if (!$ret) {
			OSS_ERROR(
				"[wechat oauth request fail][" . __CLASS__ . __FUNCTION__
				. __LINE__ . "], wx_login_act_id:" . $this->activityid . "\n"
			);
			return -1;
		}
		$json = json_decode($ret, true);
		if (!$json || $json['errcode'] != 0) {
			OSS_ERROR(
				"[wechat oauth result fail][" . __CLASS__ . __FUNCTION__
				. __LINE__ . "], wx_login_act_id:" . $this->activityid . "\n"
			);
			return -1;
		}
		return 0;
	}

	/**
	 * 获取授权后的用户资料
	 *
	 * @param string $access_token
	 * @param string $openid
	 *
	 * @return array {openid,nickname,sex,province,city,country,headimgurl,privilege}
	 */
	private function getOauthUserinfo($access_token, $openid)
	{
		$this->curl = new Curl();
		$result = $this->curl->sockopen(
			self::OAUTH_USERINFO_URL . 'access_token=' . $access_token
			. '&openid=' . $openid
		);
		OSS_ERROR(
			"[wechat getOauthUserinfo] result: " . json_encode($result) . " \n"
		);
		if ($result) {
			$json = json_decode($result, true);
			if (!$json || !empty($json['errcode'])) {
				$this->error_code = $json['errcode'];
				$this->error_message = $json['errmsg'];
				return false;
			}
			return $json;
		}
		return false;
	}

	/**
	 * 判断syb_id 和 syb_token 登录验证,如果两者不一致则判断qt_uuid 和qt_token
	 *
	 * @para
	 * @ret
	 */
	public function syb_login_check()
	{
		$this->load->logic(
			"syb_account_info", "syb_account_info_instance", "sybsvr"
		);
		$ip_info = $this->get_server_ip_port("syb_bang_info");
		$this->syb_account_info_instance->init(
			$this->uin, $ip_info, $this->time_out
		);
		$syb_id = $_COOKIE['syb_id'];
		$syb_token = $_COOKIE['syb_token'];
		$ret = $this->syb_account_info_instance->syb_login_check(
			$syb_id, $syb_token
		);
		if ($ret['errno'] != 0) {
			OSS_ERROR(
				"syb_login_check err: " . json_encode($ret) . ",syb_id:"
				. $syb_id . ",syb_token:" . $syb_token . " \n"
			);
			//判断qt_uuid 和qt_token
			$this->load->logic(
				"qt_uuid_login", "qt_uuid_login_instance", "sybsvr"
			);
			$this->qt_uuid_login_instance->init(
				$this->uin, $ip_info, $this->time_out
			);
			$qt_uuid = $_COOKIE['qt_uuid'];
			$qt_token = $_COOKIE['qt_token'];
			$ipt_clientip = 0;
			$ret_qt = $this->qt_uuid_login_instance->qt_uuid_login_check(
				$qt_uuid, $qt_token, $ipt_clientip
			);
			if ($ret_qt['errno'] != 0) {
				OSS_ERROR(
					"qt_uuid_login_check err: " . json_encode($ret)
					. ",qt_uuid:" . $qt_uuid . ",qt_token:" . $qt_token . " \n"
				);
				return -1;
			}
			return 0;
		}
		return 0;
	}

	/**
	 * 拉取openid
	 */
	public function get_open_id()
	{
		$acctype = $_GET['acctype'];
		if ($acctype == 1) {
			//$this->login_check();
			//表示qq
			MONITOR_U_START("uin_login_check");
			$ret = $this->uin_login_check();
			MONITOR_U_STOP("uin_login_check", $ret['errno']);
			if ($ret['errno'] != 0) {
				throw new Exception("您没有登录或登录已失效", 90100);
			}
			//表示qq
			$uin = $this->uin;
			$skey = $_COOKIE['skey'];
			return $this->uin_to_openid($uin, $skey);
		} else {
			if ($acctype == 2) {
				//表示wx
				return $this->wx_open_id;
			} else {
				if ($acctype == 3) {
					//syb
					$syb_id = $_COOKIE['syb_id'];
					$svc_type = $_COOKIE['svc_type'];
					$this->load->logic(
						"syb_account_info", "syb_account_info_instance",
						"sybsvr"
					);
					$ip_info = $this->get_server_ip_port("syb_bang_info");
					$this->syb_account_info_instance->init(
						$this->uin, $ip_info, $this->time_out
					);
					$ret
						= $this->syb_account_info_instance->syb_get_bang_account(
						$syb_id
					);
					if ($ret['errno'] != 0) {
						OSS_ERROR(
							"syb_get_bang_account[uin:$this->uin,errno: "
							. $ret['errno'] . ", errinfo: " . $ret['error']
							. ",cmd:" . $ret['cmd'] . ",syb_id:" . $syb_id
							. " \n"
						);
						throw new Exception(
							"系统繁忙,请稍后再试[" . $ret['cmd'] . "|" . $ret['errno']
							. "]", -($ret['errno'] + 93400)
						);
					}
					$accountinfo = $ret['accountinfo'];
					$qqcommid = $accountinfo['qqcommid'];
					$wechatcommid = $accountinfo['wechatcommid'];
					$this->load->logic(
						"account_exchange", "account_exchange_instance",
						"sybsvr"
					);
					$ip_info = $this->get_server_ip_port("syb_im_account");
					$this->account_exchange_instance->init(
						$this->uin, $ip_info, $this->time_out
					);
					if ($svc_type == 1) {
						//qq
						$game_appid = $this->qq_appid_info['appid'];
						$commonid_list[] = $qqcommid;
						$ret_qq
							= $this->account_exchange_instance->batch_commid_to_openid(
							$game_appid, $commonid_list
						);
						if ($ret_qq['errno'] != 0) {
							OSS_ERROR(
								"batch_commid_to_openid[uin:$this->uin,errno: "
								. $ret_qq['errno'] . ", errinfo: "
								. $ret_qq['error'] . ",cmd:" . $ret_qq['cmd']
								. ",game_appid:" . $game_appid . ",qqcommid:"
								. $qqcommid . " \n"
							);
							throw new Exception(
								"系统繁忙,请稍后再试[" . $ret_qq['cmd'] . "|"
								. $ret_qq['errno'] . "]",
								-($ret_qq['errno'] + 93400)
							);
						}
						return $ret_qq['info'][0]['openid'];
					} else {
						if ($svc_type == 2) {
							//wx
							$game_appid = $this->wx_appid_info['appid'];
							$ret_wx
								= $this->account_exchange_instance->unionid_to_openid(
								$game_appid, $wechatcommid
							);
							if ($ret_wx['errno'] != 0) {
								OSS_ERROR(
									"unionid_to_openid[uin:$this->uin,errno: "
									. $ret_wx['errno'] . ", errinfo: "
									. $ret_wx['error'] . ",cmd:"
									. $ret_wx['cmd'] . ",game_appid:"
									. $game_appid . ",wechatcommid:"
									. $wechatcommid . " \n"
								);
								throw new Exception(
									"系统繁忙,请稍后再试[" . $ret_wx['cmd'] . "|"
									. $ret_wx['errno'] . "]",
									-($ret_wx['errno'] + 93400)
								);
							}
							return $ret_wx['openid'];
						} else {
							OSS_ERROR(
								" get svc_type err. svc_type:" . $svc_type
								. " \n"
							);
							throw new Exception("获取svc_type错误", 90301);
						}
					}
				}
			}
		}
	}

	/**
	 * 拉取个人信息，包括昵称和图片url
	 *
	 * @para
	 * @ret
	 */
	public function get_user_info()
	{
		$ret_data['nick'] = '';
		$ret_data['url'] = '';
		$acctype = $_GET['acctype'];
		if ($acctype == 1) {
			//表示qq
			MONITOR_U_START("get_qq_profile");
			$this->load->logic(
				"account_exchange", "account_exchange_instance", "sybsvr"
			);
			$ip_info = $this->get_server_ip_port("syb_im_account");
			$this->account_exchange_instance->init(
				$this->uin, $ip_info, $this->time_out
			);
			$skey = $_COOKIE['skey'];
			$neen_head_pic = 1;
			$dst_uin_list[] = $this->uin;
			$ret = $this->account_exchange_instance->get_qq_profile(
				$this->uin, $skey, $neen_head_pic, $dst_uin_list
			);
			MONITOR_U_STOP("get_qq_profile", $ret['errno']);
			if ($ret['errno'] == 0) {
				$info = $ret['info'];
				$ret_data['nick'] = $info[0]['nick'];
				$ret_data['url'] = $info[0]['pic_url'];
			}
		} else {
			if ($acctype == 2) {
				//表示wx
				$ret_data['nick'] = $this->wx_nick;
				$ret_data['url'] = $this->wx_pic_url;
			} else {
				if ($acctype == 3) {
					//syb
					$syb_id_list[] = $_COOKIE['syb_id'];
					MONITOR_U_START("syb_get_account_info");
					$this->load->logic(
						"syb_account_info", "syb_account_info_instance",
						"sybsvr"
					);
					$ip_info = $this->get_server_ip_port("syb_bang_info");
					$this->syb_account_info_instance->init(
						$this->uin, $ip_info, $this->time_out
					);
					$ret
						= $this->syb_account_info_instance->syb_get_account_info(
						$syb_id_list
					);
					MONITOR_U_STOP("syb_login_check", $ret['errno']);
					if ($ret['errno'] == 0) {
						$userinfolist = $ret['userinfolist'][0];
						$ret_data['nick'] = $userinfolist['nickname'];
						$ret_data['url'] = $userinfolist['face'];
					}
				}
			}
		}
		return $ret_data;
	}

	/**
	 * 获取参数
	 */
	public function get_user_para()
	{
		// 判断链接来源
		if (!$this->is_referer_valid()) {
			OSS_ERROR("[IsRefererValid error][uin:" . $this->uin . "]\n");
			throw new Exception ('非法请求', 97001);
		}
		//获取活动的appid、appkey, 同时验证act_id 是否有效
		$this->get_appid();
		//验证登录
		$this->web_login_check();
		//$uid['type'] = 2;  2 表示qq; 1 表示wx
		$uid['qq_commid'] = '';
		$uid['qq_uin'] = 0;
		$uid['qq_skey'] = '';
		$uid['qq_appid'] = '';
		$uid['wx_openid'] = '';
		$uid['wx_appid'] = '';
		$uid['wx_unionid'] = '';
		$this->load->base("input", "input");
		$acctype = $this->input->get("acctype");
		/*
		acctype
		1：表示qq
		2：表示wx
		3：表示syb
		*/
		if ($acctype == 1) {
			$uid['type'] = 2;
			$uid['qq_uin'] = $this->uin;
		} else {
			if ($acctype == 2) {
				$uid['type'] = 1;
				$uid['wx_unionid'] = $this->wx_union_id;
			} else {
				if ($acctype == 3) {
					//syb
					$syb_id = $_COOKIE['syb_id'];
					$svc_type = $_COOKIE['svc_type'];
					$this->load->logic(
						"syb_account_info", "syb_account_info_instance",
						"sybsvr"
					);
					$ip_info = $this->get_server_ip_port("syb_bang_info");
					$this->syb_account_info_instance->init(
						$this->uin, $ip_info, $this->time_out
					);
					$ret
						= $this->syb_account_info_instance->syb_get_bang_account(
						$syb_id
					);
					if ($ret['errno'] != 0) {
						OSS_ERROR(
							"syb_get_bang_account[uin:$this->uin,errno: "
							. $ret['errno'] . ", errinfo: " . $ret['error']
							. ",cmd:" . $ret['cmd'] . ",syb_id:" . $syb_id
							. " \n"
						);
						throw new Exception(
							"系统繁忙,请稍后再试[" . $ret['cmd'] . "|" . $ret['errno']
							. "]", -($ret['errno'] + 93400)
						);
					}
					$accountinfo = $ret['accountinfo'];
					$qqcommid = $accountinfo['qqcommid'];
					$wechatcommid = $accountinfo['wechatcommid'];
					$qquin = $accountinfo['qquin'];
					$svc_type = $_COOKIE['svc_type'];
					if ($svc_type == 1) {
						$uid['type'] = 2;
						$uid['qq_uin'] = $qquin;
					} else {
						if ($svc_type == 2) {
							$uid['type'] = 1;
							$uid['wx_unionid'] = $wechatcommid;
						}
					}
				} else {
					$uid['type'] = 2;
					$uid['qq_uin'] = $this->uin;
				}
			}
		}
		return $uid;
	}
}

