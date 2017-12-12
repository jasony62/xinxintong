<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/base.php';
/*
 * 项目控制器
 */
class main extends \pl\fe\matter\base {
	/**
	 * 返回视图
	 */
	public function index_action() {
		\TPL::output('/pl/fe/matter/mission/frame');
		exit;
	}
	/**
	 *
	 */
	public function invite_action() {
		\TPL::output('/pl/fe/matter/mission/invite');
		exit;
	}
	/**
	 * 获得指定的项目
	 *
	 * @param int $id
	 */
	public function get_action($id, $cascaded = 'Y') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		/* 检查权限 */
		$modelAcl = $this->model('matter\mission\acl');
		if (false === ($acl = $modelAcl->byCoworker($id, $oUser->id))) {
			return new \ResponseError('项目不存在');
		}
		$oMission = $this->model('matter\mission')->byId($id, ['cascaded' => ($cascaded === 'Y' ? 'header_page_name,footer_page_name,phase' : '')]);
		if ($cascaded === 'Y') {
			/* 关联的用户名单活动 */
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
			/* 汇总报告配置信息 */
			$rpConfig = $this->model('matter\mission\report')->defaultConfigByUser($oUser, $oMission);
			$oMission->reportConfig = $rpConfig;
		}

		/* 检查当前用户的角色 */
		if ($oUser->id === $oMission->creater) {
			$oMission->yourRole = 'O';
		} else {
			$oMission->yourRole = $acl->coworker_role;
		}

		return new \ResponseData($oMission);
	}
	/**
	 * 指定团队下当前用户可访问任务列表
	 *
	 * @param int $page
	 * @param int $size
	 */
	public function list_action($site, $page = 1, $size = 20) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$filter = $this->getPostJson();
		$modelMis = $this->model('matter\mission');
		$aOptions = [
			'limit' => (object) ['page' => $page, 'size' => $size],
		];
		if (!empty($filter->byTitle)) {
			$aOptions['byTitle'] = $modelMis->escape($filter->byTitle);
		}
		$site = $modelMis->escape($site);
		$result = $modelMis->bySite($site, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 * 当前用户可访问项目列表
	 *
	 * @param int $page
	 * @param int $size
	 */
	public function listByUser_action($page = 1, $size = 20) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oFilter = $this->getPostJson();
		$modelMis = $this->model('matter\mission');
		$aOptions = [
			'limit' => (object) ['page' => $page, 'size' => $size],
		];
		if (!empty($oFilter->bySite)) {
			$aOptions['bySite'] = $oFilter->bySite;
		}
		if (!empty($oFilter->filter->by) && !empty($oFilter->filter->keyword)) {
			if ($oFilter->filter->by === 'title') {
				$aOptions['byTitle'] = $oFilter->filter->keyword;
			}
		}
		if (isset($oFilter->byStar) && $oFilter->byStar === 'Y') {
			$aOptions['byStar'] = 'Y';
		}
		if (!empty($oFilter->byTags)) {
			$aOptions['byTags'] = $oFilter->byTags;
		}
		$aOptiions['cascaded'] = 'top';
		$result = $modelMis->byAcl($oUser, $aOptions);

		return new \ResponseData($result);
	}
	/**
	 * 当前用户参与的所有项目所属的团队列表
	 */
	public function listSite_action() {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$result = $modelMis->siteByAcl($oUser);

		return new \ResponseData($result);
	}
	/**
	 * 新建项目
	 *
	 * @param string $site site'id
	 */
	public function create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelSite = $this->model('site');
		$oSite = $modelSite->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$oProto = $this->getPostJson();
		$current = time();
		$modelMis = $this->model('matter\mission')->setOnlyWriteDbConn(true);

		$oNewMis = new \stdClass;

		/* basic */
		$oNewMis->siteid = $oSite->id;
		$oNewMis->title = isset($oProto->title) ? $modelSite->escape($oProto->title) : $modelSite->escape($oUser->name) . '的项目';
		$oNewMis->summary = isset($oProto->summary) ? $modelSite->escape($oProto->summary) : '';
		$oNewMis->pic = isset($oProto->pic) ? $modelSite->escape($oProto->pic) : $oSite->heading_pic;
		$oNewMis->start_at = isset($oProto->start_at) ? $modelSite->escape($oProto->start_at) : 0;
		$oNewMis->end_at = isset($oProto->end_at) ? $modelSite->escape($oProto->end_at) : 0;
		$oNewMis->creater = $oUser->id;
		$oNewMis->creater_src = $oUser->src;
		$oNewMis->creater_name = $modelSite->escape($oUser->name);
		$oNewMis->create_at = $current;
		$oNewMis->modifier = $oUser->id;
		$oNewMis->modifier_src = $oUser->src;
		$oNewMis->modifier_name = $modelSite->escape($oUser->name);
		$oNewMis->modify_at = $current;
		$oNewMis->state = 1;
		$oNewMis->id = $modelMis->insert('xxt_mission', $oNewMis, true);
		/*记录操作日志*/
		$oNewMis->type = 'mission';
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewMis, 'C');

		/* entry rule */
		if (isset($oProto->entryRule)) {
			$oMisEntryRule = new \stdClass;
			$oMisEntryRule->scope = isset($oProto->entryRule->scope) ? $oProto->entryRule->scope : 'none';
			if ($oMisEntryRule->scope === 'member' && !empty($oProto->entryRule->mschema)) {
				$oMisEntryRule->member = new \stdClass;
				if ($oProto->entryRule->mschema->id === '_pending') {
					/* 给项目创建通讯录 */
					$oMschemaConfig = new \stdClass;
					$oMschemaConfig->matter_id = $oNewMis->id;
					$oMschemaConfig->matter_type = 'mission';
					$oMschemaConfig->valid = 'Y';
					$oMschemaConfig->title = $oNewMis->title . '-通讯录';
					$oMisMschema = $this->model('site\user\memberschema')->create($oSite, $oUser, $oMschemaConfig);
					$oProto->entryRule->mschema->id = $oMisMschema->id;
				}
				$oMisEntryRule->member->{$oProto->entryRule->mschema->id} = (object) ['entry' => ''];
			} else if ($oMisEntryRule->scope === 'sns' && isset($oProto->entryRule->sns)) {
				$oMisEntryRule->sns = new \stdClass;
				foreach ($oProto->entryRule->sns as $snsName => $valid) {
					if ($valid === 'Y') {
						$oMisEntryRule->sns->{$snsName} = (object) ['entry' => 'Y'];
					}
				}
			}
			$modelMis->update('xxt_mission', ['entry_rule' => json_encode($oMisEntryRule)], ['id' => $oNewMis->id]);
			$oNewMis->entry_rule = $oMisEntryRule;
		}

		/**
		 * 建立缺省的ACL
		 * @todo 是否应该挪到消息队列中实现
		 */
		$modelAcl = $this->model('matter\mission\acl');
		/*任务的创建人加入ACL*/
		$coworker = new \stdClass;
		$coworker->id = $oUser->id;
		$coworker->label = $oUser->name;
		$modelAcl->add($oUser, $oNewMis, $coworker, 'O');
		/*站点的系统管理员加入ACL*/
		$modelAcl->addSiteAdmin($oSite->id, $oUser, null, $oNewMis);

		/* create apps */
		if (isset($oProto->app)) {
			$oAppProto = $oProto->app;
			if (isset($oAppProto->enroll->create) && $oAppProto->enroll->create === 'Y') {
				/* 在项目下创建报名活动 */
				$modelEnl = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
				$oEnlConfig = new \stdClass;
				$oEnlConfig->proto = new \stdClass;
				$oEnlConfig->proto->title = $oNewMis->title . '-报名';
				$oEnlConfig->proto->summary = $oNewMis->summary;
				$oNewEnlApp = $modelEnl->createByTemplate($oUser, $oSite, $oEnlConfig, $oNewMis, 'registration', 'simple');
			}
			if (isset($oAppProto->signin->create) && $oAppProto->signin->create === 'Y') {
				/* 在项目下创建签到活动 */
				$modelSig = $this->model('matter\signin')->setOnlyWriteDbConn(true);
				$oSigConfig = new \stdClass;
				$oSigConfig->proto = new \stdClass;
				$oSigConfig->proto->title = $oNewMis->title . '-签到';
				if (isset($oAppProto->signin->enrollApp) && $oAppProto->signin->enrollApp === 'Y' && isset($oNewEnlApp)) {
					$oSigConfig->proto->enrollApp = $oNewEnlApp;
				}
				$oNewSigApp = $modelSig->createByTemplate($oUser, $oSite, $oSigConfig, $oNewMis, 'basic');
			}
			if (isset($oAppProto->group->create) && $oAppProto->group->create === 'Y') {
				/* 在项目下创建分组活动 */
				$modelGrp = $this->model('matter\group')->setOnlyWriteDbConn(true);
				$oGrpConfig = new \stdClass;
				$oGrpConfig->proto = new \stdClass;
				$oGrpConfig->proto->title = $oNewMis->title . '-分组';
				/* 分组用户数据源 */
				if (isset($oAppProto->group->source)) {
					if ($oAppProto->group->source === 'enroll' && isset($oNewEnlApp)) {
						$oGrpConfig->proto->sourceApp = (object) ['id' => $oNewEnlApp->id, 'type' => 'enroll'];
					} else if ($oAppProto->group->source === 'signin' && isset($oNewSigApp)) {
						$oGrpConfig->proto->sourceApp = (object) ['id' => $oNewSigApp->id, 'type' => 'signin'];
					} else if ($oAppProto->group->source === 'mschema' && isset($oMisEntryRule->member)) {
						$oGrpConfig->proto->sourceApp = (object) ['id' => $oProto->entryRule->mschema->id, 'type' => 'mschema'];
					}
				}
				//$oNewGrpApp = $modelGrp->createByMission($oUser, $oSite, $oNewMis, 'split', $oGrpConfig);
				$oNewGrpApp = $modelGrp->createByConfig($oUser, $oSite, $oGrpConfig, $oNewMis, 'split');
			}
			/* 项目的用户名单应用 */
			if (isset($oProto->userApp)) {
				$oUserApp = $oProto->userApp;
				if ($oUserApp === 'mschema' && isset($oMisEntryRule) && $oMisEntryRule->scope === 'member' && isset($oProto->entryRule->mschema)) {
					$oMschema = $oProto->entryRule->mschema;
					$modelMis->update('xxt_mission', ['user_app_id' => $oMschema->id, 'user_app_type' => 'mschema'], ['id' => $oNewMis->id]);
				} else if ($oUserApp === 'enroll' && isset($oNewEnlApp)) {
					$modelMis->update('xxt_mission', ['user_app_id' => $oNewEnlApp->id, 'user_app_type' => 'enroll'], ['id' => $oNewMis->id]);
				} else if ($oUserApp === 'signin' && isset($oNewSigApp)) {
					$modelMis->update('xxt_mission', ['user_app_id' => $oNewSigApp->id, 'user_app_type' => 'signin'], ['id' => $oNewMis->id]);
				} else if ($oUserApp === 'group' && isset($oNewGrpApp)) {
					$modelMis->update('xxt_mission', ['user_app_id' => $oNewGrpApp->id, 'user_app_type' => 'group'], ['id' => $oNewMis->id]);
				}
			}
		}

		return new \ResponseData($oNewMis);
	}
	/**
	 * 更新任务设置
	 */
	public function update_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$modelMis->setOnlyWriteDbConn(true);

		/* data */
		$posted = $this->getPostJson();

		if (isset($posted->title)) {
			$posted->title = $modelMis->escape($posted->title);
		}
		if (isset($posted->summary)) {
			$posted->summary = $modelMis->escape($posted->summary);
		}
		if (isset($posted->entry_rule)) {
			$posted->entry_rule = $modelMis->escape($modelMis->toJson($posted->entry_rule));
		}
		if (isset($posted->extattrs)) {
			$posted->extattrs = $modelMis->escape($modelMis->toJson($posted->extattrs));
		}
		/* modifier */
		$posted->modifier = $oUser->id;
		$posted->modifier_src = $oUser->src;
		$posted->modifier_name = $modelMis->escape($oUser->name);
		$posted->modify_at = time();

		/* update */
		$rst = $modelMis->update('xxt_mission', $posted, ["id" => $id]);
		if ($rst) {
			$mission = $modelMis->byId($id, 'id,siteid,title,summary,pic');
			/*记录操作日志*/
			$this->model('matter\log')->matterOp($mission->siteid, $oUser, $mission, 'U');
			/*更新acl*/
			$mission = $this->model('matter\mission\acl')->updateMission($mission);
		}

		return new \ResponseData($rst);
	}
	/**
	 *
	 * 删除项目
	 * 只有任务的创建人和项目所在团队的管理员才能删除任务，任务合作者删除任务时，只是将自己从acl列表中移除
	 *
	 * @param string $site site'id
	 * @param int $id mission'id
	 */
	public function remove_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$mission = $modelMis->byId($id, 'id,siteid,title,summary,pic,creater');

		$modelAcl = $this->model('matter\mission\acl');
		$acl = $modelAcl->byCoworker($mission->id, $oUser->id);

		if (in_array($acl->coworker_role, ['O', 'A'])) {
			/* 当前用户是项目的创建者或者团队管理员 */
			$q = [
				'siteid,matter_id,matter_type,matter_title',
				'xxt_mission_matter',
				"mission_id='$id'",
			];
			$cnts = $modelMis->query_objs_ss($q);

			if (count($cnts) > 0) {
				/* 如果已经素材，就只打标记 */
				$rst = $modelMis->update('xxt_mission_acl', ['state' => 0], ["mission_id" => $id]);
				$rst = $modelMis->update('xxt_mission', ['state' => 0], ["id" => $id]);
				$this->model('matter\log')->matterOp($mission->siteid, $oUser, $mission, 'Recycle');
				/*给项目下的活动素材打标记*/
				foreach ($cnts as $cnt) {
					if ($cnt->matter_type === 'memberschema') {
						$modelMis->update('xxt_site_member_schema', ['state' => 0], ['siteid' => $cnt->siteid, 'id' => $cnt->matter_id]);
					} else {
						$modelMis->update('xxt_' . $cnt->matter_type, ['state' => 0], ['siteid' => $cnt->siteid, 'id' => $cnt->matter_id]);
					}
					$cnt->id = $cnt->matter_id;
					$cnt->type = $cnt->matter_type;
					$cnt->title = $cnt->matter_title;
					$this->model('matter\log')->matterOp($cnt->siteid, $oUser, $cnt, 'Recycle');
				}
			} else {
				/* 清空任务的ACL */
				$modelAcl->removeMission($mission);
				/* 删除数据 */
				$modelMis->delete('xxt_mission_phase', ["mission_id" => $id]);
				$rst = $modelMis->delete('xxt_mission_acl', ["mission_id" => $id]);
				$rst = $modelMis->delete('xxt_mission', ["id" => $id]);
				$this->model('matter\log')->matterOp($mission->siteid, $oUser, $mission, 'D');
			}
		} else {
			/* 从访问列表中移除当前用户 */
			$coworker = new \stdClass;
			$coworker->id = $oUser->id;
			$modelAcl->removeCoworker($mission, $coworker);
			/* 更新用户的操作日志 */
			$this->model('matter\log')->matterOp($mission->siteid, $oUser, $mission, 'Quit');
		}

		return new \ResponseData('ok');
	}
	/**
	 * 恢复被删除的项目
	 */
	public function restore_action($site, $id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\mission');
		if (false === ($mission = $model->byId($id, 'id,title,summary,pic'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_mission',
			['state' => 1],
			["id" => $mission->id]
		);
		$rst = $model->update(
			'xxt_mission_acl',
			['state' => 1],
			["mission_id" => $mission->id]
		);
		/*恢复项目中的素材*/
		$q = [
			'siteid,matter_id,matter_type,matter_title',
			'xxt_mission_matter',
			"mission_id='$id'",
		];
		$cnts = $model->query_objs_ss($q);

		if (count($cnts) > 0) {
			foreach ($cnts as $cnt) {
				$model->update('xxt_' . $cnt->matter_type, ['state' => 1], ['siteid' => $cnt->siteid, 'id' => $cnt->matter_id]);
				$cnt->id = $cnt->matter_id;
				$cnt->type = $cnt->matter_type;
				$cnt->title = $cnt->matter_title;
				$this->model('matter\log')->matterOp($cnt->siteid, $oUser, $cnt, 'Restore');
			}
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $mission, 'Restore');

		return new \ResponseData($rst);
	}
}