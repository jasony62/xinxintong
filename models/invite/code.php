<?php
namespace invite;
/**
 * 用户邀请码
 */
class code_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_invite_code',
			['id' => $id],
		];
		$oCode = $this->query_obj_ss($q);

		return $oCode;
	}
	/**
	 *
	 */
	public function byInvite($oInvite, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_invite_code',
			['invite_id' => $oInvite->id],
		];
		$q2 = ['o' => 'create_at desc'];
		$oCodes = $this->query_objs_ss($q, $q2);

		return $oCodes;
	}
	/**
	 *
	 */
	public function add($oInvite, $aProto = []) {
		$code = $this->_genCode($oInvite);
		$oNewCode = new \stdClass;
		$oNewCode->invite_id = $oInvite->id;
		$oNewCode->from_invite_code_id = $oInvite->from_invite_code_id;
		$oNewCode->code = $code;
		$oNewCode->remark = empty($aProto['remark']) ? '' : $aProto['remark'];
		$oNewCode->create_at = time();
		$oNewCode->expire_at = empty($aProto['expire_at']) ? 0 : $aProto['expire_at'];
		$oNewCode->last_use_at = 0;
		$oNewCode->max_count = empty($aProto['max_count']) ? 0 : $aProto['max_count'];
		$oNewCode->used_count = 0;
		$oNewCode->relay_invitee_count = 0;
		$oNewCode->stop =  empty($aProto['stop']) ? 'N' : $aProto['stop'];;
		$oNewCode->state = 1;

		$oNewCode->id = $this->insert('xxt_invite_code', $oNewCode, true);

		return $oNewCode;
	}
	/**
	 * 检查并使用邀请码
	 */
	public function checkAndUse($oInvite, $inviteCode, $oInvitee, $onlyCheck = false) {
		$oInviteCode = $this->_byCode($oInvite, $inviteCode);
		if (false === $oInviteCode || $oInviteCode->state !== '1') {
			return [false, '邀请码不存在'];
		}
		if ($oInviteCode->stop !== 'N') {
			return [false, '邀请码已经停止使用'];
		}
		if ($oInviteCode->expire_at > 0 && $oInviteCode->expire_at < time()) {
			return [false, '邀请码已经过期'];
		}
		if ($oInviteCode->max_count > 0 && $oInviteCode->used_count >= $oInviteCode->max_count) {
			return [false, '邀请码已经超过使用次数'];
		}
		if ($onlyCheck) {
			return [true, false];
		}
		/* 修改邀请码使用状态 */
		$current = time();
		$bSuccess = false;
		while (!$bSuccess) {
			$rst = $this->update('update xxt_invite_code set used_count=used_count+1,last_use_at=' . $current . ' where id=' . $oInviteCode->id . ' and used_count=' . $oInviteCode->used_count);
			$bSuccess = $rst === 1;
			if (!$bSuccess) {
				$oInviteCode = $this->byId($oInviteCode->id, ['fields' => 'id,invite_id,max_count,used_count']);
				if ($oInviteCode->max_count > 0 && $oInviteCode->used_count >= $$oInviteCode->max_count) {
					return [false, '邀请码已经超过使用次数'];
				}
			}
		}
		$this->_addRelayCount($oInviteCode);

		/* 记录使用日志 */
		$oInviteCode->last_use_at = $current;
		$oInviteLog = $this->model('invite\log')->add($oInvite, $oInviteCode, $oInvitee);

		return [true, $oInviteLog];
	}
	/**
	 * 更新邀请的成功被邀请人数量
	 */
	private function _addRelayCount($oInviteCode) {
		if ($oInviteCode->from_invite_code_id) {
			$this->update('update xxt_invite_code set relay_invitee_count=relay_invitee_count+1 where id=' . $oInviteCode->from_invite_code_id);
			$oParent = $this->byId($oInviteCode->from_invite_code_id);
			$this->_addRelayCount($oParent);
		}
	}
	/**
	 *
	 */
	private function _byCode($oInvite, $code) {
		$fields = '*';
		$q = [
			$fields,
			'xxt_invite_code',
			['invite_id' => $oInvite->id, 'code' => $code],
		];
		$oInvite = $this->query_obj_ss($q);

		return $oInvite;
	}
	/**
	 * generate a 6bits code.
	 */
	private function _genCode($oInvite) {
		$alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alpha_digits_len = strlen($alpha_digits) - 1;

		$code = '';
		for ($i = 0; $i < 4; $i++) {
			$code .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
		}

		/* 已经使用重新生成 */
		if ($this->_byCode($oInvite, $code)) {
			$code = $this->_genCode($oInvite);
		}

		return $code;
	}
}