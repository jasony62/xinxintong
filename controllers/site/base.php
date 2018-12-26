<?php
namespace site;
/**
 * 站点访问控制器基类
 */
class base extends \TMS_CONTROLLER {
	/**
	 *
	 */
	public function get_access_rule() {
		$ruleAction = [
			'rule_type' => 'black',
		];

		return $ruleAction;
	}
	/**
	 *
	 */
	protected function outputError($err, $title = '程序错误') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $err);
		\TPL::output('error');
		exit;
	}
	/**
	 *
	 */
	protected function outputInfo($info, $oSite = null, $title = '提示') {
		\TPL::assign('title', $title);
		\TPL::assign('body', $info);
		if (isset($oSite->id)) {
			\TPL::assign('site', $oSite);
		} else {
			\TPL::assign('site', (object) ['id' => 'platform']);
		}
		\TPL::output('info');
		exit;
	}
}