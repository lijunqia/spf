<?php
namespace phplib;

use spf\Log;

class WebApp
{
    function init()
    {
        \set_error_handler([$this, 'errorHandler']);
        \set_exception_handler([$this, 'exceptionHandler']);
    }

    function errorHandler($errno, $errstr, $file, $line)
    {
        if (!(error_reporting() & $errno)) return FALSE;//不符合的,进行PHP标准错误处理
        throw new \ErrorException($errstr, 0, $errno, $file, $line);
    }

    function exceptionHandler($e)
    {
        Log::error($e->__toString());
        if (\inDev === TRUE) {
            $msg = \spf\ErrorRender::render($e);
        } else {
            $msg = $e->getMessage();
        }
        $this->output($msg);
        return TRUE;
    }

    function run()
    {

    }

    function output($congent = '')
    {
    }
}