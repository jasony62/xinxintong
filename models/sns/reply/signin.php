<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 签到活动的信息卡片
 */
class signin_model extends MultiArticleReply {
	/**
	 * 素材参数
	 */
	private $params;
	/**
	 *
	 */
	public function __construct($call, $matterId, $params = null) {
		parent::__construct($call, $matterId);
		if (!empty($params)) {
			$this->params = json_decode($params);
		}
	}
	/**
	 *
	 */
	protected function loadMatters() {
		$app = \TMS_APP::M('matter\base')->getCardInfoById('signin', $this->set_id);
		$modelApp = \TMS_APP::M('matter\signin');
		if (empty($this->params)) {
			$app->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id);
		} else {
			/* 指定了签到对应的轮次 */
			$signinRound = \TMS_APP::M('matter\signin\round')->byId($this->params->round, ['fields' => 'title']);
			$app->entryURL = $modelApp->getEntryUrl($this->call['siteid'], $this->set_id, $this->params->round);
			$app->title .= '-' . $signinRound->title;
		}

		return [$app];
	}
}