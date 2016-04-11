<?php
use \syb\oss\Exception\Common as CommonException;
$input = new \syb\oss\Input($request);
$syb_bang_info = "syb_bang_info";
// 1:参与活动 2:分享活动 3:进入手游宝 4:下载app 5:手游宝新用户
$qualification_type = (integer)$input->get("qualification_type");
if (!in_array($qualification_type, [1, 2, 3])) {
    throw new CommonException(90901,"qualification_type参数错误",'qualification_type参数错误');
}
$gift_query = new \syb\wup\tafsvr_gift_query();
$result = $gift_query->query_gift_remainer();
$this->outputJson($result);
