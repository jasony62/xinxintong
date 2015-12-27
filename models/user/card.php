<?php
class card_model extends TMS_MODEL {
	/**
	 * 创建一个会员卡
	 * 每个账号只有一个会员卡
	 * 所以若没有则创建一个，若有则直接返回
	 *
	 */
	public function get($mpid) {
		$q = array('*', 'xxt_member_card', "mpid='$mpid'");
		if (!($card = parent::query_obj_ss($q))) {
			parent::insert('xxt_member_card', array('mpid' => $mpid), false);
			$card = parent::query_obj_ss($q);
		}
		return $card;
	}
}