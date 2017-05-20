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
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oSchema = $this->model('site\user\memberschema')->byId($schema, 'id,siteid');
		if (false === $oSchema) {
			return new \ObjectNotFoundError();
		}

		$oResult = $this->model('site\user\memberinvite')->bySchema($oSchema);

		return new \ResponseData($oResult);
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
	/**
	 *
	 */
	public function update_action($invite) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('site\user\memberinvite');
		$oInvite = $modelInv->byId($invite);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}

		$posted = $this->getPostJson();
		$updated = new \stdClass;
		if (isset($posted->max_count)) {
			$updated->max_count = $posted->max_count;
		}

		$rst = $modelInv->update(
			'xxt_site_member_invite',
			$updated,
			['id' => $oInvite->id]
		);
		if ($rst) {
			foreach ($updated as $k => $v) {
				$oInvite->{$k} = $v;
			}
		}

		return new \ResponseData($oInvite);
	}
}