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
		$q = array(
			'm.*',
			'xxt_site_member m',
			$w,
		);
		$q2['o'] = 'm.create_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($members = $model->query_objs_ss($q, $q2)) {
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
	public function update_action($site, $id) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$member = $this->model('site\user\member')->byId($id, 'schema_id');
		$attrs = $this->model('site\user\memberschema')->byId($member->schema_id, 'attr_mobile,attr_email,attr_name,extattr');

		$data = $this->getPostJson();
		/**
		 * 基本属性
		 */
		$emailVerified = (isset($data->email_verified) && $data->email_verified === 'Y') ? 'Y' : 'N';
		$newMember = array(
			'mobile' => empty($data->mobile) ? '' : $data->mobile,
			'email' => empty($data->email) ? '' : $data->email,
			'name' => empty($data->name) ? '' : $data->name,
			'email_verified' => $emailVerified,
			'verified' => (isset($data->verified) && $data->verified === 'Y') ? 'Y' : 'N',
		);
		if ($attrs->attr_mobile[5] === '1') {
			$newMember['identity'] = $data->mobile;
		} else if ($attrs->attr_email[5] === '1') {
			$newMember['identity'] = $data->email;
		}
		/**
		 * 扩展属性
		 */
		if (!empty($attrs->extattr)) {
			$extdata = array();
			foreach ($attrs->extattr as $ea) {
				if (!empty($data->extattr->{$ea->id})) {
					$extdata[urlencode($ea->id)] = urlencode($data->extattr->{$ea->id});
				} else {
					$extdata[urlencode($ea->id)] = '';
				}

			}
			$newMember['extattr'] = urldecode(json_encode($extdata));
		}

		$rst = $this->model()->update(
			'xxt_site_member',
			$newMember,
			"siteid='$site' and id='$id'"
		);

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