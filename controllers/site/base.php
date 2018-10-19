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
	 * 客户端应用名称
	 */
	protected function &userAgent() {
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			if (preg_match('/yixin/i', $user_agent)) {
				$ca = 'yx';
			} elseif (preg_match('/MicroMessenger/i', $user_agent)) {
				$ca = 'wx';
			} else {
				$ca = false;
			}
		} else {
			$ca = false;
		}

		return $ca;
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