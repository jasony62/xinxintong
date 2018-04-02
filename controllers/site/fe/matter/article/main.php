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
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		if (preg_match('/yixin/i', $userAgent)) {
			$oSite->yx = $this->model('sns\yx')->bySite($oSite->id, 'cardname,cardid');
		}
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
		$article = $modelArticle->byId($articleid);
		/**
		 * 获取附件
		 */
		$q = [
			'*',
			'xxt_matter_attachment',
			['matter_id' => $articleid, 'matter_type' => 'article', 'id' => $attachmentid],
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