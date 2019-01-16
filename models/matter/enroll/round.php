<?php
namespace matter\enroll;

require_once dirname(dirname(__FILE__)) . '/round_base.php';

class round_model extends \TMS_MODEL {
	use \matter\Round;
	/**
	 *
	 */
	public function byId($rid, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_round',
			['rid' => $rid],
		];
		$oRound = $this->query_obj_ss($q);

		return $oRound;
	}
	/**
	 * 和指定项目轮次绑定的轮次
	 */
	public function byMissionRid($oApp, $missionRid, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$state = isset($aOptions['state']) ? $aOptions['state'] : false;
		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id, 'mission_rid' => $missionRid],
		];
		$state && $q[2]['state'] = $state;

		$oRound = $this->query_obj_ss($q);

		return $oRound;
	}
	/**
	 * 返回记录活动下的轮次
	 *
	 * @param object $oApp
	 *
	 */
	public function byApp($oApp, $aOptions = []) {
		if (!isset($oApp->sync_mission_round)) {
			throw new \ParameterError('没有提供活动轮次设置的完整信息（1）');
		}

		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$state = isset($aOptions['state']) ? $aOptions['state'] : false;
		$oPage = isset($aOptions['page']) ? $aOptions['page'] : null;

		$oResult = new \stdClass; // 返回的结果

		/* 当前激活轮次 */
		if (!isset($aOptions['withoutActive']) || $aOptions['withoutActive'] !== 'Y') {
			$oResult->active = $this->getActive($oApp, ['fields' => $fields]);
		}
		/* 活动下已有的所有轮次 */
		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id],
		];
		$state && $q[2]['state'] = $state;
		/* 开始时间 */
		if (isset($aOptions['start_at'])) {
			$q[2]['start_at'] = $aOptions['start_at'];
		}
		/* 开始时间 */
		if (isset($aOptions['end_at'])) {
			$q[2]['end_at'] = $aOptions['end_at'];
		}
		/* 轮次用途 */
		if (isset($aOptions['purpose'])) {
			$q[2]['purpose'] = $aOptions['purpose'];
		}

		$q2 = ['o' => 'create_at desc'];
		if (isset($oPage->at) && isset($oPage->size)) {
			$q2['r'] = ['o' => ($oPage->at - 1) * $oPage->size, 'l' => $oPage->size];
		}
		$oResult->rounds = $this->query_objs_ss($q, $q2);

		if (!empty($oPage)) {
			$q[0] = 'count(*)';
			$oResult->total = (int) $this->query_val_ss($q);
		}

		return $oResult;
	}
	/**
	 * 活动下填写时段的数量
	 */
	public function countByApp($oApp, $aOptions = []) {
		$state = isset($aOptions['state']) ? $aOptions['state'] : false;
		$q = [
			'count(*)',
			'xxt_enroll_round',
			['aid' => $oApp->id],
		];
		$state && $q[2]['state'] = $state;

		$count = (int) $this->query_val_ss($q);

		return $count;
	}
	/**
	 * 添加轮次
	 *
	 * @param object $oApp
	 * @param object $props
	 * @param object $oCreator
	 */
	public function create($oApp, $oProps, $oCreator = null, $bForceStopActive = false) {
		// 结束数据库读写分离带来的问题
		$this->setOnlyWriteDbConn(true);

		/* 只允许有一个指定启动轮次 */
		if (isset($oProps->state) && (int) $oProps->state === 1 && isset($oProps->start_at) && (int) $oProps->start_at === 0) {
			if ($oLastRound = $this->getAssignedActive($oApp)) {
				if ($bForceStopActive) {
					$this->update('xxt_enroll_round', ['state' => 2], ['rid' => $oLastRound->rid]);
				} else {
					return [false, '请先停止轮次【' . $oLastRound->title . '】'];
				}
			}
		}
		$roundId = uniqid();
		$aNewRound = [
			'siteid' => $oApp->siteid,
			'aid' => $oApp->id,
			'rid' => $roundId,
			'mission_rid' => empty($oProps->mission_rid) ? '' : $oProps->mission_rid,
			'creator' => isset($oCreator->id) ? $oCreator->id : '',
			'create_at' => time(),
			'title' => empty($oProps->title) ? '' : $this->escape($oProps->title),
			'state' => isset($oProps->state) ? $oProps->state : 0,
			'start_at' => empty($oProps->start_at) ? 0 : $oProps->start_at,
			'end_at' => empty($oProps->end_at) ? 0 : $oProps->end_at,
			'purpose' => empty($oProps->purpose) ? 'C' : (in_array($oProps->purpose, ['C', 'B', 'S']) ? $oProps->purpose : 'C'),
		];
		$this->insert('xxt_enroll_round', $aNewRound, false);

		$oRound = $this->byId($roundId);

		return [true, $oRound];
	}
	/**
	 * 获得指定记录活动的当前轮次（开始时间为0），必须是常规轮次
	 *
	 * @param object $oApp
	 *
	 */
	public function getAssignedActive($oApp, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id, 'purpose' => 'C', 'start_at' => 0, 'end_at' => 0, 'state' => 1],
		];
		$oRound = $this->query_obj_ss($q);

		return $oRound;
	}
	/**
	 * 获得指定记录活动中启用状态的填写轮次
	 *
	 * 没有指定开始和结束时间，且状态为启用状态的轮次优先
	 * 如果记录活动设置了轮次定时生成规则，需要检查是否需要自动生成轮次
	 *
	 * 活跃轮次只能是常规轮次
	 *
	 * @param object $app
	 *
	 */
	public function getActive($oApp, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

		$aRequireAppFields = []; // 应用必须包含的字段
		if (!isset($oApp->sync_mission_round)) {
			$aRequireAppFields[] = 'sync_mission_round';
		}
		if (!isset($oApp->mission_id)) {
			$aRequireAppFields[] = 'mission_id';
		}
		if (!empty($aRequireAppFields)) {
			$oApp2 = $this->model('matter\enroll')->byId($oApp->id, ['fields' => implode(',', $aRequireAppFields), 'notDecode' => true]);
			foreach ($oApp2 as $k => $v) {
				$oApp->{$k} = $v;
			}
		}

		if ($oApp->sync_mission_round === 'Y') {
			/* 根据项目的轮次规则生成轮次 */
			if (empty($oApp->mission_id)) {
				throw new \Exception('没有提供活动所属项目的信息');
			}
			$oMission = $this->model('matter\mission')->byId($oApp->mission_id, ['fields' => 'id,siteid,round_cron']);
			$oMisRound = $this->model('matter\mission\round')->getActive($oMission, ['fields' => 'id,rid,title,start_at,end_at']);
			if ($oMisRound) {
				$oAppRound = $this->byMissionRid($oApp, $oMisRound->rid, ['state' => 1, 'fields' => $fields]);
				if (false === $oAppRound) {
					/* 创建和项目轮次绑定的轮次 */
					$oNewRound = new \stdClass;
					$oNewRound->title = $oMisRound->title;
					$oNewRound->start_at = $oMisRound->start_at;
					$oNewRound->end_at = $oMisRound->end_at;
					$oNewRound->state = 1;
					$oNewRound->mission_rid = $oMisRound->rid;
					$oResult = $this->create($oApp, $oNewRound, null, true);
					if (false === $oResult[0]) {
						throw new \Exception($oResult[1]);
					}
					$oAppRound = $oResult[1];
				}
				return $oAppRound;
			}
		}

		/* 已经存在的，用户指定的当前轮次 */
		if ($oAppRound = $this->getAssignedActive($oApp, $aOptions)) {
			return $oAppRound;
		}
		/* 根据活动的轮次规则生成轮次 */
		if (!empty($oApp->roundCron)) {
			/* 有效的定时规则 */
			$enabledRules = array_filter($oApp->roundCron, function ($oRule) {return $this->getDeepValue($oRule, 'enabled') === 'Y' && $this->getDeepValue($oRule, 'purpose') === 'C';});
		}
		if (empty($enabledRules)) {
			/* 根据轮次开始时间获得轮次，但是必须是常规轮次 */
			$current = time();
			$q = [
				$fields,
				'xxt_enroll_round',
				['aid' => $oApp->id, 'state' => 1, 'purpose' => 'C', 'start_at' => (object) ['op' => '<=', 'pat' => $current]],
			];
			$q2 = [
				'o' => 'start_at desc',
				'r' => ['o' => 0, 'l' => 1],
			];
			$rounds = $this->query_objs_ss($q, $q2);
			$oAppRound = count($rounds) === 1 ? $rounds[0] : false;
		} else {
			/* 根据定时规则获得轮次 */
			$rst = $this->_getRoundByCron($oApp, $enabledRules, $aOptions);
			if (false === $rst[0]) {
				return false;
			}
			$oAppRound = $rst[1];
		}

		return $oAppRound;
	}
	/**
	 * 根据活动的定时规则生成轮次
	 */
	public function byCron($oApp, $purpose, $startAt = 0) {
		if (empty($oApp->roundCron)) {
			return false;
		}
		if (!in_array($purpose, ['S', 'B', 'C'])) {
			return false;
		}
		$aCron = $oApp->roundCron;

		/* 有效的定时规则 */
		$enabledRules = array_filter($aCron, function ($oRule) use ($purpose) {return $this->getDeepValue($oRule, 'enabled') === 'Y' && $this->getDeepValue($oRule, 'purpose') === $purpose;});
		if (empty($enabledRules)) {
			return false;
		}

		$rounds = [];
		switch ($purpose) {
		case 'S':
		case 'B':
			/* 根据定时规则获得轮次，可能同时存在多个匹配的汇总轮次 */
			$aCronOptions = [];
			if ($startAt > 0) {
				$aCronOptions['timestamp'] = $startAt;
			}
			foreach ($enabledRules as $oRule) {
				$rounds[] = $this->_getRoundByCron($oApp, [$oRule], $aCronOptions);
			}
			break;
		case 'C':
			break;
		}

		return $rounds;
	}
	/**
	 * 获得指定活动中的目标轮次
	 */
	public function getBaseline($oApp, $aOptions = []) {
		if (!empty($aOptions['assignedRid'])) {
			$oAssignedRnd = $this->byId($aOptions['assignedRid'], ['fields' => 'start_at']);
		}
		$this->byCron($oApp, 'B', empty($oAssignedRnd->start_at) ? 0 : $oAssignedRnd->start_at);

		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id, 'state' => 1, 'purpose' => 'B'],
		];
		if (!empty($oAssignedRnd->start_at)) {
			$q[2]['start_at'] = (object) ['op' => '<=', 'pat' => $oAssignedRnd->start_at];
		}
		$q2 = [
			'o' => 'start_at desc',
			'r' => ['o' => 0, 'l' => 1],
		];
		$rounds = $this->query_objs_ss($q, $q2);

		return count($rounds) === 1 ? $rounds[0] : false;
	}
	/**
	 * 获得指定活动中的汇总轮次
	 */
	public function getSummary($oApp, $rndStartAt = 0, $aOptions = []) {
		/* 根据活动的轮次规则生成汇总轮次 */
		$this->byCron($oApp, 'S', $rndStartAt);

		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';

		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id, 'state' => 1, 'purpose' => 'S'],
		];
		$q[2]['start_at'] = (object) ['op' => '<=', 'pat' => $rndStartAt];
		$q[2]['end_at'] = (object) ['op' => 'or', 'pat' => ['end_at=0', 'end_at>=' . $rndStartAt]];

		$q2 = ['o' => 'start_at desc'];
		$rounds = $this->query_objs_ss($q, $q2);
		if (count($rounds) === 0) {
			return false;
		}
		/* 汇总轮次包含的轮次 */
		if (!isset($aOptions['includeRounds']) || $aOptions['includeRounds'] !== 'N') {
			foreach ($rounds as $oSumRnd) {
				/* 覆盖哪些常规轮次。如果没有指定，就认为汇总所有轮次的数据 */
				$sumStartAt = $this->getDeepValue($oSumRnd, 'start_at', 0);
				$sumEndEndAt = $this->getDeepValue($oSumRnd, 'end_at', 0);
				if ($sumStartAt > 0 || $sumEndEndAt > 0) {
					/* 和汇总轮次关联的填写轮次 */
					$oSumRnd->includeRounds = $this->getSummaryInclude($oApp, $sumStartAt, $sumEndEndAt);
				}
			}
		}

		return $rounds;
	}
	/**
	 * 根据指定的开始和停止时间获得被汇总的填写轮次
	 */
	public function getSummaryInclude($oApp, $sumStartAt, $sumEndEndAt) {
		$q = [
			'rid,start_at',
			'xxt_enroll_round',
			['aid' => $oApp->id, 'state' => 1, 'purpose' => 'C'],
		];
		if ($sumStartAt > 0 && $sumEndEndAt > 0) {
			$q[2]['start_at'] = (object) ['op' => 'between', 'pat' => [$sumStartAt, $sumEndEndAt]];
		} else if ($sumStartAt > 0) {
			$q[2]['start_at'] = (object) ['op' => '>=', 'pat' => $sumStartAt];
		} else if ($sumEndEndAt > 0) {
			$q[2]['start_at'] = (object) ['op' => '<=', 'pat' => $sumEndEndAt];
		}

		$includeRounds = $this->query_objs_ss($q);

		return $includeRounds;
	}
	/**
	 * 根据定时规则生成轮次
	 *
	 * @param array $rules 定时生成轮次规则
	 *
	 */
	private function _getRoundByCron($oApp, $rules, $aOptions) {
		if (false === ($oCronRound = $this->_lastRoundByCron($rules, empty($aOptions['timestamp']) ? null : (int) $aOptions['timestamp']))) {
			return [false, '无法生成定时轮次'];
		}

		$oRound = false; // 和定时计划匹配的论次

		/* 检查已经存在的轮次是否满足定时规则 */
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$q = [
			$fields,
			'xxt_enroll_round',
			['aid' => $oApp->id, 'state' => 1, 'start_at' => $oCronRound->start_at],
		];
		$rounds = $this->query_objs_ss($q);
		if (count($rounds) > 1) {
			return [false, '轮次数据错误，同一个开始时间有多个轮次[' . date('y年n月d日 H:i', $oCronRound->start_at) . ']'];
		}
		if (count($rounds) === 1) {
			/* 找到匹配的轮次 */
			$oRound = $rounds[0];
		} else {
			/* 创建新论次 */
			$rst = $this->create($oApp, $oCronRound);
			if (false === $rst[0]) {
				return $rst;
			}
			$oRound = $rst[1];
		}

		return [true, $oRound];
	}
	/**
	 * 删除轮次
	 */
	public function remove($oApp, $oRound) {
		/* 删除轮次下的记录 */
		$modelRec = $this->model('matter\enroll\record');
		$records = $modelRec->byRound($oRound->rid);
		if (count($records)) {
			foreach ($records as $oRecord) {
				$modelRec->remove($oApp, $oRecord);
			}
			/* 打标记 */
			$rst = $this->update(
				'xxt_enroll_round',
				['state' => 100],
				['aid' => $oApp->id, 'rid' => $oRound->rid]
			);
		} else {
			/* 删除 */
			$rst = $this->delete(
				'xxt_enroll_round',
				['aid' => $oApp->id, 'rid' => $oRound->rid]
			);
		}

		return $rst;
	}
	/**
	 * 记录轮次下创建的记录
	 *
	 * 1条记录可以属于多个轮次
	 */
	public function createRecord($oRecord) {
		$oRecordRound = new \stdClass;
		$oRecordRound->siteid = $oRecord->siteid;
		$oRecordRound->aid = $oRecord->aid;
		$oRecordRound->rid = $oRecord->rid;
		$oRecordRound->enroll_key = $oRecord->enroll_key;
		$oRecordRound->userid = isset($oRecord->userid) ? $oRecord->userid : '';
		$oRecordRound->add_at = isset($oRecord->first_enroll_at) ? $oRecord->first_enroll_at : current();
		$oRecordRound->add_cause = 'C';

		$oRecordRound->id = $this->insert('xxt_enroll_record_round', $oRecordRound, false);

		return $oRecordRound;
	}
	/**
	 * 修改记录
	 */
	public function reviseRecord($oRound, $oRecord) {
		if ($oRound->rid === $oRecord->rid) {
			return [false, '记录已经在轮次中'];
		}
		$q = [
			'count(*)',
			'xxt_enroll_record_round',
			['rid' => $oRound->rid, 'enroll_key' => $oRecord->enroll_key],
		];
		if ((int) $this->query_val_ss($q) > 0) {
			return [false, '修改记录已经在轮次中'];
		}

		$oRecordRound = new \stdClass;
		$oRecordRound->siteid = $oRecord->siteid;
		$oRecordRound->aid = $oRecord->aid;
		$oRecordRound->rid = $oRound->rid;
		$oRecordRound->enroll_key = $oRecord->enroll_key;
		$oRecordRound->userid = isset($oRecord->userid) ? $oRecord->userid : '';
		$oRecordRound->add_at = isset($oRecord->enroll_at) ? $oRecord->enroll_at : current();
		$oRecordRound->add_cause = 'R';

		$oRecordRound->id = $this->insert('xxt_enroll_record_round', $oRecordRound, false);

		return [true, $oRecordRound];
	}
}