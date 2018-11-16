<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录统计
 */
class stat extends base {
	/**
	 * 统计登记信息
	 *
	 * 只统计single/multiple类型的数据项
	 *
	 * @return
	 *		name => array(l=>label,c=>count)
	 *
	 */
	public function get_action($site, $app, $rid = null, $renewCache = 'Y') {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$stat = $this->model('matter\enroll\record')->getStat($oApp, $rid, $renewCache);

		return new \ResponseData($stat);
	}
}