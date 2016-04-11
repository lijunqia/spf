<?php
/**
 * Phplib
 * An open source application development framework.
 *
 * @package       Phplib
 * @subpackage    Logic
 * @author        libearxiong
 * @version       Version 1.0
 * @copyright     Copyright (c) 2013 - , Tencent, Inc.
 * @filesource
 *                大小限制：
 *                URL：最多256个字节
 *                API：最多32个字节
 *                STRING：最多1024个字节
 *                API个数：支持最多64个API接口
 */
namespace syb\logic;
/*
 * ------------------------------------------------------
 *  Define val of MONITOR_CONFIG_NEED_REPORT
 * ------------------------------------------------------
 */
defined("MONITOR_CONFIG_NEED_REPORT") || define("MONITOR_CONFIG_NEED_REPORT", TRUE);

/**
 * Monitor Class
 * This class contains functions that does with monitor
 *
 * @package       Phplib
 * @subpackage    Logic
 * @author        libearxiong
 * @version       Version 1.0
 * @copyright     Copyright (c) 2013 - , Tencent, Inc.
 */
class Monitor
{
	/**
	 * The const is the ip of server
	 *
	 * @var string
	 */
	const ip = "10.239.169.244";
	/**
	 * The const is the port of server
	 *
	 * @var int
	 */
	const port = 40000;
	/**
	 * The const is MONITOR_NOT_SEND
	 *
	 * @var int
	 */
	const MONITOR_NOT_SEND = 1;
	/**
	 * The const is MONITOR_SEND_FAIL
	 *
	 * @var int
	 */
	const MONITOR_SEND_FAIL = -1;
	/**
	 * The const is MONITOR_SEND_SUCCESS
	 *
	 * @var int
	 */
	const MONITOR_SEND_SUCCESS = 0;
	/**
	 * The var to store url
	 *
	 * @var string
	 */
	private $url = NULL;
	/**
	 * The var to store domain
	 *
	 * @var string
	 */
	private $domain = NULL;
	/**
	 * The var to store rate
	 *
	 * @var int
	 */
	private $rate = 100;
	/**
	 * The var to store the data of monitor startly
	 *
	 * @var array
	 */
	private $u_start = array();
	/**
	 * The var to store the data of monitor endly
	 *
	 * @var array
	 */
	private $u_stop = array();
	/**
	 * The var to store status code
	 *
	 * @var array
	 */
	private $u_status = array();
	/**
	 * The var to store the timestamp of start
	 *
	 * @var int
	 */
	private $start = 0;
	/**
	 * The var to store the timestamp of end
	 *
	 * @var int
	 */
	private $stop = 0;
	/**
	 * The var to store the status of project
	 *
	 * @var int
	 */
	private $status = 0;
	/**
	 * The var to store all data to monitor
	 *
	 * @var array
	 */
	private $data = array();
	/**
	 * The var to store object for this base class
	 *
	 * @var object
	 */
	private static $instance = NULL; //唯一实例

	/**
	 * The __construct function
	 * This function is construct
	 *
	 * @access    private
	 * @param void
	 * @return    void
	 */
	private function __construct()
	{
	}

	/**
	 * The __clone function
	 * This function is clone
	 *
	 * @access    private
	 * @param void
	 * @return    void
	 */
	private function __clone()
	{
	}

	/**
	 * The instance function
	 * This function is to get the object of monitor class
	 *
	 * @access    static
	 * @param void
	 * @return    object
	 */
	static function instance()
	{
		if (self::$instance == NULL) {
			self::$instance = new Monitor();
		}
		return self::$instance;
	}

	/**
	 * The p_start function
	 * This function is to start monitor
	 *
	 * @access    public
	 * @param void
	 * @return    the timestamp of start
	 */
	public function p_start()
	{
		$this->start = $this->get_now();
	}

	/**
	 * The p_stop function
	 * This function is to stop monitor
	 *
	 * @access    public
	 * @param void
	 * @return    the timestamp of stop
	 */
	public function p_stop($status)
	{
		$rate = mt_rand() % 100;
		if ($rate > $this->rate) {
			return array('code' => MONITOR_NOT_SEND, 'msg' => 'This rand ' . $rate . ' is less than you set ' . $this->rate);
		}
		if (MONITOR_CONFIG_NEED_REPORT == FALSE) {
			return array('code' => MONITOR_NOT_SEND, 'msg' => 'Cgi setting not report!Please see define(MONITOR_CONFIG_NEED_REPORT)');
		}
		try {
			$this->stop = $this->get_now();
			$this->status = $status;
			$this->init_url();
			$this->init_domain();
			$this->validate();
			return $this->send();
		} catch (\Exception $e) {
			return array('code' => MONITOR_SEND_FAIL, 'msg' => $e->getMessage());
		}
	}

	/**
	 * The u_start function
	 * This function is to start unit monitor
	 *
	 * @access    public
	 * @param key
	 * @return    the timestamp of uinit start
	 */
	public function u_start($key)
	{
		if ($key == 'P') {
			return;
		}
		$this->u_start[ $key ] = $this->get_now();
	}

	/**
	 * The u_stop function
	 * This function is to stop unit monitor
	 *
	 * @access    public
	 * @param key , status
	 * @return    the timestamp of uinit stop
	 */
	public function u_stop($key, $status)
	{
		if ($key == 'P') {
			return;
		}
		if (!isset($this->u_start[ $key ])) {
			return;
		}
		$this->u_stop[ $key ] = $this->get_now();
		$this->u_status[ $key ] = $status;
	}

	/**
	 * The get_url function
	 * This function is to get url
	 *
	 * @access    public
	 * @param void
	 * @return    url
	 */
	public function get_url()
	{
		return $this->url;
	}

	/**
	 * The get_domain function
	 * This function is to get domain
	 *
	 * @access    public
	 * @param void
	 * @return    domain
	 */
	public function get_domain()
	{
		return $this->domain;
	}

	/**
	 * The get_private_params function
	 * This function is to get private params
	 *
	 * @access    public
	 * @param void
	 * @return    the private params
	 */
	public function get_private_params()
	{
		$params = array();
		$params['url'] = $this->url;
		$params['domain'] = $this->domain;
		$params['rate'] = $this->rate;
		$params['u_start'] = $this->u_start;
		$params['u_stop'] = $this->u_stop;
		$params['u_status'] = $this->u_status;
		$params['start'] = $this->start;
		$params['stop'] = $this->stop;
		$params['status'] = $this->status;
		$params['data'] = $this->data;
		$params['string'] = $this->to_string();
		return $params;
	}

	/**
	 * The set_rate function
	 * This function is to set rate
	 *
	 * @access    public
	 * @param rate
	 * @return    the rate
	 */
	public function set_rate($rate)
	{
		$this->rate = $rate;
	}

	/**
	 * The validate function
	 * This function is to validate the availability of the params
	 *
	 * @access    private
	 * @param void
	 * @return bool
	 */
	private function validate()
	{
		if (empty($this->url)) {
			throw new \Exception("url is null，please check your program!");
		}
		if (empty($this->domain)) {
			throw new \Exception("domain is null，please check your program!");
		}
		if ($this->rate > 100 || $this->rate <= 0) {
			throw new \Exception("rate is error，you have set " . $this->rate . " !please check your program!");
		}
		$p_timestamp = $this->stop - $this->start;
		if ($p_timestamp <= 0) {
			throw new \Exception("Monitor data error.The program need timestamp is $p_timestamp");
		}
		$this->data[] = array('P', $this->status, $this->format_timestamp($p_timestamp));
		foreach ($this->u_start as $key => $value) {
			$u_timestamp = $this->u_stop[ $key ] - $this->u_start[ $key ];
			if ($u_timestamp > 0) {
				$this->data[] = array($key, $this->u_status[ $key ], $this->format_timestamp($u_timestamp));
			}
		}
	}

	/**
	 * The to_string function
	 * This function is to convert the array to string
	 *
	 * @access    private
	 * @param void
	 * @return string
	 * @example   $data:
	 * (
	 * ("apiname"=>"*","retcode"=>203,"exectime"=>600 ),
	 * ("apiname"=>"query_jifen","retcode"=>203,"exectime"=>200 ),
	 * ("apiname"=>"mg_gw","retcode"=>203,"exectime"=>200 ),
	 * ("apiname"=>"idip","retcode"=>203,"exectime"=>200 ),
	 * ...
	 * )
	 * return:
	 * qt/phptest/kavenma/monitor/test_agent.php&R=100&P=5|240&api1=5|240
	 */
	private function to_string()
	{
		$array = array();
		$array[] = $this->domain . $this->url;
		$array[] = 'R=' . $this->rate;
		foreach ($this->data as $d) {
			$array[] = $d[0] . '=' . $d[1] . '|' . $d[2];
		}
		return implode('&', $array) . "\n";
	}

	/**
	 * The init_url function
	 * This function is to set the url by developer
	 *
	 * @access    public
	 * @param url
	 * @return url
	 */
	public function init_url($url = '')
	{
		if ($url) {
			$this->url = $url;
			return;
		}
		if ($this->url) {
			return;
		} else {
			if (!preg_match('/\.php$/', $_SERVER['SCRIPT_NAME'])) {
				if (function_exists("OSS_ERROR")) {
					OSS_ERROR("[CGI url is illegal][monitor url:" . $_SERVER['SCRIPT_NAME'] . "][SCRIPT_NAME:" . $_SERVER['SCRIPT_NAME'] . "][PHP_SELF:" . $_SERVER['PHP_SELF'] . "][REQUEST_URI:" . $_SERVER['REQUEST_URI'] . "]\n");
				}
				throw new \Exception("CGI url is illegal");
			}
			$this->url = $_SERVER['SCRIPT_NAME'];
		}
	}

	/**
	 * The init_domain function
	 * This function is to set domain url by developer
	 *
	 * @access    public
	 * @param domain
	 * @return domain
	 */
	public function init_domain($domain = '')
	{
		if ($domain) {
			$this->domain = $domain;
		}
		if ($this->domain) {
			return;
		} else {
			$d = explode(".", $_SERVER["SERVER_NAME"]);
			$this->domain = implode(".", array_slice($d, 0, count($d) - 2));
		}
	}

	/**
	 * The get_now function
	 * This function is to get the now timestamp
	 *
	 * @access    private
	 * @param void
	 * @return the now timestamp
	 */
	private function get_now()
	{
		return microtime(TRUE);
	}

	/**
	 * The format_timestamp function
	 * This function is to format the timestamp
	 *
	 * @access    private
	 * @param timestamp
	 * @return the format timestamp
	 */
	private function format_timestamp($time)
	{
		return round($time, 6) * 1000000;
	}

	/**
	 * The send function
	 * This function is to send params to the monitor server
	 *
	 * @access    private
	 * @param void
	 * @return bool
	 */
	private function send()
	{
		//  如果是公司安全扫描会带上http头："Tencent-LeakScan: TST(Tencent Security Team)"
		//  由于这些扫描会导致误告警，因此忽略这些请求
		if ($_SERVER['HTTP_TENCENT_LEAKSCAN'] === 'TST(Tencent Security Team)') {
			return 0;
		}
		$string = $this->to_string();
		try {
			//	如果没有加载osslib，这里肯定报错，目前我们的开发都依赖osslib，所以必须加载
			$udp = new UdpDgram();
			$udp->sendto($string, strlen($string), self::ip, self::port);
			return MONITOR_SEND_SUCCESS;
		} catch (\Exception $e) {
			return array('code' => MONITOR_SEND_FAIL, 'msg' => $e->getMessage());
		}
	}

	static function ucall($key, $callable)
	{
		$monitor = Monitor::instance();
		$monitor->u_start($key);
		$status = $callable();
		$monitor->u_stop($key,$status);
		return $status;
	}

	static function pcall($key, $callable)
	{
		$monitor = Monitor::instance();
		$monitor->p_start();
		$status = $callable();
		$monitor->p_stop($key,$status);
		return $status;
	}
	/**
	 * @desc 质量效率视图上报
	 * @param string $begin_time
	 * @param string $end_time
	 * @param string $id
	 * @param string $$result
	 * @return
	 */
	static function oss_event($begin_time, $end_time, $id, $result)
	{
		$result = '0x' . sprintf('%x', $result);
		$msg = "$begin_time || $end_time || $id || $result";
		$alarm_svr_ip = '172.16.195.235';
		$alarm_svr_port = 6666;
		//return SocketAPI::udpPackage($alarm_svr_ip, $alarm_svr_port, $msg, $out_buf, $err_msg);
		//udp report without recv
		$inBufLen = strlen($msg);
		$udpDgram = new \syb\oss\Network\UdpDgram();
		$udpDgram->Open($alarm_svr_ip, $alarm_svr_port);
		$ret = $udpDgram->SendTo($msg, $inBufLen, $alarm_svr_ip, $alarm_svr_port);
		if ($ret != $inBufLen) {
			unset($udpDgram);
		}
		return $ret;
	}
}
