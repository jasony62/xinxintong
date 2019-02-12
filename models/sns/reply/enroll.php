<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动的信息卡片
 */
class enroll_model extends MultiArticleReply {
	/**
	 *
	 */
	protected function loadMatters() {
		$modelApp = \TMS_APP::model('matter\enroll');
		$oApp = $modelApp->byId($this->set_id, ['fields' => 'id,state,siteid,title,summary,pic']);
		if (!empty($this->params)) {
			if (is_string($this->params)) {
				$oParams = json_decode($this->params);
			} else {
				$oParams = $this->params;
			}
			if (isset($oParams)) {
				$oApp->entryUrl = $modelApp->getEntryUrl($oApp->siteid, $oApp->id, $oParams);
			}
		}

		return [$oApp];
	}
}