<?php
namespace matter;

require_once dirname(__FILE__) . '/app_base.php';
/**
 * 签到活动
 */
class signin_model extends app_base {
	/**
	 *
	 */
	protected function table() {
		return 'xxt_signin';
	}
	/**
	 *
	 */
	public function getTypeName() {
		return 'signin';
	}
	/**
	 *
	 */
	public function getEntryUrl($siteId, $id, $roundId = null) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= '/rest/site/fe/matter/signin';
		if ($siteId === 'platform') {
			if ($oApp = $this->byId($id, ['cascaded' => 'N'])) {
				$url .= "?site={$oApp->siteid}&app=" . $id;
			} else {
				$url = 'http://' . APP_HTTP_HOST . '/404.html';
			}
		} else {
			$url .= "?site={$siteId}&app=" . $id;
		}

		if (!empty($roundId)) {
			$url .= '&round=' . $roundId;
		}

		return $url;
	}
	/**
	 * 签到活动的汇总展示链接
	 */
	public function getOpUrl($siteId, $id) {
		$url = 'http://' . APP_HTTP_HOST;
		$url .= '/rest/site/op/matter/signin';
		$url .= "?site={$siteId}&app=" . $id;

		return $url;
	}
	/**
	 *
	 * @param string $appId
	 * @param $options array []
	 */
	public function &byId($appId, $options = []) {
		$fields = isset($options['fields']) ? $options['fields'] : '*';
		$cascaded = isset($options['cascaded']) ? $options['cascaded'] : 'Y';
		$q = [
			$fields,
			'xxt_signin',
			["id" => $appId],
		];
		if (isset($options['where'])) {
			foreach ($options['where'] as $key => $value) {
				$q[2][$key] = $value;
			}
		}
		if ($oApp = $this->query_obj_ss($q)) {
			$oApp->type = 'signin';
			if (isset($oApp->siteid) && isset($oApp->id)) {
				$oApp->entryUrl = $this->getEntryUrl($oApp->siteid, $oApp->id);
				$oApp->opUrl = $this->getOpUrl($oApp->siteid, $oApp->id);
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
			if (!empty($oApp->matter_mg_tag)) {
				$oApp->matter_mg_tag = json_decode($oApp->matter_mg_tag);
			}
			if ($cascaded === 'Y') {
				/* 页面 */
				$oApp->pages = $this->model('matter\signin\page')->byApp($oApp->id);
				/* 轮次 */
				$oApp->rounds = $this->model('matter\signin\round')->byApp($oApp->id, ['fields' => 'id,rid,title,start_at,end_at,late_at,state']);
			}
		}

		return $oApp;
	}
	/**
	 * 返回签到活动列表
	 */
	public function &bySite($siteId, $page = null, $size = null, $onlySns = 'N', $aOptions = []) {
		$result = new \stdClass;
		$q = [
			"*",
			'xxt_signin s',
			"state<>0 and siteid='$siteId'",
		];
		if (!empty($aOptions['byTitle'])) {
			$q[2] .= " and title like '%" . $this->escape($aOptions['byTitle']) . "%'";
		}
		if (!empty($aOptions['byTags'])) {
			foreach ($aOptions['byTags'] as $tag) {
				$q[2] .= " and matter_mg_tag like '%" . $this->escape($tag->id) . "%'";
			}
		}
		if ($onlySns === 'Y') {
			$q[2] .= " and entry_rule like '%\"scope\":\"sns\"%'";
		}
		if (isset($aOptions['byStar'])) {
			$q[2] .= " and exists(select 1 from xxt_account_topmatter t where t.matter_type='signin' and t.matter_id=s.id and userid='{$aOptions['byStar']}')";
		}
		$q2['o'] = 'modify_at desc';
		if ($page && $size) {
			$q2['r']['o'] = ($page - 1) * $size;
			$q2['r']['l'] = $size;
		}
		$result->apps = $this->query_objs_ss($q, $q2);
		if (count($result->apps)) {
			foreach ($result->apps as $oApp) {
				$oApp->type = 'signin';
				/* 是否已经星标 */
				if ($aOptions['user']) {
					$oUser = $aOptions['user'];
					$qStar = [
						'id',
						'xxt_account_topmatter',
						['matter_id' => $oApp->id, 'matter_type' => 'signin', 'userid' => $oUser->id],
					];
					if ($oStar = $this->query_obj_ss($qStar)) {
						$oApp->star = $oStar->id;
					}
				}
			}
		}
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
	 * 返回签到活动列表
	 */
	public function &byMission($mission, $aOptions = [], $page = null, $size = null) {
		$mission = $this->escape($mission);
		$result = new \stdClass;
		$q = [
			"*,'signin' type",
			'xxt_signin',
			"state<>0 and mission_id='$mission'",
		];
		if (isset($aOptions['where'])) {
			foreach ($aOptions['where'] as $key => $value) {
				$key = $this->escape($key);
				$value = $this->escape($value);
				$q[2] .= " and " . $key . " = '" . $value . "'";
			}
		}
		if (!empty($aOptions['byTitle'])) {
			$q[2] .= " and title like '%" . $this->escape($aOptions['byTitle']) . "%'";
		}
		$q2['o'] = 'modify_at desc';
		if ($page && $size) {
			$q2['r']['o'] = ($page - 1) * $size;
			$q2['r']['l'] = $size;
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
	 * 返回和登记活动关联的签到活动
	 */
	public function &byEnrollApp($enrollAppId, $aOptions = []) {
		$fields = isset($aOptions['fields']) ? $aOptions['fields'] : '*';
		$cascaded = isset($aOptions['cascaded']) ? $aOptions['cascaded'] : 'Y';
		$mapRounds = isset($aOptions['mapRounds']) ? $aOptions['mapRounds'] : 'N';

		$q = [
			$fields,
			'xxt_signin',
			"state<>0 and enroll_app_id='" . $this->escape($enrollAppId) . "'",
		];
		$q2['o'] = 'create_at asc';

		$apps = $this->query_objs_ss($q, $q2);
		if (count($apps) && $cascaded === 'Y') {
			$modelRnd = \TMS_APP::M('matter\signin\round');
			foreach ($apps as &$app) {
				$aOptions = $mapRounds === 'Y' ? ['mapRounds' => 'Y'] : [];
				$rounds = $modelRnd->byApp($app->id, $aOptions);
				$app->rounds = $rounds;
			}
		}

		return $apps;
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

		$options = array('fields' => 'tags', 'cascaded' => 'N');
		$app = $this->byId($aid, $options);
		if (empty($app->tags)) {
			$this->update('xxt_signin', array('tags' => $tags), "id='$aid'");
		} else {
			$existent = explode(',', $app->tags);
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
				$this->update('xxt_signin', array('tags' => $updated), "id='$aid'");
			}
		}

		return true;
	}
	/**
	 * 活动报名名单
	 *
	 * 1、如果活动仅限会员报名，那么要叠加会员信息
	 * 2、如果报名的表单中有扩展信息，那么要提取扩展信息
	 *
	 * $mpid
	 * $aid
	 * $options
	 * --creater openid
	 * --visitor openid
	 * --page
	 * --size
	 * --rid 轮次id
	 * --kw 检索关键词
	 * --by 检索字段
	 *
	 *
	 * return
	 * [0] 数据列表
	 * [1] 数据总条数
	 * [2] 数据项的定义
	 */
	public function participants($siteId, $appId, $options = null, $criteria = null) {
		if ($options) {
			is_array($options) && $options = (object) $options;
		}

		$w = "state=1 and aid='$appId' and userid<>''";

		// 指定了登记记录过滤条件
		if (!empty($criteria->record)) {
			$whereByRecord = '';
			if (!empty($criteria->record->verified)) {
				$whereByRecord .= " and verified='{$criteria->record->verified}'";
			}
			$w .= $whereByRecord;
		}

		// 指定了记录标签
		if (!empty($criteria->tags)) {
			$whereByTag = '';
			foreach ($criteria->tags as $tag) {
				$whereByTag .= " and concat(',',tags,',') like '%,$tag,%'";
			}
			$w .= $whereByTag;
		}

		// 指定了登记数据过滤条件
		if (isset($criteria->data)) {
			$whereByData = '';
			foreach ($criteria->data as $k => $v) {
				if (!empty($v)) {
					$whereByData .= ' and (';
					$whereByData .= 'data like \'%"' . $k . '":"' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . '"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"%,' . $v . ',%"%\'';
					$whereByData .= ' or data like \'%"' . $k . '":"' . $v . ',%"%\'';
					$whereByData .= ')';
				}
			}
			$w .= $whereByData;
		}

		// 获得填写的登记数据
		$q = [
			'userid',
			"xxt_signin_record",
			$w,
		];
		$participants = $this->query_vals_ss($q);

		return $participants;
	}
	/**
	 *
	 */
	public function &opData(&$oApp, $onlyActiveRound = false) {
		$modelRnd = $this->model('matter\signin\round');
		$opData = [];

		if ($onlyActiveRound) {
			$oActiveRound = $modelRnd->getActive($oApp->siteid, $oApp->id, ['fields' => 'rid,title,start_at,end_at,late_at']);
			if ($oActiveRound) {
				$rounds = [$oActiveRound];
			}
		} else {
			$rounds = $modelRnd->byApp($oApp->id, ['fields' => 'rid,title,start_at,end_at,late_at']);
		}

		if (empty($rounds)) {
			return $opData;
		}
		if (!isset($oActiveRound)) {
			$oActiveRound = $modelRnd->getActive($oApp->siteid, $oApp->id, ['fields' => 'rid,title,start_at,end_at,late_at']);
		}

		foreach ($rounds as $oRound) {
			/* total */
			$q = [
				'count(*)',
				'xxt_signin_log',
				['aid' => $oApp->id, 'state' => 1, 'rid' => $oRound->rid],
			];
			$oRound->total = $this->query_val_ss($q);
			/* late */
			if ($oRound->total) {
				if ($oRound->late_at) {
					$q = [
						'count(*)',
						'xxt_signin_log',
						"aid='" . $this->escape($oApp->id) . "' and rid='{$oRound->rid}' and state=1 and signin_at>" . ((int) $oRound->late_at + 59),
					];
					$oRound->late = $this->query_val_ss($q);
				} else {
					$oRound->late = 0;
				}
			} else {
				$oRound->late = 0;
			}
			if ($oActiveRound && $oRound->rid === $oActiveRound->rid) {
				$oRound->active = 'Y';
			}

			$opData[] = $oRound;
		}

		return $opData;
	}
	/**
	 * 指定用户的行为报告
	 */
	public function reportByUser($oApp, $oUser) {
		$modelRec = $this->model('matter\signin\record');

		$oRecord = $modelRec->byUser($oUser, $oApp, ['fields' => 'id,signin_num,signin_log']);
		if (false === $oRecord) {
			return false;
		}
		$result = new \stdClass;

		$result->signin_num = $oRecord->signin_num;

		$late_num = 0;
		if (!empty($oApp->rounds) && !empty($oRecord->signin_log)) {
			foreach ($oApp->rounds as $oRound) {
				if (isset($oRecord->signin_log->{$oRound->rid})) {
					if ($oRound->late_at) {
						if ($oRecord->signin_log->{$oRound->rid} > $oRound->late_at + 60) {
							$late_num++;
						}
					}
				}

			}
		}
		if ($late_num) {
			$result->late_num = $late_num;
		}

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

		return $nickname;
	}
	/**
	 * 创建一个空的签到活动
	 *
	 * @param string $site site's id
	 * @param string $mission mission's id
	 *
	 */
	public function createByMission($oUser, $oSite, $oMission, $template = 'basic', $oCustomConfig = null) {
		/* 模板信息 */
		$templateDir = TMS_APP_TEMPLATE . '/pl/fe/matter/signin/' . $template;
		$oTemplateConfig = file_get_contents($templateDir . '/config.json');
		$oTemplateConfig = preg_replace('/\t|\r|\n/', '', $oTemplateConfig);
		$oTemplateConfig = json_decode($oTemplateConfig);
		if (JSON_ERROR_NONE !== json_last_error()) {
			return new \ResponseError('解析模板数据错误：' . json_last_error_msg());
		}
		if (empty($oTemplateConfig->entryRule)) {
			return new \ResponseError('没有获得页面进入规则');
		}

		$oNewApp = new \stdClass;
		if (!empty($oTemplateConfig->schema)) {
			$oNewApp->data_schemas = $this->toJson($oTemplateConfig->schema);
		}

		$current = time();
		$appId = uniqid();

		/* 从项目中获得定义 */
		$title = empty($oCustomConfig->proto->title) ? '新签到活动' : $this->escape($oCustomConfig->proto->title);
		$oNewApp->title = $title;
		$oNewApp->summary = $this->escape($oMission->summary);
		$oNewApp->pic = $oMission->pic;
		$oNewApp->mission_id = $oMission->id;
		$oNewApp->use_mission_header = 'Y';
		$oNewApp->use_mission_footer = 'Y';

		/* 进入规则 */
		$oEntryRule = $oTemplateConfig->entryRule;
		$oMisEntryRule = $oMission->entry_rule;
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
		/* 关联的报名名单 */
		if (isset($oCustomConfig->proto->enrollApp)) {
			$oNewApp->enroll_app_id = $oCustomConfig->proto->enrollApp->id;
		}

		/*create app*/
		$oNewApp->siteid = $oSite->id;
		$oNewApp->id = $appId;
		$oNewApp->creater = $oUser->id;
		$oNewApp->creater_src = $oUser->src;
		$oNewApp->creater_name = $this->escape($oUser->name);
		$oNewApp->create_at = $current;
		$oNewApp->start_at = $current;
		$oNewApp->modifier = $oUser->id;
		$oNewApp->modifier_src = $oUser->src;
		$oNewApp->modifier_name = $this->escape($oUser->name);
		$oNewApp->modify_at = $current;
		$oNewApp->entry_rule = $this->toJson($oEntryRule);

		/*任务码*/
		$entryUrl = $this->getOpUrl($oSite->id, $appId);
		$code = $this->model('q\url')->add($oUser, $oSite->id, $entryUrl, $oNewApp->title);
		$oNewApp->op_short_url_code = $code;

		$this->insert('xxt_signin', $oNewApp, false);
		$oNewApp->type = 'signin';

		/* 记录和任务的关系 */
		$this->model('matter\mission')->addMatter($oUser, $oSite->id, $oMission->id, $oNewApp);
		/* 创建缺省页面 */
		$this->_addPageByTemplate($oUser, $oSite->id, $oNewApp, $oTemplateConfig);
		/* 创建缺省轮次 */
		$this->_addFirstRound($oUser, $oSite->id, $oNewApp);

		/* 记录操作日志 */
		$this->model('matter\log')->matterOp($oSite->id, $oUser, $oNewApp, 'C');

		return $oNewApp;
	}
	/**
	 * 根据模板生成页面
	 *
	 * @param string $app
	 * @param string $scenario scenario's name
	 * @param string $template template's name
	 */
	private function &_addPageByTemplate(&$oUser, $siteId, &$app, &$templateConfig) {
		$pages = $templateConfig->pages;
		if (empty($pages)) {
			return false;
		}
		/* 创建页面 */
		$templateDir = TMS_APP_TEMPLATE . $templateConfig->path;
		$modelPage = $this->model('matter\signin\page');
		$modelCode = $this->model('code\page');
		foreach ($pages as $page) {
			$ap = $modelPage->add($oUser, $siteId, $app->id, $page);
			$data = [
				'html' => file_get_contents($templateDir . '/' . $page->name . '.html'),
				'css' => file_get_contents($templateDir . '/' . $page->name . '.css'),
				'js' => file_get_contents($templateDir . '/' . $page->name . '.js'),
			];
			$modelCode->modify($ap->code_id, $data);
			/*页面关联的定义*/
			$pageSchemas = [];
			$pageSchemas['data_schemas'] = isset($page->data_schemas) ? \TMS_MODEL::toJson($page->data_schemas) : '[]';
			$pageSchemas['act_schemas'] = isset($page->act_schemas) ? \TMS_MODEL::toJson($page->act_schemas) : '[]';
			$rst = $modelPage->update(
				'xxt_signin_page',
				$pageSchemas,
				"aid='{$app->id}' and id={$ap->id}"
			);
		}

		return $pages;
	}
	/**
	 * 添加第一个轮次
	 *
	 * @param string $app
	 */
	private function &_addFirstRound(&$oUser, $siteId, &$app) {
		$modelRnd = $this->model('matter\signin\round');

		$roundId = uniqid();
		$round = [
			'siteid' => $siteId,
			'aid' => $app->id,
			'rid' => $roundId,
			'creater' => $oUser->id,
			'create_at' => time(),
			'title' => '第1轮',
			'state' => 1,
		];

		$modelRnd->insert('xxt_signin_round', $round, false);

		$round = (object) $round;

		return $round;
	}
}