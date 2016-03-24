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
class LoggerType{
	const off = 64;
	const fatal = 32;
	const error = 16;
	const warn = 8;
	const info = 4;
	const debug = 2;
	const trace = 1;
	const all = 0;
}
trait Logger
{
	static $threshold = LoggerType::debug;
	protected static $msgs = array();
	protected static $optmizer = 0;
	protected static $writers;
	protected static $running = FALSE;

	public static function trace($msg, $group = '')
	{
		return self::log('trace', $msg, $group);
	}

	public static function debug($msg, $group = '')
	{
		return self::log('debug', $msg, $group);
	}

	public static function info($msg, $group = '')
	{
		return self::log('info', $msg, $group);
	}

	public static function warn($msg, $group = '')
	{
		return self::log('warn', $msg, $group);
	}

	public static function error($msg, $group = '')
	{
		return self::log('error', $msg, $group);
	}

	public static function fatal($msg, $group = '')
	{
		return self::log('fatal', $msg, $group);
	}

	/**
	 * 初始化日志类
	 */
	public static function init($conf = NULL)
	{
		if (self::$running) return TRUE;
		if (!$conf) $conf = static::load_config();
		if (!$conf) throw new \Exception("Logger config is not set");
		self::$optmizer = !empty($conf['optmizer']) ? TRUE : FALSE;
		self::$threshold = !empty($conf['threshold']) ? constant("\\spf\\LoggerType::{$conf['threshold']}") : self::ALL;
		foreach ($conf['writer'] as $writer_name => $conf) {
			$writer_class = __NAMESPACE__ . "\\LoggerWriter{$writer_name}";
			if (!class_exists($writer_class))throw new \Exception("Wrong Logger writer name defined, the class {$writer_class} not found!");
			self::$writers[] = new $writer_class($conf);
		}
		if (self::$optmizer)register_shutdown_function(array(__CLASS__, 'flush'));
		self::$running = TRUE;
	}

	protected static function log($type, $msg, $group = 'default')
	{
		if (!self::$running) self::init();
		$threshold = constant("\\spf\\LoggerType::{$type}");
		$backtrace = debug_backtrace();
		$event = new LoggerLoggingEvent($type, $msg, $group, $threshold);
		if (!self::$optmizer) {
			if ($threshold < self::$threshold) return FALSE;
			self::write($event);
		} else {
			self::$msgs[ $type ][] = $event;
		}
	}

	protected static function write($event)
	{
		//低于日志等级，不记录
		if ($event instanceof LoggerLoggingEvent && ($event->numLevel < self::$threshold)) return TRUE;
		if(empty(self::$writers))return;
		foreach (self::$writers as $writer) {
			$writer->write($event);
		}
	}

	public static function flush()
	{
		$msgs = self::$msgs;
		if (empty($msgs)) return TRUE;
		foreach (self::$writers as $writer) {
			$writer->write_more($msgs);
		}
		self::$msgs = array();
	}
}

/**
 * 日志事件
 *
 * @author leon
 */
class LoggerLoggingEvent
{
	public $level;
	public $msg;
	public $group;
	public $microtime;
	public $threadName;
	public $numLevel;
	public $host;

	public function __construct($level, $msg, $group, $numLevel)
	{
		$this->microtime = microtime(TRUE);
		$this->level = $level;
		$this->msg = $msg;
		$this->group = $group;
		$this->threadName = (string)getmypid();
		$this->numLevel = $numLevel;
		$this->host = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
	}
}

/**
 * 日志格式化器
 *
 * @param $event       LoggerLoggingEvent
 * @param $withGroup   bolean
 */
function logger_layout_common(LoggerLoggingEvent $event, $withGroup = FALSE)
{
	$date = date('c', $event->microtime);
	$level = $event->level;
	$microtime = sprintf("%8f", $event->microtime);
	$group = $withGroup ? " GRP:{$event->group}" : '';
	$msg = $event->msg;
	if (is_array($msg)) {
		$msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
	} elseif (is_object($msg)) {
		$msg = serialize($msg);
	}
	return "{$date} [host: {$event->host} pid:{$event->threadName} {$group} {$level}:] {$msg} \r\n";
}

/**
 * 日志写入器基类
 *
 * @author leon
 */
abstract class LoggerWriter
{
	protected $layout_callback = 'logger_layout_common';
	protected $run = FALSE;

	public function __construct($options = array())
	{
		if ($options) {
			foreach ($options as $k => $v) {
				$this->$k = $v;
			}
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

	public function init()
	{
		$this->run = TRUE;
	}

	public function get_handler($group)
	{
		$dir = $this->path;
		if (!isset($this->handles[ $group ])) {
			if ($this->group_as_dir) {
				$dir .= "/{$group}";
				if (!is_dir($dir)) mkdir($dir, 0777, TRUE);
				$file = "{$this->base_name}";
			} else {
				if ($group) $group = "{$group}-";
				$file = "{$group}{$this->base_name}";
			}
			$this->handles[ $group ] = fopen("{$dir}/{$file}", 'a+');
		}
		return $this->handles[ $group ];
	}

	public function write(LoggerLoggingEvent $event)
	{
		if (!$this->run) $this->init();
		$layout_callback = $this->layout_callback?:'spf\logger_layout_common';
		$messages = $layout_callback($event, FALSE);
		$handler = $this->get_handler($event->group);
		if ($handler) fwrite($handler, $messages);
	}

	public function write_more($events)
	{
		$msgs = array();
		if (is_array($events)) {
			foreach ($events as $type => $arr) {
				foreach ($arr as $event) {
					$msgs[ $event->group ][] = $this->render_event($event, FALSE);
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
			$messages = call_user_func_array($layout_callback, array($event, FALSE));
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