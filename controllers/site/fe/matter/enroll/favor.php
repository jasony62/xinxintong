<?php
namespace site\fe\matter\enroll;

include_once dirname(__FILE__) . '/base.php';
/**
 * 登记记录收藏
 */
class favor extends base {
	/**
	 * 收藏填写记录
	 */
	public function add_action($ek) {
		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state,aid']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oApp = $this->model('matter\enroll')->byId($oRecord->aid, ['cascaded' => 'N']);
		if (false === $oApp || $oApp->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$oUser = $this->getUser($oApp);
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户进行收藏，请登录后再进行此操作');
		}

		$q = [
			'id',
			'xxt_enroll_record_favor',
			['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1],
		];
		$oFavorLog = $modelRec->query_obj_ss($q);
		if ($oFavorLog) {
			return new \ResponseError('记录已经收藏');
		}

		$oFavorLog = new \stdClass;
		$oFavorLog->aid = $oRecord->aid;
		$oFavorLog->siteid = $oApp->siteid;
		$oFavorLog->record_id = $oRecord->id;
		$oFavorLog->favor_unionid = $oUser->unionid;
		$oFavorLog->favor_at = time();
		$oFavorLog->id = $modelRec->insert('xxt_enroll_record_favor', $oFavorLog, true);

		$modelRec->update(
			'xxt_enroll_record',
			['favor_num' => (object) ['op' => '+=', 'pat' => 1]],
			['id' => $oRecord->id]
		);

		return new \ResponseData($oFavorLog);
	}
	/**
	 * 取消收藏填写记录
	 */
	public function remove_action($ek) {
		$oUser = $this->who;
		if (empty($oUser->unionid)) {
			return new \ResponseError('仅支持注册用户进行收藏，请登录后再进行此操作');
		}

		$modelRec = $this->model('matter\enroll\record');
		$oRecord = $modelRec->byId($ek, ['fields' => 'id,state']);
		if (false === $oRecord || $oRecord->state !== '1') {
			return new \ObjectNotFoundError();
		}

		$q = [
			'id,favor_unionid,record_id,state',
			'xxt_enroll_record_favor',
			['record_id' => $oRecord->id, 'favor_unionid' => $oUser->unionid, 'state' => 1],
		];
		$oFavorLog = $modelRec->query_obj_ss($q);
		if (false === $oFavorLog || $oFavorLog->state !== '1') {
			return new \ResponseError('收藏记录不存在');
		}

		$rst = $modelRec->update('xxt_enroll_record_favor', ['state' => 0], ['id' => $oFavorLog->id]);

		$modelRec->update(
			'xxt_enroll_record',
			['favor_num' => (object) ['op' => '-=', 'pat' => 1]],
			['id' => $oRecord->id]
		);

		return new \ResponseData($oFavorLog);
	}
}