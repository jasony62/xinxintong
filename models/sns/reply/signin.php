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
	public function __construct($call, $matterId, $params = null) {
		parent::__construct($call, $matterId);
	}
	/**
	 *
	 */
	protected function loadMatters() {
		$oApp = \TMS_APP::M('matter\base')->getCardInfoById('signin', $this->set_id);
		$modelApp = \TMS_APP::M('matter\signin');
		if (empty($this->params)) {
			$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);
		} else {
			/* 指定了签到对应的轮次 */
			if (is_string($this->params)) {
				$oParams = json_decode($this->params);
			} else if (is_object($this->params)) {
				$oParams = $this->params;
			}
			if (isset($oParams) && !empty($oParams->round)) {
				$oSigninRnd = \TMS_APP::M('matter\signin\round')->byId($oParams->round, ['fields' => 'title']);
				$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id, $this->oParams->round);
				$oApp->title .= '-' . $oSigninRnd->title;
			} else {
				$oApp->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);
			}
		}

		return [$oApp];
	}
}