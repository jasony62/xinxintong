<?php
namespace invite;
/**
 * 用户邀请日志
 */
class log_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byInvite($oInvite, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$oPage = isset($aOptions['page']) ? $aOptions['page'] : (object) ['at' => 1, 'size' => 30];

		$q = [
			$fields,
			'xxt_invite_log',
			['invite_id' => $oInvite->id],
		];
		$q2 = ['r' => ['o' => ($oPage->at - 1) * $oPage->size, 'l' => $oPage->size]];
		$logs = $this->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->logs = $logs;
		if (count($logs)) {
			$modelCode = $this->model('invite\code');
			$oCodesById = new \stdClass;
			foreach ($logs as $oLog) {
				if (!empty($oLog->invite_code_id)) {
					if (isset($oCodesById->{$oLog->invite_code_id})) {
						$oCode = $oCodesById->{$oLog->invite_code_id};
					} else {
						$oCode = $modelCode->byId($oLog->invite_code_id, ['fields' => 'id,code,remark']);
						$oCodesById->{$oCode->id} = $oCode;
					}
					$oLog->inviteCode = $oCode;
				}
			}
		}

		if (count($logs) < $oPage->size) {
			$result->total = (($oPage->at - 1) * $oPage->size) + count($logs);
		} else {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 *
	 */
	public function byInviteCode($oInviteCode, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$oPage = isset($aOptions['page']) ? $aOptions['page'] : (object) ['at' => 1, 'size' => 30];

		$q = [
			$fields,
			'xxt_invite_log',
			['invite_code_id' => $oInviteCode->id],
		];
		$q2 = ['r' => ['o' => ($oPage->at - 1) * $oPage->size, 'l' => $oPage->size]];
		$logs = $this->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->logs = $logs;
		if (count($logs) < $oPage->size) {
			$result->total = (($oPage->at - 1) * $oPage->size) + count($logs);
		} else {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 * 添加邀请码使用日志
	 */
	public function add($oInvite, $oInviteCode, $oInvitee) {
		$oNewLog = new \stdClass;
		$oNewLog->invite_id = $oInviteCode->invite_id;
		$oNewLog->matter_id = $oInvite->matter_id;
		$oNewLog->matter_type = $oInvite->matter_type;
		$oNewLog->invite_code_id = $oInviteCode->id;
		$oNewLog->userid = $oInvitee->uid;
		$oNewLog->nickname = $oInvitee->nickname;
		$oNewLog->use_at = $oInviteCode->last_use_at;

		$oNewLog->id = $this->insert('xxt_invite_log', $oNewLog, true);

		return $oNewLog;
	}
	/**
	 * 指定用户是否收到了指定素材的邀请并通过
	 */
	public function hasPassed($oMatter, $userid) {
		$q = [
			'count(*)',
			'xxt_invite_log',
			['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'userid' => $userid],
		];
		$cnt = (int) $this->query_val_ss($q);

		return $cnt > 0;
	}
	/**
	 * 指定用户的邀请通过记录
	 */
	public function byUser($oMatter, $userid, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_invite_log',
			['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'userid' => $userid],
		];
		if (!empty($aOptions['invite_id'])) {
			$q[2]['invite_id'] = $aOptions['invite_id'];
		}
		$q2 = ['o' => 'use_at'];

		$logs = $this->query_objs_ss($q, $q2);

		return $logs;
	}
}