<?php
namespace pl\fe\site\user;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';

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
		\TPL::output('/pl/fe/site/user/frame');
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
	 * 从公众平台同步所有粉丝的基本信息和分组信息
	 *
	 * 需要开通高级接口
	 *
	 * $step
	 * $nextOpenid
	 *
	 */
	public function refreshAll_action($site, $src = 'wx', $step = 0, $nextOpenid = '') {
		$model = $this->model();

		if ($src === 'wx') {
			$config = $model->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
			$proxy = $this->model('sns\wx\proxy', $config);
		} else {
			return new \ResponseError('公众号类型不支持！');
		}

		if ($step === 0) {
			$fansCount = 0;
			/**
			 * 获得所有粉丝的openid
			 */
			$rst = $proxy->userGet($nextOpenid);
			if (false === $rst[0]) {
				return new \ResponseError($rst[1]);
			}
			if (!is_object($rst[1])) {
				return new \ResponseError($rst[1]);
			}
			$userSet = $rst[1];
			$total = $userSet->total; // 所有粉丝的数量
			$openids = $userSet->data->openid; // 本次获得的粉丝id数组
			$nextOpenid = $userSet->count == 10000 ? $userSet->next_openid : '';
		} else {
			$stack = $_SESSION['fans_refreshAll_stack'];
			$mpa = $stack['mpa'];
			$total = $stack['total'];
			$fansCount = $stack['fansCount'];
			$openids = $stack['openids'];
		}
		/**
		 * 更新粉丝
		 */
		if (!empty($openids)) {
			$current = time();
			$ins = array(
				'siteid' => $site,
				'subscribe_at' => $current,
				'sync_at' => $current,
			);
			$finish = 0;
			foreach ($openids as $index => $openid) {
				if ($index == 50 * ($step + 1)) {
					$step++;
					$stack = array(
						'mpa' => $mpa,
						'total' => $total,
						'fansCount' => $fansCount,
						'openids' => $openids,
					);
					$_SESSION['fans_refreshAll_stack'] = $stack;
					return new \ResponseData(array('total' => $total, 'step' => $step, 'left' => count($openids), 'finish' => $finish, 'refreshCount' => $fansCount, 'nextOpenid' => $nextOpenid));
				}

				$finish++;
				unset($openids[$index]);

				$lfan = $model->query_obj_ss(['*', 'xxt_site_wxfan', "siteid='$site' and openid='$openid'"]);
				if ($lfan && $lfan->sync_at + 43200 > $current) {
					/* 一小时之内不同步 */
					continue;
				}
				/**
				 * 从公众号获得粉丝信息
				 */
				$info = $proxy->userInfo($openid, true);
				if ($info[0] == false) {
					$fansCount++;
					continue;
					//return new \ResponseError($info[1]);
				}
				$rfan = $info[1];
				if ($rfan->subscribe != 0) {
					if ($lfan) {
						/**
						 * 更新关注状态粉丝信息
						 */
						$upd = array(
							'nickname' => $model->escape($rfan->nickname),
							'sex' => $rfan->sex,
							'city' => $rfan->city,
							'groupid' => $rfan->groupid,
							'sync_at' => $current,
						);
						isset($rfan->icon) && $upd['headimgurl'] = $rfan->icon;
						isset($rfan->headimgurl) && $upd['headimgurl'] = $rfan->headimgurl;
						isset($rfan->province) && $upd['province'] = $rfan->province;
						isset($rfan->country) && $upd['country'] = $rfan->country;
						$model->update(
							'xxt_site_wxfan',
							$upd,
							"siteid='$site' and openid='$openid'"
						);
						$fansCount++;
					} else {
						/**
						 * 新粉丝
						 */
						$ins['openid'] = $openid;
						if ($info[0]) {
							$ins['groupid'] = $rfan->groupid;
							$ins['nickname'] = $model->escape($rfan->nickname);
							$ins['sex'] = $rfan->sex;
							$ins['city'] = $rfan->city;
							isset($rfan->subscribe_time) && $ins['subscribe_at'] = $rfan->subscribe_time;
							isset($rfan->icon) && $ins['headimgurl'] = $rfan->icon;
							isset($rfan->headimgurl) && $ins['headimgurl'] = $rfan->headimgurl;
							isset($rfan->province) && $ins['province'] = $rfan->province;
							isset($rfan->country) && $ins['country'] = $rfan->country;
							$model->insert('xxt_site_wxfan', $ins, false);
							$fansCount++;
						}
					}
				}
			}
		}

		return new \ResponseData(array('total' => $total, 'step' => $step, 'left' => count($openids), 'finish' => $finish, 'refreshCount' => $fansCount, 'nextOpenid' => $nextOpenid));
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
				'xxt_site_wxfan',
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
			$config = $model->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
			$proxy = $this->model('sns\wx\proxy', $config);
		} else {
			return new \ResponseError('公众号类型不支持！');
		}

		$rst = $proxy->groupsGet();
		if (false === $rst[0]) {
			return new \ResponseError($rst[1]);
		}

		$groups = $rst[1]->groups;

		$model->delete('xxt_site_wxfangroup', "siteid='$site'");

		foreach ($groups as $g) {
			$i = array('id' => $g->id, 'siteid' => $site, 'name' => $g->name);
			$model->insert('xxt_site_wxfangroup', $i, false);
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
			$config = $model->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
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
			$config = $model->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
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
	/**
	 * 删除粉丝分组
	 *
	 * 同时删除公众平台上的数据和本地数据
	 */
	public function removeGroup_action($site, $src = 'wx') {
		$model = $this->model();
		$group = $this->getPostJson();
		$name = $group->name;
		/**
		 * 在公众平台上添加
		 */
		if ($src === 'wx') {
			$config = $model->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
			$proxy = $this->model('sns\wx\proxy', $config);
		} else {
			return new \ResponseError('公众号类型不支持！');
		}

		$rst = $mpproxy->groupsDelete($group);
		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		}

		/**
		 * 删除本地数据
		 * todo 级联更新粉丝所属分组数据
		 */
		$rst = $model->delete('xxt_site_wxfangroup', "siteid='$site' and id='$group->id'");

		return new \ResponseData($rst);
	}
	/**
	 *
	 */
	public function wxfansgroup_action($site) {
		$model = $this->model();
		$config = $model->query_obj_ss(['*', 'xxt_site_wx', "siteid='$site'"]);
		$proxy = $this->model('sns\\wx\\proxy', $config);
		$rst = $proxy->groupsGet();

		if ($rst[0] === false) {
			return new \ResponseError($rst[1]);
		} else {
			return new \ResponseData($rst[1]);
		}

	}
}