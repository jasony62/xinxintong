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
	public function enroll($siteId, &$app, &$user, $options = array()) {
		if (isset($options['enroll_key'])) {
			$ek = $options['enroll_key'];
		} else {
			$ek = $this->genKey($siteId, $app->id);
		}
		$player = array(
			'aid' => $app->id,
			'siteid' => $siteId,
			'enroll_key' => $ek,
			'userid' => $user->uid,
			'nickname' => $user->nickname,
		);
		$player['enroll_at'] = isset($options['enroll_at']) ? $options['enroll_at'] : time();
		isset($options['referrer']) && $player['referrer'] = $options['referrer'];

		$this->insert('xxt_group_player', $player, false);

		return $ek;
	}
	/**
	 * 保存登记的数据
	 */
	public function setData($user, $siteId, &$app, $ek, $data, $submitkey = '') {
		if (empty($data)) {
			return array(true);
		}
		if (empty($submitkey)) {
			$submitkey = $user->uid;
		}
		/* 已有的登记数据 */
		$fields = $this->query_vals_ss(array('name', 'xxt_group_player_data', "aid='{$app->id}' and enroll_key='$ek'"));
		foreach ($data as $n => $v) {
			/**
			 * 插入自定义属性
			 */
			if ($n === 'member' && is_object($v)) {
				/* 用户认证信息 */
				$vv = new \stdClass;
				isset($v->name) && $vv->name = urlencode($v->name);
				isset($v->email) && $vv->email = urlencode($v->email);
				isset($v->mobile) && $vv->mobile = urlencode($v->mobile);
				if (!empty($v->extattr)) {
					$extattr = new \stdClass;
					foreach ($v->extattr as $mek => $mev) {
						$extattr->{$mek} = urlencode($mev);
					}
					$vv->extattr = $extattr;
				}
				$vv = urldecode(json_encode($vv));
			} else if (is_array($v) && (isset($v[0]->serverId) || isset($v[0]->imgSrc))) {
				/* 上传图片 */
				$vv = array();
				$fsuser = \TMS_APP::model('fs/user', $siteId);
				foreach ($v as $img) {
					$rst = $fsuser->storeImg($img);
					if (false === $rst[0]) {
						return $rst;
					}
					$vv[] = $rst[1];
				}
				$vv = implode(',', $vv);
			} else if (is_array($v) && isset($v[0]->uniqueIdentifier)) {
				/* 上传文件 */
				$fsUser = \TMS_APP::M('fs/local', $siteId, '_user');
				$fsResum = \TMS_APP::M('fs/local', $siteId, '_resumable');
				$fsAli = \TMS_APP::M('fs/alioss', $siteId);
				$vv = array();
				foreach ($v as $file) {
					if (defined('SAE_TMP_PATH')) {
						$dest = '/' . $app->id . '/' . $submitkey . '_' . $file->name;
						$fileUploaded2 = $fsAli->getBaseURL() . $dest;
					} else {
						$fileUploaded = $fsResum->rootDir . '/' . $submitkey . '_' . $file->uniqueIdentifier;
						!file_exists($fsUser->rootDir . '/' . $submitkey) && mkdir($fsUser->rootDir . '/' . $submitkey, 0777, true);
						$fileUploaded2 = $fsUser->rootDir . '/' . $submitkey . '/' . $file->name;
						if (false === rename($fileUploaded, $fileUploaded2)) {
							return array(false, '移动上传文件失败');
						}
					}
					unset($file->uniqueIdentifier);
					$file->url = $fileUploaded2;
					$vv[] = $file;
				}
				$vv = json_encode($vv);
			} else {
				if (is_string($v)) {
					$vv = $this->escape($v);
				} else if (is_object($v) || is_array($v)) {
					$vv = implode(',', array_keys(array_filter((array) $v, function ($i) {return $i;})));
				} else {
					$vv = $v;
				}
			}
			if (!empty($fields) && in_array($n, $fields)) {
				$this->update(
					'xxt_group_player_data',
					array('value' => $vv),
					"aid='{$app->id}' and enroll_key='$ek' and name='$n'"
				);
				unset($fields[array_search($n, $fields)]);
			} else {
				$ic = array(
					'aid' => $app->id,
					'enroll_key' => $ek,
					'name' => $n,
					'value' => $vv,
				);
				$this->insert('xxt_group_player_data', $ic, false);
			}
		}

		return array(true);
	}
	/**
	 * 根据ID返回登记记录
	 */
	public function &byId($ek, $options = array()) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';

		$q = array(
			$fields,
			'xxt_group_player',
			"enroll_key='$ek'",
		);
		if (($record = $this->query_obj_ss($q)) && $cascaded === 'Y') {
			$record->data = $this->dataById($ek);
		}

		return $record;
	}
	/**
	 * 获得一条登记记录的数据
	 */
	public function dataById($ek) {
		$q = array(
			'name,value',
			'xxt_group_player_data',
			"enroll_key='$ek'",
		);
		$cusdata = array();
		$cdata = $this->query_objs_ss($q);
		if (count($cdata) > 0) {
			foreach ($cdata as $cd) {
				$cusdata[$cd->name] = $cd->value;
			}
		}
		return $cusdata;
	}
	/**
	 * 用户清单
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 */
	public function find($siteId, &$app, $options = null) {
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
		$w = "e.state=1 and e.siteid='$siteId' and e.aid='{$app->id}'";
		if (!empty($kw) && !empty($by)) {
			switch ($by) {
			case 'mobile':
				$kw && $w .= " and m.mobile like '%$kw%'";
				break;
			case 'nickname':
				$kw && $w .= " and e.nickname like '%$kw%'";
				break;
			}
		}
		/*tags*/
		if (!empty($options->tags)) {
			$aTags = explode(',', $options->tags);
			foreach ($aTags as $tag) {
				$w .= "and concat(',',e.tags,',') like '%,$tag,%'";
			}
		}
		$q = array(
			'e.enroll_key,e.enroll_at,e.tags,e.nickname,e.userid,e.round_id,e.round_title',
			"xxt_group_player e",
			$w,
		);
		/* 分页参数 */
		if (isset($page)) {
			$q2 = array(
				'r' => array('o' => ($page - 1) * $size, 'l' => $size),
			);
		}
		/* 排序 */
		$q2['o'] = 'e.enroll_at desc';
		if ($players = $this->query_objs_ss($q, $q2)) {
			/* record data */
			foreach ($players as &$player) {
				$player->data = $this->dataById($player->enroll_key);
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
	public function &byPlayer($siteid, $aid, $userid) {
		if (empty($userid)) {
			return false;
		}

		$q = array(
			'*',
			'xxt_group_player',
			"state=1 and siteid='$siteid' and aid='$aid' and userid='$userid'",
		);
		$q2 = array('o' => 'enroll_at desc');

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
				array('state' => 0),
				"aid='$appId' and enroll_key='$ek'"
			);
			$rst = $this->update(
				'xxt_group_player',
				array('state' => 0),
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
	 * 有资格参加指定轮次分组的用户
	 */
	public function &pendings($appId, $hasData = 'N') {
		/* 没有抽中过的用户 */
		$q = array(
			'id,enroll_key,nickname,userid,enroll_at,tags',
			'xxt_group_player',
			"aid='$appId' and state=1 and round_id=0",
		);
		$q2['o'] = 'enroll_at desc';
		/* 获得用户的登记数据 */
		if (($players = $this->query_objs_ss($q, $q2)) && !empty($players)) {
			/**
			 * 获得自定义数据的值
			 */
			foreach ($players as &$player) {
				$player->data = new \stdClass;
				$qc = array(
					'name,value',
					'xxt_group_player_data',
					"enroll_key='$player->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				foreach ($cds as $cd) {
					if ($cd->name === 'member') {
						$player->data->{$cd->name} = json_decode($cd->value);
					} else {
						$player->data->{$cd->name} = $cd->value;
					}
				}
			}
			/* 删除没有填写登记信息的数据 */
			if ($hasData === 'Y') {
				$players2 = array();
				foreach ($players as $p2) {
					if (empty($p2->data->name) && empty($p2->data->mobile)) {
						continue;
					}
					$players2[] = $p2;
				}
				$result = $players2;
			} else {
				$result = $players;
			}
		} else {
			$result = $players;
		}

		return $result;
	}
	/**
	 * 指定分组内的用户
	 */
	public function &winnersByRound($appId, $rid = null) {
		$q = array(
			'*',
			'xxt_group_player',
			"aid='$appId'",
		);
		if (!empty($rid)) {
			$q[2] .= " and round_id='$rid'";
		} else {
			$q[2] .= " and round_id<>0";
		}
		$q2 = array('o' => 'round_id,draw_at');
		if ($players = $this->query_objs_ss($q, $q2)) {
			/**
			 * 获得自定义数据的值
			 */
			foreach ($players as &$p) {
				$p->data = new \stdClass;
				$qc = array(
					'name,value',
					'xxt_group_player_data',
					"enroll_key='$p->enroll_key'",
				);
				$cds = $this->query_objs_ss($qc);
				foreach ($cds as $cd) {
					if ($cd->name === 'member') {
						$p->data->{$cd->name} = json_decode($cd->value);
					} else {
						$p->data->{$cd->name} = $cd->value;
					}
				}
			}
		}

		return $players;
	}
}