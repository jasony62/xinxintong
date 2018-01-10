<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动的信息卡片
 */
class signin_model extends MultiArticleReply {
	/**
	 *
	 */
	protected function loadMatters() {
		$modelApp = \TMS_APP::M('matter\signin');
		$oApp = $modelApp->byId($this->set_id, ['fiedls' => 'id,state,siteid,title,summary,pic']);
		if (!empty($this->params)) {
			/* 指定了签到对应的轮次 */
			if (is_string($this->params)) {
				$oParams = json_decode($this->params);
			} else if (is_object($this->params)) {
				$oParams = $this->params;
			}
			if (isset($oParams) && !empty($oParams->round)) {
				$oSigninRnd = \TMS_APP::M('matter\signin\round')->byId($oParams->round, ['fields' => 'title']);
				if ($oSigninRnd) {
					$oApp->entryUrl = $modelApp->getEntryUrl($oApp->siteid, $oApp->id, $oParams->round);
					$oApp->title .= '-' . $oSigninRnd->title;
				}
			}
		}

		return [$oApp];
	}
}