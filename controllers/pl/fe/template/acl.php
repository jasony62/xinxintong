<?php
namespace pl\fe\template;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 素材模板访问控制控制器
 */
class acl extends \pl\fe\base {
	/**
	 * 素材模板接收人
	 *
	 * @param string $id 素材的ID
	 * @param string $type 素材的类型
	 */
	public function byMatter_action($id) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$acls = $this->model('matter\template\acl')->byMatter($id);

		if (!empty($acls)) {
			$modelAcnt = $this->model('account');
			foreach ($acls as &$acl) {
				$account = $modelAcnt->byId($acl->receiver, ['fields' => 'nickname']);
				$acl->account = $account;
			}
		}

		return new \ResponseData($acls);
	}
	/**
	 * 增加模板接收人
	 *
	 * @param string $site
	 * @param int $mission mission's id
	 */
	public function add_action($label, $tid) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAcnt = $this->model('account');
		$account = $modelAcnt->getAccountByAuthedId($label);
		if (!$account) {
			return new \ResponseError('指定的账号不是注册账号，请先注册！');
		}
		/**
		 * has joined?
		 */
		$modelAcl = $this->model('matter\template\acl');
		$acl = $modelAcl->byReceiver($loginUser->id, $tid);
		if ($acl) {
			return new \ResponseError('【' . $label . '】已经在分享列表中！');
		}

		// ACL
		$acl = new \stdClass;
		$acl->receiver = $account->uid;
		$acl->receiver_label = $account->email;

		$modelTmpl = $this->model('matter\template');
		if ($template = $modelTmpl->byid($tid, null, ['cascaded' => 'N'])) {
			$acl = $modelAcl->add($loginUser, $template, $acl);
		}

		$acl->account = (object) ['nickname' => $account->nickname];

		return new \ResponseData($acl);
	}
	/**
	 * 删除模板接收人
	 *
	 * @param int $acl acl's id
	 */
	public function remove_action($acl) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->delete(
			'xxt_template_acl',
			["id" => $acl]
		);

		return new \ResponseData($rst);
	}
}