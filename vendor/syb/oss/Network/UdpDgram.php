<?php
//**********************************************************
// File name: UdpDgram.class.php
// Class name: UdpDgram
// Create date: 2011/3/28
// Update date: 2011/3/28
// Author: parkerzhu
// Description: UDP包装类
//**********************************************************

//require_once("Socket.class.php");
namespace syb\oss\Network;
/**
 * UdpDgram类，继承 Socket类，实现UDP发包和收包
 * @note 由于PHP的自动垃圾收集机制，请在使用完后及时调用unset，以释放socket句柄
 * @author parkerzhu
 *
 */
class UdpDgram extends Socket
{
    /**
     * @desc 构造函数，初始化Socket父类为SOCK_DGRAM类型
     */
    //public function __construct($iFamily = AF_INET)
    public function __construct(array $params = array('iFamily' => AF_INET))
    {
        $params['iType'] = SOCK_DGRAM;
        parent::__construct($params);
    }

    /**
     * @desc 打开套接字，设置为非阻塞
     * @see Socket::Open()
     */
    public function Open($sAddr = "0.0.0.0", $iPort = 0, $bReuseAddr = true)
    {
        if (parent::Open($sAddr, $iPort, $bReuseAddr) == -1
            || $this->SetBlock(false) == -1
        ) {
            $this->Close();
            return -1;
        }
        return 0;
    }

    /**
     * @desc 发送一个UDP数据包，非阻塞
     * @param inBuf 发送缓冲
     * @param maxLen 发送字节数
     * @param sAddr 对端地址
     * @param iPort 对端端口
     * @param iFlag sendto函数的标志位
     * @return >=0 实际发送的字节数 -1 失败
     */
    public function SendTo($inBuf, $maxLen, $sAddr, $iPort, $iFlag = 0)
    {
        if (!$this->IsOpen()) {
            if ($this->Open() == -1) {
                return -1;
            }
        }
        $send_len = socket_sendto($this->GetHandle(), $inBuf . "", $maxLen, $iFlag, $sAddr, $iPort);
        if ($send_len === false) {
            return -1;
        }
        return $send_len;
    }

    /**
     * @desc 接收UDP包，如果timeout未设置或为小于0，则为阻塞方式接收，否则为非阻塞
     * @param outBuf 接收数据缓冲
     * @param maxLen 最大接收长度
     * @param sAddr 来源地址，必须以引用方式传递
     * @param iPort 来源端口，必须以引用方式传递
     * @param iTimeout 超时时间 （毫秒为单位）
     * @param iFlag socket标志位
     * @return >=0 接收的字节数 <0 失败
     */
    public function RecvFrom(&$outBuf, $maxLen, &$sAddr, &$iPort, $iTimeout = -1, $iFlag = 0)
    {
        if (!$this->IsOpen()) {
            return -1;
        }

        $retcode = 0;
        if ($iTimeout < 0) {
            $retcode = $this->BlockRecvFrom($outBuf, $maxLen, $sAddr, $iPort, $iFlag);
        } else {
            $retcode = $this->NonBlockRecvFrom($outBuf, $maxLen, $sAddr, $iPort, $iTimeout, $iFlag);
        }
        return $retcode;
    }

    /**
     * @desc 阻塞方式接收UDP包
     * @param outBuf 接收数据缓冲
     * @param maxLen 最大接收长度
     * @param sAddr 来源地址，必须以引用方式传递
     * @param iPort 来源端口，必须以引用方式传递
     * @param iFlag socket标志位
     * @return >=0 接收的字节数 <0 失败
     */
    protected function BlockRecvFrom(&$outBuf, $maxLen, &$sAddr, &$iPort, $iFlag)
    {
        $this->SetBlock(true);

        $iRecvLen = 0;
        for (; ;) {
            $iRecvLen = socket_recvfrom($this->GetHandle(), $outBuf, $maxLen, $iFlag, $sAddr, $iPort);
            if ($iRecvLen === false) {
                $errno = socket_last_error();
                if ($errno != SOCKET_EINTR) {
                    $this->SetBlock(false);
                    return -1;
                }
            } else {
                break;
            }
        }
        $this->SetBlock(false);
        return $iRecvLen;
    }

    /**
     * @desc 非阻塞接收UDP包
     * @param outBuf 接收数据缓冲
     * @param maxLen 最大接收长度
     * @param sAddr 来源地址，必须以引用方式传递
     * @param iPort 来源端口，必须以引用方式传递
     * @param iTimeout 超时时间（毫秒为单位，默认为阻塞）
     * @param iFlag socket标志位
     * @return >=0 接收的字节数 <0 失败，如果超时，接收字节会为0
     */
    protected function NonBlockRecvFrom(&$outBuf, $maxLen, &$sAddr, &$iPort, $iTimeout, $iFlag)
    {
        $iTimeoutSec = null;
        $iTimeoutUSec = null;
        if ($iTimeout >= 0) {
            $iTimeoutSec = floor($iTimeout / 1000);
            $iTimeoutUSec = ($iTimeout % 1000) * 1000;
        }

        $iRecvLen = 0;
        $iTimeBegin = microtime(true) * 1000;

        for (; ;) {
            $rfds = array($this->GetHandle());
            $wfds = null;
            $efds = null;

            $nfd_ready = socket_select($rfds, $wfds, $efds, $iTimeoutSec, $iTimeoutUSec);
            if ($nfd_ready < 0) {
                if (socket_last_error() != SOCKET_EINTR) {
                    return -1;
                }
            } else if ($nfd_ready == 0) {
                return 0; // 接收超时
            } else {
                $iRecvLen = socket_recvfrom($this->GetHandle(), $outBuf, $maxLen, $iFlag, $sAddr, $iPort);
                if ($iRecvLen === false) {
                    if (socket_last_error() != SOCKET_EWOULDBLOCK) {
                        return -1;
                    }
                } else {
                    break;
                }
            }
            if ($iTimeout > 0) {
                $iTimeout = $iTimeout + $iTimeBegin - microtime(true) * 1000;
                if ($iTimeout < 0) {
                    $iTimeout = 0;
                    $iTimeoutSec = 0;
                    $iTimeoutUSec = 0;
                }
            }
        }
        return $iRecvLen;
    }

}

?>