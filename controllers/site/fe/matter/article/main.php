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
		\TPL::output('/matter/article-list');
		exit;
	}
	/**
	 * 返回请求的素材
	 *
	 * $siteId
	 * $id
	 */
	public function get_action($site, $id) {
		$user = $this->who;

		$modelArticle = $this->model('matter\article2');
		$article = $modelArticle->byId($id);
		if (isset($article->access_control) && $article->access_control === 'Y' && !empty($article->authapis)) {
			$this->accessControl($site, $id, $article->authapis, $user->uid, $article, false);
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
		$article->tags = $modelArticle->tags($id);
		if ($article->has_attachment === 'Y') {
			$article->attachments = $this->model()->query_objs_ss(
				array(
					'*',
					'xxt_article_attachment',
					"article_id='$id'",
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
		$model = $this->model('matter\article2');

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
		$modelArt = $this->model('matter\article2');
		$article = $modelArt->byId($id, 'title,score');
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
			 * 投稿人点赞不奖励积分
			 */
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
	 * 发表评论
	 *
	 * 如果公众号支持客服消息或者点对点，如果文章的投稿者具备接收客户消息的条件
	 * 如果投稿人设定了接收客服消息
	 * 那么每次有新的评论都发送一条提醒消息给投稿人
	 *
	 * @param int $id article's id.
	 * @param string $siteId article's mpid.
	 */
	public function remark_action($siteId, $id) {
		if (isset($_POST['remark'])) {
			$posted = new \stdClass;
			$posted->remark = $_POST['remark'];
		} else {
			$posted = $this->getPostJson();
		}
		if (empty($posted->remark)) {
			return new \ResponseError('评论不允许为空！');
		}
		$user = $this->getUser($siteId, array('verbose' => array('fan' => 'Y')));
		if (empty($user->openid)) {
			return new \ResponseError('无法获得用户标识，不允许发布评论');
		}
		$modelArticle = $this->model('matter\article');
		$art = $modelArticle->byId($id, 'title,creater,creater_src,remark_notice,remark_notice_all');
		/**
		 * 插入一条新评论
		 */
		$i = array(
			'mpid' => $siteId,
			'fid' => $user->fan->fid,
			'openid' => $user->openid,
			'nickname' => $user->nickname,
			'article_id' => $id,
			'article_title' => $art->title,
			'create_at' => time(),
			'remark' => $this->model()->escape($posted->remark),
		);
		$remarkId = $modelArticle->insert('xxt_article_remark', $i, true);
		$modelArticle->update("update xxt_article set remark_num=remark_num+1 where id='$id'");
		/**
		 * 获得完整的评论数据
		 */
		$remark = $modelArticle->remarks($id, $remarkId);
		/**
		 * 是否为投稿文章，投稿人是否要接收评论？？？
		 */
		$receivers = array();
		if ($art->creater_src === 'F' && $art->remark_notice === 'Y' && $art->creater !== $user->fan->fid) {
			/**
			 * 投稿人接收评论提醒
			 */
			$creater = $this->model('user/fans')->byId($art->creater);
			$receivers[] = $creater->openid;
		}
		/**
		 * 通知指定的评论接收人
		 */
		if ($art->remark_notice_all === 'Y') {
			/**
			 * 获得所有发表过评论的人
			 */
			$others = $modelArticle->remarkers($id);
			foreach ($others as $other) {
				$other->openid !== $user->openid && !in_array($other->openid, $receivers) && $receivers[] = $other->openid;
			}
		} else if (false !== strpos($remark->remark, '@')) {
			/**
			 * 通知指定的人
			 */
			$others = $modelArticle->remarkers($id);
			foreach ($others as $other) {
				if (false !== strpos($remark->remark, '@' . $other->nickname)) {
					$other->openid !== $user->openid && !in_array($other->openid, $receivers) && $receivers[] = $other->openid;
				}
			}
		}
		/**
		 * coin log
		 * 投稿人评论不奖励积分
		 */
		$modelCoin = $this->model('coin\log');
		$contribution = $modelArticle->getContributionInfo($id);
		if (!empty($contribution->openid) && $contribution->openid !== $user->openid) {
			// for contributor
			$action = 'app.' . $contribution->entry . '.article.remark';
			$modelCoin->income($siteId, $action, $id, 'sys', $contribution->openid);
		}
		if (empty($contribution->openid) || $contribution->openid !== $user->openid) {
			// for reader
			$modelCoin->income($siteId, 'mp.matter.article.remark', $id, 'sys', $user->openid);
		}
		/**
		 * 通知接收评论
		 * @todo 应该改为模版消息实现
		 */
		if (!empty($receivers)) {
			$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/mi/matter?mpid=$siteId&id=$id&tpl=std";
			$text = urlencode($remark->nickname);
			$text .= urlencode('对【');
			$text .= '<a href="' . $url . '">';
			$text .= urlencode($a->title);
			$text .= urlencode('</a>】发表了评论：');
			$text .= urlencode($remark->remark);
			$message = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $text,
				),
			);
			/**
			 * 获得所有发表过评论的人？？？
			 */
			//foreach ($receivers as $receiver) {
			//	$this->sendByOpenid($siteId, $receiver, $message);
			//}
		}

		return new \ResponseData($remark);
	}
	/**
	 * 下载附件
	 */
	public function attachmentGet_action($site, $articleid, $attachmentid) {
		$user = $this->who;
		/**
		 * 访问控制
		 */
		$modelArticle = $this->model('matter\article2');
		$article = $modelArticle->byId($articleid);
		if (isset($article->access_control) && $article->access_control === 'Y') {
			$this->accessControl($site, $articleid, $article->authapis, $user->uid, $article, false);
		}
		/**
		 * 记录日志
		 */
		$this->model()->update("update xxt_article set download_num=download_num+1 where id='$articleid'");
		$log = array(
			'userid' => $user->uid,
			'nickname' => $user->nickname,
			'download_at' => time(),
			'siteid' => $site,
			'article_id' => $articleid,
			'attachment_id' => $attachmentid,
			'user_agent' => $_SERVER['HTTP_USER_AGENT'],
			'client_ip' => $this->client_ip(),
		);
		$this->model()->insert('xxt_article_download_log', $log, false);
		/**
		 * 获取附件
		 */
		$q = array(
			'*',
			'xxt_article_attachment',
			"article_id='$articleid' and id='$attachmentid'",
		);
		$att = $this->model()->query_obj_ss($q);

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