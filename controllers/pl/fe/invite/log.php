<?php
namespace pl\fe\invite;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户邀请码使用日志
 */
class log extends \pl\fe\base {
	/**
	 *
	 */
	public function list_action($invite, $page = 1, $size = 30) {
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

		$modelLog = $this->model('invite\log');
		$aOptions = [];
		$aOptions['fields'] = '*';
		$aOptions['page'] = (object) ['at' => $page, 'size' => $size];

		$result = $modelLog->byInvite($oInvite, $aOptions);

		return new \ResponseData($result);
	}
}