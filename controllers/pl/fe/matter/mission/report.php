<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class report extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 * 获得项目汇总报告
	 * 如果用户指定了查询参数，保存查询参数
	 */
	public function userAndApp_action($mission) {
		if (false === ($oLoginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		$posted = $this->getPostJson();

		$result = $this->userAndAppData($oLoginUser, $oMission, $posted);

		return new \ResponseData($result);
	}
	/* 
	*获得用户指定app下的用户
	*/
	private function userAndAppData($oLoginUser, $oMission, $posted = '') {
		if (!isset($posted->userSource) || !isset($posted->userSource->type) || !isset($posted->userSource->id)) {
			if (isset($oMission->user_app_id) && isset($oMission->user_app_type)) {
				$userSource = new \stdClass;
				$userSource->id = $oMission->user_app_id;
				$userSource->type = $oMission->user_app_type;
			} else {
				return new \ParameterError();
			}
		} else {
			$userSource = $posted->userSource;
		}

		/* 获得用户 */
		switch ($userSource->type) {
		case 'group':
			$oGrpApp = $this->model('matter\group')->byId($userSource->id, ['fields' => 'assigned_nickname', 'cascaded' => 'N']);
			$users = $this->model('matter\group\player')->byApp($userSource, (object) ['fields' => 'userid,nickname,round_id,round_title,data,comment']);
			$users = isset($users->players) ? $users->players : [];
			if (count($users)) {
				/* 指定分组用户昵称 */
				if (!empty($oGrpApp->assigned_nickname)) {
					$oAssignedNickname = $oGrpApp->assignedNickname;
					if (isset($oAssignedNickname->valid) && $oAssignedNickname->valid === 'Y' && !empty($oAssignedNickname->schema->id)) {
						foreach ($users as $oUser) {
							if (!empty($oUser->data->{$oAssignedNickname->schema->id})) {
								$oUser->nickname = $oUser->data->{$oAssignedNickname->schema->id};
								$oUser->userAppData = $oUser->data;
								unset($oUser->data);
							}
						}
					}
				}
			}
			break;
		case 'enroll':
			$users = $this->model('matter\enroll\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname,comment,data userAppData', 'rid' => 'all', 'userid' => 'all']);
			if (count($users)) {
				foreach ($users as $oUser) {
					if (!empty($oUser->userAppData)) {
						$oUser->userAppData = json_decode($oUser->userAppData);
					}
				}
			}
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname,comment,data userAppData']);
			if (count($users)) {
				foreach ($users as $oUser) {
					if (!empty($oUser->userAppData)) {
						$oUser->userAppData = json_decode($oUser->userAppData);
					}
				}
			}
			break;
		case 'mschema':
			$users = $this->model('site\user\member')->byMschema($userSource->id, ['fields' => 'userid,name,email,mobile,extattr']);
			foreach ($users as &$oUser) {
				$oUser->nickname = empty($oUser->name) ? (empty($oUser->email) ? $oUser->mobile : $oUser->email) : $oUser->name;
				$oUser->userAppData = new \stdClass;
				$oUser->userAppData->name = $oUser->name;
				$oUser->userAppData->email = $oUser->email;
				$oUser->userAppData->mobile = $oUser->mobile;
				if (!empty($oUser->extattr)) {
					$extattrs = json_decode($oUser->extattr);
					foreach ($extattrs as $key => $extattr) {
						$oUser->userAppData->{$key} = $extattr;
					}
				}
			}
			break;
		}

		if (empty($users)) {
			return new \ParameterError('项目用户为空，无法显示用户数据');
		}
		/* 获得项目下的活动 */
		if (empty($posted->apps)) {
			/* 汇总报告配置信息 */
			$rpConfig = $this->model('matter\mission\report')->defaultConfigByUser($oLoginUser, $oMission);
			if (empty($rpConfig) || empty($rpConfig->include_apps)) {
				/* 如果没有指定 */
				$matters = $this->model('matter\mission\matter')->byMission($oMission->id);
				if (count($matters) === 0) {
					return new \ParameterError('没有获得项目中活动');
				}
				$apps = [];
				foreach ($matters as $oMatter) {
					if (in_array($oMatter->type, ['enroll', 'signin', 'group'])) {
						$apps[] = (object) ['id' => $oMatter->id, 'type' => $oMatter->type];
					}
				}
			} else {
				$apps = $rpConfig->include_apps;
			}
		} else {
			$apps = $posted->apps;
			/* 保留用户指定的查询参数 */
			$modelRp = $this->model('matter\mission\report');
			$modelRp->createConfig($oMission, $oLoginUser, ['asDefault' => 'Y', 'includeApps' => $apps]);
		}

		$modelRep = $this->model('matter\mission\report');
		$result = $modelRep->userAndApp($users, $apps);

		return $result;
	}
	/**
	 * 更新项目报告配置
	 */
	public function configUpdate_action($mission) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		$posted = $this->getPostJson();
		$apps = $posted->apps;
		/* 保留用户指定的查询参数 */
		$modelRp = $this->model('matter\mission\report');
		$oNewConfig = $modelRp->createConfig($oMission, $oUser, ['asDefault' => 'Y', 'includeApps' => $apps]);

		return new \ResponseData($oNewConfig);
	}
	/**
	 * 获得指定用户在项目中的行为记录
	 */
	public function recordByUser_action($mission, $user) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$result = new \stdClass;

		$modelEnlRec = $this->model('matter\enroll\record');
		$records = $modelEnlRec->byMission($mission, ['userid' => $user]);
		$result->enroll = $records;

		$modelSigRec = $this->model('matter\signin\record');
		$records = $modelSigRec->byMission($mission, ['userid' => $user]);
		$result->signin = $records;

		$modelGrpRec = $this->model('matter\group\player');
		$records = $modelGrpRec->byMission($mission, ['userid' => $user]);
		$result->group = $records;

		return new \ResponseData($result);
	}
	/**
	 * 导出项目汇总报告
	 */
	public function export_action($mission) {
		if (false === ($oLoginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		/* 获得用户 */
		$result = $this->userAndAppData($oLoginUser, $oMission);
		// var_dump($result);die;

		/*把result导出excel文件*/
		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator($oMission->creater_name)
			->setLastModifiedBy($oMission->creater_name)
			->setTitle($oMission->title)
			->setSubject($oMission->title)
			->setDescription($oMission->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();
		//第一行标题
		$columnNum1 = 0;
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '序号');
		$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '用户');

		foreach ($result->orderedApps as $app) {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, $app->title);
		}
		//循环每条统计
		$row = 1;
		$i = 1;
		foreach ($result->users as $rec) {
			$columnNum2 = 0;
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, ++$row, $i++);
			$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, !empty($rec->nickname) ? $rec->nickname : ('用户' . $rec->userid));
			foreach ($rec->data as $v) {
				if (is_object($v)) {
					if (isset($v->enroll_num)) {
						$content = [];
						if (!empty($v->enroll_num)) {
							$content[] = '记录：' . $v->enroll_num;
						}
						if (!empty($v->remark_other_num)) {
							$content[] = '评论：' . $v->remark_other_num;
						}
						$content = implode("\n ", $content);
					} else if (isset($v->signin_num)) {
						$content = '签到：' . $v->signin_num;
						isset($v->late_num) && $content .= "\n 迟到：" . $v->late_num;
					}
				} else if (is_array($v)) {
					if (!empty($v[0]->round_title)) {
						$content = '分组：' . $v[0]->round_title;
					} else {
						$content = '分组：空';
					}
				} else {
					$content = '';
				}

				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $content);
			}
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $oMission->title . '（汇总报告）.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;
	}
}