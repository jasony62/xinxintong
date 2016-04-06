<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 站点用户管理控制器
 */
class profile extends \pl\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/site/user');
		exit;
	}
	/**
	 *
	 */
	public function get_action($site, $userid) {
		$model = $this->model();

		$result = array();
		$q = array(
			'uid,nickname,headimgurl,reg_time',
			'xxt_site_account',
			"uid='{$userid}'",
		);
		if ($user = $model->query_obj_ss($q)) {
			$result['user'] = $user;
			/* members */
			$members = $this->model('site\user\member')->byUser($site, $userid);
			$result['members'] = $members;
		}

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function memberAdd_action($site, $userid, $schema) {
		$posted = $this->getPostJson();
		$schema = $this->model('site\user\memberschema')->byId($schema);

		$rst = $this->model('site\user\member')->create($site, $userid, $schema, $posted);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}
		$member = $rst[1];

		return new \ResponseData($member);
	}
	/**
	 * 更新成员数据
	 */
	public function memberUpd_action($site, $id) {
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
			$newMember['openid'] = $data->mobile;
		} else if ($attrs->attr_email[5] === '1') {
			$newMember['openid'] = $data->email;
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
		/**
		 * 同步到企业号
		 */
		/*$mpapis = $this->model('mp\mpaccount')->getApis($this->mpid);
			if ($mpapis->qy_joined === 'Y') {
				$fan = $this->model('user/fans')->byMid($mid);
				$posted = array(
					'mobile' => empty($data->mobile) ? '' : $data->mobile,
					'email' => empty($data->email) ? '' : $data->email,
					'name' => empty($data->name) ? '' : $data->name,
				);
				if (!empty($data->extattr->position)) {
					$posted['position'] = $data->extattr->position;
				}

				if (!empty($attrs->extattr)) {
					$extdata = array();
					foreach ($attrs->extattr as $ea) {
						if ($ea->id === 'position') {
							continue;
						}

						$extdata[] = array(
							'name' => urlencode($ea->id),
							'value' => urlencode($data->extattr->{$ea->id}),
						);
					}
					$posted['extattr'] = array('attrs' => $extdata);
				}

				$rst = $this->model('mpproxy/qy', $this->mpid)->userUpdate($fan->openid, $posted);
				if ($rst[0] === false) {
					return new \ResponseError($rst[1]);
				}

		*/

		return new \ResponseData($rst);
	}
	/**
	 * 删除一个注册用户
	 *
	 * 不删除用户数据只是打标记
	 */
	public function memberDel_action($site, $id) {
		$rst = $this->model()->update(
			'xxt_site_member',
			array('forbidden' => 'Y'),
			"site='$site' and id='$id'"
		);

		return new \ResponseData($rst);
	}
}