<?php
namespace pl\fe\matter\enroll;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 登记数据
 */
class data extends \pl\fe\matter\base {
	/**
	 *
	 */
	public function agree_action($ek, $schema, $value = '') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelData = $this->model('matter\enroll\data');
		if (!in_array($value, ['Y', 'N', 'A'])) {
			$value = '';
		}

		$rst = $modelData->update(
			'xxt_enroll_record_data',
			['agreed' => $value],
			['enroll_key' => $ek, 'schema_id' => $schema, 'state' => 1]
		);

		return new \ResponseData($rst);
	}
}