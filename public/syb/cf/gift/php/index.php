<?php
//const MONITOR_CONFIG_NEED_REPORT = false;

//同时校验参数act_id
$login = new syb\logic\Login($request);//注意 $request/$respone从上下文可获取

//$uid = $login->get_user_para();
//test_foo();
//throw new \Exception('hello world exeption.');
//echo $_GET['test'];
echo '<h1>SPF Demo ', rand(1111, 9999), '</h1><pre>';
//print_r($this);
//no_exist_func();
syb\cf\hello::test();
print_r([get_included_files(),$request]);




/*try {

    $uin = $this->uin;
    $this->load->base ( "input", "input" );
    $activityid = $this->activityid;
    $queryer = 2;//查询人，1-自己，uid有效，2-朋友，shareid有效
    $shareid = $this->input->get("shareid");
    $user_shareid = $this->input->get("user_shareid");
    $ip_port = $this->get_server_ip_port ( $this->activitysvr_ip_node );
    MONITOR_U_START("0x36110x35queryinvite");
    //dlsll llas
    $ret = activitysvr_pao_diary_queryinviteresultreq($this->uin, $this->appid, $ip_port["ip"], $ip_port["port"], $this->time_out , $activityid, $queryer, $uid, $shareid);
    MONITOR_U_STOP("0x36110x35queryinvite", $ret["errno"]);
    $ret["code"] = $ret["errno"];
    if($ret["errno"] != 0){
        OSS_ERROR ( "activitysvr_pao_diary_queryinviteresultreq[uin:$this->uin,errno: ".$ret['errno'].", errinfo: ".$ret['error'].",cmd:".$ret['cmd'].",uid_arr:".json_encode($uid_arr).",shareid:".$shareid." \n" );
        throw new Exception("系统繁忙,请稍后再试[".$ret['cmd']."|".$ret['errno']."]", -($ret['errno']+93400));
    }
    //通过user_shareid 拉去首次参与者的昵称和头像
    $ip_port = $this->get_server_ip_port("syb_common_shareid");
    $ret_shareid = shareidsvr_getshareiddatareq($this->uin, $this->appid, $ip_port["ip"], $ip_port["port"], $this->time_out , $user_shareid);
    if($ret_shareid["errno"] != 0){
        OSS_ERROR ( "shareidsvr_getshareiddatareq[uin:$this->uin,errno: ".$ret_shareid['errno'].", errinfo: ".$ret_shareid['error'].",cmd:".$ret_shareid['cmd'].",shareid: ".$user_shareid."\n" );
        throw new Exception("系统繁忙,请稍后再试[".$ret_shareid['cmd']."|".$ret_shareid['errno']."]", -($ret_shareid['errno']+93400));
    }
    $data = $ret_shareid['data']['ext4_str'];
    $user_info_arr = json_decode($data,true);
    $user_info_arr['nick'] = htmlspecialchars($user_info_arr['nick']);
    $ret["user_info"] = $user_info_arr;
    $this->output_data($ret);
} catch ( Exception $e ) {
    $this->set_p_code ( $e->getCode () );
    $this->output_data ( array ("code" => $e->getCode (), "msg" => $e->getMessage () ) );
    return;
}*/