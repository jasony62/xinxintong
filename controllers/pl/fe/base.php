<?php
namespace pl\fe;
/**
 *
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$rule_action['rule_type'] = 'white';
		$rule_action['actions'][] = 'hello';

		return $rule_action;
	}
	/**
	 * 二维码
	 */
	public function qrcode_action($url) {
		include TMS_APP_DIR . '/lib/qrcode/qrlib.php';
		// outputs image directly into browser, as PNG stream
		\QRcode::png($url);
	}
}