<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动的信息卡片
 */
class enroll_model extends MultiArticleReply {

	protected function loadMatters() {
		$oApp = \TMS_APP::model('matter\base')->getCardInfoById('enroll', $this->set_id);
		$modelApp = \TMS_APP::model('matter\enroll');
		if (!empty($this->params)) {
			if (is_object($this->params)) {
				$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id, $this->params);
			} else if (is_string($this->params)) {
				$oParams = json_decode($this->params);
				$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id, $oParams);
			} else {
				$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);
			}
		} else {
			$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);
		}

		return array($oApp);
	}
}