<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 登记活动的信息卡片
 */
class enroll_model extends MultiArticleReply {

    protected function loadMatters() 
    {
        $a = \TMS_APP::model('matter\base')->getCardInfoById('enroll', $this->set_id);
    	return array($a);
    }
}
