<?php
//**********************************************************
// File name: TcpConnector.class.php
// Class name: TcpConnector
// Create date: 2011/3/29
// Update date: 2011/3/29
// Author: parkerzhu
// Description: Tcp连接建立包装类
//**********************************************************

//require_once("TcpStream.class.php");
namespace syb\oss\Network;
/**
 * TcpConnector类，实现非阻塞的connect
 * @author parkerzhu
 *
 */
class TcpConnector
{
    /**
     * @desc 非阻塞方式打开并连接一个TcpStream对象
     * @param tcpStream tcp流对象
     * @param sAddr 对端地址
     * @param iPort 对端端口
     * @param iTimeout 超时时间 （毫秒为单位，默认为阻塞）
     * @param iSendBufferSize 发送缓冲区大小
     * @return 0: 成功 -1: 失败  -2: 超时
     */
    public function Connect(TcpStream &$tcpStream, $sAddr, $iPort, $iTimeout = -1, $iSendBufferSize = -1)
    {
        $bIsOpen = $tcpStream->IsOpen();
        if(!$bIsOpen)
        {
            if($tcpStream->Open() == -1)
            {
                return -1;
            }
        }

        $retcode = $this->NonBlockConnect($tcpStream, $sAddr, $iPort, $iTimeout, $iSendBufferSize);
        if($retcode == -1)
        {
            if(!$bIsOpen && $tcpStream->IsOpen())
            {
                $tcpStream->Close();
            }
        }
        return $retcode;
    }

    /**
     * @desc 非阻塞连接Tcp
     * @param tcpStream tcp流对象
     * @param sAddr 对端地址
     * @param iPort 对端端口
     * @param iTimeout 超时时间 （毫秒为单位，默认为阻塞）
     * @param iSendBufferSize 发送缓冲区大小
     * @return 0: 成功 -1: 失败 -2: 超时
     */
    public function NonBlockConnect(TcpStream &$tcpStream, $sAddr, $iPort, $iTimeout, $iSendBufferSize)
    {
        $sock = $tcpStream->GetHandle();
        // 设置发送缓冲区的大小
        if($iSendBufferSize >= 0)
        {
            if($tcpStream->SetSockOption(SOL_SOCKET, SO_SNDBUF, $iSendBufferSize) == -1)
            {
                return -1;
            }
        }

        // 尝试连接，等于true直接返回成功
        if(@socket_connect($sock, $sAddr, $iPort) === true)
        {
            return 0;
        }
        else
        {
            $errno = socket_last_error();

            if($errno != SOCKET_EINPROGRESS
            && $errno != SOCKET_EALREADY)
            {
                return -1;
            }
        }

        $iTimeoutSec = null;
        $iTimeoutUSec = null;
        if($iTimeout >= 0)
        {
            $iTimeoutSec = floor($iTimeout / 1000);
            $iTimeoutUSec = ($iTimeout % 1000) * 1000;
        }
        $iTimeBegin = microtime(true) * 1000;

        for(;;)
        {
            $rfds = array($sock);
            $wfds = array($sock);
            $efds = null;

            $nfd_ready = socket_select($rfds, $wfds, $efds, $iTimeoutSec, $iTimeoutUSec);

            if($nfd_ready === false)
            {
                if(socket_last_error() != SOCKET_EINTR)
                {
                    return -1;
                }
            }
            else if($nfd_ready == 0)
            {
                return -2; // 连接超时
            }
            else
            {
                // 连接出错时，变成即可读，又可写
                if(in_array($sock, $rfds) && in_array($sock, $wfds))
                {
                    return -1;
                }
                else
                {
                    break;
                }
            }
            if($iTimeout > 0)
            {
                $iTimeout = $iTimeout + $iTimeBegin - microtime(true) * 1000;
                if($iTimeout < 0)
                {
                    $iTimeout = 0;
                    $iTimeoutSec = 0;
                    $iTimeoutUSec = 0;
                }
            }
        }
        return 0;
    }
}

?>