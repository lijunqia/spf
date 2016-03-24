<?php
/**
 * 基于scribe的日志写入器
 *
 * @author leon
 *
 */
namespace spf;
class Logger_Writer_Scribe extends Logger_Writer
{

	protected $transport;

	protected $scribe_client;

	protected $host;

	protected $port = 1463;

	public function init ()
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

	public function write (LoggerLoggingEvent $events)
	{
		if (! $this->run) $this->init();
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

	public function write_more ($events)
	{
		if (! $this->run) $this->init();
		$layout_callback = $this->layout_callback;
		$messages = array();
		foreach ($events as $type => $arr)
		{
			if (! $arr) continue;
			foreach ($arr as $event)
			{
				if ($event->numLevel < Logger::$threshold) continue;
				$messages[] = new LogEntry(array(
					'category' => $event->group,
					'message' => $layout_callback($event)
				));
			}
		}
		return $this->scribe_client->Log($messages);
	}

	public function __destruct ()
	{
		if ($this->run) $this->transport->close();
	}
}
