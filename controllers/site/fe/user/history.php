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

		$q2 = ['o' => 'operate_at desc'];

		$logs = $modelAct->query_objs_ss($q, $q2);

		$oResult = new \stdClass;
		$oResult->apps = $logs;

		return new \ResponseData($oResult);
	}
	/**
	 * 获得当前用户在指定站点参与的项目
	 *
	 * @param string $site
	 */
	public function missionList_action($site, $userid = '') {

		$modelAct = $this->model('site\user\account');
		$q = [
			'distinct mission_id,mission_title',
			'xxt_log_user_matter',
			"mission_id<>0 and user_last_op='Y'",
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
		if (count($logs)) {
			$q[0] = 'max(operate_at)';
			$w = $q[2];
			foreach ($logs as &$log) {
				$q[2] = $w . " and mission_id={$log->mission_id}";
				$log->operate_at = (int) $modelAct->query_val_ss($q);
			}
			usort($logs, function ($mis1, $mis2) {
				return $mis2->operate_at - $mis1->operate_at;
			});
		}

		$oResult = new \stdClass;
		$oResult->missions = $logs;

		return new \ResponseData($oResult);
	}
}