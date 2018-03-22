<?php
namespace pl\fe\invite;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户邀请码
 */
class code extends \pl\fe\base {
	/**
	 * 给用户邀请添加邀请码
	 */
	public function add_action($invite) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oInvite = $this->model('invite')->byId($invite);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}

		$modelCode = $this->model('invite\code')->setOnlyWriteDbConn(true);

		$oCode = $modelCode->add($oInvite);

		return new \ResponseData($oCode);
	}
	/**
	 * 更新素材邀请
	 *
	 * @return
	 */
	public function update_action($code) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelCode = $this->model('invite\code');
		$oCode = $modelCode->byId($code, ['fields' => 'id,invite_id']);
		if (false === $oCode) {
			return new \ObjectNotFoundError();
		}

		$aUpdated = [];
		$posted = $this->getPostJson();
		foreach ($posted as $prop => $val) {
			if ($prop === 'remark') {
				$aUpdated[$prop] = $modelCode->escape($val);
			}
			if ($prop === 'stop') {
				$aUpdated[$prop] = $modelCode->escape($val);
			}
			if ($prop === 'expireAt' && is_numeric($val)) {
				$aUpdated['expire_at'] = (int)$val;
			}
			if ($prop === 'maxCount' && is_int($val)) {
				$aUpdated['max_count'] = $val;
			}
		}
		if (!empty($aUpdated)) {
			$modelCode->update('xxt_invite_code', $aUpdated, ['id' => $oCode->id]);
		}

		return new \ResponseData(count($aUpdated));
	}
	/**
	 *
	 */
	public function list_action($invite) {
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

		$modelLog = $this->model('invite\code');
		$aOptions = [];
		$aOptions['fields'] = '*';

		$codes = $modelLog->byInvite($oInvite, $aOptions);

		return new \ResponseData($codes);
	}
}