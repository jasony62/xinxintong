<?php
namespace pl\fe\site\member;

require_once dirname(dirname(dirname(__FILE__))) . '/base.php';
/**
 * 联系人邀请
 */
class invite extends \pl\fe\base {
	/**
	 *
	 */
	public function list_action($schema) {
		return new \ResponseData(array());
	}
	/**
	 *
	 */
	public function add_action($schema) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSchema = $this->model('site\user\memberschema')->byId($schema, 'id,siteid');
		if (false === $oSchema) {
			return new \ObjectNotFoundError();
		}

		$modelInv = $this->model('site\user\memberinvite');
		$oInvite = $modelInv->add($oUser, $oSchema);

		return new \ResponseData($oInvite);
	}
}