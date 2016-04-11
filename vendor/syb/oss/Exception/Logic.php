<?php
namespace syb\oss\Exception;
class Logic extends \LogicException
{
    const invalidRef = -99999;
    const invalidCsrf = -99997;
    const getConfig = -90110;
    const setConfig = -90100;
    const initDb = -92000;
    const loadFile = -90000;
    protected $showMessage = '系统繁忙,请稍后再试';
    protected $errno = 0;
    protected $enableLog = FALSE;

    function __construct($type,$message, $showMessage = '',$enableLog, \Exception $previous = NULL)
    {
        $this->errno = $type;
        if ($showMessage) $this->showMessage = $showMessage;
        if ($enableLog) $this->enableLog = $enableLog ? TRUE : FALSE;
        parent::__construct($message, $this->errno);
    }
    function getShowData()
    {
        return ['code' => $this->getCode(), 'msg' => $this->showMessage];
    }
}