<?php
namespace spf;
class ErrorHandler
{
	static function get_error_type(\ErrorException $e)
	{
		$exit = FALSE;
		$severity = $e->getSeverity();
		switch ($severity) {
			case E_ERROR:
				$type = 'Fatal Error';
				$exit = TRUE;
				break;
			case E_USER_ERROR:
				$type = 'User Error';
				$exit = TRUE;
				break;
			case E_PARSE:
				$type = 'Parse Error';
				$exit = TRUE;
				break;
			case E_WARNING:
				$type = 'Warning';
				break;
			case E_USER_WARNING:
				$type = 'User Warning';
				break;
			case E_USER_NOTICE:
				$type = 'User Notice';
				break;
			case E_NOTICE:
				$type = 'Notice';
				break;
			case E_STRICT:
				$type = 'Notice';
				break;
			case E_RECOVERABLE_ERROR:
				$type = 'Recoverable Error';
				break;
			default:
				$type = 'Unknown Error';
				$exit = TRUE;
		}
		return array($type,$exit);
	}

	/**
	 * 异常处理
	 */
	static function handle($e)
	{
		try {
			\Log::error($e->__toString());
			if (PHP_SAPI === 'cli') {
				$error = self::exception_text($e);
				echo $error, "\n";
				exit(1);
			}
			$severity_name = 'Exception';
			if($e instanceof \ErrorException)
			{
				list($severity_name,$exit) = self::get_error_type($e);
			}elseif($e instanceof \Error) {
				$severity_name = 'Error';
			}
			$code = $e->getCode();
			if($code)$severity_name.=":{$code}";
			$code = $severity_name;

			$message = $e->getMessage();
			$trace = $e->getTrace();
			if (!headers_sent()) {
				header('Content-Type: text/html; charset=utf-8', TRUE, 500);//header('HTTP/1.1 500 Internal Server Error');
			}
			if (self::is_ajax()) {
				if (IN_DEV) {
					echo self::exception_text($e);
					exit(1);
				} else {
					exit("\n{$message}\n");
				}
			}
			$flag = include "dist/error.tpl.php";//Include the exception HTML
			if (!$flag) echo self::exception_text($e), "\n";
			return TRUE;
		} catch (\Exception $ee) {
			ob_get_level() && ob_clean();// Clean the output buffer if one exists
			echo $ee->__toString();
			exit(1);//Exit with an error status
		}
	}

	/**
	 * Get a single line of text representing the exception:
	 * Error [ Code ]: Message ~ File [ Line ]
	 *
	 * @param   object  Exception
	 * @return  string
	 */
	static function exception_text($e)
	{
		return sprintf('%s [ %s ]: %s ~ %s [ %d ]', get_class($e), $e->getCode(), strip_tags($e->getMessage()), self::debug_path($e->getFile()), $e->getLine());
	}

	/**
	 * Removes application, system, modpath, or docroot from a filename,
	 * replacing them with the plain text equivalents. Useful for debugging
	 * when you want to display a shorter path.
	 * // Displays SYSPATH/classes/kohana.php
	 * echo self::debug_path(__FILE__);
	 *
	 * @param   string  path to debug
	 * @return  string
	 */
	static function debug_path($file)
	{
		if (strpos($file, VENDOR_PATH) === 0) {
			$file = 'vendor' . substr($file, strlen(VENDOR_PATH));
		} elseif (strpos($file, APP_PATH) === 0) {
			$file = substr($file, strlen(APP_PATH) + 1);
		}
		return $file;
	}

	/**
	 * Returns an HTML string, highlighting a specific line of a file, with some
	 * number of lines padded above and below.
	 * // Highlights the current line of the current file
	 * echo Kohana::debug_source(__FILE__, __LINE__);
	 *
	 * @param   string   file to open
	 * @param   integer  line number to highlight
	 * @param   integer  number of padding lines
	 * @return  string   source of file
	 * @return  FALSE    file is unreadable
	 */
	public static function debug_source($file, $line_number, $padding = 5)
	{
		if (!$file or !is_readable($file)) {
			// Continuing will cause errors
			return FALSE;
		}
		// Open the file and set the line position
		$file = fopen($file, 'r');
		$line = 0;
		// Set the reading range
		$range = array(
			'start' => $line_number - $padding, 'end' => $line_number + $padding
		);
		// Set the zero-padding amount for line numbers
		$format = '% ' . strlen($range['end']) . 'd';
		$source = '';
		while (($row = fgets($file)) !== FALSE) {
			// Increment the line number
			if (++$line > $range['end']) break;
			if ($line >= $range['start']) {
				// Make the row safe for output
				$row = htmlspecialchars($row, ENT_NOQUOTES, 'UTF-8');
				// Trim whitespace and sanitize the row
				$row = '<span class="number">' . sprintf($format, $line) . '</span> ' . $row;
				if ($line === $line_number) {
					// Apply highlighting to this row
					$row = '<span class="line highlight">' . $row . '</span>';
				} else {
					$row = '<span class="line">' . $row . '</span>';
				}
				// Add to the captured source
				$source .= $row;
			}
		}
		// Close the file
		fclose($file);
		return '<pre class="source"><code>' . $source . '</code></pre>';
	}

	/**
	 * Returns an array of HTML strings that represent each step in the backtrace.
	 * // Displays the entire current backtrace
	 * echo implode('<br/>', Kohana::trace());
	 *
	 * @param   string  path to debug
	 * @return  string
	 */
	public static function trace(array $trace = NULL)
	{
		if ($trace === NULL) {
			// Start a new trace
			$trace = debug_backtrace();
		}
		// Non-standard function calls
		$statements = array(
			'include', 'include_once', 'require', 'require_once'
		);
		$output = array();
		foreach ($trace as $step) {
			if (!isset($step['function'])) {
				// Invalid trace step
				continue;
			}
			if (isset($step['file']) and isset($step['line'])) {
				// Include the source of this step
				$source = self::debug_source($step['file'], $step['line']);
			}
			if (isset($step['file'])) {
				$file = $step['file'];
				if (isset($step['line'])) {
					$line = $step['line'];
				}
			}
			// function()
			$function = $step['function'];
			if (in_array($step['function'], $statements)) {
				if (empty($step['args'])) {
					// No arguments
					$args = array();
				} else {
					// Sanitize the file path
					$args = array(
						$step['args'][0]
					);
				}
			} elseif (isset($step['args'])) {
				if (!function_exists($step['function']) or strpos($step['function'], '{closure}') !== FALSE) {
					// Introspection on closures or language constructs in a stack trace is impossible
					$params = NULL;
				} else {
					if (isset($step['class'])) {
						if (method_exists($step['class'], $step['function'])) {
							$reflection = new \ReflectionMethod($step['class'], $step['function']);
						} else {
							$reflection = new \ReflectionMethod($step['class'], '__call');
						}
					} else {
						$reflection = new \ReflectionFunction($step['function']);
					}
					// Get the function parameters
					$params = $reflection->getParameters();
				}
				$args = array();
				foreach ($step['args'] as $i => $arg) {
					if (isset($params[ $i ])) {
						// Assign the argument by the parameter name
						$args[ $params[ $i ]->name ] = $arg;
					} else {
						// Assign the argument by number
						$args[ $i ] = $arg;
					}
				}
			}
			if (isset($step['class'])) {
				// Class->method() or Class::method()
				$function = $step['class'] . $step['type'] . $step['function'];
			}
			$output[] = array(
				'function' => $function, 'args' => isset($args) ? $args : NULL, 'file' => isset($file) ? $file : NULL, 'line' => isset($line) ? $line : NULL, 'source' => isset($source) ? $source : NULL
			);
			unset($function, $args, $file, $line, $source);
		}
		return $output;
	}

	/**
	 * Quick debugging of any variable. Any number of parameters can be set.
	 *
	 * @return  string
	 */
	public static function dump()
	{
		if (func_num_args() === 0) return;
		$params = func_get_args(); // Get params
		$output = array();
		foreach ($params as $var) $output[] = '<pre>(' . gettype($var) . ') ' . htmlspecialchars(print_r($var, TRUE)) . '</pre>';
		return implode("\n", $output);
	}

	/**
	 * 判断是否为ajax调用
	 */
	static function is_ajax()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
	}
}
