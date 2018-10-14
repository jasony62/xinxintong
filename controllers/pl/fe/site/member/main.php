<?php
namespace pl\fe\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 自定义用户控制器
 */
class main extends \pl\fe\base {
	/**
	 *
	 */
	public function list_action($schema, $page = 1, $size = 30, $kw = '', $by = '') {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$modelMs = $this->model('site\user\memberschema');
		$oMschema = $modelMs->byId($schema, ['fields' => 'siteid,id,title,attr_mobile,attr_email,attr_name,ext_attrs,auto_verified,require_invite']);
		if ($oMschema === false) {
			return new \ObjectNotFoundError();
		}

		$w = "m.schema_id=$schema and m.forbidden='N'";
		if (!empty($kw) && !empty($by)) {
			$w .= " and m.$by like '%{$kw}%'";
		}
		if (!empty($dept)) {
			$w .= " and m.depts like '%\"$dept\"%'";
		}
		if (!empty($tag)) {
			$w .= " and concat(',',m.tags,',') like '%,$tag,%'";
		}
		$q = [
			'm.*',
			'xxt_site_member m',
			$w,
		];
		$q2['o'] = 'm.create_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		$members = $modelMs->query_objs_ss($q, $q2);

		$oResult = new \stdClass;
		if (count($members)) {
			$modelAcnt = $this->model('site\user\account');
			$modelWxfan = $this->model('sns\wx\fan');
			foreach ($members as $oMember) {
				if (property_exists($oMember, 'extattr')) {
					$oMember->extattr = empty($oMember->extattr) ? new \stdClass : json_decode($oMember->extattr);
				}
				if (!empty($oMember->userid)) {
					$oAccount = $modelAcnt->byId($oMember->userid, ['fields' => 'wx_openid']);
					if (!empty($oAccount->wx_openid)) {
						$oWxfan = $modelWxfan->byOpenid($oMschema->siteid, $oAccount->wx_openid, 'nickname,headimgurl', 'Y');
						if ($oWxfan) {
							$oMember->wxfan = $oWxfan;
						}
					}
				}
			}
		}

		$oResult->members = $members;

		$q[0] = 'count(*)';
		$total = (int) $modelMs->query_val_ss($q);
		$oResult->total = $total;

		return new \ResponseData($oResult);
	}
	/**
	 * 更新成员数据
	 */
	public function update_action($id) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}
		$modelMem = $this->model('site\user\member')->setOnlyWriteDbConn(true);
		$oOldMember = $modelMem->byId($id, ['fields' => 'id,schema_id']);
		$attrs = $this->model('site\user\memberschema')->byId($oOldMember->schema_id, ['fields' => 'attr_mobile,attr_email,attr_name,extattr']);

		$oPosted = $this->getPostJson();
		/**
		 * 基本属性
		 */
		$emailVerified = (isset($oPosted->email_verified) && $oPosted->email_verified === 'Y') ? 'Y' : 'N';
		$aNewMember = array(
			'mobile' => empty($oPosted->mobile) ? '' : $oPosted->mobile,
			'email' => empty($oPosted->email) ? '' : $oPosted->email,
			'name' => empty($oPosted->name) ? '' : $oPosted->name,
			'email_verified' => $emailVerified,
			'verified' => in_array($oPosted->verified, ['Y', 'N', 'P']) ? $oPosted->verified : 'P',
		);
		if ($attrs->attr_mobile[5] === '1') {
			$aNewMember['identity'] = $oPosted->mobile;
		} else if ($attrs->attr_email[5] === '1') {
			$aNewMember['identity'] = $oPosted->email;
		}
		/**
		 * 扩展属性
		 */
		$aNewMember['extattr'] = empty($oPosted->extattr) ? '{}' : $modelMem->escape($modelMem->toJson($oPosted->extattr));

		/*检查数据的唯一性*/
		$aNewMember2 = $aNewMember;
		$aNewMember2['schema_id'] = $oOldMember->schema_id;
		$aNewMember2['id'] = $id;
		$aNewMember2 = (object) $aNewMember2;
		if ($errMsg = $modelMem->rejectAuth($aNewMember2, $attrs)) {
			return new \ResponseError($errMsg);
		}

		$aNewMember['modify_at'] = time();
		$rst = $modelMem->update(
			'xxt_site_member',
			$aNewMember,
			['id' => $id]
		);

		// 如果通讯录被分组活动绑定，并且设置了自动更新用户，需要更新用户
		if (isset($aNewMember['verified']) && $aNewMember['verified'] === 'Y') {
			$aNewMember2 = $modelMem->byId($id, ['fields' => 'id,forbidden,schema_id']);
			$modelMem->syncToGroupPlayer($aNewMember2->schema_id, $aNewMember2);
		}

		return new \ResponseData($rst);
	}
	/**
	 * 提交站点自定义用户信息
	 *
	 * @param int $schema 自定义用户信息定义的id
	 *
	 */
	public function create_action($schema) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oMschema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'siteid,id,title,attr_mobile,attr_email,attr_name,ext_attrs,auto_verified,require_invite']);
		if ($oMschema === false) {
			return new \ObjectNotFoundError();
		}

		$oNewMember = $this->getPostJson();
		if (emptty($oNewMember->userid)) {
			return new \ResponseError('没有指定要关联的用户');
		}

		$oSiteUser = $modelSiteUser->byId($oNewMember->userid);
		if ($oSiteUser === false) {
			return new \ResponseError('请注册或登录后再填写通讯录联系人信息');
		}

		$modelMem = $this->model('site\user\member');

		/* 给当前用户创建自定义用户信息 */
		$oNewMember->siteid = $oMschema->siteid;
		$oNewMember->schema_id = $oMschema->id;
		$oNewMember->unionid = isset($oSiteUser->unionid) ? $oSiteUser->unionid : '';
		/* check auth data */
		if ($errMsg = $modelMem->rejectAuth($oNewMember, $oMschema)) {
			return new \ResponseError($errMsg);
		}
		/* 验证状态 */
		$oNewMember->verified = $oMschema->auto_verified === 'Y' ? 'Y' : 'P';

		/* 创建通讯录用户 */
		$aResult = $modelMem->create($oSiteUser->uid, $oMschema, $oNewMember);
		if ($aResult[0] === false) {
			return new \ResponseError($aResult[1]);
		}
		$oNewMember = $aResult[1];

		return new \ResponseData($oNewMember);
	}
	/**
	 * 删除一个注册用户
	 *
	 * 不删除用户数据只是打标记
	 */
	public function remove_action($site, $id) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->update(
			'xxt_site_member',
			['forbidden' => 'Y'],
			['siteid' => $site, 'id' => $id]
		);

		return new \ResponseData($rst);
	}
}