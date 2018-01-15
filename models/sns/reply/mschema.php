<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 通讯录联系人的信息卡片
 */
class mschema_model extends MultiArticleReply {

	protected function loadMatters() {
		$oCard = new \stdClass;
		$modelMs = \TMS_APP::model('site\user\memberschema');
		$oMschema = $modelMs->byId($this->set_id, ['fields' => 'id,title,siteid,matter_id,matter_type']);

		$url = $modelMs->getEntryUrl($oMschema->siteid, $oMschema->id);

		$oCard->title = $oMschema->title;
		$oCard->entryURL = $url;

		switch ($oMschema->matter_type) {
		case 'mission':
			if (!empty($oMschema->matter_id)) {
				$oMission = \TMS_APP::model('matter\mission')->byId($oMschema->matter_id, ['fields' => 'title,pic']);
				if ($oMission) {
					$oCard->summary = $oMission->title . '需要您填写【' . $oMschema->title . '】信息。';
					$oCard->pic = $oMission->pic;
				}
			}
			break;
		default:
			$oSite = \TMS_APP::model('site')->byId($oMschema->siteid, ['fields' => 'id,name,heading_pic']);
			$oCard->summary = $oSite->name . '邀请您填写【' . $oMschema->title . '】信息。';
			$oCard->pic = $oSite->heading_pic;
		}

		return [$oCard];
	}
}