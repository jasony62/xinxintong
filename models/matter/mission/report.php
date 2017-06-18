<?php
namespace matter\mission;
/**
 * 项目报告
 */
class report_model extends \TMS_MODEL {
	/**
	 * 生成用户在活动中的行为报告
	 *
	 * 统计内容
	 * 1、登记活动统计登记条数和发表评论条数
	 * 2、签到活动统计签到次数和迟到次数
	 * 3、分组活动统计分组名称
	 *
	 * 活动排序
	 * 分组活动和分组活动创建时间
	 * 登记活动签到活动，它们的开始时间
	 *
	 * @param array $users uid,nickname
	 * @param array $apps id,type
	 *
	 */
	public function userAndApp($users, $apps) {
		if (empty($users) || empty($apps)) {
			return false;
		}

		/* 对应用进行排序 */
		$orderedApps = [];
		foreach ($apps as $oApp) {
			if (empty($oApp->id) || empty($oApp->type)) {
				continue;
			}
			switch ($oApp->type) {
			case 'enroll':
				if (!isset($modelEnl)) {
					$modelEnl = $this->model('matter\enroll');
				}
				$oEnlApp = $modelEnl->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,title,create_at,start_at,data_schemas']);
				if ($oEnlApp) {
					unset($oEnlApp->data_schemas);
					$orderedApps[] = $oEnlApp;
				}
				break;
			case 'signin':
				if (!isset($modelSig)) {
					$modelSig = $this->model('matter\signin');
				}
				$oSigApp = $modelSig->byId($oApp->id, ['cascaded' => 'Y', 'fields' => 'id,title,create_at']);
				if ($oSigApp) {
					unset($oSigApp->pages);
					$orderedApps[] = $oSigApp;
				}
				break;
			case 'group':
				if (!isset($modelGrp)) {
					$modelGrp = $this->model('matter\group');
				}
				$oGrpApp = $modelGrp->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,title,create_at']);
				if ($oGrpApp) {
					$orderedApps[] = $oGrpApp;
				}
				break;
			}
		}
		if (count($orderedApps) === 0) {
			return false;
		}

		/* 按用户获得数据 */
		foreach ($users as &$oUser) {
			$oUser->data = [];
			foreach ($orderedApps as $index => $oApp) {
				switch ($oApp->type) {
				case 'enroll':
					$oUser->data[] = $modelEnl->reportByUser($oApp, $oUser);
					break;
				case 'signin':
					$oUser->data[] = $modelSig->reportByUser($oApp, $oUser);
					break;
				case 'group':
					$oUser->data[] = $modelGrp->reportByUser($oApp, $oUser);
					break;
				}
			}
		}

		$result = new \stdClass;
		$result->users = $users;
		$result->orderedApps = $orderedApps;

		return $result;
	}
}