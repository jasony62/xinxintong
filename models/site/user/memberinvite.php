<?php
namespace site\user;
/**
 * 联系人邀请
 */
class memberinvite_model extends \TMS_MODEL {
	/**
	 * 生成邀请
	 */
	public function add(&$oUser, $oSchema) {
		$code = $this->_genCode();

		$q = [
			'1',
			'xxt_site_member_invite',
			['schema_id' => $oSchema->id, 'code' => $code, 'state' => 1],
		];

		while ('1' === $this->query_val_ss($q)) {
			$code = $this->_genCode();
			$q[2] = ['schema_id' => $oSchema->id, 'code' => $code, 'state' => 1];
		}

		$current = time();
		$oInvite = new \stdClass;
		$oInvite->siteid = $oSchema->siteid;
		$oInvite->creater = $oUser->id;
		$oInvite->schema_id = $oSchema->id;
		$oInvite->code = $code;
		$oInvite->create_at = $current;
		$oInvite->expire_at = $current;
		$oInvite->max_count = 1;
		$oInvite->use_count = 0;
		$oInvite->stop = 'N';

		$oInvite->id = $this->insert('xxt_site_member_invite', $oInvite, true);

		return $oInvite;
	}
	/**
	 * 使用邀请
	 */
	public function useCode($schemaId, $code) {
		$schemaId = $this->escape($schemaId);
		$code = $this->escape($code);
		$rst = $this->update("update xxt_site_member_invite set use_count=use_count+1 where schema_id={$schemaId} and code='$code' and use_count<max_count");

		return $rst === 1;
	}
	/**
	 * generate a 4bits code.
	 */
	private static function _genCode() {
		$alpha_digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$alpha_digits_len = strlen($alpha_digits) - 1;

		$code = '';
		for ($i = 0; $i < 6; $i++) {
			$code .= $alpha_digits[mt_rand(0, $alpha_digits_len)];
		}

		return $code;
	}
}