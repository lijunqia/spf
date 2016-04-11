<?php
//**********************************************************
// File name: socket.class.php
// Class name: SocketAPI
// Create date: 2009/04/08
// Update date: 2009/04/08
// Author: garyzou
// Description: socket处理类
//**********************************************************
//define(OUT_BUFFER_MAX_LENGTH, 1024);

//require("Common.inc.php");
//require 'Exception.class.php';
//require 'Logger.class.php';
namespace syb\oss\Network;
class SocketAPI
{
    const OUT_BUFFER_MAX_LENGTH = 52100;

    public static function udpPackage(&$hostIp, $port, &$inBuf, &$outBuf, &$errMsg, $blockFlag = false)
    {
        $inBufLength = strlen($inBuf);
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($blockFlag) {
            //设置为阻塞模式
            if (!(socket_set_block($sock))) {
                socket_close($sock);
                $errMsg = &socket_strerror(socket_last_error());
                throw new OssException($errMsg);
                return false;
            }
        } else {
            //设置为非阻塞，函数默认是非阻塞模式
            if (!(socket_set_nonblock($sock))) {
                socket_close($sock);
                $errMsg = &socket_strerror(socket_last_error());
                throw new OssException($errMsg);
                return false;
            }
        }

        //发送请求
        $fail = socket_sendto($sock, $inBuf, $inBufLength, 0x100, $hostIp, $port);
        if ($fail != $inBufLength) {
            socket_close($sock);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }

        $outBufLength = socket_recvfrom($sock, $outBuf, self::OUT_BUFFER_MAX_LENGTH, 0, $svrName, $svrPort);

        socket_close($sock);
        return true;
    }

    // added by parkerzhu 2011.3.30
    public static function udpPackageTimeout(&$hostIp, $port, &$inBuf, &$outBuf, $timeout, &$errMsg)
    {
        $inBufLen = strlen($inBuf);

        $udpDgram = new UdpDgram();
        $ret = $udpDgram->SendTo($inBuf, $inBufLen, $hostIp, $port);
        if ($ret != $inBufLen) {
            unset($udpDgram);
            $errMsg = &socket_strerror(socket_last_error());
            return $ret;
        }

        $outBufLength = $udpDgram->RecvFrom($outBuf, self::OUT_BUFFER_MAX_LENGTH, $hostIp, $port, $timeout);
        if ($outBufLength < 0) {
            unset($udpDgram);
            $errMsg = &socket_strerror(socket_last_error());
            return $outBufLength;
        }
        unset($udpDgram);
        return $outBufLength;
    }

    public static function tcpPackageTimeout(&$hostIp, $port, &$inBuf, &$outBuf, $timeout, &$errMsg)
    {
        $inBufLen = strlen($inBuf);

        $tcpConn = new TcpConnector();
        $tcpStream = new TcpStream();

        $ret = $tcpConn->Connect($tcpStream, $hostIp, $port, $timeout);
        if ($ret < 0) {
            unset($tcpStream);
            $errMsg = &socket_strerror(socket_last_error());
            return $ret;
        }

        $ret = $tcpStream->SendN($inBuf, $inBufLen, $timeout);
        if ($ret != $inBufLen) {
            unset($tcpStream);
            $errMsg = &socket_strerror(socket_last_error());
            return $ret;
        }

        $ret = $tcpStream->Recv($outBuf, self::OUT_BUFFER_MAX_LENGTH, $timeout);
        if ($ret < 0) {
            unset($tcpStream);
            $errMsg = &socket_strerror(socket_last_error());
            return $ret;
        }

        unset($tcpStream);
        return $ret;
    }

    // added by parkerzhu 2011.3.30 end

    public static function selectUDPPackage($hostIp, $port, $backHostIp, $backPort, &$inBuf, $inBufLength, &$outBuf, &$outBufLength, &$errMsg, $blockFlag = false)
    {
        $sock1 = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        $sock2 = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($blockFlag) {
            //设置为阻塞模式
            if (!(socket_set_block($sock1) || socket_set_block($sock2))) {
                socket_close($sock1);
                socket_close($sock2);
                $errMsg = &socket_strerror(socket_last_error());
                throw new OssException($errMsg);
                return false;
            }
        } else {
            //设置为非阻塞
            if (!(socket_set_nonblock($sock1) || socket_set_nonblock($sock2))) {
                socket_close($sock1);
                socket_close($sock2);
                $errMsg = &socket_strerror(socket_last_error());
                throw new OssException($errMsg);
                return false;
            }
        }

        //发送请求
        $fail1 = (socket_sendto($sock1, $inBuf, $inBufLength, 0x100, $hostIp, $port) != $inBufLength);
        $fail2 = (socket_sendto($sock2, $inBuf, $inBufLength, 0x100, $backHostIp, $backPort) != $inBufLength);
        if ($fail1 && $fail2) {
            socket_close($sock1);
            socket_close($sock2);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }
        $read = array($sock1, $sock2);
        //select
        $numSockets = socket_select($read, $write = NULL, $except = NULL, VERIFY_TIMEOUT);
        if ($numSockets > 0) {
            //接收结果
            if (in_array($sock1, $read)) {
                $outBufLength = socket_recvfrom($sock1, $outBuf, self::OUT_BUFFER_MAX_LENGTH, 0, $svrName, $svrPort);
            } else {
                $outBufLength = socket_recvfrom($sock2, $outBuf, self::OUT_BUFFER_MAX_LENGTH, 0, $svrName, $svrPort);
            }
        } else if ($numSockets === false) {
            //select出错
            socket_close($sock1);
            socket_close($sock2);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        } else {
            //超时
            socket_close($sock1);
            socket_close($sock2);
            $errMsg = "socket timed out";
            throw new OssException($errMsg);
            return false;
        }
        socket_close($sock1);
        socket_close($sock2);
        return true;
    }

    //public static function tcpSocketPackage(&$hostIp, $port, &$inBuf, &$inBufLength, &$outBuf, &$errMsg)
    public static function tcpSocketPackage(&$hostIp, $port, &$inBuf, &$outBuf, &$errMsg)
    {
        // Create the socket and connect
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket < 0) {
            //socket_close($socket);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }

        $connection = socket_connect($socket, $hostIp, $port);
        if ($connection < 0) {
            socket_close($socket);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }
        //echo "inBuf=".$inBuf."<br/>";
        // Write  data to our socket
        //if(!socket_write($socket, $inBuf, $inBufLength))
        if (!socket_write($socket, $inBuf)) {
            socket_close($socket);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }

        // Read any response from the socket
        //Optional type parameter is a named constant:
        //PHP_BINARY_READ - use the system recv() function. Safe for reading binary data. (Default in PHP >= 4.1.0)
        //PHP_NORMAL_READ - reading stops at \n or \r. (Default in PHP <= 4.0.6)
        //$outBuf="";
        if (false === ($outBuf = socket_read($socket, self::OUT_BUFFER_MAX_LENGTH, PHP_BINARY_READ))) {
            socket_close($socket);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }
        /*do
        {
            $out = socket_read($socket, 1448);
            if($out===false)
            {
                $errMsg = &socket_strerror(socket_last_error());
                socket_close($socket);
                //throw new OssException($errMsg);
                break;
            }
            else
            {
                $outBuf .= $out;
            }
            if($out=="")
            {
                break;
            }
            if($times++ == 1)
            {
                break;
            }
        }while(true);*/
        $errMsg = &socket_strerror(socket_last_error());
        socket_close($socket);
        return true;
    }

    public static function tcpNetPackage(&$hostIp, $port, &$inBuf, &$outBuf, &$errMsg)
    {
        //echo "tcpNetPackage Proxy IP :".$hostIp."<br/>";
        //echo "tcpNetPackage Proxy Port : ".$port."<br/>";
        $outBuf = "";
        $fp = fsockopen($hostIp, $port, $errno, $errdesc);
        if (!$fp) {
            fclose($fp);
            $errMsg = &socket_strerror(socket_last_error());
            throw new OssException($errMsg);
            return false;
        }
        //fputs($fp,$inBuf);
        fwrite($fp, $inBuf, strlen($inBuf));
        do {
            //$tmpBuf = fgets($fp,self::OUT_BUFFER_MAX_LENGTH);
            //$tmpBuf = fgets($fp,1024);
            $tmpBuf = fread($fp, 1024);
            $outBuf .= $tmpBuf;
        } while ($tmpBuf != "");
        echo "<br/>outbuf:" . $outBuf . "<br/>";
        echo "<br/>outbuf len:" . strlen($outBuf) . "<br/>";
        //关闭socket连接
        fclose($fp);
        return true;
    }

    public static function ntohl($n)
    {
        $arr = unpack('I', pack('N', $n));
        return $arr[1];
    }

    public static function ntohs($n)
    {
        $arr = unpack('S', pack('n', $n));
        return $arr[1];
    }
}

?>