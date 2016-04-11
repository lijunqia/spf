<?php
namespace spf;
class WebApp
{
	const outputTypeJson = 1;
	const outputTypeHtml = 2;
	protected $outputType = 2;
	/**
	 * @var \swoole_http_request
	 */
	protected $request;
	/**
	 * @var \swoole_http_response
	 */
	protected $response;
	/**
	 * @var \Loader
	 */
	protected $loader;
	protected $autoload_paths;
	public $name;
	public $config;

	function __construct(\swoole_http_request $request, \swoole_http_response $response, array $config)
	{
		$this->request = $request;
		$this->response = $response;
		$this->config = $config;
		//\set_error_handler([$this, 'errorHandler']);
		//\set_exception_handler([$this, 'exceptionHandler']);
		\register_shutdown_function([$this, 'fatalHandler']);
		$this->loader = \Loader::get_instance();
		//请求目录下的类库自动加载
		$this->autoload_paths = $this->loader->autoload_paths;
		$this->run();
	}

	function __destruct()
	{
		spl_autoload_unregister([$this, 'autoload']);
		unset($this->config, $this->request, $this->response, $this->autoload_paths);
	}

	function run()
	{
		$request = $this->request;
		$request_uri = trim($request->server['request_uri'], '/ ');
		if (strrchr($request_uri, '.') !== '.php') {
			$request_uri .= "/index.php";
		}
		$file = $this->loader->find_file($request_uri, [$this->config['root']]);
		if ($file===FALSE) {
			return $this->error404();
		}
		$this->autoload_paths [] = dirname(dirname($file)) . DIRECTORY_SEPARATOR . 'vendor';
		\spl_autoload_register([$this, 'autoload'], true, true);//添加到队列之首
		if (interface_exists('\Throwable')) {
			try {
				$this->run_file($file);
			} catch (\Throwable $e) {
				$this->exceptionHandler($e);
			}
		} else {
			try {
				$this->run_file($file);
			} catch (\Exception $e) {
				$this->exceptionHandler($e);
			}
		}
	}

	protected function run_file($file)
	{
		ob_start();
		include $file;
		$out = ob_get_clean();
		$this->output($out);
	}

	function setOutputType($type = self::outputTypeHtml)
	{
		$this->setOutputType($type);
	}

	function autoload($class)
	{
		$file = $this->loader->find_class($class, $this->autoload_paths);
		if ($file) {
			return include $file;
		} else {
			return false;
		}
	}

	function outputJson($msg, $code = 0)
	{
		$this->response->header('Content-type', 'application/json');
		$this->output(json_encode(['code' => 0, 'msg' => $msg], JSON_UNESCAPED_UNICODE));
		$this->response->end(json_encode(['code' => 0, 'msg' => $msg], JSON_UNESCAPED_UNICODE));
	}

	function outputHtml($content)
	{
		$response = $this->response;
		if (isset($response->finished)) {
			echo "repeat out\n";
			return;
		}
		$response->finished = true;
		$response->end($content . $this->getTimer());
	}

	function output($content = '', $type = self::outputTypeJson)
	{
		$response = $this->response;
		if (isset($response->finished)) {
			echo "repeat out\n";//FIXME::
			return;
		}
		//$response->finished = true;
		if($type!==self::outputTypeHtml)$content .= $this->getTimer();
		$response->end($content);
	}

	function getTimer()
	{
		$pased_time = microtime(true) - $this->request->server['request_microtime'];
		$mem = memory_get_peak_usage(true) / 1024 / 1024;
		$str = sprintf("Execute Time: %.6fs, Memory Used: {$mem}MB\n", $pased_time);
		return "<div style=\"text-align:center;color:red;\">{$str}</div>\n";
	}

	function error404()
	{
		$response = $this->response;
		$response->header("Content-Type", "text/html; charset=utf-8");
		$response->status(404);
		return $this->output('<h1>Page Not Found!</h1>');
	}

	function exceptionHandler($e)
	{
		Log::error($e->__toString());
		while (ob_get_level()) {
			ob_end_clean();
		}
//      $this->response->status(500);
		if (\inDev === true) {
			$msg = \spf\ErrorRender::render($e);
		} else {
			$msg = $e->getMessage();
		}
		$this->output($msg);
	}

	function errorHandler($errno, $errmsg, $file, $line)
	{
		//不符合的,返回false,由PHP标准错误进行处理
		//if (!(error_reporting() & $errno))return false;
		$err = new \ErrorException($errmsg, 0, $errno, $file, $line);
		$this->exceptionHandler($err);
		throw $err;
	}

	function fatalHandler()
	{
		$error = \error_get_last();
		if ($error) {
			$this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
		}
	}
}