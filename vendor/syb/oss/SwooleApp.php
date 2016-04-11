<?php
namespace syb\oss;

use spf\Log;

class SwooleApp
{
    const outputTypeJson = 1;
    const outputTypeHtml = 2;
    public $config;
    public $name;
    protected $request;
    protected $outputType = 2;
    /**
     * @var \swoole_http_response
     */
    protected $response;
    protected $autoloadPaths;
    protected $loader;

    function __construct($request, $response, $config)
    {
        $this->request = $request;
        $this->response = $response;
        $this->config = $config;
        \set_error_handler([$this, 'errorHandler']);
        \set_exception_handler([$this, 'exceptionHandler']);
        \register_shutdown_function([$this, 'fatalHandler']);
        $this->loader = $loader = \Loader::get_instance();
        $file = trim($request->server['request_uri'], '/ ');
        if (strrchr($file, '.') !== '.php') $file .= "/index.php";
        $requestFile = $loader->find_file($file);
        if (!$requestFile) return $this->error404();

        //请求目录下的类库自动加载
        $this->autoloadPaths = $loader->autoload_paths;
        $this->autoloadPaths [] = dirname(dirname($requestFile)) . DIRECTORY_SEPARATOR . 'vendor';
        \spl_autoload_register([$this, 'autoload'], true, true);//添加到队列之首
        try {
            ob_start();
            include $requestFile;
            $out = ob_get_clean();
            $this->output($out);
        } catch (\Exception $e) {
            $this->exceptionHandler($e);
        }
    }

    function setOutputType($type = self::outputTypeHtml)
    {
        $this->setOutputType($type);
    }

    function autoload($class)
    {
        $file = $this->loader->find_class($class, $this->autoloadPaths);
        if ($file) {
            return include $file;
        } else {
            return false;
        }
    }

    function __destruct()
    {
        spl_autoload_unregister([$this, 'autoload']);
        unset($this->config, $this->request, $this->response);
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
        $response->finished = TRUE;
        $response->end($content . $this->getTimer());
    }

    function output($content = '', $type = self::outputTypeJson)
    {
        $response = $this->response;
        if (isset($response->finished)) {
            echo "repeat out\n";
            return;
        }
        $response->finished = TRUE;
        $response->end($content . $this->getTimer());
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
        if ($e instanceof \syb\oss\Exception\Logic) {
            $showData = $e->getShowData();

            return $this->outputJson($showData['msg'], $showData['code']);
        }
        Log::error($e->__toString());
        while (ob_get_level()) ob_end_clean();
//      $this->response->status(500);
        if (\inDev === TRUE) {
            $msg = \spf\ErrorRender::render($e);
        } else {
            $msg = $e->getMessage();
        }
        $this->output($msg);
    }

    function errorHandler($errno, $errmsg, $file, $line)
    {
        //不符合的,返回false,由PHP标准错误进行处理
        if (!(error_reporting() & $errno)) return FALSE;
        $this->exceptionHandler(new \ErrorException($errmsg, 0, $errno, $file, $line));
    }

    function fatalHandler()
    {
        $error = \error_get_last();
        if ($error) {
            $this->errorHandler($error['type'], $error['message'], $error['file'], $error['line']);
        }
//      exit(1);
    }


}