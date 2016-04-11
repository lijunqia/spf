<?php
namespace syb\oss\Exception;
class Api extends Logic
{
    protected $showMessage = '系统繁忙,请稍后再试';
    protected $result = [];
    protected $postfix = '';

    function __construct($apiReturn, $message, $showMessage = '', $enableLog = TRUE, \Exception $previous = NULL)
    {
        $this->errno = $errno = $apiReturn['errno'];
        $cmd = (isset($apiReturn['cmd'])) ? $apiReturn['cmd'] . '|' : '';
        $postfix = $this->postfix = "[{$cmd}{$errno}]";
        parent::__construct($message . $postfix, $showMessage, $enableLog, $previous);
    }

    function getShowData()
    {
        return ['code' => $this->getCode(), 'msg' => $this->showMessage . $this->postfix];
    }
}