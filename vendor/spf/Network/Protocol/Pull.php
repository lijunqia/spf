<?php
namespace spf\Network\Protocol;

use spf\Network\Server\Base;

/**
 * Created by JetBrains PhpStorm.
 * User: jimmyszhou
 * Date: 15-3-18
 * Time: 下午3:31
 * To change this template use File | Settings | File Templates.
 * 主动通过消息通道进行拉取的server,包括原生msg_queue和zmq
 */
class PullServer extends Base
{
	private $handles;
	private $fds;
	private $time_jobs;
	/**
	 * 监听多个不同的IPC
	 * @param $conf = array('projectName' => array('timeInterval' => 100,'res' => $obj))
	 *
	 */
	public function __construct($conf)
	{
		if (is_array($conf)) {
			foreach ($conf as $k => $v) {
				$this->time_jobs[(int)$v['timeInterval']] = $v['res'];
			}
		}
	}

	public function init()
	{
	}

	public function on_start($serv, $workerId)
	{
		$this->time_poll($serv);
	}

	/**
	 * 启动定时期,监听IPC通道
	 *
	 * @param $serv
	 * @param $interval
	 */
	public function on_timer($serv, $interval)
	{
		$res = $this->time_jobs[$interval];
		if ($data = $res->recv()) {
			$this->on_receive($serv, $res->key, $res->key, $data);
		}
	}

	//设置事件接口
	public function set_handle($fun, $type = 'def')
	{
		$this->handles[$type] = $fun;
	}

	public function get_handle($type = 'def')
	{
		return isset($this->handles[$type]) ? $this->handles[$type] : null;
	}

	//要监听的fd
	public function set_fd($fd, $type = 'def')
	{
		$this->fds[$type] = $fd;
	}

	public function get_fd($type = 'def')
	{
		return isset($this->fds[$type]) ? $this->fds[$type] : -1;
	}

	//要监听的使用定时器
	private function time_poll($serv)
	{
		if (is_array($this->time_jobs)) {
			foreach ($this->time_jobs as $k => $v) {
				$serv->addtimer($k);
			}
		}
	}

	public function pepoll($opt = 'ADD', $type = 'def', $event = SWOOLE_EVENT_READ)
	{
		$fd = $this->get_fd($type);
		if ($fd === -1) {
			return false;
		}
		$read_handle = $this->get_handle('read');
		$write_handle = $this->get_handle('write');
		if ($write_handle === null && $read_handle === null) {
			return false;
		}
		switch ($opt) {
		case 'ADD' : {
			$ret = \swoole_event_add($fd, $read_handle, $write_handle, $event);
			break;
		}
		case 'MOD' : {
			$ret = \swoole_event_set($fd, $read_handle, $write_handle, $event);
			break;
		}
		case 'DEL' : {
			$ret = \swoole_event_del($fd, $read_handle, $write_handle, $event);
			break;
		}
		default : {
			return false;
			break;
		}
		}
		return $ret;
	}
}
