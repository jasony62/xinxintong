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
	public function add_action($label, $tid = null, $matter = null) {
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAcnt = $this->model('account');
		$modelTmpl = $this->model('matter\template');
		$account = $modelAcnt->getAccountByAuthedId($label);
		if (!$account) {
			return new \ResponseError('指定的账号不是注册账号，请先注册！');
		}
		/**
		 * has joined?
		 */
		if(empty($tid) && !empty($matter)){
			list($matterId,$matterType) = explode(',', $matter);

			if (false === ($template = $modelTmpl->byMatter($matterId, $matterType)) ) {
				return new \ResponseError('指定的模板不存在');
			}
		}else if(!empty($tid)) {
			if (false === ($template = $modelTmpl->byId($tid, null, ['cascaded' => 'N'])) ) {
				return new \ResponseError('指定的模板不存在');
			}
		}else{
			return new \ResponseError('请检查参数');
		}

		$modelAcl = $this->model('matter\template\acl');
		$acl = $modelAcl->byReceiver($account->uid, $template->id);
		if ($acl) {
			return new \ResponseError('【' . $label . '】已经在分享列表中！');
		}

		// ACL
		$acl = new \stdClass;
		$acl->receiver = $account->uid;
		$acl->receiver_label = $account->email;
		$acl = $modelAcl->add($loginUser, $template, $acl);

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
	/**
	 * [listAcler_action 获取分享者列表]
	 * @param  [type] $tid [description]
	 * @return [type]      [description]
	 */
	public function listAcler_action($tid){
		if (false === ($loginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelAcl = $this->model('matter\template\acl');
		$acls = $modelAcl->listAcler($tid);

		return $acls;
	}
}