<?php
namespace matter\group;
/**
 * 分组活动用户
 */
class user_model extends \TMS_MODEL {
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
			$kw = isset($oOptions->kw) ? $oOptions->kw : null;
			$by = isset($oOptions->by) ? $oOptions->by : null;
		}

		$fields = isset($oOptions->fields) ? $oOptions->fields : 'enroll_key,enroll_at,comment,tags,data,userid,nickname,is_leader,wx_openid,yx_openid,qy_openid,headimgurl,round_id,round_title,role_rounds';

		/* 数据过滤条件 */
		$w = "state=1 and aid='{$oApp->id}'";
		/*tags*/
		if (!empty($oOptions->tags)) {
			$aTags = explode(',', $oOptions->tags);
			foreach ($aTags as $tag) {
				$w .= " and concat(',',tags,',') like '%,$tag,%'";
			}
		}
		if (isset($oOptions->roleRoundId) && ($oOptions->roleRoundId == '' || $oOptions->roleRoundId == 'pending')) {
			$w .= " and role_rounds = ''";
		} else if (isset($oOptions->roleRoundId) && (strcasecmp($oOptions->roleRoundId, 'all') !== 0)) {
			$w .= " and role_rounds like '%\"" . $oOptions->roleRoundId . "\"%' and userid <> ''";
		}
		if (isset($oOptions->roundId) && ($oOptions->roundId == '' || $oOptions->roundId == 'pending')) {
			$w .= " and round_id = ''";
		} else if (isset($oOptions->roundId) && (strcasecmp($oOptions->roundId, 'all') !== 0)) {
			$w .= " and round_id = '" . $oOptions->roundId . "' and userid <> ''";
		}
		// 根据用户昵称过滤
		if (isset($oOptions->nickname)) {
			$w .= " and nickname like '%{$oOptions->nickname}%'";
		}
		$q = [
			$fields,
			'xxt_group_player',
			$w,
		];
		/* 分页参数 */
		if (isset($page)) {
			$q2 = [
				'r' => ['o' => ($page - 1) * $size, 'l' => $size],
			];
		}
		/* 排序 */
		$q2['o'] = 'round_id asc,enroll_at desc';

		$oResult = new \stdClass; // 返回的结果
		$users = $this->query_objs_ss($q, $q2);
		if (count($users)) {
			/* record data */
			if ($fields === '*' || false !== strpos($fields, 'data') || false !== strpos($fields, 'role_rounds')) {
				foreach ($users as $oUser) {
					if (!empty($oUser->data)) {
						$oUser->data = json_decode($oUser->data);
					}
					if (!empty($oUser->role_rounds)) {
						$oUser->role_rounds = json_decode($oUser->role_rounds);
					} else {
						$oUser->role_rounds = [];
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
			'xxt_group_player',
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
			if ($fields === '*' || false !== strpos($fields, 'role_rounds')) {
				$aRecHandler[] = function (&$oUser) {
					$oUser->role_rounds = empty($oUser->role_rounds) ? [] : json_decode($oUser->role_rounds);
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
		$oRound = $this->model('matter\group\round')->byId($rid, ['fields' => 'aid,round_id,round_type']);
		if (false === $oRound) {
			return false;
		}

		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_group_player',
			['aid' => $oRound->aid, 'state' => 1],
		];
		switch ($oRound->round_type) {
		case 'T':
			$q[2]['round_id'] = $oRound->round_id;
			break;
		case 'R':
			$q[2]['role_rounds'] = (object) ['op' => 'like', 'pat' => '%' . $oRound->round_id . '%'];
			break;
		default:
			return false;
		}

		$q2 = ['o' => 'round_id,draw_at'];

		if ($users = $this->query_objs_ss($q, $q2)) {
			if ($fields === '*' || false !== strpos($fields, 'data') || false !== strpos($fields, 'role_rounds')) {
				foreach ($users as $oUser) {
					if (!empty($oUser->data)) {
						$oUser->data = json_decode($oUser->data);
					}
					if (!empty($oUser->role_rounds)) {
						$oUser->role_rounds = json_decode($oUser->role_rounds);
					} else {
						$oUser->role_rounds = [];
					}
				}
			}
		}

		return $users;
	}
	/**
	 * 指定用户是否属于指定用户组
	 */
	public function isInRound($roundId, $userid) {
		/* 主分组 */
		$q = [
			'1',
			'xxt_group_player',
			['userid' => $userid, 'state' => 1, 'round_id' => $roundId],
		];
		$oUser = $this->query_obj_ss($q);
		if ($oUser) {
			return true;
		}
		/* 辅助分组 */
		unset($q[2]['round_id']);
		$q[2]['role_rounds'] = (object) ['op' => 'like', 'pat' => '%' . $roundId . '%'];
		$oUser = $this->query_obj_ss($q);
		if ($oUser) {
			return true;
		}

		return false;
	}
}