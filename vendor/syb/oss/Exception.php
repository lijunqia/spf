<?php
namespace syb\oss;
class Exception extends \Exception
{
    const DEFAULT_USER_VISIBLE_MSG = "系统繁忙，请稍后再试！";

    public function __construct($message, $code = 0)
    {
        $this->file = basename($this->file);
        parent::__construct($message, $code);
    }
    public function __toString()
    {
        return "{errno:" . $this->code . ",errmsg:'" . addcslashes($this->message) . '\'}';
    }
    public function GetUserVisibleMsg()
    {
        return self::DEFAULT_USER_VISIBLE_MSG;
    }

}
