<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动的信息卡片
 */
class signin_model extends MultiArticleReply {

	protected function loadMatters() {
		$app = \TMS_APP::model('matter\base')->getCardInfoById('signin', $this->set_id);
		$modelApp = \TMS_APP::model('matter\signin');
		$app->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);

		return [$app];
	}
}