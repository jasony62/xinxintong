<?php
namespace site\fe\user;

require_once dirname(dirname(__FILE__)) . '/base.php';
/**
 * 用户历史轨迹
 */
class history extends \site\fe\base {
	/**
	 *
	 */
	public function index_action() {
		\TPL::output('/site/fe/user/history/main');
		exit;
	}
	/**
	 * 获得当前用户在指定站点参与的活动
	 *
	 * @param string $site site'id
	 * @param string $matterType
	 */
	public function appList_action($site = '', $matterType = 'enroll,signin', $userid = '') {
		$result = new \stdClass;
		$modelAct = $this->model('site\user\account');
		$q = [
			'matter_id,matter_type,matter_title,operate_at',
			'xxt_log_user_matter',
			"user_last_op='Y' and operation='submit'",
		];
		// 指定团队下的访问记录
		if (!empty($site) && $site !== 'platform') {
			$site = $modelAct->escape($site);
			$q[2] .= " and siteid='{$site}'";
		}
		// 指定用户的访问记录
		if (!empty($userid)) {
			$userid = $modelAct->escape($userid);
			$q[2] .= " and userid='{userid}'";
		} else if (empty($this->who->unionid)) {
			$q[2] .= " and userid='{$this->who->uid}'";
		} else {
			$aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
			$q[2] .= " and userid in('";
			foreach ($aSiteAccounts as $index => $oSiteAccount) {
				if ($index > 0) {
					$q[2] .= "','";
				}
				$q[2] .= $oSiteAccount->uid;
			}
			$q[2] .= "')";
		}
		// 指定素材类型
		if (!empty($matterType)) {
			$matterType = explode(',', $matterType);
			$matterType = "'" . implode("','", $matterType) . "'";
			$q[2] .= " and matter_type in (" . $matterType . ")";
		}

		$logs = $modelAct->query_objs_ss($q);
		$result->apps = $logs;

		return new \ResponseData($result);
	}
	/**
	 * 获得当前用户在指定站点参与的项目
	 *
	 * @param string $site
	 */
	public function missionList_action($site, $userid = '') {
		$result = new \stdClass;

		$modelAct = $this->model('site\user\account');
		$q = [
			'distinct mission_id,mission_title',
			'xxt_log_user_matter',
			"mission_id<>0",
		];

		// 指定团队下的访问记录
		if (!empty($site) && $site !== 'platform') {
			$site = $modelAct->escape($site);
			$q[2] .= " and siteid='{$site}'";
		}

		// 指定用户的访问记录
		if (!empty($userid)) {
			$userid = $modelAct->escape($userid);
			$q[2] .= " and userid='{userid}'";
		} else if (empty($this->who->unionid)) {
			$q[2] .= " and userid='{$this->who->uid}'";
		} else {
			$aSiteAccounts = $modelAct->byUnionid($this->who->unionid, ['fields' => 'uid']);
			$q[2] .= " and userid in('";
			foreach ($aSiteAccounts as $index => $oSiteAccount) {
				if ($index > 0) {
					$q[2] .= "','";
				}
				$q[2] .= $oSiteAccount->uid;
			}
			$q[2] .= "')";
		}

		$logs = $modelAct->query_objs_ss($q);

		$result->missions = $logs;

		return new \ResponseData($result);
	}
}