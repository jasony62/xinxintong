<?php
namespace app\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动
 */
class record extends base {
	/**
	 *
	 */
	public function submitkeyGet_action() {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		$key = md5(uniqid() . mt_rand());

		return new \ResponseData($key);
	}
	/**
	 * 报名登记页，记录登记信息
	 *
	 * $mpid
	 * $aid
	 * $ek enrollKey 如果要更新之前已经提交的数据，需要指定
	 * $submitkey
	 */
	public function submit_action($mpid, $aid, $ek = null, $submitkey = '') {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods:POST');
		header('Access-Control-Allow-Headers:Content-Type');
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit;
		}

		empty($mpid) && die('mpid is empty.');
		empty($aid) && die('aid is empty.');

		$model = $this->model('app\enroll');
		if (false === ($act = $model->byId($aid))) {
			die('活动不存在');
		}
		/**
		 * 当前访问用户的基本信息
		 */
		$user = $this->getUser($mpid,
			array(
				'authapis' => $act->authapis,
				'matter' => $act,
				'verbose' => array('member' => 'Y', 'fan' => 'Y'),
			)
		);
		/**
		 * 当前用户是否可以进行提交操作
		 */
		if (empty($user->fan)) {
			/**
			 * 非关注用户
			 */
			$rule = $act->entry_rule->nonfan->enroll;
		} else {
			if (isset($user->fan)) {
				/* 关注用户 */
				$rule = $act->entry_rule->fan->enroll;
			}
			if (isset($user->membersInAcl) && !empty($user->members)) {
				/* 认证用户不在白名单中 */
				$rule = $act->entry_rule->member_outacl->enroll;
			}
			if (!empty($user->membersInAcl) || (!isset($user->membersInAcl) && !empty($user->members))) {
				/* 白名单中的认证用户，或者，不限制白名单的认证用户，允许登记 */
				$rule = 'Y';
			}
		}
		switch ($rule) {
		case '$authapi_outacl':
			$actAuthapis = explode(',', $act->authapis);
			$this->gotoOutAcl($mpid, $actAuthapis[0]);
			break;
		case '$mp_follow':
			$this->askFollow($mpid, $user->openid);
			break;
		case '$authapi_auth':
			$this->gotoAuth($mpid, $act->authapis, $user->openid, false);
			break;
		}
		/**
		 * 处理提交数据
		 */
		$posted = $this->getPostJson();
		$mid = empty($user->membersInAcl) ? '' : $user->membersInAcl[0]->mid;
		/**
		 * 包含用户身份信息
		 */
		if (isset($posted->member) && isset($posted->member->authid)) {
			$rst = $this->submitMember($mpid, $posted, $user->fan->fid, $mid);
			if ($rst[0] === false) {
				return new \ParameterError($rst[1]);
			}
		}
		/**
		 * 处理提交数据
		 */
		if (empty($ek)) {
			/* 插入报名数据 */
			$ek = $model->enroll($mpid, $act, $user->openid, $user->vid, $mid);
			/* 处理自定义信息 */
			$rst = \TMS_APP::M('app\enroll\record')->setData($user, $mpid, $aid, $ek, $posted, $submitkey);
		} else {
			/* 已经登记，更新原先提交的数据 */
			$this->model()->update('xxt_enroll_record',
				array('enroll_at' => time()),
				"enroll_key='$ek'"
			);
			/* 重新插入新提交的数据 */
			$rst = \TMS_APP::M('app\enroll\record')->setData($user, $mpid, $aid, $ek, $posted, $submitkey);
		}
		if (false === $rst[0]) {
			return new ResponseError($rst[1]);
		}
		/**
		 * 通知登记活动的管理员
		 */
		!empty($act->receiver_page) && $this->notifyAdmin($mpid, $act, $user, $ek);

		return new \ResponseData($ek);
	}
	/**
	 * 提交信息中包含的用户身份信息
	 */
	private function submitMember($mpid, $posted, $fid) {
		/**
		 * 处理用户认证信息
		 */
		$member = $posted->member;
		//unset($posted->member);
		$authid = $member->authid;
		unset($member->authid);
		$memberModel = $this->model('user/member');

		if ($existentMember = $memberModel->byFanid($fid, 'mid', $authid)) {
			$rst = $memberModel->modify($mpid, $authid, $existentMember->mid, $member);
		} else {
			$rst = $memberModel->create2($mpid, $authid, $fid, $member);
		}
		$member->authid = $authid;

		return $rst;
	}
	/**
	 * 通知活动管理员
	 */
	private function notifyAdmin($mpid, $act, $user, $ek) {
		$admins = \TMS_APP::model('acl')->enrollReceivers($mpid, $act->id);
		if (false !== ($key = array_search($user->openid, $admins))) {
			/* 管理员是登记人，不再通知 */
			unset($admins[$key]);
		}

		if (!empty($admins)) {
			$url = 'http://' . $_SERVER['HTTP_HOST'] . "/rest/app/enroll?mpid=$mpid&aid=$act->id&ek=$ek&page=$act->receiver_page";
			$txt = urlencode("【" . $act->title . "】有新登记数据，");
			$txt .= "<a href=\"$url\">";
			$txt .= urlencode("请处理");
			$txt .= "</a>";
			$message = array(
				"msgtype" => "text",
				"text" => array(
					"content" => $txt,
				),
			);
			$mpa = $this->model('mp\mpaccount')->getApis($mpid);
			if ($mpa->mpsrc === 'qy') {
				$message['touser'] = implode('|', $admins);
				$this->send_to_qyuser($mpid, $message);
			} else if ($mpa->mpsrc === 'yx' && $mpa->yx_p2p === 'Y') {
				$this->send_to_yxuser_byp2p($mpid, $message, $admins);
			} else {
				foreach ($admins as $admin) {
					$this->send_to_user($mpid, $admin, $message);
				}
			}
		}

		return true;
	}
	/**
	 * 分段上传文件
	 */
	public function uploadFile_action($mpid, $aid, $submitkey = '') {
		/* support CORS */
		header('Access-Control-Allow-Origin:*');
		header('Access-Control-Allow-Methods:POST');
		if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
			exit;
		}

		if (empty($submitkey)) {
			$user = $this->getUser($mpid);
			$submitkey = $user->vid;
		}
		/**
		 * 分块上传文件
		 */
		$modelFs = \TMS_APP::M('fs/local', $mpid, '_resumable');
		$dest = $submitkey . '_' . $_POST['resumableIdentifier'];
		$resumable = \TMS_APP::M('fs/resumable', $mpid, $dest, $modelFs);
		$resumable->handleRequest($_POST);

		return new \ResponseData('ok');
	}
}
