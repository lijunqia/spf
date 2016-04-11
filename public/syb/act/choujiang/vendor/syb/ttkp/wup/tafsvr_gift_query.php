<?php
namespace syb\ttkp\wup;
require('OidbAssist_wup.php');
require('GiftCom_wup.php');
require('GiftQueryServant_wup.php');
use syb\wup\Taf_svr;
class tafsvr_gift_query extends Taf_svr
{
	public function query_gift_remainer()
	{
		debug(get_included_files());
        $servantName = 'MiniGame.GiftServiceSvr.GiftQueryServantObj';
        $funcName = 'queryGiftRemainder';
        $params = array();
        //$stReq = new wup\TQueryGiftRemainerReq;
        $stReq = new TQueryGiftRemainerReq;
        $stReq->sGiftIdList->val = "123";
        $params['stReq'] = $stReq;
        //$result = array('stRsp' => new wup\TQueryGiftRemainerRsp);
        $result = array('stRsp' => new TQueryGiftRemainerRsp);
        $this->request($servantName, $funcName, $params, $result);
        return $result;
	}
}