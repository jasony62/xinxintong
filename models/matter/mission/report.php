<?php
namespace matter\mission;
/**
 * 项目报告
 */
class report_model extends \TMS_MODEL {
	/**
	 * 获得用户保存的报告定义
	 */
	public function defaultConfigByUser($oCreater, $oMission, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : 'id,as_default,include_apps';
		$q = [
			$fields,
			'xxt_mission_report',
			['mission_id' => $oMission->id, 'creater' => $oCreater->id, 'as_default' => 'Y'],
		];
		$oConfig = $this->query_obj_ss($q);
		if ($oConfig) {
			if (isset($oConfig->include_apps)) {
				$oConfig->include_apps = empty($oConfig->include_apps) ? new \stdClass : json_decode($oConfig->include_apps);
			}
		}

		return $oConfig;
	}
	/**
	 * 新建一个报告配置
	 *
	 * 默认替换已有的配置
	 */
	public function createConfig($oMission, $oCreater, $options = []) {
		$includeApps = $options['includeApps'];
		if (!is_object($includeApps)) {
			$includeApps = new \stdClass;
			$includeApps->apps = [];
			$includeApps->show_schema = [];
		}

		$asDefault = isset($options['asDefault']) ? $options['asDefault'] : 'Y';
		if (!preg_match('/Y|N/', $asDefault)) {
			$asDefault = 'N';
		}

		$oNewDefaultConfig = false;
		if ($asDefault === 'Y') {
			if ($oConfig = $this->defaultConfigByUser($oCreater, $oMission)) {
				if ($oConfig->as_default === 'Y') {
					$isReplaceDefault = isset($options['isReplaceDefault']) ? $options['isReplaceDefault'] : 'Y';
					if ($isReplaceDefault === 'Y') {
						$oNewDefaultConfig = $oConfig;
					} else {
						$this->update('xxt_mission_report', ['as_default' => 'N'], ['id' => $oConfig->id]);
					}
				}
			}
		}

		if ($oNewDefaultConfig) {
			$this->update(
				'xxt_mission_report',
				['include_apps' => $this->toJson($includeApps), 'create_at' => time()],
				['id' => $oNewDefaultConfig->id]
			);
			$oNewDefaultConfig->include_apps = $includeApps;
			$oNewConfig = $oNewDefaultConfig;
		} else {
			$oNewConfig = new \stdClass;
			$oNewConfig->mission_id = $oMission->id;
			$oNewConfig->siteid = $oMission->siteid;
			$oNewConfig->creater = $oCreater->id;
			$oNewConfig->creater_name = $oCreater->name;
			$oNewConfig->create_at = time();
			$oNewConfig->as_default = $asDefault;
			$oNewConfig->include_apps = $this->toJson($includeApps);

			$oNewConfig->id = $this->insert('xxt_mission_report', $oNewConfig, true);
			$oNewConfig->include_apps = $includeApps;
		}

		return $oNewConfig;
	}
	/**
	 * 生成用户在活动中的行为报告
	 *
	 * 统计内容
	 * 1、登记活动统计登记条数和发表留言条数
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
				$oEnlApp = $modelEnl->byId($oApp->id, ['cascaded' => 'N', 'fields' => 'id,title,create_at,start_at,data_schemas,mission_id,sync_mission_round,round_cron']);
				if ($oEnlApp) {
					unset($oEnlApp->data_schemas);
					unset($oEnlApp->pages);
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
			/* 如果分组用户是导入的，且没有和其他活动的填写用户进行过关联，userid就为空 */
			if (empty($oUser->userid)) {
				continue;
			}
			$oUser->data = [];
			foreach ($orderedApps as $index => $oApp) {
				switch ($oApp->type) {
				case 'enroll':
					$modelEnlUsr = $this->model('matter\enroll\user');
					$oUser->data[] = $modelEnlUsr->reportByUser($oApp, $oUser);
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