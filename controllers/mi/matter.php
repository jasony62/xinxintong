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

		$who = $this->doAuth($mpid, $code, $mocker);

		$this->afterOAuth($mpid, $id, $type, $shareby, $openid, $who);
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
	private function afterOAuth($mpid, $id, $type, $shareby, $openid, $who = null) {
		/**
		 * visit fans.
		 */
		$ooid = empty($who) ? $this->getCookieOAuthUser($mpid) : $who;
		/**
		 * 根据类型获得处理素材的对象
		 */
		switch ($type) {
		case 'article':
			if (isset($_GET['tpl']) && $_GET['tpl'] === 'std') {
				\TPL::output('article');
				exit;
			} else {
				require_once dirname(__FILE__) . '/page_article.php';
				$page = new page_article($id, $ooid, $shareby);
			}
			break;
		case 'news':
		case 'channel':
			\TPL::output($type);
			exit;
		case 'addressbook':
			require_once dirname(__FILE__) . '/page_addressbook.php';
			$page = new page_addressbook($id, $ooid);
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
					"mpid='$mpid' and openid='$ooid' and unsubscribe_at=0",
				);
				if (1 !== (int) $this->model()->query_val_ss($q)) {
					/**
					 * 不是关注用户引导用户进行关注
					 */
					$fea = $this->model('mp\mpaccount')->getFeatures($mpid);
					\TPL::assign('follow_ele', $fea->follow_ele);
					\TPL::assign('follow_css', $fea->follow_css);
					\TPL::output('follow');
					exit;
				}
			}
			$link->type = 'L';
			switch ($link->urlsrc) {
			case 0:
				$page = new page_external($link, $ooid);
				break;
			case 1:
				require_once dirname(__FILE__) . '/page_news.php';
				$page = new page_news((int) $link->url, $ooid);
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
			$this->accessControl($mpid, $matter->id, $matter->authapis, $ooid, $matter);
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

		$posted = $this->getPostJson();

		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));

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
		$logUser = new \stdClass;
		$logUser->vid = $user->vid;
		$logUser->openid = empty($user->fan) ? '' : $user->openid;
		$logUser->nickname = empty($user->fan) ? '' : $user->fan->nickname;

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $title;

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$search = isset($posted->search) ? $posted->search : '';
		$referer = isset($posted->referer) ? $posted->referer : '';

		$this->model('log')->writeMatterRead($mpid, $logUser, $logMatter, $logClient, $shareby, $search, $referer);

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

		$user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y')));
		//$user = $this->getUser($mpid);

		$logUser = new \stdClass;
		$logUser->vid = $user->vid;
		$logUser->openid = $user->openid;
		$logUser->nickname = empty($user->fan) ? '' : $this->model()->escape($user->fan->nickname);

		$logMatter = new \stdClass;
		$logMatter->id = $id;
		$logMatter->type = $type;
		$logMatter->title = $this->model()->escape($title);

		$logClient = new \stdClass;
		$logClient->agent = $_SERVER['HTTP_USER_AGENT'];
		$logClient->ip = $this->client_ip();

		$this->model('log')->writeShareAction($mpid, $shareid, $shareto, $shareby, $logUser, $logMatter, $logClient);

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
	/**
	 *
	 */
	protected function canAccessObj($mpid, $matterId, $member, $authapis, &$matter) {
		return $this->model('acl')->canAccessMatter($mpid, $matter->type, $matterId, $member, $authapis);
	}
	/**
	 * 发送含有链接的邮件
	 * todo 邮件的内容不应该在代码中写死
	 */
	private function send_link_email($mpid, $email, $url, $text = '打开', $code = null) {
		$mp = $this->model('mp\mpaccount')->byId($mpid);

		$subject = $mp->name . "-链接";

		$content = "<p></p>";
		$content .= "<p>请点击下面的链接完成操作：</p>";
		$content .= "<p></p>";
		$content .= "<p><a href='$url'>$text</a></p>";
		if (!empty($code)) {
			$content .= "<p></p>";
			$content .= "<p>密码：$code</p>";
		}
		if (true !== ($msg = $this->send_email($mpid, $subject, $content, $email))) {
			return $msg;
		}

		return true;
	}
	/**
	 * 下载文件
	 *
	 * todo 仅对会员开放
	 */
	public function link_action($mpid, $user, $url, $text, $code) {
		if ($mid = $this->getMemberId($call)) {
			$q = array(
				'email,email_verified',
				'xxt_member',
				"mpid='$mpid' and mid='$mid'",
			);
			$identity = $this->model()->query_obj_ss($q);
			if ($identity->email && $identity->email_verified === 'Y') {
				if (true !== ($msg = $this->send_link_email($mpid, $identity->email, $url, $text, $code))) {
					return new \ResponseData($msg);
				} else {
					$rsp = '已通过【xin_xin_tong@163.com】将链接发送到你的个人邮箱，请在邮件内打开！';
					return new \ResponseData($rsp);
				}
			} else {
				$rsp = '没有获取邮箱信息，请向指定个人邮箱！';
				return new \ResponseData($rsp);
			}
		} else {
			/**
			 * 引导用户进行认证
			 */
			$tr = $this->register_reply($call);
			$tr->exec();
		}
	}
}
