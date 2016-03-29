<?php
namespace phplib;
class Route{
    static function parseUri($uri)
    {
        $uri = trim($uri,'/ ');
        $ret = ['m'=>'','c'=>'','a'=>'','args'=>''];
        $arr=$uri?explode('/',$uri,4):[];
        switch(count($arr)) {
            case 4:
                break;
            case 3:
                $arr=array_merge($arr,['']);
                break;
            case 2:
                $arr=array_merge($arr,['index','']);
                break;
            case 1:
                $arr=array_merge($arr,['index','index','']);
                break;
            default:
                $arr=['common','index','index',''];
        }
        list($ret['m'],$ret['c'],$ret['a'],$ret['args']) = $arr;
        $ret['args'] = $ret['args']?explode('/',$ret['args']):[];
        return $ret;
    }
}