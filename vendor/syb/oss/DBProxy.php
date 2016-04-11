<?php
//**********************************************************
// File name: DBProxy.class.php
// Class name: DBProxy
// Create date: 2009/04/08
// Update date: 2011/10/25
// Author: garyzou
// Description: DBProxy处理类
//**********************************************************
namespace syb\oss;
use syb\oss\Network\TcpConnector;
use syb\oss\Network\TcpStream;
use syb\oss\Network\SocketAPI;

use \socket_last_error;
use \socket_strerror;

define('PKG_FLAG', 0x1f);
define('PKG_VERSION', 0x01);
define('PKG_MAX_SIZE', 52100);
define('TRANS_MAX_SIZE', 128);
define('SQLT_MAX_SIZE', 16);

define('SQLT_NONE', 0);
define('SQLT_SELECT', 1);
define('SQLT_UPDATE', 2);
define('SQLT_DELETE', 3);
define('SQLT_REPLACE', 4);
define('SQLT_INSERT', 5);
define('SQLT_SET', 6);

//事务
define('CMD_TRANS', 1);
//更新
define('CMD_UPDATE', 2);
//插入
define('CMD_INSERT', 3);
//查询
define('CMD_QUERY', 4);


class DBProxy
{
    private $m_host;
    private $m_port;
    private $m_database;
    private $m_errno;
    private $m_errstr;

    function __construct($host, $port, $database)
    {
        $this->m_host = $host;
        $this->m_port = $port;
        $this->m_database = $database;
        $this->m_errno = 0;
        $this->m_errstr = NULL;
    }

    public function SetDatabase($database)
    {
        $this->m_database = $database;
    }

    public function GetErrMsg()
    {
        return $this->m_errstr;
    }

    /*
    *返回值：记录的行数
    */
    public function ExecQuery($stmt, &$resultset, $timeout = 30000)
    {
        $sql_type = self::get_sql_type($stmt);
        if ($sql_type != SQLT_SELECT) {
            throw new Exception("invalid statement.\n");
            return;
            //$this->m_errstr="invalid statement.";
            //return -1;
        }

        /****new**/
        $inBufLen = $this->encode(CMD_QUERY, $stmt, &$inBuf);

        $tcpConn = new TcpConnector();
        $tcpStream = new TcpStream();

        $ret = $tcpConn->Connect($tcpStream, $this->m_host, $this->m_port, $timeout);
        if ($ret < 0) {
            unset($tcpStream);
            $errMsg = &socket_strerror(socket_last_error());
            throw new Exception($errMsg);
        }

        //发送查询请求
        $ret = $tcpStream->SendN($inBuf, $inBufLen, $timeout);
        if ($ret != $inBufLen) {
            unset($tcpStream);
            $errMsg = &socket_strerror(socket_last_error());
            throw new Exception($errMsg);
        }

        //接收响应头
        $headerBuf=NULL;
        $ret = $tcpStream->RecvN($headerBuf, 12, $timeout);
        if ($ret < 0) {
            unset($tcpStream);
            $errMsg = &socket_strerror(socket_last_error());
            throw new Exception($errMsg);
            return false;
        }

        if ($headerBuf == "") {
            throw new Exception("tcp socket no data echo.\n");
            return;
        }

        if ($this->checkHeader($headerBuf, CMD_QUERY, $pkglen, $row) != 0) {
            throw new Exception("echo data header error.\n");
            return;
        }
        if ($row > 0) {
            //接收包体
            $pkglen = $pkglen - 12;
            $outBuf = "";
            $ret = $tcpStream->RecvN($outBuf, $pkglen, $timeout);
            if ($ret < 0) {
                unset($tcpStream);
                $errMsg = &socket_strerror(socket_last_error());
                throw new Exception($errMsg);
                return false;
            }
            if ($outBuf != "")
                $this->decode($outBuf, $pkglen, $row, $resultset);
            unset($outBuf);
        }
        unset($tcpStream);
        return $row;
    }

    /*
    *返回值说明：
    *insert：有自增字段，返回seqid，没有返回0
    *update：返回影响记录的行数
    */
    public function ExecUpdate($stmt)
    {
        $sql_type = self::get_sql_type($stmt);
        if ($sql_type != SQLT_INSERT && $sql_type != SQLT_UPDATE
            && $sql_type != SQLT_DELETE && $sql_type != SQLT_REPLACE
            && $sql_type != SQLT_SET && $sql_type != SQLT_CREATE
            && $sql_type != SQLT_ALTER && $sql_type != SQLT_DROP
        ) {
            throw new Exception("invalid statement.\n");
            return;
            //$this->m_errstr="invalid statement.\n";
            //return -5;
        }

        $cmd = CMD_UPDATE;
        if ($sql_type == SQLT_INSERT)
            $cmd = CMD_INSERT;
        $inBufLength = $this->encode($cmd, $stmt, &$inBuf);
        if (!SocketAPI::tcpSocketPackage($this->m_host, $this->m_port, $inBuf, $outBuf, $errMsg)) {
            throw new Exception("tcpSocketPackage error:" . $errMsg . ".\n");
            return;
            //$this->m_errstr="tcpSocketPackage error:".$errMsg.".\n";
            //return -6;
        }

        $oufbuflen = strlen($outBuf);
        if ($outBuf == "" || $oufbuflen <= 0) {
            throw new Exception("tcpSocketPackage no data echo.\n");
            return;
            //$this->m_errstr="tcpSocketPackage no data echo.\n";
            //return -7;
        }

        if ($this->checkHeader($outBuf, $cmd, $pkglen, $ret) != 0) {
            throw new Exception("echo data header error.\n");
            return;
            //$this->m_errstr="echo data header error.\n";
            //return -8;
        }
        //echo "ret:$ret";
        return $ret;
    }

    public function ExecTrans($statements)
    {
        if (count($statements) > TRANS_MAX_SIZE) {
            throw new Exception("Exec Trans Sql too long.\n");
            return;
            //$this->m_errstr="Exec Trans Sql too long.\n";
            //return -9;
        }
        $sql_type = SQLT_NONE;
        if (!is_array($statements)) {
            throw new Exception("Exec Trans Sqls not array.\n");
            return;
            //$this->m_errstr="Exec Trans Sqls not array.\n";
            //return -10;
        }
        for ($i = 0; $i < count($statements); ++$i) {
            $sql_type = self::get_sql_type($statements[$i]);;
            if ($sql_type != SQLT_INSERT
                && $sql_type != SQLT_UPDATE
                && $sql_type != SQLT_DELETE
                && $sql_type != SQLT_SET
                && $sql_type != SQLT_REPLACE
            ) {
                throw new Exception("invalid statement.\n");
                return;
                //$this->m_errstr="invalid statement.\n";
                //return -11;
            }
        }
        $cmd = CMD_TRANS;
        $inBufLength = $this->encodeTrans($cmd, $statements, &$inBuf);
        if (!SocketAPI::tcpSocketPackage($this->m_host, $this->m_port, $inBuf, $outBuf, $errMsg)) {
            throw new Exception("tcpSocketPackage error:" . $errMsg);
            return;
            //$this->m_errstr="tcpSocketPackage error:".$errMsg;
            //return -12;
        }
        if ($outBuf == "") {
            throw new Exception("tcpSocketPackage no data echo.\n");
            return;
            //$this->m_errstr="tcpSocketPackage no data echo.\n";
            //return -13;
        }

        if ($this->checkHeader($outBuf, $cmd, $pkglen, $ret) != 0) {
            throw new Exception("echo data header error.\n");
            return;
            //$this->m_errstr="echo data header error.\n";
            //return -14;
        }
        return $ret;
    }

    public function ExecProc($statement, $args)
    {
        throw new Exception("not support.\n");
    }

    public function Begin()
    {
        throw new Exception("not support.\n");
    }

    public function Commit()
    {
        throw new Exception("not support.\n");
    }

    public function Rollback()
    {
        throw new Exception("not support.\n");
    }

    public function Errno()
    {
        return m_errno;
    }

    public function Errstr()
    {
        return m_errstr;
    }

    public function IsUpdate($sql)
    {
        return ($this->get_sql_type($sql) != SQLT_SELECT);
    }

    //打包单条sql语句请求，如查询，更新
    protected function encode($cmd, $stmt, &$buf)
    {
        $abuf = pack("n", strlen($stmt) + 1) . pack("a*", $stmt) . pack("a", "\0");
        $abuf = pack("n", strlen($this->m_database) + 1) . pack("a*", $this->m_database) . pack("a", "\0") . $abuf;
        $pakLength = 12 + strlen($this->m_database) + 3 + strlen($stmt) + 3;
        $buf = pack("CCnnni", PKG_FLAG, PKG_VERSION, $pakLength, 0, $cmd, 0) . $abuf;
        return $pakLength;
    }

    //打包事务请求
    protected function encodeTrans($cmd, $stmts, &$buf, $flag = true)
    {
        $abuf = "";
        for ($i = 0; $i < count($stmts); ++$i) {
            $abuf = $abuf . pack("n", strlen($stmts[$i]) + 1) . pack("a*", $stmts[$i]) . pack("a", "\0");
        }
        $Flag = "";
        if ($flag) {
            //$Flag = pack("n",2).pack("n",1);
            $Flag = pack("n", 2) . pack("a", "1") . pack("a", "\0");
        } else {
            $Flag = pack("n", 2) . pack("n", 0);
        }
        //$pakLength = 12 + strlen($this->m_database)+3+4+strlen($abuf);
        $abuf = pack("n", strlen($this->m_database) + 1) . pack("a*", $this->m_database) . pack("a", "\0") . $Flag . $abuf;
        $pakLength = 12 + strlen($abuf);
        $buf = pack("CCnnni", PKG_FLAG, PKG_VERSION, $pakLength, 0, $cmd, 0) . $abuf;
        return $pakLength;
    }

    //解析数据库结果Header,并判断合法性
    protected function checkHeader(&$buf, $cmd, &$pkglen, &$ret)
    {
        $res = &unpack("Cflag/Cversion/npkglen/nservice/ncmd/iret", $buf);
        $ret = SocketAPI::ntohl($res['ret']);
        $pkglen = $res['pkglen'];
        //截去header
        //$buf = substr($buf, 12);
        //$recvLen = $recvLen-12;
        if ($ret < 0 || $res['cmd'] != $cmd) {
            throw new Exception("sql excute error or echo data header error.\n");
            return;
            //$this->m_errstr="sql excute error or echo data header error.\n";
            //return -15;
        }
        return 0;
    }

    //解析数据库查询结果
    protected function decode(&$buf, &$recvLen, $ret, &$resultset)
    {
        $this->unpack_body_field($buf, $recvLen, $element, $elelen);
        //解析字段
        $resultitem = array();
        $this->UnpackItemRow($element, $elelen, $resultitem);
        $resultset = array();
        for ($i = 0; $i < $ret; ++$i) {
            $this->unpack_body_field($buf, $recvLen, $row, $rowlen);
            $resulttmp = array();
            $this->UnpackRow($row, $rowlen, $resultitem, $resulttmp);
            $resultset[$i] = $resulttmp;
        }
        return 0;
    }

    //解包单个域
    protected function unpack_body_field(&$body, &$bodylen, &$element, &$len)
    {
        if ($body == "" || $bodylen == 0) {
            throw new Exception("parse echo data body error.\n");
            return;
            //$this->m_errstr="parse echo data body error.\n";
            //return -16;
        }
        $res = unpack('nlen', $body);
        $len = $res['len'];
        if ($len > $bodylen || $len < 0) {
            throw new Exception("parse echo data body error.\n");
            return;
            //$this->m_errstr="parse echo data body error.\n";
            //return -17;
        }
        $element = substr($body, 2, $len);
        $body = substr($body, $len + 2);
        $bodylen = $bodylen - ($len + 2);
        return 0;
    }

    //解包一条记录
    protected function UnpackItemRow($row, $rowlen, &$resultset)
    {
        if ($row == "" || $rowlen == 0) {
            throw new Exception("parse echo data body error.\n");
            return;
            //$this->m_errstr="parse echo data body error.\n";
            //return -18;
        }
        $resultset = array();
        while ($rowlen > 0) {
            $this->unpack_body_field($row, $rowlen, $field, $fieldlen);
            $resultset[] = substr($field, 0, $fieldlen - 1);
        }
        return 0;
    }

    //解包一条记录
    protected function UnpackRow($row, $rowlen, &$resultitem, &$resultset)
    {
        if ($row == "" || $rowlen == 0) {
            throw new Exception("parse echo data body error\n");
            return;
            //$this->m_errstr="parse echo data body error\n";
            //return -19;
        }

        $resultset = array();
        for ($i = 0; $i < count($resultitem); $i++) {
            $this->unpack_body_field($row, $rowlen, $field, $fieldlen);
            $resultset[$resultitem[$i]] = substr($field, 0, $fieldlen - 1);
        }
        return 0;
    }

    private function get_sql_type(&$sql)
    {
        $sql = ltrim($sql);
        $sql_type = strtolower(rtrim(substr($sql, 0, strpos($sql, " ", 0))));
        if (strcmp($sql_type, "select") == 0)
            return SQLT_SELECT;
        else if (strcmp($sql_type, "update") == 0)
            return SQLT_UPDATE;
        else if (strcmp($sql_type, "insert") == 0)
            return SQLT_INSERT;
        else if (strcmp($sql_type, "delete") == 0)
            return SQLT_DELETE;
        else if (strcmp($sql_type, "replace") == 0)
            return SQLT_REPLACE;
        else if (strcmp($sql_type, "set") == 0)
            return SQLT_SET;
        else if (strcmp($sql_type, "create") == 0)
            return SQLT_CREATE;
        else if (strcmp($sql_type, "drop") == 0)
            return SQLT_DROP;
        else if (strcmp($sql_type, "alter") == 0)
            return SQLT_ALTER;
        return SQLT_NONE;
    }
}