<?php
namespace site\fe\invite;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 邀请码
 */
class code extends \site\fe\base {
	/**
	 * 给用户邀请添加邀请码
	 */
	public function add_action($invite) {
		if (empty($this->who->unionid)) {
			return new \ResponseError('仅限注册用户访问');
		}
		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byId($invite, ['fields' => 'id,creator,creator_type,from_invite_code_id']);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}
		if ($oInvite->creator_type !== 'A' || $oInvite->creator !== $this->who->unionid) {
			return new \ResponseError('没有访问当前对象的权限');
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
		if (empty($this->who->unionid)) {
			return new \ResponseError('仅限注册用户访问');
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
		}
		if (!empty($aUpdated)) {
			$modelCode->update('xxt_invite_code', $aUpdated, ['id' => $oCode->id]);
		}

		return new \ResponseData(count($aUpdated));
	}
}