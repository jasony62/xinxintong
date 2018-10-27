<?php
namespace matter\group;
/**
 * 分组活动用户
 */
class user_model extends \TMS_MODEL {
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
}