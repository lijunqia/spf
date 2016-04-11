<?php
namespace spf\Network\Protocol;
class Protobuf extends Base
{
	const ST_FINISH = 1; //完成，进入处理流程
	const ST_WAIT = 2; //等待数据
	const ST_ERROR = 3; //错误，丢弃此包
	protected $requests;
	public $web_stx;
	public $web_etx;

	public function on_receive($serv, $fd, $fromId, $data)
	{
		$ret = $this->check_buffer($fd, $data);// 检查buffer
		switch ($ret) {
		case self::ST_ERROR:
			return true;           // 错误的请求
		case self::ST_WAIT:
			return true;          // 数据不完整，继续等待
		default:
			break;                 // 完整数据
		}
		$request = $this->requests[$fd];
		$this->on_request($serv, $fd, $request);
		unset($this->requests[$fd]);
	}

	public function check_buffer($fd, $data)
	{
		//新的连接
		if (!isset($this->requests[$fd])) {
			$web_stx = substr($data, 0, 1); // 获取起始符
			if (pack("C", $this->web_stx) !== $web_stx) {
				return self::ST_ERROR; // 错误的开始符
			}
			// buffer解析
			$cmd = substr($data, 1, 4); // 获取命令号
			$cmd_arr = unpack('Ncmd', $cmd);
			$cmd = $cmd_arr['cmd'];
			$seq = substr($data, 5, 4);
			$seq = unpack('Nseq', $seq);
			$seq = $seq['seq'];
			$head_len = substr($data, 9, 4);
			$head_len = unpack('Nlen', $head_len);
			$head_len = $head_len['len'];
			$body_len = substr($data, 13, 4);
			$body_len = unpack('Nlen', $body_len);
			$body_len = $body_len['len'];
			$total_len = 18 + $head_len + $body_len;
			if (strlen($data) > $total_len) {
				return self::ST_ERROR; // 无效数据包，弃之
			}
			$this->requests[$fd] = array(
				'cmd' => $cmd, 'seq' => $seq, 'headLen' => $head_len, 'bodyLen' => $body_len, 'length' => $total_len, 'buffer' => $data,
			);
		} else {  // 大包数据需要合并数据，默认超过8k需要走此逻辑
			$this->requests[$fd]['buffer'] .= $data;
		}
		// 检查包的大小
		$dataLength = strlen($this->requests[$fd]['buffer']);
		if ($dataLength > $this->requests[$fd]['length']) {
			// 无效数据包，弃之
			return self::ST_ERROR;
		} elseif ($dataLength < $this->requests[$fd]['length']) {
			return self::ST_WAIT; // 数据包不完整，继续等待
		}
		$webEtx = substr($data, -1);   // 获取结束符
		if (pack("C", $this->web_etx) !== $webEtx) {
			return self::ST_ERROR;
		}
		return self::ST_FINISH; // 数据包完整
	}

	public function on_close($server, $fd, $fromId)
	{
		unset($this->requests[$fd]);
	}
}