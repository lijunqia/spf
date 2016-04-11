<?php
/**
 * 一个简化版本的log4php，支持scribe分布式日志与文件日志
 * log4php虽然很灵活，但太重了
 * author rosenzhang <rosenzhang@tencent.com>
 * 调用demo如下：
 * Logger::Fatal(msg,group);//msg为日志内容，group为日志的目录分组，如果未没定义，转为默认不进行分组
 * Logger::error(msg,group);
 * Logger::warn(msg,group);
 * Logger::info(msg,group);
 * Logger::debug(msg,group);
 * Logger::trace(msg,group);
 */
namespace spf;
class LoggerType
{
    const all = 0;
    const trace = 1;
    const debug = self::trace << 1;
    const info = self::trace << 2;
    const user1 = self::trace << 3;
    const user2 = self::trace << 4;
    const warn = self::trace << 5;
    const error = self::trace << 6;
    const fatal = self::trace << 7;
    const off = self::trace << 8;
}

//trait Logger
class Logger
{
    protected $threshold = LoggerType::all;
    protected $msgs = [];
    protected $optmizer = false;
    protected $writers = [];

    /**
     * 初始化日志类
     */
    function __construct($cfg)
    {
        if (!$cfg) throw new \Exception("Logger config is not set");
        if (!empty($cfg['optmizer'])) {
            register_shutdown_function([$this, 'flush']);
            $this->optmizer = true;
        }
        if ($cfg['threshold']) {
            $this->threshold = constant('\spf\LoggerType::' . $cfg['threshold']);
        }
        foreach ($cfg['writer'] as $writer_name => $o) {
            $writer_class = __NAMESPACE__ . "\\LoggerWriter{$writer_name}";
            if (!class_exists($writer_class)) throw new \Exception("Wrong fromat for the logger writer name, The class {$writer_class} not found!");
            $this->writers[] = new $writer_class($o);
        }
    }

    function trace($msg, $group = '')
    {
        return $this->log('trace', $msg, $group);
    }

    function debug($msg, $group = '')
    {
        return $this->log('debug', $msg, $group);
    }

    function info($msg, $group = '')
    {
        return $this->log('info', $msg, $group);
    }

    function user1($msg, $group = '')
    {
        return $this->log('user1', $msg, $group);
    }

    function user2($msg, $group = '')
    {
        return $this->log('user2', $msg, $group);
    }

    function warn($msg, $group = '')
    {
        return $this->log('warn', $msg, $group);
    }

    function error($msg, $group = '')
    {
        return $this->log('error', $msg, $group);
    }

    function fatal($msg, $group = '')
    {
        return $this->log('fatal', $msg, $group);
    }


    function log($type, $msg, $group = '')
    {
        $numLevel = constant("\\spf\\LoggerType::{$type}");
        $backtrace = debug_backtrace()[1];
        $event = new LoggerLoggingEvent($type, $msg, $group, $numLevel, $backtrace['file'], $backtrace['line']);
        if (!$this->optmizer) {
            if ($numLevel < $this->threshold) return FALSE;
            $this->write($event);
        } else {
            $this->msgs[$type][] = $event;
        }
    }

    protected function write($event)
    {
        //低于日志等级，不记录
        if ($event instanceof LoggerLoggingEvent && ($event->numLevel < $this->threshold)) return TRUE;
        if (empty($this->writers)) return;
        foreach ($this->writers as $writer)
            $writer->write($event);
    }

    function flush()
    {
        if (empty($this->msgs)) return TRUE;
        foreach ($this->writers as $writer)
            $writer->write_more($this->msgs);
        unset($this->msgs);
    }
}

/**
 * 日志事件
 *
 * @author leon
 */
class LoggerLoggingEvent
{
    public $microtime;
    public $level;
    public $group;
    public $msg;
    public $file;
    public $line;
    public $thread_name;
    public $numLevel;
    public $host = '127.0.0.1';

    function __construct($level, $msg, $group, $numLevel, $file, $line)
    {
        $this->microtime = microtime(TRUE);
        $this->level = $level;
        $this->msg = $msg;
        $this->group = $group ?: '';
        $this->file = $file;
        $this->line = $line;
        $this->thread_name = (string)getmypid();
        $this->numLevel = $numLevel;
        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->host = $_SERVER['SERVER_ADDR'];
        } elseif (function_exists('swoole_get_local_ip')) {
            $this->host = implode(';', swoole_get_local_ip());
        }
    }
}

/**
 * 日志格式化器
 *
 * @param $event       LoggerLoggingEvent
 * @param $withGroup   bolean
 */
function logger_layout_common(LoggerLoggingEvent $event)
{
    $date = date('c', $event->microtime);
    $level = $event->level;
//    $microtime = sprintf("%8f", $event->microtime);
    $group = $event->group ? $event->group . ':' : '';
    $msg = $event->msg;
    if (!is_scalar($msg)) $msg = var_export($msg, true);
    return "{$date} [host: {$event->host} pid:{$event->thread_name} {$event->file}:{$event->line} {$group}{$level}]\n{$msg} \n";
}

/**
 * 日志写入器基类
 *
 * @author leon
 */
abstract class LoggerWriter
{
    protected $layout_callback = '';
    protected $run = FALSE;

    function __construct($options = array())
    {
        if ($options) {
            foreach ($options as $k => $v) {
                $this->$k = $v;
            }
        }
        $this->layout_callback ?: ($this->layout_callback = __NAMESPACE__ . '\logger_layout_common');
        $this->init();
    }

    protected function init()
    {

    }
}

/**
 * Class LoggerWriterEcho
 * 打印日志内容
 * @package spf
 */
class LoggerWriterEcho extends LoggerWriter
{
    function write(LoggerLoggingEvent $event)
    {
        $layout_callback = $this->layout_callback;
        echo $layout_callback($event);
    }

    function write_more($events)
    {
        $layout_callback = $this->layout_callback;
        foreach ($events as $event) {
            echo $layout_callback($event);
        }
    }
}

/**
 * 基于文件的日志写入器
 *
 * @author leon
 */
class LoggerWriterFile extends LoggerWriter
{
    protected $handles;
    protected $path = '/tmp';
    protected $base_name = 'all.log';
    protected $group_as_dir = 1;

    function init()
    {
        $this->run = TRUE;
    }

    function get_handler($group)
    {
        $dir = $this->path;
        if (!isset($this->handles[$group])) {
            if ($this->group_as_dir) {
                $dir .= "/{$group}";
                if (!is_dir($dir)) mkdir($dir, 0777, TRUE);
                $file = "{$this->base_name}";
            } else {
                if ($group) $group = "{$group}-";
                $file = "{$group}{$this->base_name}";
            }
            $this->handles[$group] = fopen("{$dir}/{$file}", 'a+');
        }
        return $this->handles[$group];
    }

    function write(LoggerLoggingEvent $event)
    {
        if (!$this->run) $this->init();
        $layout_callback = $this->layout_callback ?: 'spf\logger_layout_common';
        $messages = $layout_callback($event, FALSE);
        $handler = $this->get_handler($event->group);
        if ($handler) fwrite($handler, $messages);
    }

    function write_more($events)
    {
        $msgs = array();
        if (is_array($events)) {
            foreach ($events as $type => $arr) {
                foreach ($arr as $event) {
                    $msgs[$event->group][] = $this->render_event($event, FALSE);
                }
            }
            foreach ($msgs as $group => $message) {
                $handler = $this->get_handler($group);
                if ($handler) fwrite($handler, implode('', $message));
            }
        }
        return TRUE;
    }

    function render_event(LoggerLoggingEvent $event)
    {
        $layout_callback = $this->layout_callback;
        if (is_string($layout_callback)) {
            $messages = $layout_callback($event, FALSE);
        } else {
            $messages = call_user_func_array($layout_callback, array($event));
        }
        return $messages;
    }

    function __destruct()
    {
        if ($this->run && $this->handles) {
            foreach ($this->handles as $handle) fclose($handle);
        }
    }
}

/**
 * 基于scribe的日志写入器
 *
 * @author leon
 *
 */
class Logger_Writer_Scribe extends LoggerWriter
{

    protected $transport;

    protected $scribe_client;

    protected $host;

    protected $port = 1463;

    public function init()
    {
        $GLOBALS['THRIFT_ROOT'] = THRIFT_ROOT;
        include_once THRIFT_ROOT . '/scribe.php';
        include_once THRIFT_ROOT . '/transport/TSocket.php';
        include_once THRIFT_ROOT . '/transport/TFramedTransport.php';
        include_once THRIFT_ROOT . '/protocol/TBinaryProtocol.php';
        $socket = new TSocket($this->host, $this->port, true);
        $this->transport = new TFramedTransport($socket);
        // $protocol = new TBinaryProtocol($trans, $strictRead=false,
        // $strictWrite=true)
        $protocol = new TBinaryProtocol($this->transport, false, false);
        // $scribe_client = new scribeClient($iprot=$protocol, $oprot=$protocol)
        $this->scribe_client = new scribeClient($protocol, $protocol);
        $this->transport->open();
        $this->run = TRUE;
    }

    public function write(LoggerLoggingEvent $events)
    {
        if (!$this->run) $this->init();
        $layout_callback = $this->layout_callback;
        $msg = array(
            'category' => $events->group,
            'message' => $layout_callback($events)
        );
        $messages = array(
            new LogEntry($msg)
        );
        return $this->scribe_client->Log($messages);
    }

    public function write_more($events)
    {
        if (!$this->run) $this->init();
        $layout_callback = $this->layout_callback;
        $messages = array();
        foreach ($events as $type => $arr) {
            if (!$arr) continue;
            foreach ($arr as $event) {
                if ($event->numLevel < Logger::$threshold) continue;
                $messages[] = new LogEntry(array(
                    'category' => $event->group,
                    'message' => $layout_callback($event)
                ));
            }
        }
        return $this->scribe_client->Log($messages);
    }

    public function __destruct()
    {
        if ($this->run) $this->transport->close();
    }
}
