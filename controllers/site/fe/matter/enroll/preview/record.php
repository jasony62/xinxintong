<?php
namespace site\fe\matter\enroll\preview;
/**
 * 登记活动预览
 */
class record extends \TMS_CONTROLLER {
	/**
	 * 返回登记记录
	 */
	public function get_action() {
		return new \ResponseData('ok');
	}
}