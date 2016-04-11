<?php
namespace spf;

class Input
{

    /**
     * 是否开启xss安全监测 TRUE or FALSE
     * @var bool
     */
    private $_enable_xss = TRUE;

    /**
     * hash后的xss字符串
     * @var string
     */
    private $_xss_hash = '';

    protected $get = [];
    protected $post = [];
    protected $cookie = [];
    protected $request = [];
    protected $files = [];
    protected $server = [];

    public function __construct(\swoole_http_request $request = NULL)
    {
        if ($request) {
            isset($request->get) && $this->get =& $request->get;
            isset($request->post) && $this->post =& $request->post;
            isset($request->cookie) && $this->cookie =& $request->cookie;
            $this->request = array_merge($this->get, $this->post, $this->cookie);
            isset($request->files) && $this->files =& $request->files;
            $this->server =& $request->server;//$this->header = $request->header;//merge to server
        } else {
            $this->get =& $_GET;
            $this->post =& $_POST;
            $this->cookie =& $_COOKIE;
            $this->request =& $_REQUEST;
            $this->files =& $_FILES;
            $this->server =& $_SERVER;
        }
    }

    public function get($key = NULL, $default = NULL)
    {
        return $this->get_arr($this->get, $key, $default);
    }

    public function post($key = NULL, $default = NULL)
    {
        return $this->get_arr($this->post, $key, $default);
    }

    public function cookie($key = NULL, $default = NULL)
    {
        return $this->get_arr($this->cookie, $key, $default);
    }

    public function request($key = NULL, $default = NULL)
    {
        return $this->get_arr($this->request, $key, $default);
    }

    /**
     * 从给定数组中检索元素,并安全清理
     * @param $arr
     * @param string $key
     * @param string $default
     * @return string
     */
    public function get_arr($arr, $key = NULL, $default = NULL)
    {
        if ($key === NULL) {
            return $this->security_clean_arr($arr);
        }
        if (isset($arr[$key])) {
            $val = $arr[$key];
            return $this->security_clean(trim($val), $this->_enable_xss);
        } else {
            return $default;
        }
    }

    public function setEnableXss($val = TRUE)
    {
        $this->_enable_xss = $val ? TRUE : FALSE;
    }

    /**
     * 安全清理
     * @param string $str
     * @param bool $force 强制清理
     * @return string
     */
    public function security_clean($str, $force = FALSE)
    {
        $str = trim($str);
        if (!$str || is_numeric($str)) return $str;
        if ($this->_enable_xss || $force) {
            $str = $this->remove_invisible_characters($str);
            $str = rawurldecode($str);
            $str = $this->validate_entities($str);
            $str = $this->remove_invisible_characters($str);
        }
        return $str;
    }

    /**
     * 安全清理一个数组
     * @param $arr
     * @return array
     */
    protected function security_clean_arr($arr)
    {
        foreach ($arr as &$v) {
            $v = $this->security_clean(trim($v), $this->_enable_xss);
        }
        return $arr;
    }

    /**
     * 清理不可见字符
     * @param string $str
     * @param bool $url_encoded
     * @return string
     */
    public function remove_invisible_characters($str, $url_encoded = TRUE)
    {
        $non_displayables = array();

        if ($url_encoded) {
            $non_displayables[] = '/%0[0-8bcef]/';
            $non_displayables[] = '/%1[0-9a-f]/';
        }

        $non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';

        do {
            $str = \preg_replace($non_displayables, '', $str, -1, $count);
        } while ($count);

        return $str;
    }

    /**
     * @param string $str
     * @return    string
     */
    protected function validate_entities($str)
    {

        $str = \preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash() . "\\1=\\2", $str);
        $str = \preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);
        $str = \preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str);
        $str = \str_replace($this->xss_hash(), '&', $str);
        return $str;
    }

    /**
     *
     * get xss hash
     *
     * @return string
     */
    public function xss_hash()
    {
        if ($this->_xss_hash == '') {
            \mt_srand(\hexdec(\substr(\md5(\microtime()), -8)) & 0x7fffffff);
            $this->_xss_hash = \md5(\time() + \mt_rand(0, 1999999999));
        }
        return $this->_xss_hash;
    }
}