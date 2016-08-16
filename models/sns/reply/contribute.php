<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 投稿活动的信息卡片
 */
class contribute_model extends MultiArticleReply {

	protected function loadMatters() {
		$app = \TMS_APP::model('matter\base')->getCardInfoById('contribute', $this->set_id);
		$modelApp = \TMS_APP::model('matter\contribute');
		$app->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);

		return [$app];
	}
}