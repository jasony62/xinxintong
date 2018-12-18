<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 记录活动投票结果
 */
class votes extends base {
	/**
	 * 统计登记信息
	 *
	 * 只统计single/multiple类型的数据项
	 *
	 *
	 */
	public function get_action($app, $rid = '', $renewCache = 'Y') {
		$oApp = $this->model('matter\enroll')->byId($app, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$aResult = $this->model('matter\enroll\record')->getStat($oApp, $rid, $renewCache);

		$aOrderedResult = [];
		foreach ($oApp->dynaDataSchemas as $oSchema) {
			if (isset($aResult[$oSchema->id])) {
				$aOrderedResult[] = $aResult[$oSchema->id];
			}
		}

		return new \ResponseData($aOrderedResult);
	}
}