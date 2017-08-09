<?php
namespace matter\group;
/**
 *
 */
class player_model extends \TMS_MODEL {
	/**
	 * 用户登记（不包括登记数据）
	 *
	 * @param string $siteId
	 * @param object $app
	 * @param object $user
	 * @param array $options
	 */
	public function enroll($siteId, &$app, &$user, $options = []) {
		if (is_object($options)) {
			$options = (array) $options;
		}

		if (isset($options['enroll_key'])) {
			$ek = $options['enroll_key'];
		} else {
			$ek = $this->genKey($siteId, $app->id);
		}

		$player = [
			'aid' => $app->id,
			'siteid' => $siteId,
			'enroll_key' => $ek,
			'userid' => $user->uid,
			'nickname' => $this->escape($user->nickname),
			'wx_openid' => $user->wx_openid,
			'yx_openid' => $user->yx_openid,
			'qy_openid' => $user->qy_openid,
			'headimgurl' => $user->headimgurl,
		];
		$player['enroll_at'] = isset($options['enroll_at']) ? $options['enroll_at'] : time();
		isset($options['round_id']) && $player['round_id'] = $options['round_id'];
		isset($options['round_title']) && $player['round_title'] = $this->escape($options['round_title']);
		isset($options['comment']) && $player['comment'] = $this->escape($options['comment']);
		isset($options['tags']) && $player['tags'] = $this->escape($options['tags']);
		isset($options['referrer']) && $player['referrer'] = $options['referrer'];

		$this->insert('xxt_group_player', $player, false);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData($siteId, &$app, $ek, $data) {
		if (empty($data)) {
			return array(true);
		}
		// 处理后的登记记录
		$dbData = new \stdClass;

		$schemasById = [];
		foreach ($app->dataSchemas as $schema) {
			$schemasById[$schema->id] = $schema;
		}

		/* 已有的登记数据 */
		$fields = $this->query_vals_ss(['name', 'xxt_group_player_data', "aid='{$app->id}' and enroll_key='$ek'"]);

		foreach ($data as $n => $v) {
			if (!isset($schemasById[$n])) {
				continue;
			}

			$schema = $schemasById[$n];
			/**
			 * 插入自定义属性
			 */
			if ($n === 'member' && is_object($v)) {
				//
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
			} else if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
				/* 上传图片 */
				$treatedValue = array();
				$fsuser = \TMS_APP::model('fs/user', $siteId);
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
					'xxt_group_player_data',
					['value' => $treatedValue],
					"aid='{$app->id}' and enroll_key='$ek' and name='$n'"
				);
				unset($fields[array_search($n, $fields)]);
			} else {
				$ic = [
					'aid' => $app->id,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $treatedValue,
				];
				$this->insert('xxt_group_player_data', $ic, false);
			}
		}
		// 记录数据
		$dbData = $this->toJson($dbData);
		$this->update(
			'xxt_group_player',
			['enroll_at' => time(), 'data' => $dbData],
			"enroll_key='$ek'"
		);

		return [true, $dbData];
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function &byId($aid, $ek, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = [
			$fields,
			'xxt_group_player',
			"aid='$aid' and enroll_key='$ek' and state=1",
		];
		if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
			if (!empty($record->data)) {
				$record->data = json_decode($record->data);
			}
		}

		return $record;
	}
	/**
	 * 根据ID返回登记记录
	 *
	 * @param string $ek 因为分组活动的用户有可能是从其他活动导入的，使用的是导入记录的ek，因为有可能一个ek导入到多个分组活动中
	 * @param string $aid 分组活动的id。如果指定只返回单条记录，如果不指定返回数据
	 *
	 */
	public function byEnrollKey($ek, $aid = null, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = [
			$fields,
			'xxt_group_player',
			["enroll_key" => $ek, "state" => 1],
		];
		if (empty($aid)) {
			$records = $this->query_objs_ss($q);
			if (count($records) && $cascaded === 'Y' && ($fields === '*' || false !== strpos($fields, 'data'))) {
				foreach ($records as &$record) {
					if (!empty($record->data)) {
						$record->data = json_decode($record->data);
					}
				}
			}
			return $records;
		} else {
			$q[2]['aid'] = $aid;
			if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y' && ($fields === '*' || false !== strpos($fields, 'data'))) {
				if (!empty($record->data)) {
					$record->data = json_decode($record->data);
				}
			}
			return $record;
		}

	}
	/**
	 * 获得指定项目下的登记记录
	 *
	 * @param int $missionId
	 */
	public function &byMission($missionId, $options) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_player r',
		];
		$missionId = $this->escape($missionId);
		$where = "state=1 and exists(select 1 from xxt_group g where r.aid=g.id and g.mission_id={$missionId})";

		if (isset($options['userid'])) {
			$where .= " and userid='" . $this->escape($options['userid']) . "'";
		}
		$q[2] = $where;

		$list = $this->query_objs_ss($q);
		if (count($list)) {
			if ($fields === '*' || strpos($fields, 'data') !== false) {
				foreach ($list as &$record) {
					$record->data = json_decode($record->data);
				}
			}
		}

		return $list;
	}
	/**
	 * 根据指定的数据查找匹配的记录
	 */
	public function &byData(&$oApp, &$data, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$records = false;

		// 查找条件
		$whereByData = '';
		foreach ($data as $k => $v) {
			if ($k === '_round_id') {
				$whereByData .= ' and (';
				$whereByData .= 'round_id="' . $v . '"';
				$whereByData .= ')';
			} else {
				if (!empty($v)) {
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
			'xxt_group_player',
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
		}

		return $records;
	}
	/**
	 * 用户清单
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 */
	public function byApp(&$oApp, $options = null) {
		if (is_string($oApp)) {
			$oApp = $this->model('matter\group')->byId($oApp, ['cascaded' => 'N']);
		}
		if ($options) {
			is_array($options) && $options = (object) $options;
			$orderby = isset($options->orderby) ? $options->orderby : '';
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$kw = isset($options->kw) ? $options->kw : null;
			$by = isset($options->by) ? $options->by : null;
		}
		$result = new \stdClass; // 返回的结果
		$result->total = 0;
		/* 数据过滤条件 */
		$w = "e.state=1 and e.aid='{$oApp->id}'";
		/*tags*/
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}
		}
		$q = [
			'e.enroll_key,e.enroll_at,e.comment,e.tags,e.data,e.nickname,e.is_leader,e.wx_openid,e.yx_openid,e.qy_openid,e.headimgurl,e.userid,e.round_id,e.round_title',
			"xxt_group_player e",
			$w,
		];
		/* 分页参数 */
		if (isset($page)) {
			$q2 = [
				'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			];
		}
		/* 排序 */
		$q2['o'] = 'e.enroll_at desc';
		if ($players = $this->query_objs_ss($q, $q2)) {
			/* record data */
			foreach ($players as &$player) {
				$player->data = json_decode($player->data);
			}
			$result->players = $players;
			/* total */
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		}

		return $result;
	}
	/**
	 * 获得用户的登记
	 */
	public function &byUser($oApp, $userid, $options = []) {
		if (empty($userid)) {
			return false;
		}

		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_player',
			['state' => 1, 'aid' => $oApp->id, 'userid' => $userid],
		];
		$q2 = ['o' => 'enroll_at desc'];

		$list = $this->query_objs_ss($q, $q2);

		return $list;
	}
	/**
	 * 生成活动登记的key
	 */
	public function genKey($siteId, $aid) {
		return md5(uniqid() . $siteId . $aid);
	}
	/**
	 *
	 */
	public function modify($ek, $data) {
		$rst = $this->update(
			'xxt_group_player',
			$data,
			"enroll_key='$ek'"
		);
		return $rst;
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
				'xxt_group_player_data',
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->delete(
				'xxt_group_player',
				"aid='$appId' and enroll_key='$ek'"
			);
		} else {
			$rst = $this->update(
				'xxt_group_player_data',
				array('state' => 100),
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->update(
				'xxt_group_player',
				array('state' => 100),
				"aid='$appId' and enroll_key='$ek'"
			);
		}

		return $rst;
	}
	/**
	 * 清除所有登记记录
	 *
	 * @param string $appId
	 */
	public function clean($appId, $byDelete = false) {
		if ($byDelete) {
			$rst = $this->delete(
				'xxt_group_player_data',
				"aid='$appId'"
			);
			$rst = $this->delete(
				'xxt_group_player',
				"aid='$appId'"
			);
		} else {
			$rst = $this->update(
				'xxt_group_player_data',
				array('state' => 0),
				"aid='$appId'"
			);
			$rst = $this->update(
				'xxt_group_player',
				array('state' => 0),
				"aid='$appId'"
			);
		}

		return $rst;
	}
	/**
	 * 移出分组
	 */
	public function quitGroup($appId, $ek) {
		$rst = $this->update(
			'xxt_group_player',
			[
				'round_id' => 0,
				'round_title' => '',
			],
			["aid" => $appId, "enroll_key" => $ek]
		);

		return $rst;
	}
	/**
	 * 移入分组
	 */
	public function joinGroup($appId, &$round, $ek) {
		$rst = $this->update(
			'xxt_group_player',
			[
				'round_id' => $round->round_id,
				'round_title' => $round->title,
			],
			["aid" => $appId, "enroll_key" => $ek]
		);

		return $rst;
	}
	/**
	 * 有资格参加指定轮次分组的用户
	 */
	public function &pendings($appId) {
		/* 没有抽中过的用户 */
		$q = array(
			'id,enroll_key,nickname,wx_openid,yx_openid,qy_openid,headimgurl,userid,enroll_at,data,tags,comment',
			'xxt_group_player',
			"aid='$appId' and state=1 and round_id=0",
		);
		$q2['o'] = 'enroll_at desc';
		/* 获得用户的登记数据 */
		if (($players = $this->query_objs_ss($q, $q2)) && !empty($players)) {
			foreach ($players as &$player) {
				$player->data = json_decode($player->data);
			}
		}

		return $players;
	}
	/**
	 * 指定分组内的用户
	 */
	public function &byRound($appId, $rid = null) {
		$q = [
			'*',
			'xxt_group_player',
			"aid='$appId' and state=1",
		];
		if (!empty($rid)) {
			$q[2] .= " and round_id='$rid'";
		} else {
			$q[2] .= " and round_id<>0";
		}
		$q2 = ['o' => 'round_id,draw_at'];
		if ($players = $this->query_objs_ss($q, $q2)) {
			foreach ($players as &$player) {
				$player->data = json_decode($player->data);
			}
		}

		return $players;
	}
	/**
	 * 获得分组内用户的数量
	 */
	public function &countByRound($appId, $rid) {
		$q = [
			'count(*)',
			'xxt_group_player',
			['aid' => $appId, 'round_id' => $rid, 'state' => 1],
		];
		$cnt = $this->query_val_ss($q);

		return $cnt;
	}
}