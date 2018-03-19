<?php
namespace invite;
/**
 * 用户邀请令牌
 */
class token_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function add($oInviteLog, $expire = 3600) {
		$current = time();
		$token = md5($oInviteLog->id . '-' . $oInviteLog->userid . '-' . $current);

		$oNewToken = new \stdClass;
		$oNewToken->invite_id = $oInviteLog->invite_id;
		$oNewToken->invite_log_id = $oInviteLog->id;
		$oNewToken->matter_id = $oInviteLog->matter_id;
		$oNewToken->matter_type = $oInviteLog->matter_type;
		$oNewToken->userid = $oInviteLog->userid;
		$oNewToken->access_at = $current;
		$oNewToken->expire_at = $current + $expire;
		$oNewToken->token = $token;

		$oNewToken->id = $this->insert('xxt_invite_access', $oNewToken, true);

		return $oNewToken;
	}
	/**
	 * 检查token的有效性
	 */
	public function checkToken($token, $userid, $oMatter) {
		$q = [
			'*',
			'xxt_invite_access',
			['token' => $token, 'userid' => $userid, 'matter_type' => $oMatter->type, 'matter_id' => $oMatter->id],
		];
		$oToken = $this->query_obj_ss($q);
		if (false === $oToken) {
			return [false, '邀请验证令牌未通过验证'];
		}
		if ($oToken->expire_at < time()) {
			return [false, '邀请验证令牌已经超过有效期'];
		}

		return [true, $oToken];
	}
	/*
	 *
	 */
	public function byToken($token, $options = []) {
		$fields = empty($options['fields']) ? '*' : $options['fields'];
		$q = [
			$fields,
			'xxt_invite_access',
			['token' => $token],
		];
		$oToken = $this->query_obj_ss($q);

		return $oToken;
	}
}