<?php

//**********************************************************
// File name: CgiOutput.class.php
// Class name: 日志记录类
// Create date: 2009/04/30
// Update date: 2009/04/30
// Author: garyzou
// Description: CGI输出操作类
// Example:
//**********************************************************

namespace syb\oss;
// 当前窗口
define('WT_SELF', 0);
// 父窗口
define('WT_PARENT', 1);
// 最上层窗口
define('WT_TOP', 2);

/**
 * 将WindowType化为字符串
 * @param $iWinType int 窗口类型
 * @return string 转化后的字符串
 */
function ConvertWinowTypeToString($iWinType)
{
    switch ($iWinType) {
        case WT_SELF:
            return "self";
        case WT_TOP:
            return "top";
        case WT_PARENT:
            return "parent";
        default:
            return "self";
    }
}


/*! \brief CGI输入操作类
 */

class CGIOutput
{
    /**
     * 缺省content-type
     */
    const DEFAULT_CONTENT_TYPE = "text/html";
    /**
     * 缺省域名
     */
    const DEFAULT_DOMAIN = "qq.com";
    /**
     * @var bool
     */
    private $m_bIsHeaderEnd;
    /**
     * @var  CGI输出的UNIX描述符
     */
    private $m_fd;
    /**
     * @var 流输出器
     */
    private $m_pOutputStream;
    /**
     * @var array 存储输出到浏览器的头信息
     */
    private $m_mHeadInfo;

    /**
     * 设置mime头
     * @param string $sContentType 设置的头类型
     * @return int 0:成功 -1:失败
     */
    public function SetContentType($sContentType = self::DEFAULT_CONTENT_TYPE)
    {
        return $this->SetHeaderContent("Content-Type", $sContentType);
    }

    /**
     * 设置浏览器cache超时时间
     * @param int $iSecond 超时秒数
     * @return int 0:成功 -1:失败
     */
    public function SetCacheSec($iSecond = 3600)
    {
        return $this->SetHeaderContent("Cache-Control", "max-age=" . $iSecond);
    }

    /**
     * 设置cookie
     * @param string $sName cookie name
     * @param string $sValue cookie value
     * @param int $iExpireSec 超时秒数
     * @param string $sDomain 域名
     * @param string $sPath 目录
     * @param bool $bSecure 是否使用加密
     * @return int 0:成功 -1:失败
     * @throws Exception
     */
    public function SetCookie($sName, $sValue, $iExpireSec = 0, $sDomain = NULL, $sPath = "/", $bSecure = false)
    {
        if ($sDomain == NULL)
            $sDomain = $_SERVER["SERVER_NAME"];
        $sNameRep = $sName;
        $sNameRep = str_replace(";", "%3B", $sNameRep);
        $sNameRep = str_replace("=", "%3D", $sNameRep);
        $ssCookie = $sNameRep . "=" . CookieCoder::Encode($sValue);

        if ($iExpireSec != 0) {
            $tTime = time() + $iExpireSec;
            $ssCookie .= gmdate("%a, %d %b %Y %H:%M:%S %Z", $tTime);
        }

        if (!$sPath != "") {
            $ssCookie .= " path=" . $sPath . ";";
        }

        if (!$sDomain != "") {
            $ssCookie .= " domain=" . $sDomain . ";";
        }

        if ($bSecure) {
            $ssCookie .= " secure;";
        }

        return $this->HandleHeader("Set-Cookie", $ssCookie);
    }


    /**
     * 清除cookie
     * 出错时是抛异常还是返回结果值是由库编译宏 OSS_NO_EXCEPTIONS 决定的
     * @param string $sName cookie name
     * @param null $sDomain 域名
     * @param string $sPath 目录
     * @return bool 0:成功 -1:失败
     */
    public function ClearCookie($sName, $sDomain = NULL, $sPath = "/")
    {
        if ($sDomain == NULL)
            $sDomain = $_SERVER["SERVER_NAME"];
        return SetCookie($sName, "", -86400 * 365, $sDomain, $sPath);
    }

    /**
     * 设置自定义http头
     * 出错时是抛异常还是返回结果值是由库编译宏 OSS_NO_EXCEPTIONS 决定的
     *
     * @param string $sName HTTP Response Name
     * @param string $sValue HTTP Response Value
     * @throws Exception
     * @return bool 0:成功 -1:失败
     */
    public function SetHeader($sName, $sValue)
    {
        if ($this->m_bIsHeaderEnd == true) {
            throw new Exception("set http header [$sName: $sValue] error.\n");
        }
        return $this->SetHeaderContent($sName, $sValue);
    }

    /**
     * 输出Http Response Head 结束标志
     * @return string
     * @throws Exception
     */
    public function EndHeader()
    {
        if ($this->m_bIsHeaderEnd == false) {
            foreach ($this->m_mHeadInfo as $key => $value) {
                $this->HandleHeader($key, $value);
            }
            unset($this->m_mHeadInfo);
            $this->m_pOutputStream .= "\r\n";
            $this->m_bIsHeaderEnd = true;
            return $this->m_pOutputStream;
        }
        return '';
    }

    /**
     * 取cgi output stream
     * @return string header头字符串
     */
    public function GetOutputStream()
    {
        return $this->EndHeader();
    }

    /**
     * 输出alert. 此函数会将sMsg的内容自动做Script转义
     * @param string $sMsg sMsg 需要输出的msg
     */
    public function MessageBox($sMsg)
    {
        //echo "asdfasd fasdf"."<br/>";
        //echo $sMsg."<br/>";
        $out = $this->GetOutputStream();
        //$sMsg = oss_iconv(ICONV_FROM, ICONV_TO, $sMsg);
        $out .= "<script>alert('" . $sMsg . "');</script>\r\n";
        echo $out;
    }

    /**
     * 输出script跳转到指定url
     * @param string $sUrl 跳转的url。如果sUrl==""则调用GoHistory(iWinType);
     * @param int $iWinType 跳转的窗口类型，见 enum WindowType
     */
    public function GoUrl($sUrl = "", $iWinType = WT_SELF)
    {
        if ($sUrl == "") {
            $this->GoHistory(-1, $iWinType);
            return;
        }
        $out = $this->GetOutputStream();
        $out .= "<script>location.href='" . $sUrl;
        $StartPos = strpos($sUrl, "?", 0);
        if ($StartPos === false) {
            $out .= "?PcacheTime=" . time();
        } else {
            $out .= "&PcacheTime=" . time();
        }

        $out .= "'</script>\r\n";
        echo $out;
        return;
    }

    /**
     * 输出script go history
     * @param $iIndex 同 history.go()的参数，如果iIndex==-1则代表返回前一页
     * @param int $iWinType 跳转的窗口类型，见 enum WindowType
     */
    public function GoHistory($iIndex, $iWinType = WT_SELF)
    {
        $out = $this->GetOutputStream();
        $out .= "<script>" . ConvertWinowTypeToString($iWinType) . ".history.go(" . $iIndex . ");</script>\r\n";
        echo $out;
        return;
    }

    /**
     * 输出script关闭本窗口
     *
     * @param int $iWinType iWinType 关闭的窗口类型，见 enum WindowType
     */
    public function CloseWindow($iWinType = WT_SELF)
    {
        $out = $this->GetOutputStream();
        $out .= "<script>" . ConvertWinowTypeToString($iWinType) . ".close();</script>\r\n";
        echo $out;
        return;
    }

    /**
     * 输出script刷新窗口
     * @param int $iWinType 刷新的窗口类型，见 enum WindowType
     * @param bool $bUseCache 是否使用cache策略。false 忽略所有的cache策略
     */
    public function Refresh($iWinType = WT_SELF, $bUseCache = true)
    {
        $out = $this->GetOutputStream();
        if ($bUseCache) {
            $out .= "<script>" . ConvertWinowTypeToString($iWinType) . ".location=";
            $out .= ConvertWinowTypeToString($iWinType) << ".location;";
            $out .= "</script>\r\n";
        } else {
            $out .= "<script>";
            $out .= ConvertWinowTypeToString($iWinType) << ".location.reload(true)";
            $out .= "</script>\r\n";
        }
        echo $out;
        return;
    }

    /**
     * CGIOutput constructor.
     */
    function __construct()
    {
        $this->m_mHeadInfo = array();
    }

    /**
     * CGIOutput destructor.
     */
    function __destruct()
    {
    }

    /**
     * 置设置头信息
     * @param string $sTarget
     * @param string $sValue
     * @return int
     */
    private function SetHeaderContent($sTarget, $sValue)
    {
        $this->m_mHeadInfo[$sTarget] = $sValue;
        //FIXME::这是什么参数,如何修改
        if (array_key_exists($param, $this->m_mHeadInfo)) {
            return 0;
        }
        return -1;
    }

    /**
     * 处理头信息,传输到WEB
     * @param string $sName
     * @param string $sValue
     * @return int
     * @throws Exception
     */
    private function HandleHeader($sName, $sValue)
    {
        if ($this->m_bIsHeaderEnd == true) {
            throw new Exception("set http header [$sName: $sValue] error.\n");
        }
        $this->m_pOutputStream .= $sName . ": " . $sValue . "\r\n";
        return 0;
    }
}

class CookieCoder
{
    /**
     * Cookie编码，对应于javascript的escape。考虑到兼容性问题，对所有双字节的字符不进行编码
     * @param string $str 待编码的字符串
     * @return string 编码后的字符串
     */
    public static function Encode($str)
    {
        preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/", $str, $r);
        $ar = $r[0];
        foreach ($ar as $k => $v) {
            if (ord($v[0]) < 128) {
                $ar[$k] = rawurlencode($v);
            } else {
                $ar[$k] = "%u" . bin2hex(iconv("GB2312", "UCS-2", $v));
            }
        }
        return join("", $ar);
    }

    /**
     * Cookie解码，对应于javascript的unescape
     * @param string $sSrc 待解码的字符串
     * @return string Cookie解码后的字符串
     */
    public static function Decode($sSrc)
    {
        $str = rawurldecode($sSrc);
        preg_match_all("/(?:%u.{4})|.+/", $str, $r);
        $ar = $r[0];
        foreach ($ar as $k => $v) {
            if (substr($v, 0, 2) == "%u" && strlen($v) == 6) {
                $ar[$k] = iconv("UCS-2", "GB2312", pack("H4", substr($v, -4)));
            }
        }
        return implode('', $ar);
    }
}