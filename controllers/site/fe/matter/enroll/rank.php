<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动排行榜
 */
class rank extends base {
	/**
	 * 用户排行榜
	 */
	public function userByApp_action($app, $page = 1, $size = 100) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			return new \ParameterError();
		}
		$modelUsr = $this->model('matter\enroll\user');

		$q = [
			'u.userid,u.nickname,a.headimgurl',
			'xxt_enroll_user u left join xxt_site_account a on u.userid = a.uid and u.siteid = a.siteid',
			"u.aid='{$oApp->id}' and u.state=1",
		];

		if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
			$oCriteria->round = explode(',', $oCriteria->round);
		}
		if (empty($oCriteria->round) || in_array('ALL', $oCriteria->round)) {
			$q[2] .= " and u.rid = 'ALL'";
		} else {
			$whereByRound = ' and rid in("';
			$whereByRound .= implode('","', $oCriteria->round);
			$whereByRound .= '")';
			$q[2] .= $whereByRound;
		}

		switch ($oCriteria->orderby) {
		case 'enroll':
			$q[0] .= ',sum(u.enroll_num) enroll_num';
			$q[2] .= ' and u.enroll_num>0';
			$q2 = ['o' => 'enroll_num desc'];
			break;
		case 'remark':
			$q[0] .= ',sum(u.remark_num) remark_num';
			$q[2] .= ' and u.remark_num>0';
			$q2 = ['o' => 'remark_num desc'];
			break;
		case 'like':
			$q[0] .= ',sum(u.like_num) like_num';
			$q[2] .= ' and u.like_num>0';
			$q2 = ['o' => 'like_num desc'];
			break;
		case 'remark_other':
			$q[0] .= ',sum(u.do_remark_num) do_remark_num';
			$q[2] .= ' and u.do_remark_num>0';
			$q2 = ['o' => 'do_remark_num desc'];
			break;
		case 'do_like':
			$q[0] .= ',sum(u.do_like_num) do_like_num';
			$q[2] .= ' and u.do_like_num>0';
			$q2 = ['o' => 'do_like_num desc'];
			break;
		case 'total_coin':
			$q[0] .= ',sum(u.user_total_coin) user_total_coin';
			$q[2] .= ' and u.user_total_coin>0';
			$q2 = ['o' => 'user_total_coin desc'];
			break;
		case 'score':
			$q[0] .= ',sum(u.score) score';
			$q[2] .= ' and u.score>0';
			$q2 = ['o' => 'score desc'];
			break;
		}
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$q2['g'] = ['userid'];

		$oResult = new \stdClass;
		$users = $modelUsr->query_objs_ss($q, $q2);
		if (count($users) && !empty($oApp->entryRule->group->id)) {
			$q = [
				'userid,round_id,round_title',
				'xxt_group_player',
				['aid' => $oApp->entryRule->group->id],
			];
			$userGroups = $modelUsr->query_objs_ss($q);
			if (count($userGroups)) {
				$userGroups2 = new \stdClass;
				foreach ($userGroups as $oUserGroup) {
					if (!empty($oUserGroup->userid)) {
						$userGroups2->{$oUserGroup->userid} = new \stdClass;
						$userGroups2->{$oUserGroup->userid}->round_id = $oUserGroup->round_id;
						$userGroups2->{$oUserGroup->userid}->round_title = $oUserGroup->round_title;
					}
				}
				foreach ($users as $oUser) {
					$oUser->group = isset($userGroups2->{$oUser->userid}) ? $userGroups2->{$oUser->userid} : new \stdClass;
				}
			}
		}
		$oResult->users = $users;

		$q[0] = 'count(*)';
		$oResult->total = (int) $modelUsr->query_val_ss($q);

		return new \ResponseData($oResult);
	}
	/**
	 * 分组排行榜
	 */
	public function groupByApp_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N', 'id,state,entry_rule,data_schemas']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelGrpRnd = $this->model('matter\group\round');
		if (!empty($oApp->entryRule->group->id)) {
			$rounds = $modelGrpRnd->byApp($oApp->entryRule->group->id, ['cascade' => 'playerCount']);
		}
		if (empty($rounds)) {
			return new \ObjectNotFoundError();
		}

		$userGroups = [];
		foreach ($rounds as $oRound) {
			$oNewGroup = new \stdClass;
			$oNewGroup->v = $oRound->round_id;
			$oNewGroup->l = $oRound->title;
			$oNewGroup->playerCount = $oRound->playerCount;
			$userGroups[] = $oNewGroup;
		}

		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			return new \ParameterError();
		}

		$sql = 'select ';
		switch ($oCriteria->orderby) {
		case 'enroll':
			$sql .= 'sum(enroll_num)';
			break;
		case 'remark':
			$sql .= 'sum(remark_num)';
			break;
		case 'like':
			$sql .= 'sum(like_num)';
			break;
		case 'remark_other':
			$sql .= 'sum(do_remark_num)';
			break;
		case 'like_other':
			$sql .= 'sum(do_like_num)';
			break;
		case 'total_coin':
			$sql .= 'sum(user_total_coin)';
			break;
		case 'score':
		case 'average_score':
			$sql .= 'sum(score)';
			break;
		}
		$sql .= ' from xxt_enroll_user where aid=\'' . $oApp->id . "' and state=1";
		if (!empty($oCriteria->round) && is_string($oCriteria->round)) {
			$oCriteria->round = explode(',', $oCriteria->round);
		}
		if (empty($oCriteria->round) || in_array('ALL', $oCriteria->round)) {
			$sql .= " and rid = 'ALL'";
		} else {
			$whereByRound = ' and rid in("';
			$whereByRound .= implode('","', $oCriteria->round);
			$whereByRound .= '")';
			$sql .= $whereByRound;
		}

		/* 获取分组的数据 */
		$modelUsr = $this->model('matter\enroll\user');
		foreach ($userGroups as $oUserGroup) {
			$sqlByGroup = $sql . ' and group_id=\'' . $oUserGroup->v . '\'';
			$oUserGroup->id = $oUserGroup->v;
			$oUserGroup->title = $oUserGroup->l;
			unset($oUserGroup->v);
			unset($oUserGroup->l);
			if (in_array($oCriteria->orderby, ['score', 'average_score'])) {
				if ($oCriteria->orderby === 'score') {
					$oUserGroup->num = round((float) $modelUsr->query_value($sqlByGroup), 2);
				} else {
					if (!empty($oUserGroup->playerCount)) {
						$oUserGroup->num = round((float) ($modelUsr->query_value($sqlByGroup) / $oUserGroup->playerCount), 2);
					} else {
						$oUserGroup->num = 0;
					}
				}
			} else {
				$oUserGroup->num = (int) $modelUsr->query_value($sqlByGroup);
			}
		}
		/* 对分组数据进行排序 */
		usort($userGroups, function ($a, $b) {
			return $a->num < $b->num ? 1 : -1;
		});

		$oResult = new \stdClass;
		$oResult->groups = $userGroups;

		return new \ResponseData($oResult);
	}
	/**
	 * 记录内容排行榜
	 */
	public function dataByApp_action($app, $page = 1, $size = 20) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		// 是否需要分组信息
		$oAssocGrpSchema = $this->model('matter\enroll\schema')->getAssocGroupSchema($oApp);
		if ($oAssocGrpSchema) {
			$aAssocGroups = [];
			foreach ($oAssocGrpSchema->ops as $op) {
				$aAssocGroups[$op->v] = $op->l;
			}
		}

		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			return new \ParameterError();
		}
		$modelData = $this->model('matter\enroll\data');

		$q = [
			'd.id,d.value,d.enroll_key,d.schema_id,d.agreed,a.headimgurl,d.multitext_seq',
			"xxt_enroll_record_data d left join xxt_site_account a on d.userid = a.uid and a.siteid = '{$oApp->siteid}'",
			"d.aid='{$oApp->id}' and d.state=1",
		];
		if (!empty($aAssocGroups)) {
			$q[0] .= ',d.group_id';
		}
		if (isset($oCriteria->agreed) && $oCriteria->agreed === 'Y') {
			$q[2] .= " and d.agreed='Y'";
		} else {
			$q[2] .= " and d.multitext_seq = 0";
		}
		if (!empty($oCriteria->round)) {
			if (is_string($oCriteria->round)) {
				$oCriteria->round = explode(',', $oCriteria->round);
			}
			if (!in_array('ALL', $oCriteria->round)) {
				$whereByRound = ' and rid in("';
				$whereByRound .= implode('","', $oCriteria->round);
				$whereByRound .= '")';
				$q[2] .= $whereByRound;
			}
		}
		switch ($oCriteria->orderby) {
		case 'remark':
			$q[0] .= ',d.remark_num';
			$q[2] .= ' and d.remark_num>0';
			$q2 = ['o' => 'd.remark_num desc,d.last_remark_at'];
			break;
		case 'like':
			$q[0] .= ',d.like_num';
			$q[2] .= ' and d.like_num>0';
			$q2 = ['o' => 'd.like_num desc,d.submit_at'];
			break;
		}
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$oResult = new \stdClass;
		$records = $modelData->query_objs_ss($q, $q2);
		if (count($records)) {
			// 题目类型
			$dataSchemas = new \stdClass;
			if (isset($oApp->dataSchemas)) {
				foreach ($oApp->dataSchemas as $dataSchema) {
					$dataSchemas->{$dataSchema->id} = $dataSchema;
				}
			}

			$modelRec = $this->model('matter\enroll\record');
			foreach ($records as $oRecord) {
				$oRec = $modelRec->byId($oRecord->enroll_key, ['fields' => 'nickname,supplement']);
				if ($oRec) {
					$oRecord->nickname = $oRec->nickname;
					if (isset($oRec->supplement->{$oRecord->schema_id})) {
						$oRecord->supplement = $oRec->supplement->{$oRecord->schema_id};
					}
				}
				if (!empty($aAssocGroups) && !empty($oRecord->group_id)) {
					$oRecord->group_title = isset($aAssocGroups[$oRecord->group_id]) ? $aAssocGroups[$oRecord->group_id] : '';
				}
				// 处理多项填写题
				if (isset($oRecord->schema_id) && isset($dataSchemas->{$oRecord->schema_id}) && $dataSchemas->{$oRecord->schema_id}->type === 'multitext' && $oRecord->multitext_seq == 0) {
					$oRecord->value = empty($oRecord->value) ? [] : json_decode($oRecord->value);
				}
			}
		}
		$oResult->records = $records;

		$q[0] = 'count(*)';
		$oResult->total = (int) $modelData->query_val_ss($q);

		return new \ResponseData($oResult);
	}
	/**
	 *
	 */
	public function remarkByApp_action($app, $page = 1, $size = 20) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		$modelRem = $this->model('matter\enroll\remark');

		$q = [
			'r.id,r.userid,r.nickname,r.content,r.enroll_key,r.schema_id,r.data_id,r.like_num,r.agreed,a.headimgurl',
			"xxt_enroll_record_remark r left join xxt_site_account a on r.userid = a.uid and a.siteid = '{$oApp->siteid}'",
			"r.aid='{$oApp->id}' and r.state=1 and r.like_num>0",
		];
		if (isset($oCriteria->agreed) && $oCriteria->agreed === 'Y') {
			$q[2] .= " and r.agreed='Y'";
		}
		if (!empty($oCriteria->round)) {
			if (is_string($oCriteria->round)) {
				$oCriteria->round = explode(',', $oCriteria->round);
			}
			if (!in_array('ALL', $oCriteria->round)) {
				$whereByRound = ' and rid in("';
				$whereByRound .= implode('","', $oCriteria->round);
				$whereByRound .= '")';
				$q[2] .= $whereByRound;
			}
		}
		$q2 = [
			'o' => 'r.like_num desc,r.create_at',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$oResult = new \stdClass;
		$remarks = $modelRem->query_objs_ss($q, $q2);
		if (count($remarks) && !empty($oApp->entryRule->group->id)) {
			$q = [
				'userid,round_id,round_title',
				'xxt_group_player',
				['aid' => $oApp->entryRule->group->id],
			];
			if ($userGroups = $modelRem->query_objs_ss($q)) {
				$userGroups2 = new \stdClass;
				foreach ($userGroups as $userGroup) {
					$userGroups2->{$userGroup->userid} = new \stdClass;
					$userGroups2->{$userGroup->userid}->round_id = $userGroup->round_id;
					$userGroups2->{$userGroup->userid}->round_title = $userGroup->round_title;
				}
				foreach ($remarks as $remark) {
					$remark->group = $userGroups2->{$remark->userid};
				}
			}
		}
		$oResult->remarks = $remarks;

		$q[0] = 'count(*)';
		$oResult->total = (int) $modelRem->query_val_ss($q);

		return new \ResponseData($oResult);
	}
}