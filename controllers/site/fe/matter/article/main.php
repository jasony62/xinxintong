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
	public function index_action($siteId) {
		$modelArticle = $this->model('matter\article');
		$article = $modelArticle->byId($id);
		if (false === $article) {
			return new \ObjectNotFoundError();
		}

		$this->checkEntryRule($article, true);

		\TPL::output('/site/fe/matter/article/list');
		exit;
	}
	/**
	 * 检查登记活动参与规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 */
	protected function checkEntryRule($oApp, $bRedirect = false) {
		if (!isset($oApp->entryRule->scope)) {
			return [true];
		}
		$oUser = $this->who;
		$oEntryRule = $oApp->entryRule;
		$oScope = $oEntryRule->scope;

		if (isset($oScope->member) && $oScope->member === 'Y') {
			$aResult = $this->enterAsMember($oApp);
			/**
			 * 限通讯录用户访问
			 * 如果指定的任何一个通讯录要求用户关注公众号，但是用户还没有关注，那么就要求用户先关注公众号，再填写通讯录
			 */
			if (false === $aResult[0]) {
				if (true === $bRedirect) {
					$aMemberSchemaIds = [];
					$modelMs = $this->model('site\user\memberschema');
					foreach ($oEntryRule->member as $mschemaId => $oRule) {
						$oMschema = $modelMs->byId($mschemaId, ['fields' => 'is_wx_fan', 'cascaded' => 'N']);
						if ($oMschema->is_wx_fan === 'Y') {
							$oApp2 = clone $oApp;
							$oApp2->entryRule = new \stdClass;
							$oApp2->entryRule->sns = (object) ['wx' => (object) ['entry' => 'Y']];
							$aResult = $this->checkSnsEntryRule($oApp2, $bRedirect);
							if (false === $aResult[0]) {
								return $aResult;
							}
						}
						$aMemberSchemaIds[] = $mschemaId;
					}
					$this->gotoMember($oApp, $aMemberSchemaIds);
				} else {
					$msg = '您没有填写通讯录信息，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
					return [false, $msg];
				}
			}
		}
		if (isset($oScope->sns) && $oScope->sns === 'Y') {
			$aResult = $this->checkSnsEntryRule($oApp, $bRedirect);
			if (false === $aResult[0]) {
				return $aResult;
			}
		}
		if (isset($oScope->group) && $oScope->group === 'Y') {
			$bMatched = false;
			/* 限分组用户访问 */
			if (isset($oEntryRule->group->id)) {
				$oGroupApp = $this->model('matter\group')->byId($oEntryRule->group->id, ['fields' => 'id,state,title']);
				if ($oGroupApp && $oGroupApp->state === '1') {
					$oGroupUsr = $this->model('matter\group\player')->byUser($oGroupApp, $oUser->uid, ['fields' => 'round_id,round_title']);
					if (count($oGroupUsr)) {
						$oGroupUsr = $oGroupUsr[0];
						if (isset($oEntryRule->group->round->id)) {
							if ($oGroupUsr->round_id === $oEntryRule->group->round->id) {
								$bMatched = true;
							}
						} else {
							$bMatched = true;
						}
					}
				}
			}
			if (false === $bMatched) {
				$msg = '您目前的分组，不满足【' . $oApp->title . '】的参与规则，无法访问，请联系活动的组织者解决。';
				if (true === $bRedirect) {
					$this->outputInfo($msg);
				} else {
					return [false, $msg];
				}
			}
		}

		return [true];
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

		/*如果此单图文属于引用那么需要返回被引用的单图文*/
		if ($article->from_mode === 'C') {
			$id2 = $article->from_id;
			$article2 = $modelArticle->byId($id2, ['fields' => 'body,author,siteid,id']);
			$article->body = $article2->body;
			$article->author = $article2->author;
		}
		/* 单图文所属的频道 */
		$article->channels = $this->model('matter\channel')->byMatter($id, 'article', ['public_visible' => 'Y']);
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