<?php
//**********************************************************
// File name: UdpLogger.class.php
// Class name: UDP日志记录类
// Create date: 2012/04/05
// Update date: 2012/04/05
// Author: parkerzhu
// Description: Udp日志记录类
//**********************************************************
namespace syb\oss\Network;
use oss\StructBase;
class stLogPlat extends StructBase
{
    protected $strKey;
    protected $cLogType;
    protected $strIdName;
    protected $strPlatName;
    protected $strDomName;
    protected $strExeName;
    protected $strUserName;
    protected $ulUin;
    protected $strTime;
    protected $strPlatIp;
    protected $strClientIp;
    protected $ulCmdKey;
    protected $cSuccFlag;
    protected $strBtime;
    protected $strEtime;
    protected $strOtherinfo;

    public function __construct()
    {
        $this->strKey = new OString(3);
        $this->cLogType = new OUChar();
        $this->strIdName = new OString(110);
        $this->strPlatName = new OString(8);
        $this->strDomName = new OString(20);
        $this->strExeName = new OString(30);
        $this->strUserName = new OString(10);
        $this->ulUin = new OBEULong();
        $this->strTime = new OString(14);
        $this->strPlatIp = new OString(15);
        $this->strClientIp = new OString(15);
        $this->ulCmdKey = new OLEULong();
        $this->cSuccFlag = new OChar();
        $this->strBtime = new OString(27);
        $this->strEtime = new OString(27);
        $this->strOtherinfo = new OString();
    }
}

class stTLOGBinTime extends StructBase
{
    protected $ulSec;
    protected $ulUsec;

    public function __construct()
    {
        $this->ulSec = new OBEULong();
        $this->ulUsec = new OBEULong();
    }
}

class stTLOGBinHead extends StructBase
{
    protected $cVer;
    protected $cMagic;
    protected $cCmd;
    protected $cHeadLen;
    protected $ulBodyLen;
    protected $stBintime;
    protected $ulSeq;
    protected $ulId;
    protected $ulCls;
    protected $ulType;
    protected $ulBodyVer;
    protected $ulCheckSum;

    public function __construct()
    {
        $this->cVer = new OUChar();
        $this->cMagic = new OUChar();
        $this->cCmd = new OUChar();
        $this->cHeadLen = new OUChar();
        $this->ulBodyLen = new OBEULong();
        $this->stBintime = new stTLOGBinTime();
        $this->ulSeq = new OBEULong();
        $this->ulId = new OBEULong();
        $this->ulCls = new OBEULong();
        $this->ulType = new OBEULong();
        $this->ulBodyVer = new OBEULong();
        $this->ulCheckSum = new OBEULong();
    }
}

class stTLOGPack extends StructBase
{
    protected $stHead;
    protected $strBody;

    public function __construct()
    {
        $this->stHead = new stTLOGBinHead();
        $this->strBody = new OString();
    }
}

/**
 *
 *
 */


class UdpLogger
{
    const TLOGBIN_VER = 0x01;
    const TLOGBIN_MAGIC = 0x55;

    public function __construct($ip, $port)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->logType = 0x00;
        $this->platName = "";
    }

    public function sendLog($priority, $msg)
    {
        $tlog = new stTLOGPack();

        $tlog->stHead->cVer = TLOGBIN_VER;
        $tlog->stHead->cMagic = TLOGBIN_MAGIC;
        $tlog->stHead->cCmd = 0x00;
        $tlog->stHead->cHeadLen = strlen($tlog->stHead);
        $tlog->stHead->ulBodyLen = strlen($msg);
        list($usec, $sec) = explode(" ", microtime());
        $tlog->stHead->stBinTime->ulSec = floatval($sec);
        $tlog->stHead->stBinTime->ulUsec = floatval($usec);
        $tlog->stHead->ulSeq = 0x00;
        $tlog->stHead->ulId = 0x00;
        $tlog->stHead->ulCls = 0x00;
        $tlog->stHead->ulType = 0x00;
        $tlog->stHead->ulBodyVer = 0x00;
        $tlog->stHead->ulCheckSum = 0x00;
        $tlog->strBody = $msg;

        // send to server
        $udpDgram = new UdpDgram();
        $udpDgram->Open($this->ip, $this->port);
        //$udpDgram->SetSockOption(0 /* SOL_IP */,
        //                         3 /* IP_TOS */,
        //                         96);

        $udpDgram->SendTo($tlog, strlen($tlog), $this->ip, $this->port);
        return 0;
    }

    public function logPlat($ulCmd, $cSuccFlag, $ulUin, $strUsername, $strOthers)
    {
        $logplat = new stLogPlat();

        $logplat->cLogType = $this->logType;
        $logplat->ulCmdKey = $ulCmd;
        $logplat->cSuccFlag = $cSuccFlag;
        $logplat->ulUin = $ulUin;
        $logplat->strPlatName = $this->platName;
        if(!empty($strUsername)) $logplat->strUserName = $strUsername;
        if(empty($strOthers)) $strOthers = "NULL";
        $logplat->strOtherinfo = $strOthers . "\0";
        return $this->logBase($logplat);
    }

    public function logComm($ulId, $strBegintime, $strEndtime, $strOthers)
    {
        $logplat = new stLogPlat();

        $logplat->cLogType = $this->logType;
        $logplat->ulCmdKey = $ulId;
        $logplat->strPlatName = $this->platName;
        $logplat->strBtime = $strBegintime;
        $logplat->strEtime = $strEndtime;
        $logplat->strOtherinfo = $strOthers . "\0";
        $logplat->cSuccFlag = $this->succFlag;
        return $this->logBase($logplat);
    }

    public function setLogType($type)
    {
        $this->logType = $type;
    }

    public function getLogType()
    {
        return $this->logType;
    }

    public function setPlatName($platName)
    {
        $this->platName = $platName;
    }

    public function getPlatName()
    {
        return $this->platName;
    }

    public function setSuccFlag($succFlag)
    {
        $this->succFlag = $succFlag;
    }

    public function logBase(&$logplat)
    {
        // set key
        $logplat->strKey = '@@';

        // set idname
        $logplat->strIdName = $_SERVER['PHP_SELF'];

        // set exename
        $logplat->strExeName = basename($_SERVER['PHP_SELF']);

        // set domname
        $servername = $_SERVER['SERVER_NAME'];
        $remote_addr_n = @inet_pton($servername);
        if(empty($servername))
        {
            $servername = "admin.ied.com";
        }
        else if($remote_addr_n)
        {
            $servername = "internal";
        }
        $logplat->strDomName = $servername;

        // set time
        $logplat->strTime = date("md.H:i:s");

        // set client ip
        $remoteaddr = $_SERVER['REMOTE_ADDR'];
        if(empty($remoteaddr)) $remoteaddr = "127.0.0.1";
        $logplat->strClientIp = $remoteaddr;

        // set uin
        $logplat->ulUin = floatval(substr($_COOKIE['uin'], 1));

        // send to server
        $udpDgram = new UdpDgram();
        $udpDgram->Open();
        //$udpDgram->SetSockOption(0 /* SOL_IP */,
        //                         3 /* IP_TOS */,
        //                         96);

        $udpDgram->SendTo($logplat, strlen($logplat), $this->ip, $this->port);
        return 0;
    }

    private $ip;
    private $port;
    private $logType;
    private $platName;
    private $succFlag;
}
