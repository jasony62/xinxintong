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

		$oSchema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'id,siteid']);
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
		$oSchema = $this->model('site\user\memberschema')->byId($schema, ['fields' => 'id,siteid']);
		if (false === $oSchema) {
			return new \ObjectNotFoundError();
		}

		$posted = $this->getPostJson();
		$created = new \stdClass;
		if (isset($posted->max_count)) {
			$created->max_count = $posted->max_count;
		}
		if (isset($posted->expire_at)) {
			$created->expire_at = $posted->expire_at;
		}

		$modelInv = $this->model('site\user\memberinvite');
		$oInvite = $modelInv->add($oUser, $oSchema, $created);

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
		if (isset($posted->expire_at)) {
			$updated->expire_at = $posted->expire_at;
		}
		if (isset($posted->stop)) {
			$updated->stop = $posted->stop === 'Y' ? 'Y' : 'N';
		}
		if (isset($posted->state)) {
			$updated->state = $posted->state;
		}
		if (count(get_object_vars($updated)) === 0) {
			return new \ParameterError('没有指定有效的更新数据');
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