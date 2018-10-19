<?php
namespace matter\group;
/**
 * 分组活动用户
 */
class user_model extends \TMS_MODEL {
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
			"aid='{$oRound->aid}' and state=1",
		];
		switch ($oRound->round_type) {
		case 'T':
			$q[2] .= " and round_id='$rid'";
			break;
		case 'R':
			$q[2] .= " and role_rounds like '%\"" . $oRound->rid . "\"%'";
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