<?php
namespace matter\group;
/**
 * 分组活动用户
 */
class user_model extends \TMS_MODEL {
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $aid) {
		return md5(uniqid() . $siteId . $aid);
	}
	/**
	 * 用户登记（不包括登记数据）
	 *
	 * @param object $app
	 * @param object $user
	 * @param array $options
	 *
	 */
	public function enroll($oApp, $oUser, $aOptions = []) {
		if (is_object($aOptions)) {
			$aOptions = (array) $aOptions;
		}

		if (isset($aOptions['enroll_key'])) {
			$ek = $aOptions['enroll_key'];
		} else {
			$ek = $this->genKey($oApp->siteid, $oApp->id);
		}
		$current = time();
		$aNewPlayer = [
			'aid' => $oApp->id,
			'siteid' => $oApp->siteid,
			'enroll_key' => $ek,
			'userid' => $oUser->uid,
			'nickname' => $this->escape($oUser->nickname),
		];
		$aNewPlayer['enroll_at'] = isset($aOptions['enroll_at']) ? $aOptions['enroll_at'] : $current;
		$aNewPlayer['draw_at'] = isset($aOptions['draw_at']) ? $aOptions['draw_at'] : $current;
		isset($aOptions['team_id']) && $aNewPlayer['team_id'] = $aOptions['team_id'];
		isset($aOptions['team_title']) && $aNewPlayer['team_title'] = $this->escape($aOptions['team_title']);
		isset($aOptions['comment']) && $aNewPlayer['comment'] = $this->escape($aOptions['comment']);
		isset($aOptions['tags']) && $aNewPlayer['tags'] = $this->escape($aOptions['tags']);
		isset($aOptions['referrer']) && $aNewPlayer['referrer'] = $aOptions['referrer'];

		$this->insert('xxt_group_user', $aNewPlayer, false);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData($oApp, $ek, $data) {
		if (empty($data)) {
			return [true];
		}
		// 处理后的登记记录
		$dbData = new \stdClass;

		$schemasById = [];
		foreach ($oApp->dataSchemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		/* 已有的登记数据 */
		$fields = $this->query_vals_ss(['name', 'xxt_group_user_data', "aid='{$oApp->id}' and enroll_key='$ek'"]);

		foreach ($data as $n => $v) {
			if ($n === 'member' && is_object($v)) {
				$dbData->{$n} = $v;
				/* 自定义用户信息 */
				$treatedValue = new \stdClass;
				isset($v->name) && $treatedValue->name = urlencode($v->name);
				isset($v->email) && $treatedValue->email = urlencode($v->email);
				isset($v->mobile) && $treatedValue->mobile = urlencode($v->mobile);
				if (!empty($v->extattr)) {
					$extattr = new \stdClass;
					foreach ($v->extattr as $mek => $mev) {
						$extattr->{$mek} = urlencode($mev);
					}
					$treatedValue->extattr = $extattr;
				}
				$treatedValue = urldecode(json_encode($treatedValue));
			} else {
				if (!isset($schemasById[$n])) {
					continue;
				}
				$schema = $schemasById[$n];
				/**
				 * 插入自定义属性
				 */
				if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
					/* 上传图片 */
					$treatedValue = array();
					$fsuser = $this->model('fs/user', $oApp->siteid);
					foreach ($v as $img) {
						$rst = $fsuser->storeImg($img);
						if (false === $rst[0]) {
							return $rst;
						}
						$treatedValue[] = $rst[1];
					}
					$treatedValue = implode(',', $treatedValue);
					$dbData->{$n} = $treatedValue;
				} else if ($schema->type === 'score') {
					$dbData->{$n} = $v;
					$treatedValue = json_encode($v);
				} else {
					if (is_string($v)) {
						$treatedValue = $this->escape($v);
					} else if (is_object($v) || is_array($v)) {
						$treatedValue = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
					} else {
						$treatedValue = $v;
					}
					$dbData->{$n} = $treatedValue;
				}
				if (!empty($fields) && in_array($n, $fields)) {
					$this->update(
						'xxt_group_user_data',
						['value' => $treatedValue],
						['aid' => $oApp->id, 'enroll_key' => $ek, 'name' => $n]
					);
					unset($fields[array_search($n, $fields)]);
				} else {
					$ic = [
						'aid' => $oApp->id,
						'enroll_key' => $ek,
						'name' => $n,
						'value' => $treatedValue,
					];
					$this->insert('xxt_group_user_data', $ic, false);
				}
			}
		}
		// 记录数据
		$dbData = $this->escape($this->toJson($dbData));
		$this->update(
			'xxt_group_user',
			['enroll_at' => time(), 'data' => $dbData],
			['enroll_key' => $ek]
		);

		return [true, $dbData];
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function byId($aid, $ek, $aOptions = array()) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';

		$q = [
			$fields,
			'xxt_group_user',
			['aid' => $aid, 'enroll_key' => $ek, 'state' => 1],
		];
		if (($oRecord = $this->query_obj_ss($q)) && $cascaded === 'Y') {
			if (!empty($oRecord->data)) {
				$oRecord->data = json_decode($oRecord->data);
			}
			$oRecord->role_teams = empty($oRecord->role_teams) ? [] : json_decode($oRecord->role_teams);
		}

		return $oRecord;
	}
	/**
	 * 根据ID返回登记记录
	 *
	 * @param string $ek 因为分组活动的用户有可能是从其他活动导入的，使用的是导入记录的ek，因为有可能一个ek导入到多个分组活动中
	 * @param string $aid 分组活动的id。如果指定只返回单条记录，如果不指定返回数据
	 *
	 */
	public function byEnrollKey($ek, $aid = null, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';

		if ($fields === '*' || false !== strpos($fields, 'data') || false !== strpos($fields, 'role_teams')) {
			$fnHandleRecord = function (&$oRecord) {
				if (!empty($oRecord->data)) {
					$oRecord->data = json_decode($oRecord->data);
				}
				$oRecord->role_teams = empty($oRecord->role_teams) ? [] : json_decode($oRecord->role_teams);
			};
		}
		$q = [
			$fields,
			'xxt_group_user',
			["enroll_key" => $ek, "state" => 1],
		];
		if (empty($aid)) {
			$records = $this->query_objs_ss($q);
			if (count($records) && $cascaded === 'Y' && isset($fnHandleRecord)) {
				array_walk($records, $fnHandleRecord);
			}
			return $records;
		} else {
			$q[2]['aid'] = $aid;
			if (($oRecord = $this->query_obj_ss($q)) && $cascaded === 'Y' && isset($fnHandleRecord)) {
				$fnHandleRecord($oRecord);
			}
			return $oRecord;
		}
	}
	/**
	 * 获得指定项目下的登记记录
	 *
	 * @param int $missionId
	 */
	public function byMission($missionId, $aOptions) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_user r',
		];
		$missionId = $this->escape($missionId);
		$where = "state=1 and exists(select 1 from xxt_group g where r.aid=g.id and g.mission_id={$missionId})";

		if (isset($aOptions['userid'])) {
			$where .= " and userid='" . $this->escape($aOptions['userid']) . "'";
		}
		$q[2] = $where;

		$list = $this->query_objs_ss($q);
		if (count($list)) {
			if ($fields === '*' || strpos($fields, 'data') !== false || strpos($fields, 'role_teams') !== false) {
				foreach ($list as &$record) {
					if (!empty($record->data)) {
						$record->data = json_decode($record->data);
					}
					if (!empty($record->role_teams)) {
						$record->role_teams = json_decode($record->role_teams);
					} else {
						$record->role_teams = [];
					}
				}
			}
		}

		return $list;
	}
	/**
	 * 用户清单
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 */
	public function byApp($oApp, $oOptions = null) {
		if (is_string($oApp)) {
			$oApp = (object) ['id' => $oApp];
		}
		if ($oOptions) {
			is_array($oOptions) && $oOptions = (object) $oOptions;
			$page = isset($oOptions->page) ? $oOptions->page : null;
			$size = isset($oOptions->size) ? $oOptions->size : null;
		}

		$fields = isset($oOptions->fields) ? $oOptions->fields : 'enroll_key,enroll_at,comment,tags,data,userid,nickname,is_leader,team_id,team_title,role_teams';

		/* 数据过滤条件 */
		$w = "state=1 and aid='{$oApp->id}'";
		/*tags*/
		if (!empty($oOptions->tags)) {
			$aTags = explode(',', $oOptions->tags);
			foreach ($aTags as $tag) {
				$w .= " and concat(',',tags,',') like '%,$tag,%'";
			}
		}
		if (isset($oOptions->teamId)) {
			if ($oOptions->teamId === 'inTeam') {
				$w .= " and team_id <> ''";
			} else if ($oOptions->teamId === '' || $oOptions->teamId === 'pending') {
				$w .= " and team_id = ''";
			} else if (strcasecmp($oOptions->teamId, 'all') !== 0) {
				$w .= " and team_id = '" . $oOptions->teamId . "' and userid <> ''";
			}
		}
		if (!empty($oOptions->roleTeamId)) {
			$w .= " and role_teams like '%\"" . $oOptions->roleTeamId . "\"%' and userid <> ''";
		}
		// 根据用户昵称过滤
		if (!empty($oOptions->nickname)) {
			$w .= " and nickname like '%{$oOptions->nickname}%'";
		}
		$q = [
			$fields,
			'xxt_group_user',
			$w,
		];
		/* 分页参数 */
		if (isset($page) && isset($size)) {
			$q2 = [
				'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			];
		}
		/* 排序 */
		$q2['o'] = 'team_id asc,enroll_at desc';

		$oResult = new \stdClass; // 返回的结果
		$users = $this->query_objs_ss($q, $q2);
		if (count($users)) {
			/* record data */
			if ($fields === '*' || false !== strpos($fields, 'data') || false !== strpos($fields, 'role_teams')) {
				foreach ($users as $oUser) {
					if (!empty($oUser->data)) {
						$oUser->data = json_decode($oUser->data);
					}
					if (!empty($oUser->role_teams)) {
						$oUser->role_teams = json_decode($oUser->role_teams);
					} else {
						$oUser->role_teams = [];
					}
				}
			}

		}
		$oResult->users = $users;

		/* total */
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q);
		$oResult->total = $total;

		return $oResult;
	}
	/**
	 * 根据用户id获得在指定分组活动中的分组信息
	 */
	public function byUser($oApp, $userid, $aOptions = []) {
		if (empty($userid)) {
			return false;
		}

		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_user',
			['state' => 1, 'aid' => $oApp->id, 'userid' => $userid],
		];
		$q2 = ['o' => 'enroll_at desc'];
		if (isset($aOptions['onlyOne']) && $aOptions['onlyOne'] === true) {
			$q2['r'] = ['o' => 0, 'l' => 1];
		}

		$list = $this->query_objs_ss($q, $q2);
		if (count($list)) {
			$aRecHandler = [];
			if ($fields === '*' || false !== strpos($fields, 'data')) {
				$aRecHandler[] = function (&$oUser) {
					$oUser->data = empty($oUser->data) ? new \stdClass : json_decode($oUser->data);
				};
			}
			if ($fields === '*' || false !== strpos($fields, 'role_teams')) {
				$aRecHandler[] = function (&$oUser) {
					$oUser->role_teams = empty($oUser->role_teams) ? [] : json_decode($oUser->role_teams);
				};
			}
			foreach ($list as $oUser) {
				foreach ($aRecHandler as $fnHandler) {
					$fnHandler($oUser);
				}
			}
		}
		if (isset($aOptions['onlyOne']) && $aOptions['onlyOne'] === true) {
			return count($list) ? $list[0] : false;
		}

		return $list;
	}
	/**
	 * 获得指定分组内的用户
	 */
	public function byRound($rid, $aOptions = []) {
		$oTeam = $this->model('matter\group\team')->byId($rid, ['fields' => 'aid,team_id,team_type']);
		if (false === $oTeam) {
			return false;
		}

		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_user',
			['aid' => $oTeam->aid, 'state' => 1],
		];
		switch ($oTeam->team_type) {
		case 'T':
			$q[2]['team_id'] = $oTeam->team_id;
			break;
		case 'R':
			$q[2]['role_teams'] = (object) ['op' => 'like', 'pat' => '%' . $oTeam->team_id . '%'];
			break;
		default:
			return false;
		}

		$q2 = ['o' => 'team_id,draw_at'];

		if ($users = $this->query_objs_ss($q, $q2)) {
			if ($fields === '*' || false !== strpos($fields, 'data') || false !== strpos($fields, 'role_teams')) {
				foreach ($users as $oUser) {
					if (!empty($oUser->data)) {
						$oUser->data = json_decode($oUser->data);
					}
					if (!empty($oUser->role_teams)) {
						$oUser->role_teams = json_decode($oUser->role_teams);
					} else {
						$oUser->role_teams = [];
					}
				}
			}
		}

		return $users;
	}
	/**
	 * 指定角色分组内的用户(角色分组)
	 */
	public function byRoleRound($appId, $tid = null, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_user',
			"aid='$appId' and state=1",
		];
		if (!empty($tid)) {
			$q[2] .= " and role_teams like '%\"" . $tid . "\"%'";
		} else {
			$q[2] .= " and (role_teams <> '' or role_teams <> '[]'";
		}
		$q2 = ['o' => 'team_id,draw_at'];

		if ($grpUsers = $this->query_objs_ss($q, $q2)) {
			if ($fields === '*' || false !== strpos($fields, 'data') || false !== strpos($fields, 'role_teams')) {
				foreach ($grpUsers as $oPlayer) {
					if (!empty($oPlayer->data)) {
						$oPlayer->data = json_decode($oPlayer->data);
					}
					if (!empty($oPlayer->role_teams)) {
						$oPlayer->role_teams = json_decode($oPlayer->role_teams);
					} else {
						$oPlayer->role_teams = [];
					}
				}
			}
		}

		return $grpUsers;
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 */
	public function byData($oApp, $data, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$records = false;

		// 查找条件
		$whereByData = '';
		foreach ($data as $k => $v) {
			if ($k === '_round_id') {
				$whereByData .= ' and (';
				$whereByData .= 'team_id="' . $v . '"';
				$whereByData .= ')';
			} else {
				if (!empty($v)) {
					/* 通讯录字段简化处理 */
					if (strpos($k, 'member.') === 0) {
						$k = str_replace('member.', '', $k);
					}
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
		}

		// 没有指定条件时就认为没有符合条件的记录
		if (empty($whereByData)) {
			return $records;
		}

		// 查找匹配条件的数据
		$q = [
			$fields,
			'xxt_group_user',
			"state=1 and aid='{$oApp->id}' $whereByData",
		];
		$records = $this->query_objs_ss($q);
		foreach ($records as &$record) {
			if (empty($record->data)) {
				$record->data = new \stdClass;
			} else {
				$data = json_decode($record->data);
				if ($data === null) {
					$record->data = 'json error(' . json_last_error() . '):' . $r->data;
				} else {
					$record->data = $data;
				}
			}
			if (empty($record->role_teams)) {
				$record->role_teams = [];
			} else {
				$record->role_teams = json_decode($record->role_teams);
			}
		}

		return $records;
	}
	/**
	 * 有资格参加指定轮次分组的用户(团队分组)
	 */
	public function &pendings($appId) {
		/* 没有抽中过的用户 */
		$q = [
			'id,enroll_key,nickname,userid,enroll_at,data,tags,comment,role_teams',
			'xxt_group_user',
			"aid='$appId' and state=1 and team_id=0",
		];
		$q2['o'] = 'enroll_at desc';
		/* 获得用户的登记数据 */
		if (($grpUsers = $this->query_objs_ss($q, $q2)) && !empty($grpUsers)) {
			foreach ($grpUsers as &$player) {
				if (!empty($player->data)) {
					$player->data = json_decode($player->data);
				}
				if (!empty($player->role_teams)) {
					$player->role_teams = json_decode($player->role_teams);
				} else {
					$player->role_teams = [];
				}
			}
		}

		return $grpUsers;
	}
	/**
	 * 没有参加角色分组的用户(角色分组)
	 */
	public function &pendingsRole($appId) {
		/* 没有抽中过的用户 */
		$q = [
			'id,enroll_key,nickname,userid,enroll_at,data,tags,comment,role_teams',
			'xxt_group_user',
			"aid='$appId' and state=1 and (role_teams = '' or role_teams = '[]')",
		];
		$q2['o'] = 'enroll_at desc';
		/* 获得用户的登记数据 */
		if (($grpUsers = $this->query_objs_ss($q, $q2)) && !empty($grpUsers)) {
			foreach ($grpUsers as &$player) {
				if (!empty($player->data)) {
					$player->data = json_decode($player->data);
				}
				if (!empty($player->role_teams)) {
					$player->role_teams = json_decode($player->role_teams);
				} else {
					$player->role_teams = [];
				}
			}
		}

		return $grpUsers;
	}
	/**
	 * 获得分组内用户的数量（团队分组）
	 */
	public function countByRound($appId, $tid) {
		$q = [
			'count(*)',
			'xxt_group_user',
			['aid' => $appId, 'team_id' => $tid, 'state' => 1],
		];
		$cnt = (int) $this->query_val_ss($q);

		return $cnt;
	}
	/**
	 * 获得角色分组内用户的数量（角色分组）
	 */
	public function countByRoleRound($appId, $tid) {
		$q = [
			'count(*)',
			'xxt_group_user',
			"aid = '{$appId}' and state = 1 and role_teams like '%\"" . $tid . "\"%'",
		];
		$cnt = (int) $this->query_val_ss($q);

		return $cnt;
	}
	/**
	 * 指定用户是否属于指定用户组
	 */
	public function isInRound($roundId, $userid) {
		/* 主分组 */
		$q = [
			'1',
			'xxt_group_user',
			['userid' => $userid, 'state' => 1, 'team_id' => $roundId],
		];
		$oUser = $this->query_obj_ss($q);
		if ($oUser) {
			return true;
		}
		/* 辅助分组 */
		unset($q[2]['team_id']);
		$q[2]['role_teams'] = (object) ['op' => 'like', 'pat' => '%' . $roundId . '%'];
		$oUser = $this->query_obj_ss($q);
		if ($oUser) {
			return true;
		}

		return false;
	}
	/**
	 * 移入分组 （团队分组）
	 */
	public function joinGroup($appId, $oTeam, $ek) {
		$rst = 0;
		switch ($oTeam->team_type) {
		case 'T':
			$rst = $this->update(
				'xxt_group_user',
				[
					'team_id' => $oTeam->team_id,
					'team_title' => $oTeam->title,
				],
				["aid" => $appId, "enroll_key" => $ek]
			);
			break;
		case 'R':
			$oUser = $this->byEnrollKey($ek, $appId, ['fields' => 'role_teams']);
			if ($oUser && !in_array($oTeam->team_id, $oUser->role_teams)) {
				$oUser->role_teams[] = $oTeam->team_id;
				$rst = $this->update(
					'xxt_group_user',
					[
						'role_teams' => json_encode($oUser->role_teams),
					],
					["aid" => $appId, "enroll_key" => $ek]
				);
			}
			break;
		}

		return $rst;
	}
	/**
	 * 移出分组 （团队分组）
	 */
	public function quitGroup($appId, $ek) {
		$rst = $this->update(
			'xxt_group_user',
			[
				'team_id' => 0,
				'team_title' => '',
			],
			["aid" => $appId, "enroll_key" => $ek]
		);

		return $rst;
	}
	/**
	 * 同步数据
	 */
	public function syncRecord($siteId, &$objGrp, &$records, &$modelRec, $type = '', $assignRound = '') {
		$this->setOnlyWriteDbConn(true);
		$cnt = 0;
		if (!empty($records)) {
			$aOptions = ['cascaded' => 'Y'];
			foreach ($records as $oRecord) {
				if ($oRecord->state === '1' || $oRecord->state === 'N') {
					if ($type === 'mschema') {
						$oRecord = $this->_getMschData($objGrp, $oRecord->enroll_key);
					} else {
						$oRecord = $modelRec->byId($oRecord->enroll_key, $aOptions);
					}
					if (empty($oRecord)) {
						continue;
					}
					$oGrpUser = new \stdClass;
					$oGrpUser->uid = $oRecord->userid;
					$oGrpUser->nickname = $oRecord->nickname;
					if ($oBeforeGrpUser = $this->byId($objGrp->id, $oRecord->enroll_key, ['cascaded' => 'N'])) {
						$updata = [];
						if (!empty($assignRound) && is_object($assignRound)) {
							$updata['team_id'] = $assignRound->team_id;
							$updata['team_title'] = $assignRound->title;
						}
						if ($oBeforeGrpUser->nickname !== $oGrpUser->nickname) {
							$updata['nickname'] = $oGrpUser->nickname;
						}
						if (!empty($updata)) {
							$this->_modify($oRecord->enroll_key, $updata);
						}
						// 已经同步过的用户
						$this->setData($objGrp, $oRecord->enroll_key, $oRecord->data);
					} else {
						// 新用户
						$aOptions2 = ['enroll_key' => $oRecord->enroll_key, 'enroll_at' => $oRecord->enroll_at];
						if (!empty($assignRound) && is_object($assignRound)) {
							$aOptions2['team_id'] = $assignRound->team_id;
							$aOptions2['team_title'] = $assignRound->title;
						}
						$this->enroll($objGrp, $oGrpUser, $aOptions2);
						$this->setData($objGrp, $oRecord->enroll_key, $oRecord->data);
					}
					$cnt++;
				} else {
					// 删除用户
					if ($this->remove($objGrp->id, $oRecord->enroll_key, true)) {
						$cnt++;
					}
				}
			}
		}

		return $cnt;
	}
	/**
	 *
	 */
	private function _modify($ek, $oData) {
		$rst = $this->update(
			'xxt_group_user',
			$oData,
			['enroll_key' => $ek]
		);
		return $rst;
	}
	/**
	 * 获取通讯录用户的data
	 *
	 */
	private function _getMschData($objGrp, $id) {
		/* 获取变化的登记数据 */
		$modelRec = $this->model('site\user\member');
		$q = [
			'm.id enroll_key,m.modify_at enroll_at,m.name nickname,m.name,m.mobile,m.email,m.extattr,m.forbidden,m.userid,a.wx_openid,a.yx_openid,a.qy_openid,a.headimgurl',
			'xxt_site_member m,xxt_site_account a',
			"m.id = $id and a.uid = m.userid",
		];
		$oRecord = $modelRec->query_obj_ss($q);
		if ($oRecord === false) {
			return false;
		}
		if (!empty($oRecord->extattr)) {
			$extattr = json_decode($oRecord->extattr);
			foreach ($extattr as $k => $e) {
				$oRecord->{$k} = $e;
			}
		}

		$dataSchemas = $objGrp->dataSchemas;
		$data = new \stdClass;
		foreach ($dataSchemas as $ds) {
			$data->{$ds->id} = !empty($ds->format) ? $oRecord->{$ds->format} : (!empty($oRecord->{$ds->id}) ? $oRecord->{$ds->id} : '');
		}
		$oRecord->data = $data;

		return $oRecord;
	}
	/**
	 * 从通讯录中导入数据
	 */
	public function assocWithMschema($oGrpApp, $mschemaId, $sync = 'N') {
		$modelMsc = $this->model('site\user\memberschema');

		$oMschema = $modelMsc->byId($mschemaId);
		$dataSchemas = [];
		if ($oMschema->attr_mobile[0] === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'ms_' . $mschemaId . '_mobile';
			$dataSchema->type = 'shorttext';
			$dataSchema->title = '手机号';
			$dataSchema->format = 'mobile';
			$dataSchemas[] = $dataSchema;
		}
		if ($oMschema->attr_mobile[0] === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'ms_' . $mschemaId . '_email';
			$dataSchema->type = 'shorttext';
			$dataSchema->title = '电子邮件';
			$dataSchema->format = 'email';
			$dataSchemas[] = $dataSchema;
		}
		if ($oMschema->attr_mobile[0] === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'ms_' . $mschemaId . '_name';
			$dataSchema->type = 'shorttext';
			$dataSchema->title = '姓名';
			$dataSchema->format = 'name';
			$dataSchemas[] = $dataSchema;
		}
		$extDataSchemas = [];
		if (!empty($oMschema->extAttrs)) {
			foreach ($oMschema->extAttrs as $ea) {
				$dataSchema = new \stdClass;
				foreach ($ea as $key => $val) {
					$dataSchema->{$key} = $val;
				}
				$extDataSchemas[] = $dataSchema;
			}
		}

		$oMschema->dataSchemas = array_merge($dataSchemas, $extDataSchemas);

		/* 导入活动定义 */
		$this->update(
			'xxt_group',
			[
				'last_sync_at' => 0,
				'source_app' => '{"id":"' . $mschemaId . '","type":"mschema"}',
				'data_schemas' => $this->toJson($oMschema->dataSchemas),
			],
			['id' => $oGrpApp->id]
		);
		/* 清空已有分组数据 */
		$this->clean($oGrpApp->id, true);

		return $oMschema;
	}
	/**
	 * 关联报名活动数据
	 */
	public function assocWithEnroll($oGrpApp, $byApp) {
		$modelEnl = $this->model('matter\enroll');

		$oSourceApp = $modelEnl->byId($byApp, ['fields' => 'id,data_schemas,assigned_nickname', 'cascaded' => 'N']);
		$aDataSchemas = $oSourceApp->dataSchemas;

		/* 移除题目中和其他活动、通讯录的关联信息 */
		$modelEnl->replaceAssocSchema($aDataSchemas);
		$modelEnl->replaceMemberSchema($aDataSchemas, null, true);

		/* 导入活动定义 */
		$this->update(
			'xxt_group',
			[
				'last_sync_at' => 0,
				'source_app' => '{"id":"' . $byApp . '","type":"enroll"}',
				'data_schemas' => $this->escape($this->toJson($aDataSchemas)),
				'assigned_nickname' => $oSourceApp->assignedNickname,
			],
			['id' => $oGrpApp->id]
		);
		$oGrpApp->dataSchemas = $oSourceApp->dataSchemas;
		/* 清空已有分组数据 */
		$this->clean($oGrpApp->id, true);

		return $oSourceApp;
	}
	/**
	 * 从签到活动导入数据
	 * 如果指定了包括报名数据，只需要从报名活动中导入登记项的定义，签到时已经自动包含了报名数据
	 */
	public function assocWithSignin($oGrpApp, $byApp, $includeEnroll = 'Y') {
		$modelSignin = $this->model('matter\signin');
		$oSourceApp = $modelSignin->byId($byApp, ['fields' => 'entry_rule,data_schemas,assigned_nickname', 'cascaded' => 'N']);
		$aSourceDataSchemas = $oSourceApp->dataSchemas;
		/**
		 * 导入报名数据，需要合并签到和报名的登记项
		 */
		if ($includeEnroll === 'Y') {
			if (!empty($oSourceApp->entryRule->enroll->id)) {
				$modelEnl = $this->model('matter\enroll');
				$enrollApp = $modelEnl->byId($oSourceApp->entryRule->enroll->id, ['fields' => 'data_schemas', 'cascaded' => 'N']);
				$diff = array_udiff($enrollApp->dataSchemas, $aSourceDataSchemas, create_function('$a,$b', 'return strcmp($a->id,$b->id);'));
				$aSourceDataSchemas = array_merge($aSourceDataSchemas, $diff);
			}
		}

		/* 移除题目中和其他活动、通讯录的关联信息 */
		$modelSignin->replaceAssocSchema($aSourceDataSchemas);
		$modelSignin->replaceMemberSchema($aSourceDataSchemas, null, true);

		/* 导入活动定义 */
		$this->update(
			'xxt_group',
			[
				'last_sync_at' => 0,
				'source_app' => '{"id":"' . $byApp . '","type":"signin"}',
				'data_schemas' => $this->escape($this->toJson($aSourceDataSchemas)),
				'assigned_nickname' => $oSourceApp->assigned_nickname,
			],
			['id' => $oGrpApp->id]
		);
		$oGrpApp->dataSchemas = $aSourceDataSchemas;

		/* 清空已有数据 */
		$this->clean($oGrpApp->id, true);

		return $oSourceApp;
	}
	/**
	 * 从信息墙导入数据
	 * $onlySpeaker 是否为发言的用户
	 */
	public function assocWithWall($oGrpApp, $byApp, $onlySpeaker) {
		$modelWall = $this->model('matter\wall');
		$oSourceApp = $modelWall->byId($byApp, ['fields' => 'data_schemas']);
		$aSourceDataSchemas = $oSourceApp->dataSchemas;

		/* 移除题目中和其他活动、通讯录的关联信息 */
		$modelWall->replaceAssocSchema($aSourceDataSchemas);
		$modelWall->replaceMemberSchema($aSourceDataSchemas, null, true);

		/* 导入活动定义 */
		$this->update(
			'xxt_group',
			[
				'last_sync_at' => 0,
				'source_app' => '{"id":"' . $byApp . '","type":"wall"}',
				'data_schemas' => $this->escape($this->toJson($aSourceDataSchemas)),
			],
			['id' => $oGrpApp->id]
		);
		$oGrpApp->dataSchemas = $aSourceDataSchemas;

		/* 清空已有分组数据 */
		$this->clean($oGrpApp->id, true);

		return $oSourceApp;
	}
	/**
	 * 删除一个分组用户
	 *
	 * @param string $appId
	 * @param string $ek
	 */
	public function remove($appId, $ek, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_group_user_data',
				['aid' => $appId, 'enroll_key' => $ek]
			);
			$rst = $this->delete(
				'xxt_group_user',
				['aid' => $appId, 'enroll_key' => $ek]
			);
		} else {
			$rst = $this->update(
				'xxt_group_user_data',
				['state' => 100],
				['aid' => $appId, 'enroll_key' => $ek]
			);
			$rst = $this->update(
				'xxt_group_user',
				['state' => 100],
				['aid' => $appId, 'enroll_key' => $ek]
			);
		}

		return $rst;
	}
	/**
	 * 清除所有记录
	 *
	 * @param string $appId
	 */
	public function clean($appId, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_group_user_data',
				['aid' => $appId]
			);
			$rst = $this->delete(
				'xxt_group_user',
				['aid' => $appId]
			);
		} else {
			$rst = $this->update(
				'xxt_group_user_data',
				['state' => 0],
				['aid' => $appId]
			);
			$rst = $this->update(
				'xxt_group_user',
				['state' => 0],
				['aid' => $appId]
			);
		}

		return $rst;
	}
}