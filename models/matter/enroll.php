<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class enroll_model extends app_base {
	/**
	 * 记录日志时需要的列
	 */
	const LOG_FIELDS = 'siteid,id,title,summary,pic,mission_id';
	/**
	 *
	 */
	protected function table() {
		return 'xxt_enroll';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id, $oParams = null) {
		$url = 'http://' . APP_HTTP_HOST;

		if ($siteId === 'platform') {
			if ($oApp = $this->byId($id, ['cascaded' => 'N'])) {
				$url .= "/rest/site/fe/matter/enroll";
				$url .= "?site={$oApp->siteid}&app=" . $id;
			} else {
				$url = 'http://' . APP_HTTP_HOST . '/404.html';
			}
		} else {
			$url .= "/rest/site/fe/matter/enroll";
			$url .= "?site={$siteId}&app=" . $id;
		}

		if (isset($oParams) && is_object($oParams)) {
			foreach ($oParams as $k => $v) {
				if (is_string($v)) {
					$url .= '&' . $k . '=' . $v;
				}
			}
		}

		return $url;
	}
	/**
	 * 登记活动的汇总展示链接
	 */
	public function getOpUrl($siteId, $id) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= '/rest/site/op/matter/enroll';
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 * 登记活动的统计报告链接
	 */
	public function getRpUrl($siteId, $id) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= '/rest/site/op/matter/enroll/report';
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 *
	 * @param string $aid
	 * @param array $options
	 */
	public function &byId($aid, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = [
			$fields,
			'xxt_enroll',
			["id" => $aid],
		];

		if ($oApp = $this->query_obj_ss($q)) {
			$oApp->type = 'enroll';
			if (isset($oApp->siteid) && isset($oApp->id)) {
				$oApp->entryUrl = $this->getEntryUrl($oApp->siteid, $oApp->id);
				$oApp->opUrl = $this->getOpUrl($oApp->siteid, $oApp->id);
				$oApp->rpUrl = $this->getRpUrl($oApp->siteid, $oApp->id);
			}
			if ($fields === '*' || false !== strpos($fields, 'entry_rule')) {
				if (empty($oApp->entry_rule)) {
					$oApp->entry_rule = new \stdClass;
					$oApp->entry_rule->scope = 'none';
				} else {
					$oApp->entry_rule = json_decode($oApp->entry_rule);
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'data_schemas')) {
				if (!empty($oApp->data_schemas)) {
					$oApp->dataSchemas = json_decode($oApp->data_schemas);
				} else {
					$oApp->dataSchemas = [];
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'assigned_nickname')) {
				if (!empty($oApp->assigned_nickname)) {
					$oApp->assignedNickname = json_decode($oApp->assigned_nickname);
				} else {
					$oApp->assignedNickname = new \stdClass;
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'user_task')) {
				if (!empty($oApp->user_task)) {
					$oApp->userTask = json_decode($oApp->user_task);
				} else {
					$oApp->userTask = new \stdClass;
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'scenario_config')) {
				if (!empty($oApp->scenario_config)) {
					$oApp->scenarioConfig = json_decode($oApp->scenario_config);
				} else {
					$oApp->scenarioConfig = new \stdClass;
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'round_cron')) {
				if (!empty($oApp->round_cron)) {
					$oApp->roundCron = json_decode($oApp->round_cron);
					$modelRnd = \TMS_APP::M('matter\enroll\round');
					foreach ($oApp->roundCron as &$rec) {
						$rules[0] = $rec;
						$rec->case = $modelRnd->byCron($rules);
					}
				} else {
					$oApp->roundCron = [];
				}
			}
			if ($fields === '*' || false !== strpos($fields, 'rp_config')) {
				if (!empty($oApp->rp_config)) {
					$oApp->rpConfig = json_decode($oApp->rp_config);
				} else {
					$oApp->rpConfig = new \stdClass;
				}
			}
			if (!empty($oApp->matter_mg_tag)) {
				$oApp->matter_mg_tag = json_decode($oApp->matter_mg_tag);
			}
			$oApp->dataTags = $this->model('matter\enroll\tag')->byApp($oApp);

			$modelPage = $this->model('matter\enroll\page');
			if ($cascaded === 'Y') {
				$oApp->pages = $modelPage->byApp($aid);
			} else {
				$oApp->pages = $modelPage->byApp($aid, ['cascaded' => 'N', 'fields' => 'id,name,type,title']);
			}
		}

		return $oApp;
	}
	/**
	 * 返回登记活动列表
	 */
	public function &bySite($site, $page = 1, $size = 30, $mission = null, $scenario = null) {
		$result = array();

		$q = array(
			'*',
			'xxt_enroll a',
			"siteid='$site' and state<>0",
		);
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		if (!empty($mission)) {
			$q[2] .= " and exists(select 1 from xxt_mission_matter where mission_id='$mission' and matter_type='enroll' and matter_id=a.id)";
		}
		$q2['o'] = 'a.modify_at desc';
		$q2['r']['o'] = ($page - 1) * $size;
		$q2['r']['l'] = $size;
		if ($a = $this->query_objs_ss($q, $q2)) {
			$result['apps'] = $a;
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result['total'] = $total;
		}

		return $result;
	}
	/**
	 * 返回登记活动列表
	 */
	public function &byMission($mission, $scenario = null, $page = 1, $size = 30) {
		$result = new \stdClass;

		$q = [
			'*',
			'xxt_enroll',
			"state<>0 and mission_id='$mission'",
		];
		if (!empty($scenario)) {
			$q[2] .= " and scenario='$scenario'";
		}
		$q2['o'] = 'modify_at desc';
		if ($page) {
			$q2['r'] = ['o' => ($page - 1) * $size, 'l' => $size];
		}
		$result->apps = $this->query_objs_ss($q, $q2);
		if ($page && $size) {
			$q[0] = 'count(*)';
			$total = (int) $this->query_val_ss($q);
			$result->total = $total;
		} else {
			$result->total = count($result->apps);
		}

		return $result;
	}
	/**
	 * 更新登记活动标签
	 */
	public function updateTags($aid, $tags) {
		if (empty($tags)) {
			return false;
		}
		if (is_array($tags)) {
			$tags = implode(',', $tags);
		}

		$options = array('fields' => 'id,tags', 'cascaded' => 'N');
		$oApp = $this->byId($aid, $options);
		if (empty($oApp->tags)) {
			$this->update('xxt_enroll', ['tags' => $tags], ["id" => $aid]);
		} else {
			$existent = explode(',', $oApp->tags);
			$checked = explode(',', $tags);
			$updated = array();
			foreach ($checked as $c) {
				if (!in_array($c, $existent)) {
					$updated[] = $c;
				}
			}
			if (count($updated)) {
				$updated = array_merge($existent, $updated);
				$updated = implode(',', $updated);
				$this->update('xxt_enroll', ['tags' => $updated], ["id" => $aid]);
			}
		}

		return true;
	}
	/**
	 * 根据邀请到的用户数量进行的排名
	 */
	public function rankByFollower($mpid, $aid, $openid) {
		$modelRec = \TMS_APP::M('matter\enroll\record');
		$user = new \stdClass;
		$user->openid = $openid;
		$last = $modelRec->lastByUser($aid, $user);

		$q = array(
			'count(*)',
			'xxt_enroll_record',
			"state=1 and aid='$aid' and follower_num>$last->follower_num",
		);

		$rank = (int) $this->query_val_ss($q);

		return $rank + 1;
	}
	/**
	 * 登记活动运行情况摘要
	 *
	 * @param object $oApp
	 *
	 * @return
	 */
	public function &opData(&$oApp, $onlyActiveRound = false) {
		$modelUsr = $this->model('matter\enroll\user');
		$modelRnd = $this->model('matter\enroll\round');

		$mschemaIds = [];
		if (!empty($oApp->entry_rule) && is_object($oApp->entry_rule)) {
			if (!empty($oApp->entry_rule->member) && is_object($oApp->entry_rule->member)) {
				foreach ($oApp->entry_rule->member as $mschemaId => $rule) {
					if (!empty($rule->entry)) {
						$mschemaIds[] = $mschemaId;
					}
				}
			}
		}

		if ($onlyActiveRound) {
			if ($oActiveRound = $modelRnd->getActive($oApp)) {
				$recentRounds[] = $oActiveRound;
			}
		} else {
			$page = (object) ['num' => 1, 'size' => 3];
			$result = $modelRnd->byApp($oApp, ['fields' => 'rid,title', 'page' => $page]);
			$recentRounds = $result->rounds;
		}

		if (empty($recentRounds)) {
			$oRound = new \stdClass;
			/* total */
			$q = [
				'count(*)',
				'xxt_enroll_record',
				['aid' => $oApp->id, 'state' => 1],
			];
			$oRound->total = $this->query_val_ss($q);
			/* remark */
			$q = [
				'count(*)',
				'xxt_enroll_record_remark',
				['aid' => $oApp->id],
			];
			$oRound->remark_total = $this->query_val_ss($q);
			/* enrollee */
			$oEnrollees = $modelUsr->enrolleeByApp($oApp);
			$oRound->enrollee_num = $oEnrollees->total;
			/* member */
			if (!empty($mschemaIds)) {
				$oRound->mschema = new \stdClass;
				foreach ($mschemaIds as $mschemaId) {
					$oRound->mschema->{$mschemaId} = $this->_opByMschema($oApp->id, $mschemaId);
				}
			}
			$summary[] = $oRound;
		} else {
			$summary = [];
			$oActiveRound = $modelRnd->getActive($oApp);
			foreach ($recentRounds as $oRound) {
				if ($oActiveRound && $oRound->rid === $oActiveRound->rid) {
					$oRound->active = 'Y';
				}
				/* total */
				$q = [
					'count(*)',
					'xxt_enroll_record',
					['aid' => $oApp->id, 'state' => 1, 'rid' => $oRound->rid],
				];
				$oRound->total = $this->query_val_ss($q);
				/* remark */
				$q = [
					'count(*)',
					'xxt_enroll_record_remark',
					['aid' => $oApp->id, 'rid' => $oRound->rid],
				];
				$oRound->remark_total = $this->query_val_ss($q);
				/* enrollee */
				$oEnrollees = $modelUsr->enrolleeByApp($oApp, '', '', ['rid' => $oRound->rid]);
				$oRound->enrollee_num = $oEnrollees->total;

				/* member */
				if (!empty($mschemaIds)) {
					$oRound->mschema = new \stdClass;
					foreach ($mschemaIds as $mschemaId) {
						$oRound->mschema->{$mschemaId} = $this->_opByMschema($oApp->id, $mschemaId, $oRound->rid);
					}
				}
				$summary[] = $oRound;
			}
		}

		return $summary;
	}
	/**
	 * 通讯录联系人登记情况
	 */
	private function _opByMschema($appId, $mschemaId, $rid = null) {
		$result = new \stdClass;
		$q = [
			'count(*)',
			'xxt_site_member',
			"verified='Y' and forbidden='N' and schema_id=$mschemaId and userid in (select r.userid from xxt_enroll_record r where r.aid='{$appId}' and r.state=1 ",
		];
		!empty($rid) && $q[2] .= " and r.rid='{$rid}'";
		$q[2] .= ")";

		$result->enrolled = $this->query_val_ss($q);

		return $result;
	}
	/**
	 * 获得参加登记活动的用户的昵称
	 *
	 * @param object $oApp
	 * @param object $oUser [uid,nickname]
	 */
	public function getUserNickname(&$oApp, $oUser) {
		if (empty($oUser->uid)) {
			return '';
		}
		$nickname = '';
		$entryRule = $oApp->entry_rule;
		if (isset($entryRule->anonymous) && $entryRule->anonymous === 'Y') {
			/* 匿名访问 */
			$nickname = '';
		} else {
			if (isset($entryRule->scope) && $entryRule->scope === 'member') {
				foreach ($entryRule->member as $schemaId => $rule) {
					$modelMem = $this->model('site\user\member');
					if (empty($oUser->unionid)) {
						$aMembers = $modelMem->byUser($oUser->uid, ['schemas' => $schemaId]);
						if (count($aMembers) === 1) {
							$oMember = $aMembers[0];
							if ($oMember->verified === 'Y') {
								$nickname = empty($oMember->name) ? $oMember->identity : $oMember->name;
								break;
							}
						}
					} else {
						$modelAcnt = $this->model('site\user\account');
						$aUnionUsers = $modelAcnt->byUnionid($oUser->unionid, ['siteid' => $oApp->siteid, 'fields' => 'uid']);
						foreach ($aUnionUsers as $oUnionUser) {
							$aMembers = $modelMem->byUser($oUnionUser->uid, ['schemas' => $schemaId]);
							if (count($aMembers) === 1) {
								$oMember = $aMembers[0];
								if ($oMember->verified === 'Y') {
									$nickname = empty($oMember->name) ? $oMember->identity : $oMember->name;
									break;
								}
							}
						}
						if (!empty($nickname)) {
							break;
						}
					}
				}
			} else if (isset($entryRule->scope) && $entryRule->scope === 'sns') {
				$modelAcnt = $this->model('site\user\account');
				if ($siteUser = $modelAcnt->byId($oUser->uid)) {
					foreach ($entryRule->sns as $snsName => $rule) {
						if ($snsName === 'wx') {
							$modelWx = $this->model('sns\wx');
							if (($wxConfig = $modelWx->bySite($oApp->siteid)) && $wxConfig->joined === 'Y') {
								$snsSiteId = $oApp->siteid;
							} else {
								$snsSiteId = 'platform';
							}
						} else {
							$snsSiteId = $oApp->siteid;
						}
						$modelSnsUser = $this->model('sns\\' . $snsName . '\fan');
						if ($snsUser = $modelSnsUser->byOpenid($snsSiteId, $siteUser->{$snsName . '_openid'})) {
							$nickname = $snsUser->nickname;
							break;
						}
					}
				}
			} else if (empty($entryRule->scope) || $entryRule->scope === 'none' || $entryRule->scope === 'group') {
				if (!empty($oApp->mission_id)) {
					/* 从项目中获得用户昵称 */
					$oMission = (object) ['id' => $oApp->mission_id];
					$modelMisUsr = $this->model('matter\mission\user');
					$oMisUsr = $modelMisUsr->byId($oMission, $oUser->uid, ['fields' => 'nickname']);
					if ($oMisUsr) {
						$nickname = $oMisUsr->nickname;
					} else {
						$nickname = empty($oUser->nickname) ? '' : $oUser->nickname;
					}
				} else {
					$nickname = empty($oUser->nickname) ? '' : $oUser->nickname;
				}
			}
		}

		return $nickname;
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
	public function createByTemplate($oUser, $oSite, $oCustomConfig, $oMission = null, $scenario = 'common', $template = 'simple') {
		$current = time();
		$oNewApp = new \stdClass;
		/* 从站点或项目获得的信息 */
		if (empty($oMission)) {
			$oNewApp->pic = $oSite->heading_pic;
			$oNewApp->summary = '';
			$oNewApp->use_mission_header = 'N';
			$oNewApp->use_mission_footer = 'N';
			$oMission = null;
		} else {
			$oNewApp->pic = $oMission->pic;
			$oNewApp->summary = $oMission->summary;
			$oNewApp->mission_id = $oMission->id;
			$oNewApp->use_mission_header = 'Y';
			$oNewApp->use_mission_footer = 'Y';
			$oMisEntryRule = $oMission->entry_rule;
		}
		$appId = uniqid();
		/* 使用指定模板，插入选择分组题 */
		$oTemplateConfig = $this->_getSysTemplate($scenario, $template);
		/* 进入规则 */
		$oEntryRule = $oTemplateConfig->entryRule;
		if (empty($oEntryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}
		if (!empty($oCustomConfig->proto->entryRule->scope)) {
			$oProtoEntryRule = $oCustomConfig->proto->entryRule;
			$oEntryRule->scope = $oProtoEntryRule->scope;
			switch ($oEntryRule->scope) {
			case 'group':
				if (!empty($oProtoEntryRule->group->id) && !empty($oProtoEntryRule->group->round->id)) {
					$oEntryRule->group = (object) ['id' => $oProtoEntryRule->group->id];
					$oEntryRule->group->round = (object) ['id' => $oProtoEntryRule->group->round->id];
				}
				break;
			case 'member':
				if (isset($oProtoEntryRule->mschemas)) {
					$oEntryRule->member = new \stdClass;
					foreach ($oProtoEntryRule->mschemas as $oMschema) {
						$oRule = new \stdClass;
						$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
						$oEntryRule->member->{$oMschema->id} = $oRule;
					}
					$oEntryRule->other = new \stdClass;
					$oEntryRule->other->entry = '$memberschema';
				}
				break;
			case 'sns':
				$oRule = new \stdClass;
				$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
				$oSns = new \stdClass;
				if (isset($oProtoEntryRule->sns)) {
					foreach ($oProtoEntryRule->sns as $snsName => $bValid) {
						if ($bValid) {
							$oSns->{$snsName} = $oRule;
						}
					}
				} else {
					$modelWx = $this->model('sns\wx');
					$wxOptions = ['fields' => 'joined'];
					if (($wx = $modelWx->bySite($site, $wxOptions)) && $wx->joined === 'Y') {
						$oSns->wx = $oRule;
					} else if (($wx = $modelWx->bySite('platform', $wxOptions)) && $wx->joined === 'Y') {
						$oSns->wx = $oRule;
					}
					$yxOptions = ['fields' => 'joined'];
					if ($yx = $this->model('sns\yx')->bySite($site, $yxOptions)) {
						if ($yx->joined === 'Y') {
							$oSns->yx = $oRule;
						}
					}
					if ($qy = $this->model('sns\qy')->bySite($site, ['fields' => 'joined'])) {
						if ($qy->joined === 'Y') {
							$oSns->qy = $oRule;
						}
					}
				}
				$oEntryRule->sns = $oSns;
				$oEntryRule->other = new \stdClass;
				$oEntryRule->other->entry = '$mpfollow';
				break;
			}
		} else if (isset($oMisEntryRule)) {
			if (isset($oMisEntryRule->scope) && $oMisEntryRule->scope !== 'none') {
				$oEntryRule->scope = $oMisEntryRule->scope;
				switch ($oEntryRule->scope) {
				case 'member':
					if (isset($oMisEntryRule->member)) {
						$oEntryRule->member = $oMisEntryRule->member;
						foreach ($oEntryRule->member as &$oRule) {
							$oRule->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
						}
						$oEntryRule->other = new \stdClass;
						$oEntryRule->other->entry = '$memberschema';
					}
					break;
				case 'sns':
					$oEntryRule->sns = new \stdClass;
					if (isset($oMisEntryRule->sns)) {
						foreach ($oMisEntryRule->sns as $snsName => $oRule) {
							if (isset($oRule->entry) && $oRule->entry === 'Y') {
								$oEntryRule->sns->{$snsName} = new \stdClass;
								$oEntryRule->sns->{$snsName}->entry = isset($oEntryRule->otherwise->entry) ? $oEntryRule->otherwise->entry : '';
							}
						}
						$oEntryRule->other = new \stdClass;
						$oEntryRule->other->entry = '$mpfollow';
					}
					break;
				}
			}
		}
		if (!isset($oEntryRule->scope)) {
			$oEntryRule->scope = 'none';
		}
		/* 关联了分组活动 */
		if (!empty($oCustomConfig->proto->groupApp->id)) {
			$oNewApp->group_app_id = $this->escape($oCustomConfig->proto->groupApp->id);
			$oRoundSchema = new \stdClass;
			$oRoundSchema->id = '_round_id';
			$oRoundSchema->type = 'single';
			$oRoundSchema->title = '分组名称';
			$oRoundSchema->required = 'Y';
			$oRoundSchema->ops = [];
			$oGroupApp = $this->model('matter\group')->byId($oNewApp->group_app_id);
			if (!empty($oGroupApp->rounds)) {
				foreach ($oGroupApp->rounds as $oRound) {
					$op = new \stdClass;
					$op->v = $oRound->round_id;
					$op->l = $oRound->title;
					$oRoundSchema->ops[] = $op;
				}
			}
			if (empty($oTemplateConfig->schema)) {
				$oTemplateConfig->schema = [$oRoundSchema];
			} else {
				array_splice($oTemplateConfig->schema, 0, 0, [$oRoundSchema]);
			}
			/**
			 * 处理页面数据定义
			 */
			foreach ($oTemplateConfig->pages as $oAppPage) {
				if (!empty($oAppPage->data_schemas)) {
					/* 自动添加项目阶段定义 */
					if ($oAppPage->type === 'I') {
						$newPageSchema = new \stdClass;
						$schemaPhaseConfig = new \stdClass;
						$schemaPhaseConfig->component = 'R';
						$schemaPhaseConfig->align = 'V';
						$newPageSchema->schema = $oRoundSchema;
						$newPageSchema->config = $schemaPhaseConfig;
						array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
					} else if ($oAppPage->type === 'V') {
						$newPageSchema = new \stdClass;
						$schemaPhaseConfig = new \stdClass;
						$schemaPhaseConfig->id = 'V' . time();
						$schemaPhaseConfig->pattern = 'record';
						$schemaPhaseConfig->inline = 'Y';
						$schemaPhaseConfig->splitLine = 'Y';
						$newPageSchema->schema = $oRoundSchema;
						$newPageSchema->config = $schemaPhaseConfig;
						array_splice($oAppPage->data_schemas, 0, 0, [$newPageSchema]);
					}
				}
			}
		}
		/* 通讯录关联题目 */
		if (!empty($oEntryRule->scope) && $oEntryRule->scope === 'member') {
			$mschemaIds = array_keys(get_object_vars($oEntryRule->member));
			if (!empty($mschemaIds)) {
				$oMschema1st = $this->model('site\user\memberschema')->byId($mschemaIds[0], ['fields' => 'id,attr_name,attr_mobile,attr_email', 'cascaded' => 'N']);
				/* 应用的题目 */
				foreach ($oTemplateConfig->schema as $oSchema) {
					if ($oSchema->type === 'shorttext' && in_array($oSchema->id, ['name', 'email', 'mobile'])) {
						if (false === $oMschema1st->attrs->{$oSchema->id}->hide) {
							$oSchema->type = 'member';
							$oSchema->schema_id = $oMschema1st->id;
							$oSchema->id = 'member.' . $oSchema->id;
						}
					}
				}
				/* 页面的题目 */
				foreach ($oTemplateConfig->pages as $oAppPage) {
					if (!empty($oAppPage->data_schemas)) {
						foreach ($oAppPage->data_schemas as $oSchemaConfig) {
							$oSchema = $oSchemaConfig->schema;
							if ($oSchema->type === 'shorttext' && in_array($oSchema->id, ['name', 'email', 'mobile'])) {
								if (false === $oMschema1st->attrs->{$oSchema->id}->hide) {
									$oSchema->type = 'member';
									$oSchema->schema_id = $oMschema1st->id;
									$oSchema->id = 'member.' . $oSchema->id;
								}
							}
						}
					}
				}
			}
		}
		/* 添加页面 */
		$this->_addPageByTemplate($oUser, $oSite, $oMission, $appId, $oTemplateConfig, $oCustomConfig);

		/* 登记数量限制 */
		if (isset($oTemplateConfig->count_limit)) {
			$oNewApp->count_limit = $oTemplateConfig->count_limit;
		}
		if (isset($oTemplateConfig->can_repos)) {
			$oNewApp->can_repos = $oTemplateConfig->repos;
		}
		if (isset($oTemplateConfig->can_rank)) {
			$oNewApp->can_rank = $oTemplateConfig->can_rank;
		}
		if (isset($oTemplateConfig->enrolled_entry_page)) {
			$oNewApp->enrolled_entry_page = $oTemplateConfig->enrolled_entry_page;
		}
		/* 场景设置 */
		if (isset($oTemplateConfig->scenarioConfig)) {
			$oScenarioConfig = $oTemplateConfig->scenarioConfig;
			if (isset($oCustomConfig->scenarioConfig) && is_object($oCustomConfig->scenarioConfig)) {
				foreach ($oCustomConfig->scenarioConfig as $k => $v) {
					$oScenarioConfig->{$k} = $v;
				}
			}
			$oNewApp->scenario_config = json_encode($oScenarioConfig);
		}
		$oNewApp->scenario = $scenario;
		/* create app */
		$oNewApp->id = $appId;
		$oNewApp->siteid = $oSite->id;
		$oNewApp->title = empty($oCustomConfig->proto->title) ? '新登记活动' : $this->escape($oCustomConfig->proto->title);
		$oNewApp->summary = empty($oCustomConfig->proto->summary) ? '' : $this->escape($oCustomConfig->proto->summary);
		$oNewApp->can_repos = empty($oCustomConfig->proto->can_repos) ? 'N' : $this->escape($oCustomConfig->proto->can_repos);
		$oNewApp->can_rank = empty($oCustomConfig->proto->can_rank) ? 'N' : $this->escape($oCustomConfig->proto->can_rank);
		$oNewApp->enroll_app_id = empty($oCustomConfig->proto->enrollApp->id) ? '' : $this->escape($oCustomConfig->proto->enrollApp->id);
		$oNewApp->start_at = isset($oCustomConfig->proto->start_at) ? $oCustomConfig->proto->start_at : 0;
		$oNewApp->end_at = isset($oCustomConfig->proto->end_at) ? $oCustomConfig->proto->end_at : 0;
		$oNewApp->entry_rule = json_encode($oEntryRule);
		$oNewApp->can_siteuser = 'Y';
		isset($oTemplateConfig) && $oNewApp->data_schemas = $this->toJson($oTemplateConfig->schema);

		/*任务码*/
		$entryUrl = $this->getOpUrl($oSite->id, $appId);
		$code = $this->model('q\url')->add($oUser, $oSite->id, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$oNewApp = $this->create($oUser, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return $oNewApp;
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
		$oConfig = file_get_contents($templateDir . '/config.json');
		$oConfig = preg_replace('/\t|\r|\n/', '', $oConfig);
		$oConfig = json_decode($oConfig);
		/**
		 * 处理页面
		 */
		if (!empty($oConfig->pages)) {
			foreach ($oConfig->pages as $oPage) {
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

		return $oConfig;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $appId
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$user, &$site, $oMission, &$appId, &$oTemplateConfig) {
		$pages = $oTemplateConfig->pages;
		if (empty($pages)) {
			return false;
		}

		$modelPage = $this->model('matter\enroll\page');
		$modelCode = $this->model('code\page');

		/* 包含项目阶段 */
		if (isset($oTemplateConfig->schema_include_mission_phases) && $oTemplateConfig->schema_include_mission_phases === 'Y') {
			if (!empty($oMissio->multi_phase) && $oMission->multi_phase === 'Y') {
				$schemaPhase = new \stdClass;
				$schemaPhase->id = 'phase';
				$schemaPhase->title = '项目阶段';
				$schemaPhase->type = 'phase';
				$schemaPhase->ops = [];
				$phases = $this->model('matter\mission\phase')->byMission($oMission->id);
				foreach ($phases as $phase) {
					$newOp = new \stdClass;
					$newOp->l = $phase->title;
					$newOp->v = $phase->phase_id;
					$schemaPhase->ops[] = $newOp;
				}
				$oTemplateConfig->schema[] = $schemaPhase;
			}
		}
		/**
		 * 处理页面
		 */
		foreach ($pages as $page) {
			$ap = $modelPage->add($user, $site->id, $appId, (array) $page);
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
			} else {
				/* 自动添加项目阶段定义 */
				if (isset($schemaPhase)) {
					if ($page->type === 'I') {
						$newPageSchema = new \stdClass;
						$schemaPhaseConfig = new \stdClass;
						$schemaPhaseConfig->component = 'R';
						$schemaPhaseConfig->align = 'V';
						$newPageSchema->schema = $schemaPhase;
						$newPageSchema->config = $schemaPhaseConfig;
						$page->data_schemas[] = $newPageSchema;
					} else if ($page->type === 'V') {
						$newPageSchema = new \stdClass;
						$schemaPhaseConfig = new \stdClass;
						$schemaPhaseConfig->id = 'V' . time();
						$schemaPhaseConfig->pattern = 'record';
						$schemaPhaseConfig->inline = 'Y';
						$schemaPhaseConfig->splitLine = 'Y';
						$newPageSchema->schema = $schemaPhase;
						$newPageSchema->config = $schemaPhaseConfig;
						$page->data_schemas[] = $newPageSchema;
					}
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
}