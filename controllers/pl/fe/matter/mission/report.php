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
	public function userAndApp_action($mission = '') {
		if (false === ($oLoginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (empty($mission)) {
			return new \ResponseError('未指定项目id');
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ResponseError('指定项目不存在');
		}

		$posted = $this->getPostJson();

		$result = $this->userAndAppData($oLoginUser, $oMission, $posted);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}

		return new \ResponseData($result[1]);
	}
	/**
	 * 获得用户指定app下的用户
	 */
	private function userAndAppData($oLoginUser, $oMission, $posted = '') {
		if (!isset($posted->userSource) || !isset($posted->userSource->type) || !isset($posted->userSource->id)) {
			if (isset($oMission->user_app_id) && isset($oMission->user_app_type)) {
				$userSource = new \stdClass;
				$userSource->id = $oMission->user_app_id;
				$userSource->type = $oMission->user_app_type;
			} else {
				return [false, '未找到用户名单'];
			}
		} else {
			$userSource = $posted->userSource;
		}

		$modelRp = $this->model('matter\mission\report');
		if (!empty($posted->defaultConfig->show_schema) || !empty($posted->defaultConfig->apps)) {
			$defaultConfig = $posted->defaultConfig;
			/* 保留用户指定的查询参数 */
			$modelRp->createConfig($oMission, $oLoginUser, ['asDefault' => 'Y', 'includeApps' => $defaultConfig]);
		} else {
			/* 汇总报告配置信息 */
			$rpConfig = $modelRp->defaultConfigByUser($oLoginUser, $oMission);
			if ($rpConfig !== false) {
				$defaultConfig = $rpConfig->include_apps;
			} else {
				$defaultConfig = new \stdClass;
				$defaultConfig->apps = [];
				$defaultConfig->show_schema = [];
			}
		}

		/* 获得项目下的活动 */
		$apps = $defaultConfig->apps;
		if (empty($apps)) {
			/* 如果没有指定 */
			$matters = $this->model('matter\mission\matter')->byMission($oMission->id);
			if (count($matters) === 0) {
				return [false, '未获得项目中活动'];
			}
			foreach ($matters as $oMatter) {
				if (in_array($oMatter->type, ['enroll', 'signin', 'group'])) {
					$apps[] = (object) ['id' => $oMatter->id, 'type' => $oMatter->type];
				}
			}
		}

		/* 获得用户 */
		switch ($userSource->type) {
		case 'group':
			$oGrpApp = $this->model('matter\group')->byId($userSource->id, ['fields' => 'assigned_nickname', 'cascaded' => 'N']);
			$users = $this->model('matter\group\player')->byApp($userSource, (object) ['fields' => 'userid,nickname,round_id,round_title,data show_schema_datas']);
			$users = isset($users->players) ? $users->players : [];
			if (count($users)) {
				foreach ($users as $oUser) {
					$show_schema_data = new \stdClass;
					if (!empty($oUser->show_schema_datas)) {
						$show_schema_datas = json_decode($oUser->show_schema_datas);
						/* 处理用户指定显示的列 */
						if (!empty($defaultConfig->show_schema)) {
							foreach ($defaultConfig->show_schema as $show_schema) {
								$show_schema_data->{$show_schema->id} = $show_schema_datas->{$show_schema->id};
							}
						} else {
							$show_schema_data = $show_schema_datas;
						}
					}
					$oUser->show_schema_data = $show_schema_data;
					/* 指定分组用户昵称 */
					if (!empty($oGrpApp->assigned_nickname)) {
						$oAssignedNickname = $oGrpApp->assignedNickname;
						if (isset($oAssignedNickname->valid) && $oAssignedNickname->valid === 'Y' && !empty($oAssignedNickname->schema->id)) {
							if (!empty($show_schema_datas->{$oAssignedNickname->schema->id})) {
								$oUser->nickname = $show_schema_datas->{$oAssignedNickname->schema->id};
							}
						}
					}
					unset($oUser->show_schema_datas);
				}
			}
			break;
		case 'enroll':
			$oResult = $this->model('matter\enroll\user')->enrolleeByApp($userSource, '', '', ['fields' => 'userid,nickname', 'cascaded' => 'N']);
			if (count($oResult->users)) {
				foreach ($oResult->users as $oUser) {
					$show_schema_data = new \stdClass;
					// if (!empty($oUser->show_schema_datas)) {
					// 	$show_schema_datas = json_decode($oUser->show_schema_datas);
					// 	/* 处理用户指定显示的列 */
					// 	if (!empty($defaultConfig->show_schema)) {
					// 		foreach ($defaultConfig->show_schema as $show_schema) {
					// 			if (strpos($show_schema->id, 'member') === 0) {
					// 				$schId = explode('.', $show_schema->id)[1];
					// 				if (!isset($show_schema_data->member) || !is_object($show_schema_data->member)) {
					// 					$show_schema_data->member = new \stdClass;
					// 				}
					// 				$show_schema_data->member->{$schId} = $show_schema_datas->member->{$schId};
					// 			} else {
					// 				$show_schema_data->{$show_schema->id} = $show_schema_datas->{$show_schema->id};
					// 			}
					// 		}
					// 	} else {
					// 		$show_schema_data = $show_schema_datas;
					// 	}
					// }
					$oUser->show_schema_data = $show_schema_data;
					//unset($oUser->show_schema_datas);
				}
			}
			break;
		case 'signin':
			$users = $this->model('matter\signin\record')->enrolleeByApp($userSource, ['fields' => 'distinct userid,nickname,data show_schema_datas']);
			if (count($users)) {
				foreach ($users as $oUser) {
					$show_schema_data = new \stdClass;
					if (!empty($oUser->show_schema_datas)) {
						$show_schema_datas = json_decode($oUser->show_schema_datas);
						/* 处理用户指定显示的列 */
						if (!empty($defaultConfig->show_schema)) {
							foreach ($defaultConfig->show_schema as $show_schema) {
								$show_schema_data->{$show_schema->id} = $show_schema_datas->{$show_schema->id};
							}
						} else {
							$show_schema_data = $show_schema_datas;
						}
					}
					$oUser->show_schema_data = $show_schema_data;
					unset($oUser->show_schema_datas);
				}
			}
			break;
		case 'mschema':
			$users = $this->model('site\user\member')->byMschema($userSource->id, ['fields' => 'userid,name,email,mobile,extattr']);
			foreach ($users as &$oUser) {
				$oUser->nickname = empty($oUser->name) ? (empty($oUser->email) ? $oUser->mobile : $oUser->email) : $oUser->name;
				$show_schema_data1 = new \stdClass;
				$show_schema_data1->name = $oUser->name;
				$show_schema_data1->email = $oUser->email;
				$show_schema_data1->mobile = $oUser->mobile;
				if (!empty($oUser->extattr)) {
					$extattrs = json_decode($oUser->extattr);
					foreach ($extattrs as $key => $extattr) {
						$show_schema_data1->{$key} = $extattr;
					}
				}
				$show_schema_data2 = new \stdClass;
				if (!empty($defaultConfig->show_schema)) {
					foreach ($defaultConfig->show_schema as $show_schema) {
						$show_schema_data2->{$show_schema->id} = $show_schema_data1->{$show_schema->id};
					}
				} else {
					$show_schema_data2 = $show_schema_data1;
				}
				$oUser->show_schema_data = $show_schema_data2;
			}
			break;
		}

		if (empty($users)) {
			return [false, '项目用户为空，无法显示用户数据'];
		}

		$modelRep = $this->model('matter\mission\report');
		if ($result = $modelRep->userAndApp($users, $apps)) {
			$result->show_schema = $defaultConfig->show_schema;
			$result->apps = $defaultConfig->apps;
		}

		return [true, $result];
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
	public function export_action($mission = '') {
		if (false === ($oLoginUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (empty($mission)) {
			return new \ResponseError('未指定项目id');
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($mission);
		if ($oMission === false) {
			return new \ObjectNotFoundError();
		}

		if ($oMission->user_app_id) {
			if ($oMission->user_app_type === 'group') {
				$oMission->userApp = $this->model('matter\group')->byId($oMission->user_app_id, ['cascaded' => 'N']);
			} else if ($oMission->user_app_type === 'enroll') {
				$oMission->userApp = $this->model('matter\enroll')->byId($oMission->user_app_id, ['cascaded' => 'N']);
			} else if ($oMission->user_app_type === 'signin') {
				$oMission->userApp = $this->model('matter\signin')->byId($oMission->user_app_id, ['cascaded' => 'N']);
			} else if ($oMission->user_app_type === 'mschema') {
				$oMission->userApp = $this->model('site\user\memberschema')->byId($oMission->user_app_id, ['cascaded' => 'N', 'fields' => 'siteid,id,title,create_at,start_at,end_at,url,attr_email,attr_mobile,attr_name,extattr']);
				$data_schemas = [];
				($oMission->userApp->attr_mobile[0] == '0') && $data_schemas[] = (object) ['id' => 'mobile', 'title' => '手机'];
				($oMission->userApp->attr_email[0] == '0') && $data_schemas[] = (object) ['id' => 'email', 'title' => '邮箱'];
				($oMission->userApp->attr_name[0] == '0') && $data_schemas[] = (object) ['id' => 'name', 'title' => '姓名'];
				if (!empty($oMission->userApp->extattr)) {
					$extattrs = $oMission->userApp->extattr;
					foreach ($extattrs as $extattr) {
						$data_schemas[] = (object) ['id' => $extattr->id, 'title' => $extattr->label];
					}
				}
				$oMission->userApp->dataSchemas = $data_schemas;
			}
		}

		/* 获得用户 */
		$result = $this->userAndAppData($oLoginUser, $oMission);
		if ($result[0] === false) {
			return new \ResponseError($result[1]);
		}
		$result = $result[1];
		if (empty($result->show_schema)) {
			$result->show_schema = $oMission->userApp->dataSchemas;
		}

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

		if ($oMission->user_app_type === 'group') {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, '分组');
		}
		foreach ($result->show_schema as $show_schema) {
			$objActiveSheet->setCellValueByColumnAndRow($columnNum1++, 1, $show_schema->title);
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
			if ($oMission->user_app_type === 'group') {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $rec->round_title);
			}
			foreach ($result->show_schema as $show_schema) {
				if ($show_schema->id === '_round_id') {
					$roundTitle = '';
					if (!empty($rec->show_schema_data->{$show_schema->id})) {
						$value = $rec->show_schema_data->{$show_schema->id};
						if (isset($show_schema->ops)) {
							$rounds = $show_schema->ops;
							foreach ($rounds as $round) {
								if ($round->v === $value) {
									$roundTitle = $round->l;
								}
							}
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $roundTitle);
				} else if (strpos($show_schema->id, 'member') === 0) {
					$schId = explode('.', $show_schema->id)[1];
					if (isset($rec->show_schema_data->member->{$schId})) {
						$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $rec->show_schema_data->member->{$schId});
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, '');
					}
				} else {
					if (isset($rec->show_schema_data->{$show_schema->id})) {
						$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $rec->show_schema_data->{$show_schema->id});
					} else {
						$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, '');
					}
				}
			}
			if (!empty($rec->data)) {
				foreach ($rec->data as $v) {
					if (is_object($v)) {
						if (isset($v->enroll_num)) {
							$content = [];
							if (!empty($v->enroll_num)) {
								$content[] = '记录：' . $v->enroll_num;
							}
							if (!empty($v->do_remark_num)) {
								$content[] = "\n 留言：" . $v->do_remark_num;
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
						$content = '';
						foreach ($v as $k => $val) {
							if (!empty($val->round_title)) {
								$content .= '分组：' . $val->round_title;
								if (!empty($val->comment)) {
									$content .= "\n 备注：" . $val->comment;
								}
							} else {
								$content = '分组：空';
								if (!empty($val->comment)) {
									$content .= "\n 备注：" . $val->comment;
								}
							}
						}
					} else {
						$content = '';
					}

					$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, $content);
				}
			} else {
				$objActiveSheet->setCellValueByColumnAndRow($columnNum2++, $row, '');
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