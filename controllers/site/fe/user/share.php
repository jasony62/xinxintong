<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户历史轨迹
 */
class share extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/share/main');
		exit;
	}
	/**
	 * 获得当前用户在指定站点参与的活动
	 *
	 * @param string $site site'id
	 * @param string $matterType
	 */
	public function listShare_action($site = '', $userid = '', $page = null, $size = null) {
		$model = $this->model('matter\log');
		$users = [];
		// 指定用户的访问记录
		// if (!empty($userid)) {
		// 	$users[] = $userid;
		// } else if (empty($this->who->unionid)) {
		// 	$users[] = $this->who->uid;
		// } else if (empty($userid) && !empty($this->who->unionid)) {
		// 	$modelAct = $this->model('site\user\account');
		// 	$aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
		// 	foreach ($aSiteAccounts as $oSiteAccount) {
		// 		$users[] = $oSiteAccount->uid;
		// 	}
		// }
		if (!empty($userid)) {
			$userid = $model->escape($userid);
			$users[] = $userid;
		} else {
			$users[] = $this->who->uid;
		}

		$data = $model->listUserShare($site, $users, $page, $size);

		return new \ResponseData($data);
	}
	/*
	* 获取我的分享信息
	*/
	public function getMyShareInfo_action($userid = '', $matterType, $matterId, $orderBy = 'read', $page = null, $size = null) {
		$model = $this->model('matter\log');
		// 指定用户的访问记录
		if (!empty($userid)) {
			$user = new \stdClass;
			$user->uid = $model->escape($userid);
		} else {
			$user = $this->who;
		}

		$users = $model->getMyShareInfo($user, $matterType, $matterId, $orderBy, $page, $size);

		return new \ResponseData($users);
	}
}