<?php
namespace site\op\matter\enroll;

require_once TMS_APP_DIR . '/controllers/site/op/base.php';
/*
 * 登记活动主控制器
 */
class data extends \site\op\base {
	/**
	 *
	 */
	public function agree_action($ek, $schema, $value = '') {
		if (!$this->checkAccessToken()) {
			return new \InvalidAccessToken();
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