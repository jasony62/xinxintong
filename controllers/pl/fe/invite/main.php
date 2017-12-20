<?php
namespace pl\fe\invite;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户邀请
 */
class main extends \pl\fe\base {
	/**
	 * 根据url的参数获得对应的素材
	 */
	private function _getMatter($matter) {
		$matter = explode(',', $matter);
		if (2 !== count($matter)) {
			return false;
		}
		list($type, $id) = $matter;
		$oMatter = $this->model('matter\\' . $type)->byId($id);

		return $oMatter;
	}
	/**
	 * 获得素材邀请定义
	 *
	 * @return
	 */
	public function get_action($matter) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oMatter = $this->_getMatter($matter);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$oCreator = new \stdClass;
		$oCreator->id = $oMatter->siteid;
		$oCreator->name = '';
		$oCreator->type = 'S';

		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byMatter($oMatter, $oCreator, ['fields' => 'id,code,expire_at,require_code,invitee_count,relay_invitee_count,matter_type,matter_id']);
		if ($oInvite) {
			$oInvite->entryUrl = $modelInv->getEntryUrl($oInvite);
		}

		return new \ResponseData($oInvite);
	}
	/**
	 * 给指定素材创建邀请
	 *
	 * @return
	 */
	public function create_action($matter) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oMatter = $this->_getMatter($matter);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		$oCreator = new \stdClass;
		$oCreator->id = $oMatter->siteid;
		$oCreator->name = '';
		$oCreator->type = 'S';

		$modelInv = $this->model('invite')->setOnlyWriteDbConn(true);

		/* 如果已经存在邀请直接返回，每个素材，每个用户只能创建一个邀请 */
		$oInvite = $modelInv->byMatter($oMatter, $oCreator);
		if (false === $oInvite) {
			$aResult = $modelInv->add($oMatter, $oCreator);
			if (false === $aResult[0]) {
				return new \ResponseError($aResult[1]);
			}
			$oInvite = $aResult[1];
		}

		$oInvite->entryUrl = $modelInv->getEntryUrl($oInvite);

		return new \ResponseData($oInvite);
	}
	/**
	 * 删除素材邀请
	 *
	 * @return
	 */
	public function remove_action($code) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('invite');

		$rst = $modelInv->removeByCode($code);

		return new \ResponseData($rst);
	}
	/**
	 * 更新素材邀请
	 *
	 * @return
	 */
	public function update_action($invite) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byId($invite, ['fields' => 'id,creator,creator_type']);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}
		if ($oInvite->creator_type !== 'S') {
			return new \ResponseError('没有访问当前对象的权限');
		}

		$aUpdated = [];
		$posted = $this->getPostJson();
		foreach ($posted as $prop => $val) {
			if ($prop === 'require_code') {
				$aUpdated[$prop] = $val === 'Y' ? 'Y' : 'N';
			}
		}
		if (!empty($aUpdated)) {
			$modelInv->update('xxt_invite', $aUpdated, ['id' => $oInvite->id]);
		}

		return new \ResponseData(count($aUpdated));
	}
	/**
	 *
	 */
	public function relayList_action($matter, $page = 1, $size = 30) {
		if (false === $this->accountUser()) {
			return new \ResponseTimeout();
		}

		$oMatter = $this->_getMatter($matter);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}
		$q = [
			'*',
			'xxt_invite',
			['matter_type' => $oMatter->type, 'matter_id' => $oMatter->id, 'from_invite_id' => (object) ['op' => '<>', 'pat' => '0']],
		];
		$q2 = ['r' => ['o' => ($page - 1) * $size, 'l' => $size]];

		$modelInv = $this->model('invite');
		$relayInvites = $modelInv->query_objs_ss($q, $q2);

		$result = new \stdClass;
		$result->invites = $relayInvites;
		if (count($relayInvites) < $size) {
			$result->total = (($page - 1) * $size) + count($relayInvites);
		} else {
			$q[0] = 'count(*)';
			$result->total = (int) $modelInv->query_val_ss($q);
		}

		return new \ResponseData($result);
	}
}