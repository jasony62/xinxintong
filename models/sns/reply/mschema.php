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
		$oMschema = $modelMs->byId($this->set_id);

		$oSite = \TMS_APP::model('site')->byId($oMschema->siteid, ['fields' => 'id,name,heading_pic']);

		$url = $modelMs->getEntryUrl($oMschema->siteid, $oMschema->id);

		$oCard->title = $oMschema->title;
		$oCard->summary = $oSite->name . '邀请您填写【' . $oMschema->title . '】信息。';
		$oCard->pic = $oSite->heading_pic;
		$oCard->entryURL = $url;

		return [$oCard];
	}
}