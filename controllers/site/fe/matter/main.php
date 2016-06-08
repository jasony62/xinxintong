<?php
namespace site\fe\matter;

require_once dirname(__FILE__) . '/base.php';
/**
 * 返回访问的素材页面
 */
class main extends \site\fe\matter\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * @param string $id
	 * @param string $type
	 * @param string $shareby
	 */
	public function index_action($site, $id, $type, $shareby = '') {
		if (!$this->afterSnsOAuth()) {
			/* 检查是否需要第三方社交帐号OAuth */
			$this->_requireSnsOAuth($site);
		}
		/* 返回页面 */
		switch ($type) {
		case 'article':
		case 'custom':
			$modelArticle = $this->model('matter\article2');
			$article = $modelArticle->byId($id, 'title');
			\TPL::assign('title', $article->title);
			if ($type === 'article') {
				\TPL::output('site/fe/matter/article/main');
			} else {
				\TPL::output('site/fe/matter/custom/main');
			}
			break;
		case 'news':
			\TPL::output('site/fe/matter/news/main');
			break;
		case 'channel':
			$modelChn = $this->model('matter\channel');
			$channel = $modelChn->byId($id, 'title');
			\TPL::assign('title', $channel->title);
			\TPL::output('site/fe/matter/channel/main');
			break;
		}
		exit;
	}
	/**
	 * 检查是否需要第三方社交帐号认证
	 * 检查条件：
	 * 0、应用是否设置了需要认证
	 * 1、站点是否绑定了第三方社交帐号认证
	 * 2、平台是否绑定了第三方社交帐号认证
	 * 3、用户客户端是否可以发起认证
	 *
	 * @param string $site
	 */
	private function _requireSnsOAuth($siteid) {
		if ($this->userAgent() === 'wx') {
			if (!isset($this->who->sns->wx)) {
				if ($wxConfig = $this->model('sns\wx')->bySite($siteid)) {
					if ($wxConfig->joined === 'Y') {
						$this->snsOAuth($wxConfig, 'wx');
					}
				}
			}
			if (!isset($this->who->sns->qy)) {
				if ($qyConfig = $this->model('sns\qy')->bySite($siteid)) {
					if ($qyConfig->joined === 'Y') {
						$this->snsOAuth($qyConfig, 'qy');
					}
				}
			}
		} else if ($this->userAgent() === 'yx') {
			if (!isset($this->who->sns->yx)) {
				if ($yxConfig = $this->model('sns\yx')->bySite($siteid)) {
					if ($yxConfig->joined === 'Y') {
						$this->snsOAuth($yxConfig, 'yx');
					}
				}
			}
		}

		return false;
	}
	/**
	 * 记录访问日志
	 */
	public function logAccess_action($site, $id, $type, $title = '', $shareby = '') {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods:POST');
		header('Access-Control-Allow-Headers:Content-Type');
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit;
		}

		switch ($type) {
		case 'article':
			$this->model()->update("update xxt_article set read_num=read_num+1 where id='$id'");
			break;
		case 'channel':
			$this->model()->update("update xxt_channel set read_num=read_num+1 where id='$id'");
			break;
		case 'news':
			$this->model()->update("update xxt_news set read_num=read_num+1 where id='$id'");
			break;
		}

		$posted = $this->getPostJson();
		$user = $this->who;

		$this->logRead($site, $user, $id, $type, $title, $shareby = '');

		return new \ResponseData('ok');
	}
	/**
	 * 记录访问日志
	 */
	protected function logRead($siteId, $user, $id, $type, $title, $shareby = '') {
		$logUser = new \stdClass;
		$logUser->userid = $user->uid;
		$logUser->nickname = $user->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $title;

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$search = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

		$this->model('matter\log')->writeMatterRead($siteId, $logUser, $logMatter, $logClient, $shareby, $search, $referer);
		/**
		 * coin log
		 * 如果是投稿人阅读没有奖励
		 */
		/*$modelCoin = $this->model('coin\log');
			if ($type === 'article') {
				$contribution = $this->model('matter\article')->getContributionInfo($id);
				if (!empty($contribution->openid) && $contribution->openid !== $logUser->openid) {
					// for contributor
					$action = 'app.' . $contribution->entry . '.article.read';
					$modelCoin->income($siteId, $action, $id, 'sys', $contribution->openid);
				}
				if (empty($contribution->openid) || $contribution->openid !== $logUser->openid) {
					// for reader
					$modelCoin->income($siteId, 'mp.matter.' . $type . '.read', $id, 'sys', $user->userid);
				}
			} else {
				// for reader
				$modelCoin->income($siteId, 'mp.matter.' . $type . '.read', $id, 'sys', $user->openid);
		*/

		return true;
	}
	/**
	 * 记录分享动作
	 *
	 * $shareid
	 * $site 公众号ID，是当前用户
	 * $id 分享的素材ID
	 * $type 分享的素材类型
	 * $share_to  分享给好友或朋友圈
	 * $shareby 谁分享的当前素材ID
	 *
	 */
	public function logShare_action($shareid, $site, $id, $type, $title, $shareto, $shareby = '') {
		header('Access-Control-Allow-Origin:*');
		/* 检查请求是否由客户端发起 */
		if ($type === 'lottery') {
			if (!$this->_isAgentEnter($id)) {
				return new \ResponseError('请从指定客户端发起请求');
			}
		}

		switch ($type) {
		case 'article':
			$table = 'xxt_article';
			break;
		case 'news':
			$table = 'xxt_news';
			break;
		case 'channel':
			$table = 'xxt_channel';
			break;
		case 'enroll':
			$table = 'xxt_enroll';
			break;
		case 'lottery':
			$table = 'xxt_lottery';
			break;
		}
		if (isset($table)) {
			if ($shareto === 'F') {
				$this->model()->update("update $table set share_friend_num=share_friend_num+1 where id='$id'");
			} else if ($shareto === 'T') {
				$this->model()->update("update $table set share_timeline_num=share_timeline_num+1 where id='$id'");
			}

			$user = $this->who;

			$logUser = new \stdClass;
			$logUser->userid = $user->uid;
			$logUser->nickname = $user->nickname;

			$logMatter = new \stdClass;
			$logMatter->id = $id;
			$logMatter->type = $type;
			$logMatter->title = $this->model()->escape($title);

			$logClient = new \stdClass;
			$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
			$logClient->ip = $this->client_ip();

			$this->model('log')->writeShareAction($site, $shareid, $shareto, $shareby, $logUser, $logMatter, $logClient);

			return new \ResponseData('ok');
		}
		/**
		 * coin log
		 * 投稿人分享不奖励积分
		 */
		/*$modelCoin = $this->model('coin\log');
			if ($type === 'article') {
				$contribution = $this->model('matter\article')->getContributionInfo($id);
				if (!empty($contribution->openid) && $contribution->openid !== $logUser->openid) {
					// for contributor
					$action = 'app.' . $contribution->entry . '.article.share.' . $shareto;
					$modelCoin->income($site, $action, $id, 'sys', $contribution->openid);
				}
				if (empty($contribution->openid) || $contribution->openid !== $logUser->openid) {
					// for reader
					$modelCoin->income($site, 'mp.matter.article.share.' . $shareto, $id, 'sys', $logUser->openid);
				}
			} else if ($type === 'enroll') {
				$action = 'app.enroll,' . $id . '.share.' . $shareto;
				$modelCoin->income($site, $action, $id, 'sys', $logUser->openid);
			} else {
				// for reader
				$modelCoin->income($site, 'mp.matter.' . $type . '.share.' . $shareto, $id, 'sys', $logUser->openid);
		*/

		return new \ResponseError('不支持的类型');
	}
}