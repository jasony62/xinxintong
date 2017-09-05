<?php
namespace site\fe\matter\contribute;

include_once dirname(__FILE__) . '/base.php';
/**
 * 投稿活动
 */
class entry extends \site\fe\base {
	/**
	 * 获得当前用户的信息
	 *
	 * @param string $site
	 *
	 */
	public function list_action($site, $app = null) {
		/* 身份信息*/
		$user = $this->who;
		$mine = array();
		$members = $this->model('site\user\member')->byUser($user->uid);
		foreach ($members as $member) {
			/**
			 * 检查用户是否通过了验证
			 */
			$q = array(
				'verified',
				'xxt_site_member',
				"siteid='$site' and id='$member->id'",
			);
			if ('Y' !== $this->model()->query_val_ss($q)) {
				$r = $this->model('site\user\memberschema')->getNotpassStatement($member->schema_id, $site);
				$protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
				header($protocol . ' 401 Unauthorized');
				\TPL::assign('title', '访问控制未通过');
				\TPL::assign('body', $r);
				\TPL::output('error');
				exit;
			}
		}
		if (!empty($members)) {
			/**
			 * 投稿活动
			 */
			$entries = $this->model('matter\contribute')->bySite($site, $app);

			$member = $members[0];
			if (!empty($entries)) {
				$modelAcl = $this->model('acl');
				foreach ($entries as $entry) {
					/* 可以参与投稿？ */
					$set = "cid='$entry->id' and role='I'";
					$entry->isInitiator = $modelAcl->canAccess2(
						$site,
						'xxt_contribute_user',
						$set,
						$member->id,
						array($member->schema_id), false);
					/* 可以参与审稿？ */
					$set = "cid='$entry->id' and role='R'";
					$entry->isReviewer = $modelAcl->canAccess2(
						$site,
						'xxt_contribute_user',
						$set,
						$member->id,
						array($member->schema_id), true);
					/* 可以参与版面？ */
					$set = "cid='$entry->id' and role='T'";
					$entry->isTypesetter = $modelAcl->canAccess2(
						$site,
						'xxt_contribute_user',
						$set,
						$member->id,
						array($member->schema_id), true);
					//
					if ($entry->isInitiator || $entry->isReviewer || $entry->isTypesetter) {
						$entry->pk = 'contribute,' . $entry->id;
						$mine[] = $entry;
					}
				}
			}
		}

		$params = array();
		$params['entries'] = $mine;
		$params['user'] = $user;

		return new \ResponseData($params);
	}
	/**
	 * 获得投稿活动定义
	 */
	public function get_action($site, $type, $id) {
		$modelCtrb = $this->model('matter\contribute');

		$c = $modelCtrb->byId($id);
		/**
		 * 设置投稿子频道（允许投稿人指定的频道）
		 */
		if (!empty($c->params)) {
			$modelCh = $this->model('matter\channel');
			$params = json_decode($c->params);
			if (!empty($params->subChannels)) {
				foreach ($params->subChannels as $scid) {
					$ch = $modelCh->byId($scid, 'id,title');
					$chs[] = $ch;
				}
				$c->subChannels = $chs;
			}
		}
		/**
		 * 提示在PC端完成
		 */
		if (in_array($this->userAgent(), array('wx', 'yx')) && isset($c->shift2pc) && $c->shift2pc === 'Y') {
			/**
			 * 获得用户信息
			 */
			$entry = 'contribute,' . $c->id;
			$myUrl = 'http://' . APP_HTTP_HOST . "/rest/site/fe/matter/contribute/initiate?site=$site&entry=$entry";
			/**
			 * 提示在PC端完成
			 */
			$oSite = $this->model('site')->byId($site, 'shift2pc_page_id');
			$page = $this->model('code\page')->byId($oSite->shift2pc_page_id, 'html,css,js');
			/**
			 * 任务码
			 */
			$user = new \stdClass;
			$user->id = $this->who->uid;
			$user->name = $this->who->nickname;
			if ($c->can_taskcode && $c->can_taskcode === 'Y') {
				$taskCode = $this->model('q\url')->add($user, $site, $myUrl);
				$page->html = str_replace('{{taskCode}}', $taskCode, $page->html);
			}
			$c->pageShift2Pc = $page;
		}
		/**
		 * 审稿人列表
		 */
		$modelMem = $this->model('site\user\member');
		$c->reviewers = $modelCtrb->editors($site, $id, 'R');
		foreach ($c->reviewers as &$reviewer) {
			switch ($reviewer->idsrc) {
			case 'M':
				$reviewer->member = $modelMem->byId($reviewer->identity);
				break;
			}
		}
		/**
		 * 发稿人列表
		 */
		$c->typesetters = $modelCtrb->editors($site, $id, 'T');
		foreach ($c->typesetters as &$typesetter) {
			switch ($typesetter->idsrc) {
			case 'M':
				$typesetter->member = $modelMem->byId($reviewer->identity);
				break;
			}
		}

		$c->user = $this->who;

		return new \ResponseData($c);
	}
}