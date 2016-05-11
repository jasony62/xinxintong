<?php
namespace app\contribute;

require_once dirname(dirname(dirname(__FILE__))) . '/member_base.php';
/**
 * 投稿活动
 */
class main extends \member_base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'black';
		$rule_action['actions'] = array();

		return $rule_action;
	}
	/**
	 * 获得当前用户的信息
	 * $mpid
	 * $entry
	 */
	public function index_action($mpid, $entry = null, $code = null, $mocker = null) {

		$openid = $this->doAuth($mpid, $code, $mocker);

		$myUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/contribute?mpid=$mpid";
		/**身份信息*/
		$user = $this->getUser($mpid, array('openid' => $openid, 'verbose' => array('member' => 'Y')));
		/**必须是关注用户*/
		$this->getClientSrc() && $this->askFollow($mpid, $openid);
		/**
		 * 必须是认证用户
		 */
		$authids = array();
		$authapis = $this->model('user/authapi')->byMpid($mpid, 'Y');
		foreach ($authapis as $aa) {
			$authids[] = $aa->authid;
		}

		empty($user->members) && $this->gotoAuth($mpid, $authids, $user->openid, $myUrl);
		/**
		 * 投稿活动
		 */
		if ($entry === null) {
			$entries = $this->model('app\contribute')->byMpid($mpid);
		} else {
			$entry = explode(',', $entry);
			$entry = $this->model('app\contribute')->byId($entry[1]);
			$entries = array($entry);
		}

		$member = $user->members[0];
		$authids = implode(',', $authids);
		$mine = array();
		if (!empty($entries)) {
			foreach ($entries as $entry) {
				// 可以参与投稿？
				$set = "cid='$entry->id' and role='I'";
				$entry->isInitiator = $this->model('acl')->canAccess(
					$mpid,
					'xxt_contribute_user',
					$set,
					$member->authed_identity,
					$authids, false);
				// 可以参与审稿？
				$set = "cid='$entry->id' and role='R'";
				$entry->isReviewer = $this->model('acl')->canAccess(
					$mpid,
					'xxt_contribute_user',
					$set,
					$member->authed_identity,
					$authids, true);
				// 可以参与版面？
				$set = "cid='$entry->id' and role='T'";
				$entry->isTypesetter = $this->model('acl')->canAccess(
					$mpid,
					'xxt_contribute_user',
					$set,
					$member->authed_identity,
					$authids, true);
				//
				if ($entry->isInitiator || $entry->isReviewer || $entry->isTypesetter) {
					$entry->pk = 'contribute,' . $entry->id;
					$mine[] = $entry;
				}
			}
		}

		if (count($mine) === 1) {
			$entry = $mine[0];
			$roles = array();
			$entry->isInitiator && $roles[] = 'initiate';
			$entry->isReviewer && $roles[] = 'review';
			$entry->isTypesetter && $roles[] = 'typeset';
			if (count($roles) === 1) {
				$url = '/rest/app/contribute/' . $roles[0];
				$url .= '?mpid=' . $mpid;
				$url .= '&entry=' . $entry->pk;
				$this->redirect($url);
			}
		}
		$params = array();
		$params['mpid'] = $mpid;
		$params['entries'] = $mine;

		\TPL::assign('params', $params);
		$this->view_action('/app/contribute/main');
	}
	/**
	 * 获得当前访问用户的信息
	 *
	 * $mpid
	 */
	private function getCurrentUserInfo($mpid, $callbackUrl) {
		$fan = $this->getCookieOAuthUser($mpid);

		$authapis = $this->model('user/authapi')->byMpid($mpid, 'Y');
		$aAuthids = array();
		foreach ($authapis as $a) {
			$aAuthids[] = $a->authid;
		}

		$members = $this->authenticate($mpid, $aAuthids, $callbackUrl, $fan->openid);
		empty($members) && $this->outputError('无法获得用户认证信息');

		$mid = $members[0]->mid;
		$fan = $this->model('user/fans')->byMid($mid, 'fid,openid');
		$vid = $this->getVisitorId($mpid);

		return array($fan->fid, $fan->openid, $mid, $vid);
	}
	/**
	 * 获得投稿活动定义
	 */
	public function entryGet_action($mpid, $type, $id) {
		$modelCtrb = $this->model('app\contribute');

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
		if ($this->getClientSrc() && isset($c->shift2pc) && $c->shift2pc === 'Y') {
			/**
			 * 获得用户信息
			 */
			$entry = 'contribute,' . $c->id;
			$myUrl = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/contribute/initiate?mpid=$mpid&entry=$entry";
			list($fid) = $this->getCurrentUserInfo($mpid, $myUrl);
			/**
			 * 提示在PC端完成
			 */
			$fea = $this->model('mp\mpaccount')->getFeature($mpid, 'shift2pc_page_id');
			$page = $this->model('code\page')->byId($fea->shift2pc_page_id, 'html,css,js');
			/**
			 * 任务码
			 */
			if ($c->can_taskcode && $c->can_taskcode === 'Y') {
				$taskCode = $this->model('task')->addTask($mpid, $fid, $myUrl);
				$page->html = str_replace('{{taskCode}}', $taskCode, $page->html);
			}
			$c->pageShift2Pc = $page;
		}
		/**
		 * 审稿人列表
		 */
		$c->reviewers = $modelCtrb->userAcls($mpid, $id, 'R');
		foreach ($c->reviewers as &$reviewer) {
			switch ($reviewer->idsrc) {
			case 'M':
				$reviewer->member = $this->model('user/member')->byId($reviewer->identity);
				break;
			}
		}
		/**
		 * 当前用户
		 */
		$c->user = $this->getUser($mpid, array('verbose' => array('fan' => 'Y', 'member' => 'Y')));

		return new \ResponseData($c);
	}
}