<?php
namespace site\fe\matter\contribute;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 投稿活动
 */
class base extends \site\fe\matter\base {
	/**
	 * 单篇文章
	 */
	public function articleGet_action($site, $id) {
		$article = $this->getArticle($site, $id);

		return new \ResponseData($article);
	}
	/**
	 * 单篇文章
	 */
	protected function &getArticle($site, $id) {
		$articleModel = $this->model('matter\article2');
		$article = $articleModel->byId($id);
		$article->disposer = $articleModel->disposer($id);
		/**
		 * channels
		 */
		$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
		/**
		 * tags
		 */
		//$article->tags = $this->model('tag')->tagsByRes($article->id, 'article');
		/**
		 * attachments
		 */
		if ($article->has_attachment === 'Y') {
			$article->attachments = $this->model()->query_objs_ss(array('*', 'xxt_article_attachment', "article_id='$id'"));
		}

		return $article;
	}
	/**
	 * 单篇文章
	 */
	protected function &getNews($site, $id) {
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
	public function articleUpdate_action($site, $id) {
		$nv = (array) $this->getPostJson();

		isset($nv['title']) && $nv['title'] = $this->model()->escape($nv['title']);
		isset($nv['summary']) && $nv['summary'] = $this->model()->escape($nv['summary']);
		isset($nv['author']) && $nv['author'] = $this->model()->escape($nv['author']);
		isset($nv['body']) && $nv['body'] = $this->model()->escape(urldecode($nv['body']));

		$nv['modify_at'] = time();
		$rst = $this->model()->update(
			'xxt_article',
			$nv,
			"siteid='$site' and id='$id'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 退回到上一步
	 */
	public function articleReturn_action($site, $id, $msg = '') {
		/**
		 * 记录日志
		 */
		$articleModel = $this->model('matter\article2');
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

		$log = $articleModel->forward($site, $id, $mid, $phase, $msg);
		/**
		 * 发送通知
		 */
		$article = $articleModel->byId($id);
		$url = 'http://' . $_SERVER['HTTP_HOST'];
		$url .= '/rest/site/fe/matter/contribute/initiate/article';
		$url .= "?site=$site";
		$url .= "&entry=$article->entry";
		$url .= "&id=$id";

		$reply = '您的投稿【';
		$reply .= $article->title;
		$reply .= '】已退回。退回原因【' . $msg . '】，请修改后再次送审。';

		foreach (array('wx', 'yx', 'qy') as $snsName) {
			if ($snsName === 'yx') {
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
			if ($snsUser = $this->snsUserByMember($site, $mid, $snsName)) {
				$this->notify($site, $snsName, $snsUser, $message);
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 转发给指定人进行处理
	 *
	 * $site 公众平台ID
	 * $id 文章ID
	 * $phase 处理的阶段
	 */
	public function articleForward_action($site, $id, $phase, $mid) {
		$model = $this->model('matter\article2');
		$article = $model->byId($id);

		$modelCtrb = $this->model('matter\contribute');
		$entry = explode(',', $article->entry);
		$c = $modelCtrb->byId($entry[1]);

		$log = $model->forward($site, $id, $mid, $phase);
		/**
		 * 发给指定用户进行处理
		 * @todo 应该改为模版消息实现
		 */
		$url = $this->articleReviewUrl($site, $id);
		$msg = '投稿活动【' . $c->title . '】有一篇新稿件，';
		foreach (array('wx', 'yx', 'qy') as $snsName) {
			if ($snsName === 'yx') {
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
			if ($snsUser = $this->snsUserByMember($site, $mid, $snsName)) {
				$rst = $this->notify($site, $snsName, $snsUser, $message);
			}
		}

		return new \ResponseData('ok');
	}
	/**
	 * 文章的投稿链接
	 *
	 * $site 公众平台ID
	 * $id 文章ID
	 */
	public function articleInitiateUrl($site, $id) {
		$model = $this->model('matter\article2');
		$article = $model->byId($id);

		$modelCtrb = $this->model('matter\contribute');
		$entry = explode(',', $article->entry);
		$c = $modelCtrb->byId($entry[1]);

		$url = 'http://' . $_SERVER['HTTP_HOST'];
		$url .= '/rest/site/fe/matter/contribute/initiate/article';
		$url .= "?site=$site";
		$url .= "&entry=$article->entry";
		$url .= "&id=$id";

		return $url;
	}
	/**
	 * 文章的审稿链接
	 *
	 * $site 公众平台ID
	 * $id 文章ID
	 */
	public function articleReviewUrl($site, $id) {
		$model = $this->model('matter\article2');
		$article = $model->byId($id);

		$modelCtrb = $this->model('matter\contribute');
		$entry = explode(',', $article->entry);
		$c = $modelCtrb->byId($entry[1]);

		$url = 'http://' . $_SERVER['HTTP_HOST'];
		$url .= '/rest/site/fe/matter/contribute/review/article';
		$url .= "?site=$site";
		$url .= "&entry=$article->entry";
		$url .= "&id=$id";

		return $url;
	}
	/**
	 * 发送通知
	 *
	 * $site 公众平台ID
	 * $id 文章ID
	 * $phase 处理的阶段
	 */
	public function notify($site, $snsName, &$snsUser, $message) {
		$rst = $this->sendBySnsUser($site, $snsName, $snsUser, $message);

		return $rst;
	}
	/**
	 *
	 */
	public function articleAddChannel_action($site) {
		$user = $this->who;
		$relations = $this->getPostJson();

		$channels = $relations->channels;
		$matter = $relations->matter;

		$model = $this->model('matter\channel');
		foreach ($channels as $channel) {
			$model->addMatter($channel->id, $matter, $user->uid, $user->nickname, 'U');
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
	public function newsGet_action($site, $id) {
		$q = array(
			"n.*",
			'xxt_news n',
			"n.siteid='$site' and n.id=$id",
		);

		if ($news = $this->model()->query_obj_ss($q)) {
			$news->matters = $this->model('matter\news')->getArticles($id);
		}

		return new \ResponseData($news);
	}
	/**
	 * 将多图文转发给指定人进行处理
	 *
	 * $site 公众平台ID
	 * $id 文章ID
	 * $phase 处理的阶段
	 */
	public function newsForward_action($site, $id, $phase) {
		$reviewer = $this->getPostJson();

		$mid = $reviewer->userSet[0]->identity;

		$model = $this->model('matter\news');

		$log = $model->forward($site, $id, $mid, $phase);
		/**
		 * 发给指定用户进行预览
		 */
		$mpa = $this->model('mp\mpaccount')->byId($site);
		$fan = $this->model('user/fans')->byMid($mid);
		if ($mpa->mpsrc === 'wx') {
			$message = $model->forWxGroupPush($site, $id);
		} else if ($mpa->mpsrc === 'yx' || $mpa->mpsrc === 'qy') {
			$message = $model->forCustomPush($site, $id);
		}
		$rst = $this->sendByOpenid($site, $fan->openid, $message);

		return new \ResponseData('ok');
	}
	/**
	 * 退回到多图文的上一处理人
	 * $site
	 * $id
	 */
	public function newsReturn_action($site, $id) {
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

		$log = $newsModel->forward($site, $id, $mid, $phase);

		return new \ResponseData('ok');
	}
	/**
	 * 可用的频道
	 */
	public function channelGet_action($site, $acceptType = null) {
		$channels = $this->model('matter\channel')->byMpid($site, $acceptType);

		return new \ResponseData($channels);
	}
}