<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 计划任务活动的信息卡片
 */
class plan_model extends MultiArticleReply {
	/**
	 * 返回素材信息
	 */
	protected function loadMatters() {
		$modelApp = \TMS_MODEL::model('matter\plan');
		$oApp = $modelApp->byId($this->set_id, ['fields' => 'id,siteid,state,title,summary,pic']);
		if ($oApp && $oApp->state === '1') {
			if (!empty($this->params)) {
				$params = $this->params;
				if (is_string($this->params)) {
					$params = json_decode($this->params);
				}
				if (!empty($params->inviteToken)) {
					$oApp->entryUrl .= '&inviteToken=' . $params->inviteToken;
				}
			}
		}

		return [$oApp];
	}
}