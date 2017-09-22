<?php
namespace site\fe\matter\article;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 单图文
 */
class main extends \site\fe\matter\base {
	/**
	 *
	 */
	protected function canAccessObj($siteId, $matterId, $member, $authapis, &$matter) {
		return $this->model('matter\acl')->canAccessMatter($siteId, 'article', $matterId, $member, $authapis);
	}
	/**
	 *
	 */
	public function index_action($siteId) {
		\TPL::output('/site/fe/matter/article/list');
		exit;
	}
	/**
	 * 返回请求的素材
	 *
	 * @param strng $site
	 * @param int $id
	 */
	public function get_action($site, $id) {
		$model = $this->model();
		$user = $this->who;

		$modelArticle = $this->model('matter\article');
		$article = $modelArticle->byId($id);
		if (false === $article) {
			return new \ObjectNotFoundError();
		}

		if (isset($article->access_control) && $article->access_control === 'Y' && !empty($article->authapis)) {
			$this->accessControl($site, $id, $article->authapis, $user->uid, $article, false);
		}
		/*如果此单图文属于引用那么需要返回被引用的单图文*/
		if ($article->from_mode === 'C') {
			$id2 = $article->from_id;
			$article2 = $modelArticle->byId($id2, ['fields' => 'body,author,siteid,id']);
			$article->body = $article2->body;
			$article->author = $article2->author;
		}
		/* 单图文所属的频道 */
		$article->channels = $this->model('matter\channel')->byMatter($id, 'article');
		$modelCode = $this->model('code\page');
		foreach ($article->channels as &$channel) {
			if ($channel->style_page_id) {
				$channel->style_page = $modelCode->lastPublishedByName($site, $channel->style_page_name, 'id,html,css,js');
			}
		}
		/* 单图文所属的标签 */
		$tags = [];
		if (!empty($article->matter_cont_tag)) {
			foreach ($article->matter_cont_tag as $key => $tagId) {
				$T = [
					'id,title',
					'xxt_tag',
					['id' => $tagId],
				];
				$tag = $model->query_obj_ss($T);
				$tags[] = $tag;
			}
		}
		$article->tags = $tags;
		if ($article->has_attachment === 'Y') {
			$article->attachments = $model->query_objs_ss(
				array(
					'*',
					'xxt_article_attachment',
					['article_id' => $id],
				)
			);
		}
		if ($article->custom_body === 'N') {
			$article->remarks = $article->remark_num > 0 ? $modelArticle->remarks($id) : false;
			$article->praised = $modelArticle->praised($user, $id);
		} else if ($article->page_id) {
			/* 定制页 */
			$modelCode = $this->model('code\page');
			$article->page = $modelCode->lastPublishedByName($site, $article->body_page_name);
		}
		$data = array();
		$data['article'] = &$article;
		$data['user'] = &$user;
		/* 站点信息 */
		if ($article->use_site_header === 'Y' || $article->use_site_footer === 'Y') {
			$site = $this->model('site')->byId(
				$site,
				array('cascaded' => 'header_page_name,footer_page_name')
			);
		} else {
			$site = $this->model('site')->byId($site);
		}
		$data['site'] = &$site;
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/yixin/i', $userAgent)) {
			$site->yx = $this->model('sns\yx')->bySite($site->id, 'cardname,cardid');
		}
		/*项目页面设置*/
		if ($article->use_mission_header === 'Y' || $article->use_mission_footer === 'Y') {
			if ($article->mission_id) {
				$data['mission'] = $this->model('matter\mission')->byId(
					$article->mission_id,
					array('cascaded' => 'header_page_name,footer_page_name')
				);
			}
		}

		return new \ResponseData($data);
	}
	/**
	 *
	 */
	public function list_action($site, $tagid, $page = 1, $size = 10) {
		$model = $this->model('matter\article');

		$user = $this->who;

		$options = new \stdClass;
		$options->tag = array($tagid);

		$result = $model->find($site, $user, $page, $size, $options);

		return new \ResponseData($result);
	}
	/**
	 * 文章点赞
	 *
	 * $siteId
	 * $id article's id.
	 * $scope 分数
	 */
	public function score_action($site, $id, $score = 1) {
		$modelArt = $this->model('matter\article');
		$article = $modelArt->byId($id);
		$user = $this->who;
		if ($modelArt->praised($user, $id)) {
			/* 点了赞，再次点击，取消赞 */
			$this->model()->delete('xxt_article_score', "article_id='$id' and userid='{$user->uid}'");
			$this->model()->update("update xxt_article set score=score-$score where id='$id'");
			$praised = false;
			$article->score--;
		} else {
			/* 点赞 */
			$log = array(
				'siteid' => $site,
				'userid' => $user->uid,
				'nickname' => $user->nickname,
				'article_id' => $id,
				'article_title' => $article->title,
				'create_at' => time(),
				'score' => $score,
			);
			$this->model()->insert('xxt_article_score', $log);
			$this->model()->update("update xxt_article set score=score+$score where id='$id'");
			$praised = true;
			$article->score++;
			/**
			 * coin log
			 * 用户a点赞b的投稿两个人都奖励积分
			 */
			$modelCoin = $this->model('site\coin\log');
			$modelCoin->award($article, $user, 'site.matter.article.like');
			/*$modelCoin = $this->model('coin\log');
				$contribution = $modelArt->getContributionInfo($id);
				if (!empty($contribution->openid) && $contribution->openid !== $user->openid) {
					// for contributor
					$action = 'app.' . $contribution->entry . '.article.appraise';
					$modelCoin->income($siteId, $action, $id, 'sys', $contribution->openid);
				}
				if (empty($contribution->openid) || $contribution->openid !== $user->openid) {
					// for reader
					$modelCoin->income($site, 'mp.matter.article.appraise', $id, 'sys', $user->uid);
			*/
		}

		return new \ResponseData(array($article->score, $praised));
	}
	/**
	 * 下载附件
	 */
	public function attachmentGet_action($site, $articleid, $attachmentid) {
		if (empty($site) || empty($articleid) || empty($attachmentid)) {
			die('没有指定有效的附件');
		}

		$user = $this->who;
		/**
		 * 访问控制
		 */
		$modelArticle = $this->model('matter\article');
		$article = $modelArticle->byId($articleid);
		if (isset($article->access_control) && $article->access_control === 'Y') {
			$this->accessControl($site, $articleid, $article->authapis, $user->uid, $article);
		}

		/**
		 * 获取附件
		 */
		$q = [
			'*',
			'xxt_article_attachment',
			['article_id' => $articleid, 'id' => $attachmentid],
		];
		if (false === ($att = $modelArticle->query_obj_ss($q))) {
			die('指定的附件不存在');
		}

		/**
		 * 记录日志
		 */
		$site = $modelArticle->escape($site);
		$articleid = $modelArticle->escape($articleid);
		$attachmentid = $modelArticle->escape($attachmentid);
		$modelArticle->update("update xxt_article set download_num=download_num+1 where id='$articleid'");
		$log = [
			'userid' => $user->uid,
			'nickname' => $user->nickname,
			'download_at' => time(),
			'siteid' => $site,
			'article_id' => $articleid,
			'attachment_id' => $attachmentid,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'client_ip' => $this->client_ip(),
		];
		$modelArticle->insert('xxt_article_download_log', $log, false);

		if (strpos($att->url, 'alioss') === 0) {
			$downloadUrl = 'http://xxt-attachment.oss-cn-shanghai.aliyuncs.com/' . $site . '/article/' . $articleid . '/' . urlencode($att->name);
			$this->redirect($downloadUrl);
		} else if (strpos($att->url, 'local') === 0) {
			$fs = $this->model('fs/local', $site, '附件');
			//header("Content-Type: application/force-download");
			header("Content-Type: $att->type");
			header("Content-Disposition: attachment; filename=" . $att->name);
			header('Content-Length: ' . $att->size);
			echo $fs->read(str_replace('local://', '', $att->url));
		} else {
			$fs = $this->model('fs/saestore', $site);
			//header("Content-Type: application/force-download");
			header("Content-Type: $att->type");
			header("Content-Disposition: attachment; filename=" . $att->name);
			header('Content-Length: ' . $att->size);
			echo $fs->read($att->url);
		}

		exit;
	}
}