<?php
namespace app\contribute;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 发起投稿
 */
class base extends \member_base {

	protected $user;

	public function __construct() {
		$mpid = $_GET['mpid'];
		$_SESSION['mpid'] = $mpid;

		list($fid, $openid, $mid, $vid) = $this->getCurrentUserInfo($mpid);
		$user = new \stdClass;
		$user->fid = $fid;
		$user->openid = $openid;
		$user->mid = $mid;
		$user->vid = $vid;
		$this->user = $user;
	}

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得当前用户的信息
	 * $mpid
	 * $entry
	 */
	public function index_action($mpid, $entry, $code = null, $mocker = null) {
		$openid = $this->doAuth($mpid, $code, $mocker);
		$this->afterOAuth($mpid, $entry, $openid);
	}
	/**
	 * 单篇文章
	 */
	public function articleGet_action($mpid, $id) {
		$article = $this->getArticle($mpid, $id);

		return new \ResponseData($article);
	}
	/**
	 * 单篇文章
	 */
	protected function &getArticle($mpid, $id) {
		$articleModel = $this->model('matter\article');
		$article = $articleModel->byId($id);
		$article->disposer = $articleModel->disposer($id);
		/**
		 * channels
		 */
		$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
		/**
		 * tags
		 */
		$article->tags = $this->model('tag')->tagsByRes($article->id, 'article');

		return $article;
	}
	/**
	 * 单篇文章
	 */
	protected function &getNews($mpid, $id) {
		$newsModel = $this->model('matter\news');
		$news = $newsModel->byId($id);
		$news->disposer = $newsModel->disposer($id);

		return $news;
	}
	/**
	 * 更新单图文的字段
	 *
	 * $id article's id
	 * $nv pair of name and value
	 */
	public function articleUpdate_action($mpid, $id) {
		$nv = (array) $this->getPostJson();

		isset($nv['title']) && $nv['title'] = $this->model()->escape($nv['title']);
		isset($nv['summary']) && $nv['summary'] = $this->model()->escape($nv['summary']);
		isset($nv['author']) && $nv['author'] = $this->model()->escape($nv['author']);
		isset($nv['body']) && $nv['body'] = $this->model()->escape(urldecode($nv['body']));

		$nv['modify_at'] = time();
		$rst = $this->model()->update(
			'xxt_article',
			$nv,
			"mpid='$mpid' and creater='" . $this->user->mid . "' and id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 退回到上一步
	 */
	public function articleReturn_action($mpid, $id, $msg = '') {
		/**
		 * 记录日志
		 */
		$articleModel = $this->model('matter\article');
		$disposer = $articleModel->disposer($id);
		if ($disposer->seq == 1) {
			$article = $articleModel->byId($id);
			$mid = $article->creater;
			$phase = 'I';
		} else {
			$q = array(
				'*',
				'xxt_article_review_log',
				"article_id=$id and seq=" . ($disposer->seq - 1),
			);
			$prev = $this->model()->query_obj_ss($q);
			$mid = $prev->mid;
			$phase = $prev->phase;
		}

		$log = $articleModel->forward($mpid, $id, $mid, $phase, $msg);
		/**
		 * 发送通知
		 */
		$article = $articleModel->byId($id);
		$url = 'http://' . $_SERVER['HTTP_HOST'];
		$url .= '/rest/app/contribute/initiate/article';
		$url .= "?mpid=$mpid";
		$url .= "&entry=$article->entry";
		$url .= "&id=$id";

		$reply = '您的投稿【';
		$reply .= $article->title;
		$reply .= '】已退回。退回原因【' . $msg . '】，请修改后再次送审。';
		$mpa = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc');
		if ($mpa->mpsrc === 'yx') {
			$reply .= '查看详情：\n' . $url;
		} else {
			$reply .= "<a href='" . $url . "'>查看详情</a>";
		}
		$message = array(
			"msgtype" => "text",
			"text" => array(
				"content" => $reply,
			),
		);

		$fan = $this->model('user/fans')->byMid($mid);

		$rst = $this->notify($mpid, $fan->openid, $message);

		return new \ResponseData('ok');
	}
	/**
	 * 转发给指定人进行处理
	 *
	 * $mpid 公众平台ID
	 * $id 文章ID
	 * $phase 处理的阶段
	 */
	public function articleForward_action($mpid, $id, $phase, $mid) {
		$model = $this->model('matter\article');
		$article = $model->byId($id);

		$modelCtrb = $this->model('app\contribute');
		$entry = explode(',', $article->entry);
		$c = $modelCtrb->byId($entry[1]);

		$log = $model->forward($mpid, $id, $mid, $phase);
		/**
		 * 发给指定用户进行处理
		 * @todo 应该改为模版消息实现
		 */
		$url = $this->articleReviewUrl($mpid, $id);
		$msg = '投稿活动【' . $c->title . '】有一篇新稿件，';
		$mpa = $this->model('mp\mpaccount')->byId($mpid, 'mpsrc');
		if ($mpa->mpsrc === 'yx') {
			$msg .= '请处理：\n' . $url;
		} else {
			$msg .= "<a href='" . $url . "'>请处理</a>";
		}
		$message = array(
			"msgtype" => "text",
			"text" => array(
				"content" => $msg,
			),
		);

		$fan = $this->model('user/fans')->byMid($mid);

		$rst = $this->notify($mpid, $fan->openid, $message);

		return new \ResponseData($rst);
	}
	/**
	 * 文章的投稿链接
	 *
	 * $mpid 公众平台ID
	 * $id 文章ID
	 */
	public function articleInitiateUrl($mpid, $id) {
		$model = $this->model('matter\article');
		$article = $model->byId($id);

		$modelCtrb = $this->model('app\contribute');
		$entry = explode(',', $article->entry);
		$c = $modelCtrb->byId($entry[1]);

		$url = 'http://' . $_SERVER['HTTP_HOST'];
		$url .= '/rest/app/contribute/initiate/article';
		$url .= "?mpid=$mpid";
		$url .= "&entry=$article->entry";
		$url .= "&id=$id";

		return $url;
	}
	/**
	 * 文章的审稿链接
	 *
	 * $mpid 公众平台ID
	 * $id 文章ID
	 */
	public function articleReviewUrl($mpid, $id) {
		$model = $this->model('matter\article');
		$article = $model->byId($id);

		$modelCtrb = $this->model('app\contribute');
		$entry = explode(',', $article->entry);
		$c = $modelCtrb->byId($entry[1]);

		$url = 'http://' . $_SERVER['HTTP_HOST'];
		$url .= '/rest/app/contribute/review/article';
		$url .= "?mpid=$mpid";
		$url .= "&entry=$article->entry";
		$url .= "&id=$id";

		return $url;
	}
	/**
	 * 发送通知
	 *
	 * $mpid 公众平台ID
	 * $id 文章ID
	 * $phase 处理的阶段
	 */
	public function notify($mpid, $openid, $message) {
		$rst = $this->sendByOpenid($mpid, $openid, $message);

		return $rst;
	}
	/**
	 *
	 */
	public function articleAddChannel_action($mpid) {
		$fan = $this->model('user/fans')->byId($this->user->fid, 'nickname');

		$relations = $this->getPostJson();

		$channels = $relations->channels;
		$matter = $relations->matter;

		$model = $this->model('matter\channel');
		foreach ($channels as $channel) {
			$model->addMatter($channel->id, $matter, $this->user->mid, $fan->nickname, 'M');
		}

		return new \ResponseData('ok');
	}
	/**
	 *
	 */
	public function articleRemoveChannel_action($id, $channelId) {
		$model = $this->model('matter\channel');

		$matter = new \stdClass;
		$matter->id = $id;
		$matter->type = 'article';

		$rst = $model->removeMatter($channelId, $matter);

		return new \ResponseData($rst);
	}
	/**
	 * 获得单个多图文
	 */
	public function newsGet_action($mpid, $id) {
		$q = array(
			"n.*",
			'xxt_news n',
			"n.mpid='$mpid' and n.id=$id",
		);

		if ($news = $this->model()->query_obj_ss($q)) {
			$news->matters = $this->model('matter\news')->getArticles($id);
		}

		return new \ResponseData($news);
	}
	/**
	 * 将多图文转发给指定人进行处理
	 *
	 * $mpid 公众平台ID
	 * $id 文章ID
	 * $phase 处理的阶段
	 */
	public function newsForward_action($mpid, $id, $phase) {
		$reviewer = $this->getPostJson();

		$mid = $reviewer->userSet[0]->identity;

		$model = $this->model('matter\news');

		$log = $model->forward($mpid, $id, $mid, $phase);
		/**
		 * 发给指定用户进行预览
		 */
		$mpa = $this->model('mp\mpaccount')->byId($mpid);
		$fan = $this->model('user/fans')->byMid($mid);
		if ($mpa->mpsrc === 'wx') {
			$message = $model->forWxGroupPush($mpid, $id);
		} else if ($mpa->mpsrc === 'yx' || $mpa->mpsrc === 'qy') {
			$message = $model->forCustomPush($mpid, $id);
		}
		$rst = $this->sendByOpenid($mpid, $fan->openid, $message);

		return new \ResponseData('ok');
	}
	/**
	 * 退回到多图文的上一处理人
	 * $mpid
	 * $id
	 */
	public function newsReturn_action($mpid, $id) {
		$newsModel = $this->model('matter\news');
		$disposer = $newsModel->disposer($id);
		if ($disposer->seq == 1) {
			$news = $newsModel->byId($id);
			$mid = $news->creater;
			$phase = 'T';
		} else {
			$q = array(
				'*',
				'xxt_news_review_log',
				"news_id=$id and seq=" . ($disposer->seq - 1),
			);
			$prev = $this->model()->query_obj_ss($q);
			$mid = $prev->mid;
			$phase = $prev->phase;
		}

		$log = $newsModel->forward($mpid, $id, $mid, $phase);

		return new \ResponseData('ok');
	}
	/**
	 * 当前公众号的父账号的所有子公众号
	 */
	public function childmps_action($mpid) {
		$mpa = $this->model('mp\mpaccount')->byId($mpid, 'parent_mpid');

		if ($mpa && !empty($mpa->parent_mpid)) {
			$q = array(
				'mpid,name,mpsrc,create_at,yx_joined,wx_joined,qy_joined',
				'xxt_mpaccount a',
				"parent_mpid='$mpa->parent_mpid'",
			);
			$q2 = array('o' => 'name');

			$mps = $this->model()->query_objs_ss($q, $q2);
		} else {
			$q = array(
				'mpid,name,mpsrc,create_at,yx_joined,wx_joined,qy_joined',
				'xxt_mpaccount a',
				"mpid='$mpid'",
			);
			$mp = $this->model()->query_obj_ss($q);
			$mps = array($mp);
		}

		return new \ResponseData($mps);
	}
	/**
	 * $pid 获得父公众平台下的子平台
	 */
	public function mpaccountGet_action($mpid) {
		$mpas = $this->model('mp\mpaccount')->byMpid($mpid);

		return new \ResponseData($mpas);
	}
	/**
	 * 可用的频道
	 */
	public function channelGet_action($mpid, $acceptType = null) {
		$channels = $this->model('matter\channel')->byMpid($mpid, $acceptType);

		return new \ResponseData($channels);
	}
	/**
	 * 获得当前访问用户的信息
	 *
	 * $mpid
	 */
	protected function getCurrentUserInfo($mpid, $callbackUrl = null) {
		$authapis = $this->model('user/authapi')->byMpid($mpid, 'Y');
		$aAuthids = array();
		foreach ($authapis as $a) {
			$aAuthids[] = $a->authid;
		}

		$fan = $this->getCookieOAuthUser($mpid);

		$members = $this->authenticate($mpid, $aAuthids, $callbackUrl, $fan->openid);
		empty($members) && $this->outputError('当前用户不是认证用户');

		$mid = $members[0]->mid;
		$fan = $this->model('user/fans')->byMid($mid, 'fid,openid');
		$vid = $this->getVisitorId($mpid);

		return array($fan->fid, $fan->openid, $mid, $vid);
	}
}