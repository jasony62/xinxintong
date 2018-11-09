<?php
namespace matter\mission;
/**
 *
 */
class user_model extends \TMS_MODEL {
	/**
	 * 获得指定项目下指定用户的行为数据
	 */
	public function byId($oMission, $userid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_user',
			['mission_id' => $oMission->id, 'userid' => $userid],
		];

		$oUser = $this->query_obj_ss($q);
		if ($oUser) {
			if (property_exists($oUser, 'modify_log')) {
				$oUser->modify_log = empty($oUser->modify_log) ? [] : json_decode($oUser->modify_log);
			}
			if (property_exists($oUser, 'custom')) {
				$oUser->custom = empty($oUser->custom) ? new \stdClass : json_decode($oUser->custom);
			}
		}

		return $oUser;
	}
	/**
	 * 添加一个项目用户
	 */
	public function add($oMission, $oUser, $data = []) {
		$oNewUsr = new \stdClass;
		$oNewUsr->siteid = $oMission->siteid;
		$oNewUsr->mission_id = $oMission->id;
		$oNewUsr->userid = $oUser->uid;
		$oNewUsr->group_id = empty($oUser->group_id) ? '' : $oUser->group_id;
		$oNewUsr->nickname = $this->escape($oUser->nickname);

		foreach ($data as $k => $v) {
			switch ($k) {
			case 'last_enroll_at':
			case 'last_cowork_at':
			case 'last_do_cowork_at':
			case 'last_like_at':
			case 'last_dislike_at':
			case 'last_like_cowork_at':
			case 'last_dislike_cowork_at':
			case 'last_do_like_at':
			case 'last_do_dislike_at':
			case 'last_remark_at':
			case 'last_remark_cowork_at':
			case 'last_do_remark_at':
			case 'last_like_remark_at':
			case 'last_dislike_remark_at':
			case 'last_do_like_cowork_at':
			case 'last_do_dislike_cowork_at':
			case 'last_do_like_remark_at':
			case 'last_do_dislike_remark_at':
			case 'last_agree_at':
			case 'last_agree_cowork_at':
			case 'last_agree_remark_at':
			case 'last_topic_at':
			case 'enroll_num':
			case 'cowork_num':
			case 'do_cowork_num':
			case 'do_like_num':
			case 'do_dislike_num':
			case 'do_like_cowork_num':
			case 'do_dislike_cowork_num':
			case 'do_like_remark_num':
			case 'do_dislike_remark_num':
			case 'like_num':
			case 'dislike_num':
			case 'like_cowork_num':
			case 'dislike_cowork_num':
			case 'like_remark_num':
			case 'dislike_remark_num':
			case 'do_remark_num':
			case 'remark_num':
			case 'remark_cowork_num':
			case 'agree_num':
			case 'agree_cowork_num':
			case 'agree_remark_num':
			case 'user_total_coin':
			case 'topic_num':
			case 'do_repos_read_num':
			case 'do_topic_read_num':
			case 'topic_read_num':
			case 'do_cowork_read_num':
			case 'cowork_read_num':
			case 'do_cowork_read_elapse':
			case 'cowork_read_elapse':
			case 'do_topic_read_elapse':
			case 'topic_read_elapse':
			case 'do_repos_read_elapse':
				$oNewUsr->{$k} = $v;
				break;
			case 'score':
				$oNewUsr->{$k} = $v;
				break;
			case 'modify_log':
				if (!is_string($v)) {
					$oNewUsr->{$k} = json_encode([$v]);
				}
			case 'custom':
				if (!is_string($v)) {
					$oNewUsr->{$k} = $this->escape($this->toJson($v));
				}
				break;
			}
		}
		$oNewUsr->id = $this->insert('xxt_mission_user', $oNewUsr, true);

		return $oNewUsr;
	}
	/**
	 * 修改用户数据
	 */
	public function modify($oBeforeData, $oUpdatedData) {
		$aDbData = [];
		foreach ($oUpdatedData as $field => $value) {
			switch ($field) {
			case 'last_entry_at':
			case 'last_enroll_at':
			case 'last_cowork_at':
			case 'last_do_cowork_at':
			case 'last_like_at':
			case 'last_dislike_at':
			case 'last_like_cowork_at':
			case 'last_dislike_cowork_at':
			case 'last_do_like_at':
			case 'last_do_dislike_at':
			case 'last_remark_at':
			case 'last_remark_cowork_at':
			case 'last_do_remark_at':
			case 'last_like_remark_at':
			case 'last_dislike_remark_at':
			case 'last_do_like_cowork_at':
			case 'last_do_dislike_cowork_at':
			case 'last_do_like_remark_at':
			case 'last_do_dislike_remark_at':
			case 'last_agree_at':
			case 'last_agree_cowork_at':
			case 'last_agree_remark_at':
			case 'last_topic_at':
				$aDbData[$field] = $value;
				break;
			case 'entry_num':
			case 'total_elapse':
			case 'enroll_num':
			case 'cowork_num':
			case 'do_cowork_num':
			case 'do_like_num':
			case 'do_dislike_num':
			case 'do_like_cowork_num':
			case 'do_dislike_cowork_num':
			case 'do_like_remark_num':
			case 'do_dislike_remark_num':
			case 'like_num':
			case 'dislike_num':
			case 'like_cowork_num':
			case 'dislike_cowork_num':
			case 'like_remark_num':
			case 'dislike_remark_num':
			case 'do_remark_num':
			case 'remark_num':
			case 'remark_cowork_num':
			case 'agree_num':
			case 'agree_cowork_num':
			case 'agree_remark_num':
			case 'user_total_coin':
			case 'topic_num':
			case 'do_repos_read_num':
			case 'do_topic_read_num':
			case 'topic_read_num':
			case 'do_cowork_read_num':
			case 'cowork_read_num':
			case 'do_cowork_read_elapse':
			case 'cowork_read_elapse':
			case 'do_topic_read_elapse':
			case 'topic_read_elapse':
			case 'do_repos_read_elapse':
				$aDbData[$field] = (int) $oBeforeData->{$field}+$value;
				break;
			case 'score':
				/* 更新时传入的得分可能只是用户在某个活动中的得分，需要重新计算用户在整个项目中的得分 */
				$aDbData['score'] = $this->_scoreByUser($oBeforeData);
				break;
			case 'group_id':
			case 'nickname':
				$aDbData[$field] = $value;
				break;
			case 'modify_log':
				if (empty($oBeforeData->modify_log) || !is_array($oBeforeData->modify_log)) {
					$oBeforeData->modify_log = [];
				}
				array_unshift($oBeforeData->modify_log, $value);
				$aDbData['modify_log'] = json_encode($oBeforeData->modify_log);
				break;
			}
		}
		if (!empty($aDbData)) {
			$rst = $this->update('xxt_mission_user', $aDbData, ['id' => $oBeforeData->id]);
		}

		return true;
	}
	/**
	 * 用户在整个项目中的得分
	 */
	private function _scoreByUser($oMisUser) {
		$q = [
			'id',
			'xxt_enroll',
			['mission_id' => $oMisUser->mission_id, 'state' => 1],
		];
		$appIds = $this->query_vals_ss($q);
		if (count($appIds)) {
			$q = [
				'sum(score)',
				'xxt_enroll_user',
				['userid' => $oMisUser->userid, 'aid' => $appIds, 'rid' => 'ALL'],
			];
			$sum = (float) $this->query_val_ss($q);
		} else {
			$sum = 0;
		}

		return $sum;
	}
	/**
	 * 删除1条记录
	 */
	public function removeRecord($missionId, $oRecord) {
		if (empty($missionId) || empty($oRecord->userid)) {
			return [false, '参数不完整'];
		}

		$rst = $this->update(
			'xxt_mission_user',
			['enroll_num' => (object) ['op' => '-=', 'pat' => 1]],
			['mission_id' => $missionId, 'userid' => $oRecord->userid, 'enroll_num' => (object) ['op' => '>', 'pat' => 0]]
		);

		return [true, $rst];
	}
	/**
	 * 恢复1条记录
	 */
	public function restoreRecord($missionId, $oRecord) {
		if (empty($missionId) || empty($oRecord->userid)) {
			return [false, '参数不完整'];
		}

		$rst = $this->update(
			'xxt_mission_user',
			['enroll_num' => (object) ['op' => '+=', 'pat' => 1]],
			['mission_id' => $missionId, 'userid' => $oRecord->userid]
		);

		return [true, $rst];
	}
	/**
	 * 参与过活动任务的用户
	 */
	public function enrolleeByMission($oMission, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_mission_user',
			['mission_id' => $oMission->id],
		];

		/* 筛选条件 */
		if (isset($aOptions['filter'])) {
			$oFilter = $aOptions['filter'];
			if (!empty($oFilter->by) && !empty($oFilter->keyword)) {
				$q[2][$oFilter->by] = (object) ['op' => 'like', 'pat' => '%' . $oFilter->keyword . '%'];
			}
		}
		$q2 = [];
		/* 排序规则 */
		if (!empty($aOptions['orderBy'])) {
			$q2['o'] = $aOptions['orderBy'] . ' desc';
		}

		$oUsers = $this->query_objs_ss($q, $q2);

		return $oUsers;
	}
	/**
	 * 项目用户获得奖励积分
	 */
	public function awardCoin($oMission, $userid, $deltaCoin) {
		$oMisUsr = $this->byId($oMission, $userid, ['fields' => 'id,userid,nickname,user_total_coin']);
		if (false === $oMisUsr) {
			return false;
		}
		$modelMisUsr->update(
			'xxt_mission_user',
			['user_total_coin' => (int) $oMisUsr->user_total_coin + $deltaCoin],
			['id' => $oMisUsr->id]
		);

		return true;
	}
	/**
	 * 项目用户扣除奖励积分
	 */
	public function deductCoin($oMission, $userid, $deductCoin) {
		$oMisUsr = $this->byId($oMission, $userid, ['fields' => 'id,userid,nickname,user_total_coin']);
		if (false === $oMisUsr) {
			return false;
		}
		$modelMisUsr->update(
			'xxt_mission_user',
			['user_total_coin' => (int) $oMisUsr->user_total_coin - $deductCoin],
			['id' => $oMisUsr->id]
		);

		return true;
	}
	/*
		 * 用户参与过的项目
	*/
	public function byUser($userId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'u.user_total_coin,u.modify_log,m.id,m.title';

		$q = array(
			$fields,
			'xxt_mission_user u,xxt_mission m',
			"u.userid = '{$userId}' and u.mission_id = m.id and m.state = 1",
		);

		if (!empty($options['bySite'])) {
			$q['2'] .= " and u.siteid = '{$options['bySite']}'";
		}
		if (!empty($options['byName'])) {
			$q['2'] .= " and m.title like '%" . $options['byName'] . "%'";
		}

		$p = ['o' => 'u.id desc'];
		if (!empty($options['at'])) {
			$p['r'] = ['o' => ($options['at']['page'] - 1), 'l' => $options['at']['size']];
		}

		$model = $this->model();
		$missions = $model->query_objs_ss($q, $p);

		$data = new \stdClass;
		$data->logs = $missions;
		$q[0] = 'count(m.id)';
		$data->total = $model->query_val_ss($q);

		return $data;
	}
}