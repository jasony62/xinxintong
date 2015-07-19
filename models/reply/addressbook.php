<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 通讯录信息卡片 
 */
class addressbook_model extends MultiArticleReply {

    protected function loadMatters() 
    {
    	$ab = \TMS_APP::model('matter\base')->getCardInfoById('addressbook', $this->set_id);
    	return array($ab);
    }
}