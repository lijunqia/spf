<?php
namespace syb\cf;
class hello{
    static function test(){
        echo "called in ",__CLASS__,"::test()\n";
    }
    //TODO::get_l5_ip_port/get_server_ip_port/open_varnish/db

    static function is_test_environment()
    {
        $local_ips = array_values(swoole_get_local_ip());
        $dev_ips = ['10.191.15.209', '10.191.16.215', '10.205.2.217', '127.0.0.1'];
        return array_intersect($dev_ips,$local_ips)?true:false;
    }
}