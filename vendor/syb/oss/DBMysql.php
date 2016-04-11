<?php
//**********************************************************
// File name: DBProxy.class.php
// Class name: ProxyDBConnection
// Create date: 2009/04/08
// Update date: 2009/04/08
// Author: garyzou
// Description: socket处理类
//**********************************************************

//require 'SocketAPI.class.php';
//require 'Exception.class.php';
namespace syb\oss;

define('SQLT_NONE', 0);
define('SQLT_SELECT', 1);
define('SQLT_UPDATE', 2);
define('SQLT_DELETE', 3);
define('SQLT_REPLACE', 4);
define('SQLT_INSERT', 5);
define('SQLT_SET', 6);

$myerr_str = array(
    "success",
    "init mysql client failed",
    "connect failed",
    "query failed",
    "invalid statement",
    "transaction failed",
    "proc failed",
    NULL
);

use \mysql_error;

class DBMysql
{
    private $m_host;
    private $m_port;
    private $m_user;
    private $m_password;
    private $m_database;
    private $m_errno;
    private $m_errstr;
    private $m_instance;

    const return_success = 0;
    const err_init = -1;
    const err_connect = -2;
    const err_query = -3;
    const err_statement = -4;
    const err_transaction = -5;
    const err_proc = -6;

    function __construct($host, $user, $password, $database, $port = 3306, $forceNew = false)
    {
        $this->m_host = $host;
        $this->m_user = $user;
        $this->m_password = $password;
        //$this->m_database = $database;
        $this->m_port = $port;
        $this->m_errno = 0;
        $this->m_errstr = NULL;
        //初始化链接
        $this->m_instance = \mysql_connect(($this->m_host . ':' . $this->m_port), $this->m_user, $this->m_password, $forceNew);
        if (!($this->m_instance)) {
            throw new Exception('Direct Connect To Mysql error:' . mysql_error() . ".\n");
        }
        $this->SetDatabase($database);
    }

    function __destruct()
    {
        if ($this->m_instance) {
            \mysql_close($this->m_instance);
            $this->m_instance = null;
        }
    }

    public function SetDatabase($database)
    {
        $this->m_database = $database;
        if (!\mysql_select_db($this->m_database, $this->m_instance)) {
            throw new Exception('Can\'t use ' . $this->m_database . ' : ' . mysql_error($this->m_instance) . ".\n");
        }
    }

    public function ExecQuery($stmt, &$resultset)
    {
        $sql_type = self::get_sql_type($stmt);
        if ($sql_type != SQLT_SELECT) {
            throw new Exception("invalid statement\n");
        }

        $result = mysql_query($stmt, $this->m_instance);
        if (!$result) {
            throw new Exception('Invalid query:[' . $stmt . '] Error:' . mysql_error($this->m_instance) . ".\n");
        }
        $num_rows = \mysql_num_rows($result);
        //$num_fields = mysql_num_fields($result);

        //MYSQL_NUM：索引数组
        //MYSQL_ASSOC：关联数组
        //MYSQL_BOTH
        $resultset = array();
        while ($row = \mysql_fetch_array($result, MYSQL_ASSOC)) {
            array_push($resultset, $row);
        }
        \mysql_free_result($result);
        return $num_rows;
    }

    public function ExecUpdate($stmt)
    {
        $sql_type = self::get_sql_type($stmt);
        if ($sql_type != SQLT_INSERT && $sql_type != SQLT_UPDATE
            && $sql_type != SQLT_DELETE && $sql_type != SQLT_REPLACE
        ) {
            throw new Exception("invalid statement.\n");
        }
        $result = mysql_query($stmt, $this->m_instance);
        if (!$result) {
            throw new Exception('Invalid query:[' . $stmt . '] Error:' . mysql_error($this->m_instance) . ".\n");
        }

        if ($sql_type == SQLT_INSERT) {
            return \mysql_insert_id($this->m_instance);
        } else {
            return \mysql_affected_rows($this->m_instance);
        }
    }

    public function ExecTrans($statements)
    {
        $sql_type = SQLT_NONE;
        if (!is_array($statements)) {
            throw new Exception("Exec Trans Sqls not array.\n");
        }
        for ($i = 0; $i < count($statements); ++$i) {
            $sql_type = self::get_sql_type($statements[$i]);
            if ($sql_type != SQLT_INSERT
                && $sql_type != SQLT_UPDATE
                && $sql_type != SQLT_DELETE
                && $sql_type != SQLT_SET
                && $sql_type != SQLT_REPLACE
            ) {
                throw new Exception("invalid statement.\n");
            }
        }

        $this->Begin();
        for ($i = 0; $i < count($statements); ++$i) {
            $sql_type = self::get_sql_type($statements[$i]);
            if (!\mysql_query($statements[$i], $this->m_instance)) {
                $this->Rollback();
                throw new Exception('Mysql query:[' . $statements[$i] . '] Error:' . mysql_error($this->m_instance) . ".\n");
            }
            if ($sql_type != SQLT_INSERT && \mysql_affected_rows($this->m_instance) == 0) {
                $this->Rollback();
                //$this->m_errno = self::err_transaction ;
                //$this->m_errstr = self::myerr_str[-(self::err_transaction)];
                throw new Exception('Mysql query:[' . $statements[$i] . '] not affected any rows, Error:' . mysql_error($this->m_instance) . ".\n");
            }
        }
        $this->Commit();
        return 0;
    }

    public function ExecProc($statement, $args)
    {
        return -1;
    }

    public function Begin()
    {
        if (!\mysql_query("start transaction;", $this->m_instance)) {
            throw new Exception('Invalid query: [start transaction;] Error:' . mysql_error($this->m_instance) . ".\n");
        }
        return 0;
    }

    public function Commit()
    {
        if (!\mysql_query("commit;", $this->m_instance)) {
            throw new Exception('Invalid query: [commit;] Error:' . mysql_error($this->m_instance) . ".\n");
        }
        return 0;
    }

    public function Rollback()
    {
        if (!\mysql_query("rollback;", $this->m_instance)) {
            throw new Exception('Invalid query: [rollback;] Error:' . mysql_error($this->m_instance) . ".\n");
        }
        return 0;
    }

    public function Errno()
    {
        return $this->m_errno;
    }

    public function Errstr()
    {
        return $this->m_errstr;
    }

    private function get_sql_type(&$sql)
    {
        $sql = ltrim($sql);
        $sql_type = strtolower(rtrim(substr($sql, 0, strpos($sql, " ", 0))));
        //echo "get_sql_type sql_type:".$sql_type."<br/>";
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
        return SQLT_NONE;
    }

}