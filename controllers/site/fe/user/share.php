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
	public function index_action($page = '') {
		switch ($page) {
			case 'log':
				\TPL::output('/site/fe/user/share/log');
				break;
			default:
				\TPL::output('/site/fe/user/share/main');
				break;
		}

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
		// 	$users[] = $model->escape($userid);
		// } else if (empty($this->who->unionid)) {
		// 	$users[] = $this->who->uid;
		// } else {
		// 	$modelAct = $this->model('site\user\account');
		// 	$aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
		// 	foreach ($aSiteAccounts as $oSiteAccount) {
		// 		$users[] = $oSiteAccount->uid;
		// 	}
		// }
		if (!empty($userid)) {
			$users[] = $model->escape($userid);
		} else {
			$users[] = $this->who->uid;
		}

		$data = $model->listUserShare($site, $users, $page, $size);

		return new \ResponseData($data);
	}
	/*
	* 获取我的分享信息
	*/
	public function getMyShareLog_action($userid = '', $matterType, $matterId, $orderBy = 'read', $page = null, $size = null) {
		$model = $this->model('matter\log');
		// 指定用户的访问记录
		if (!empty($userid)) {
			$userid = $model->escape($userid);
		} else {
			$userid = $this->who->uid;
		}

		$users = $model->getMyShareLog($userid, $matterType, $matterId, $orderBy, $page, $size);

		return new \ResponseData($users);
	}
}