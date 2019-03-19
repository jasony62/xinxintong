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
		$oUser = $this->who;

		$modelArticle = $this->model('matter\article');
		$oArticle = $modelArticle->byId($id);
		if (false === $oArticle) {
			return new \ObjectNotFoundError();
		}
		/*如果此单图文属于引用那么需要返回被引用的单图文*/
		if ($oArticle->from_mode === 'C') {
			$id2 = $oArticle->from_id;
			$oArticle2 = $modelArticle->byId($id2, ['fields' => 'body,author,siteid,id']);
			$oArticle->body = $oArticle2->body;
			$oArticle->author = $oArticle2->author;
		}
		/* 单图文所属的频道 */
		$oArticle->channels = $this->model('matter\channel')->byMatter($oArticle->id, 'article', ['public_visible' => 'Y']);
		if (count($oArticle->channels) && !isset($oArticle->config->nav->app)) {
			$aNavApps = [];
			foreach ($oArticle->channels as $oChannel) {
				if (!empty($oChannel->config->nav->app)) {
					$aNavApps = array_merge($aNavApps, $oChannel->config->nav->app);
				}
			}
			if (!isset($oArticle->config->nav)) {
				$oArticle->config->nav = new \stdClass;
			}
			$oArticle->config->nav->app = $aNavApps;
		}
		$modelCode = $this->model('code\page');
		foreach ($oArticle->channels as &$channel) {
			if ($channel->style_page_id) {
				$channel->style_page = $modelCode->lastPublishedByName($site, $channel->style_page_name, 'id,html,css,js');
			}
		}
		/* 单图文所属的标签 */
		$tags = [];
		if (!empty($oArticle->matter_cont_tag)) {
			foreach ($oArticle->matter_cont_tag as $key => $tagId) {
				$T = [
					'id,title',
					'xxt_tag',
					['id' => $tagId],
				];
				$tag = $modelArticle->query_obj_ss($T);
				$tags[] = $tag;
			}
		}
		$oArticle->tags = $tags;
		if ($oArticle->has_attachment === 'Y') {
			$oArticle->attachments = $modelArticle->query_objs_ss(
				array(
					'*',
					'xxt_matter_attachment',
					['matter_id' => $id, 'matter_type' => 'article'],
				)
			);
		}
		if ($oArticle->custom_body === 'Y' && $oArticle->page_id) {
			/* 定制页 */
			$modelCode = $this->model('code\page');
			$oArticle->page = $modelCode->lastPublishedByName($oArticle->siteid, $oArticle->body_page_name);
		}
		$aData = array();
		$aData['article'] = $oArticle;
		$aData['user'] = $oUser;
		/* 站点信息 */
		if ($oArticle->use_site_header === 'Y' || $oArticle->use_site_footer === 'Y') {
			$oSite = $this->model('site')->byId(
				$site,
				['cascaded' => 'header_page_name,footer_page_name']
			);
		} else {
			$oSite = $this->model('site')->byId($site);
		}
		$aData['site'] = $oSite;
		/*项目页面设置*/
		if ($oArticle->use_mission_header === 'Y' || $oArticle->use_mission_footer === 'Y') {
			if ($oArticle->mission_id) {
				$aData['mission'] = $this->model('matter\mission')->byId(
					$oArticle->mission_id,
					['cascaded' => 'header_page_name,footer_page_name']
				);
			}
		}

		return new \ResponseData($aData);
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
		$oArticle = $modelArticle->byId($articleid);
		if ($oArticle === false || $oArticle->state !== '1') {
			die('指定的活动不存在，请检查参数是否正确');
		}
		$this->checkDownloadRule($oArticle, true);
		/**
		 * 记录日志
		 */
		$modelArticle->update("update xxt_article set download_num=download_num+1 where id='$articleid'");
		$this->attachmentGet($oArticle, $attachmentid);
	}
	/**
	 * 检查附件下载规则
	 *
	 * @param object $oApp
	 * @param boolean $redirect
	 *
	 */
	private function checkDownloadRule($oApp, $bRedirect = false) {
		$oApp2 = clone $oApp;
		$oApp2->entryRule = $oApp->downloadRule;
		$oApp2->entry_rule = $oApp->download_rule;

		$results = $this->checkEntryRule($oApp2, $bRedirect);

		return $results;
	}
}