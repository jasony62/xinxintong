<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记行为追踪
 */
class analysis extends base {
	/**
	 *
	 */
	public function submit_action($app, $page, $record = null) {
		$oPosted = $this->getPostJson();

		return new \ResponseData('ok');
	}
}