<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 投稿活动的信息卡片
 */
class contribute_model extends MultiArticleReply {

	protected function loadMatters() 
	{
		$a = \TMS_APP::model('matter\base')->getCardInfoById('contribute', $this->set_id);
		return array($a);
	}
}
