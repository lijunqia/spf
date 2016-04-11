<?php
namespace syb\oss;
class JSONExt
{
    function __construct()
    {
    }

    public static function json_ext_encode($ret = 0, $errmsg = '', $data = NULL)
    {
        $ret = ['ret'=>$ret,'errmsg'=>$errmsg];
        if (\is_array($data)) {
            $ret['rows'] = \count($data);
            $ret['data'] = $data;
        }
        return \json_encode($ret,JSON_UNESCAPED_UNICODE);
    }

    public static function write2jsfile($jsfile, $varName, $jsonData)
    {
        $infoStart = "//build start " . \date('Y-m-d H:i:s') . "\n";
        $infoEnd = "\n//build end \n";
        \file_put_contents($jsfile, $infoStart . "\nvar " . $varName . " = " . $jsonData . ";\n" . $infoEnd);
    }
}