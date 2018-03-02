<?php
namespace sns\reply;

require_once dirname(__FILE__) . '/base.php';
/**
 * 多图文信息卡片
 */
class news_model extends MultiArticleReply {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_news';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'news';
	}
	/**
	 *
	 */
	protected function loadMatters() {
		$siteId = $this->call['siteid'];
		$openid = $this->call['from_user'];
		$ufrom = $this->call['src'];

		$news = \TMS_APP::model('matter\news')->byId($this->set_id);
		$matters = \TMS_APP::model('matter\news')->getMatters($this->set_id);

		// 获取用户的自定义信息
		// added by yangyue: 一个openid可能对应多个userid
		$users = \TMS_APP::model('site\user\account')->byOpenid($siteId, $ufrom, $openid, ['fields' => 'uid']);
		if (empty($users)) {
			$members = [];
		} else {
			$members = \TMS_APP::model('site\user\member')->byUser($users[0]->uid);
		}
		$matters2 = array();
		foreach ($matters as $m) {
			$m->entryURL = \TMS_APP::model('matter\\' . $m->type)->getEntryUrl($siteId, $m->id, $openid);
			$matters2[] = $m;
		}

		if (count($matters2) === 0) {
			$m = \TMS_APP::model('matter\base')->getCardInfoById($news->empty_reply_type, $news->empty_reply_id);
			return $m;
		} else {
			return $matters2;
		}
	}
}
