<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户邀请记录
 */
class invite extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/invite/main');
		exit;
	}
	/**
	 *
	 */
	public function detail_action() {
		\TPL::output('/site/fe/user/invite/detail');
		exit;
	}
	/**
	 * 当前用户发起的邀请
	 */
	public function list_action($page = 1, $size = 30) {
		if (empty($this->who->unionid)) {
			return new \ResponseError('仅限注册用户访问');
		}
		$modelInv = $this->model('invite');

		$oCreator = new \stdClass;
		$oCreator->id = $this->who->unionid;
		$oCreator->type = 'A';

		$aOptions = [];
		$aOptions['fields'] = 'id,code,require_code,expire_at,matter_id,matter_type,matter_title,matter_summary,matter_pic';
		$aOptions['page'] = (object) ['at' => $page, 'size' => $size];

		$result = $modelInv->byCreator($oCreator, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 *
	 */
	public function get_action($invite) {
		if (empty($this->who->unionid)) {
			return new \ResponseError('仅限注册用户访问');
		}
		$modelInv = $this->model('invite');

		$aOptions['fields'] = 'id,code,require_code,invitee_count,relay_invitee_count,expire_at,matter_siteid,matter_id,matter_title,matter_summary,matter_pic,creator,creator_type';

		$oInvite = $modelInv->byId($invite, $aOptions);
		if (false === $oInvite) {
			return new \ObjectNotFoundError();
		}
		if ($oInvite->creator_type !== 'A' || $oInvite->creator !== $this->who->unionid) {
			return new \ResponseError('没有访问当前对象的权限');
		}

		$oInvite->entryUrl = $modelInv->getEntryUrl($oInvite);

		return new \ResponseData($oInvite);
	}
	/**
	 * 指定邀请的用户邀请码
	 */
	public function codeList_action($invite) {
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

		$modelLog = $this->model('invite\code');
		$aOptions = [];
		$aOptions['fields'] = '*';

		$codes = $modelLog->byInvite($oInvite, $aOptions);

		return new \ResponseData($codes);
	}
	/**
	 * 指定邀请对应的使用日志
	 */
	public function logList_action($invite, $page = 1, $size = 30) {
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

		$modelLog = $this->model('invite\log');
		$aOptions = [];
		$aOptions['fields'] = '*';
		$aOptions['page'] = (object) ['at' => $page, 'size' => $size];

		$result = $modelLog->byInvite($oInvite, $aOptions);

		return new \ResponseData($result);
	}
}