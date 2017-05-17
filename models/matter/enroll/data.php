<?php
namespace matter\enroll;
/**
 * 登记的数据项
 */
class data_model extends \TMS_MODEL {
	/**
	 * 缺省返回的列
	 */
	const DEFAULT_FIELDS = 'id,value,enroll_key,schema_id,userid,submit_at,score,remark_num,last_remark_at,like_num,like_log,modify_log,agreed';
	/**
	 * 获得指定登记记录登记数据的详细信息
	 */
	public function byRecord($ek, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : self::DEFAULT_FIELDS;

		$q = [
			$fields,
			'xxt_enroll_record_data',
			['enroll_key' => $ek, 'state' => 1],
		];

		if (isset($options['schema'])) {
			$q[2]['schema_id'] = $options['schema'];
			$data = $this->query_obj_ss($q);

			return $data;
		} else {
			$result = new \stdClass;
			$data = $this->query_objs_ss($q);
			if (count($data)) {
				foreach ($data as $schemaData) {
					$schemaId = $schemaData->schema_id;
					unset($schemaData->schema_id);
					$result->{$schemaId} = $schemaData;
				}
			}

			return $result;
		}
	}
	/**
	 * 返回指定活动，指定登记项的填写数据
	 */
	public function bySchema(&$oApp, $oSchema, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
			$page = isset($options->page) ? $options->page : null;
			$size = isset($options->size) ? $options->size : null;
			$rid = isset($options->rid) ? $this->escape($options->rid) : null;
		}
		$result = new \stdClass; // 返回的结果

		// 查询参数
		$schemaId = $this->escape($oSchema->id);
		$q = [
			'distinct value',
			"xxt_enroll_record_data",
			"state=1 and aid='{$oApp->id}' and schema_id='{$schemaId}' and value<>''",
		];
		/* 限制填写轮次 */
		if (!empty($rid)) {
			if ($rid !== 'ALL') {
				$q[2] .= " and rid='{$rid}'";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and rid='{$activeRound->rid}'";
			}
		}
		/* 限制填写用户 */
		if (!empty($options->userid)) {
			$q[2] .= " and userid='{$options->userid}'";
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		// 处理获得的数据
		$result->records = $this->query_objs_ss($q, $q2);

		// 符合条件的数据总数
		$q[0] = 'count(distinct value)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
	/**
	 * 返回指定活动，填写的数据
	 */
	public function byApp(&$oApp, $oUser, $options = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
		}
		$fields = isset($options->fields) ? $options->fields : self::DEFAULT_FIELDS;
		$page = isset($options->page) ? $options->page : null;
		$size = isset($options->size) ? $options->size : null;
		$rid = isset($options->rid) ? $this->escape($options->rid) : null;

		$result = new \stdClass; // 返回的结果

		// 查询参数
		$q = [
			$fields,
			"xxt_enroll_record_data",
			"state=1 and aid='{$oApp->id}'",
		];
		if (empty($options->keyword)) {
			$q[2] .= " and value<>''";
		} else {
			$q[2] .= " and value like '%" . $options->keyword . "%'";
		}
		if (isset($options->schemas) && count($options->schemas)) {
			$q[2] .= " and schema_id in(";
			foreach ($options->schemas as $index => $schemaId) {
				if ($index > 0) {
					$q[2] .= ',';
				}
				$q[2] .= "'" . $this->escape($schemaId) . "'";
			}
			$q[2] .= ")";
		}
		/* 限制填写轮次 */
		if (!empty($rid)) {
			if (!strcasecmp($rid, 'all') === 0) {
				$q[2] .= " and rid='{$rid}'";
			}
		} else {
			/* 没有指定轮次，就使用当前轮次 */
			if ($activeRound = $this->model('matter\enroll\round')->getActive($oApp)) {
				$q[2] .= " and rid='{$activeRound->rid}'";
			}
		}
		/* 限制填写用户 */
		if (!empty($options->owner) && strcasecmp($options->owner, 'all') !== 0) {
			$q[2] .= " and userid='{$options->owner}'";
		} else if (!empty($oUser->uid)) {
			$q[2] .= " and (agreed<>'N' or userid='{$oUser->uid}')";
		} else {
			$q[2] .= " and agreed<>'N'";
		}

		$q2 = [];
		// 查询结果分页
		if (!empty($page) && !empty($size)) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}

		// 处理获得的数据
		$mapOfNicknames = [];
		$aRecords = $this->query_objs_ss($q, $q2);
		if (count($aRecords)) {
			$modelRec = $this->model('matter\enroll\record');
			foreach ($aRecords as &$oRecord) {
				/* 获得nickname */
				if (!isset($mapOfNicknames[$oRecord->userid])) {
					$rec = $modelRec->byId($oRecord->enroll_key, ['fields' => 'nickname']);
					$mapOfNicknames[$oRecord->userid] = $rec->nickname;
				}
				$oRecord->nickname = $mapOfNicknames[$oRecord->userid];
			}
		}
		$result->records = $aRecords;

		// 符合条件的数据总数
		$q[0] = 'count(*)';
		$total = (int) $this->query_val_ss($q, $q2);
		$result->total = $total;

		return $result;
	}
}