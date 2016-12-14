<?php
namespace matter;

/**
 * 评论回调方法
 */
class discuss_model extends \TMS_MODEL {
	/**
	 * 对素材点赞
	 *
	 */
	public function vote($threadKey, &$user, &$result) {
		list($matterType, $matterId) = explode(',', $threadKey);
		switch ($matterType) {
		case 'article':
			$matter = $this->model('matter\article2')->byId($matterId, ['fields' => 'siteid,id,title,entry,creater']);
			$this->_logVote($matter, $user);
			// 行为积分
			if ($result->first) {
				$coinUser = new \stdClass;
				$coinUser->uid = $user->key;
				$coinUser->nickname = $user->name;
				$modelCoin = $this->model('site\coin\log');
				$modelCoin->award($matter, $coinUser, 'site.matter.article.discuss.like');
			}
			break;
		case 'enroll':
			$matter = $this->model('matter\enroll')->byId($matterId, ['cascaded' => 'N', 'fields' => 'siteid,id,title']);
			$this->_logVote($matter, $user);
			// 行为积分
			if ($result->first) {
				$coinUser = new \stdClass;
				$coinUser->uid = $user->key;
				$coinUser->nickname = $user->name;
				$modelCoin = $this->model('site\coin\log');
				$modelCoin->award($matter, $coinUser, 'site.matter.enroll.discuss.like');
			}
			break;
		}
	}
	/**
	 *
	 */
	private function _logVote($matter, $user) {
		$modelLog = $this->model('matter\log');

		$logUser = new \stdClass;
		$logUser->userid = $user->key;
		$logUser->nickname = $user->name;
		//$siteid = $user->domain;

		$operation = new \stdClass;
		$operation->name = 'discuss.like';

		$client = new \stdClass;
		$client->agent = $_SERVER['HTTP_USER_AGENT'];

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $modelLog->addUserMatterOp($matter->siteid, $logUser, $matter, $operation, $client, $referer);

		return $logid;
	}
	/**
	 * 对素材评论
	 *
	 */
	public function comment($threadKey, &$user, &$result) {
		list($matterType, $matterId) = explode(',', $threadKey);
		switch ($matterType) {
		case 'article':
			$matter = $this->model('matter\article2')->byId($matterId, ['fields' => 'siteid,id,title,entry,creater']);
			$this->_logComment($matter, $user);
			// 行为积分
			$coinUser = new \stdClass;
			$coinUser->uid = $user->key;
			$coinUser->nickname = $user->name;
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($matter, $coinUser, 'site.matter.article.discuss.comment');
			break;
		case 'enroll':
			$matter = $this->model('matter\enroll')->byId($matterId, ['cascaded' => 'N', 'fields' => 'siteid,id,title']);
			$this->_logComment($matter, $user);
			// 行为积分
			$coinUser = new \stdClass;
			$coinUser->uid = $user->key;
			$coinUser->nickname = $user->name;
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($matter, $coinUser, 'site.matter.enroll.discuss.comment');
			break;
		}
	}
	/**
	 *
	 */
	private function _logComment($matter, $user) {
		$modelLog = $this->model('matter\log');

		$logUser = new \stdClass;
		$logUser->userid = $user->key;
		$logUser->nickname = $user->name;
		//$siteid = $user->domain;

		$operation = new \stdClass;
		$operation->name = 'discuss.comment';

		$client = new \stdClass;
		$client->agent = $_SERVER['HTTP_USER_AGENT'];

		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$logid = $modelLog->addUserMatterOp($matter->siteid, $logUser, $matter, $operation, $client, $referer);

		return $logid;
	}
}