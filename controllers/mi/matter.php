<?php
namespace mi;

require_once dirname(dirname(__FILE__)) . '/member_base.php';
require_once dirname(__FILE__) . '/matter_page_base.php';
/**
 * 根据用户请求的资源返回页面
 */
class matter extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 *
	 */
	protected function canAccessObj($mpid, $matterId, $member, $authapis, &$matter) {
		return $this->model('acl')->canAccessMatter($mpid, $matter->type, $matterId, $member, $authapis);
	}
	/**
	 * 打开指定的素材
	 *
	 * 这个接口是由浏览器直接调动，不能可靠提供关注用户（openid）信息
	 *
	 * $mpid 当前正在运行的公众号id。因为素材可能来源于父账号，所以素材的公众号可能和当前运行的公众号并不一致。
	 * $id int matter's id
	 * $type article|news|link|channel|addressbook
	 * $shareby 谁分享的素材
	 * $openid optional 不一定是当前访问用户，只代表从公众号获取到该素材的用户
	 * $code
	 *
	 */
	public function index_action($mpid, $id, $type, $shareby = '', $openid = '', $mocker = null, $code = null) {
		empty($mpid) && $this->outputError('没有指定当前运行的公众号');
		empty($id) && $this->outputError('素材id为空');
		empty($type) && $this->outputError('素材type为空');

		$openid = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $id, $type, $shareby, $openid);
	}
	/**
	 * 返回请求的素材
	 *
	 * $mpid
	 * $id
	 * $type
	 * $shareby
	 * $openid
	 * $who
	 */
	private function afterOAuth($mpid, $id, $type, $shareby, $openid) {
		/**
		 * 根据类型获得处理素材的对象
		 */
		switch ($type) {
		case 'article':
			$modelArticle = $this->model('matter\article');
			$article = $modelArticle->byId($id);
			if (isset($_GET['tpl']) && $_GET['tpl'] === 'cus') {
				\TPL::assign('title', $article->title);
				\TPL::output('custom');
				exit;
			} else {
				\TPL::assign('title', $article->title);
				\TPL::output('article');
				exit;
			}
			break;
		case 'news':
		case 'channel':
			\TPL::output($type);
			exit;
		case 'addressbook':
			require_once dirname(__FILE__) . '/page_addressbook.php';
			$page = new page_addressbook($id, $openid);
			break;
		case 'link':
			require_once dirname(__FILE__) . '/page_external.php';
			$link = $this->model('matter\link')->byIdWithParams($id);
			if ($link->fans_only === 'Y') {
				/**
				 * 仅限关注用户访问
				 */
				$q = array(
					'count(*)',
					'xxt_fans',
					"mpid='$mpid' and openid='$openid' and unsubscribe_at=0",
				);
				if (1 !== (int) $this->model()->query_val_ss($q)) {
					/**
					 * 不是关注用户引导用户进行关注
					 */
					$fea = $this->model('mp\mpaccount')->getFeature($mpid);
					\TPL::assign('follow_ele', $fea->follow_ele);
					\TPL::assign('follow_css', $fea->follow_css);
					\TPL::output('follow');
					exit;
				}
			}
			$link->type = 'L';
			switch ($link->urlsrc) {
			case 0:
				$page = new page_external($link, $openid);
				break;
			case 1:
				require_once dirname(__FILE__) . '/page_news.php';
				$page = new page_news((int) $link->url, $openid);
				break;
			case 2:
				$channelUrl = $this->model('matter\channel')->getEntryUrl($mpid, (int) $link->url);
				$this->redirect($channelUrl);
				break;
			}
			break;
		}

		$matter = $page->getMatter();
		empty($matter->mpid) && die("parameter($id:$type) error!");
		/**
		 * 记录访客信息
		 */
		$vid = $this->getVisitorId($mpid);
		/**
		 * write log.
		 */
		$this->logAccess_action($mpid, $id, $type, $matter->title, $shareby);
		/**
		 * 访问控制
		 */
		$mid = false;
		if (isset($matter->access_control) && $matter->access_control === 'Y') {
			$this->accessControl($mpid, $matter->id, $matter->authapis, $openid, $matter);
		}

		$page->output($mpid, $mid, $vid, $this);
		exit;
	}
	/**
	 * 记录访问日志
	 */
	public function logAccess_action($mpid, $id, $type, $title = '', $shareby = '') {
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
		$user = $this->getUser($mpid);

		$this->logRead($mpid, $user, $id, $type, $title, $shareby = '');

		return new \ResponseData('ok');
	}
	/**
	 * 记录分享动作
	 *
	 * $shareid
	 * $mpid 公众号ID，是当前用户
	 * $id 分享的素材ID
	 * $type 分享的素材类型
	 * $share_to  分享给好友或朋友圈
	 * $shareby 谁分享的当前素材ID
	 *
	 */
	public function logShare_action($shareid, $mpid, $id, $type, $title, $shareto, $shareby = '') {
		header('Access-Control-Allow-Origin:*');

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
		}
		if (isset($table)) {
			if ($shareto === 'F') {
				$this->model()->update("update $table set share_friend_num=share_friend_num+1 where id='$id'");
			} else if ($shareto === 'T') {
				$this->model()->update("update $table set share_timeline_num=share_timeline_num+1 where id='$id'");
			}
		}

		$user = $this->getUser($mpid);

		$logUser = new \stdClass;
		$logUser->vid = $user->vid;
		$logUser->openid = $user->openid;
		$logUser->nickname = $user->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $this->model()->escape($title);

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$this->model('log')->writeShareAction($mpid, $shareid, $shareto, $shareby, $logUser, $logMatter, $logClient);
		/**
		 * coin log
		 * 投稿人分享不奖励积分
		 */
		$modelCoin = $this->model('coin\log');
		if ($type === 'article') {
			$contribution = $this->model('matter\article')->getContributionInfo($id);
			if (!empty($contribution->openid) && $contribution->openid !== $logUser->openid) {
				// for contributor
				$action = 'app.' . $contribution->entry . '.article.share.' . $shareto;
				$modelCoin->income($mpid, $action, $id, 'sys', $contribution->openid);
			}
			if (empty($contribution->openid) || $contribution->openid !== $logUser->openid) {
				// for reader
				$modelCoin->income($mpid, 'mp.matter.article.share.' . $shareto, $id, 'sys', $logUser->openid);
			}
		} else if ($type === 'enroll') {
			$action = 'app.enroll,' . $id . '.share.' . $shareto;
			$modelCoin->income($mpid, $action, $id, 'sys', $logUser->openid);
		} else {
			// for reader
			$modelCoin->income($mpid, 'mp.matter.' . $type . '.share.' . $shareto, $id, 'sys', $logUser->openid);
		}

		return new \ResponseData('ok');
	}
	/**
	 *
	 * $mpid
	 * $id channel's id
	 */
	public function byChannel_action($mpid, $id, $orderby = 'time', $page = null, $size = null) {
		$vid = $this->getVisitorId($mpid);

		$params = new \stdClass;
		$params->orderby = $orderby;
		if ($page !== null && $size !== null) {
			$params->page = $page;
			$params->size = $size;
		}

		$matters = \TMS_APP::M('matter\channel')->getMattersNoLimit($id, $vid, $params);
		$tagModel = $this->model('tag');
		foreach ($matters as $m) {
			$matterModel = \TMS_APP::M('matter\\' . $m->type);
			$m->url = $matterModel->getEntryUrl($mpid, $m->id);
			$m->tags = $tagModel->tagsByRes($m->id, 'article');
		}

		return new \ResponseData($matters);
	}
	/**
	 *
	 * $mpid
	 * $id channel's id
	 */
	public function byNews_action($mpid, $id) {
		$matters = \TMS_APP::M('matter\news')->getMatters($id);
		$tagModel = $this->model('tag');
		foreach ($matters as $m) {
			$matterModel = \TMS_APP::M('matter\\' . $m->type);
			$m->url = $matterModel->getEntryUrl($mpid, $m->id);
			if ($m->type === 'article') {
				$m->tags = $tagModel->tagsByRes($m->id, 'article');
			}

		}

		header('Access-Control-Allow-Origin:*');

		return new \ResponseData($matters);
	}
}