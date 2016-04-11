<?php
namespace site\fe\matter;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 返回访问的素材页面
 */
class main extends \site\fe\base {

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
		$this->afterSnsOAuth();
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
			\TPL::output('site/fe/matter/channel/main');
			break;
		}
		exit;
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
}