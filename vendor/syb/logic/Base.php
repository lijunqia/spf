<?php
namespace syb\logic;
abstract class Base{
	/**
	 * 性能上报
	 * @var Monitor
	 */
	protected $monitor;
	public function __construct()
	{
		$this->monitor = Monitor::instance();
	}
}