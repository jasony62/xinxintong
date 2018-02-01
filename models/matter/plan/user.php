<?php
namespace matter\plan;
/**
 *
 */
class user_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_user',
			['id' => $id],
		];
		$oAppUsr = $this->query_obj_ss($q);

		return $oAppUsr;
	}
	/**
	 *
	 */
	public function byUser($oApp, $oUser, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_user',
			['aid' => $oApp->id, 'userid' => $oUser->uid],
		];
		$oAppUsr = $this->query_obj_ss($q);

		return $oAppUsr;
	}
	/**
	 *
	 */
	public function byApp($oApp, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_plan_user',
			"aid = '{$oApp->id}'",
		];
		if (!empty($aOptions['onlyEnrolled']) && $aOptions['onlyEnrolled'] === 'Y') {
			$q[2] .= " and task_num > 0";
		}

		$q2 = [];
		if (isset($aOptions['paging'])) {
			$q2['r'] = [];
			$q2['r']['o'] = ($aOptions['paging']['page'] - 1) * $aOptions['paging']['size'];
			$q2['r']['l'] = $aOptions['paging']['size'];
		}
		$oAppUsrs = $this->query_objs_ss($q, $q2);
		
		foreach ($oAppUsrs as $oUser) {
			$p = [
				'wx_openid,yx_openid,qy_openid',
				"xxt_site_account",
				['uid' => $oUser->userid],
			];
			if ($oOpenid = $this->query_obj_ss($p)) {
				$oUser->wx_openid = $oOpenid->wx_openid;
				if (!empty($oOpenid->wx_openid)) {
					if (!isset($modelWxfan)) {
						$modelWxfan = $this->model('sns\wx\fan');
					}
					$oWxfan = $modelWxfan->byOpenid($oApp->siteid, $oOpenid->wx_openid, 'nickname,headimgurl', 'Y');
					if ($oWxfan) {
						$oUser->wxfan = $oWxfan;
					}
				}
				$oUser->yx_openid = $oOpenid->yx_openid;
				if (!empty($oOpenid->yx_openid)) {
					if (!isset($modelYxfan)) {
						$modelYxfan = $this->model('sns\yx\fan');
					}
					$oYxfan = $modelYxfan->byOpenid($oApp->siteid, $oOpenid->yx_openid, 'nickname,headimgurl', 'Y');
					if ($oYxfan) {
						$oUser->yxfan = $oYxfan;
					}
				}
			} else {
				$oUser->wx_openid = '';
				$oUser->yx_openid = '';
			}
		}

		$data = new \stdClass;
		$data->users = $oAppUsrs;
		$q[0] = "count(id)";
		$data->total = (int) $this->query_val_ss($q);

		return $data;
	}
	/**
	 * 添加或更新
	 */
	public function createOrUpdate($oApp, $oUser, $aData = []) {
		$oAppUsr = $this->byUser($oApp, $oUser);

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