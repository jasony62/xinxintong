<?php
namespace job\log\site\fe\matter;
/**
 * 调用model需要引用的文件
 */
require_once TMS_APP_DIR . '/tms/db.php';
require_once TMS_APP_DIR . '/tms/tms_model.php';
/**
 * 素材访问日志
 */
class access extends \TMS_MODEL {
	/**
	 * 执行任务
	 */
	public function perform() {
		$args2 = $this->args;
		$logid = $this->model('matter\log')->logRead($args2);
	}
}