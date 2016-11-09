<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 信息墙的信息卡片
 */
class wall_model extends MultiArticleReply {

	protected function loadMatters() {
		$app = \TMS_APP::model('matter\base')->getCardInfoById('wall', $this->set_id);
		$modelApp = \TMS_APP::model('matter\wall');
		$app->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);

		return array($app);
	}
}