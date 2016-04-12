<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 频道
 */
class channel_model extends MultiArticleReply {
	/**
	 * 如果频道设置了【固定标题】，要用固定标题替换掉第一个图文的标题
	 */
	protected function loadMatters() {
		$siteId = $this->call['siteid'];

		$model = \TMS_APP::model('matter\channel');

		$matters = $model->getMatters($this->set_id, null, $siteId);

		$channel = $model->byId($this->set_id, 'fixed_title');
		if (!empty($matters) && !empty($channel->fixed_title)) {
			$matters[0]->title = $channel->fixed_title;
		}
		$openid = $this->call['from_user'];
		foreach ($matters as &$matter) {
			$matter->entryURL = $url = \TMS_APP::model('matter\\' . $matter->type)->getEntryUrl($siteId, $matter->id, $openid, $this->call);
		}

		return $matters;
	}
}