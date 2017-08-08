<?php
namespace matter\article;
/**
 * 复制单图文
 */
class copy_model extends \TMS_MODEL {
	/**
	 * 跨团队转发单图文
	 *
	 * @param int $id
	 * @param array [{siteid:'',channelId:''}]
	 *
	 */
	public function forward($id, $aTargetSites, $oUser, $fromSiteId = null) {
		$modelArt = $this->model('matter\article');
		$modelArt->setOnlyWriteDbConn(true);
		$modelLog = $this->model('matter\log');
		$modelChn = $this->model('matter\channel');
		$modelChn->setOnlyWriteDbConn(true);

		$copied = $modelArt->byId($id);
		empty($fromSiteId) && $fromSiteId = $copied->siteid;
		/*获取元图文的团队名称*/
		$fromSite = $this->model('site')->byId($fromSiteId, ['fields' => 'name']);
		$current = time();

		$newArticle = new \stdClass;
		$newArticle->creater = $oUser->id;
		$newArticle->creater_src = 'A';
		$newArticle->creater_name = $modelArt->escape($oUser->name);
		$newArticle->create_at = $current;
		$newArticle->modifier = $oUser->id;
		$newArticle->modifier_src = 'A';
		$newArticle->modifier_name = $modelArt->escape($oUser->name);
		$newArticle->modify_at = $current;
		$newArticle->author = $modelArt->escape($oUser->name);
		$newArticle->summary = $modelArt->escape($copied->summary);
		/* 引用情况下，不复制文章的正文 */
		//$newArticle->body = $modelArt->escape($copied->body);
		$newArticle->body = '';
		$newArticle->hide_pic = $copied->hide_pic;
		$newArticle->url = $copied->url;
		$newArticle->can_siteuser = $copied->can_siteuser;
		$newArticle->from_siteid = $modelArt->escape($fromSiteId);
		$newArticle->from_site_name = $modelArt->escape($fromSite->name);
		$newArticle->from_id = $modelArt->escape($id);
		$newArticle->title = $modelArt->escape($copied->title);

		/* 复制到其他团队 */
		$newArticle->from_mode = 'C';
		foreach ($aTargetSites as $targetSite) {
			$targetSiteId = $modelArt->escape($targetSite->siteid);
			$newArticle->siteid = $targetSiteId;
			if ($copied->siteid === $targetSiteId) {
				continue;
			}
			if (isset($newArticle->type)) {
				unset($newArticle->type);
			}
			if (isset($newArticle->id)) {
				unset($newArticle->id);
			}
			$newArticle->id = $modelArt->insert('xxt_article', $newArticle, true);

			$newArticle->type = 'article';

			/* 放入指定的频道 */
			if (!empty($targetSite->channelId)) {
				$modelChn->addMatter($targetSite->channelId, $newArticle, $oUser->id, $oUser->name);
			}

			/* 记录操作日志 */
			$modelLog->matterOp($targetSiteId, $oUser, $newArticle, 'C');

			/* 增加原图文的复制数 */
			if ($copied->siteid !== $targetSiteId) {
				$modelArt->update("update xxt_article set copy_num = copy_num +1 where id = $id");
			}
		}

		return true;
	}
}