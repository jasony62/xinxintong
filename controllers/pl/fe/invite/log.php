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
		$oInvite = $modelInv->byId($invite, ['fields' => 'id,creator,creator_type,matter_type,matter_id']);
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
		if (!empty($result->logs)) {
			$oMatter = $this->model('matter\\' . $oInvite->matter_type)->byId($oInvite->matter_id);
			if ($oMatter && !empty($oMatter->entry_rule)) {
				$oEntryRule = is_string($oMatter->entry_rule) ? json_decode($oMatter->entry_rule) : $oMatter->entry_rule;
				if (isset($oEntryRule->scope) && $oEntryRule->scope === 'member') {
					$mschemas = $oEntryRule->member;
				}
			}
			$modelAct = $this->model('site\user\account');
			if (!empty($mschemas)) {
				$modelMem = $this->model('site\user\member');
			}

			$modelAct = $this->model('site\user\account');
			foreach ($result->logs as $log) {
				if (empty($mschemas)) {
					$log->user = $modelAct->byId($log->userid, ['fields' => 'nickname']);
				} else {
					foreach ($mschemas as $mschemaId) {
						$aMembers = $modelMem->byUser($log->userid, ['mschema' => $mschemaId, 'fields' => 'id,name,email,mobile,extattr']);
						if (count($aMembers)) {
							$log->member = $aMembers[0];
							break;
						}
					}
				}
			}
		}

		return new \ResponseData($result);
	}
}