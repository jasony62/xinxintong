<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动的信息卡片
 */
class enroll_model extends MultiArticleReply {

	protected function loadMatters() {
		$app = \TMS_APP::model('matter\base')->getCardInfoById('enroll', $this->set_id);
		$modelApp = \TMS_APP::model('matter\enroll');
		$app->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);

		return array($app);
	}
}