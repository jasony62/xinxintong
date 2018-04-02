<?php
namespace pl\fe\matter\enroll;

require_once dirname(__FILE__) . '/main_base.php';
/*
 * 登记活动主控制器
 */
class main extends main_base {
	/**
	 * 返回视图
	 */
	public function index_action($site, $id) {
		$access = $this->accessControlUser('enroll', $id);
		if ($access[0] === false) {
			die($access[1]);
		}

		\TPL::output('/pl/fe/matter/enroll/frame');
		exit;
	}
	/**
	 * 返回指定的登记活动
	 */
	public function get_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnl = $this->model('matter\enroll');
		if (false === ($oApp = $modelEnl->byId($app))) {
			return new \ResponseError('指定的数据不存在');
		}
		unset($oApp->data_schemas);
		unset($oApp->round_cron);
		unset($oApp->rp_config);

		/* channels */
		$oApp->channels = $this->model('matter\channel')->byMatter($oApp->id, 'enroll');
		/* 所属项目 */
		if ($oApp->mission_id) {
			$oApp->mission = $this->model('matter\mission')->byId($oApp->mission_id);
		}
		/* 关联登记活动 */
		if ($oApp->enroll_app_id) {
			$oApp->enrollApp = $modelEnl->byId($oApp->enroll_app_id, ['cascaded' => 'N']);
		}
		/* 关联分组活动 */
		if ($oApp->group_app_id) {
			$oApp->groupApp = $this->model('matter\group')->byId($oApp->group_app_id);
		}
		/* 指定分组活动访问 */
		if (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y') {
			if (isset($oApp->entryRule->group)) {
				$oRuleApp = $oApp->entryRule->group;
				if (!empty($oRuleApp->id)) {
					$oGroupApp = $this->model('matter\group')->byId($oRuleApp->id, ['fields' => 'title', 'cascaded' => 'N']);
					if ($oGroupApp) {
						$oRuleApp->title = $oGroupApp->title;
						if (!empty($oRuleApp->round->id)) {
							$oGroupRnd = $this->model('matter\group\round')->byId($oRuleApp->round->id, ['fields' => 'title']);
							if ($oGroupRnd) {
								$oRuleApp->round->title = $oGroupRnd->title;
							}
						}
					}
				}
			}
		}
		/**
		 * 获得当前活动的分组
		 */
		if ((isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && !empty($oApp->entryRule->group->id)) || !empty($oApp->group_app_id)) {
			$assocGroupAppId = (isset($oApp->entryRule->scope->group) && $oApp->entryRule->scope->group === 'Y' && !empty($oApp->entryRule->group->id)) ? $oApp->entryRule->group->id : $oApp->group_app_id;
			/* 获得的分组信息 */
			$modelGrpRnd = $this->model('matter\group\round');
			$groups = $modelGrpRnd->byApp($assocGroupAppId, ['fields' => "round_id,title"]);
			$oApp->groups = $groups;
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 返回登记活动列表
	 *
	 * @param string $onlySns 是否仅查询进入规则为仅限关注用户访问的活动列表
	 */
	public function list_action($site = null, $mission = null, $page = 1, $size = 30, $scenario = null, $onlySns = 'N', $platform = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oFilter = $this->getPostJson();
		$modelApp = $this->model('matter\enroll');
		$q = [
			"e.*",
			'xxt_enroll e',
			"state<>0",
		];
		if (!empty($mission)) {
			$q[2] .= " and mission_id=" . $mission;
		} else if ($platform === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_home_matter where as_global='Y' and matter_type='enroll' and matter_id=e.id)";
		} else {
			$q[2] .= " and siteid='" . $site . "'";
		}
		if (!empty($scenario)) {
			$q[2] .= " and scenario='" . $modelApp->escape($scenario) . "'";
		}
		if ($onlySns === 'Y') {
			$q[2] .= " and entry_rule like '%\"scope.sns\":\"Y\"%'";
		}
		if (!empty($oFilter->byTitle)) {
			$q[2] .= " and title like '%" . $modelApp->escape($oFilter->byTitle) . "%'";
		}
		if (!empty($oFilter->byTags)) {
			foreach ($oFilter->byTags as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $modelApp->escape($tag->id) . "%'";
			}
		}
		if (isset($oFilter->byStar) && $oFilter->byStar === 'Y') {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='enroll' and t.matter_id=e.id and userid='{$oUser->id}')";
		}

		$q2['o'] = 'e.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;

		$result = ['apps' => null, 'total' => 0];

		if ($apps = $modelApp->query_objs_ss($q, $q2)) {
			foreach ($apps as $oApp) {
				$oApp->type = 'enroll';
				$oApp->url = $modelApp->getEntryUrl($oApp->siteid, $oApp->id);
				$oApp->opData = $modelApp->opData($oApp, true);
				/* 是否已经星标 */
				$qStar = [
					'id',
					'xxt_account_topmatter',
					['matter_id' => $oApp->id, 'matter_type' => 'enroll', 'userid' => $oUser->id],
				];
				if ($oStar = $modelApp->query_obj_ss($qStar)) {
					$oApp->star = $oStar->id;
				}
			}
			$result['apps'] = $apps;
		}
		if (!empty($apps) || $page != 1) {
			$q[0] = 'count(*)';
			$total = (int) $modelApp->query_val_ss($q);
			$result['total'] = $total;
		}

		return new \ResponseData($result);
	}
	/**
	 * 创建登记活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 *
	 */
	public function create_action($site, $mission = null, $scenario = 'common', $template = 'simple') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		if (empty($mission)) {
			$oMission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$oMission = $modelMis->byId($mission);
			if (false === $oMission) {
				return new \ObjectNotFoundError();
			}
		}
		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);

		$oCustomConfig = $this->getPostJson();

		$oNewApp = $modelApp->createByTemplate($oUser, $oSite, $oCustomConfig, $oMission, $scenario, $template);

		return new \ResponseData($oNewApp);
	}
	/**
	 *
	 * 复制指定的登记活动
	 *
	 * 跨项目进行复制：
	 * 1、关联了项目的通讯录，取消关联，修改相关题目的id和type
	 * 2、关联了分组活动，取消和分组活动的关联，修改分组题目，修改相关题目的id和type
	 * 3、关联了登记活动，取消和登记活动的关联，修改分组题目，修改相关题目的id和type
	 *
	 * @param string $site 是否要支持跨团队进行活动的复制？
	 * @param string $app
	 * @param int $mission
	 * @param int $cpRecord 是否复制数据
	 * @param int $cpEnrollee 是否复制用户行为
	 *
	 */
	public function copy_action($site, $app, $mission = null, $cpRecord = 'N', $cpEnrollee = 'N') {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
		$modelPg = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');

		$oCopied = $modelApp->byId($app);
		if (false === $oCopied) {
			return new \ObjectNotFoundError();
		}
		$oEntryRule = clone $oCopied->entryRule;
		$aDataSchemas = $oCopied->dataSchemas;
		$aPages = $oCopied->pages;
		$newaid = uniqid();
		$oNewApp = new \stdClass;
		$oNewApp->siteid = $site;
		$oNewApp->id = $newaid;
		/**
		 * 如果通讯录的所属范围和新活动的范围不一致，需要解除关联的通信录
		 */
		if (isset($oEntryRule->scope->member) && $oEntryRule->scope->member === 'Y') {
			$aMatterMschemas = $modelApp->getEntryMemberSchema($oEntryRule);
			foreach ($aMatterMschemas as $oMschema) {
				if (!empty($oMschema->matter_type) && ($oMschema->matter_type !== 'mission' || $oMschema->matter_id !== $mission)) {
					/* 应用的题目 */
					$modelApp->replaceMemberSchema($aDataSchemas, $oMschema);
					/* 页面的题目 */
					foreach ($aPages as $oPage) {
						$modelPg->replaceMemberSchema($oPage, $oMschema);
					}
					unset($oEntryRule->member->{$oMschema->id});
				}
			}
			if (count((array) $oEntryRule->member) === 0) {
				unset($oEntryRule->scope->member);
				unset($oEntryRule->member);
			}
		}
		/**
		 * 跨项目进行复制
		 */
		if ($oCopied->mission_id !== $mission) {
			/**
			 * 只有同项目内的分组活动可以作为参与规则
			 */
			if (isset($oEntryRule->scope->group) && $oEntryRule->scope->group === 'Y') {
				unset($oEntryRule->scope->group);
				unset($oEntryRule->group);
			}
			/**
			 * 如果关联了分组或登记活动，需要去掉题目的关联信息
			 */
			$aAssocApps = [];
			if (!empty($oCopied->group_app_id)) {
				$aAssocApps[] = $oCopied->group_app_id;
				$oCopied->group_app_id = '';
			}
			if (!empty($oCopied->enroll_app_id)) {
				$aAssocApps[] = $oCopied->enroll_app_id;
				$oCopied->enroll_app_id = '';
			}
			if (count($aAssocApps)) {
				/* 页面的题目 */
				foreach ($aPages as $oPage) {
					$modelPg->replaceAssocSchema($oPage, $aAssocApps);
				}
				/* 应用的题目 */
				$modelApp->replaceAssocSchema($aDataSchemas, $aAssocApps);
			}
		}

		/* 作为昵称的题目 */
		$oNicknameSchema = $modelApp->findAssignedNicknameSchema($aDataSchemas);
		if (!empty($oNicknameSchema)) {
			$oNewApp->assigned_nickname = json_encode(['valid' => 'Y', 'schema' => ['id' => $oNicknameSchema->id]]);
		}

		/**
		 * 获得的基本信息
		 */
		$oNewApp->start_at = 0;
		$oNewApp->title = $modelApp->escape($oCopied->title) . '（副本）';
		$oNewApp->pic = $oCopied->pic;
		$oNewApp->summary = $modelApp->escape($oCopied->summary);
		$oNewApp->scenario = $oCopied->scenario;
		$oNewApp->scenario_config = $oCopied->scenario_config;
		$oNewApp->count_limit = $oCopied->count_limit;
		$oNewApp->multi_rounds = $oCopied->multi_rounds;
		$oNewApp->enrolled_entry_page = $oCopied->enrolled_entry_page;
		$oNewApp->can_siteuser = 'Y';
		$oNewApp->entry_rule = json_encode($oEntryRule);
		$oNewApp->data_schemas = $modelApp->escape($modelApp->toJson($aDataSchemas));
		$oNewApp->group_app_id = $oCopied->group_app_id;
		$oNewApp->enroll_app_id = $oCopied->enroll_app_id;
		$oNewApp->tags = $modelApp->escape($oCopied->tags);

		/* 所属项目 */
		if (!empty($mission)) {
			$oNewApp->mission_id = $mission;
		}
		/* 任务码 */
		$entryUrl = $modelApp->getOpUrl($oNewApp->siteid, $oNewApp->id);
		$code = $this->model('q\url')->add($oUser, $oNewApp->siteid, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$oNewApp = $modelApp->create($oUser, $oNewApp);
		/**
		 * 复制自定义页面
		 */
		if (count($oCopied->pages)) {
			foreach ($oCopied->pages as $ep) {
				$oNewPage = $modelPg->add($oUser, $oNewApp->siteid, $oNewApp->id);
				$rst = $modelPg->update(
					'xxt_enroll_page',
					[
						'title' => $ep->title,
						'name' => $ep->name,
						'type' => $ep->type,
						'data_schemas' => $modelApp->escape($ep->data_schemas),
						'act_schemas' => $modelApp->escape($ep->act_schemas),
						'user_schemas' => $modelApp->escape($ep->user_schemas),
					],
					['aid' => $oNewApp->id, 'id' => $oNewPage->id]
				);
				$data = [
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				];
				$modelCode->modify($oNewPage->code_id, $data);
			}
		}
		/* 复制登记活动数据 */
		if ($cpRecord === 'Y') {
			$oNewApp = $modelApp->byId($oNewApp->id);
			$modelRec = $this->model('matter\enroll\record')->setOnlyWriteDbConn(true);
			/* 创建新活动的轮次和元活动匹配 */
			$modelRound = $this->model('matter\enroll\round');
			$oldRounds = $modelRound->byApp($oCopied)->rounds;
			//轮次为空的用户
			$nullRound = new \stdClass;
			$nullRound->rid = '';
			$oldRounds[] = $nullRound;
			foreach ($oldRounds as $oldRound) {
				if (!empty($oldRound->rid)) {
					$props = new \stdClass;
					$props->title = $oldRound->title;
					$props->summary = $oldRound->summary;
					$props->start_at = $oldRound->start_at;
					$props->end_at = $oldRound->end_at;
					$props->state = $oldRound->state;
					$newRound = $modelRound->create($oNewApp, $props, $oUser);
					if (!$newRound[0]) {
						return new \ResponseError($newRound[1]);
					}
					$newRound = $newRound[1]->rid;
				} else {
					$newRound = '';
				}
				//插入数据
				$oldCriteria = new \stdClass;
				$oldCriteria->record = new \stdClass;
				$oldCriteria->record->rid = $oldRound->rid;
				$oldUsers = $modelRec->byApp($oCopied, null, $oldCriteria);

				if (isset($oldUsers->records) && count($oldUsers->records)) {
					foreach ($oldUsers->records as $record) {
						$cpUser = new \stdClass;
						$cpUser->uid = ($cpEnrollee !== 'Y') ? '' : $record->userid;
						$cpUser->nickname = ($cpEnrollee !== 'Y') ? '' : $record->nickname;
						/* 插入登记数据 */
						$ek = $modelRec->enroll($oNewApp, $cpUser, ['nickname' => $cpUser->nickname, 'assignRid' => $newRound]);
						/* 处理自定义信息 */
						if (isset($record->data->member) && $oNewApp->entryRule->scope->member !== 'Y') {
							unset($record->data->member->schema_id);
							foreach ($record->data->member as $schemaId => $val) {
								$record->data->{$schemaId} = $val;
							}
							unset($record->data->member);
						}
						$oEnrolledData = $record->data;
						$rst = $modelRec->setData($cpUser, $oNewApp, $ek, $oEnrolledData, '', false);
						if (!empty($record->supplement) && count(get_object_vars($record->supplement))) {
							$rst = $modelRec->setSupplement($cpUser, $oEnrollApp, $ek, $record->supplement);
						}
						$upDate = [];
						$upDate['verified'] = $record->verified;
						$upDate['comment'] = $modelRec->escape($record->comment);
						if (!empty($record->tags)) {
							$upDate['tags'] = $modelRec->escape($record->tags);
						}
						$rst = $modelRec->update(
							'xxt_enroll_record',
							$upDate,
							['enroll_key' => $ek, 'state' => 1]
						);
					}
				}
			}
		}

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oNewApp->siteid, $oUser, $oNewApp, 'C', (object) ['id' => $oCopied->id, 'title' => $oCopied->title]);

		return new \ResponseData($oNewApp);
	}
	/**
	 * 更新活动的属性信息
	 *
	 * @param string $app app'id
	 *
	 */
	public function update_action($app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, 'id,siteid,title,summary,pic,scenario,start_at,end_at,mission_id,absent_cause');
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}

		$oPosted = $this->getPostJson();
		/* 处理数据 */
		$oUpdated = new \stdClass;
		foreach ($oPosted as $n => $v) {
			if (in_array($n, ['title', 'summary'])) {
				$oUpdated->{$n} = $modelApp->escape($v);
			} else if (in_array($n, ['data_schemas', 'recycle_schemas'])) {
				$oUpdated->{$n} = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'entryRule') {
				if ($v->scope === 'group') {
					if (isset($v->group->title)) {
						unset($v->group->title);
					}
					if (isset($v->group->round->title)) {
						unset($v->group->round->title);
					}
				}
				$oUpdated->entry_rule = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'actionRule') {
				$oUpdated->action_rule = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'assignedNickname') {
				$oUpdated->assigned_nickname = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'scenarioConfig') {
				$oUpdated->scenario_config = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'roundCron') {
				$rst = $this->checkCron($v);
				if ($rst[0] === false) {
					return new \ResponseError($rst[1]);
				}
				$oUpdated->round_cron = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'rpConfig') {
				$oUpdated->rp_config = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'rankConfig') {
				$oUpdated->rank_config = $modelApp->escape($modelApp->toJson($v));
			} else if ($n === 'absent_cause') {
				$absentCause = !empty($oApp->absent_cause) ? $oApp->absent_cause : new \stdClass;
				foreach ($v as $uid => $val) {
					!isset($absentCause->{$uid}) && $absentCause->{$uid} = new \stdClass;
					$absentCause->{$uid}->{$val->rid} = $val->cause;
				}
				$oUpdated->{$n} = $modelApp->escape($modelApp->toJson($absentCause));
			} else {
				$oUpdated->{$n} = $v;
			}
		}

		if ($oApp = $modelApp->modify($oUser, $oApp, $oUpdated)) {
			// 记录操作日志并更新信息
			$this->model('matter\log')->matterOp($oApp->siteid, $oUser, $oApp, 'U', $oUpdated);
		}

		return new \ResponseData($oApp);
	}
	/**
	 * 从共享模板模板创建登记活动
	 *
	 * @param string $site
	 * @param int $template
	 * @param int $mission
	 *
	 * @return object ResponseData
	 *
	 */
	public function createByOther_action($site, $template, $vid = null, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$oCustomConfig = $this->getPostJson();
		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');

		$template = $this->model('matter\template')->byId($template, $vid);
		if (empty($template->pub_version)) {
			return new \ResponseError('模板已下架');
		}
		if ($template->pub_status === 'N') {
			return new \ResponseError('当前版本未发布，无法使用');
		}

		/* 检查用户积分 */
		if ($template->coin) {
			$account = $this->model('account')->byId($oUser->id, ['fields' => 'uid,nickname,coin']);
			if ((int) $account->coin < (int) $template->coin) {
				return new \ResponseError('使用模板【' . $template->title . '】需要积分（' . $template->coin . '），你的积分（' . $account->coin . '）不足');
			}
		}

		/* 创建活动 */
		$current = time();
		$oNewApp = new \stdClass;
		if (empty($mission)) {
			$oNewApp->pic = $template->pic;
			$oNewApp->summary = $template->summary;
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$oNewApp->pic = $mission->pic;
			$oNewApp->summary = $mission->summary;
			$oNewApp->mission_id = $mission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
		}
		$oNewApp->title = empty($oCustomConfig->proto->title) ? $template->title : $oCustomConfig->proto->title;
		$oNewApp->siteid = $site;
		$oNewApp->start_at = $current;
		$oNewApp->scenario = $template->scenario;
		$oNewApp->scenario_config = $template->scenario_config;
		$oNewApp->multi_rounds = $template->multi_rounds;
		$oNewApp->data_schemas = $modelApp->escape($template->data_schemas);
		$oNewApp->open_lastroll = $template->open_lastroll;
		$oNewApp->enrolled_entry_page = $template->enrolled_entry_page;
		$oNewApp->template_id = $template->id;
		$oNewApp->template_version = $template->version;
		$oNewApp->can_siteuser = 'Y';
		/* 进入规则 */
		$oEntryRule = new \stdClass;
		$oEntryRule->scope = new \stdClass;
		$oNewApp->entry_rule = json_encode($oEntryRule);

		$oNewApp = $modelApp->create($oUser, $oNewApp);
		$oNewApp->type = 'enroll';

		/* 复制自定义页面 */
		if ($template->pages) {
			foreach ($template->pages as $ep) {
				$newPage = $modelPage->add($oUser, $site, $oNewApp->id);
				$rst = $modelPage->update(
					'xxt_enroll_page',
					['title' => $ep->title, 'name' => $ep->name, 'type' => $ep->type, 'data_schemas' => $modelApp->escape($ep->data_schemas), 'act_schemas' => $modelApp->escape($ep->act_schemas)],
					["aid" => $oNewApp->id, "id" => $newPage->id]
				);
				$data = [
					'title' => $ep->title,
					'html' => $ep->html,
					'css' => $ep->css,
					'js' => $ep->js,
				];
				$modelCode->modify($newPage->code_id, $data);
			}
		}
		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($site, $oUser, $oNewApp, 'C');

		/* 支付积分 */
		if ($template->coin) {
			$modelCoin = $this->model('pl\coin\log');
			$creator = $this->model('account')->byId($template->creater, ['fields' => 'uid id,nickname name']);
			$modelCoin->transfer('pl.template.use', $oUser, $creator, (int) $template->coin);
		}
		/* 更新模板使用情况数据 */

		return new \ResponseData($oNewApp);
	}
	/**
	 * 根据活动定义文件创建登记活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByConfig_action($site, $mission = null) {
		if (false === ($user = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$config = $this->getPostJson();
		$current = time();
		$oNewApp = new \stdClass;

		/* 从站点或任务获得的信息 */
		if (empty($mission)) {
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->summary = '';
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
			$mission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$oNewApp->pic = $mission->pic;
			$oNewApp->summary = $mission->summary;
			$oNewApp->mission_id = $mission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
		}
		$appId = uniqid();
		$oCustomConfig = isset($config->customConfig) ? $config->customConfig : null;
		!empty($config->scenario) && $oNewApp->scenario = $config->scenario;
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$oNewApp->count_limit = $config->count_limit;
		}

		if (!empty($config->pages) && !empty($config->entryRule)) {
			$this->_addPageByTemplate($user, $site, $mission, $appId, $config, $oCustomConfig);
			/*进入规则*/
			$entryRule = $config->entryRule;
			if (!empty($entryRule)) {
				if (!isset($entryRule->scope)) {
					$entryRule->scope = new \stdClass;
				}
			}
			if (isset($config->enrolled_entry_page)) {
				$oNewApp->enrolled_entry_page = $config->enrolled_entry_page;
			}
			/*场景设置*/
			if (isset($config->scenarioConfig)) {
				$scenarioConfig = $config->scenarioConfig;
				$oNewApp->scenario_config = json_encode($scenarioConfig);
			}
		} else {
			$entryRule = $this->_addBlankPage($user, $oSite->id, $appId);
			if (!empty($entryRule)) {
				if (!isset($entryRule['scope'])) {
					$entryRule['scope'] = new \stdClass;
				}
			}
		}
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}

		/* create app */
		$oNewApp->id = $appId;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = empty($oCustomConfig->proto->title) ? '新登记活动' : $oCustomConfig->proto->title;
		$oNewApp->start_at = $current;
		$oNewApp->entry_rule = json_encode($entryRule);
		$oNewApp->can_siteuser = 'Y';
		isset($config) && $oNewApp->data_schemas = \TMS_MODEL::toJson($config->schema);

		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
		$oNewApp = $modelApp->create($oUser, $oNewApp);

		/* 保存数据 */
		$records = $config->records;
		$this->_persist($oSite->id, $appId, $records);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $user, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * 根据登记记录创建登记活动
	 * 选中的登记项的标题作为题目，选中的记录对应的内容作为选项
	 * 目前支持生成单选题、多选题和打分题
	 * 目前只支持通用登记模板页面
	 *
	 * @param string $site site's id
	 * @param string $app app's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByRecords_action($site, $app, $mission = null) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}
		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
		$modelRec = $this->model('matter\enroll\record');

		$oCustomConfig = $this->getPostJson();
		/* 获得指定记录的数据 */
		$records = [];
		$eks = $oCustomConfig->record->eks;
		foreach ($eks as $index => $ek) {
			$records[] = $modelRec->byId($ek);
		}
		/* 生成活动的schema */
		$protoSchema = $oCustomConfig->proto->schema;
		$newSchemas = [];
		foreach ($oCustomConfig->record->schemas as $recordSchema) {
			$newSchema = clone $protoSchema;
			$newSchema->id = $recordSchema->id;
			$newSchema->title = $recordSchema->title;
			$newSchema->required = 'Y';
			$newSchema->ops = [];
			foreach ($records as $index => $record) {
				if (empty($record->data->{$recordSchema->id})) {
					continue;
				}
				$op = new \stdClass;
				$op->v = 'v' . ($index + 1);
				$op->l = $record->data->{$recordSchema->id};
				$newSchema->ops[] = $op;
			}
			$newSchemas[] = $newSchema;
		}
		/* 使用缺省模板 */
		$config = $this->_getSysTemplate('common', 'simple');

		/* 修改模板的配置 */
		$config->schema = [];
		foreach ($config->pages as &$page) {
			if ($page->type === 'I') {
				$page->data_schemas = [];
			} else if ($page->type === 'V') {
				$page->data_schemas = [];
			} else if ($page->type === 'L') {
				$page->data_schemas = [];
			}
		}
		foreach ($newSchemas as $newSchema) {
			$config->schema[] = $newSchema;
			foreach ($config->pages as &$page) {
				if ($page->type === 'I') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$newWrap->config = $wrapConfig;
					$page->data_schemas[] = $newWrap;
				} else if ($page->type === 'V') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$newWrap->config = $wrapConfig;
					$wrapConfig->id = "V1";
					$wrapConfig->pattern = "record";
					$wrapConfig->inline = "N";
					$wrapConfig->splitLine = "Y";
					$page->data_schemas[] = $newWrap;
				}
			}
		}
		/* 进入规则 */
		$entryRule = $config->entryRule;
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		if (!isset($entryRule->scope)) {
			$entryRule->scope = new \stdClass;
		}

		$current = time();
		$appId = uniqid();
		$oNewApp = new \stdClass;
		/*从站点或任务获得的信息*/
		if (empty($mission)) {
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->summary = '';
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$oNewApp->pic = $mission->pic;
			$oNewApp->summary = $mission->summary;
			$oNewApp->mission_id = $mission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
		}
		/* 添加页面 */
		$this->_addPageByTemplate($oUser, $oSite, $mission, $appId, $config, null);
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$oNewApp->count_limit = $config->count_limit;
		}
		if (isset($config->enrolled_entry_page)) {
			$oNewApp->enrolled_entry_page = $config->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($config->scenarioConfig)) {
			$scenarioConfig = $config->scenarioConfig;
			$oNewApp->scenario_config = json_encode($scenarioConfig);
		}
		$oNewApp->scenario = $oCustomConfig->proto->scenario;
		/* create app */
		$oNewApp->id = $appId;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = empty($oCustomConfig->proto->title) ? '新登记活动' : $modelApp->escape($oCustomConfig->proto->title);
		$oNewApp->start_at = $current;
		$oNewApp->entry_rule = json_encode($entryRule);
		$oNewApp->can_siteuser = 'Y';
		$oNewApp->data_schemas = \TMS_MODEL::toJson($config->schema);

		$oNewApp = $modelApp->create($oUser, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return new \ResponseData($oNewApp);
	}
	/**
	 * 为创建活动上传的xlsx
	 */
	public function uploadExcel4Create_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}

		$modelFs = $this->model('fs/local', $site, '_resumable');
		$dest = '/enroll_' . $site . '_' . $_POST['resumableFilename'];
		$resumable = $this->model('fs/resumable', $site, $dest, $modelFs);

		$resumable->handleRequest($_POST);

		exit;
	}
	/**
	 * 通过导入的Excel数据记录创建登记活动
	 * 目前就是填空题
	 */
	public function createByExcel_action($site) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		if (defined('SAE_TMP_PATH')) {
			return new \ResponseError('not support');
		}
		$oSite = $this->model('site')->byId($site, ['fields' => 'id,heading_pic']);
		if (false === $oSite) {
			return new \ObjectNotFoundError();
		}

		$oExcelFile = $this->getPostJson();

		// 文件存储在本地
		$modelFs = $this->model('fs/local', $site, '_resumable');
		$fileUploaded = 'enroll_' . $site . '_' . $oExcelFile->name;
		$filename = $modelFs->rootDir . '/' . $fileUploaded;
		if (!file_exists($filename)) {
			return new \ResponseError('上传文件失败！');
		}

		require_once TMS_APP_DIR . '/lib/PHPExcel.php';
		$objPHPExcel = \PHPExcel_IOFactory::load($filename);
		$objWorksheet = $objPHPExcel->getActiveSheet();
		//xlsx 行号是数字
		$highestRow = $objWorksheet->getHighestRow();
		//xlsx 列的标识 eg：A,B,C,D,……,Z
		$highestColumn = $objWorksheet->getHighestColumn();
		//把最大的列换成数字
		$highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn);
		/**
		 * 提取数据定义信息
		 */
		$schemasByCol = [];
		$record = [];
		for ($col = 0; $col < $highestColumnIndex; $col++) {
			$colTitle = (string) $objWorksheet->getCellByColumnAndRow($col, 1)->getValue();
			$data = new \stdClass;
			if ($colTitle === '备注') {
				$schemasByCol[$col] = 'comment';
			} else if ($colTitle === '标签') {
				$schemasByCol[$col] = 'tags';
			} else if ($colTitle === '审核通过') {
				$schemasByCol[$col] = 'verified';
			} else if ($colTitle === '昵称') {
				$schemasByCol[$col] = false;
			} else if (preg_match("/.*时间/", $colTitle)) {
				$schemasByCol[$col] = 'submit_at';
			} else if (preg_match("/姓名.*/", $colTitle)) {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = 'name';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			} else if (preg_match("/手机.*/", $colTitle)) {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = 'mobile';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			} else if (preg_match("/邮箱.*/", $colTitle)) {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = 'email';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			} else {
				$data->id = $this->getTopicId();
				$data->title = $colTitle;
				$data->type = 'shorttext';
				$data->required = 'Y';
				$data->format = '';
				$data->unique = 'N';
				$data->_ver = '1';
				$schemasByCol[$col]['id'] = $data->id;
			}
			if (!empty((array) $data)) {
				$record[] = $data;
			}
		}
		/* 使用缺省模板 */
		$config = $this->_getSysTemplate('common', 'simple');

		/* 修改模板的配置 */
		$config->schema = [];
		foreach ($config->pages as &$page) {
			if ($page->type === 'I') {
				$page->data_schemas = [];
			} else if ($page->type === 'V') {
				$page->data_schemas = [];
			} else if ($page->type === 'L') {
				$page->data_schemas = [];
			}
		}
		foreach ($record as $newSchema) {
			$config->schema[] = $newSchema;
			foreach ($config->pages as &$page) {
				if ($page->type === 'I') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$newWrap->config = $wrapConfig;
					$page->data_schemas[] = $newWrap;
				} else if ($page->type === 'V') {
					$newWrap = new \stdClass;
					$newWrap->schema = $newSchema;
					$wrapConfig = new \stdClass;
					$newWrap->config = $wrapConfig;
					$wrapConfig->id = "V1";
					$wrapConfig->pattern = "record";
					$wrapConfig->inline = "N";
					$wrapConfig->splitLine = "Y";
					$page->data_schemas[] = $newWrap;
				}
			}
		}
		/* 进入规则 */
		$entryRule = $config->entryRule;
		if (empty($entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		if (!isset($entryRule->scope)) {
			$entryRule->scope = new \stdClass;
		}

		$modelApp = $this->model('matter\enroll')->setOnlyWriteDbConn(true);
		$appId = uniqid();
		$current = time();
		$oNewApp = new \stdClass;
		/*从站点或任务获得的信息*/
		if (empty($mission)) {
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->summary = '';
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
			$mission = null;
		} else {
			$modelMis = $this->model('matter\mission');
			$mission = $modelMis->byId($mission);
			$oNewApp->pic = $mission->pic;
			$oNewApp->summary = $mission->summary;
			$oNewApp->mission_id = $mission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
		}
		/* 添加页面 */
		$this->_addPageByTemplate($oUser, $oSite, $mission, $appId, $config, null);
		/* 登记数量限制 */
		if (isset($config->count_limit)) {
			$oNewApp->count_limit = $config->count_limit;
		}
		if (isset($config->enrolled_entry_page)) {
			$oNewApp->enrolled_entry_page = $config->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($config->scenarioConfig)) {
			$scenarioConfig = $config->scenarioConfig;
			$oNewApp->scenario_config = json_encode($scenarioConfig);
		}
		$oNewApp->scenario = 'common';
		/* create app */
		$title = strtok($oExcelFile->name, '.');
		$oNewApp->id = $appId;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = $modelApp->escape($title);
		$oNewApp->start_at = $current;
		$oNewApp->entry_rule = json_encode($entryRule);
		$oNewApp->can_siteuser = 'Y';
		$oNewApp->data_schemas = \TMS_MODEL::toJson($record);

		$oNewApp = $modelApp->create($oUser, $oNewApp);

		/* 存放数据 */
		$records2 = [];
		for ($row = 2; $row <= $highestRow; $row++) {
			$record2 = new \stdClass;
			$data2 = new \stdClass;
			for ($col = 0; $col < $highestColumnIndex; $col++) {
				$schema = $schemasByCol[$col];
				if ($schema === false) {
					continue;
				}
				$value = (string) $objWorksheet->getCellByColumnAndRow($col, $row)->getValue();
				if ($schema === 'verified') {
					if (in_array($value, ['Y', '是'])) {
						$record2->verified = 'Y';
					} else {
						$record2->verified = 'N';
					}
				} else if ($schema === 'comment') {
					$record2->comment = $value;
				} else if ($schema === 'tags') {
					$record2->tags = $value;
				} else if ($schema === 'submit_at') {
					$record2->submit_at = $value;
				} else {
					$data2->{$schema['id']} = $value;
				}
			}
			$record2->data = $data2;
			$records2[] = $record2;
		}
		/* 保存数据*/
		$this->_persist($site, $appId, $records2);
		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		// 删除上传的文件
		$modelFs->delete($fileUploaded);

		return new \ResponseData($oNewApp);
	}
	/**
	 * 保存数据
	 */
	private function _persist($site, $appId, &$records) {
		$current = time();
		$modelApp = $this->model('matter\enroll');
		$modelRec = $this->model('matter\enroll\record');
		$enrollKeys = [];

		foreach ($records as $record) {
			$ek = $modelRec->genKey($site, $appId);

			$r = array();
			$r['aid'] = $appId;
			$r['siteid'] = $site;
			$r['enroll_key'] = $ek;
			$r['enroll_at'] = $current;
			$r['verified'] = isset($record->verified) ? $record->verified : 'N';
			$r['comment'] = isset($record->comment) ? $record->comment : '';
			if (isset($record->tags)) {
				$r['tags'] = $record->tags;
				$modelApp->updateTags($appId, $record->tags);
			}
			$id = $modelRec->insert('xxt_enroll_record', $r, true);
			$r['id'] = $id;
			/**
			 * 登记数据
			 */
			if (isset($record->data)) {
				//
				$jsonData = $modelRec->toJson($record->data);
				$modelRec->update('xxt_enroll_record', ['data' => $jsonData], "enroll_key='$ek'");
				$enrollKeys[] = $ek;
				//
				foreach ($record->data as $n => $v) {
					if (is_object($v) || is_array($v)) {
						$v = json_encode($v);
					}
					if (count($v)) {
						$cd = [
							'aid' => $appId,
							'enroll_key' => $ek,
							'schema_id' => $n,
							'value' => $v,
						];
						$modelRec->insert('xxt_enroll_record_data', $cd, false);
					}
				}
			}
		}

		return $enrollKeys;
	}
	/**
	 * 创建题目的id
	 *
	 */
	protected function getTopicId() {
		list($usec, $sec) = explode(" ", microtime());
		$microtime = ((float) $usec) * 1000000;
		$id = 's' . floor($microtime);

		return $id;
	}
	/**
	 * 检查传入的定时规则
	 *
	 * @param object $rules
	 */
	protected function checkCron(&$rules) {
		foreach ($rules as $k => $rule) {
			switch ($rule->period) {
			//1-28 日期
			case 'M':
				if (empty($rule->mday)) {return [false, '请设置定时轮次每月的开始日期！'];}
				if (empty($rule->end_mday)) {return [false, '请设置定时轮次每月的结束日期！'];}
				if ($rule->hour === '') {return [false, '请设置定时轮次每月开始日期的几点开始！'];}
				break;
			// 0-6 周几
			case 'W':
				if ($rule->wday === '') {return [false, '请设置定时轮次每周几开始！'];}
				if ($rule->end_wday === '') {return [false, '请设置定时轮次每周几结束！'];}
				if ($rule->hour === '') {return [false, '请设置定时轮次每周几的几点开始！'];}
				break;
			// 0-23 几点
			default:
				if ($rule->hour === '') {return [false, '请设置定时轮次每天的几点开始！'];}
				break;
			}
		}

		return [true];
	}
	/**
	 * 添加空页面
	 */
	private function _addBlankPage($oUser, $siteId, $appid) {
		$current = time();
		$modelPage = $this->model('matter\enroll\page');
		/* form page */
		$page = [
			'title' => '填写信息页',
			'type' => 'I',
			'name' => 'z' . $current,
		];
		$page = $modelPage->add($oUser, $siteId, $appid, $page);
		/*entry rules*/
		$entryRule = [
			'otherwise' => ['entry' => $page->name],
		];
		/* result page */
		$page = [
			'title' => '查看结果页',
			'type' => 'V',
			'name' => 'z' . ($current + 1),
		];
		$modelPage->add($oUser, $siteId, $appid, $page);

		return $entryRule;
	}
	/**
	 * 获得系统内置登记活动模板
	 * 如果没有指定场景或模板，那么就使用系统的缺省模板
	 *
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 *
	 */
	private function _getSysTemplate($scenario = null, $template = null) {
		if (empty($scenario) || empty($template)) {
			$scenario = 'common';
			$template = 'simple';
		}
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/enroll/scenario/' . $scenario . '/templates/' . $template;
		$config = file_get_contents($templateDir . '/config.json');
		$config = preg_replace('/\t|\r|\n/', '', $config);
		$config = json_decode($config);
		/**
		 * 处理页面
		 */
		if (!empty($config->pages)) {
			foreach ($config->pages as &$oPage) {
				$templateFile = $templateDir . '/' . $oPage->name;
				/* 填充代码 */
				$code = [
					'html' => file_exists($templateFile . '.html') ? file_get_contents($templateFile . '.html') : '',
					'css' => file_exists($templateFile . '.css') ? file_get_contents($templateFile . '.css') : '',
					'js' => file_exists($templateFile . '.js') ? file_get_contents($templateFile . '.js') : '',
				];
				$oPage->code = $code;
			}
		}

		return $config;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $appId
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$oUser, &$site, $oMission, &$appId, &$oTemplateConfig, $oCustomConfig) {
		$pages = $oTemplateConfig->pages;
		if (empty($pages)) {
			return false;
		}

		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');
		/* 简单schema定义，目前用于投票场景 */
		if (isset($oCustomConfig->simpleSchema)) {
			$oTemplateConfig->schema = $modelPage->schemaByText($oCustomConfig->simpleSchema);
		}
		/**
		 * 处理页面
		 */
		foreach ($pages as $page) {
			$ap = $modelPage->add($oUser, $site->id, $appId, (array) $page);
			/**
			 * 处理页面数据定义
			 */
			if (empty($page->data_schemas) && !empty($oTemplateConfig->schema) && !empty($page->simpleConfig)) {
				/* 页面使用应用的所有数据定义 */
				$page->data_schemas = [];
				foreach ($oTemplateConfig->schema as $schema) {
					$newPageSchema = new \stdClass;
					$newPageSchema->schema = $schema;
					$newPageSchema->config = clone $page->simpleConfig;
					if ($page->type === 'V') {
						$newPageSchema->config->id = 'V_' . $schema->id;
					}
					$page->data_schemas[] = $newPageSchema;
				}
			}
			$pageSchemas = [];
			$pageSchemas['data_schemas'] = isset($page->data_schemas) ? \TMS_MODEL::toJson($page->data_schemas) : '[]';
			$pageSchemas['act_schemas'] = isset($page->act_schemas) ? \TMS_MODEL::toJson($page->act_schemas) : '[]';
			$rst = $modelPage->update(
				'xxt_enroll_page',
				$pageSchemas,
				"aid='$appId' and id={$ap->id}"
			);
			/* 填充页面 */
			if (!empty($page->code)) {
				$code = (array) $page->code;
				/* 页面存在动态信息 */
				$matched = [];
				$pattern = '/<!-- begin: generate by schema -->.*<!-- end: generate by schema -->/s';
				if (preg_match($pattern, $code['html'], $matched)) {
					$html = $modelPage->htmlBySchema($page->data_schemas, $matched[0]);
					$code['html'] = preg_replace($pattern, $html, $code['html']);
				}
				$modelCode->modify($ap->code_id, $code);
			}
		}

		return $oTemplateConfig;
	}
	/**
	 * 应用的微信二维码
	 *
	 * @param string $site
	 * @param string $appId
	 *
	 */
	public function wxQrcode_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\wx\call\qrcode');

		$qrcodes = $modelQrcode->byMatter('enroll', $app);

		return new \ResponseData($qrcodes);
	}
	/**
	 * 应用的易信二维码
	 *
	 * @param string $site
	 * @param string $app
	 *
	 */
	public function yxQrcode_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelQrcode = $this->model('sns\yx\call\qrcode');

		$qrcode = $modelQrcode->byMatter('enroll', $app);

		return new \ResponseData($qrcode);
	}
	/**
	 * 删除一个活动
	 *
	 * 只允许活动的创建者删除数据，其他用户不允许删除
	 * 如果没有报名数据，就将活动彻底删除，否则只是打标记
	 *
	 * @param string $site site's id
	 * @param string $app app's id
	 *
	 */
	public function remove_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}
		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, 'id,siteid,scenario,title,summary,pic,mission_id,creater');
		if ($oApp === false) {
			return new \ObjectNotFoundError();
		}
		if ($oApp->creater !== $oUser->id) {
			if (!$this->model('site')->isAdmin($oApp->siteid, $oUser->id)) {
				return new \ResponseError('没有删除数据的权限');
			}
			$rst = $modelApp->remove($oUser, $oApp, 'Recycle');
		} else {
			$q = [
				'count(*)',
				'xxt_enroll_record',
				['aid' => $oApp->id],
			];
			if ((int) $modelApp->query_val_ss($q) > 0) {
				$rst = $modelApp->remove($oUser, $oApp, 'Recycle');
			} else {
				$modelApp->delete(
					'xxt_enroll_receiver',
					["aid" => $oApp->id]
				);
				$modelApp->delete(
					'xxt_enroll_round',
					["aid" => $oApp->id]
				);
				$modelApp->delete(
					'xxt_code_page',
					"id in (select code_id from xxt_enroll_page where aid='" . $modelApp->escape($oApp->id) . "')"
				);
				$modelApp->delete(
					'xxt_enroll_page',
					["aid" => $oApp->id]
				);
				$rst = $modelApp->remove($oUser, $oApp, 'D');
			}
		}

		return new \ResponseData($rst);
	}
	/**
	 * 将应用定义导出为模板
	 */
	public function exportAsTemplate_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelEnroll = \TMS_APP::M('matter\enroll');
		$oApp = $modelEnroll->byId($app);
		$template = new \stdClass;
		/* setting */
		!empty($oApp->scenario) && $template->scenario = $oApp->scenario;
		$template->count_limit = $oApp->count_limit;

		/* schema */
		$template->schema = json_decode($oApp->data_schemas);

		/* pages */
		$pages = $oApp->pages;
		foreach ($pages as &$rec) {
			$rec->data_schemas = json_decode($rec->data_schemas);
			$rec->act_schemas = json_decode($rec->act_schemas);
			$code = new \stdClass;
			$code->css = $rec->css;
			$code->js = $rec->js;
			$code->html = $rec->html;
			$rec->code = $code;
		}
		$template->pages = $pages;

		/* entry_rule */
		$template->entryRule = $oApp->entryRule;

		/* records */
		$records = $modelEnroll->query_objs_ss([
			'id,userid,wx_openid,yx_openid,qy_openid,nickname,data',
			'xxt_enroll_record',
			['siteid' => $site, 'aid' => $app],
		]);

		foreach ($records as &$rec) {
			$rec->data = json_decode($rec->data);
		}
		$template->records = $records;

		$template = \TMS_MODEL::toJson($template);
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header('Content-disposition: attachment; filename=' . $oApp->title . '.json');
		header("Content-Type: text/plain");
		header('Content-Length: ' . strlen($template));
		die($template);
	}
	/**
	 * 登记情况汇总信息
	 *
	 * @param string $site site'id
	 * @param string $app app'id
	 *
	 */
	public function opData_action($site, $app) {
		if (false === ($oUser = $this->accountUser())) {
			return new \ResponseTimeout();
		}

		$modelApp = $this->model('matter\enroll');
		$oApp = $modelApp->byId($app, ['cascaded' => 'N']);
		if (false === $oApp) {
			return new \ObjectNotFoundError();
		}
		$opData = $modelApp->opData($oApp);

		return new \ResponseData($opData);
	}
}