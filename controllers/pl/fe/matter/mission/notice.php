<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 用户发送通知日志
 */
class notice extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 查看通知发送日志
	 *
	 * @param int $batch 通知批次id
	 */
	public function logList_action($batch, $onlySns = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelTmplBat = $this->model('matter\tmplmsg\batch');
		$batch = $modelTmplBat->escape($batch);
		$q = [
			'de.*,a.nickname',
			'xxt_log_tmplmsg_detail de,xxt_site_account a',
			"de.batch_id = $batch and a.siteid = de.siteid and a.uid = de.userid",
		];
		if ($onlySns === 'Y') {
			$q[2] .= " and a.openid<>''";
		}

		$logs = $modelTmplBat->query_objs_ss($q);
		$oResult = new \stdClass;
		$oResult->logs = $logs;

		return new \ResponseData($oResult);
	}
}