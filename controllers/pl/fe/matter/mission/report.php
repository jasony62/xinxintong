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
			$users = $this->model('matter\group\player')->byApp($userSource, (object) ['fields' => 'userid,nickname,round_id,round_title,data show_schema_data']);
			$users = isset($users->players) ? $users->players : [];
			if (count($users)) {
				foreach ($users as $oUser) {
					if (!empty($oUser->show_schema_data)) {
						$oUser->show_schema_data = json_decode($oUser->show_schema_data);
					}
				}
				/* 指定分组用户昵称 */
				if (!empty($oGrpApp->assigned_nickname)) {
					$oAssignedNickname = $oGrpApp->assignedNickname;
					if (isset($oAssignedNickname->valid) && $oAssignedNickname->valid === 'Y' && !empty($oAssignedNickname->schema->id)) {
						foreach ($users as $oUser) {
							if (!empty($oUser->show_schema_data->{$oAssignedNickname->schema->id})) {
								$oUser->nickname = $oUser->show_schema_data->{$oAssignedNickname->schema->id};
							}
						}
					}
				}
			}
			break;
		case 'enroll':
			$users = $this->model('matter\enroll\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname,data show_schema_data', 'rid' => 'all', 'userid' => 'all']);
			if (count($users)) {
				foreach ($users as $oUser) {
					if (!empty($oUser->show_schema_data)) {
						$oUser->show_schema_data = json_decode($oUser->show_schema_data);
					}
				}
			}
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname,data show_schema_data']);
			if (count($users)) {
				foreach ($users as $oUser) {
					if (!empty($oUser->show_schema_data)) {
						$oUser->show_schema_data = json_decode($oUser->show_schema_data);
					}
				}
			}
			break;
		case 'mschema':
			$users = $this->model('site\user\member')->byMschema($userSource->id, ['fields' => 'userid,name,email,mobile,extattr']);
			foreach ($users as &$oUser) {
				$oUser->nickname = empty($oUser->name) ? (empty($oUser->email) ? $oUser->mobile : $oUser->email) : $oUser->name;
				$oUser->show_schema_data = new \stdClass;
				$oUser->show_schema_data->name = $oUser->name;
				$oUser->show_schema_data->email = $oUser->email;
				$oUser->show_schema_data->mobile = $oUser->mobile;
				if (!empty($oUser->extattr)) {
					$extattrs = json_decode($oUser->extattr);
					foreach ($extattrs as $key => $extattr) {
						$oUser->show_schema_data->{$key} = $extattr;
					}
				}
			}
			break;
		}

		if (empty($users)) {
			return new \ParameterError('项目用户为空，无法显示用户数据');
		}
		/* 获得项目下的活动 */
		if (empty($posted->defaultConfig->apps)) {
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
				$defaultConfig = new \stdClass;
				$defaultConfig->apps = $apps;
			} else {
				$defaultConfig = $rpConfig->include_apps;
				$apps = $defaultConfig->apps;
			}
		} else {
			$defaultConfig = $posted->defaultConfig;
			/* 保留用户指定的查询参数 */
			$modelRp = $this->model('matter\mission\report');
			$modelRp->createConfig($oMission, $oLoginUser, ['asDefault' => 'Y', 'includeApps' => $defaultConfig]);
			$apps = $defaultConfig->apps;
		}

		$modelRep = $this->model('matter\mission\report');
		$result = $modelRep->userAndApp($users, $apps);
		$result->show_schema = empty($defaultConfig->show_schema) ? new \stdClass : $defaultConfig->show_schema;

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

		if (!empty($result->show_schema)) {
			foreach ($result->show_schema as $show_schema) {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, $show_schema->title);
			}
		}

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
			
			if (!empty($result->show_schema)) {
				foreach ($result->show_schema as $show_schema) {
					if ($show_schema->id === '_round_id') {
						$value = $rec->show_schema_data->{$show_schema->id};
						$rounds = $show_schema->ops;
						$roundTitle = '';
						foreach ($rounds as $round) {
							if ($round->v === $value) {
								$roundTitle = $round->l;
							}
						}
						$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $roundTitle);
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $rec->show_schema_data->{$show_schema->id});
					}
				}
			}

			foreach ($rec->data as $v) {
				if (is_object($v)) {
					if (isset($v->enroll_num)) {
						$content = [];
						if (!empty($v->enroll_num)) {
							$content[] = '记录：' . $v->enroll_num;
						}
						if (!empty($v->remark_other_num)) {
							$content[] = "\n 评论：" . $v->remark_other_num;
						}
						$content = implode("\n ", $content);
					} else if (isset($v->signin_num)) {
						$content = '签到：' . $v->signin_num;
						isset($v->late_num) && $content .= "\n 迟到：" . $v->late_num;
					}
					if (isset($v->comment) && !empty($v->comment)) {
						$content .= "\n 备注：" . $v->comment;
					}
				} else if (is_array($v)) {
					if (!empty($v[0]->round_title)) {
						$content = '分组：' . $v[0]->round_title;
					} else {
						$content = '分组：空';
					}
					if (isset($v[0]->comment) && !empty($v[0]->comment)) {
						$content .= "\n 备注：" . $v[0]->comment;
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