<?php
//**********************************************************
// File name: StringTools.class.php
// Class name: StringTools
// Create date: 2011/12/15
// Update date: 2011/12/15
// Author: parkerzhu
// Description: 字符串工具
//**********************************************************
namespace syb\oss;

use \ctype_alnum;
use \substr;

class StringTools
{
    /*!
     * \brief 16进制解码
     * \param[in] src:待解码字符串
     * \return 解码后的字符串
     */
    public static function HexDecode($src, $decorator = "%")
    {
        $res = "";
        $len = strlen($src);
        for ($i = 0; $i < $len; ++$i) {
            if ($decorator == "") {
                if (($len - $i) >= 2 && ctype_alnum($src[$i]) && ctype_alnum($src[$i + 1])) {
                    $res .= pack("H*", substr($src, $i, 2));
                    ++$i;
                } else {
                    $res .= $src[$i];
                }
            } else if ($src[$i] == $decorator && ($len - $i) > 2) {
                if (ctype_alnum($src[$i + 1]) && ctype_alnum($src[$i + 2])) {
                    ++$i;
                    $res .= pack("H*", substr($src, $i, 2));
                    ++$i;
                } else {
                    $res .= $decorator;
                }
            } else {
                $res .= $src[$i];
            }
        }
        return $res;
    }

    static function substrUTF8($str, $length = 80, $need_point = TRUE)
    {

        $all_len = strlen($str);
        $str_len = 0;
        for ($i = 0; $i < $all_len;) {
            if (ord($str[$i]) > 127) {
                $i += 3;
                $str_len += 2;
            } else {
                $i += 1;
                $str_len += 1;
            }
        }
        if ($length >= $str_len) {
            return $str;
        }

        $i = 0;
        $cur_len = 0;
        for (; $cur_len < $length - 2;) {
            if ($i > $all_len)
                break;
            if (ord($str[$i]) > 127) {
                $i += 3;
                $cur_len += 2;
            } else {
                $i += 1;
                $cur_len += 1;
            }
        }
        $end_pos = $i;
        if ($need_point) {
            return substr($str, 0, $end_pos) . '...';
        }
        return substr($str, 0, $end_pos);
    }

    /**
     * 截取字符串  length是英文字的长度，一个中文字相当于两个英文字
     * @param $str
     * @param int $length
     * @param bool $need_point
     * @return string|substr
     */
    static function substrUTF8New($str, $length = 80, $need_point = TRUE)
    {

        $all_len = strlen($str);
        $str_len = 0;
        for ($i = 0; $i < $all_len;) {
            $ord = ord($str[$i]);
            if ($ord < 192) {
                $i += 1;
                $str_len += 1;
            } elseif ($ord < 224) {
                $i += 2;
                $str_len += 2;
            } else {
                $i += 3;
                $str_len += 2;
            }
        }
        if ($length >= $str_len) {
            return $str;
        }

        $i = 0;
        $cur_len = 0;
        for (; $cur_len < $length;) {
            if ($i > $all_len)
                break;
            $ord = ord($str[$i]);
            if ($ord < 192) {
                $i += 1;
                $cur_len += 1;
            } elseif ($ord < 224) {
                $i += 2;
                $cur_len += 2;
            } else {
                $i += 3;
                $cur_len += 2;
            }
        }
        $end_pos = $i;
        if ($need_point) {
            return substr($str, 0, $end_pos) . '...';
        }
        return substr($str, 0, $end_pos);
    }
}
