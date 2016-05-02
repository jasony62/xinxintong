<?php
namespace site\fe\matter\contribute;

require_once dirname(__FILE__) . '/base.php';
/**
 * 审核
 */
class review extends base {
	/**
	 * 获得当前用户的信息
	 * $site
	 * $entry
	 */
	public function index_action() {
		\TPL::output('/site/fe/matter/contribute/review/list');
		exit;
	}
	/**
	 * 待审核稿件
	 */
	public function articleList_action($site, $entry) {
		/* 当前用户负责审核的文稿 */
		$myArticles = $this->model('matter\article2')->byReviewer($site, $this->who->uid, $entry, 'R', '*', true);
		if (!empty($myArticles)) {
			foreach ($myArticles as $a) {
				/* 文稿当前的处理人 */
				$disposer = $a->disposer;
				if (!empty($disposer) && $disposer->phase === 'R' && $disposer->receive_at == 0) {
					$this->model()->update(
						'xxt_article_review_log',
						array('receive_at' => time()),
						"id=" . $a->disposer->id);
				}
			}
		}

		return new \ResponseData($myArticles);
	}
	/**
	 * 页面
	 */
	public function article_action($site, $id) {
		$article = $this->getArticle($site, $id);

		$disposer = $article->disposer;
		if ($disposer && $disposer->phase === 'R' && $disposer->state === 'P') {
			$member = $this->model('site\user\member')->byId($disposer->mid, array('fields' => 'userid'));
			if ($member->userid === $this->who->uid) {
				$this->model()->update(
					'xxt_article_review_log',
					array('receive_at' => time()),
					"id=$disposer->id and receive_at=0");
				$this->model()->update(
					'xxt_article_review_log',
					array('read_at' => time(), 'state' => 'D'),
					"id=$disposer->id");
			}
		}
		\TPL::output('/site/fe/matter/contribute/review/article-r');
		exit;
	}
	/**
	 * 页面
	 */
	public function reviewlog_action($siteid, $id) {
		$article = $this->getArticle($siteid, $id);

		$disposer = $article->disposer;
		if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'P') {
			$this->model()->update(
				'xxt_article_review_log',
				array('read_at' => time(), 'state' => 'D'),
				"id=$disposer->id");
		}

		$this->view_action('/app/contribute/review/article-r');
	}

	/**
	 * 文稿审核通过
	 */
	public function articlePass_action($site, $id) {
		$article = $this->getArticle($site, $id);
		$contributor = $this->model('site\user\member')->byId($article->creater);
		/**
		 * 更新日志状态
		 */
		$user = $this->who;
		$members = $this->model('site\user\member')->byUser($site, $user->uid);
		$member = $members[0];
		$disposer = $article->disposer;
		if ($disposer && $disposer->mid === $member->id && $disposer->phase === 'R' && $disposer->state === 'D') {
			$this->model()->update(
				'xxt_article_review_log',
				array('close_at' => time(), 'state' => 'C'),
				"id=$disposer->id"
			);
		}
		/**
		 * 更新文稿状态
		 */
		$rst = $this->model()->update(
			'xxt_article',
			array('approved' => 'Y'),
			"siteid='$site' and id='$id'"
		);
		/**
		 * 奖励投稿人
		 */
		//$modelCoin = $this->model('coin\log');
		//$action = 'app.' . $article->entry . '.article.approved';
		//$modelCoin->income($siteid, $action, $id, 'sys', $contributor->openid);
		/**
		 * 发送通知
		 */
		/*$url = 'http://' . $_SERVER['HTTP_HOST'];
			$url .= '/rest/app/contribute/initiate/article';
			$url .= "?siteid=$siteid";
			$url .= "&entry=$article->entry";
			$url .= "&id=$id";

			$reply = '您的稿件【';
			$reply .= $article->title;
			$reply .= '】已经通过审核，';
			$mpa = $this->model('mp\mpaccount')->byId($siteid, 'mpsrc');
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

			$rst = $this->notify($siteid, $contributor->openid, $message);
		*/
		return new \ResponseData('ok');
	}
	/**
	 * 版面页面
	 */
	public function news_action($siteid, $id) {
		$news = $this->getNews($siteid, $id);
		$disposer = $news->disposer;
		/**
		 * 更改处理状态
		 */
		if ($disposer && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->state === 'P') {
			$this->model()->update(
				'xxt_news_review_log',
				array('read_at' => time(), 'state' => 'D'),
				"id=$disposer->id");
		}

		$this->view_action('/app/contribute/review/news-r');
	}
	/**
	 * 待审核版面
	 */
	public function newsList_action($siteid, $id) {
		$myNews = $this->model('matter\news')->byReviewer($this->user->mid, 'R', '*', true);

		if (!empty($myNews)) {
			foreach ($myNews as $n) {
				$disposer = $n->disposer;
				if (!empty($disposer) && $disposer->mid === $this->user->mid && $disposer->phase === 'R' && $disposer->receive_at == 0) {
					$this->model()->update(
						'xxt_news_review_log',
						array('receive_at' => time()),
						"id=" . $n->disposer->id);
				}
			}
		}

		return new \ResponseData($myNews);
	}
}