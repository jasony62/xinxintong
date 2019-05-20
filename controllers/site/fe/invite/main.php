<?php
namespace site\fe\invite;

include_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 素材邀请
 */
class main extends \site\fe\base {
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
	 * 用户邀请页
	 */
	public function index_action($matter) {
		$oMatter = $this->_getMatter($matter);
		if (false === $oMatter) {
			\TPL::assign('title', '调用失败');
			\TPL::assign('body', '指定的对象不存在');
			\TPL::output('error');
			exit;
		}

		\TPL::assign('title', $oMatter->title);
		\TPL::output('/site/fe/invite/matter');
		exit;
	}
	/*
	 * 根据matter和inviteToken返回用户有权邀请的素材
	 */
	public function listInviteMatter_action($matter, $inviteToken) {
		$user = $this->who;
		if (empty($user->unionid)) {
			return new \ResponseError('仅限注册用户访问');
		}

		$invMatters = [];
		// 根据$inviteToken查询用户通过什么素材进入的
		$oToken = $this->model('invite\token')->byToken($inviteToken, ['fields' => 'userid,matter_id,matter_type']);
		if ($oToken === false) {
			return new \ResponseError('inviteToken参数错误');
		}
		$objMatter1 = $this->model('matter\\' . $oToken->matter_type)->byId($oToken->matter_id, ['fields' => 'id,title']);
		$invMatters[] = $objMatter1;

		// 根据matter获取当前素材信息
		$matter = explode(',', $matter);
		list($type, $id) = $matter;
		// 如果是同一个素材直接返回
		if ($type === $objMatter1->type && $id == $objMatter1->id) {
			return new \ResponseData($invMatters);
		}

		/* 检查用户是否已经通过邀请 */
		$objMatter2 = $this->model('matter\\' . $type)->byId($id, ['fields' => 'id,title,siteid']);
		$siteUsers = $this->model('site\user\account')->byUnionid($user->unionid, ['fields' => 'uid', 'siteid' => $objMatter2->siteid]);
		if (count($siteUsers)) {
			$modelInvLog = $this->model('invite\log');
			foreach ($siteUsers as $oSiteUser) {
				if (true === $modelInvLog->hasPassed($objMatter2, $oSiteUser->uid)) {
					$invMatters[] = $objMatter2;
					break;
				}
			}
		}

		return new \ResponseData($invMatters);
	}
	/**
	 * 根据邀请的编码获得邀请
	 */
	public function get_action($inviteCode) {
		$inviteCode = $this->escape($inviteCode);

		$modelInv = $this->model('invite')->setOnlyWriteDbConn(true);
		$oInvite = $modelInv->byCode($inviteCode);
		if (empty($oInvite->matter_type) || empty($oInvite->matter_id)) {
			return new \ResponseError('邀请没有指定的素材');
		}
		$oMatter = $this->model('matter\\' . $oInvite->matter_type)->byId($oInvite->matter_id);
		if (false === $oMatter) {
			return new \ObjectNotFoundError('邀请指定的素材不存在');
		}
		if (!empty($oMatter->entry_rule) || !empty($oMatter->entryRule)) {
			if (isset($oMatter->entryRule)) {
				$oEntryRule = $oMatter->entryRule;
			} else {
				$oEntryRule = is_string($oMatter->entry_rule) ? json_decode($oMatter->entry_rule) : $oMatter->entry_rule;
			}
			if (isset($oEntryRule->scope) && $oEntryRule->scope === 'member') {
				if (is_array($oEntryRule->member)) {
					foreach ($oEntryRule->member as $mschemaId) {
						if ($oUserMember = $this->whoMember($oMatter->siteid, $mschemaId)) {
							break;
						}
					}
				} else {
					foreach ($oEntryRule->member as $mschemaId => $oRule) {
						if ($oUserMember = $this->whoMember($oMatter->siteid, $mschemaId)) {
							break;
						}
					}
				}
				$oEntryRule->passed = $oUserMember;
				if (false === $oUserMember) {
					$oMschemas = [];
					$modelMs = $this->model('site\user\memberschema');
					if (is_array($oEntryRule->member)) {
						foreach ($oEntryRule->member as $mschemaId) {
							$oMschemas[] = $modelMs->byId($mschemaId);
						}
					} else {
						foreach ($oEntryRule->member as $mschemaId => $oRule) {
							$oMschemas[] = $modelMs->byId($mschemaId);
						}
					}
					$oEntryRule->mschemas = $oMschemas;
				}
				$oInvite->entryRule = $oEntryRule;
			}
		}

		return new \ResponseData($oInvite);
	}
	/**
	 * 给指定素材创建邀请
	 * 1、必须是注册用户才能创建邀请
	 * 2、必须是已经参加邀请的用户才能创建邀请
	 *
	 * @return
	 */
	public function create_action($matter) {
		if (empty($this->who->unionid)) {
			return new \ResponseError('仅限注册用户访问');
		}
		$oMatter = $this->_getMatter($matter);
		if (false === $oMatter) {
			return new \ObjectNotFoundError();
		}

		/* 检查用户是否已经通过邀请 */
		$siteUsers = $this->model('site\user\account')->byUnionid($this->who->unionid, ['fields' => 'uid', 'siteid' => $oMatter->siteid]);
		if (count($siteUsers)) {
			$modelInvLog = $this->model('invite\log');
			foreach ($siteUsers as $oSiteUser) {
				if (true === $modelInvLog->hasPassed($oMatter, $oSiteUser->uid)) {
					$inviteeUid = $oSiteUser->uid;
					break;
				}
			}
		}
		if (!isset($inviteeUid)) {
			return new \ResponseError('只有获得邀请并通过的用户可再发出邀请');
		}

		$oCreator = new \stdClass;
		$oCreator->id = $this->who->unionid;
		$oCreator->name = $this->who->nickname;
		$oCreator->type = 'A';

		/* 如果已经存在邀请直接返回，每个素材，每个用户只能创建一个邀请 */
		$modelInv = $this->model('invite')->setOnlyWriteDbConn(true);
		$oInvite = $modelInv->byMatter($oMatter, $oCreator);
		if (false === $oInvite) {
			$aResult = $modelInv->add($oMatter, $oCreator, $inviteeUid);
			if (false === $aResult[0]) {
				return new \ResponseError($aResult[1]);
			}
			$oInvite = $aResult[1];
		}
		$oInvite->entryUrl = $modelInv->getEntryUrl($oInvite);

		return new \ResponseData($oInvite);
	}
	/**
	 *
	 */
	public function update_action($invite) {
		if (empty($this->who->unionid)) {
			return new \ResponseError('仅限注册用户访问');
		}
		$modelInv = $this->model('invite');
		$oInvite = $modelInv->byId($invite, ['fields' => 'id,creator,creator_type']);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}
		if ($oInvite->creator_type !== 'A' || $oInvite->creator !== $this->who->unionid) {
			return new \ResponseError('没有访问当前对象的权限');
		}

		$aUpdated = [];
		$posted = $this->getPostJson();
		if ($posted) {
			foreach ($posted as $prop => $val) {
				switch ($prop) {
				case 'require_code':
					$aUpdated[$prop] = $val === 'Y' ? 'Y' : 'N';
					break;
				case 'message':
					$aUpdated[$prop] = $val;
					break;
				}
			}
		}
		if (!empty($aUpdated)) {
			$modelInv->update('xxt_invite', $aUpdated, ['id' => $oInvite->id]);
		}

		return new \ResponseData(count($aUpdated));
	}
}