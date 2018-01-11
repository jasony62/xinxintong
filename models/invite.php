<?php
/**
 * 用户邀请
 */
class invite_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function getEntryUrl($oInvite) {
		$url = "http://" . APP_HTTP_HOST;
		$url .= "/i/{$oInvite->code}";

		return $url;
	}
	/**
	 *
	 */
	public function byId($id, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$cascaded = empty($aOptions['cascaded']) ? 'N' : $aOptions['cascaded'];
		$q = [
			$fields,
			'xxt_invite',
			['id' => $id],
		];
		$oInvite = $this->query_obj_ss($q);
		if ($oInvite && $cascaded === 'Y') {
			/* 用户邀请码 */
			$modelCode = $this->model('invite\code');
			$oInvite->inviteCodes = $modelCode->byInvite($oInvite);
		}

		return $oInvite;
	}
	/**
	 *
	 */
	public function byCode($code, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_invite',
			['code' => $code],
		];
		$oInvite = $this->query_obj_ss($q);

		return $oInvite;
	}
	/**
	 * 根据指定的素材或邀请创建用户返回邀请
	 */
	public function byMatter($oMatter, $oCreator = null, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$q = [
			$fields,
			'xxt_invite',
			['matter_id' => $oMatter->id, 'matter_type' => $oMatter->type],
		];
		if (empty($oCreator)) {
			$oInvite = $this->query_objs_ss($q);
		} else {
			$q[2]['creator'] = $oCreator->id;
			$q[2]['creator_type'] = $oCreator->type;
			$oInvite = $this->query_obj_ss($q);
		}

		return $oInvite;
	}
	/**
	 * 根据指定的素材或邀请创建用户返回邀请
	 */
	public function byCreator($oCreator, $aOptions = []) {
		$fields = empty($aOptions['fields']) ? '*' : $aOptions['fields'];
		$oPage = isset($aOptions['page']) ? $aOptions['page'] : (object) ['at' => 1, 'size' => 30];

		$q = [
			$fields,
			'xxt_invite',
			['creator' => $oCreator->id, 'creator_type' => $oCreator->type, 'state' => 1],
		];
		$q2 = ['r' => ['o' => ($oPage->at - 1) * $oPage->size, 'l' => $oPage->size]];
		$invites = $this->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->invites = $invites;
		if (count($invites) < $oPage->size) {
			$result->total = (($oPage->at - 1) * $oPage->size) + count($invites);
		} else {
			$q[0] = 'count(*)';
			$result->total = (int) $this->query_val_ss($q);
		}

		return $result;
	}
	/**
	 * 创建新邀请
	 */
	public function add($oMatter, $oCreator, $inviteeUid = null) {
		$oNewInvite = new \stdClass;
		// matter
		$oNewInvite->matter_siteid = $oMatter->siteid;
		$oNewInvite->matter_id = $oMatter->id;
		$oNewInvite->matter_type = $oMatter->type;
		$oNewInvite->matter_title = $oMatter->title;
		$oNewInvite->matter_summary = $oMatter->summary;
		$oNewInvite->matter_pic = $oMatter->pic;
		// creator
		$oNewInvite->creator = $oCreator->id;
		$oNewInvite->creator_name = $oCreator->name;
		$oNewInvite->creator_type = $oCreator->type;
		// code
		$code = $this->_genCode();
		$oNewInvite->code = $code;
		// 记录邀请传递关系
		if ($oCreator->type === 'A') {
			if (empty($inviteeUid)) {
				return [false, '参数不完整'];
			}
			$inviteLogs = $this->model('invite\log')->byUser($oMatter, $inviteeUid);
			if (empty($inviteLogs)) {
				return [false, '只有获得邀请并通过的用户可再发出邀请'];
			}
			$firstLog = $inviteLogs[0];
			$oNewInvite->from_invite_id = $firstLog->invite_id;
			$oNewInvite->from_invite_code_id = $firstLog->invite_code_id;
		} else {
			$oNewInvite->from_invite_id = 0;
			$oNewInvite->from_invite_code_id = 0;
		}

		$oNewInvite->create_at = time();
		$oNewInvite->expire_at = 0;
		$oNewInvite->require_code = 'Y';
		$oNewInvite->can_relay = 'N';
		$oNewInvite->state = 1;

		$oNewInvite->id = $this->insert('xxt_invite', $oNewInvite, true);

		return [true, $oNewInvite];
	}
	/**
	 * 更新邀请的成功被邀请人数量
	 */
	public function addInviterCount($oInvite, $bFromChild = true) {
		if ($bFromChild) {
			$this->update('update xxt_invite set invitee_count=invitee_count+1 where id=' . $oInvite->id);
		} else {
			$this->update('update xxt_invite set relay_invitee_count=relay_invitee_count+1 where id=' . $oInvite->id);
		}
		if (!empty($oInvite->from_invite_id)) {
			$oParent = $this->byId($oInvite->from_invite_id, ['fields' => 'id,from_invite_id']);
			$this->addInviterCount($oParent, false);
		}
	}
	/**
	 * generate a 6bits code.
	 */
	private function _genCode() {
		$alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alpha_digits_len = strlen($alpha_digits) - 1;

		$code = '';
		for ($i = 0; $i < 6; $i++) {
			$code .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
		}

		/* 已经使用重新生成 */
		if ($this->byCode($code)) {
			$code = $this->_genCode();
		}

		return $code;
	}
}