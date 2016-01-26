<?php
namespace app\contribute;

require_once dirname(__FILE__) . '/base.php';
/**
 * 审核活动
 */
class typeset extends base {
	/**
	 *
	 */
	public function afterOAuth($mpid, $entry, $openid = null) {
		$myUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/contribute/typeset?mpid=$mpid&entry=$entry";
		$this->getCurrentUserInfo($mpid, $myUrl);

		$this->view_action('/app/contribute/typeset/list');
	}
	/**
	 *
	 */
	public function articleList_action($mpid, $entry) {
		list($fid, $openid, $mid) = $this->getCurrentUserInfo($mpid);

		/*$myArticles = $this->model('matter\article')->byReviewer($mid, $entry, 'T', '*', true);
			        if (!empty($myArticles)) foreach ($myArticles as $a) {
			            if (!empty($a->disposer) && $a->disposer->mid === $mid && $a->disposer->phase === 'T' && $a->disposer->receive_at == 0) {
			                $this->model()->update(
			                    'xxt_article_review_log',
			                    array('receive_at'=>time()),
			                    "id=".$a->disposer->id);
			            }
		*/

		$approved = $this->model('matter\article')->getApproved($mpid, $entry);

		return new \ResponseData($approved);
	}
	/**
	 *
	 */
	public function article_action($mpid, $id) {
		$article = $this->getArticle($mpid, $id);

		$this->view_action('/app/contribute/typeset/article');
	}
	/**
	 *
	 */
	public function news_action($mpid, $id) {
		$this->view_action('/app/contribute/typeset/news');
	}
	/**
	 *
	 */
	public function newsList_action($mpid, $id) {
		$q = array(
			"n.*",
			'xxt_news n',
			"n.mpid='$mpid' and n.state=1",
		);
		$q2['o'] = 'create_at desc';

		$news = $this->model()->query_objs_ss($q, $q2);
		/**
		 * 处理日志
		 */
		$newsModel = $this->model('matter\news');
		if (!empty($news)) {
			foreach ($news as &$n) {
				$disposer = $newsModel->disposer($n->id);
				$n->disposer = $disposer;
				if (!empty($disposer) && $disposer->mid === $this->user->mid && $disposer->phase === 'T' && $disposer->receive_at == 0) {
					$this->model()->update(
						'xxt_news_review_log',
						array('receive_at' => time()),
						"id=" . $n->disposer->id);
				}
			}
		}

		return new \ResponseData($news);
	}
	/**
	 * 创建新版面
	 *
	 * $mpid
	 * $entry 投稿入口
	 */
	public function newsCreate_action($mpid, $entry = null) {
		list($fid, $openid, $mid) = $this->getCurrentUserInfo($mpid);

		$d = array();
		$d['mpid'] = $mpid;
		$d['creater'] = $mid;
		//$d['creater_name'] = $fan->nickname;
		//$d['creater_src'] = 'M';
		$d['create_at'] = time();
		$d['title'] = date('Y-m-d_Hi');
		$id = $this->model()->insert('xxt_news', $d, true);
		/**
		 * articles
		 */
		$articleIds = $this->getPostJson();
		foreach ($articleIds as $i => $articleId) {
			$ns['news_id'] = $id;
			$ns['matter_id'] = $articleId;
			$ns['matter_type'] = 'article';
			$ns['seq'] = $i;
			$this->model()->insert('xxt_news_matter', $ns);
		}

		return new \ResponseData($id);
	}
	/**
	 *
	 */
	public function channelAddMatter_action($mpid) {
		$relations = $this->getPostJson();

		$creater = '';
		$createrName = '';

		$channels = $relations->channels;
		$matter = $relations->matter;

		$model = $this->model('matter\channel');
		foreach ($channels as $channel) {
			$model->addMatter($channel->id, $matter, $creater, $createrName);
		}

		return new \ResponseData('ok');
	}
	/**
	 *
	 */
	public function channelDelMatter_action($mpid, $id, $reload = 'N') {
		$matter = $this->getPostJson();

		$model = $this->model('matter\channel');

		$rst = $model->removeMatter($id, $matter);

		if ($reload === 'Y') {
			$matters = $model->getMatters($id);
			return new \ResponseData($matters);
		} else {
			return new \ResponseData($rst);
		}
	}
}