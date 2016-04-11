<?php
namespace syb\oss;

use \syb\oss\Exception\Common;

//TODO::修改该类中的全局变量为request变量
class Request
{
    /**
     * @var \swoole_http_request
     */
    protected $request;

    function __construct(\swoole_http_request $request)
    {

    }

    /**
     * The is_referer_valid function
     * This function is to verify whether is valid reference
     * @param void
     * @return bool
     */
    function is_referer_valid()
    {
        $referer = $_SERVER["HTTP_REFERER"];//TODO::使用request
        if ($referer) {
            $referers = parse_url($referer);
            $host = strtolower($referers['host']);
            $valid_domain = ".qq.com";
            if (substr($host, -strlen($valid_domain)) == $valid_domain) {
                return TRUE;
            }
        }
        throw new Common(Common::invalidRef, '访问引用地址非法:' . $referer, 'no permission');
    }

    /**
     * The is_csrf_valid function
     *
     * This function is to verify whether is valid csrf
     *
     */
    static function is_csrf_valid()
    {
        $is_valid = self::CHECK_CSRF();
        if (!$is_valid) {
            throw new Common(Common::invalidCsrf, 'csrc check failed', 'no permission');
        }
        return $is_valid;
    }

    static function CHECK_CSRF()
    {
        $gTk = false;
        $key = $_COOKIE['skey'] ? $_COOKIE['skey'] : self::getAccessToken();
        if (!empty($_GET['g_tk'])) {
            $gTk = (self::CAL_CSRF_TOKEN($key) == $_GET['g_tk']);
        } else if (!empty($_POST['g_tk'])) {
            $gTk = (self::CAL_CSRF_TOKEN($key) == $_POST['g_tk']);
        }
        return $gTk;
    }

    /**
     * The CAL_CSRF_TOKEN function
     * This function is to get the value of csrf
     * @param string $str the value of skey
     * @return int    the value of csrf
     */
    static function CAL_CSRF_TOKEN($str)
    {
        $hash = 5381;
        $len = strlen($str);
        for ($i = 0; $i < $len; ++$i) {
            $hash = (int)(($hash << 5 & 0x7fffffff) + ord($str{$i}) + $hash);
        }
        return $hash & 0x7fffffff;
    }

    static function getAccessToken()
    {
        foreach ($_COOKIE as $key => $val) {
            if (strpos($key, "access_token") !== false) {
                return $val;
            }
        }
        return null;
    }
}