<?php
namespace syb\wup;
class ResponsePacket extends c_struct
{
	public $iVersion;
	public $cPacketType;
	public $iRequestId;
	public $iMessageType;
	public $iRet;
	public $sBuffer;
	public $status;
	public $sResultDesc;

	public function __clone()
	{
		$this->iVersion = clone $this->iVersion;
		$this->cPacketType = clone $this->cPacketType;
		$this->iRequestId = clone $this->iRequestId;
		$this->iMessageType = clone $this->iMessageType;
		$this->iRet = clone $this->iRet;
		$this->sBuffer = clone $this->sBuffer;
		$this->status = clone $this->status;
		$this->sResultDesc = clone $this->sResultDesc;
	}

	public function __construct()
	{
		$this->iVersion = new  c_short;
		$this->cPacketType = new  c_char;
		$this->iRequestId = new  c_int;
		$this->iMessageType = new  c_int;
		$this->iRet = new  c_int;
		$this->sBuffer = new  c_vector (new c_char);
		$this->status = new  c_map (new c_string, new c_string);
		$this->sResultDesc = new  c_string;
	}

	public function get_class_name()
	{
		return "taf.ResponsePacket";
	}

	public function writeTo(&$_out, $tag = 0)
	{
		$this->iVersion->write($_out, 1);
		$this->cPacketType->write($_out, 2);
		$this->iRequestId->write($_out, 3);
		$this->iMessageType->write($_out, 4);
		$this->iRet->write($_out, 5);
		$this->sBuffer->write($_out, 6);
		$this->status->write($_out, 7);
		$this->sResultDesc->write($_out, 8);
	}

	public function readFrom(&$_in, $tag = 0, $isRequire = TRUE)
	{
		$this->iVersion->read($_in, 1, TRUE);
		$this->cPacketType->read($_in, 2, TRUE);
		$this->iRequestId->read($_in, 3, TRUE);
		$this->iMessageType->read($_in, 4, TRUE);
		$this->iRet->read($_in, 5, TRUE);
		$this->sBuffer->read($_in, 6, TRUE);
		$this->status->read($_in, 7, TRUE);
		$this->sResultDesc->read($_in, 8, FALSE);
		$this->_skip_struct($_in);
	}
}