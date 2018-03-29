<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户动态
 */
class event extends base {
	/**
	 * 列出指定活动中和当前用户相关的事件
	 */
	public function timeline_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$modelEvt = $this->model('matter\enroll\event');

		$fields = 'rid,enroll_key,event_at,event_name,event_op,group_id,userid,nickname,earn_coin,owner_userid,owner_nickname,owner_earn_coin,target_id,target_type';
		$oResult = $modelEvt->logByApp($oApp, ['fields' => $fields]);

		return new \ResponseData($oResult);
	}
}