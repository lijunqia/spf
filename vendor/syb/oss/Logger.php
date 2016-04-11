<?php
//**********************************************************
// File name: LogsClass.class.php
// Class name: 日志记录类
// Create date: 2009/04/07
// Update date: 2009/04/07
// Author: garyzou
// Description: 日志记录类
// Example: $log = new Logger();
//			//$log->initLogger(DIRECT_ECHO);
//			//$log->initLogger(DATE_FILE_LOGGER,"./log/");
//			$log->initLogger(ROLL_FILE_LOGGER,"./log/","logtest.dddd",256,5);
//			$log->setNullLoger(LP_BASE|LP_TRACE|LP_DEBUG);
//			$log->writeLog(__FILE__,__LINE__,LP_BASE,"test log string\n");
//**********************************************************

/*//! \brief 滚动日志的默认文件最大值为10MB 1024*1024*10
define(DEFAULT_LOG_MAX_SIZE, 1024*1024*10);
//! \brief 滚动日志默认保存最近3个
define(DEFALUT_LOG_SAVE_NUM, 5);

//日志类型
define(ROLL_FILE_LOGGER, 1);
define(DATE_FILE_LOGGER, 2);
define(DIRECT_ECHO, 3);

//日志级别
/// \brief 基础级别。 此级别一般不用。
define(LP_BASE, 1);
/// \brief 跟踪级别。用在函数的进入/退出时做记录用，一般只会用在前期的debug版。\n
/// 生成release版时一般要将此级别的信息忽略 (使用NullLogger处理)
define(LP_TRACE, LP_BASE << 1);
/// \brief 调试级别。程序输出debug信息时用。\n
/// 生成release版时一般要将此级别的信息忽略 (使用NullLogger处理)
define(LP_DEBUG, LP_BASE << 2);
/// \brief 普通级别。记录一般性的非错信息
define(LP_INFO, LP_BASE << 3);
/// \brief 用户级别1。级别说明的最终解释权归应用程序所有。
define(LP_USER1, LP_BASE << 4);
/// \brief 用户级别2。级别说明的最终解释权归应用程序所有。
define(LP_USER2, LP_BASE << 5);
/// \brief 警告信息。
define(LP_WARNING, LP_BASE << 6);
/// \brief 普通错误。大部分错误信息都用此级别记录。
define(LP_ERROR, LP_BASE << 7);
/// \brief 严重错误。只在系统层发生严重错误时，才用此级别。例如出现硬件故障的情况。
define(LP_CRITICAL, LP_BASE << 8);
/// \brief 当前最大日志级别。
define(LP_MAX, LP_CRITICAL);*/

//日志级别,在应用使用此宏
/*define(BASE, "__FILE__,__LINE__,LP_BASE");
define(TRACE, "__FILE__,__LINE__,LP_TRACE");
define(DEBUG, "__FILE__,__LINE__,LP_DEBUG");
define(INFO, "__FILE__,__LINE__,LP_INFO");
define(USER1, "__FILE__,__LINE__,LP_USER1");
define(USER2, "__FILE__,__LINE__,LP_USER2");
define(WARNING, "__FILE__,__LINE__,LP_WARNING");
define(ERROR, "__FILE__,__LINE__,LP_ERROR");
define(CRITICAL, "__FILE__,__LINE__,LP_CRITICAL");*/
namespace syb\oss;

/**
 *
 *
 */
class Logger
{
    static $Logger_pInstance = NULL;
    private $FilePath;
    private $FileName;
    private $FullFileName;
    private $MaxSize;
    private $FileNum;
    private $LogType;
    private $NullLog;

    /**
     * Constructor
     * @access protected
     */
    function __construct()
    {
        self::$Logger_pInstance = $this;
    }

    /**
     * 单件方法
     * @return Logger 单件指针
     */
    static function Instance()
    {
        return self::$Logger_pInstance?:(new self);
    }

    //作用:初始化日志记录类
    //输入:文件的路径,要写入的文件名，不要带后缀,日志类型是ROLL_FILE_LOGGER必须设置文件名
    //输出:无
    function initLogger($logtype = DIRECT_ECHO, $dir = NULL, $filename = NULL, $maxsize = DEFAULT_LOG_MAX_SIZE, $filenum = DEFALUT_LOG_SAVE_NUM)
    {
        $this->FilePath = $dir;
        $this->LogType = $logtype;
        if ($logtype == ROLL_FILE_LOGGER) {
            if ($filename == NULL) {
                //抛出异常，没有指定文件名。
                throw new OssException("filename cannot be null.\n");
            } else {
                $pos = strrpos($filename, ".");
                if ($pos === false) {
                    //not find
                } else {
                    $filename = substr($filename, 0, $pos);
                }
            }
        } else if ($logtype == DATE_FILE_LOGGER) {
            $filename = $filename . date("Ymd");
        }
        $this->FileName = $filename;
        $this->FullFileName = $dir . "/" . $filename . ".log";
        $this->MaxSize = $maxsize;
        $this->FileNum = $filenum;
        $this->NullLog = 0;

        if ($logtype != DIRECT_ECHO) {
            //判断是否存在该文件
            if (!file_exists($this->FullFileName)) {//不存在
                //创建目录
                if (!$this->createFolder($this->FilePath)) {
                    //创建目录不成功的处理
                    throw new OssException("Create Directory Error.\n");
                }
                //创建文件
                if (!$this->createLogFile($this->FullFileName)) {
                    //创建文件不成功的处理
                    throw new OssException("Create file Error.\n");
                }
            }
        }
    }

    //作用:设置不要打印日志的级别
    //输入:要写入的记录
    //输出:无
    function setNullLoger($loglevel)
    {
        if ($loglevel > 0) {
            $this->NullLog = $loglevel;
        }
    }

    //作用:写入记录
    //输入:要写入的记录
    //输出:无
    function writeLog($codefilename = __FILE__, $codefileline = __LINE__, $loglevel, $log)
    {
        if ((($this->NullLog) & $loglevel) >= LP_BASE) {
            //不需要打印日志
            return false;
        }

        if ($this->LogType == ROLL_FILE_LOGGER) {
            if (strlen($log) > $this->MaxSize) {
                //太长不能记录
                throw new OssException("Log Content too long.\n");
            }
            if (filesize($this->FullFileName) + strlen($log) > $this->MaxSize) {
                $this->ShiftLogFile($this->FilePath);
            }
        }
        //[2009-04-03 18:18:51] [DEBUG] [xxxxx.php:149] logmessage
        //"/usr/local/xxx/yyy/abc.log"
        $codefilename = basename($codefilename);        // $file is set to "abc.log"
        $log = "[" . date("Y-m-d H:i:s") . "] [" . $this->getDescribe($loglevel) . "] [pid " . getmypid() . "] [" . $codefilename . ":" . $codefileline . "] " . $log;
        if (($loglevel == LP_ERROR || gethostbyname($_SERVER["SERVER_NAME"]) == "172.27.129.153")
            && strpos($_SERVER["SERVER_NAME"], "qt.qq.com") === FALSE
        ) {
            mt_srand(time());
            $_num = (mt_rand() + getmypid()) % 5;
            $ports = array(
                5550, 5551, 5552, 5553, 5554
            );

            $_udpLogPlat = new UdpLogger("172.27.134.213", $ports[$_num]);
            $_udpLogPlat->setLogType(0x00);
            $_udpLogPlat->setPlatName("PHP");
            $_udpLogPlat->logComm(0, "0", "0", $log);
        }
        //$log = oss_iconv(ICONV_FROM,ICONV_TO,$log);
        if ($this->LogType == DIRECT_ECHO) {
            //直接打印到页面上
            echo nl2br($log);
        } else {
            $handle = fopen($this->FullFileName, "a+");
            //写日志
            if (!fwrite($handle, $log)) {//写日志失败
                throw new OssException("Write Log to file Error.\n");
            }
            //关闭文件
            fclose($handle);
        }
    }


    private function ShiftLogFile($dir)
    {
        $needremovefile = $this->createFileName($this->FilePath, $this->FileName, ($this->FileNum - 1));
        if (file_exists($needremovefile)) {
            unlink($needremovefile);
        }
        for ($i = ($this->FileNum - 2); $i >= 0; $i--) {
            $oldlogfile = "";
            if ($i == 0) {
                $oldlogfile = $this->FullFileName;
            } else {
                $oldlogfile = $this->createFileName($this->FilePath, $this->FileName, $i);
            }
            if (file_exists($oldlogfile)) {
                $newlogfile = $this->createFileName($this->FilePath, $this->FileName, ($i + 1));
                rename($oldlogfile, $newlogfile);
            }
        }
    }

    //组装文件名
    private function createFileName($dir, $filename, $num)
    {
        return $dir . "/" . $filename . $num . ".log";
    }

    //作用:创建目录
    //输入:要创建的目录
    //输出:true | false
    private function createDir($dir)
    {
        return is_dir($dir) or ($this->createDir(dirname($dir)) and mkdir($dir, 0777));
    }

    //创建多层目录
    //createFolder("2007/3/4");
    //在当前目录下创建2007/3/4的目录结构．
    private function createFolder($dir)
    {
        return is_dir($dir) or ($this->createFolder(dirname($dir)) and mkdir($dir, 0777));
    }

    //作用:创建日志文件
    //输入:要创建的目录
    //输出:true | false
    private function createLogFile($path)
    {
        $handle = fopen($path, "w"); //创建文件
        fclose($handle);
        return file_exists($path);
    }

    private function getDescribe($loglevel)
    {
        $LPDescribe = array
        (LP_BASE => "BASE",
            LP_TRACE => "TRACE",
            LP_DEBUG => "DEBUG",
            LP_INFO => "INFO",
            LP_USER1 => "USER1",
            LP_USER2 => "USER2",
            LP_WARNING => "WARNING",
            LP_ERROR => "ERROR",
            LP_CRITICAL => "CRITICAL",
            LP_MAX => "MAX"
        );
        return $LPDescribe[$loglevel];
    }
}
