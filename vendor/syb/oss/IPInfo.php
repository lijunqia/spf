<?php
//**********************************************************
// File name: IPInfo.class.php
// Class name: IPInfo
// Create date: 2011/11/03
// Update date: 2011/11/03
// Author: parkerzhu
// Description: 封装IP类
//**********************************************************
namespace syb\oss;
/**
 * IPInfo类，封装IP信息
 * @author parkerzhu
 *
 */

class IPInfo {
    public $ip;
    public $port;

    public function __construct($ip = "", $port = 0) {
        $this->ip = $ip;
        $this->port = intval($port);
    }
}
