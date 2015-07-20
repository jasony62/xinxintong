<?php
namespace reply;

require_once dirname(__FILE__).'/base.php';
/**
 * 抽奖活动的信息卡片
 */
class lottery_model extends MultiArticleReply {

	protected function loadMatters() 
	{
		$l = \TMS_APP::model('matter\base')->getCardInfoById('lottery', $this->set_id);
		return array($l);
	}
}
