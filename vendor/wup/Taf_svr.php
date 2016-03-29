<?php
namespace syb\wup;
class Taf_svr
{
	/**
	 * 创建socket连接
	 *
	 * @param string $host     主机名/ip
	 * @param int    $port     端口
	 * @param int    $sentimeo 发送超时时间(秒)
	 * @param int    $rcvtimeo 读取超时时间(秒)
	 * @throws Exception
	 */
	public function socket_connect($host, $port, $sentimeo = 5, $rcvtimeo = 5)
	{
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if (!$socket) {
			$err = socket_last_error($socket);
			throw new \Exception("socket_create() error [ $err ] : " . socket_strerror($err));
		}
		socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array("sec" => $sentimeo, "usec" => 0));
		socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => $rcvtimeo, "usec" => 0));
		if (!@socket_connect($socket, $host, $port)) {
			$err = socket_last_error($socket);
			throw new \Exception("socket_connect() error [ $err ] : " . socket_strerror($err));
		}
		return $socket;
	}

	/**
	 * 获取 servant 配置
	 *
	 * @param string $servantName
	 * @throws Exception
	 * @return array
	 */
	public function get_servant_cfg($servantName)
	{
		$cfg = array(
			'MiniGame.GiftServiceSvr.GiftQueryServantObj' => array(
				array('ip' => '10.219.9.24', 'port' => 10060),
			),
		);
		if (array_key_exists($servantName, $cfg)) {
			return $cfg[ $servantName ];
		} else {
			throw new Exception("servant cfg error : key [ $servantName ] doesn't exists");
		}
	}

	/**
	 * 调用服务
	 *
	 * @param string  $servantName 调用servant名
	 * @param string  $funcName    调用方法名
	 * @param array   $params      输入参数
	 * @param array   $out         输出参数
	 * @param boolean $resp        是否获取响应结果，默认true， 为false时发送请求后直接返回
	 */
	public function request($servantName, $funcName, $params = array(), &$out = array(), $resp = TRUE)
	{
		$packet = new wup_unipacket();
		static $id = 1;
		$id++;
		$packet->setVersion(3);
		$packet->setRequestId($id);
		$packet->setServantName($servantName);
		$packet->setFuncName($funcName);
		if ($params) {
			foreach ($params as $key => $value) {
				$packet->put($key, $value);
			}
		}
		$packet->_encode($sendBuffer);
		$hosts = $this->get_servant_cfg($servantName);
		$host = $hosts[ rand(0, count($hosts) - 1) ];
		$socket = $this->socket_connect($host['ip'], $host['port']);
		if (!@socket_write($socket, $sendBuffer, strlen($sendBuffer))) {
			$err = socket_last_error($socket);
			throw new Exception("socket_write() error [ $err ] : " . socket_strerror($err));
		}
		if (!$resp) {
			socket_close($socket);
			return TRUE;
		}
		$respBuffer = socket_read($socket, 4);
		$len = ord($respBuffer[0]);
		$len = $len * 256 + ord($respBuffer[1]);
		$len = $len * 256 + ord($respBuffer[2]);
		$len = $len * 256 + ord($respBuffer[3]);
		$rlen = strlen($respBuffer);
		while ($len - $rlen > 0) {
			$b = socket_read($socket, $len - $rlen);
			$respBuffer .= $b;
			$rlen += strlen($b);
		}
		socket_close($socket);
		$wupResp = new wup_unipacket();
		$wupResp->_decode($respBuffer);
		if ($wupResp->getResultCode() == 0) {
			foreach ($out as $key => $val) {
				$wupResp->get($key, $val);
				$out[ $key ] = $this->parseObj($val);
			}
		} else {
			throw new Exception($wupResp->getResultDesc() . '(code:' . $wupResp->getResultCode() . ')');
		}
	}

	/**
	 * 转换jce对象
	 */
	function parseObj($obj)
	{
		if (!$obj) return $obj;
		if ($obj->is_base_datatype()) {
			if ($obj->get_class_name() == 'char' && ord($obj->val) == 1) {
				return 1;
			}
			return $obj->val;
		} else {
			$classname = ' ' . $obj->get_class_name();
			//c_vector类型
			if (strpos($classname, 'list<') == 1) {
				$ret = array();
				foreach ($obj->get_val() as $el) {
					$ret[] = $this->parseObj($el);
				}
				return $ret;
			} else if (strpos($classname, 'map<') == 1) {
				//c_map类型
				$ret = array();
				foreach ($obj->get_map() as $map) {
					$keyname = '';
					$val = NULL;
					foreach ($map as $key => $value) {
						if ($key == 'key') {
							$keyname = $this->parseObj($value);
						} else {
							$val = $this->parseObj($value);
						}
					}
					$ret[ $keyname ] = $val;
				}
				return $ret;
			} else {
				//其它都为c_struct类型
				$ret = array();
				$vars = get_class_vars(get_class($obj));
				foreach ($vars as $key => $value) {
					if (!$key) continue;
					$ret[ $key ] = $this->parseObj($obj->$key);
				}
				return $ret;
			}
		}
	}

	/**
	 * 格式化二进制为16进制格式显示
	 *
	 * @param string $str
	 * @param string $break
	 */
	public function bin2hex($str, $break = "\n")
	{
		return wordwrap(wordwrap(bin2hex($str), 2, " ", TRUE), 48, $break, TRUE);
	}
}