<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 *
 */
class enroll_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_enroll';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'enroll';
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
		if (isset($options['where'])) {
			foreach ($options['where'] as $key => $value) {
				$q[2][$key] = $value;
			}
		}

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
	public function &opData(&$oApp) {
		$modelRnd = $this->model('matter\enroll\round');
		$modelRec = $this->model('matter\enroll\record');
		$page = (object) ['num' => 1, 'size' => 3];
		$result = $modelRnd->byApp($oApp, ['fields' => 'rid,title', 'page' => $page]);
		$rounds = $result->rounds;
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
		if (empty($rounds)) {
			$summary = new \stdClass;
			/* total */
			$q = [
				'count(*)',
				'xxt_enroll_record',
				['aid' => $oApp->id, 'state' => 1],
			];
			$summary->total = $this->query_val_ss($q);
			/* remark */
			$q = [
				'count(*)',
				'xxt_enroll_record_remark',
				['aid' => $oApp->id],
			];
			$summary->remark_total = $this->query_val_ss($q);
			/* enrollee */
			$enrollees = $modelRec->enrolleeByApp($oApp);
			$summary->enrollee_num = count($enrollees);
			/* member */
			if (!empty($mschemaIds)) {
				$summary->mschema = new \stdClass;
				foreach ($mschemaIds as $mschemaId) {
					$summary->mschema->{$mschemaId} = $this->_opByMschema($oApp->id, $mschemaId);
				}
			}
		} else {
			$summary = [];
			$oActiveRound = $modelRnd->getActive($oApp);
			foreach ($rounds as $oRound) {
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
				$enrollees = $modelRec->enrolleeByApp($oApp, ['rid' => $oRound->rid]);
				$oRound->enrollee_num = count($enrollees);

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
			} else if (empty($entryRule->scope) || $entryRule->scope === 'none') {
				/* 不限制用户访问来源 */
				$nickname = empty($oUser->nickname) ? '' : $oUser->nickname;
			}
		}

		return $nickname;
	}
}