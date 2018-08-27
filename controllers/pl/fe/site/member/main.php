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
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model();

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
		$result = array();
		$q = [
			'm.*',
			'xxt_site_member m',
			$w,
		];
		$q2['o'] = 'm.create_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($members = $model->query_objs_ss($q, $q2)) {
			foreach ($members as $oMember) {
				if (property_exists($oMember, 'extattr')) {
					$oMember->extattr = empty($oMember->extattr) ? new \stdClass : json_decode($oMember->extattr);
				}
			}
			$result['members'] = $members;
			$q[0] = 'count(*)';
			$total = (int) $model->query_val_ss($q);
			$result['total'] = $total;
		} else {
			$result['members'] = array();
			$result['total'] = 0;
		}

		return new \ResponseData($result);
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
	 * 删除一个注册用户
	 *
	 * 不删除用户数据只是打标记
	 */
	public function remove_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model()->update(
			'xxt_site_member',
			array('forbidden' => 'Y'),
			"siteid='$site' and id='$id'"
		);

		return new \ResponseData($rst);
	}
}