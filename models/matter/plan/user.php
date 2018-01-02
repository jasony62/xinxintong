<?php
namespace matter\plan;
/**
 *
 */
class user_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byUser($oUser, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_user',
			['userid' => $oUser->uid],
		];
		$oAppUsr = $this->query_obj_ss($q);

		return $oAppUsr;
	}
	/**
	 * 添加或更新
	 */
	public function createOrUpdate($oApp, $oUser, $aData = []) {
		$oAppUsr = $this->byUser($oUser);

		$oNewAppUsr = new \stdClass;
		if ($oAppUsr) {
			$oNewAppUsr->nickname = $this->escape($oUser->nickname);
			$oAppUsr->nickname = $oUser->nickname;
			$oAppUsr->group_id = $oNewAppUsr->group_id = empty($oUser->group_id) ? '' : $oUser->group_id;
			if (isset($aData['task_num'])) {
				$oAppUsr->task_num = $oNewAppUsr->task_num = (int) $aData['task_num'] + (int) $oAppUsr->task_num;
			}
			if (isset($aData['last_enroll_at'])) {
				$oAppUsr->last_enroll_at = $oNewAppUsr->last_enroll_at = $aData['last_enroll_at'];
			}
			if (isset($aData['score'])) {
				$oAppUsr->score = $oNewAppUsr->score = $aData['score'];
			}
			$this->update('xxt_plan_user', $oNewAppUsr, ['id' => $oAppUsr->id]);
			$oNewAppUsr = $oAppUsr;
		} else {
			$oNewAppUsr->siteid = $oApp->siteid;
			$oNewAppUsr->aid = $oApp->id;
			$oNewAppUsr->group_id = '';
			$oNewAppUsr->userid = $oUser->uid;
			$oNewAppUsr->group_id = empty($oUser->group_id) ? '' : $oUser->group_id;
			$oNewAppUsr->nickname = $this->escape($oUser->nickname);
			$oNewAppUsr->start_at = time();
			$oNewAppUsr->task_num = isset($aData['task_num']) ? (int) $aData['task_num'] : 0;
			$oNewAppUsr->last_enroll_at = isset($aData['last_enroll_at']) ? $aData['last_enroll_at'] : 0;
			$oNewAppUsr->score = isset($aData['score']) ? $aData['score'] : 0;

			$oNewAppUsr->id = $this->insert('xxt_plan_user', $oNewAppUsr, true);
		}

		return $oNewAppUsr;
	}
}