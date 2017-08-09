<?php
namespace pl\fe\matter\group;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 分组活动控制器
 */
class player extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/group/frame');
		exit;
	}
	/**
	 * 查看分组数据
	 */
	public function list_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);
		$result = $modelPlayer->byApp($app);

		return new \ResponseData($result);
	}
	/**
	 * 导出分组数据
	 */
	public function export_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelGrp = $this->model('matter\group');
		$modelPlayer = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);
		$schemas = json_decode($app->data_schemas);
		$result = $modelPlayer->byApp($app);
		if ($result->total == 0) {
			die('player empty');
		}
		$players = $result->players;

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';

		// Create new PHPExcel object
		$objPHPExcel = new \PHPExcel();
		// Set properties
		$objPHPExcel->getProperties()->setCreator("信信通")
			->setLastModifiedBy("信信通")
			->setTitle($app->title)
			->setSubject($app->title)
			->setDescription($app->title);

		$objActiveSheet = $objPHPExcel->getActiveSheet();

		$colNumber = 0;
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '分组');

		// 转换标题
		foreach ($schemas as $schema) {
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, $schema->title);
		}
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '备注');
		$objActiveSheet->setCellValueByColumnAndRow($colNumber++, 1, '标签');

		// 转换数据
		$rowNumber = 2;
		foreach ($players as $player) {
			$colNumber = 0;
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $player->round_title);
			// 处理登记项
			$data = (object) $player->data;
			foreach ($schemas as $schema) {
				$v = isset($data->{$schema->id}) ? $data->{$schema->id} : '';
				switch ($schema->type) {
				case 'single':
				case 'phase':
					foreach ($schema->ops as $op) {
						if ($op->v === $v) {
							$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $op->l);
							$disposed = true;
							break;
						}
					}
					empty($disposed) && $objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				case 'multiple':
					$labels = [];
					$v = explode(',', $v);
					foreach ($v as $oneV) {
						foreach ($schema->ops as $op) {
							if ($op->v === $oneV) {
								$labels[] = $op->l;
								break;
							}
						}
					}
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, implode(',', $labels));
					break;
				default:
					$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, $v);
					break;
				}
			}
			// 备注
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, empty($player->comment) ? '' : $player->comment);
			// 标签
			$objActiveSheet->setCellValueByColumnAndRow($colNumber++, $rowNumber, empty($player->tags) ? '' : $player->tags);

			// next row
			$rowNumber++;
		}

		// 输出
		header('Content-Type: application/vnd.ms-excel');
		header('Content-Disposition: attachment;filename="' . $app->title . '.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');

		exit;
	}
	/**
	 * 分组用户数量
	 */
	public function count_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$q = array(
			'count(*)',
			"xxt_group_player",
			"aid='$app' and state=1",
		);

		$cnt = $this->model()->query_val_ss($q);

		return new \ResponseData($cnt);
	}
	/**
	 * 从其他活动导入数据
	 */
	public function importByApp_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$sourceApp = null;
		$params = $this->getPostJson();

		if (!empty($params->app)) {
			if ($params->appType === 'registration') {
				$sourceApp = $this->_importByEnroll($site, $app, $params->app);
			} else if ($params->appType === 'signin') {
				$sourceApp = $this->_importBySignin($site, $app, $params);
			} else if ($params->appType === 'wall') {
				$sourceApp = $this->_importByWall($site, $app, $params->app, $params->onlySpeaker);
			} else if ($params->appType === 'mschema') {
				$sourceApp = $this->_importByMschema($app, $params->app);
			}
		}

		return new \ResponseData($sourceApp);
	}
	/**
	 * 从关联活动同步数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	public function syncByApp_action($site, $app, $onlySpeaker = 'N') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$count = 0;
		$modelGrp = $this->model('matter\group');
		$app = $modelGrp->byId($app, array('cascaded' => 'N'));
		if (!empty($app->source_app)) {
			$sourceApp = json_decode($app->source_app);
			if ($sourceApp->type === 'enroll') {
				$count = $this->_syncByEnroll($site, $app, $sourceApp->id);
			} else if ($sourceApp->type === 'signin') {
				$count = $this->_syncBySignin($site, $app, $sourceApp->id);
			} else if ($sourceApp->type === 'wall') {
				$count = $this->_syncByWall($site, $app, $sourceApp->id, $onlySpeaker);
			} else if ($sourceApp->type === 'mschema') {
				$count = $this->_syncByMschema($app, $sourceApp->id);
			}
			// 更新同步时间
			$modelGrp->update(
				'xxt_group',
				['last_sync_at' => time()],
				['id' => $app->id]
			);
		}

		return new \ResponseData($count);
	}
	/**
	 * 从报名活动导入数据
	 */
	private function &_importByMschema($groupId, $mschemaId, $sync = 'N') {
		$modelGrp = $this->model('matter\group');
		$modelGrp->setOnlyWriteDbConn(true);
		$modelPly = $this->model('matter\group\player');
		$modelMsc = $this->model('site\user\memberschema');

		$oMschema = $modelMsc->byId($mschemaId);
		$dataSchemas = [];
		if ($oMschema->attr_mobile[0] === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'ms_' . $mschemaId . '_mobile';
			$dataSchema->type = 'shorttext';
			$dataSchema->title = '手机号';
			$dataSchema->format = 'mobile';
			$dataSchemas[] = $dataSchema;
		}
		if ($oMschema->attr_mobile[0] === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'ms_' . $mschemaId . '_email';
			$dataSchema->type = 'shorttext';
			$dataSchema->title = '电子邮件';
			$dataSchema->format = 'email';
			$dataSchemas[] = $dataSchema;
		}
		if ($oMschema->attr_mobile[0] === '0') {
			$dataSchema = new \stdClass;
			$dataSchema->id = 'ms_' . $mschemaId . '_name';
			$dataSchema->type = 'shorttext';
			$dataSchema->title = '姓名';
			$dataSchema->format = 'name';
			$dataSchemas[] = $dataSchema;
		}
		$extDataSchemas = [];
		if (!empty($oMschema->extattr)) {
			foreach ($oMschema->extattr as $ea) {
				$dataSchema = new \stdClass;
				$dataSchema->id = $ea->id;
				$dataSchema->type = 'shorttext';
				$dataSchema->title = $ea->label;
				$extDataSchemas[] = $dataSchema;
			}
		}

		$oMschema->data_schemas = array_merge($dataSchemas, $extDataSchemas);

		/* 导入活动定义 */
		$modelGrp->update(
			'xxt_group',
			[
				'last_sync_at' => time(),
				'source_app' => '{"id":"' . $mschemaId . '","type":"mschema"}',
				'data_schemas' => $modelGrp->escape($modelGrp->toJson($oMschema->data_schemas)),
			],
			['id' => $groupId]
		);
		/* 清空已有分组数据 */
		$modelPly->clean($groupId, true);
		/* 获取所有登记数据 */
		$modelMem = $this->model('site\user\member');
		$members = $modelMem->byMschema($mschemaId);
		/* 导入数据 */
		if (count($members)) {
			$objGrp = $modelGrp->byId($groupId, ['cascaded' => 'N']);
			$options = ['cascaded' => 'Y'];
			$modelUsr = $this->model('site\user\account');
			foreach ($members as $oMember) {
				$oSiteUser = $modelUsr->byId($oMember->userid);
				$user = new \stdClass;
				$user->uid = $oMember->userid;
				$user->nickname = $modelUsr->escape($oSiteUser->nickname);
				$user->wx_openid = $oSiteUser->wx_openid;
				$user->yx_openid = $oSiteUser->yx_openid;
				$user->qy_openid = $oSiteUser->qy_openid;
				$user->headimgurl = $oSiteUser->headimgurl;
				$modelPly->enroll($objGrp->siteid, $objGrp, $user, ['enroll_key' => $oMember->id, 'enroll_at' => $oMember->create_at]);
				$data = new \stdClass;
				foreach ($dataSchemas as $ds) {
					$data->{$ds->id} = isset($oMember->{$ds->format}) ? $oMember->{$ds->format} : '';
				}
				if (count($extDataSchemas) && !empty($oMember->extattr)) {
					$oExtData = json_decode($oMember->extattr);
					foreach ($extDataSchemas as $ds) {
						$data->{$ds->id} = isset($oExtData->{$ds->id}) ? $oExtData->{$ds->id} : '';
					}
				}
				$modelPly->setData($objGrp->siteid, $objGrp, $oMember->id, $data);
			}
		}

		return $oMschema;
	}
	/**
	 * 从报名活动导入数据
	 */
	private function &_importByEnroll($site, $app, $byApp, $sync = 'N') {
		$modelGrp = $this->model('matter\group');
		$modelGrp->setOnlyWriteDbConn(true);
		$modelPly = $this->model('matter\group\player');
		$modelEnl = $this->model('matter\enroll');

		$sourceApp = $modelEnl->byId($byApp, ['fields' => 'data_schemas', 'cascaded' => 'N']);
		/* 导入活动定义 */
		$modelGrp->update(
			'xxt_group',
			[
				'last_sync_at' => time(),
				'source_app' => '{"id":"' . $byApp . '","type":"enroll"}',
				'data_schemas' => $sourceApp->data_schemas,
			],
			['id' => $app]
		);
		/* 清空已有分组数据 */
		$modelPly->clean($app, true);
		/* 获取所有登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = [
			'enroll_key',
			'xxt_enroll_record',
			"aid='$byApp' and state=1",
		];
		$eks = $modelRec->query_vals_ss($q);
		/* 导入数据 */
		if (!empty($eks)) {
			$objGrp = $modelGrp->byId($app, ['cascaded' => 'N']);
			$options = ['cascaded' => 'Y'];
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek, $options);
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$user->wx_openid = $record->wx_openid;
				$user->yx_openid = $record->yx_openid;
				$user->qy_openid = $record->qy_openid;
				$user->headimgurl = $record->headimgurl;
				$modelPly->enroll($site, $objGrp, $user, ['enroll_key' => $ek, 'enroll_at' => $record->enroll_at]);
				$modelPly->setData($site, $objGrp, $ek, $record->data);
			}
		}

		return $sourceApp;
	}
	/**
	 * 从签到活动导入数据
	 * 如果指定了包括报名数据，只需要从报名活动中导入登记项的定义，签到时已经自动包含了报名数据
	 */
	private function &_importBySignin($site, $app, &$params) {
		$byApp = $params->app;
		$modelGrp = $this->model('matter\group');
		$modelGrp->setOnlyWriteDbConn(true);
		$modelPly = $this->model('matter\group\player');
		$modelSignin = $this->model('matter\signin');

		$sourceApp = $modelSignin->byId($byApp, ['fields' => 'data_schemas,enroll_app_id', 'cascaded' => 'N']);
		$sourceDataSchemas = $sourceApp->data_schemas;
		/**
		 * 导入报名数据，需要合并签到和报名的登记项
		 */
		if (isset($params->includeEnroll) && $params->includeEnroll === 'Y') {
			if (!empty($sourceApp->enroll_app_id)) {
				$modelEnl = $this->model('matter\enroll');
				$enrollApp = $modelEnl->byId($sourceApp->enroll_app_id, ['fields' => 'data_schemas', 'cascaded' => 'N']);
				$enrollDataSchemas = json_decode($enrollApp->data_schemas);
				$sourceDataSchemas = json_decode($sourceDataSchemas);
				$diff = array_udiff($enrollDataSchemas, $sourceDataSchemas, create_function('$a,$b', 'return strcmp($a->id,$b->id);'));
				$sourceDataSchemas = array_merge($sourceDataSchemas, $diff);
				$sourceDataSchemas = $modelGrp->toJson($sourceDataSchemas);
			}
		}
		/* 导入活动定义 */
		$modelGrp->update(
			'xxt_group',
			[
				'last_sync_at' => time(),
				'source_app' => '{"id":"' . $byApp . '","type":"signin"}',
				'data_schemas' => $sourceDataSchemas,
			],
			['id' => $app]
		);
		/* 清空已有数据 */
		$modelPly->clean($app, true);
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = [
			'enroll_key',
			'xxt_signin_record',
			"aid='$byApp' and state=1",
		];
		$eks = $modelRec->query_vals_ss($q);
		/* 导入数据 */
		if (!empty($eks)) {
			$objGrp = $modelGrp->byId($app, array('cascaded' => 'N'));
			$options = array('cascaded' => 'Y');
			foreach ($eks as $ek) {
				$record = $modelRec->byId($ek, $options);
				$user = new \stdClass;
				$user->uid = $record->userid;
				$user->nickname = $record->nickname;
				$user->wx_openid = $record->wx_openid;
				$user->yx_openid = $record->yx_openid;
				$user->qy_openid = $record->qy_openid;
				$user->headimgurl = $record->headimgurl;
				$modelPly->enroll($site, $objGrp, $user, ['enroll_key' => $ek, 'enroll_at' => $record->enroll_at]);
				$modelPly->setData($site, $objGrp, $ek, $record->data);
			}
		}

		return $sourceApp;
	}
	/**
	 * 从信息墙导入数据
	 * $onlySpeaker 是否为发言的用户
	 */
	private function &_importByWall($site, $app, $byApp, $onlySpeaker) {
		$modelGrp = $this->model('matter\group');
		$modelGrp->setOnlyWriteDbConn(true);
		$modelPly = $this->model('matter\group\player');
		$modelWall = $this->model('matter\wall');

		$sourceApp = $modelWall->byId($byApp, ['fields' => 'data_schemas']);
		/* 导入活动定义 */
		$modelGrp->update(
			'xxt_group',
			[
				'last_sync_at' => time(),
				'source_app' => '{"id":"' . $byApp . '","type":"wall"}',
				'data_schemas' => $sourceApp->data_schemas,
			],
			['id' => $app]
		);
		/* 清空已有分组数据 */
		$modelPly->clean($app, true);
		//获取所有用户数据
		$u = array(
			'*',
			'xxt_wall_enroll',
			"wid = '{$byApp}' and siteid = '{$site}'",
		);
		if ($onlySpeaker === 'Y') {
			$u[2] .= " and last_msg_at>0";
		}
		$wallUsers = $this->model()->query_objs_ss($u);

		/* 导入数据 */
		if (!empty($wallUsers)) {
			$objGrp = $modelGrp->byId($app, ['cascaded' => 'N']);
			foreach ($wallUsers as $wallUser) {
				$wallUser->data = empty($wallUser->data) ? '' : json_decode($wallUser->data);
				$user = new \stdClass;
				$user->uid = $wallUser->userid;
				$user->nickname = $wallUser->nickname;
				$user->wx_openid = $wallUser->wx_openid;
				$user->yx_openid = $wallUser->yx_openid;
				$user->qy_openid = $wallUser->qy_openid;
				$user->headimgurl = $wallUser->headimgurl;
				if (empty($wallUser->enroll_key)) {
					$ek = $modelPly->genKey($site, $app);
					$wallUser->enroll_key = $ek;
				}
				$modelPly->enroll($site, $objGrp, $user, ['enroll_key' => $wallUser->enroll_key, 'enroll_at' => $wallUser->join_at]);
				$modelPly->setData($site, $objGrp, $wallUser->enroll_key, $wallUser->data);
			}
		}

		$sourceApp->onlySpeaker = $onlySpeaker;
		return $sourceApp;
	}
	/**
	 * 从通讯录联系人中导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncByMschema(&$objGrp, $mschemaId) {
		/* 获取变化的登记数据 */
		$modelMem = $this->model('site\user\member');
		$q = [
			'*',
			'xxt_site_member',
			"schema_id='$mschemaId' and (modify_at>{$objGrp->last_sync_at} or forbidden='Y')",
		];
		$members = $modelMem->query_objs_ss($q);

		$cnt = 0;
		if (!empty($members)) {
			$modelPly = $this->model('matter\group\player');
			$modelUsr = $this->model('site\user\account');
			$options = ['cascaded' => 'Y'];
			foreach ($members as $oMember) {
				if ($oMember->forbidden === 'Y') {
					// 删除用户
					if ($modelPly->remove($objGrp->id, $oMember->id, true)) {
						$cnt++;
					}
				} else {
					$dataSchemas = json_decode($objGrp->data_schemas);
					$oNewData = new \stdClass;
					$oExtData = empty($oMember->extattr) ? new \stdClass : json_decode($oMember->extattr);
					foreach ($dataSchemas as $ds) {
						if (isset($ds->format)) {
							$oNewData->{$ds->id} = isset($oMember->{$ds->format}) ? $oMember->{$ds->format} : '';
						} else {
							$oNewData->{$ds->id} = isset($oExtData->{$ds->id}) ? $oExtData->{$ds->id} : '';
						}
					}
					if ($modelPly->byId($objGrp->id, $oMember->id, ['cascaded' => 'N'])) {
						// 已经同步过的用户
						$modelPly->setData($objGrp->siteid, $objGrp, $oMember->id, $oNewData);
					} else {
						$oSiteUser = $modelUsr->byId($oMember->userid);
						$user = new \stdClass;
						$user->uid = $oMember->userid;
						$user->nickname = $modelUsr->escape($oSiteUser->nickname);
						$user->wx_openid = $oSiteUser->wx_openid;
						$user->yx_openid = $oSiteUser->yx_openid;
						$user->qy_openid = $oSiteUser->qy_openid;
						$user->headimgurl = $oSiteUser->headimgurl;
						// 新用户
						$modelPly->enroll($objGrp->siteid, $objGrp, $user, ['enroll_key' => $oMember->id, 'enroll_at' => $oMember->create_at]);
						$modelPly->setData($objGrp->siteid, $objGrp, $oMember->id, $oNewData);
					}
					$cnt++;
				}
			}
		}

		return $cnt;
	}
	/**
	 * 从登记活动导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncByEnroll($siteId, &$objGrp, $byApp) {
		/* 获取变化的登记数据 */
		$modelRec = $this->model('matter\enroll\record');
		$q = [
			'enroll_key,state',
			'xxt_enroll_record',
			"aid='$byApp' and (enroll_at>{$objGrp->last_sync_at} or state<>1)",
		];
		$records = $modelRec->query_objs_ss($q);

		return $this->_syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 从签到活动导入数据
	 *
	 * 同步在最后一次同步之后的数据或已经删除的数据
	 */
	private function _syncBySignin($siteId, &$objGrp, $byApp) {
		/* 获取数据 */
		$modelRec = $this->model('matter\signin\record');
		$q = array(
			'enroll_key,state',
			'xxt_signin_record',
			"aid='$byApp' and (enroll_at>{$objGrp->last_sync_at} or state<>1)",
		);
		$records = $modelRec->query_objs_ss($q);

		return $this->_syncRecord($siteId, $objGrp, $records, $modelRec);
	}
	/**
	 * 同步在最后一次同步之后的数据
	 * $onlySpeaker 是否为发言的用户
	 */
	private function _syncByWall($siteId, &$objGrp, $byApp, $onlySpeaker) {
		//获取新增用户数据
		$u = array(
			'*',
			'xxt_wall_enroll',
			"wid = '{$byApp}' and siteid = '{$siteId}' and join_at > {$objGrp->last_sync_at} ",
		);
		if ($onlySpeaker === 'Y') {
			$u[2] .= " and last_msg_at>0";
		}
		$wallUsers = $this->model()->query_objs_ss($u);

		$modelPly = $this->model('matter\group\player');
		if (!empty($wallUsers)) {
			foreach ($wallUsers as $wallUser) {
				$wallUser->data = empty($wallUser->data) ? '' : json_decode($wallUser->data);
				$user = new \stdClass;
				$user->uid = $wallUser->userid;
				$user->nickname = $wallUser->nickname;
				$user->wx_openid = $wallUser->wx_openid;
				$user->yx_openid = $wallUser->yx_openid;
				$user->qy_openid = $wallUser->qy_openid;
				$user->headimgurl = $wallUser->headimgurl;
				if ($modelPly->byId($objGrp->id, $wallUser->enroll_key, ['cascaded' => 'N'])) {
					// 已经同步过的用户
					$modelPly->setData($siteId, $objGrp, $wallUser->enroll_key, $wallUser->data);
				} else {
					// 新用户
					$modelPly->enroll($siteId, $objGrp, $user, ['enroll_key' => $wallUser->enroll_key, 'enroll_at' => $wallUser->join_at]);
					$modelPly->setData($siteId, $objGrp, $wallUser->enroll_key, $wallUser->data);
				}
			}
		}

		return count($wallUsers);
	}
	/**
	 * 同步数据
	 */
	private function _syncRecord($siteId, &$objGrp, &$records, &$modelRec) {
		$cnt = 0;
		$modelPly = $this->model('matter\group\player');
		if (!empty($records)) {
			$options = ['cascaded' => 'Y'];
			foreach ($records as $record) {
				if ($record->state === '1') {
					$record = $modelRec->byId($record->enroll_key, $options);
					$user = new \stdClass;
					$user->uid = $record->userid;
					$user->nickname = $record->nickname;
					$user->wx_openid = $record->wx_openid;
					$user->yx_openid = $record->yx_openid;
					$user->qy_openid = $record->qy_openid;
					$user->headimgurl = $record->headimgurl;
					if ($modelPly->byId($objGrp->id, $record->enroll_key, ['cascaded' => 'N'])) {
						// 已经同步过的用户
						$modelPly->setData($siteId, $objGrp, $record->enroll_key, $record->data);
					} else {
						// 新用户
						$modelPly->enroll($siteId, $objGrp, $user, ['enroll_key' => $record->enroll_key, 'enroll_at' => $record->enroll_at]);
						$modelPly->setData($siteId, $objGrp, $record->enroll_key, $record->data);
					}
					$cnt++;
				} else {
					// 删除用户
					if ($modelPly->remove($objGrp->id, $record->enroll_key, true)) {
						$cnt++;
					}
				}
			}
		}

		return $cnt;
	}
	/**
	 * 手工添加分组用户信息
	 *
	 * @param string $aid
	 */
	public function add_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$posted = $this->getPostJson();
		$current = time();
		$modelGrp = $this->model('matter\group');
		$modelPly = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);
		$ek = $modelPly->genKey($site, $app->id);
		/**
		 * 分组用户登记数据
		 */
		$user = new \stdClass;
		$user->uid = '';
		$user->nickname = '';
		$user->wx_openid = '';
		$user->yx_openid = '';
		$user->qy_openid = '';
		$user->headimgurl = '';

		$player = new \stdClass;
		$player->enroll_key = $ek;
		$player->enroll_at = $current;
		$player->comment = isset($posted->comment) ? $posted->comment : '';
		if (isset($posted->tags)) {
			$player->tags = $posted->tags;
			$modelGrp->updateTags($app->id, $posted->tags);
		}
		if (!empty($posted->round_id)) {
			$modelRnd = $this->model('matter\group\round');
			$round = $modelRnd->byId($posted->round_id);
			$player->round_id = $posted->round_id;
			$player->round_title = $round->title;
		}

		$modelPly->enroll($site, $app, $user, $player);
		$result = $modelPly->setData($site, $app, $ek, $posted->data);
		if (false === $result[0]) {
			return new \ResponseError($result[1]);
		}
		$player->data = json_decode($result[1]);

		return new \ResponseData($player);
	}
	/**
	 * 更新登记记录
	 *
	 * @param string $site
	 * @param string $app
	 * @param $ek record's key
	 */
	public function update_action($site, $app, $ek) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$player = $this->getPostJson();
		$modelGrp = $this->model('matter\group');
		$modelPly = $this->model('matter\group\player');

		$app = $modelGrp->byId($app);

		/* 更新记录数据 */
		$record = new \stdClass;
		if (isset($player->is_leader)) {
			$record->is_leader = $player->is_leader === 'Y' ? 'Y' : 'N';
		}
		if (isset($player->comment)) {
			$record->comment = $player->comment;
		}
		if (isset($player->tags)) {
			$record->tags = $player->tags;
		}
		if (empty($player->round_id)) {
			$record->round_id = 0;
			$record->round_title = '';
		} else {
			$modelRnd = $this->model('matter\group\round');
			if ($round = $modelRnd->byId($player->round_id)) {
				$record->round_id = $player->round_id;
				$record->round_title = $round->title;
			}
		}
		$modelPly->update(
			'xxt_group_player',
			$record,
			["aid" => $app->id, "enroll_key" => $ek]
		);
		/* 更新登记数据 */
		$result = $modelPly->setData($site, $app, $ek, $player->data);
		if (false === $result[0]) {
			return new \ResponseError($result[1]);
		}
		$player = $modelPly->byId($app->id, $ek);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $user, $app, 'update', $player);

		return new \ResponseData($player);
	}
	/**
	 * 未分组的人
	 */
	public function pendingsGet_action($app, $rid = null) {
		$result = $this->model('matter\group\player')->pendings($app);

		return new \ResponseData($result);
	}
	/**
	 * 清空一条登记信息
	 */
	public function remove_action($site, $app, $ek, $keepData = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\group\player')->remove($app, $ek, $keepData === 'N');

		return new \ResponseData($rst);
	}
	/**
	 * 清空登记信息
	 */
	public function empty_action($site, $app, $keepData = 'Y') {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$rst = $this->model('matter\group\player')->clean($app, $keepData === 'N');

		return new \ResponseData($rst);
	}
	/**
	 * 将用户移出分组
	 */
	public function quitGroup_action($site, $app) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$eks = $this->getPostJson();
		if (empty($eks)) {
			return new \ResponseError('没有指定用户');
		}

		$result = new \stdClass;
		$modelPly = $this->model('matter\group\player');
		foreach ($eks as $ek) {
			if ($player = $modelPly->byId($app, $ek)) {
				if ($modelPly->quitGroup($app, $ek)) {
					$result->{$ek} = $player->round_id;
				} else {
					$result->{$ek} = false;
				}
			} else {
				$result->{$ek} = false;
			}
		}

		// 记录操作日志
		$app = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		$this->model('matter\log')->matterOp($site, $user, $app, 'quitGroup', $result);

		return new \ResponseData($result);
	}
	/**
	 * 将用户移入分组
	 */
	public function joinGroup_action($site, $app, $round) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$eks = $this->getPostJson();
		if (empty($eks)) {
			return new \ResponseError('没有指定用户');
		}

		$round = $this->model('matter\group\round')->byId($round);

		$result = new \stdClass;
		$modelPly = $this->model('matter\group\player');
		foreach ($eks as $ek) {
			if ($player = $modelPly->byId($app, $ek)) {
				if ($modelPly->joinGroup($app, $round, $ek)) {
					$result->{$ek} = $round->round_id;
				} else {
					$result->{$ek} = false;
				}
			} else {
				$result->{$ek} = false;
			}
		}

		// 记录操作日志
		$app = $this->model('matter\group')->byId($app, ['cascaded' => 'N']);
		$this->model('matter\log')->matterOp($site, $user, $app, 'joinGroup', $result);

		return new \ResponseData($result);
	}
}