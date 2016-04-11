<?php
//**********************************************************
// File name: CheckTools.class.php
// Class name: CheckTools
// Create date: 2010/10/20
// Update date: 2010/10/20
// Author: garyzou
// Description: check工具源文件
//**********************************************************
namespace syb\oss;
class CheckTools
{
    const DIRTY_WORDS_LIST_FILE = '/usr/local/oss_dev/config/dirty.txt';

    /**
     * 判断是否有限制级词语
     * @param string $src 待判断字符串
     * @param string $dirtyfile 附加限制词语列表文件
     * @return bool true: 有限制级词语 flase: 没有限制级词语
     */
    static function IsDirtyWords($src, $dirtyfile = '')
    {
        $files = $dirtyfile ? [$dirtyfile, DIRTY_WORDS_LIST_FILE] : [DIRTY_WORDS_LIST_FILE];
        foreach ($files as $file) {
            if (!(\is_file($file) || ($handle = \fopen($file, "r")))) continue;
            while (!\feof($handle)) {
                $line = trim(fgets($handle, 32));
                if (empty($line)) continue;
                if (\stristr($src, $line)) return true;
            }
            \fclose($handle);
        }
        return false;
    }

    static function CheckRefererByHost($domain = "qq.com")
    {
        $referer = $_SERVER["HTTP_REFERER"];
        if (!$referer) return false;
        $host = \parse_url($referer, PHP_URL_HOST);
        if (!$host) return false;

        if (\preg_match('/' . $domain . '/', $referer) <= 0) {
            return false;
        }
    }
}
