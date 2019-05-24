<?php
namespace site\user;
/**
 * 联系人邀请
 */
class memberinvite_model extends \TMS_MODEL {
	/**
	 *
	 */
	public function byId($id, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$result = new \stdClass;
		$q = [
			$fields,
			'xxt_site_member_invite',
			['id' => $id],
		];
		$oInvite = $this->query_obj_ss($q);

		return $oInvite;
	}
	/**
	 *
	 */
	public function bySchema($oSchema, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$result = new \stdClass;
		$q = [
			$fields,
			'xxt_site_member_invite',
			['schema_id' => $oSchema->id, 'state' => 1],
		];
		$aInvites = $this->query_objs_ss($q);

		$result->invites = $aInvites;

		return $result;
	}
	/**
	 * 生成邀请
	 */
	public function add(&$oUser, $oSchema, $options = null) {
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
		$oInvite->expire_at = isset($options->expire_at) ? $options->expire_at : 0;
		$oInvite->max_count = isset($options->max_count) ? $options->max_count : 0;
		$oInvite->use_count = 0;
		$oInvite->stop = 'N';
		$oInvite->state = 1;

		$oInvite->id = $this->insert('xxt_site_member_invite', $oInvite, true);

		return $oInvite;
	}
	/**
	 * 使用邀请
	 */
	public function useCode($schemaId, $code) {
		$current = time();
		$rst = $this->update("update xxt_site_member_invite set use_count=use_count+1 where schema_id={$schemaId} and code='$code' and use_count<max_count and (expire_at=0 or expire_at>{$current})");

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