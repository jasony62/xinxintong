<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 项目的信息卡片
 */
class mission_model extends MultiArticleReply {
	/**
	 *
	 */
	protected function loadMatters() {
		$modelMis = \TMS_APP::M('matter\mission');
		$oMission = $modelMis->byId($this->set_id, ['fiedls' => 'id,state,siteid,title,summary,pic']);

		return [$oMission];
	}
}