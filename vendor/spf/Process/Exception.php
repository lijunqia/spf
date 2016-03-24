<?php
namespace spf\Process;
use \Exception;
class ProcessException extends Exception{
	function __construct($msg,$ret, Exception $previous)
	{

		parent::__construct($message, $code, $previous);
	}
}