<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动排行榜
 */
class rank extends base {
	/**
	 *
	 */
	public function userByApp_action($app, $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			return new \ParameterError();
		}
		$q = [
			'userid,nickname',
			'xxt_enroll_user',
			"aid='{$oApp->id}' and rid = 'ALL'",
		];

		switch ($oCriteria->orderby) {
		case 'enroll':
			$q[0] .= ',enroll_num';
			$q[2] .= ' and enroll_num>0';
			$q2 = ['o' => 'enroll_num desc,last_enroll_at'];
			break;
		case 'remark':
			$q[0] .= ',remark_num';
			$q[2] .= ' and remark_num>0';
			$q2 = ['o' => 'remark_num desc,last_remark_at'];
			break;
		case 'like':
			$q[0] .= ',like_num';
			$q[2] .= ' and like_num>0';
			$q2 = ['o' => 'like_num desc,last_like_at'];
			break;
		case 'remark_other':
			$q[0] .= ',remark_other_num';
			$q[2] .= ' and remark_other_num>0';
			$q2 = ['o' => 'remark_other_num desc,last_remark_other_at'];
			break;
		case 'like_other':
			$q[0] .= ',like_other_num';
			$q[2] .= ' and like_other_num>0';
			$q2 = ['o' => 'like_other_num desc,last_like_other_at'];
			break;
		case 'total_coin':
			$q[0] .= ',user_total_coin';
			$q[2] .= ' and user_total_coin>0';
			$q2 = ['o' => 'user_total_coin desc,id'];
			break;
		}
		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];

		$modelUsr = $this->model('matter\enroll\user');
		$result = new \stdClass;
		$users = $modelUsr->query_objs_ss($q, $q2);
		$result->users = $users;

		$q[0] = 'count(*)';
		$result->total = (int) $modelUsr->query_val_ss($q);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function groupByApp_action($app) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		foreach ($oApp->dataSchemas as $oSchema) {
			if ($oSchema->id === '_round_id') {
				$userGroups = $oSchema->ops;
			}
		}
		if (empty($userGroups)) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			return new \ParameterError();
		}

		/* 获取分组的数据 */
		$modelUsr = $this->model('matter\enroll\user');
		foreach ($userGroups as &$userGroup) {
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
				$sql .= 'sum(remark_other_num)';
				break;
			case 'like_other':
				$sql .= 'sum(like_other_num)';
				break;
			case 'total_coin':
				$sql .= 'sum(user_total_coin)';
				break;
			}
			$sql .= ' from xxt_enroll_user where aid=\'' . $oApp->id . '\' and rid=\'ALL\'';
			$sql .= ' and group_id=\'' . $userGroup->v . '\'';
			$userGroup->id = $userGroup->v;
			$userGroup->title = $userGroup->l;
			unset($userGroup->v);
			unset($userGroup->l);
			$userGroup->num = (int) $modelUsr->query_value($sql);
		}
		/* 对分组数据进行排讯 */
		usort($userGroups, function ($a, $b) {
			return $b->num - $a->num;
		});

		$result = new \stdClass;
		$result->groups = $userGroups;

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function dataByApp_action($app, $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}
		if (!empty($oApp->group_app_id)) {
			foreach ($oApp->dataSchemas as $oSchema) {
				if ($oSchema->id === '_round_id') {
					$aAssocGroups = [];
					foreach ($oSchema->ops as $op) {
						$aAssocGroups[$op->v] = $op->l;
					}
					break;
				}
			}
		}
		$oCriteria = $this->getPostJson();
		if (empty($oCriteria->orderby)) {
			return new \ParameterError();
		}

		$q = [
			'value,enroll_key,schema_id,agreed',
			'xxt_enroll_record_data',
			"aid='{$oApp->id}'",
		];
		if (!empty($aAssocGroups)) {
			$q[0] .= ',group_id';
		}
		if (isset($oCriteria->agreed) && $oCriteria->agreed === 'Y') {
			$q[2] .= " and agreed='Y'";
		}
		switch ($oCriteria->orderby) {
		case 'remark':
			$q[0] .= ',remark_num';
			$q[2] .= ' and remark_num>0';
			$q2 = ['o' => 'remark_num desc,last_remark_at'];
			break;
		case 'like':
			$q[0] .= ',like_num';
			$q[2] .= ' and like_num>0';
			$q2 = ['o' => 'like_num desc,submit_at'];
			break;
		}

		$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		$modelData = $this->model('matter\enroll\data');
		$result = new \stdClass;
		$records = $modelData->query_objs_ss($q, $q2);
		if (count($records)) {
			$modelRec = $this->model('matter\enroll\record');
			foreach ($records as &$record) {
				$oRec = $modelRec->byId($record->enroll_key, ['fields' => 'nickname,supplement']);
				if ($oRec) {
					$record->nickname = $oRec->nickname;
					if (isset($oRec->supplement->{$record->schema_id})) {
						$record->supplement = $oRec->supplement->{$record->schema_id};
					}
				}
				if (!empty($aAssocGroups) && !empty($record->group_id)) {
					$record->group_title = isset($aAssocGroups[$record->group_id]) ? $aAssocGroups[$record->group_id] : '';
				}
			}
		}
		$result->records = $records;

		$q[0] = 'count(*)';
		$result->total = (int) $modelData->query_val_ss($q);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function remarkByApp_action($app, $page = 1, $size = 10) {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}

		$oCriteria = $this->getPostJson();

		$q = [
			'id,userid,nickname,content,enroll_key,schema_id,like_num,agreed',
			'xxt_enroll_record_remark',
			"aid='{$oApp->id}' and like_num>0",
		];
		if (isset($oCriteria->agreed) && $oCriteria->agreed === 'Y') {
			$q[2] .= " and agreed='Y'";
		}
		$q2 = [
			'o' => 'like_num desc,create_at',
			'r' => ['o' => ($page - 1) * $size, 'l' => $size],
		];

		$modelRem = $this->model('matter\enroll\remark');
		$result = new \stdClass;
		$remarks = $modelRem->query_objs_ss($q, $q2);
		$result->remarks = $remarks;

		$q[0] = 'count(*)';
		$result->total = (int) $modelRem->query_val_ss($q);

		return new \ResponseData($result);
	}
}