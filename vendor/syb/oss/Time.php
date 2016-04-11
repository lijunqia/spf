<?php
//**********************************************************
// File name: DateTime.class.php
// Class name: DateTime
// Create date: 2011/03/25
// Update date: 2011/03/25
// Author: samsonsheng
// Description: 时间计算
// Example:
//**********************************************************
namespace syb\oss;
class Time
{
    /**
     * 获取当前格式化时间,纳秒级
     * @return string 当前格式化时间字符串
     */
    public static function GetCurTime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return strftime('%Y-%m-%d %H:%M:%S', $sec) . ':' . $usec * 1000 * 1000;
    }
}