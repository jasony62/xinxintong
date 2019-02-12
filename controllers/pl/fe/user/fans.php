<?php
namespace pl\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';

class fans extends \pl\fe\base {

	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/pl/fe/user/frame');
		die();
	}
	/**
	 * 根据uid（userid）获取公众号用户信息
	 */
	public function getSnsInfo_action($site, $uid) {
		$model = $this->model();

		$user = $model->query_obj_ss([
			'ufrom,wx_openid,qy_openid',
			'xxt_site_account',
			"siteid='$site' and uid='$uid'",
		]);

		if (empty($user)) {
			return new \ResponseError('暂无该用户公众号信息！');
		}

		if (!empty($user->wx_openid)) {
			$wx = $model->query_obj_ss([
				'f.*',
				'xxt_site_wxfan f',
				"f.siteid='$site' and f.openid='$user->wx_openid'",
			]);

			!empty($wx->groupid) && $wx->group_name = $model->query_val_ss(['name', 'xxt_site_wxfangroup', "siteid='$site' and id='$wx->groupid'"]);

			$user->wx = $wx;
		}

		if (!empty($user->qy_openid)) {
			$one = $model->query_obj_ss([
				'*',
				'xxt_site_qyfan',
				"siteid='$site' and openid='$user->qy_openid'",
			]);
			//获取所属部门
			if (!empty($one->depts)) {
				$depts = json_decode($one->depts);

				foreach ($depts as $v1) {
					$arr = array();
					foreach ($v1 as $v2) {
						$arr[] = $model->query_val_ss([
							'name',
							'xxt_site_member_department',
							"siteid='$site' and id='$v2'",
						]);
					}
					$brr[] = (object) $arr;
				}

				$one->depts_name = (object) $brr;
			}
			//获取成员标签
			if (!empty($one->tags)) {
				$arr = explode(',', $one->tags);

				foreach ($arr as $v) {
					$tag[$v] = $model->query_val_ss(['name', 'xxt_site_member_tag', "siteid='$site' and id='$v'"]);
				}

				isset($tag) && $one->tag_name = (object) $tag;
			}

			$user->qy = $one;
		}

		return new \ResponseData($user);
	}
	/**
	 * 用户的交互足迹
	 */
	public function track_action($site, $openid, $page = 1, $size = 30) {
		$track = $this->model('matter\log')->track($site, $openid, $page, $size);

		return new \ResponseData($track);
	}
	/**
	 * 更新粉丝信息
	 */
	public function update_action($site, $openid) {
		$model = $this->model();
		$src = $model->query_val_ss([
			'ufrom',
			'xxt_site_account',
			"siteid='$site' and (wx_openid='$openid' or qy_openid='$openid')",
		]);

		$nv = $this->getPostJson();
		/**
		 * 如果要更新粉丝的分组，需要先在公众平台上更新
		 */
		if (isset($nv->groupid)) {
			/**
			 * 更新公众平台上的数据
			 */
			$config = $model->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);
			$proxy = $this->model('sns/' . $src . '/proxy', $config);
			$rst = $proxy->groupsMembersUpdate($openid, $nv->groupid);

			if ($rst[0] === false) {
				return new \ResponseError($rst[1]);
			}
		}
		/**
		 * 更新本地数据
		 */
		$rst = $model->update(
			'xxt_site_' . $src . 'fan',
			(array) $nv,
			"siteid='$site' and openid='$openid'"
		);

		return new \ResponseData($rst);
	}
	/**
	 * 获得一个指定粉丝的信息
	 */
	protected function getFanInfo($site, $openid, $getGroup = false) {
		$model = $this->model();
		$src = $model->query_val_ss([
			'ufrom',
			'xxt_site_account',
			"siteid='$site' and (wx_openid='$openid' or qy_openid='$openid')",
		]);

		if (empty($src)) {
			$result = array(false, '找不到用户的注册信息');

			return $result;
		}

		$config = $model->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);

		$proxy = $this->model('sns\\' . $src . '\\proxy', $config);

		if ($src == 'qy') {
			$result = $proxy->userGet($openid);
		} else {
			$result = $proxy->userInfo($openid, $getGroup);
		}

		return $result;
	}
	/**
	 * 从公众平台同步指定粉丝的基本信息和分组信息
	 *
	 * todo 从公众号获得粉丝的代码是否应该挪走？
	 */
	public function refreshOne_action($site, $openid) {
		$qy = \TMS_APP::M('sns\qy\fan');
		$src = $qy->query_val_ss([
			'ufrom',
			'xxt_site_account',
			"wx_openid='$openid' or qy_openid='$openid'",
		]);

		if ($src === 'qy') {
			$authid = $qy->query_val_ss(['id', 'xxt_site_member_schema', "siteid='$site' and qy_ab='Y'"]);
			if (empty($authid)) {
				return new \ResponseError('没有设置自定义认证用户信息');
			}

			$result = $this->getFanInfo($site, $openid, false);
			if ($result[0] === false) {
				return new \ResponseError($result[1]);
			}

			$user = $result[1];
			$time = time();

			if ($luser = $qy->query_val_ss(['id', 'xxt_site_qyfan', "siteid='$site' and openid='$user->userid'"])) {
				$qy->updateQyFan($site, $luser, $user, $authid, $time);
			}

			$one = $qy->query_obj_ss(['*', 'xxt_site_qyfan', "siteid='$site' and openid='$openid'"]);

			//获取所属部门
			if (!empty($one->depts)) {
				$depts = json_decode($one->depts);

				foreach ($depts as $v1) {
					$arr = array();
					foreach ($v1 as $v2) {
						$arr[] = $qy->query_val_ss([
							'name',
							'xxt_site_member_department',
							"siteid='$site' and id='$v2'",
						]);
					}
					$brr[] = (object) $arr;
				}

				$one->depts_name = (object) $brr;
			}
			//获取成员标签
			if (!empty($one->tags)) {
				$arr = explode(',', $one->tags);

				foreach ($arr as $v) {
					$tag[$v] = $qy->query_val_ss(['name', 'xxt_site_member_tag', "siteid='$site' and id='$v'"]);
				}

				isset($tag) && $one->tag_name = (object) $tag;
			}

			return new \ResponseData($one);
		} else {
			$info = $this->getFanInfo($site, $openid, true);
			if ($info[0] === false) {
				return new \ResponseError($info[1]);
			}
			if ($info[1]->subscribe != 1) {
				return new \ResponseError('指定用户未关注公众号，无法获取用户信息');
			}
			/**更新数据 */
			$nickname = trim($qy->escape($info[1]->nickname));
			$u = array(
				'nickname' => empty($nickname) ? '未知' : $nickname,
				'sex' => $info[1]->sex,
				'city' => $info[1]->city,
				'groupid' => $info[1]->groupid,
			);
			isset($info[1]->headimgurl) && $u['headimgurl'] = $info[1]->headimgurl;
			isset($info[1]->icon) && $u['headimgurl'] = $info[1]->icon;
			isset($info[1]->province) && $u['province'] = $info[1]->province;
			isset($info[1]->country) && $u['country'] = $info[1]->country;
			$qy->update(
				'xxt_site_' . $src . 'fan',
				$u,
				"siteid='$site' and openid='$openid'"
			);
			return new \ResponseData($info[1]);
		}
	}
	/**
	 * 从公众平台更新粉丝分组信息
	 *
	 * 1、清除现有的分组
	 * 2、同步公众的号的分组
	 * 不更新粉丝所属的分组
	 */
	public function refreshGroup_action($site, $src = 'wx') {
		$model = $this->model();
		$group = $this->getPostJson();
		$name = $group->name;
		/**
		 * 在公众平台上添加
		 */
		if ($src === 'wx') {
			$config = $model->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);
			$proxy = $this->model('sns\wx\proxy', $config);
		} else {
			return new \ResponseError('公众号类型不支持！');
		}

		$rst = $proxy->groupsGet();
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}

		$groups = $rst[1]->groups;

		$model->delete('xxt_site_' . $src . 'fangroup', "siteid='$site'");

		foreach ($groups as $g) {
			$i = array('id' => $g->id, 'siteid' => $site, 'name' => $g->name);
			$this->model()->insert('xxt_site_wxfangroup', $i, false);
		}

		return new \ResponseData(count($groups));
	}
	/**
	 * 添加粉丝分组
	 *
	 * 同时在公众平台和本地添加
	 */
	public function addGroup_action($site, $src = 'wx') {
		$model = $this->model();
		$group = $this->getPostJson();
		$name = $group->name;
		/**
		 * 在公众平台上添加
		 */
		if ($src === 'wx') {
			$config = $model->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);
			$proxy = $this->model('sns\wx\proxy', $config);
		} else {
			return new \ResponseError('公众号类型不支持！');
		}

		$rst = $proxy->groupsCreate($group);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		$group = $rst[1]->group;
		/**
		 * 在本地添加
		 */
		$group->siteid = $site;
		$group->name = $name;
		$model->insert('xxt_site_wxfangroup', (array) $group, false);

		return new \ResponseData($group);
	}
	/**
	 * 更新粉丝分组的名称
	 *
	 * 同时修改公众平台的数据和本地数据
	 */
	public function updateGroup_action($site, $src = 'wx') {
		$model = $this->model();
		$group = $this->getPostJson();
		$name = $group->name;
		/**
		 * 在公众平台上添加
		 */
		if ($src === 'wx') {
			$config = $model->query_obj_ss(['*', 'xxt_site_' . $src, "siteid='$site'"]);
			$proxy = $this->model('sns\wx\proxy', $config);
		} else {
			return new \ResponseError('公众号类型不支持！');
		}

		$rst = $mpproxy->groupsUpdate($group);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		/**
		 * 更新本地数据
		 */
		$rst = $model->update(
			'xxt_site_wxfangroup',
			array('name' => $group->name),
			"siteid='$site' and id='$group->id'"
		);

		return new \ResponseData($rst);
	}
}