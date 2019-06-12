<?php
namespace pl\fe\matter\mission;

require_once dirname(dirname(__FILE__)) . '/main_base.php';
/*
 * 项目控制器
 */
class main extends \pl\fe\matter\main_base {
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
		$oMission = $this->model('matter\mission')->byId($id, ['cascaded' => ($cascaded === 'Y' ? 'header_page_name,footer_page_name' : '')]);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* 进入规则 */
		if (isset($oMission->entryRule)) {
			$this->fillEntryRule($oMission->entryRule);
		}

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
					if ($oMission->userApp) {
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
			}
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
			$aOptions['byTitle'] = $filter->byTitle;
		}

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
		if (empty($oFilter->bySite)) {
			return new \ParameterError();
		}

		$modelMis = $this->model('matter\mission');
		$aOptions = [
			'limit' => (object) ['page' => $page, 'size' => $size],
			'bySite' => $oFilter->bySite,
		];
		if (!empty($oFilter->byTitle)) {
			$aOptions['byTitle'] = $oFilter->byTitle;
		}
		if (!empty($oFilter->byCreator)) {
			$aOptions['byCreator'] = $oFilter->byCreator;
		}
		if (isset($oFilter->byStar) && $oFilter->byStar === 'Y') {
			$aOptions['byStar'] = 'Y';
		}
		if (!empty($oFilter->byTags) && is_array($oFilter->byTags)) {
			$byTags = [];
			foreach ($oFilter->byTags as &$tag) {
				if (is_object($tag) && !empty($tag->id)) {
					$tag->id = $tag->id;
					$byTags[] =$tag;
				}
			}
			if (!empty($byTags)) {
				$aOptions['byTags'] = $byTags;
			}
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
		$oNewMis->title = isset($oProto->title) ? $oProto->title : $modelSite->escape($oUser->name) . '的项目';
		$oNewMis->summary = isset($oProto->summary) ? $oProto->summary : '';
		$oNewMis->pic = isset($oProto->pic) ? $oProto->pic : $oSite->heading_pic;
		$oNewMis->start_at = isset($oProto->start_at) ? $oProto->start_at : 0;
		$oNewMis->end_at = isset($oProto->end_at) ? $oProto->end_at : 0;
		$oNewMis->creater = $oUser->id;
		$oNewMis->creater_name = $modelSite->escape($oUser->name);
		$oNewMis->create_at = $current;
		$oNewMis->modifier = $oUser->id;
		$oNewMis->modifier_name = $modelSite->escape($oUser->name);
		$oNewMis->modify_at = $current;
		$oNewMis->state = 1;
		$oNewMis->id = $modelMis->insert('xxt_mission', $oNewMis, true);
		/*记录操作日志*/
		$oNewMis->type = 'mission';
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewMis, 'C');

		/* entry rule */
		if (isset($oProto->entryRule->scope)) {
			$oMisEntryRule = new \stdClass;
			if ($this->getDeepValue($oProto->entryRule->scope, 'register') === 'Y') {
				$this->setDeepValue($oMisEntryRule, 'scope.register', 'Y');
			}
			if ($this->getDeepValue($oProto->entryRule->scope, 'member') === 'Y' && !empty($oProto->entryRule->member)) {
				$this->setDeepValue($oMisEntryRule, 'scope.member', 'Y');
				$oMisEntryRule->member = new \stdClass;
				foreach ($oProto->entryRule->member as $msid => $oRule) {
					if ($msid === '_pending') {
						/* 给项目创建通讯录 */
						$oMschemaConfig = new \stdClass;
						$oMschemaConfig->matter_id = $oNewMis->id;
						$oMschemaConfig->matter_type = 'mission';
						$oMschemaConfig->valid = 'Y';
						$oMschemaConfig->title = $oNewMis->title . '-通讯录';
						$oMisMschema = $this->model('site\user\memberschema')->create($oSite, $oUser, $oMschemaConfig);
						$msid = $oMisMschema->id;
					}
					$oMisEntryRule->member->{$msid} = (object) ['entry' => 'Y'];
				}
			}
			if ($this->getDeepValue($oProto->entryRule->scope, 'sns') === 'Y' && isset($oProto->entryRule->sns)) {
				$this->setDeepValue($oMisEntryRule, 'scope.sns', 'Y');
				$oMisEntryRule->sns = new \stdClass;
				foreach ($oProto->entryRule->sns as $snsName => $valid) {
					if ($this->getDeepValue($valid, 'entry') === 'Y') {
						$oMisEntryRule->sns->{$snsName} = (object) ['entry' => 'Y'];
					}
				}
			}
			$modelMis->update('xxt_mission', ['entry_rule' => json_encode($oMisEntryRule)], ['id' => $oNewMis->id]);
			$oNewMis->entryRule = $oMisEntryRule;
		}

		/**
		 * 建立缺省的ACL
		 * @todo 是否应该挪到消息队列中实现
		 */
		$modelAcl = $this->model('matter\mission\acl');
		/*任务的创建人加入ACL*/
		$oCoworker = new \stdClass;
		$oCoworker->id = $oUser->id;
		$oCoworker->label = $oUser->name;
		$modelAcl->add($oUser, $oNewMis, $oCoworker, 'O');
		/*站点的系统管理员加入ACL*/
		$modelAcl->addSiteAdmin($oSite->id, $oUser, null, $oNewMis);

		/* create apps */
		if (isset($oProto->app)) {
			$oAppProto = $oProto->app;
			if ($this->getDeepValue($oAppProto, 'enroll.create') === 'Y') {
				/* 在项目下创建报名活动 */
				$modelEnl = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
				$oEnlConfig = new \stdClass;
				$oEnlConfig->proto = new \stdClass;
				$oEnlConfig->proto->title = $oNewMis->title . '-报名';
				$oEnlConfig->proto->summary = $oNewMis->summary;
				$oNewEnlApp = $modelEnl->createByTemplate($oUser, $oSite, $oEnlConfig, $oNewMis, 'registration', 'simple');
			}
			if ($this->getDeepValue($oAppProto, 'signin.create') === 'Y') {
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
			if ($this->getDeepValue($oAppProto, 'group.create') === 'Y') {
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
					} else if ($oAppProto->group->source === 'mschema' && isset($oNewMis->entryRule->member)) {
						$msid = array_keys((array) $oNewMis->entryRule->member)[0];
						$oGrpConfig->proto->sourceApp = (object) ['id' => $msid, 'type' => 'mschema'];
					}
				}
				$oNewGrpApp = $modelGrp->createByConfig($oUser, $oSite, $oGrpConfig, $oNewMis, 'split');
			}
			/* 项目的用户名单应用 */
			if (isset($oProto->userApp)) {
				$oUserApp = $oProto->userApp;
				if ($oUserApp === 'mschema' && isset($oNewMis->entryRule->member)) {
					$msid = array_keys((array) $oNewMis->entryRule->member)[0];
					$modelMis->update('xxt_mission', ['user_app_id' => $msid, 'user_app_type' => 'mschema'], ['id' => $oNewMis->id]);
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

		$modelMis = $this->model('matter\mission')->setOnlyWriteDbConn(true);
		$oMission = $modelMis->byId($id);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		/* data */
		$oPosted = $this->getPostJson(false);
		/* 处理数据 */
		$oUpdated = new \stdClass;
		foreach ($oPosted as $prop => $val) {
			switch ($prop) {
			case 'title':
			case 'summary':
				$oUpdated->title = $this->escape($val);
				break;
			case 'entryRule':
				$oUpdated->entry_rule = $this->escape($modelMis->toJson($oPosted->entryRule));
				break;
			case 'extattrs':
				$oUpdated->extattrs = $this->escape($modelMis->toJson($oPosted->extattrs));
				break;
			case 'round_cron':
				$aCheckResult = $this->model('matter\mission\round')->checkCron($val);
				if ($aCheckResult[0] === false) {
					return new \ResponseError($aCheckResult[1]);
				}
				$oUpdated->round_cron = $this->escape($modelMis->toJson($val));
				break;
			default:
				$oUpdated->{$prop} = $val;
			}
		}
		/* modifier */
		$oUpdated->modifier = $oUser->id;
		$oUpdated->modifier_name = $this->escape($oUser->name);
		$oUpdated->modify_at = time();

		/* update */
		$rst = $modelMis->update('xxt_mission', $oUpdated, ["id" => $oMission->id]);
		if (!$rst) {
			return new \ResponseError('更新失败！');
		}

		$oMission = $modelMis->byId($oMission->id);
		/*记录操作日志*/
		$this->model('matter\log')->matterOp($oMission->siteid, $oUser, $oMission, 'U', $oUpdated);
		/*更新acl*/
		$this->model('matter\mission\acl')->updateMission($oMission);

		return new \ResponseData($oMission);
	}
	/**
	 *
	 * 删除项目
	 * 只有任务的创建人和项目所在团队的管理员才能删除任务，任务合作者删除任务时，只是将自己从acl列表中移除
	 *
	 * @param int $id mission'id
	 */
	public function remove_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelMis = $this->model('matter\mission');
		$oMission = $modelMis->byId($id);
		if (false === $oMission) {
			return new \ObjectNotFoundError();
		}

		$modelAcl = $this->model('matter\mission\acl');
		$acl = $modelAcl->byCoworker($oMission->id, $oUser->id);

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
				$this->model('matter\log')->matterOp($oMission->siteid, $oUser, $oMission, 'Recycle');
				/*给项目下的活动素材打标记*/
				foreach ($cnts as $cnt) {
					if ($cnt->matter_type === 'memberschema') {
						$modelMis->update('xxt_site_member_schema', ['valid' => 'N'], ['siteid' => $cnt->siteid, 'id' => $cnt->matter_id]);
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
				$modelAcl->removeMission($oMission);
				/* 删除数据 */
				$rst = $modelMis->delete('xxt_mission_acl', ["mission_id" => $id]);
				$rst = $modelMis->delete('xxt_mission', ["id" => $id]);
				$this->model('matter\log')->matterOp($oMission->siteid, $oUser, $oMission, 'D');
			}
		} else {
			/* 从访问列表中移除当前用户 */
			$oCoworker = new \stdClass;
			$oCoworker->id = $oUser->id;
			$modelAcl->removeCoworker($oMission, $oCoworker);
			/* 更新用户的操作日志 */
			$this->model('matter\log')->matterOp($oMission->siteid, $oUser, $oMission, 'Quit');
		}

		return new \ResponseData('ok');
	}
	/**
	 * 恢复被删除的项目
	 */
	public function restore_action($id) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$model = $this->model('matter\mission');
		if (false === ($oMission = $model->byId($id, 'id,siteid,title,summary,pic'))) {
			return new \ResponseError('数据已经被彻底删除，无法恢复');
		}

		/* 恢复数据 */
		$rst = $model->update(
			'xxt_mission',
			['state' => 1],
			["id" => $oMission->id]
		);
		$rst = $model->update(
			'xxt_mission_acl',
			['state' => 1],
			["mission_id" => $oMission->id]
		);
		/*恢复项目中的素材*/
		$q = [
			'siteid,matter_id,matter_type,matter_title',
			'xxt_mission_matter',
			['mission_id' => $id],
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
		$this->model('matter\log')->matterOp($oMission->siteid, $oUser, $oMission, 'Restore');

		return new \ResponseData($rst);
	}
}