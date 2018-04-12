<?php
require_once '../../db.php';
//
$sqls = [];
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add remark_num int not null default 0 after remark_id";
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add seq_in_record int not null default 0 after id";
$sqls[] = "ALTER TABLE xxt_enroll_record_remark add seq_in_data int not null default 0 after seq_in_record";
//
$sqls[] = "ALTER TABLE xxt_enroll_record add rec_remark_num int not null default 0 after remark_num";
//
foreach ($sqls as $sql) {
	if (!$mysqli->query($sql)) {
		header('HTTP/1.0 500 Internal Server Error');
		echo 'database error: ' . $mysqli->error;
	}
}
//
require_once '../../config.php';
require_once '../../db.php';
require_once '../../tms/db.php';
require_once '../../tms/tms_model.php';

//update `xxt_enroll_record` set rec_remark_num=0
//update `xxt_enroll_record_remark` set seq_in_record=0,seq_in_data=0

$model = TMS_MODEL::model();
/**
 * 从数据库中获得留言数据
 */
$remarks = $model->query_objs_ss(['id,state,enroll_key,data_id,remark_id', 'xxt_enroll_record_remark', ['1' => 1]]);
/**
 * 处理获得的数据
 */
foreach ($remarks as $oRemark) {
	/* seq in record */
	$seq = (int) $model->query_val_ss([
		'max(seq_in_record)',
		'xxt_enroll_record_remark',
		['enroll_key' => $oRemark->enroll_key],
	]);
	$model->update('xxt_enroll_record_remark', ['seq_in_record' => $seq + 1], ['id' => $oRemark->id]);
	/* seq in data */
	if (empty($oRemark->data_id) && empty($oRemark->remark_id)) {
		if ($oRemark->state === '1') {
			$model->update('xxt_enroll_record', ['rec_remark_num' => (object) ['op' => '+=', 'pat' => 1]], ['enroll_key' => $oRemark->enroll_key]);
		}
	} else if (!empty($oRemark->data_id)) {
		$seq = (int) $model->query_val_ss([
			'max(seq_in_data)',
			'xxt_enroll_record_remark',
			['data_id' => $oRemark->data_id],
		]);
		$model->update('xxt_enroll_record_remark', ['seq_in_data' => $seq + 1], ['id' => $oRemark->id]);
	}
}

echo "end update " . __FILE__ . PHP_EOL;