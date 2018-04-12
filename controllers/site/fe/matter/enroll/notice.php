<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记活动用户提醒
 */
class notice extends base {
	/**
	 * 用户提醒列表
	 */
	public function list_action($app, $page = 1, $size = 30) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$fields = 'id,event_name,event_op,event_at,event_userid,event_nickname,notice_reason,rid,enroll_key,event_target_id
,event_target_type';
		$oOptions['fields'] = $fields;
		$oOptions['user'] = $oUser;
		$oOptions['page'] = (object) ['at' => $page, 'size' => $size];

		$oResult = $this->model('matter\enroll\notice')->byApp($oApp, $oOptions);

		return new \ResponseData($oResult);
	}
	/**
	 * 用户待处理提醒数量
	 */
	public function count_action($app) {
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if ($oApp === false || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->getUser($oApp);

		$q = [
			'count(*)',
			'xxt_enroll_notice',
			['aid' => $oApp->id, 'state' => 1, 'userid' => $oUser->uid],
		];
		$total = (int) $modelApp->query_val_ss($q);

		return new \ResponseData($total);
	}
	/**
	 * 关闭通知
	 */
	public function close_action($notice) {
		$modelNotice = $this->model('matter\enroll\notice');
		$oNotice = $modelNotice->byId($notice, 'id,state,userid');
		if (false === $oNotice) {
			return new \ObjectNotFoundError();
		}
		$oUser = $this->who;
		if ($oUser->uid !== $oNotice->userid) {
			return new \ResponseError('没有关闭通知的权限');
		}
		$rst = $modelNotice->update(
			'xxt_enroll_notice',
			['state' => 0],
			['id' => $oNotice->id]
		);

		return new \ResponseData($rst);
	}
}