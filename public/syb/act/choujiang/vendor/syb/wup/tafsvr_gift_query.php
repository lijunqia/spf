<?php
namespace syb\wup;
require('OidbAssist_wup.php');
require('GiftCom_wup.php');
require('GiftQueryServant_wup.php');
class tafsvr_gift_query extends Taf_svr
{
	public function query_gift_remainer()
	{
        $servantName = 'MiniGame.GiftServiceSvr.GiftQueryServantObj';
        $funcName = 'queryGiftRemainder';
        $params = array();
        $stReq = new TQueryGiftRemainerReq;
        $stReq->sGiftIdList->val = "123";
        $params['stReq'] = $stReq;
        $result = array('stRsp' => new TQueryGiftRemainerRsp);
        $this->request($servantName, $funcName, $params, $result);
        return $result;
	}
}